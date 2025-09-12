<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Create new admin user
    $user = User::create([
        'name' => 'Bot Test Admin',
        'email' => 'bot-test-admin@example.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    echo "✅ Admin user created successfully!\n";
    echo "Email: bot-test-admin@example.com\n";
    echo "Password: password123\n";
    echo "Role: admin\n";
    echo "User ID: {$user->id}\n";
    
    // Create a token for API testing
    $token = $user->createToken('Bot Testing Token')->plainTextToken;
    echo "API Token: {$token}\n";
    echo "\nYou can now use this token for testing bot filtering endpoints.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating admin user: " . $e->getMessage() . "\n";
    
    // Try to find existing user and create token
    try {
        $existingUser = User::where('email', 'bot-test-admin@example.com')->first();
        if ($existingUser) {
            echo "🔍 Found existing user, creating new token...\n";
            $token = $existingUser->createToken('Bot Testing Token')->plainTextToken;
            echo "API Token: {$token}\n";
        }
    } catch (Exception $e2) {
        echo "❌ Error with existing user: " . $e2->getMessage() . "\n";
    }
}