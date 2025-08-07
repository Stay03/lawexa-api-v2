<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG DIVISION 27 RELATIONSHIPS ===\n\n";

// Check if division 27 exists
$division = App\Models\StatuteDivision::find(27);
if (!$division) {
    echo "Division 27 not found!\n";
    exit;
}

echo "Division 27 found:\n";
echo "- ID: {$division->id}\n";
echo "- Title: {$division->division_title}\n";
echo "- Statute ID: {$division->statute_id}\n";
echo "- Type: {$division->division_type}\n";
echo "- Number: {$division->division_number}\n\n";

// Check for child divisions (parts under this chapter)
echo "=== CHECKING CHILD DIVISIONS ===\n";
$childDivisions = App\Models\StatuteDivision::where('parent_division_id', 27)->get();
echo "Found " . $childDivisions->count() . " child divisions:\n";

foreach ($childDivisions as $child) {
    echo "- ID: {$child->id}, Title: {$child->division_title}, Number: {$child->division_number}, Type: {$child->division_type}\n";
}

// Check for provisions directly under this division
echo "\n=== CHECKING PROVISIONS ===\n";
$provisions = App\Models\StatuteProvision::where('division_id', 27)->get();
echo "Found " . $provisions->count() . " provisions:\n";

foreach ($provisions as $provision) {
    echo "- ID: {$provision->id}, Title: {$provision->provision_title}, Number: {$provision->provision_number}\n";
}

// Check if there are any divisions in the same statute that might be mislinked
echo "\n=== ALL DIVISIONS IN STATUTE {$division->statute_id} ===\n";
$allDivisions = App\Models\StatuteDivision::where('statute_id', $division->statute_id)
    ->orderBy('sort_order')
    ->get();

foreach ($allDivisions as $div) {
    echo "- ID: {$div->id}, Parent: " . ($div->parent_division_id ?: 'NULL') . ", Title: {$div->division_title}, Number: {$div->division_number}, Type: {$div->division_type}\n";
}

echo "\n=== RELATIONSHIP LOADING TEST ===\n";
// Test the actual relationship loading
$divisionWithRelations = App\Models\StatuteDivision::with([
    'childDivisions:id,division_title,division_number,parent_division_id',
    'provisions:id,provision_title,provision_number,division_id'
])->find(27);

if ($divisionWithRelations) {
    echo "Child divisions loaded: " . $divisionWithRelations->childDivisions->count() . "\n";
    echo "Provisions loaded: " . $divisionWithRelations->provisions->count() . "\n";
    
    if ($divisionWithRelations->childDivisions->count() > 0) {
        echo "\nChild divisions details:\n";
        foreach ($divisionWithRelations->childDivisions as $child) {
            echo "- {$child->division_title} (ID: {$child->id})\n";
        }
    }
    
    if ($divisionWithRelations->provisions->count() > 0) {
        echo "\nProvisions details:\n";
        foreach ($divisionWithRelations->provisions as $provision) {
            echo "- {$provision->provision_title} (ID: {$provision->id})\n";
        }
    }
}

echo "\n=== END DEBUG ===\n";