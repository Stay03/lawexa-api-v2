<?php

/**
 * Full Report Text Migration Script
 * Migrates full_report_text from old_database.sqlite to case_reports table in database.sqlite
 * 
 * Usage:
 * php migrate-full-reports.php [--dry-run] [--skip-backup]
 * 
 * Options:
 * --dry-run      : Preview what would be migrated without making changes
 * --skip-backup  : Skip creating backup before migration
 */

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_full_reports_migration_' . date('Y-m-d_H-i-s') . '.sqlite';
$logPath = __DIR__ . '/../database/backups/full_reports_migration_log_' . date('Y-m-d_H-i-s') . '.txt';
$batchSize = 100;

// Parse command line arguments
$options = [
    'dry-run' => false,
    'skip-backup' => false
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--skip-backup') {
        $options['skip-backup'] = true;
    }
}

// Initialize log file
function writeLog($message) {
    global $logPath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logPath, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

echo "=== FULL REPORT TEXT MIGRATION SCRIPT ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION") . "\n";
echo "Log File: $logPath\n\n";

writeLog("Starting full report text migration");
writeLog("Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION"));

// Verify files exist
if (!file_exists($oldDbPath)) {
    $error = "Error: Old database file not found: $oldDbPath";
    echo "$error\n";
    writeLog($error);
    die();
}

if (!file_exists($newDbPath)) {
    $error = "Error: New database file not found: $newDbPath";
    echo "$error\n";
    writeLog($error);
    die();
}

