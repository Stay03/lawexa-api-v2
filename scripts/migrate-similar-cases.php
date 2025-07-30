<?php

/**
 * Similar Cases Migration Script
 * Migrates similar cases from old_database.sqlite to database.sqlite with ID-based relationships
 * 
 * Usage:
 * php migrate-similar-cases.php [--dry-run] [--skip-backup] [--verbose]
 * 
 * Options:
 * --dry-run     : Preview what would be migrated without making changes
 * --skip-backup : Skip creating backup before migration  
 * --verbose     : Show detailed output including matched/unmatched cases
 */

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_similar_cases_migration_' . date('Y-m-d_H-i-s') . '.sqlite';
$batchSize = 100;

// Parse command line arguments
$options = [
    'dry-run' => false,
    'skip-backup' => false,
    'verbose' => false
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--skip-backup') {
        $options['skip-backup'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    }
}

echo "=== SIMILAR CASES MIGRATION SCRIPT ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION") . "\n";
echo "Verbose: " . ($options['verbose'] ? "ON" : "OFF") . "\n\n";

// Verify files exist
if (!file_exists($oldDbPath)) {
    die("Error: Old database file not found: $oldDbPath\n");
}

if (!file_exists($newDbPath)) {
    die("Error: New database file not found: $newDbPath\n");
}

