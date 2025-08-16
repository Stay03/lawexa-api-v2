<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Increase memory limit for database migration
ini_set('memory_limit', '512M');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Dotenv\Dotenv;

class DatabaseSwitcher
{
    private $envPath;
    private $currentConnection;
    private $backupPath;
    private $verbose = false;
    
    private $supportedDatabases = ['sqlite', 'mysql'];
    
    public function __construct()
    {
        $this->envPath = __DIR__ . '/../.env';
        $this->backupPath = __DIR__ . '/../database/backups';
        
        // Load environment variables
        $this->loadEnvironment();
        
        $this->currentConnection = $this->getCurrentConnection();
        
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    private function loadEnvironment()
    {
        if (file_exists($this->envPath)) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }
    }
    
    public function run($argv)
    {
        if (count($argv) < 2) {
            $this->showUsage();
            return;
        }
        
        $targetDb = $argv[1];
        $options = array_slice($argv, 2);
        
        // Parse options
        $migrateBefore = in_array('--migrate-before', $options);
        $migrateAfter = in_array('--migrate-after', $options);
        $this->verbose = in_array('--verbose', $options) || in_array('-v', $options);
        
        if (!in_array($targetDb, $this->supportedDatabases)) {
            $this->error("Unsupported database: $targetDb");
            $this->showUsage();
            return;
        }
        
        if ($this->currentConnection === $targetDb) {
            $this->info("Already using $targetDb database.");
            return;
        }
        
        $this->info("Switching from {$this->currentConnection} to $targetDb");
        
        try {
            if ($migrateBefore) {
                $this->info("Step 1: Exporting current data...");
                $backupFile = $this->exportData();
                
                $this->info("Step 2: Switching database connection...");
                $this->switchDatabase($targetDb);
                
                $this->info("Step 3: Running migrations...");
                $this->runMigrations();
                
                $this->info("Step 4: Importing data...");
                $recordCounts = $this->importData($backupFile);
                $this->validateMigration($recordCounts);
                
            } elseif ($migrateAfter) {
                $this->info("Step 1: Switching database connection...");
                $this->switchDatabase($targetDb);
                
                $this->info("Step 2: Running migrations...");
                $this->runMigrations();
                
                $this->info("Step 3: Importing previously exported data...");
                $latestBackup = $this->getLatestBackup();
                if ($latestBackup) {
                    $this->importData($latestBackup);
                } else {
                    $this->warning("No backup file found for data import.");
                }
                
            } else {
                $this->info("Step 1: Creating backup...");
                $this->exportData();
                
                $this->info("Step 2: Switching database connection...");
                $this->switchDatabase($targetDb);
                
                $this->info("Step 3: Running migrations...");
                $this->runMigrations();
            }
            
            $this->success("Database successfully switched to $targetDb!");
            
        } catch (Exception $e) {
            $this->error("Error during database switch: " . $e->getMessage());
            $this->error("You may need to manually restore from backup.");
        }
    }
    
    private function getCurrentConnection()
    {
        $envContent = file_get_contents($this->envPath);
        preg_match('/DB_CONNECTION=(.*)/', $envContent, $matches);
        return $matches[1] ?? 'sqlite';
    }
    
    private function switchDatabase($targetDb)
    {
        $envContent = file_get_contents($this->envPath);
        
        if ($targetDb === 'mysql') {
            // Switch to MySQL
            $envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mysql', $envContent);
            $envContent = preg_replace('/# DB_HOST=(.*)/', 'DB_HOST=$1', $envContent);
            $envContent = preg_replace('/# DB_DATABASE=(.*)/', 'DB_DATABASE=$1', $envContent);
            $envContent = preg_replace('/# DB_USERNAME=(.*)/', 'DB_USERNAME=$1', $envContent);
            $envContent = preg_replace('/# DB_PASSWORD=(.*)/', 'DB_PASSWORD=$1', $envContent);
            
        } elseif ($targetDb === 'sqlite') {
            // Switch to SQLite
            $envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=sqlite', $envContent);
            $envContent = preg_replace('/DB_HOST=(.*)/', '# DB_HOST=$1', $envContent);
            $envContent = preg_replace('/DB_DATABASE=(.*)/', '# DB_DATABASE=$1', $envContent);
            $envContent = preg_replace('/DB_USERNAME=(.*)/', '# DB_USERNAME=$1', $envContent);
            $envContent = preg_replace('/DB_PASSWORD=(.*)/', '# DB_PASSWORD=$1', $envContent);
        }
        
        file_put_contents($this->envPath, $envContent);
        $this->currentConnection = $targetDb;
        
        // Reload environment variables after switching
        $this->loadEnvironment();
        
        // Force reconnection with new configuration
        $this->initializeDatabase();
    }
    
    private function exportData()
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupPath . "/backup_{$this->currentConnection}_{$timestamp}.json";
        
        // Initialize Laravel database connection
        $this->initializeDatabase();
        
        // Get all tables
        $tables = $this->getTables();
        