try {
    // Connect to databases
    $oldDb = new PDO("sqlite:$oldDbPath");
    $newDb = new PDO("sqlite:$newDbPath");
    
    $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to both databases\n";
    writeLog("Successfully connected to both databases");
    
    // Check if case_reports table exists in new database
    $tableCheck = $newDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='case_reports'");
    if (!$tableCheck->fetch()) {
        $error = "Error: case_reports table does not exist in new database. Run migration first: php artisan migrate";
        echo "$error\n";
        writeLog($error);
        die();
    }
    
    // Get count of cases with full_report_text from old database
    $oldCountStmt = $oldDb->query("SELECT COUNT(*) as count FROM my_cases WHERE full_report_text IS NOT NULL AND full_report_text != ''");
    $oldCount = $oldCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Found $oldCount cases with full_report_text in old database\n";
    writeLog("Found $oldCount cases with full_report_text in old database");
    
    if ($oldCount == 0) {
        echo "No cases with full_report_text found. Nothing to migrate.\n";
        writeLog("No cases with full_report_text found. Migration completed.");
        exit(0);
    }
    
    // Get count of existing case_reports in new database
    $newCountStmt = $newDb->query("SELECT COUNT(*) as count FROM case_reports");
    $existingCount = $newCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Found $existingCount existing case reports in new database\n";
    writeLog("Found $existingCount existing case reports in new database");
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        
        // Sample some cases to check title matching
        $sampleStmt = $oldDb->query("SELECT title, LENGTH(full_report_text) as text_length FROM my_cases WHERE full_report_text IS NOT NULL AND full_report_text != '' LIMIT 5");
        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample cases with full_report_text:\n";
        foreach ($samples as $sample) {
            $matchStmt = $newDb->prepare("SELECT id FROM court_cases WHERE title = ?");
            $matchStmt->execute([$sample['title']]);
            $match = $matchStmt->fetch();
            
            $status = $match ? "✓ Match found (ID: {$match['id']})" : "✗ No match";
            echo "  - Title: " . substr($sample['title'], 0, 50) . "... (Text: {$sample['text_length']} chars) - $status\n";
            
            writeLog("Sample: " . substr($sample['title'], 0, 50) . "... - $status");
        }
        
        echo "\n=== DRY RUN SUMMARY ===\n";
        echo "Would process: $oldCount cases\n";
        echo "Estimated batches: " . ceil($oldCount / $batchSize) . "\n";
        writeLog("Dry run completed. Would process $oldCount cases in " . ceil($oldCount / $batchSize) . " batches");
        exit(0);
    }
    
    // Create backup if not skipped
    if (!$options['skip-backup']) {
        echo "Creating backup of new database...\n";
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }
        if (!copy($newDbPath, $backupPath)) {
            $error = "Error: Failed to create backup";
            echo "$error\n";
            writeLog($error);
            die();
        }
        echo "✓ Backup created: $backupPath\n";
        writeLog("Backup created: $backupPath");
    }
    
    echo "\n=== STARTING MIGRATION ===\n";
    writeLog("Starting live migration");
    
    // Begin transaction
    $newDb->beginTransaction();
    
    $totalMigrated = 0;
    $totalSkipped = 0;
    $totalErrors = 0;
    $errorLimit = 10;
    
    // Prepare statements
    $selectStmt = $oldDb->prepare("SELECT title, full_report_text FROM my_cases WHERE full_report_text IS NOT NULL AND full_report_text != '' LIMIT ? OFFSET ?");
    $findCaseStmt = $newDb->prepare("SELECT id FROM court_cases WHERE title = ?");
    $insertReportStmt = $newDb->prepare("INSERT INTO case_reports (case_id, full_report_text, created_at, updated_at) VALUES (?, ?, datetime('now'), datetime('now'))");
    $existsStmt = $newDb->prepare("SELECT id FROM case_reports WHERE case_id = ?");
    
    $offset = 0;
    $batchNum = 1;
    $totalBatches = ceil($oldCount / $batchSize);
    
    while ($offset < $oldCount) {
        echo "Processing batch $batchNum/$totalBatches (offset: $offset)...\n";
        writeLog("Processing batch $batchNum/$totalBatches (offset: $offset)");
        
        $selectStmt->execute([$batchSize, $offset]);
        $cases = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cases)) {
            break;
        }
        
        foreach ($cases as $case) {
            try {
                // Find matching case in new database by title
                $findCaseStmt->execute([$case['title']]);
                $matchedCase = $findCaseStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$matchedCase) {
                    $totalSkipped++;
                    writeLog("SKIPPED: No matching case found for title: " . substr($case['title'], 0, 100));
                    continue;
                }
                
                $caseId = $matchedCase['id'];
                
                // Check if report already exists for this case
                $existsStmt->execute([$caseId]);
                if ($existsStmt->fetch()) {
                    $totalSkipped++;
                    writeLog("SKIPPED: Report already exists for case ID: $caseId");
                    continue;
                }
                
                // Insert the full report text
                $insertReportStmt->execute([$caseId, $case['full_report_text']]);
                $totalMigrated++;
                
                if ($totalMigrated % 50 == 0) {
                    echo "  Migrated: $totalMigrated cases...\n";
                }
                
            } catch (Exception $e) {
                $totalErrors++;
                $error = "Error migrating case '{$case['title']}': " . $e->getMessage();
                echo "  $error\n";
                writeLog("ERROR: $error");
                
                if ($totalErrors >= $errorLimit) {
                    throw new Exception("Too many errors encountered, stopping migration");
                }
            }
        }
        
        $offset += $batchSize;
        $batchNum++;
    }
    
    // Commit transaction
    $newDb->commit();
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    echo "Successfully migrated: $totalMigrated cases\n";
    echo "Skipped (no match/duplicate): $totalSkipped cases\n";
    echo "Errors encountered: $totalErrors cases\n";
    
    writeLog("Migration completed successfully");
    writeLog("Successfully migrated: $totalMigrated cases");
    writeLog("Skipped: $totalSkipped cases");
    writeLog("Errors: $totalErrors cases");
    
    // Final verification
    $finalCountStmt = $newDb->query("SELECT COUNT(*) as count FROM case_reports");
    $finalCount = $finalCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Total case reports in database: $finalCount\n";
    echo "✓ Migration completed successfully!\n";
    writeLog("Final count: $finalCount case reports in database");
    
    if ($totalErrors > 0) {
        echo "\nWarning: $totalErrors errors were encountered. Check the log file for details.\n";
    }
    
} catch (Exception $e) {
    if (isset($newDb) && $newDb->inTransaction()) {
        $newDb->rollback();
        echo "Transaction rolled back due to error.\n";
        writeLog("Transaction rolled back due to error");
    }
    
    $error = "Migration failed: " . $e->getMessage();
    echo "$error\n";
    writeLog("FATAL ERROR: $error");
    exit(1);
}

writeLog("Script execution completed");