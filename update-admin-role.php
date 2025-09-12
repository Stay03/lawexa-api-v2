<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $user = User::where('email', 'bot-test-admin@example.com')->first();
    
    if ($user) {
        $user->update(['role' => 'superadmin']);
        echo "✅ User role updated to superadmin!\n";
        echo "User ID: {$user->id}\n";
        echo "Role: {$user->role}\n";
        
        // Create new token
        $token = $user->createToken('Bot Testing Token Superadmin')->plainTextToken;
        echo "New API Token: {$token}\n";
    } else {
        echo "❌ User not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}