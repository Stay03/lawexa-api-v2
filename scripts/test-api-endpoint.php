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

// Test the similar cases functionality
echo "=== TESTING SIMILAR CASES FUNCTIONALITY ===\n\n";

// Get a case with similar cases
$case = Capsule::table('court_cases')
    ->where('id', 15)  // Using case ID from our sample
    ->first();

if (!$case) {
    echo "Case not found!\n";
    exit(1);
}

echo "Testing Case: {$case->title} (ID: {$case->id})\n\n";

// Get similar cases relationships
$similarCases = Capsule::table('similar_cases')
    ->join('court_cases', 'similar_cases.similar_case_id', '=', 'court_cases.id')
    ->where('similar_cases.case_id', $case->id)
    ->select([
        'court_cases.id',
        'court_cases.title',
        'court_cases.slug',
        'court_cases.court',
        'court_cases.date',
        'court_cases.country',
        'court_cases.citation'
    ])
    ->get();

echo "Similar Cases Found: " . $similarCases->count() . "\n";

foreach ($similarCases as $similarCase) {
    echo "  -> ID {$similarCase->id}: {$similarCase->title}\n";
    echo "     Court: {$similarCase->court}\n";
    echo "     Date: {$similarCase->date}\n";
    echo "     Citation: {$similarCase->citation}\n\n";
}

// Test bidirectional relationships
$bidirectionalCases = Capsule::table('similar_cases')
    ->join('court_cases', 'similar_cases.case_id', '=', 'court_cases.id')
    ->where('similar_cases.similar_case_id', $case->id)
    ->select([
        'court_cases.id',
        'court_cases.title',
        'court_cases.slug',
        'court_cases.court',
        'court_cases.date',
        'court_cases.country',
        'court_cases.citation'
    ])
    ->get();

echo "Bidirectional Similar Cases Found: " . $bidirectionalCases->count() . "\n";

foreach ($bidirectionalCases as $bidirectionalCase) {
    echo "  <- ID {$bidirectionalCase->id}: {$bidirectionalCase->title}\n";
    echo "     Court: {$bidirectionalCase->court}\n";
    echo "     Date: {$bidirectionalCase->date}\n\n";
}

// Simulate API response format
echo "=== API RESPONSE SIMULATION ===\n\n";

$apiResponse = [
    'id' => $case->id,
    'title' => $case->title,
    'court' => $case->court,
    'date' => $case->date,
    'citation' => $case->citation,
    'similar_cases' => $similarCases->merge($bidirectionalCases)->unique('id')->map(function($item) {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'court' => $item->court,
            'date' => $item->date,
            'country' => $item->country,
            'citation' => $item->citation,
        ];
    })->values()->toArray(),
    'similar_cases_count' => $similarCases->merge($bidirectionalCases)->unique('id')->count()
];

echo json_encode($apiResponse, JSON_PRETTY_PRINT);

?>