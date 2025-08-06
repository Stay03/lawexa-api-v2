<?php

/**
 * Statute Migration Script (Lock-Safe Eloquent Version)
 * Handles SQLite database locks gracefully with retry logic
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

// Import Eloquent models
use App\Models\Statute;
use App\Models\StatuteDivision;  
use App\Models\StatuteProvision;

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_statutes_migration_' . date('Y-m-d_H-i-s') . '.sqlite';
$maxRetries = 3;
$retryDelay = 2; // seconds
$defaultUserId = 1;

// Pattern configuration (same as original)
$patterns = [
    'part-section-subsection' => [
        'name' => 'Federal Act Pattern (Parts → Sections → Subsections)',
        'description' => 'Nigerian Federal Acts like ACJA 2015',
        'divisions' => [
            'source_table' => 'statute_chapters',
            'division_type' => 'part',
            'level' => 1,
            'title_field' => 'title',
            'number_field' => 'number',
            'content_field' => null,
            'foreign_key' => 'statute_id'
        ],
        'provisions' => [
            'source_table' => 'statute_sections', 
            'provision_type' => 'section',
            'level' => 2,
            'title_field' => 'section_title',
            'number_field' => 'section_number', 
            'text_field' => null,
            'foreign_key' => 'statute_chapter_id',
            'statute_foreign_key' => null
        ],
        'child_provisions' => [
            'source_table' => 'sub_sections',
            'provision_type' => 'subsection', 
            'level' => 3,
            'number_field' => 'number',
            'text_field' => 'body',
            'foreign_key' => 'statute_section_id',
            'statute_foreign_key' => null
        ]
    ]
];

// Parse command line arguments
$options = [
    'dry-run' => false,
    'verbose' => false,
    'pattern' => 'part-section-subsection',
    'statute-id' => null,
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (strpos($arg, '--statute-id=') === 0) {
        $options['statute-id'] = (int)substr($arg, 13);
    }
}

$pattern = $patterns[$options['pattern']];

echo "=== LOCK-SAFE STATUTE MIGRATION SCRIPT ===\n";
echo "Pattern: {$options['pattern']} - {$pattern['name']}\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION") . "\n";
if ($options['statute-id']) {
    echo "Target Statute ID: {$options['statute-id']}\n";
}
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

try {
    // Connect to old database
    echo "Connecting to old database...\n";
    $oldDb = new PDO('sqlite:' . $oldDbPath);
    $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get source data
    $statuteFilter = $options['statute-id'] ? "WHERE id = {$options['statute-id']}" : "";
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM statutes $statuteFilter")->fetchColumn();
    echo "Found $sourceCount statutes to migrate\n\n";
    
    if ($options['dry-run']) {
        echo "=== DRY RUN COMPLETE ===\n";
        echo "Would migrate $sourceCount statutes using Eloquent models with automatic slug generation.\n";
        exit(0);
    }
    
    // Start migration with individual record processing
    echo "=== STARTING SAFE MIGRATION ===\n";
    $migratedCounts = ['statutes' => 0, 'divisions' => 0, 'provisions' => 0, 'child_provisions' => 0];
    $idMaps = ['statutes' => [], 'divisions' => [], 'provisions' => []];
    
    // Phase 1: Migrate Statutes one by one
    echo "Phase 1: Migrating statutes (with retry logic)...\n";
    $statuteStmt = $oldDb->prepare("SELECT * FROM statutes $statuteFilter ORDER BY id");
    $statuteStmt->execute();
    
    while ($oldStatute = $statuteStmt->fetch(PDO::FETCH_ASSOC)) {
        $result = executeWithRetry(function() use ($oldStatute, $defaultUserId) {
            return Statute::create([
                'title' => $oldStatute['title'],
                'description' => $oldStatute['description'] ?? '',
                'jurisdiction' => 'Federal',
                'country' => $oldStatute['country'] ?? 'Nigeria',
                'created_by' => $defaultUserId,
                'status' => 'active',
            ]);
        }, "statute ID {$oldStatute['id']}", $maxRetries, $retryDelay);
        
        if ($result) {
            $idMaps['statutes'][$oldStatute['id']] = $result->id;
            $migratedCounts['statutes']++;
            
            if ($options['verbose']) {
                echo "  ✓ Statute: {$oldStatute['title']} (old ID: {$oldStatute['id']} → new ID: {$result->id}) [slug: {$result->slug}]\n";
            }
        }
    }
    
    echo "Phase 1 complete: {$migratedCounts['statutes']} statutes migrated\n\n";
    
    // Phase 2: Migrate Divisions
    if (isset($pattern['divisions']) && $migratedCounts['statutes'] > 0) {
        echo "Phase 2: Migrating divisions...\n";
        
        $divisionConfig = $pattern['divisions'];
        $divisionStmt = $oldDb->prepare("SELECT * FROM {$divisionConfig['source_table']} ORDER BY id");
        $divisionStmt->execute();
        
        while ($oldDivision = $divisionStmt->fetch(PDO::FETCH_ASSOC)) {
            $oldStatuteId = $oldDivision[$divisionConfig['foreign_key']];
            if (!isset($idMaps['statutes'][$oldStatuteId])) {
                continue;
            }
            
            $result = executeWithRetry(function() use ($oldDivision, $divisionConfig, $idMaps) {
                return StatuteDivision::create([
                    'statute_id' => $idMaps['statutes'][$oldDivision[$divisionConfig['foreign_key']]],
                    'division_type' => $divisionConfig['division_type'],
                    'division_number' => $oldDivision[$divisionConfig['number_field']] ?? '',
                    'division_title' => $oldDivision[$divisionConfig['title_field']] ?? '',
                    'content' => $oldDivision[$divisionConfig['content_field']] ?? '',
                    'sort_order' => $oldDivision['sort_order'] ?? 0,
                    'level' => $divisionConfig['level'],
                    'status' => 'active',
                ]);
            }, "division ID {$oldDivision['id']}", $maxRetries, $retryDelay);
            
            if ($result) {
                $idMaps['divisions'][$oldDivision['id']] = $result->id;
                $migratedCounts['divisions']++;
                
                if ($options['verbose']) {
                    $title = $oldDivision[$divisionConfig['title_field']] ?? 'Untitled';
                    echo "  ✓ Division: $title (old ID: {$oldDivision['id']} → new ID: {$result->id}) [slug: {$result->slug}]\n";
                }
            }
        }
        
        echo "Phase 2 complete: {$migratedCounts['divisions']} divisions migrated\n\n";
    }
    
    echo "=== MIGRATION COMPLETE ===\n";
    echo "Successfully migrated:\n";
    echo "  Statutes: {$migratedCounts['statutes']}\n";
    echo "  Divisions: {$migratedCounts['divisions']}\n";
    echo "\nNote: This safe version migrates statutes and divisions only to avoid lock issues.\n";
    echo "Run provisions migration separately if needed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>