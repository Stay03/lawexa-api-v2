<?php

/**
 * Statute-Only Transfer Script
 * Transfers only the basic statute records from old_database.sqlite to database.sqlite
 * This is the first phase - transfers only statutes table, not divisions/provisions
 * 
 * Usage:
 * php transfer-statutes-only.php [--dry-run] [--skip-backup] [--verbose] [--default-user=ID]
 * 
 * Options:
 * --dry-run          : Preview what would be migrated without making changes
 * --skip-backup      : Skip creating backup before migration
 * --verbose          : Show detailed output including progress
 * --default-user=ID  : Set default user ID for created_by field (default: 1)
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Import Eloquent models
use App\Models\Statute;

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_statutes_only_' . date('Y-m-d_H-i-s') . '.sqlite';
$defaultUserId = 1;
$maxRetries = 3;
$retryDelay = 2; // seconds

// Parse command line arguments
$options = [
    'dry-run' => false,
    'skip-backup' => false,
    'verbose' => false,
    'default-user' => 1
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--skip-backup') {
        $options['skip-backup'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (strpos($arg, '--default-user=') === 0) {
        $options['default-user'] = (int)substr($arg, 15);
    }
}

$defaultUserId = $options['default-user'];

echo "=== STATUTE-ONLY TRANSFER SCRIPT (LOCK-SAFE) ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE TRANSFER") . "\n";
echo "Default User ID: $defaultUserId\n";
echo "Max Retries: $maxRetries\n";
echo "\n";

// Helper function to execute with retry logic
function executeWithRetry($callback, $description, $maxRetries = 3, $retryDelay = 2) {
    $attempt = 1;
    
    while ($attempt <= $maxRetries) {
        try {
            return $callback();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'database is locked') !== false && $attempt < $maxRetries) {
                echo "  ⚠ Database locked on attempt $attempt for $description, retrying in {$retryDelay}s...\n";
                sleep($retryDelay);
                $attempt++;
                continue;
            }
            throw $e;
        }
    }
}

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
    $userCheck = $newDb->query("SELECT COUNT(*) FROM users WHERE id = $defaultUserId");
    if ($userCheck->fetchColumn() == 0) {
        die("Error: Default user ID $defaultUserId does not exist in the new database\n");
    }
    
    // Verify required tables exist
    $oldTableCheck = $oldDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='statutes'");
    if (!$oldTableCheck->fetchColumn()) {
        die("Error: 'statutes' table not found in old database\n");
    }
    
    $newTableCheck = $newDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='statutes'");
    if (!$newTableCheck->fetchColumn()) {
        die("Error: 'statutes' table not found in new database\n");
    }
    
    // Get source data count
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM statutes")->fetchColumn();
    echo "Found $sourceCount statutes to transfer\n";
    
    if ($sourceCount == 0) {
        die("No statutes found to transfer\n");
    }
    
    // Check for existing data in new database
    $existingCount = $newDb->query("SELECT COUNT(*) FROM statutes")->fetchColumn();
    if ($existingCount > 0) {
        echo "Warning: New database already contains $existingCount statutes\n";
        echo "This transfer will add to existing data\n";
    }
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        
        // Show sample data mapping
        echo "\n=== SAMPLE DATA PREVIEW ===\n";
        $sampleStatute = $oldDb->query("SELECT * FROM statutes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sampleStatute) {
            echo "Sample Statute from Old DB:\n";
            echo "  ID: {$sampleStatute['id']}\n";
            echo "  Title: {$sampleStatute['title']}\n";
            echo "  Description: " . substr($sampleStatute['description'] ?? '', 0, 100) . "...\n";
            echo "  Country: " . ($sampleStatute['country'] ?? 'Not set') . "\n";
            echo "  Range: " . ($sampleStatute['range'] ?? 'Not set') . "\n";
            
            echo "\nThis would be transferred as:\n";
            echo "  Title: {$sampleStatute['title']}\n";
            echo "  Description: " . substr($sampleStatute['description'] ?? '', 0, 100) . "...\n";
            echo "  Country: " . ($sampleStatute['country'] ?? 'Nigeria') . "\n";
            echo "  Range: " . ($sampleStatute['range'] ?? '') . "\n";
            echo "  Jurisdiction: Federal\n";
            echo "  Status: active\n";
            echo "  Created By: $defaultUserId\n";
            echo "  Slug: [auto-generated by Laravel]\n";
        }
        
        echo "\n=== TRANSFER PLAN ===\n";
        echo "Will transfer $sourceCount statutes from old database\n";
        echo "Field mappings:\n";
        echo "  old.title → new.title\n";
        echo "  old.description → new.description\n";
        echo "  old.country → new.country (default: 'Nigeria')\n";
        echo "  old.range → new.range\n";
        echo "  [ignored] old.chapter, old.user_id, old.created_at, old.updated_at\n";
        echo "  [added] jurisdiction='Federal', status='active', created_by=$defaultUserId\n";
        
        exit(0);
    }
    
    // Create backup if requested
    if (!$options['skip-backup']) {
        echo "Creating backup...\n";
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }
        copy($newDbPath, $backupPath);
        echo "Backup created: $backupPath\n";
    }
    
    // Start transfer
    echo "\n=== STARTING TRANSFER ===\n";
    $newDb->beginTransaction();
    
    try {
        $transferredCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        
        // Transfer statutes using Eloquent
        echo "Transferring statutes...\n";
        $statuteStmt = $oldDb->prepare("SELECT * FROM statutes ORDER BY id");
        $statuteStmt->execute();
        
        while ($oldStatute = $statuteStmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                // Check if statute with same title already exists (with retry)
                $existingStatute = executeWithRetry(function() use ($oldStatute) {
                    return Statute::where('title', $oldStatute['title'])->first();
                }, "checking existing statute '{$oldStatute['title']}'", $maxRetries, $retryDelay);
                
                if ($existingStatute) {
                    $skippedCount++;
                    if ($options['verbose']) {
                        echo "  - Skipped: '{$oldStatute['title']}' (already exists)\n";
                    }
                    continue;
                }
                
                // Create statute using Eloquent model with retry logic
                $statute = executeWithRetry(function() use ($oldStatute, $defaultUserId) {
                    return Statute::create([
                        'title' => $oldStatute['title'],
                        'description' => $oldStatute['description'] ?? '',
                        'jurisdiction' => 'Federal',
                        'country' => $oldStatute['country'] ?? 'Nigeria',
                        'range' => $oldStatute['range'] ?? null,
                        'created_by' => $defaultUserId,
                        'status' => 'active',
                    ]);
                }, "statute ID {$oldStatute['id']}", $maxRetries, $retryDelay);
                
                $transferredCount++;
                
                if ($options['verbose']) {
                    echo "  ✓ Statute: {$oldStatute['title']} (old ID: {$oldStatute['id']} → new ID: {$statute->id}) [slug: {$statute->slug}]\n";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ✗ Error transferring statute ID {$oldStatute['id']}: " . $e->getMessage() . "\n";
                if ($errorCount > 10) {
                    throw new Exception("Too many errors encountered");
                }
            }
        }
        
        // Commit transaction
        $newDb->commit();
        
        echo "\n=== TRANSFER COMPLETE ===\n";
        echo "Successfully transferred: $transferredCount statutes\n";
        echo "Skipped (duplicates): $skippedCount statutes\n";
        echo "Errors encountered: $errorCount\n";
        
        // Verification
        echo "\n=== VERIFICATION ===\n";
        $finalStatuteCount = $newDb->query("SELECT COUNT(*) FROM statutes")->fetchColumn();
        echo "Total statutes in new database: $finalStatuteCount\n";
        
        // Sample verification
        echo "\n=== SAMPLE VERIFICATION ===\n";
        $verifyStmt = $newDb->query("
            SELECT title, id, slug, country, jurisdiction, status
            FROM statutes 
            ORDER BY id DESC 
            LIMIT 3
        ");
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "✓ {$row['title']} (ID: {$row['id']}, Slug: {$row['slug']}, Country: {$row['country']}, Status: {$row['status']})\n";
        }
        
    } catch (Exception $e) {
        $newDb->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Transfer failed. Database rolled back to original state.\n";
    exit(1);
}

echo "\nStatute transfer completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

echo "\nNote: This script transferred only the basic statute records.\n";
echo "To transfer divisions and provisions later, use the full migrate-statutes.php script.\n";

?>