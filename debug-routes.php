<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\Route;

// Create a mock application to test route matching
$app = new Application();
$app->singleton('config', function () {
    return new \Illuminate\Config\Repository([]);
});

// Create a router instance
$router = new Router($app['events'] = new \Illuminate\Events\Dispatcher($app), $app);

// Test the URI patterns that would be generated
echo "=== URI Pattern Analysis ===\n";

// Simulate the route structure from your routes/api.php
$testRoutes = [
    'api/admin/cases' => 'AdminCaseController@index',
    'api/admin/cases/{case}' => 'AdminCaseController@show',
];

foreach ($testRoutes as $uri => $action) {
    echo "Route URI: {$uri}\n";
    
    // Test the str_contains logic
    $adminCheck = str_contains($uri, 'admin/cases');
    echo "str_contains('{$uri}', 'admin/cases'): " . ($adminCheck ? 'TRUE' : 'FALSE') . "\n";
    
    // Test specific case ID
    if (str_contains($uri, '{case}')) {
        $testUri = str_replace('{case}', '7195', $uri);
        echo "With case ID 7195: {$testUri}\n";
        $adminCheckWithId = str_contains($testUri, 'admin/cases');
        echo "str_contains('{$testUri}', 'admin/cases'): " . ($adminCheckWithId ? 'TRUE' : 'FALSE') . "\n";
    }
    
    echo "---\n";
}

echo "\n=== Route Model Binding Analysis ===\n";

// Test the route model binding logic
$routeModelBindingTest = function ($value, $routeUri) {
    echo "Testing route model binding for:\n";
    echo "  Value: {$value}\n";
    echo "  Route URI: {$routeUri}\n";
    
    if (str_contains($routeUri, 'admin/cases')) {
        echo "  Result: Would bind by ID (admin route detected)\n";
        echo "  Query: CourtCase::findOrFail({$value})\n";
    } else {
        echo "  Result: Would bind by slug (user route)\n";
        echo "  Query: CourtCase::where('slug', '{$value}')->firstOrFail()\n";
    }
    echo "---\n";
};

// Test various scenarios
$routeModelBindingTest('7195', 'api/admin/cases/7195');
$routeModelBindingTest('7195', 'api/admin/cases/{case}');
$routeModelBindingTest('some-slug', 'api/cases/some-slug');
$routeModelBindingTest('some-slug', 'api/cases/{case}');

echo "\n=== Regex Pattern Analysis ===\n";

// Test the regex pattern constraint
$regex = '[0-9]+';
$testValues = ['7195', 'abc', '123abc', ''];

foreach ($testValues as $value) {
    $matches = preg_match("/^{$regex}$/", $value);
    echo "Value '{$value}' matches regex '{$regex}': " . ($matches ? 'TRUE' : 'FALSE') . "\n";
}

echo "\n=== Potential Issues ===\n";
echo "1. The route model binding checks for 'admin/cases' in the route URI\n";
echo "2. Laravel's route URIs during model binding include the parameter placeholders\n";
echo "3. The actual resolved URI (api/admin/cases/7195) should match the pattern\n";
echo "4. The regex constraint [0-9]+ should allow numeric values like 7195\n";
echo "5. Check if there are middleware or other route conflicts\n";

?>