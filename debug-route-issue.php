<?php

echo "=== Debugging Admin Case Route Issue ===\n\n";

// 1. Analyze the route model binding pattern
echo "1. Route Model Binding Analysis:\n";
echo "   Pattern to check: str_contains(\$route->uri(), 'admin/cases')\n";
echo "   Expected URI for GET /api/admin/cases/7195: 'api/admin/cases/{case}'\n";
echo "   Result: " . (str_contains('api/admin/cases/{case}', 'admin/cases') ? 'MATCH' : 'NO MATCH') . "\n\n";

// 2. Check the regex constraint
echo "2. Regex Constraint Analysis:\n";
echo "   Pattern: ->where('case', '[0-9]+')\n";
echo "   Test value '7195': " . (preg_match('/^[0-9]+$/', '7195') ? 'MATCH' : 'NO MATCH') . "\n\n";

// 3. Analyze potential conflicts
echo "3. Potential Route Conflicts:\n";
echo "   From the routes/api.php structure:\n";
echo "   - User routes: Route::get('{case}', [CaseController::class, 'show']) [line 103]\n";
echo "   - Admin routes: Route::get('{case}', [AdminCaseController::class, 'show'])->where('case', '[0-9]+') [line 147]\n";
echo "   \n";
echo "   Key observations:\n";
echo "   - User routes are registered BEFORE admin routes\n";
echo "   - User routes have NO regex constraint\n";
echo "   - Admin routes are within middleware groups and prefixes\n\n";

// 4. Route registration order analysis
echo "4. Route Registration Order Analysis:\n";
echo "   In routes/api.php:\n";
echo "   Line 60-104: User routes (auth:sanctum middleware)\n";
echo "   Line 103: Route::get('{case}', [CaseController::class, 'show']) - NO CONSTRAINT\n";
echo "   \n";
echo "   Line 115-177: Admin routes (role:admin,researcher,superadmin + auth:sanctum middleware)\n";
echo "   Line 147: Route::get('{case}', [AdminCaseController::class, 'show'])->where('case', '[0-9]+') - WITH CONSTRAINT\n";
echo "   \n";
echo "   POTENTIAL ISSUE: Laravel might be matching the user route first!\n\n";

// 5. URI patterns comparison
echo "5. Full URI Pattern Comparison:\n";
echo "   User route full pattern: 'api/cases/{case}'\n";
echo "   Admin route full pattern: 'api/admin/cases/{case}'\n";
echo "   \n";
echo "   These are different paths, so no direct conflict expected.\n\n";

// 6. Model binding execution analysis
echo "6. Model Binding Execution Flow:\n";
echo "   When accessing GET /api/admin/cases/7195:\n";
echo "   1. Laravel finds route: api/admin/cases/{case}\n";
echo "   2. Applies regex constraint: [0-9]+ (7195 matches)\n";
echo "   3. Executes route model binding with:\n";
echo "      - value: '7195'\n";
echo "      - route->uri(): 'api/admin/cases/{case}'\n";
echo "   4. str_contains('api/admin/cases/{case}', 'admin/cases') = TRUE\n";
echo "   5. Executes: CourtCase::findOrFail('7195')\n";
echo "   6. Passes bound model to AdminCaseController::show()\n\n";

// 7. Most likely causes
echo "7. Most Likely Causes of 'Endpoint not found':\n";
echo "   A. Middleware authentication failure\n";
echo "   B. Role-based access control rejection\n";
echo "   C. Route caching issues\n";
echo "   D. Case with ID 7195 doesn't exist (but we verified it does)\n";
echo "   E. Model binding throws ModelNotFoundException\n";
echo "   F. Controller method throws an exception\n\n";

// 8. Debug recommendations
echo "8. Debug Recommendations:\n";
echo "   1. Check authentication: Is user logged in with proper token?\n";
echo "   2. Check user roles: Does user have admin/researcher/superadmin role?\n";
echo "   3. Clear route cache: php artisan route:clear\n";
echo "   4. Check logs: Look for authentication/authorization errors\n";
echo "   5. Test with middleware disabled temporarily\n";
echo "   6. Verify case 7195 exists and is accessible\n\n";

echo "=== End Debug Analysis ===\n";

?>