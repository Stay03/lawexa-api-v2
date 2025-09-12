<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\ModelView;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "🔍 Checking for data consistency issues...\n\n";
    
    // Find records with device_type='robot' but is_bot is not true
    $inconsistentRecords = ModelView::where('device_type', 'robot')
        ->where(function($query) {
            $query->where('is_bot', false)
                  ->orWhereNull('is_bot');
        })
        ->get();
        
    echo "Found {$inconsistentRecords->count()} inconsistent records with device_type='robot' but is_bot != true\n";
    
    if ($inconsistentRecords->count() > 0) {
        echo "📝 Sample inconsistent records:\n";
        $inconsistentRecords->take(3)->each(function($record) {
            echo "- ID {$record->id}: device_type='{$record->device_type}', is_bot=" . 
                 ($record->is_bot === null ? 'null' : ($record->is_bot ? 'true' : 'false')) . 
                 ", user_agent: " . substr($record->user_agent, 0, 80) . "...\n";
        });
        
        echo "\n🔧 Fixing inconsistent records...\n";
        
        $updatedCount = ModelView::where('device_type', 'robot')
            ->where(function($query) {
                $query->where('is_bot', false)
                      ->orWhereNull('is_bot');
            })
            ->update([
                'is_bot' => true,
                // Try to detect bot names from existing patterns if they're null
                'bot_name' => DB::raw("CASE 
                    WHEN bot_name IS NULL AND user_agent LIKE '%Googlebot%' THEN 'Google Bot'
                    WHEN bot_name IS NULL AND user_agent LIKE '%Bingbot%' THEN 'Bing Bot' 
                    WHEN bot_name IS NULL AND user_agent LIKE '%bot%' THEN 'Unknown Bot'
                    ELSE bot_name 
                END"),
                'is_search_engine' => DB::raw("CASE 
                    WHEN is_search_engine IS NULL AND (user_agent LIKE '%Googlebot%' OR user_agent LIKE '%Bingbot%') THEN 1
                    ELSE is_search_engine 
                END")
            ]);
        
        echo "✅ Updated {$updatedCount} records to have is_bot=true\n";
    }
    
    // Also find records with is_bot=true but device_type != 'robot'
    $otherBotRecords = ModelView::where('is_bot', true)
        ->where('device_type', '!=', 'robot')
        ->count();
        
    echo "\n📊 Found {$otherBotRecords} records with is_bot=true but device_type != 'robot'\n";
    echo "(These might be legitimate bots detected via user-agent but not classified as 'robot' device type)\n";
    
    echo "\n🎯 Data consistency check complete!\n";
    echo "Now test the dashboard again with is_bot=0 to see if robot device types are filtered out.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}