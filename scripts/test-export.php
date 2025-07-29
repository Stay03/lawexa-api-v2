<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection
$capsule = new Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// SQLite connection
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../database/database.sqlite',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Test connection by getting tables
    $tables = Capsule::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    
    echo "Connected to SQLite database successfully!\n";
    echo "Found " . count($tables) . " tables:\n";
    
    foreach ($tables as $table) {
        $count = Capsule::table($table->name)->count();
        echo "  - {$table->name}: $count records\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}