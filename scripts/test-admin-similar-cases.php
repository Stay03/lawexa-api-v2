<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../database/database.sqlite',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== TESTING ADMIN SIMILAR CASES MANAGEMENT ===\n\n";

// Test the syncSimilarCases functionality
function syncSimilarCases($caseId, array $similarCaseIds): void
{
    // Remove the case itself from the list to prevent self-referencing
    $similarCaseIds = array_filter($similarCaseIds, function($id) use ($caseId) {
        return $id != $caseId;
    });

    // Remove duplicates
    $similarCaseIds = array_unique($similarCaseIds);

    // Get current relationships
    $currentRelationships = Capsule::table('similar_cases')
        ->where('case_id', $caseId)
        ->pluck('similar_case_id')
        ->toArray();

    echo "Current relationships for case $caseId: " . implode(', ', $currentRelationships) . "\n";
    echo "New relationships to sync: " . implode(', ', $similarCaseIds) . "\n";

    // Remove relationships that are not in the new list
    $toRemove = array_diff($currentRelationships, $similarCaseIds);
    if (!empty($toRemove)) {
        Capsule::table('similar_cases')
            ->where('case_id', $caseId)
            ->whereIn('similar_case_id', $toRemove)
            ->delete();
        echo "Removed relationships: " . implode(', ', $toRemove) . "\n";
    }

    // Add new relationships
    $toAdd = array_diff($similarCaseIds, $currentRelationships);
    foreach ($toAdd as $similarCaseId) {
        Capsule::table('similar_cases')->insert([
            'case_id' => $caseId,
            'similar_case_id' => $similarCaseId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    if (!empty($toAdd)) {
        echo "Added relationships: " . implode(', ', $toAdd) . "\n";
    }
}

// Test case - let's use case ID 100 and sync it with cases 200, 300, 400
$testCaseId = 100;
$testSimilarCaseIds = [200, 300, 400];

// Verify test cases exist
$caseExists = Capsule::table('court_cases')->where('id', $testCaseId)->exists();
$similarCasesExist = Capsule::table('court_cases')->whereIn('id', $testSimilarCaseIds)->count();

echo "Test case $testCaseId exists: " . ($caseExists ? 'YES' : 'NO') . "\n";
echo "Similar cases exist: $similarCasesExist out of " . count($testSimilarCaseIds) . "\n\n";

if ($caseExists && $similarCasesExist == count($testSimilarCaseIds)) {
    echo "1. Initial sync with cases 200, 300, 400:\n";
    syncSimilarCases($testCaseId, $testSimilarCaseIds);
    
    echo "\n2. Update sync to only include cases 200, 500 (should remove 300, 400 and add 500):\n";
    $caseExists500 = Capsule::table('court_cases')->where('id', 500)->exists();
    
    if ($caseExists500) {
        syncSimilarCases($testCaseId, [200, 500]);
    } else {
        echo "Case 500 doesn't exist, skipping update test\n";
    }
    
    echo "\n3. Clear all relationships:\n";
    syncSimilarCases($testCaseId, []);
    
    echo "\nFinal verification:\n";
    $finalRelationships = Capsule::table('similar_cases')
        ->where('case_id', $testCaseId)
        ->pluck('similar_case_id')
        ->toArray();
    echo "Final relationships for case $testCaseId: " . (empty($finalRelationships) ? 'NONE' : implode(', ', $finalRelationships)) . "\n";
    
} else {
    echo "Cannot run sync test - required test cases don't exist\n";
}

echo "\n=== TESTING VALIDATION ===\n\n";

// Test self-referencing prevention
echo "Testing self-referencing prevention:\n";
$selfRefTest = [100, 200, 100, 300]; // Case 100 trying to be similar to itself
syncSimilarCases(100, $selfRefTest);

?>