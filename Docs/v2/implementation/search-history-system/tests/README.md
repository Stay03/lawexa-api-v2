# Search History System Tests

This directory contains comprehensive tests for the Search History System feature.

## Test Script

**File:** `test-search-history-system.sh`

A comprehensive bash script that tests all endpoints and functionality of the Search History System.

### Prerequisites

1. Laravel application must be running (e.g., `php artisan serve`)
2. Database migrations must be completed
3. A valid user authentication token is required

### Usage

The script can be run from two locations:

#### Option 1: From Project Root
```bash
export USER_TOKEN='your-token-here'
bash Docs/v2/implementation/search-history-system/tests/test-search-history-system.sh
```

#### Option 2: From Test Directory
```bash
cd Docs/v2/implementation/search-history-system/tests
export USER_TOKEN='your-token-here'
bash test-search-history-system.sh
```

### Environment Variables

- `USER_TOKEN` (required): Authentication token for a valid user
- `API_URL` (optional): Base API URL, defaults to `http://localhost:8000/api`

### Example

```bash
# Get a token first (using tinker or login)
php artisan tinker
> $user = User::first();
> $token = $user->createToken('test-token')->plainTextToken;
> echo $token;

# Then run the test
export USER_TOKEN='your-token-from-above'
bash Docs/v2/implementation/search-history-system/tests/test-search-history-system.sh
```

### Test Coverage

The script runs 30 comprehensive tests covering:

1. **Pre-testing Verification** (5 tests)
   - Database migration verification
   - Route registration
   - Authentication
   - Test data availability

2. **View Tracking with Search Query** (5 tests)
   - Tracking views with search parameters
   - Normal views without search
   - Different search queries
   - URL encoding handling
   - Long query truncation

3. **Search History Endpoints** (6 tests)
   - Aggregated search history
   - Pagination
   - Date filtering
   - Content type filtering
   - Search within history
   - Sorting

4. **Search Views Endpoints** (6 tests)
   - Individual search views
   - Query-specific views
   - Date filtering
   - Content type filtering
   - Sorting by viewed_at
   - Pagination

5. **Search Statistics** (4 tests)
   - Overall statistics
   - Date-filtered statistics
   - Most searched query
   - Content type breakdown

6. **Privacy & Isolation** (1 test)
   - User data isolation

7. **Response Structure Validation** (3 tests)
   - Search history response structure
   - Search views response structure
   - Statistics response structure

### Output

- Test results are displayed in the terminal with colored output
- A detailed log file is created in this directory: `test-search-history-YYYYMMDD-HHMMSS.log`
- Final summary shows pass/fail counts and pass rate

### Expected Result

When all tests pass, you should see:
```
🎉 ALL TESTS PASSED! 🎉
Pass Rate: 100.0%
```