        // Start JSON file
        file_put_contents($backupFile, "{\n");
        $firstTable = true;
        
        foreach ($tables as $table) {
            if (in_array($table, ['migrations'])) {
                continue; // Skip system tables
            }
            
            $this->verbose("Exporting table: $table");
            
            try {
                $totalRecords = Capsule::table($table)->count();
                
                if (!$firstTable) {
                    file_put_contents($backupFile, ",\n", FILE_APPEND);
                }
                $firstTable = false;
                
                // Write table header
                file_put_contents($backupFile, "  \"$table\": [\n", FILE_APPEND);
                
                // Process in chunks of 100 records to reduce memory usage
                $chunkSize = 100;
                $offset = 0;
                $firstRecord = true;
                $exportedCount = 0;
                
                // Use cursor to avoid memory issues with large datasets
                try {
                    // Try with id column first
                    Capsule::table($table)->orderBy('id')->chunk($chunkSize, function($records) use ($backupFile, &$firstRecord, &$exportedCount, $totalRecords) {
                        foreach ($records as $record) {
                            if (!$firstRecord) {
                                file_put_contents($backupFile, ",\n", FILE_APPEND);
                            }
                            $firstRecord = false;
                            
                            $recordJson = json_encode($record, JSON_PRETTY_PRINT);
                            // Indent the record JSON
                            $indentedJson = "    " . str_replace("\n", "\n    ", $recordJson);
                            file_put_contents($backupFile, $indentedJson, FILE_APPEND);
                            $exportedCount++;
                        }
                        
                        if ($this->verbose && $exportedCount % 1000 == 0) {
                            $this->verbose("  Progress: $exportedCount / $totalRecords records");
                        }
                    });
                } catch (Exception $innerE) {
                    // Fallback: try without ordering for tables without id
                    try {
                        Capsule::table($table)->chunk($chunkSize, function($records) use ($backupFile, &$firstRecord, &$exportedCount, $totalRecords) {
                            foreach ($records as $record) {
                                if (!$firstRecord) {
                                    file_put_contents($backupFile, ",\n", FILE_APPEND);
                                }
                                $firstRecord = false;
                                
                                $recordJson = json_encode($record, JSON_PRETTY_PRINT);
                                // Indent the record JSON
                                $indentedJson = "    " . str_replace("\n", "\n    ", $recordJson);
                                file_put_contents($backupFile, $indentedJson, FILE_APPEND);
                                $exportedCount++;
                            }
                            
                            if ($this->verbose && $exportedCount % 1000 == 0) {
                                $this->verbose("  Progress: $exportedCount / $totalRecords records");
                            }
                        });
                    } catch (Exception $fallbackE) {
                        // If both chunk methods fail, just export empty
                        $this->warning("Could not export table $table with chunking: " . $fallbackE->getMessage());
                    }
                }
                
                file_put_contents($backupFile, "\n  ]", FILE_APPEND);
                $this->verbose("  Exported $exportedCount records");
                
            } catch (Exception $e) {
                $this->warning("Could not export table $table: " . $e->getMessage());
                // Skip failed tables entirely to avoid JSON corruption
                if ($firstTable) {
                    // If this is the first table and it fails, we need to start with something
                    file_put_contents($backupFile, "  \"_placeholder\": []\n", FILE_APPEND);
                    $firstTable = false;
                }
            }
        }
        
        // Close JSON file
        file_put_contents($backupFile, "\n}\n", FILE_APPEND);
        $this->info("Data exported to: $backupFile");
        
