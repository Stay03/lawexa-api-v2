<?php

/**
 * Statute Migration Script (Full Eloquent Version)
 * Migrates statutes from old_database.sqlite to database.sqlite using Laravel Eloquent models
 * All phases (statutes, divisions, provisions, child provisions) use Eloquent for automatic slug generation.
 * 
 * Usage:
 * php migrate-statutes.php [--dry-run] [--skip-backup] [--verbose] [--pattern=NAME] [--statute-id=ID] [--batch-size=N]
 * 
 * Options:
 * --dry-run          : Preview what would be migrated without making changes
 * --skip-backup      : Skip creating backup before migration
 * --verbose          : Show detailed output including progress
 * --pattern=NAME     : Migration pattern (default: part-section-subsection)
 * --statute-id=ID    : Migrate specific statute only (old database ID)
 * --batch-size=N     : Records per batch (default: 100)
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Import Eloquent models
use App\Models\Statute;
use App\Models\StatuteDivision;  
use App\Models\StatuteProvision;

// Configuration
$oldDbPath = __DIR__ . '/../database/old_database.sqlite';
$newDbPath = __DIR__ . '/../database/database.sqlite';
$backupPath = __DIR__ . '/../database/backups/backup_statutes_migration_' . date('Y-m-d_H-i-s') . '.sqlite';
$batchSize = 100;
$defaultUserId = 1;

// Pattern configurations
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
    'skip-backup' => false,
    'verbose' => false,
    'pattern' => 'part-section-subsection',
    'statute-id' => null,
    'batch-size' => $batchSize
];

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--skip-backup') {
        $options['skip-backup'] = true;
    } elseif ($arg === '--verbose') {
        $options['verbose'] = true;
    } elseif (strpos($arg, '--pattern=') === 0) {
        $options['pattern'] = substr($arg, 10);
    } elseif (strpos($arg, '--statute-id=') === 0) {
        $options['statute-id'] = (int)substr($arg, 13);
    } elseif (strpos($arg, '--batch-size=') === 0) {
        $options['batch-size'] = (int)substr($arg, 13);
    }
}

// Validate pattern
if (!isset($patterns[$options['pattern']])) {
    echo "Error: Unknown pattern '{$options['pattern']}'\n";
    echo "Available patterns:\n";
    foreach ($patterns as $key => $pattern) {
        echo "  $key: {$pattern['name']}\n";
    }
    exit(1);
}

$pattern = $patterns[$options['pattern']];

echo "=== STATUTE MIGRATION SCRIPT ===\n";
echo "Old DB: $oldDbPath\n";
echo "New DB: $newDbPath\n";
echo "Pattern: {$options['pattern']} - {$pattern['name']}\n";
echo "Mode: " . ($options['dry-run'] ? "DRY RUN" : "LIVE MIGRATION") . "\n";
echo "Batch Size: {$options['batch-size']}\n";
if ($options['statute-id']) {
    echo "Target Statute ID: {$options['statute-id']}\n";
}
echo "\n";

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
    
    // Verify required tables exist in old database
    $requiredTables = ['statutes'];
    if (isset($pattern['divisions'])) $requiredTables[] = $pattern['divisions']['source_table'];
    if (isset($pattern['provisions'])) $requiredTables[] = $pattern['provisions']['source_table'];
    if (isset($pattern['child_provisions'])) $requiredTables[] = $pattern['child_provisions']['source_table'];
    
    foreach ($requiredTables as $table) {
        $tableCheck = $oldDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if (!$tableCheck->fetchColumn()) {
            die("Error: Required table '$table' not found in old database\n");
        }
    }
    
    // Get source data counts
    $statuteFilter = $options['statute-id'] ? "WHERE id = {$options['statute-id']}" : "";
    $sourceCount = $oldDb->query("SELECT COUNT(*) FROM statutes $statuteFilter")->fetchColumn();
    echo "Found $sourceCount statutes to migrate\n";
    
    if ($sourceCount == 0) {
        die("No statutes found to migrate\n");
    }
    
    // Count related data
    if (isset($pattern['divisions'])) {
        $divisionCount = $oldDb->query("SELECT COUNT(*) FROM {$pattern['divisions']['source_table']}")->fetchColumn();
        echo "Found $divisionCount divisions\n";
    }
    
    if (isset($pattern['provisions'])) {
        $provisionCount = $oldDb->query("SELECT COUNT(*) FROM {$pattern['provisions']['source_table']}")->fetchColumn();
        echo "Found $provisionCount provisions\n";
    }
    
    if (isset($pattern['child_provisions'])) {
        $childProvisionCount = $oldDb->query("SELECT COUNT(*) FROM {$pattern['child_provisions']['source_table']}")->fetchColumn();
        echo "Found $childProvisionCount child provisions\n";
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
    
    if ($options['dry-run']) {
        echo "\n=== DRY RUN ANALYSIS ===\n";
        echo "Pattern: {$pattern['name']}\n";
        echo "Description: {$pattern['description']}\n";
        
        // Show sample data mapping
        echo "\n=== SAMPLE DATA PREVIEW ===\n";
        $sampleStatute = $oldDb->query("SELECT * FROM statutes $statuteFilter LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sampleStatute) {
            echo "Sample Statute: {$sampleStatute['title']}\n";
            echo "Database ID: {$sampleStatute['id']}\n";
            
            // Show divisions
            if (isset($pattern['divisions'])) {
                $divisionTable = $pattern['divisions']['source_table'];
                $sampleDivisions = $oldDb->query("
                    SELECT * FROM $divisionTable 
                    WHERE {$pattern['divisions']['foreign_key']} = {$sampleStatute['id']} 
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "\nSample Divisions ({$pattern['divisions']['division_type']}, level {$pattern['divisions']['level']}):\n";
                foreach ($sampleDivisions as $division) {
                    $title = $division[$pattern['divisions']['title_field']] ?? 'No title';
                    $number = $division[$pattern['divisions']['number_field']] ?? 'No number';
                    echo "  - $number: $title\n";
                }
            }
            
            // Show provisions
            if (isset($pattern['provisions'])) {
                $provisionTable = $pattern['provisions']['source_table'];
                $sampleProvisions = $oldDb->query("
                    SELECT * FROM $provisionTable 
                    LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "\nSample Provisions ({$pattern['provisions']['provision_type']}, level {$pattern['provisions']['level']}):\n";
                foreach ($sampleProvisions as $provision) {
                    $title = $provision[$pattern['provisions']['title_field']] ?? 'No title';
                    $number = $provision[$pattern['provisions']['number_field']] ?? 'No number';
                    echo "  - $number: $title\n";
                }
            }
        }
        
        echo "\n=== MIGRATION PLAN ===\n";
        echo "Phase 1: Migrate $sourceCount statutes\n";
        if (isset($pattern['divisions'])) {
            echo "Phase 2: Migrate divisions as {$pattern['divisions']['division_type']} (level {$pattern['divisions']['level']})\n";
        }
        if (isset($pattern['provisions'])) {
            echo "Phase 3: Migrate provisions as {$pattern['provisions']['provision_type']} (level {$pattern['provisions']['level']})\n";
        }
        if (isset($pattern['child_provisions'])) {
            echo "Phase 4: Migrate child provisions as {$pattern['child_provisions']['provision_type']} (level {$pattern['child_provisions']['level']})\n";
        }
        
        exit(0);
    }
    
    // Start migration
    echo "\n=== STARTING MIGRATION ===\n";
    $newDb->beginTransaction();
    
    try {
        $migratedCounts = [
            'statutes' => 0,
            'divisions' => 0, 
            'provisions' => 0,
            'child_provisions' => 0
        ];
        $errorCount = 0;
        $idMaps = [
            'statutes' => [],
            'divisions' => [],
            'provisions' => []
        ];
        
        // Phase 1: Migrate Statutes using Eloquent
        echo "Phase 1: Migrating statutes...\n";
        $statuteStmt = $oldDb->prepare("SELECT * FROM statutes $statuteFilter ORDER BY id");
        $statuteStmt->execute();
        
        while ($oldStatute = $statuteStmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                // Create statute using Eloquent model (automatic slug generation)
                $statute = Statute::create([
                    'title' => $oldStatute['title'],
                    'description' => $oldStatute['description'] ?? '',
                    'jurisdiction' => 'Federal',
                    'country' => $oldStatute['country'] ?? 'Nigeria',
                    'created_by' => $defaultUserId,
                    'status' => 'active',
                ]);
                
                $idMaps['statutes'][$oldStatute['id']] = $statute->id;
                $migratedCounts['statutes']++;
                
                if ($options['verbose']) {
                    echo "  ✓ Statute: {$oldStatute['title']} (old ID: {$oldStatute['id']} → new ID: {$statute->id}) [slug: {$statute->slug}]\n";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ✗ Error migrating statute ID {$oldStatute['id']}: " . $e->getMessage() . "\n";
                if ($errorCount > 10) {
                    throw new Exception("Too many errors encountered");
                }
            }
        }
        
        echo "Phase 1 complete: {$migratedCounts['statutes']} statutes migrated\n\n";
        
        // Phase 2: Migrate Divisions (if configured)
        if (isset($pattern['divisions']) && $migratedCounts['statutes'] > 0) {
            echo "Phase 2: Migrating divisions ({$pattern['divisions']['division_type']})...\n";
            
            $divisionConfig = $pattern['divisions'];
            $divisionStmt = $oldDb->prepare("SELECT * FROM {$divisionConfig['source_table']} ORDER BY id");
            $divisionStmt->execute();
            
            while ($oldDivision = $divisionStmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $oldStatuteId = $oldDivision[$divisionConfig['foreign_key']];
                    if (!isset($idMaps['statutes'][$oldStatuteId])) {
                        if ($options['verbose']) {
                            echo "  - Skipping division (statute not migrated): old statute ID $oldStatuteId\n";
                        }
                        continue;
                    }
                    
                    $newStatuteId = $idMaps['statutes'][$oldStatuteId];
                    
                    // Create division using Eloquent model (automatic slug generation)
                    $division = StatuteDivision::create([
                        'statute_id' => $newStatuteId,
                        'division_type' => $divisionConfig['division_type'],
                        'division_number' => $oldDivision[$divisionConfig['number_field']] ?? '',
                        'division_title' => $oldDivision[$divisionConfig['title_field']] ?? '',
                        'content' => $oldDivision[$divisionConfig['content_field']] ?? '',
                        'sort_order' => $oldDivision['sort_order'] ?? 0,
                        'level' => $divisionConfig['level'],
                        'status' => 'active',
                    ]);
                    
                    $idMaps['divisions'][$oldDivision['id']] = $division->id;
                    $migratedCounts['divisions']++;
                    
                    if ($options['verbose']) {
                        $title = $oldDivision[$divisionConfig['title_field']] ?? 'Untitled';
                        echo "  ✓ Division: $title (old ID: {$oldDivision['id']} → new ID: {$division->id}) [slug: {$division->slug}]\n";
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    echo "  ✗ Error migrating division ID {$oldDivision['id']}: " . $e->getMessage() . "\n";
                    if ($errorCount > 10) {
                        throw new Exception("Too many errors encountered");
                    }
                }
            }
            
            echo "Phase 2 complete: {$migratedCounts['divisions']} divisions migrated\n\n";
        }
        
        // Phase 3: Migrate Provisions (if configured)
        if (isset($pattern['provisions']) && $migratedCounts['statutes'] > 0) {
            echo "Phase 3: Migrating provisions ({$pattern['provisions']['provision_type']})...\n";
            
            $provisionConfig = $pattern['provisions'];
            $provisionStmt = $oldDb->prepare("SELECT * FROM {$provisionConfig['source_table']} ORDER BY id");
            $provisionStmt->execute();
            
            while ($oldProvision = $provisionStmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    // Find statute ID through division relationship
                    $oldChapterId = $oldProvision[$provisionConfig['foreign_key']];
                    if (!isset($idMaps['divisions'][$oldChapterId])) {
                        if ($options['verbose']) {
                            echo "  - Skipping provision (division not migrated): old chapter ID $oldChapterId\n";
                        }
                        continue;
                    }
                    
                    $divisionId = $idMaps['divisions'][$oldChapterId];
                    
                    // Get statute ID from division
                    $statuteStmt = $newDb->prepare("SELECT statute_id FROM statute_divisions WHERE id = ?");
                    $statuteStmt->execute([$divisionId]);
                    $newStatuteId = $statuteStmt->fetchColumn();
                    
                    $provisionText = '';
                    if ($provisionConfig['text_field']) {
                        $provisionText = $oldProvision[$provisionConfig['text_field']] ?? '';
                    }
                    
                    // Create provision using Eloquent model (automatic slug generation)
                    $provision = StatuteProvision::create([
                        'statute_id' => $newStatuteId,
                        'division_id' => $divisionId,
                        'provision_type' => $provisionConfig['provision_type'],
                        'provision_number' => $oldProvision[$provisionConfig['number_field']] ?? '',
                        'provision_title' => $oldProvision[$provisionConfig['title_field']] ?? null,
                        'provision_text' => $provisionText,
                        'sort_order' => $oldProvision['sort_order'] ?? 0,
                        'level' => $provisionConfig['level'],
                        'status' => 'active',
                    ]);
                    
                    $idMaps['provisions'][$oldProvision['id']] = $provision->id;
                    $migratedCounts['provisions']++;
                    
                    if ($options['verbose']) {
                        $title = $oldProvision[$provisionConfig['title_field']] ?? 'Section ' . ($oldProvision[$provisionConfig['number_field']] ?? '');
                        echo "  ✓ Provision: $title (old ID: {$oldProvision['id']} → new ID: {$provision->id}) [slug: {$provision->slug}]\n";
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    echo "  ✗ Error migrating provision ID {$oldProvision['id']}: " . $e->getMessage() . "\n";
                    if ($errorCount > 10) {
                        throw new Exception("Too many errors encountered");  
                    }
                }
            }
            
            echo "Phase 3 complete: {$migratedCounts['provisions']} provisions migrated\n\n";
        }
        
        // Phase 4: Migrate Child Provisions (if configured)
        if (isset($pattern['child_provisions']) && $migratedCounts['provisions'] > 0) {
            echo "Phase 4: Migrating child provisions ({$pattern['child_provisions']['provision_type']})...\n";
            
            $childConfig = $pattern['child_provisions'];
            $childStmt = $oldDb->prepare("SELECT * FROM {$childConfig['source_table']} ORDER BY id");
            $childStmt->execute();
            
            while ($oldChild = $childStmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $oldSectionId = $oldChild[$childConfig['foreign_key']];
                    
                    if (!isset($idMaps['provisions'][$oldSectionId])) {
                        if ($options['verbose']) {
                            echo "  - Skipping child provision (parent not migrated): old section ID $oldSectionId\n";
                        }
                        continue;
                    }
                    
                    $parentProvisionId = $idMaps['provisions'][$oldSectionId];
                    
                    // Get statute and division ID from parent provision
                    $parentStmt = $newDb->prepare("SELECT statute_id, division_id FROM statute_provisions WHERE id = ?");
                    $parentStmt->execute([$parentProvisionId]);
                    $parentData = $parentStmt->fetch(PDO::FETCH_ASSOC);
                    $newStatuteId = $parentData['statute_id'];
                    $divisionId = $parentData['division_id'];
                    
                    // Create child provision using Eloquent model (automatic slug generation)
                    $childProvision = StatuteProvision::create([
                        'statute_id' => $newStatuteId,
                        'division_id' => $divisionId,
                        'parent_provision_id' => $parentProvisionId,
                        'provision_type' => $childConfig['provision_type'],
                        'provision_number' => $oldChild[$childConfig['number_field']] ?? '',
                        'provision_text' => $oldChild[$childConfig['text_field']] ?? '',
                        'sort_order' => $oldChild['sort_order'] ?? 0,
                        'level' => $childConfig['level'],
                        'status' => 'active',
                    ]);
                    
                    $migratedCounts['child_provisions']++;
                    
                    if ($options['verbose']) {
                        $number = $oldChild[$childConfig['number_field']] ?? 'No number';
                        echo "  ✓ Child Provision: $number (old ID: {$oldChild['id']} → new ID: {$childProvision->id}) [slug: {$childProvision->slug}]\n";
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    echo "  ✗ Error migrating child provision ID {$oldChild['id']}: " . $e->getMessage() . "\n";
                    if ($errorCount > 10) {
                        throw new Exception("Too many errors encountered");
                    }
                }
            }
            
            echo "Phase 4 complete: {$migratedCounts['child_provisions']} child provisions migrated\n\n";
        }
        
        // Commit transaction
        $newDb->commit();
        
        echo "=== MIGRATION COMPLETE ===\n";
        echo "Successfully migrated:\n";
        echo "  Statutes: {$migratedCounts['statutes']}\n";
        echo "  Divisions: {$migratedCounts['divisions']}\n";
        echo "  Provisions: {$migratedCounts['provisions']}\n";
        echo "  Child Provisions: {$migratedCounts['child_provisions']}\n";
        echo "Errors encountered: $errorCount\n";
        
        // Verification
        echo "\n=== VERIFICATION ===\n";
        $finalStatuteCount = $newDb->query("SELECT COUNT(*) FROM statutes")->fetchColumn();
        $finalDivisionCount = $newDb->query("SELECT COUNT(*) FROM statute_divisions")->fetchColumn();
        $finalProvisionCount = $newDb->query("SELECT COUNT(*) FROM statute_provisions")->fetchColumn();
        
        echo "Final counts in new database:\n";
        echo "  Statutes: $finalStatuteCount\n"; 
        echo "  Divisions: $finalDivisionCount\n";
        echo "  Provisions: $finalProvisionCount\n";
        
        // Sample verification
        echo "\n=== SAMPLE VERIFICATION ===\n";
        $verifyStmt = $newDb->query("
            SELECT s.title, s.id as statute_id, 
                   COUNT(DISTINCT sd.id) as divisions_count,
                   COUNT(DISTINCT sp.id) as provisions_count
            FROM statutes s
            LEFT JOIN statute_divisions sd ON s.id = sd.statute_id
            LEFT JOIN statute_provisions sp ON s.id = sp.statute_id
            GROUP BY s.id
            ORDER BY s.id DESC
            LIMIT 3
        ");
        
        while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "✓ {$row['title']} (ID: {$row['statute_id']}) - {$row['divisions_count']} divisions, {$row['provisions_count']} provisions\n";
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

echo "\nStatute migration completed successfully!\n";
if (!$options['skip-backup']) {
    echo "Backup available at: $backupPath\n";
}

echo "\nNote: Slugs are automatically generated by Laravel Eloquent models during migration.\n";

?>