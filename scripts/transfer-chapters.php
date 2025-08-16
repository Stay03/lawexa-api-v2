<?php

/**
 * Chapter Transfer Script
 * Transfers statute chapters from old_database.sqlite to database.sqlite as statute divisions
 * Only transfers simple single division patterns (Part 1, Chapter 1, Order 1, etc.)
 * Excludes complex patterns like "Part 2 Chapter 7"
 * 
 * Usage:
 * php transfer-chapters.php [--dry-run] [--skip-backup] [--verbose] [--default-user=ID]
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
use App\Models\StatuteDivision;

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_chapters_' . date('Y-m-d_H-i-s') . '.sqlite';
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

echo "=== CHAPTER TO DIVISION TRANSFER SCRIPT ===\n";
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

// Helper function to check if chapter number is a simple single division
function isSimpleSingleDivision($number) {
    // Match patterns like: Part 1, Part I, Chapter 1, Chapter I, Order 1, Order I, Schedule, Schedule 1
    // But exclude complex patterns like: Part 2 Chapter 7, Chapter 1 Section 2, etc.
    // Also exclude bare numbers (0, 1, 2, etc.) for now - they need special handling
    
    $patterns = [
        '/^Part\s+[0-9]+$/i',           // Part 1, Part 2, etc.
        '/^Part\s+[IVX]+$/i',           // Part I, Part II, Part III, etc.
        '/^PART\s+[0-9]+$/i',           // PART 1, PART 2, etc.
        '/^PART\s+[IVX]+$/i',           // PART I, PART II, etc.
        '/^Chapter\s+[0-9]+$/i',        // Chapter 1, Chapter 2, etc.
        '/^Chapter\s+[IVX]+$/i',        // Chapter I, Chapter II, etc.
        '/^Order\s+[0-9]+$/i',          // Order 1, Order 2, etc.
        '/^Order\s+[IVX]+$/i',          // Order I, Order II, etc.
        '/^Schedule$/i',                // Just "Schedule"
        '/^Schedule\s+[0-9]+$/i',       // Schedule 1, Schedule 2, etc.
        '/^Schedule\s+[IVX]+$/i',       // Schedule I, Schedule II, etc.
        '/^First\s+Schedule$/i',        // First Schedule
        '/^Second\s+Schedule$/i',       // Second Schedule
        '/^Third\s+Schedule$/i'         // Third Schedule
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, trim($number))) {
            return true;
        }
    }
    
    return false;
}

// Helper function to extract division type and number from chapter number
function parseDivisionInfo($number) {
    $number = trim($number);
    
    if (preg_match('/^(Part|PART)\s+(.+)$/i', $number, $matches)) {
        return ['type' => 'part', 'number' => $matches[2]];
    } elseif (preg_match('/^Chapter\s+(.+)$/i', $number, $matches)) {
        return ['type' => 'chapter', 'number' => $matches[1]];
    } elseif (preg_match('/^Order\s+(.+)$/i', $number, $matches)) {
        return ['type' => 'order', 'number' => $matches[1]];  // Updated to use 'order' type
    } elseif (preg_match('/^Schedule\s*(.*)$/i', $number, $matches)) {
        $scheduleNumber = trim($matches[1]);
        return ['type' => 'schedule', 'number' => $scheduleNumber ?: '1'];
    } elseif (preg_match('/^(First|Second|Third)\s+Schedule$/i', $number, $matches)) {
        $ordinals = ['First' => '1', 'Second' => '2', 'Third' => '3'];
        $scheduleNumber = $ordinals[ucfirst(strtolower($matches[1]))] ?? '1';
        return ['type' => 'schedule', 'number' => $scheduleNumber];
    }
    
    // Default fallback (should not reach here with proper filtering)
    return ['type' => 'chapter', 'number' => $number];
}

// Helper function to generate slug
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
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
    $oldTableCheck = $oldDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='statute_chapters'");
    if (!$oldTableCheck->fetchColumn()) {
        die("Error: 'statute_chapters' table not found in old database\n");
    }
    
    $newTableCheck = $newDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='statute_divisions'");
    if (!$newTableCheck->fetchColumn()) {
        die("Error: 'statute_divisions' table not found in new database\n");
    }
    
    // Get source data count
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM statute_chapters")->fetchColumn();
    echo "Found $sourceCount total chapters in old database\n";
    
    // Count chapters that match our filtering criteria
    $filteredStmt = $oldDb->prepare("SELECT * FROM statute_chapters ORDER BY id");
    $filteredStmt->execute();
    $filteredCount = 0;
    $sampleFiltered = [];
    
    while ($chapter = $filteredStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isSimpleSingleDivision($chapter['number'])) {
            $filteredCount++;
            if (count($sampleFiltered) < 5) {
                $sampleFiltered[] = $chapter;
            }
        }
    }
    
    echo "Found $filteredCount chapters matching filter criteria (simple single divisions)\n";
    
    if ($filteredCount == 0) {
        die("No chapters found matching filter criteria\n");
    }
    
    // Check for existing data in new database
    $existingCount = $newDb->query("SELECT COUNT(*) FROM statute_divisions")->fetchColumn();
    if ($existingCount > 0) {
        echo "Warning: New database already contains $existingCount statute divisions\n";
        echo "This transfer will add to existing data\n";
    }
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        
        echo "\n=== FILTERING EXAMPLES ===\n";
        echo "Sample chapters that WILL be transferred:\n";
        foreach ($sampleFiltered as $chapter) {
            $divInfo = parseDivisionInfo($chapter['number']);
            echo "  ✓ Number: '{$chapter['number']}' → Type: {$divInfo['type']}, Number: {$divInfo['number']}\n";
            echo "    Title: {$chapter['title']}\n";
        }
        
        echo "\nSample chapters that will be EXCLUDED:\n";
        $excludedStmt = $oldDb->prepare("
            SELECT number, title FROM statute_chapters 
            WHERE number LIKE '%Chapter%Part%' 
               OR number LIKE '%Part%Chapter%'
               OR number LIKE '%Section%'
               OR number IN ('0', '1', '2', '3', '-', 'X', 'O')
            LIMIT 5
        ");
        $excludedStmt->execute();
        while ($row = $excludedStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ✗ Number: '{$row['number']}' (complex pattern)\n";
            echo "    Title: {$row['title']}\n";
        }
        
        echo "\n=== TRANSFER PLAN ===\n";
        echo "Will transfer $filteredCount chapters from old database\n";
        echo "Field mappings:\n";
        echo "  old.statute_id → find matching statute by title → new.statute_id\n";
        echo "  old.number → parse → new.division_type + new.division_number\n";
        echo "  old.title → new.division_title\n";
        echo "  old.range → new.range\n";
        echo "  [added] status='active', level=1, sort_order=0\n";
        
        echo "\nDivision type mappings:\n";
        echo "  'Part X' → division_type='part'\n";
        echo "  'Chapter X' → division_type='chapter'\n";
        echo "  'Order X' → division_type='order'\n";
        echo "  'Schedule X' → division_type='schedule'\n";
        echo "  'First/Second/Third Schedule' → division_type='schedule'\n";
        
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
        $notFoundCount = 0;
        
        // Transfer chapters using Eloquent
        echo "Transferring filtered chapters...\n";
        $chapterStmt = $oldDb->prepare("SELECT * FROM statute_chapters ORDER BY id");
        $chapterStmt->execute();
        
        while ($oldChapter = $chapterStmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                // Apply filter
                if (!isSimpleSingleDivision($oldChapter['number'])) {
                    if ($options['verbose']) {
                        echo "  - Filtered out: '{$oldChapter['number']}' (complex pattern)\n";
                    }
                    continue;
                }
                
                // Find old statute
                $oldStatuteStmt = $oldDb->prepare("SELECT title FROM statutes WHERE id = ?");
                $oldStatuteStmt->execute([$oldChapter['statute_id']]);
                $oldStatute = $oldStatuteStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$oldStatute) {
                    $notFoundCount++;
                    if ($options['verbose']) {
                        echo "  - Skipped: Chapter ID {$oldChapter['id']} (old statute not found)\n";
                    }
                    continue;
                }
                
                // Find matching statute in new database (with retry)
                $newStatute = executeWithRetry(function() use ($oldStatute) {
                    return Statute::where('title', $oldStatute['title'])->first();
                }, "finding statute '{$oldStatute['title']}'", $maxRetries, $retryDelay);
                
                if (!$newStatute) {
                    $notFoundCount++;
                    if ($options['verbose']) {
                        echo "  - Skipped: '{$oldStatute['title']}' (new statute not found)\n";
                    }
                    continue;
                }
                
                // Parse division info
                $divInfo = parseDivisionInfo($oldChapter['number']);
                
                // Check if division already exists (with retry)
                $existingDivision = executeWithRetry(function() use ($newStatute, $divInfo, $oldChapter) {
                    return StatuteDivision::where('statute_id', $newStatute->id)
                        ->where('division_type', $divInfo['type'])
                        ->where('division_number', $divInfo['number'])
                        ->where('division_title', $oldChapter['title'])
                        ->first();
                }, "checking existing division", $maxRetries, $retryDelay);
                
                if ($existingDivision) {
                    $skippedCount++;
                    if ($options['verbose']) {
                        echo "  - Skipped: '{$oldChapter['title']}' (already exists)\n";
                    }
                    continue;
                }
                
                // Create division using Eloquent model with retry logic
                $division = executeWithRetry(function() use ($newStatute, $divInfo, $oldChapter, $defaultUserId) {
                    $slug = generateSlug($oldChapter['title']);
                    
                    return StatuteDivision::create([
                        'slug' => $slug,
                        'statute_id' => $newStatute->id,
                        'division_type' => $divInfo['type'],
                        'division_number' => $divInfo['number'],
                        'division_title' => $oldChapter['title'],
                        'range' => $oldChapter['range'] ?? null,
                        'status' => 'active',
                        'level' => 1,
                        'sort_order' => 0
                    ]);
                }, "chapter ID {$oldChapter['id']}", $maxRetries, $retryDelay);
                
                $transferredCount++;
                
                if ($options['verbose']) {
                    echo "  ✓ Division: {$oldChapter['title']} (old chapter ID: {$oldChapter['id']} → new division ID: {$division->id})\n";
                    echo "    Type: {$divInfo['type']}, Number: {$divInfo['number']}, Statute: {$newStatute->title}\n";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ✗ Error transferring chapter ID {$oldChapter['id']}: " . $e->getMessage() . "\n";
                if ($errorCount > 10) {
                    throw new Exception("Too many errors encountered");
                }
            }
        }
        
        // Commit transaction
        $newDb->commit();
        
        echo "\n=== TRANSFER COMPLETE ===\n";
        echo "Successfully transferred: $transferredCount divisions\n";
        echo "Skipped (duplicates): $skippedCount divisions\n";
        echo "Skipped (not found): $notFoundCount divisions\n";
        echo "Errors encountered: $errorCount\n";
        
        // Verification
        echo "\n=== VERIFICATION ===\n";
        $finalDivisionCount = $newDb->query("SELECT COUNT(*) FROM statute_divisions")->fetchColumn();
        echo "Total divisions in new database: $finalDivisionCount\n";
        
        // Sample verification
        echo "\n=== SAMPLE VERIFICATION ===\n";
        $verifyStmt = $newDb->query("
            SELECT sd.division_title, sd.id, sd.division_type, sd.division_number, s.title as statute_title
            FROM statute_divisions sd
            JOIN statutes s ON sd.statute_id = s.id
            ORDER BY sd.id DESC 
            LIMIT 5
        ");
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "✓ {$row['division_title']} (ID: {$row['id']}, Type: {$row['division_type']}, Number: {$row['division_number']})\n";
            echo "  Statute: {$row['statute_title']}\n";
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

echo "\nChapter transfer completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

echo "\nNote: This script transferred only simple single division chapters.\n";
echo "Complex patterns like 'Part 2 Chapter 7' were excluded as requested.\n";

?>