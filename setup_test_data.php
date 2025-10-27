<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Statute;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;

echo "Setting up test data...\n\n";

// Get statute
$statute = Statute::where('slug', 'test-statute')->first();
if (!$statute) {
    echo "ERROR: Statute not found\n";
    exit(1);
}

echo "Statute found: {$statute->slug} (ID: {$statute->id})\n\n";

// Create chapter-3 division
$division3 = StatuteDivision::firstOrCreate(
    ['slug' => 'chapter-3'],
    [
        'statute_id' => $statute->id,
        'division_type' => 'chapter',
        'division_number' => 'III',
        'division_title' => 'Third Chapter',
        'level' => 1,
        'order_index' => 500,
        'sort_order' => 3,
        'status' => 'active',
        'created_by' => $statute->created_by,
    ]
);
echo "Division 3: {$division3->slug} (order_index: {$division3->order_index})\n";

// Create provisions
$provision1 = StatuteProvision::firstOrCreate(
    ['slug' => 'section-1-1'],
    [
        'statute_id' => $statute->id,
        'provision_type' => 'section',
        'provision_number' => '1.1',
        'provision_title' => 'First Section',
        'level' => 1,
        'order_index' => 150,
        'sort_order' => 1,
        'status' => 'active',
        'created_by' => $statute->created_by,
    ]
);
echo "Provision 1: {$provision1->slug} (order_index: {$provision1->order_index})\n";

$provision2 = StatuteProvision::firstOrCreate(
    ['slug' => 'section-1-2'],
    [
        'statute_id' => $statute->id,
        'provision_type' => 'section',
        'provision_number' => '1.2',
        'provision_title' => 'Second Section',
        'level' => 1,
        'order_index' => 250,
        'sort_order' => 2,
        'status' => 'active',
        'created_by' => $statute->created_by,
    ]
);
echo "Provision 2: {$provision2->slug} (order_index: {$provision2->order_index})\n";

$provision3 = StatuteProvision::firstOrCreate(
    ['slug' => 'section-2-1'],
    [
        'statute_id' => $statute->id,
        'provision_type' => 'section',
        'provision_number' => '2.1',
        'provision_title' => 'Third Section',
        'level' => 1,
        'order_index' => 350,
        'sort_order' => 3,
        'status' => 'active',
        'created_by' => $statute->created_by,
    ]
);
echo "Provision 3: {$provision3->slug} (order_index: {$provision3->order_index})\n";

// Create more provisions for better testing
$provision4 = StatuteProvision::firstOrCreate(
    ['slug' => 'section-3-1'],
    [
        'statute_id' => $statute->id,
        'provision_type' => 'section',
        'provision_number' => '3.1',
        'provision_title' => 'Fourth Section',
        'level' => 1,
        'order_index' => 450,
        'sort_order' => 4,
        'status' => 'active',
        'created_by' => $statute->created_by,
    ]
);
echo "Provision 4: {$provision4->slug} (order_index: {$provision4->order_index})\n";

// Verify
echo "\n--- Verification ---\n";
$divisions = StatuteDivision::where('statute_id', $statute->id)->orderBy('order_index')->get();
echo "Total divisions: " . $divisions->count() . "\n";
foreach ($divisions as $div) {
    echo "  - {$div->slug} (order_index: {$div->order_index})\n";
}

$provisions = StatuteProvision::where('statute_id', $statute->id)->orderBy('order_index')->get();
echo "Total provisions: " . $provisions->count() . "\n";
foreach ($provisions as $prov) {
    echo "  - {$prov->slug} (order_index: {$prov->order_index})\n";
}

echo "\nTest data setup complete!\n";