try {
    // Connect to databases
    echo "Connecting to databases...\n";
    $oldDb = new PDO('sqlite:' . $oldDbPath);
    $newDb = new PDO('sqlite:' . $newDbPath);
    
    // Set error mode
    $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create backup if requested
    if (!$options['skip-backup'] && !$options['dry-run']) {
        echo "Creating backup...\n";
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }
        copy($newDbPath, $backupPath);
        echo "Backup created: $backupPath\n";
    }
    
    // Check if similar_cases table exists in old database
    $tableCheck = $oldDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='similar_cases'");
    if (!$tableCheck->fetchColumn()) {
        die("Error: 'similar_cases' table not found in old database\n");
    }
    
    // Check if similar_cases table exists in new database
    $newTableCheck = $newDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='similar_cases'");
    if (!$newTableCheck->fetchColumn()) {
        die("Error: 'similar_cases' table not found in new database. Please run migrations first.\n");
    }
    
    // Get source data count
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM similar_cases")->fetchColumn();
    echo "Found $sourceCount similar case relationships in old database\n";
    
    // Get existing data count  
    $existingCount = $newDb->query("SELECT COUNT(*) FROM similar_cases")->fetchColumn();
    echo "Found $existingCount existing similar case relationships in new database\n";
    
    // Build title-to-ID lookup map from new database
    echo "Building case title to ID lookup map...\n";
    $titleToIdMap = [];
    $slugToIdMap = [];
    $caseResults = $newDb->query("SELECT id, title, slug FROM court_cases");
    
    $caseCount = 0;
    while ($case = $caseResults->fetch(PDO::FETCH_ASSOC)) {
        $titleToIdMap[trim($case['title'])] = $case['id'];
        $slugToIdMap[trim($case['slug'])] = $case['id'];
        $caseCount++;
    }
    
    echo "Built lookup map for $caseCount cases\n";
    
    // Get sample of old similar_cases data
    $sampleOldData = $oldDb->query("SELECT * FROM similar_cases LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        echo "Would process: $sourceCount similar case relationships\n";
        echo "Batch size: $batchSize\n";
        echo "Estimated batches: " . ceil($sourceCount / $batchSize) . "\n";
        
        // Analyze sample data
        echo "\n=== SAMPLE DATA ANALYSIS ===\n";
        $matchCount = 0;
        $unmatchedTitles = [];
        $unmatchedTitleSyns = [];
        
        foreach ($sampleOldData as $sample) {
            $title = trim($sample['title'] ?? '');
            $titleSyn = trim($sample['title_syn'] ?? '');
            
            echo "Sample: '$title' <-> '$titleSyn'\n";
            
            $titleId = $titleToIdMap[$title] ?? $slugToIdMap[$title] ?? null;
            $titleSynId = $titleToIdMap[$titleSyn] ?? $slugToIdMap[$titleSyn] ?? null;
            
            if ($titleId && $titleSynId) {
                echo "  ✓ Both cases found: ID $titleId <-> ID $titleSynId\n";
                $matchCount++;
            } else {
                if (!$titleId) {
                    echo "  ✗ Title not found: '$title'\n";
                    $unmatchedTitles[] = $title;
                }
                if (!$titleSynId) {
                    echo "  ✗ Title_syn not found: '$titleSyn'\n";
                    $unmatchedTitleSyns[] = $titleSyn;
                }
            }
        }
        
        echo "\nSample match rate: $matchCount/" . count($sampleOldData) . "\n";
        
        if (!empty($unmatchedTitles)) {
            echo "\nUnmatched titles:\n";
            foreach (array_unique($unmatchedTitles) as $title) {
                echo "  - '$title'\n";
            }
        }
        
        if (!empty($unmatchedTitleSyns)) {
            echo "\nUnmatched title_syns:\n";
            foreach (array_unique($unmatchedTitleSyns) as $titleSyn) {
                echo "  - '$titleSyn'\n";
            }
        }
        
        exit(0);
    }
    
    // Start migration 
    echo "\n=== STARTING MIGRATION ===\n";
    $newDb->beginTransaction();
    
    try {
        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $unmatchedCount = 0;
        $unmatchedCases = [];
        
        // Prepare statements
        $selectStmt = $oldDb->prepare("
            SELECT * FROM similar_cases 
            ORDER BY rowid 
            LIMIT $batchSize OFFSET ?
        ");
        
        $checkExistingStmt = $newDb->prepare("
            SELECT COUNT(*) FROM similar_cases 
            WHERE (case_id = ? AND similar_case_id = ?) 
               OR (case_id = ? AND similar_case_id = ?)
        ");
        
        $insertStmt = $newDb->prepare("
            INSERT INTO similar_cases (case_id, similar_case_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?)
        ");
        
        // Process in batches
        $offset = 0;
        while ($offset < $sourceCount) {
            echo "Processing batch " . (floor($offset / $batchSize) + 1) . "/" . ceil($sourceCount / $batchSize) . " (offset: $offset)...\n";
            
            $selectStmt->execute([$offset]);
            $similarCases = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($similarCases as $similarCase) {
                try {
                    $title = trim($similarCase['title'] ?? '');
                    $titleSyn = trim($similarCase['title_syn'] ?? '');
                    
                    // Skip empty titles
                    if (empty($title) || empty($titleSyn)) {
                        $skippedCount++;
                        if ($options['verbose']) {
                            echo "  Skipping empty title: '$title' <-> '$titleSyn'\n";
                        }
                        continue;
                    }
                    
                    // Look up case IDs
                    $caseId = $titleToIdMap[$title] ?? $slugToIdMap[$title] ?? null;
                    $similarCaseId = $titleToIdMap[$titleSyn] ?? $slugToIdMap[$titleSyn] ?? null;
                    
                    if (!$caseId || !$similarCaseId) {
                        $unmatchedCount++;
                        $unmatchedCases[] = [
                            'title' => $title,
                            'title_syn' => $titleSyn,
                            'case_id_found' => $caseId ? 'YES' : 'NO',
                            'similar_case_id_found' => $similarCaseId ? 'YES' : 'NO'
                        ];
                        
                        if ($options['verbose']) {
                            echo "  Unmatched: '$title' " . ($caseId ? "✓" : "✗") . " <-> '$titleSyn' " . ($similarCaseId ? "✓" : "✗") . "\n";
                        }
                        continue;
                    }
                    
                    // Check if relationship already exists (bidirectional)
                    $checkExistingStmt->execute([$caseId, $similarCaseId, $similarCaseId, $caseId]);
                    if ($checkExistingStmt->fetchColumn() > 0) {
                        $skippedCount++;
                        if ($options['verbose']) {
                            echo "  Skipping duplicate: ID $caseId <-> ID $similarCaseId\n";
                        }
                        continue;
                    }
                    
                    // Insert the relationship
                    $now = date('Y-m-d H:i:s');
                    $insertStmt->execute([$caseId, $similarCaseId, $now, $now]);
                    
                    $migratedCount++;
                    
                    if ($options['verbose']) {
                        echo "  ✓ Migrated: '$title' (ID $caseId) <-> '$titleSyn' (ID $similarCaseId)\n";
                    }
                    
                    // Progress indicator
                    if ($migratedCount % 50 == 0) {
                        echo "  Migrated: $migratedCount relationships...\n";
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    echo "  Error migrating relationship: " . $e->getMessage() . "\n";
                    
                    // Stop if too many errors
                    if ($errorCount > 10) {
                        throw new Exception("Too many errors encountered, stopping migration");
                    }
                }
            }
            
            $offset += $batchSize;
        }
        
        // Commit transaction
        $newDb->commit();
        
        echo "\n=== MIGRATION COMPLETE ===\n";
        echo "Successfully migrated: $migratedCount relationships\n";
        echo "Skipped (duplicates/empty): $skippedCount relationships\n";
        echo "Unmatched cases: $unmatchedCount relationships\n";
        echo "Errors encountered: $errorCount relationships\n";
        
        // Verify final counts
        $finalCount = $newDb->query("SELECT COUNT(*) FROM similar_cases")->fetchColumn();
        echo "Total relationships in new database: $finalCount\n";
        echo "Expected total: " . ($existingCount + $migratedCount) . "\n";
        
        if ($finalCount == ($existingCount + $migratedCount)) {
            echo "✓ Data integrity check passed!\n";
        } else {
            echo "⚠ Data integrity check failed! Please review the migration.\n";
        }
        
        // Show unmatched cases summary
        if (!empty($unmatchedCases)) {
            echo "\n=== UNMATCHED CASES SUMMARY ===\n";
            echo "Total unmatched: " . count($unmatchedCases) . "\n";
            
            if (count($unmatchedCases) <= 20) {
                foreach ($unmatchedCases as $unmatched) {
                    echo "  '{$unmatched['title']}' ({$unmatched['case_id_found']}) <-> '{$unmatched['title_syn']}' ({$unmatched['similar_case_id_found']})\n";
                }
            } else {
                echo "First 20 unmatched cases:\n";
                for ($i = 0; $i < 20; $i++) {
                    $unmatched = $unmatchedCases[$i];
                    echo "  '{$unmatched['title']}' ({$unmatched['case_id_found']}) <-> '{$unmatched['title_syn']}' ({$unmatched['similar_case_id_found']})\n";
                }
                echo "  ... and " . (count($unmatchedCases) - 20) . " more\n";
            }
        }
        
        // Sample verification
        echo "\n=== VERIFICATION SAMPLE ===\n";
        $verifyStmt = $newDb->query("
            SELECT sc.case_id, sc.similar_case_id, cc1.title as case_title, cc2.title as similar_case_title
            FROM similar_cases sc
            JOIN court_cases cc1 ON sc.case_id = cc1.id
            JOIN court_cases cc2 ON sc.similar_case_id = cc2.id
            ORDER BY sc.id DESC
            LIMIT 3
        ");
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "✓ '{$row['case_title']}' (ID {$row['case_id']}) <-> '{$row['similar_case_title']}' (ID {$row['similar_case_id']})\n";
        }
        
    } catch (Exception $e) {
        $newDb->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. Database rolled back to original state.\n";
    exit(1);
}

echo "\nSimilar cases migration completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

?>