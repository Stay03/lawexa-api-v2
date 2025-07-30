<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Load Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Resetting tables...\n";
    
    $driver = DB::connection()->getDriverName();
    echo "Database driver: {$driver}\n";
    
    if ($driver === 'mysql') {
        // MySQL specific commands
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE similar_cases;');
        DB::statement('TRUNCATE TABLE court_cases;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "✓ Tables truncated (MySQL)\n";
    } else {
        // SQLite specific commands
        DB::statement('PRAGMA foreign_keys = OFF;');
        DB::statement('DELETE FROM similar_cases;');
        DB::statement('DELETE FROM court_cases;');
        DB::statement('DELETE FROM sqlite_sequence WHERE name = "similar_cases";');
        DB::statement('DELETE FROM sqlite_sequence WHERE name = "court_cases";');
        DB::statement('PRAGMA foreign_keys = ON;');
        echo "✓ Records deleted and sequences reset (SQLite)\n";
    }
    
    echo "\n✅ Both tables have been reset. New entries will start from ID 1.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}