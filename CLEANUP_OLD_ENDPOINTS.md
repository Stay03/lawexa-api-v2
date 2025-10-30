# Cleanup Reminder: Old Sequential Endpoint

## Background

A new `/content/sequential-pure` endpoint has been implemented to replace the old `/content/sequential` endpoint. The new endpoint provides a cleaner, more optimized structure specifically designed for frontend lazy loading with:

- All fields at root level (no `content` wrapper)
- Both division and provision fields on every item
- Optional breadcrumbs on each item
- Pure flat list structure

## Action Required

Once the frontend has **fully migrated** to the new `/content/sequential-pure` endpoint and is no longer using `/content/sequential`:

### 1. Remove Old Endpoint

**File:** `app/Http/Controllers/StatuteContentController.php`

Delete the `sequential()` method (lines ~111-141)

### 2. Remove Old Route

**File:** `routes/api.php`

Delete or comment out the line:
```php
Route::get('{statute}/content/sequential', [App\Http\Controllers\StatuteContentController::class, 'sequential']);
```

### 3. Update Service Layer (Optional)

**File:** `app/Services/SequentialNavigatorService.php`

Consider removing these methods if they're no longer used:
- `loadBefore()` (lines ~33-63)
- `loadAfter()` (lines ~75-105)
- `loadRange()` (lines ~117-153)
- `transformResults()` (lines ~332-368)
- `transformResultsFlat()` (lines ~376-391)

**Note:** Only remove if they're not being used by other parts of the codebase.

### 4. Update Documentation

Remove any API documentation that references:
- `/api/statutes/{statute}/content/sequential`
- `format=nested` or `format=flat` query parameters

### 5. Update Tests

**File:** `tests/Feature/SequentialPureApiTest.php`

This test file already covers the new endpoint. The old endpoint tests can be removed if they exist.

---

## Migration Timeline

**DO NOT DELETE** the old endpoint until:
1. ✅ Frontend team confirms they've fully migrated to `sequential-pure`
2. ✅ All frontend environments (dev, staging, production) are using the new endpoint
3. ✅ No API calls to `/content/sequential` appear in logs for at least 2 weeks

## Benefits of Cleanup

- **Reduced maintenance burden** - One endpoint instead of two
- **Clearer API surface** - Less confusion about which endpoint to use
- **Smaller codebase** - Remove ~300 lines of code
- **Better performance** - No need to maintain backward compatibility

---

## Contact

If you have questions about this cleanup, contact the backend team or reference the PR that introduced sequential-pure.

**Created:** $(date)
**Status:** Pending frontend migration
