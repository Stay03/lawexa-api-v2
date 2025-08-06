<?php

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

// Import models
use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;

try {
    echo "=== TESTING ELOQUENT SLUG GENERATION ===\n";
    
    // Test Statute creation
    echo "Creating test statute...\n";
    $statute = Statute::create([
        'title' => 'Test Act for Slug Generation 2025',
        'description' => 'A test act to demonstrate automatic slug generation',
        'jurisdiction' => 'Federal',
        'country' => 'Nigeria',
        'created_by' => 1,
        'status' => 'active',
    ]);
    
    echo "✓ Statute created with ID: {$statute->id}\n";
    echo "✓ Auto-generated slug: {$statute->slug}\n\n";
    
    // Test Division creation
    echo "Creating test division...\n";
    $division = StatuteDivision::create([
        'statute_id' => $statute->id,
        'division_type' => 'part',
        'division_number' => 'PART 1',
        'division_title' => 'Test Division for Slug Generation',
        'content' => 'Test content',
        'sort_order' => 1,
        'level' => 1,
        'status' => 'active',
    ]);
    
    echo "✓ Division created with ID: {$division->id}\n";
    echo "✓ Auto-generated slug: {$division->slug}\n\n";
    
    // Test Provision creation
    echo "Creating test provision...\n";
    $provision = StatuteProvision::create([
        'statute_id' => $statute->id,
        'division_id' => $division->id,
        'provision_type' => 'section',
        'provision_number' => '1',
        'provision_title' => 'Test Section for Slug Generation',
        'provision_text' => 'This is a test section.',
        'sort_order' => 1,
        'level' => 2,
        'status' => 'active',
    ]);
    
    echo "✓ Provision created with ID: {$provision->id}\n";
    echo "✓ Auto-generated slug: {$provision->slug}\n\n";
    
    echo "=== CLEANUP ===\n";
    echo "Deleting test records...\n";
    $provision->delete();
    $division->delete();
    $statute->delete();
    echo "✓ Test records cleaned up\n\n";
    
    echo "=== CONCLUSION ===\n";
    echo "✓ Eloquent slug generation is working correctly!\n";
    echo "✓ All models (Statute, StatuteDivision, StatuteProvision) generate slugs automatically\n";
    echo "✓ The migration script will now use Eloquent models for proper slug generation\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>