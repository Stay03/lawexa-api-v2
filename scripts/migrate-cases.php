<?php

/**
 * Case Migration Script
 * Migrates cases from old_database.sqlite (my_cases table) to database.sqlite (court_cases table)
 * 
 * Usage:
 * php migrate-cases.php [--dry-run] [--skip-backup] [--default-user=ID]
 * 
 * Options:
 * --dry-run        : Preview what would be migrated without making changes
 * --skip-backup    : Skip creating backup before migration
 * --default-user=ID: Set default user ID for created_by field (default: 1)
 */

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_migration_' . date('Y-m-d_H-i-s') . '.sqlite';
$batchSize = 100;
$defaultUserId = 1;

// Parse command line arguments
$options = [
    'dry-run' => false,
    'skip-backup' => false,
    'default-user' => 1
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--skip-backup') {
        $options['skip-backup'] = true;
    } elseif (strpos($arg, '--default-user=') === 0) {
        $options['default-user'] = (int)substr($arg, 15);
    }
}

echo "=== CASE MIGRATION SCRIPT ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION") . "\n";
echo "Default User ID: {$options['default-user']}\n\n";

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
    
    // Verify default user exists
    $userCheck = $newDb->query("SELECT COUNT(*) FROM users WHERE id = {$options['default-user']}");
    if ($userCheck->fetchColumn() == 0) {
        die("Error: Default user ID {$options['default-user']} does not exist in the new database\n");
    }
    
    // Create backup if requested
    if (!$options['skip-backup'] && !$options['dry-run']) {
        echo "Creating backup...\n";
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }
        copy($newDbPath, $backupPath);
        echo "Backup created: $backupPath\n";
    }
    
    // Get source data count
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM my_cases")->fetchColumn();
    echo "Found $sourceCount cases in old database\n";
    
    // Get existing data count
    $existingCount = $newDb->query("SELECT COUNT(*) FROM court_cases")->fetchColumn();
    echo "Found $existingCount existing cases in new database\n";
    
    // Check for potential slug conflicts
    echo "Checking for potential conflicts...\n";
    
    // Get all slugs from old database
    $oldSlugs = [];
    $oldSlugResult = $oldDb->query("SELECT DISTINCT slug FROM my_cases WHERE slug != ''");
    while ($row = $oldSlugResult->fetch()) {
        $oldSlugs[] = $row['slug'];
    }
    
    // Check conflicts in batches to avoid SQL length limits
    $conflicts = 0;
    $batchSlugSize = 100;
    for ($i = 0; $i < count($oldSlugs); $i += $batchSlugSize) {
        $batchSlugs = array_slice($oldSlugs, $i, $batchSlugSize);
        $placeholders = str_repeat('?,', count($batchSlugs) - 1) . '?';
        $stmt = $newDb->prepare("SELECT COUNT(*) FROM court_cases WHERE slug IN ($placeholders)");
        $stmt->execute($batchSlugs);
        $conflicts += $stmt->fetchColumn();
    }
    
    echo "Found $conflicts potential slug conflicts\n";
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN SUMMARY ===\n";
        echo "Would migrate: $sourceCount cases\n";
        echo "Potential conflicts: $conflicts cases\n";
        echo "Batch size: $batchSize\n";
        echo "Estimated batches: " . ceil($sourceCount / $batchSize) . "\n";
        
        // Show sample mapping
        echo "\n=== SAMPLE DATA MAPPING ===\n";
        $sample = $oldDb->query("SELECT * FROM my_cases LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sample) {
            echo "Sample case: {$sample['title']}\n";
            echo "Fields that will be mapped:\n";
            $fieldMapping = [
                'title' => 'title',
                'body' => 'body', 
                'report' => 'report',
                'course' => 'course',
                'topic' => 'topic',
                'tag' => 'tag',
                'principles' => 'principles',
                'level' => 'level',
                'slug' => 'slug',
                'court' => 'court',
                'date' => 'date',
                'country' => 'country',
                'citation' => 'citation',
                'judges' => 'judges',
                'judicial_precedent' => 'judicial_precedent',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at'
            ];
            
            foreach ($fieldMapping as $oldField => $newField) {
                $value = $sample[$oldField] ?? 'NULL';
                if (strlen($value) > 50) $value = substr($value, 0, 50) . '...';
                echo "  $oldField -> $newField: $value\n";
            }
            echo "  [NEW] created_by: {$options['default-user']}\n";
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
        
        // Prepare statements
        $selectStmt = $oldDb->prepare("
            SELECT * FROM my_cases 
            ORDER BY id 
            LIMIT $batchSize OFFSET ?
        ");
        
        $checkExistingStmt = $newDb->prepare("
            SELECT COUNT(*) FROM court_cases WHERE slug = ?
        ");
        
        $insertStmt = $newDb->prepare("
            INSERT INTO court_cases (
                title, body, report, course, topic, tag, principles, level, slug,
                court, date, country, citation, judges, judicial_precedent,
                created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Process in batches
        $offset = 0;
        while ($offset < $sourceCount) {
            echo "Processing batch " . (floor($offset / $batchSize) + 1) . "/" . ceil($sourceCount / $batchSize) . " (offset: $offset)...\n";
            
            $selectStmt->execute([$offset]);
            $cases = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($cases as $case) {
                try {
                    // Check if case already exists
                    $checkExistingStmt->execute([$case['slug']]);
                    if ($checkExistingStmt->fetchColumn() > 0) {
                        echo "  Skipping duplicate slug: {$case['slug']}\n";
                        $skippedCount++;
                        continue;
                    }
                    
                    // Insert case
                    $insertStmt->execute([
                        $case['title'],
                        $case['body'],
                        $case['report'],
                        $case['course'],
                        $case['topic'],
                        $case['tag'],
                        $case['principles'],
                        $case['level'],
                        $case['slug'],
                        $case['court'],
                        $case['date'],
                        $case['country'],
                        $case['citation'],
                        $case['judges'],
                        $case['judicial_precedent'],
                        $options['default-user'],
                        $case['created_at'] ?: date('Y-m-d H:i:s'),
                        $case['updated_at'] ?: date('Y-m-d H:i:s')
                    ]);
                    
                    $migratedCount++;
                    
                    // Progress indicator
                    if ($migratedCount % 50 == 0) {
                        echo "  Migrated: $migratedCount cases...\n";
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    echo "  Error migrating case ID {$case['id']}: " . $e->getMessage() . "\n";
                    
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
        echo "Successfully migrated: $migratedCount cases\n";
        echo "Skipped (duplicates): $skippedCount cases\n";
        echo "Errors encountered: $errorCount cases\n";
        
        // Verify final counts
        $finalCount = $newDb->query("SELECT COUNT(*) FROM court_cases")->fetchColumn();
        echo "Total cases in new database: $finalCount\n";
        echo "Expected total: " . ($existingCount + $migratedCount) . "\n";
        
        if ($finalCount == ($existingCount + $migratedCount)) {
            echo "✓ Data integrity check passed!\n";
        } else {
            echo "⚠ Data integrity check failed! Please review the migration.\n";
        }
        
        // Sample verification
        echo "\n=== VERIFICATION SAMPLE ===\n";
        $verifyStmt = $newDb->query("
            SELECT title, course, topic, created_by, created_at 
            FROM court_cases 
            WHERE created_by = {$options['default-user']} 
            ORDER BY id DESC 
            LIMIT 3
        ");
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "✓ {$row['title']} | {$row['course']} - {$row['topic']} | User: {$row['created_by']}\n";
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

echo "\nMigration completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

?>