        return $backupFile;
    }
    
    private function importData($backupFile)
    {
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found: $backupFile");
        }
        
        $this->initializeDatabase();
        
        // Disable foreign key checks temporarily
        if ($this->currentConnection === 'mysql') {
            Capsule::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            Capsule::statement('PRAGMA foreign_keys=OFF');
        }
        
        // Use simple JSON decode instead of streaming for now (the memory issue is resolved)
        $jsonContent = file_get_contents($backupFile);
        $data = json_decode($jsonContent, true);
        
        if (!$data) {
            throw new Exception("Invalid backup data or failed to parse JSON");
        }
        
        foreach ($data as $tableName => $records) {
            $this->verbose("Importing table: $tableName");
            
            try {
                // Clear existing data
                Capsule::table($tableName)->truncate();
                
                // Insert data in chunks
                if (!empty($records)) {
                    $chunks = array_chunk($records, 100);
                    foreach ($chunks as $chunk) {
                        // Convert objects to arrays
                        $chunk = array_map(function($record) {
                            return (array) $record;
                        }, $chunk);
                        
                        Capsule::table($tableName)->insert($chunk);
                    }
                    
                    if ($this->verbose) {
                        $this->verbose("  Imported " . count($records) . " records to $tableName");
                    }
                } else {
                    if ($this->verbose) {
                        $this->verbose("  No records to import for $tableName");
                    }
                }
                
            } catch (Exception $e) {
                $this->warning("Could not import table $tableName: " . $e->getMessage());
            }
        }
        
        // Re-enable foreign key checks
        if ($this->currentConnection === 'mysql') {
            Capsule::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            Capsule::statement('PRAGMA foreign_keys=ON');
        }
        
        $this->info("Data import completed");
        
        return $this->getRecordCounts();
    }
    
    private function importRecordsChunk($tableName, $records)
    {
        if (empty($records)) {
            return;
        }
        
        try {
            // Convert objects to arrays
            $records = array_map(function($record) {
                return (array) $record;
            }, $records);
            
            Capsule::table($tableName)->insert($records);
            if ($this->verbose) {
                $this->verbose("  Imported " . count($records) . " records to $tableName");
            }
        } catch (Exception $e) {
            $this->warning("Could not import records for table $tableName: " . $e->getMessage());
            if ($this->verbose) {
                $this->verbose("Error details: " . $e->getTraceAsString());
            }
        }
    }
    
    private function runMigrations()
    {
        $command = "php " . __DIR__ . "/../artisan migrate --force";
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Migration failed: " . implode("\n", $output));
        }
        
        $this->info("Migrations completed successfully");
    }
    
    private function getTables()
    {
        if ($this->currentConnection === 'sqlite') {
            $tables = Capsule::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_map(function($table) {
                return $table->name;
            }, $tables);
        } else {
            $tables = Capsule::select('SHOW TABLES');
            $dbName = Capsule::connection()->getDatabaseName();
            $columnName = 'Tables_in_' . $dbName;
            return array_map(function($table) use ($columnName) {
                return $table->$columnName;
            }, $tables);
        }
    }
    
    private function getLatestBackup()
    {
        $backups = glob($this->backupPath . '/backup_*.json');
        if (empty($backups)) {
            return null;
        }
        
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $backups[0];
    }
    
    private function initializeDatabase()
    {
        $capsule = new Capsule;
        
        if ($this->currentConnection === 'sqlite') {
            $config = [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../database/database.sqlite',
                'prefix' => '',
            ];
        } else {
            $config = [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'database' => $_ENV['DB_DATABASE'] ?? 'lawexa_api_dev',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'port' => $_ENV['DB_PORT'] ?? 3306,
            ];
        }
        
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        // Test the connection
        $this->testConnection($config);
    }
    
    private function testConnection($config)
    {
        try {
            $result = Capsule::select('SELECT 1 as test');
            if ($this->verbose) {
                $dbName = $config['driver'] === 'sqlite' ? 'SQLite' : $config['database'];
                $this->verbose("✓ Connected to {$config['driver']} database: $dbName");
            }
        } catch (Exception $e) {
            throw new Exception("Failed to connect to {$config['driver']} database: " . $e->getMessage());
        }
    }
    
    private function getRecordCounts()
    {
        $tables = $this->getTables();
        $counts = [];
        
        foreach ($tables as $table) {
            if (in_array($table, ['migrations'])) {
                continue;
            }
            
            try {
                $count = Capsule::table($table)->count();
                $counts[$table] = $count;
            } catch (Exception $e) {
                $counts[$table] = 0;
            }
        }
        
        return $counts;
    }
    
    private function validateMigration($recordCounts)
    {
        $totalRecords = array_sum($recordCounts);
        $this->info("Migration validation:");
        $this->info("Total records imported: $totalRecords");
        
        if ($this->verbose) {
            foreach ($recordCounts as $table => $count) {
                if ($count > 0) {
                    $this->verbose("  $table: $count records");
                }
            }
        }
        
        if ($totalRecords === 0) {
            $this->warning("No records were imported. This might indicate a connection issue.");
        } else {
            $this->info("✓ Migration validation passed");
        }
    }
    
    private function showUsage()
    {
        echo "\nDatabase Switcher Usage:\n";
        echo "php scripts/db-switch.php <target_db> [options]\n\n";
        echo "Arguments:\n";
        echo "  target_db    Target database (sqlite|mysql)\n\n";
        echo "Options:\n";
        echo "  --migrate-before    Export data before switching, then import after\n";
        echo "  --migrate-after     Switch first, then import from latest backup\n";
        echo "  --verbose, -v       Show detailed output\n\n";
        echo "Examples:\n";
        echo "  php scripts/db-switch.php mysql --migrate-before\n";
        echo "  php scripts/db-switch.php sqlite --migrate-after\n";
        echo "  php scripts/db-switch.php mysql\n\n";
    }
    
    private function info($message)
    {
        echo "\033[32m[INFO]\033[0m $message\n";
    }
    
    private function warning($message)
    {
        echo "\033[33m[WARNING]\033[0m $message\n";
    }
    
    private function error($message)
    {
        echo "\033[31m[ERROR]\033[0m $message\n";
    }
    
    private function success($message)
    {
        echo "\033[32m[SUCCESS]\033[0m $message\n";
    }
    
    private function verbose($message)
    {
        if ($this->verbose) {
            echo "\033[36m[VERBOSE]\033[0m $message\n";
        }
    }
}

// Run the script
$switcher = new DatabaseSwitcher();
$switcher->run($argv);