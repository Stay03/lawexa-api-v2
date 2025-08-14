<?php

/**
 * Direct Statute Transfer Script
 * Uses direct PDO operations to avoid Laravel database locks
 * Transfers only the basic statute records from old_database.sqlite to database.sqlite
 * 
 * Usage:
 * php transfer-statutes-direct.php [--dry-run] [--skip-backup] [--verbose]
 */

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_statutes_direct_' . date('Y-m-d_H-i-s') . '.sqlite';
$defaultUserId = 1;

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

echo "=== DIRECT STATUTE TRANSFER SCRIPT ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE TRANSFER") . "\n";
echo "Default User ID: $defaultUserId\n";
echo "\n";

// Helper function to generate slug
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Helper function to ensure unique slug
function ensureUniqueSlug($baseSlug, $pdo) {
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM statutes WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
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
    // Connect to databases with shorter timeout to avoid locks
    echo "Connecting to databases...\n";
    $oldDb = new PDO('sqlite:' . $oldDbPath);
    $newDb = new PDO('sqlite:' . $newDbPath);
    
    // Set error mode and timeout
    $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $newDb->setAttribute(PDO::ATTR_TIMEOUT, 10); // 10 second timeout
    
    // Set WAL mode to reduce locking (if supported)
    try {
        $newDb->exec("PRAGMA journal_mode=WAL");
        echo "Enabled WAL mode for better concurrency\n";
    } catch (Exception $e) {
        echo "Note: Could not enable WAL mode: " . $e->getMessage() . "\n";
    }
    
    // Verify default user exists
    $userCheck = $newDb->query("SELECT COUNT(*) FROM users WHERE id = $defaultUserId");
    if ($userCheck->fetchColumn() == 0) {
        die("Error: Default user ID $defaultUserId does not exist in the new database\n");
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
    }
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        
        $sampleStatute = $oldDb->query("SELECT * FROM statutes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sampleStatute) {
            echo "Sample Statute:\n";
            echo "  Title: {$sampleStatute['title']}\n";
            echo "  Will generate slug: " . generateSlug($sampleStatute['title']) . "\n";
        }
        
        echo "\nWould transfer $sourceCount statutes using direct PDO operations\n";
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
        
        // Prepare insert statement
        $insertStmt = $newDb->prepare("
            INSERT INTO statutes (
                slug, title, description, jurisdiction, country, range, 
                created_by, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Prepare check statement
        $checkStmt = $newDb->prepare("SELECT COUNT(*) FROM statutes WHERE title = ?");
        
        echo "Transferring statutes using direct PDO...\n";
        $statuteStmt = $oldDb->prepare("SELECT * FROM statutes ORDER BY id");
        $statuteStmt->execute();
        
        $timestamp = date('Y-m-d H:i:s');
        
        while ($oldStatute = $statuteStmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                // Check if statute with same title already exists
                $checkStmt->execute([$oldStatute['title']]);
                if ($checkStmt->fetchColumn() > 0) {
                    $skippedCount++;
                    if ($options['verbose']) {
                        echo "  - Skipped: '{$oldStatute['title']}' (already exists)\n";
                    }
                    continue;
                }
                
                // Generate unique slug
                $baseSlug = generateSlug($oldStatute['title']);
                $uniqueSlug = ensureUniqueSlug($baseSlug, $newDb);
                
                // Insert statute
                $insertStmt->execute([
                    $uniqueSlug,
                    $oldStatute['title'],
                    $oldStatute['description'] ?? '',
                    'Federal',
                    $oldStatute['country'] ?? 'Nigeria',
                    $oldStatute['range'] ?? null,
                    $defaultUserId,
                    'active',
                    $timestamp,
                    $timestamp
                ]);
                
                $newId = $newDb->lastInsertId();
                $transferredCount++;
                
                if ($options['verbose']) {
                    echo "  ✓ Statute: {$oldStatute['title']} (old ID: {$oldStatute['id']} → new ID: $newId) [slug: $uniqueSlug]\n";
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
            echo "✓ {$row['title']} (ID: {$row['id']}, Slug: {$row['slug']})\n";
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

echo "\nDirect statute transfer completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

echo "\nNote: This script used direct PDO operations to avoid database locks.\n";

?>