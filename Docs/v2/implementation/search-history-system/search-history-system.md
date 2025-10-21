# Search History System - Implementation Documentation

## Table of Contents
1. [Overview](#overview)
2. [Requirements & Business Rules](#requirements--business-rules)
3. [Database Design](#database-design)
4. [Model Implementation](#model-implementation)
5. [Service Layer](#service-layer)
6. [Middleware Layer](#middleware-layer)
7. [API Resources](#api-resources)
8. [Controllers](#controllers)
9. [Routing](#routing)
10. [Integration Points](#integration-points)
11. [Database Compatibility](#database-compatibility)
12. [Testing Guidelines](#testing-guidelines)
13. [API Documentation](#api-documentation)
14. [Future Extensions](#future-extensions)

---

## Overview

### Purpose
The Search History System tracks user search queries and links them to subsequent content views. This enables:
- Understanding which searches lead to actual engagement (views)
- Building user search history with resulting interactions
- Analyzing search effectiveness and patterns
- Improving search results based on user behavior

### User Stories

**As a User:**
- When I search for "James v John" and click on a case, the system remembers this was a search-initiated view
- I can see my search history with the content I actually viewed from each search
- I can revisit content I found through previous searches

**As an Admin/Analyst:**
- I can see which searches led to the most views
- I can identify searches that resulted in no views (potential content gaps)
- I can analyze search patterns to improve the platform
- I can see which content types are most commonly accessed via search

**As a Developer:**
- The system automatically captures search context without complex frontend logic
- Search tracking works seamlessly with existing view tracking infrastructure
- Database queries for search history are efficient and performant

### Key Features
- Captures search queries when users view content from search results
- Works with all searchable content types (cases, statutes, notes, divisions, provisions, schedules)
- Differentiates between search-initiated views and direct/browsing views
- Provides aggregated search history (unique searches with view counts)
- Provides detailed view history (individual views from searches)
- Cross-database compatible (MySQL and SQLite)
- Minimal frontend changes required
- No impact on existing view tracking performance

---

## Requirements & Business Rules

### Functional Requirements

1. **Search Query Capture**
   - When a user views content from a search result, the search query is captured
   - Search queries are stored verbatim (no normalization or stemming)
   - Maximum search query length: 500 characters
   - Search queries are case-sensitive as entered by user
   - Empty or whitespace-only queries are not stored

2. **View Classification**
   - Views are automatically classified as search-initiated or non-search
   - `is_from_search` flag is set to `true` when `search_query` is present
   - `is_from_search` flag is set to `false` for normal browsing views
   - Classification happens server-side (not dependent on frontend)

3. **Search Context Passing**
   - Frontend includes `search_query` parameter when viewing from search results
   - Parameter format: `GET /cases/{slug}?search_query=James+v+John`
   - Search query is URL-encoded by frontend
   - Backend URL-decodes and stores the original query
   - Works with all view-tracked endpoints (cases, statutes, notes, etc.)

4. **Data Retention**
   - Search queries are stored indefinitely (same retention as views)
   - No automatic cleanup (relies on existing view cleanup policies)
   - Search queries are immutable once recorded
   - Historical search data enables long-term trend analysis

5. **Privacy Considerations**
   - Search queries are associated with user_id (for authenticated users)
   - Search queries are associated with session/IP (for guest users)
   - Search history is private to each user
   - Aggregated/anonymized search analytics may be exposed to admins
   - Users should be able to clear their search history (future feature)

### Non-Functional Requirements

1. **Performance**
   - Adding search tracking should not slow down existing view tracking
   - Search history queries should be optimized with proper indexes
   - Search history endpoints should support pagination (default: 15 per page)
   - Maximum response time for search history: <500ms for typical queries

2. **Database Compatibility**
   - Migration must work on both MySQL 8.0+ and SQLite 3.35+
   - Use TEXT data type for cross-database compatibility
   - BOOLEAN type must be compatible with both databases
   - Indexes should work efficiently on both databases

3. **Backward Compatibility**
   - Existing views without search_query remain valid
   - New columns are nullable (no data migration required)
   - Existing API responses unchanged
   - New endpoints are additive (no breaking changes)

4. **Error Handling**
   - Invalid/oversized search queries are truncated, not rejected
   - Missing search_query parameter is treated as non-search view
   - Database errors during search tracking should not fail the view request
   - Malformed search queries should be sanitized before storage

---

## Database Design

### Schema Changes

**Migration:** `add_search_tracking_to_model_views_table.php`

**Table:** `model_views`

**New Columns:**

| Column Name | Type | Nullable | Default | Description |
|------------|------|----------|---------|-------------|
| `search_query` | TEXT | YES | NULL | The search query that led to this view |
| `is_from_search` | BOOLEAN | NO | false | Whether this view came from a search |

**Indexes to Add:**

```
Index: idx_model_views_search_query
- Columns: is_from_search, search_query(255)
- Type: BTREE
- Purpose: Efficiently query views by search status and query

Index: idx_model_views_user_search
- Columns: user_id, is_from_search, viewed_at
- Type: BTREE
- Purpose: User's search history chronologically

Index: idx_model_views_search_viewable
- Columns: is_from_search, viewable_type, viewable_id
- Type: BTREE
- Purpose: Find all searches leading to specific content
```

### Migration Structure

**Up Migration:**
```
1. Add search_query column (TEXT, NULLABLE)
2. Add is_from_search column (BOOLEAN, DEFAULT false, NOT NULL)
3. Add index idx_model_views_search_query
4. Add index idx_model_views_user_search
5. Add index idx_model_views_search_viewable
```

**Down Migration:**
```
1. Drop index idx_model_views_search_viewable
2. Drop index idx_model_views_user_search
3. Drop index idx_model_views_search_query
4. Drop column is_from_search
5. Drop column search_query
```

### Data Constraints

- `search_query` max length: 500 characters (enforced at service layer before insert)
- `is_from_search` is automatically derived from presence of `search_query`
- No foreign key constraints (search_query is freeform text)
- No unique constraints (same search can occur multiple times)

### Database-Specific Considerations

**MySQL:**
- Use `TEXT` type for search_query (stores up to 65,535 chars, but we limit to 500)
- Use `TINYINT(1)` for is_from_search boolean
- Prefix index on search_query (first 255 chars) for efficient indexing
- Use `utf8mb4` charset for international character support

**SQLite:**
- Use `TEXT` type for search_query (SQLite has no size limit)
- Use `INTEGER` (0/1) for is_from_search boolean
- SQLite doesn't support prefix indexes, but TEXT indexing works well for our use case
- SQLite supports full-text search if needed in the future

---

## Model Implementation

### ModelView Model Updates

**File:** `app/Models/ModelView.php`

**Fillable Attributes:**
Add to existing `$fillable` array:
```
'search_query',
'is_from_search',
```

**Casts:**
Add to `$casts` array:
```
'is_from_search' => 'boolean',
```

**Scopes:**

**1. Search Views Scope**
```
scopeFromSearch($query)
- Filters views that came from search (is_from_search = true)
- Returns: Builder
```

**2. Non-Search Views Scope**
```
scopeNotFromSearch($query)
- Filters views that didn't come from search (is_from_search = false)
- Returns: Builder
```

**3. By Search Query Scope**
```
scopeBySearchQuery($query, $searchQuery)
- Filters views by specific search query (exact match)
- Parameters:
  - $searchQuery: string
- Returns: Builder
```

**4. Similar Search Queries Scope**
```
scopeSimilarSearchQueries($query, $searchQuery)
- Filters views by similar search queries (LIKE match)
- Parameters:
  - $searchQuery: string
- Returns: Builder
- Example: "james" matches "James v John", "james case", etc.
```

**Static Methods:**

**1. Update recordView() Method**
```
recordView(array $viewData): ?ModelView
- Add support for search_query and is_from_search in $viewData array
- Automatically set is_from_search = true if search_query is not empty
- Truncate search_query to 500 chars if longer
- Existing logic remains unchanged
```

**Accessor Methods:**

**1. getSearchQueryAttribute**
```
- Returns search_query or null
- No transformation needed
```

**2. getIsFromSearchAttribute**
```
- Returns boolean
- Already handled by $casts
```

### Updated ModelView Structure

**Complete fillable array:**
```
[
    // Existing fields
    'viewable_type',
    'viewable_id',
    'user_id',
    'session_id',
    'ip_address',
    'user_agent_hash',
    'user_agent',
    'ip_country',
    'ip_country_code',
    'ip_continent',
    'ip_continent_code',
    'ip_region',
    'ip_city',
    'ip_timezone',
    'device_type',
    'device_platform',
    'device_browser',
    'viewed_at',
    'is_bot',
    'bot_name',
    'is_search_engine',
    'is_social_media',

    // New fields
    'search_query',
    'is_from_search',
]
```

---

## Service Layer

### ViewTrackingService Updates

**File:** `app/Services/ViewTrackingService.php`

**Method Updates:**

**1. extractViewData() Method**

**Current signature:**
```
private function extractViewData(Model $model, Request $request): array
```

**Updates needed:**
- Extract `search_query` from request query parameters
- Check for parameter name: `search_query` or `q`
- URL-decode the search query
- Trim whitespace
- Truncate to 500 characters if longer
- Set `is_from_search` based on presence of non-empty search_query
- Sanitize search_query (remove null bytes, control characters)

**New data keys in returned array:**
```
[
    // ... existing keys ...
    'search_query' => $searchQuery,           // string|null
    'is_from_search' => !empty($searchQuery), // boolean
]
```

**Logic flow:**
```
1. Get search_query from request: $request->query('search_query') ?? $request->query('q')
2. If search_query exists:
   a. URL decode: urldecode($searchQuery)
   b. Trim whitespace: trim($searchQuery)
   c. Remove control characters and null bytes
   d. Truncate to 500 chars: Str::limit($searchQuery, 500, '')
   e. If result is empty string, set to null
3. Set is_from_search = !empty($searchQuery)
4. Add to viewData array
```

**2. trackView() Method**

**Current signature:**
```
public function trackView(Model $model, Request $request): void
```

**Updates needed:**
- No changes required (already passes full request to extractViewData)
- extractViewData() will handle search query extraction

**3. canTrackView() Method**

**Current signature:**
```
public function canTrackView(array $viewData, Request $request = null): bool
```

**Updates needed:**
- No changes required
- Search views follow same cooldown rules as non-search views

**4. recordView() Method**

**Current signature:**
```
public function recordView(array $viewData): ?ModelView
```

**Updates needed:**
- No changes required (delegates to ModelView::recordView)
- ModelView::recordView() will handle the new fields

**5. getViewStats() Method**

**Current signature:**
```
public function getViewStats(Model $model): array
```

**Updates needed (optional):**
- Could add search-specific stats:
  - `search_views_count`: views from search
  - `direct_views_count`: views not from search
  - `top_search_queries`: most common search queries leading to this content

This is optional and can be added later if needed.

### New Service Methods (Optional)

These are helper methods that could be added to ViewTrackingService or a new SearchHistoryService:

**1. getSearchHistory()**
```
getSearchHistory(?int $userId, array $filters = []): Collection
- Get unique search queries for a user
- Parameters:
  - $userId: null for current user's session/guest views
  - $filters: ['date_from', 'date_to', 'content_type']
- Returns: Collection of search queries with metadata
- Aggregates: count of views per query, last searched timestamp
```

**2. getSearchViews()**
```
getSearchViews(?int $userId, ?string $searchQuery = null): Collection
- Get individual views from searches
- Parameters:
  - $userId: null for current user
  - $searchQuery: filter by specific query (optional)
- Returns: Collection of ModelView instances with relationships loaded
```

**3. getMostPopularSearches()**
```
getMostPopularSearches(int $limit = 10, array $filters = []): Collection
- Get most common search queries (admin function)
- Parameters:
  - $limit: number of results
  - $filters: ['date_from', 'date_to', 'content_type', 'user_type']
- Returns: Collection with query, view_count, unique_users
```

---

## Middleware Layer

### ViewTrackingMiddleware Updates

**File:** `app/Http/Middleware/ViewTrackingMiddleware.php`

**Current Flow:**
```
1. handle() method receives request
2. Checks if route should be tracked
3. Checks guest view limits (before response)
4. Returns response via next($request)
5. After response (terminate), calls ViewTrackingService::trackView()
```

**Updates Needed:**

**None required!**

The middleware already passes the full `$request` object to `ViewTrackingService::trackView()`, which means:
- Query parameters (including `search_query`) are already available
- ViewTrackingService::extractViewData() can extract search_query from request
- No middleware changes needed

**Why it works:**
```
// Existing code in terminate() method:
$this->viewTrackingService->trackView($model, $request);

// The $request object contains all query parameters:
// GET /cases/my-case?search_query=James+v+John
// $request->query('search_query') === 'James v John'
```

**Optional Enhancement:**

If you want to validate/sanitize search_query at middleware level:

**Add method:**
```
private function extractSearchQuery(Request $request): ?string
- Extract and sanitize search_query from request
- Validate length (max 500)
- Remove dangerous characters
- Return sanitized query or null
```

**Use in terminate():**
```
// Before calling trackView, optionally pre-process search_query
$searchQuery = $this->extractSearchQuery($request);
if ($searchQuery && strlen($searchQuery) > 500) {
    $searchQuery = substr($searchQuery, 0, 500);
}
```

However, this is **optional** since ViewTrackingService already does this sanitization.

---

## API Resources

### New Resource: SearchHistoryResource

**File:** `app/Http/Resources/SearchHistoryResource.php`

**Purpose:** Format aggregated search history data

**Structure:**
```
{
    "search_query": "James v John",
    "views_count": 15,
    "unique_content_count": 8,
    "content_types": {
        "cases": 5,
        "statutes": 2,
        "notes": 1
    },
    "first_searched_at": "2025-10-20T14:30:00Z",
    "last_searched_at": "2025-10-21T09:15:00Z",
    "sample_views": [
        {
            "id": 123,
            "type": "case",
            "title": "James v John [2023] UKSC 42",
            "slug": "james-v-john-2023-uksc-42",
            "viewed_at": "2025-10-21T09:15:00Z"
        }
        // ... up to 3 sample views
    ]
}
```

**Fields:**
- `search_query`: The original search query
- `views_count`: Total number of views from this search
- `unique_content_count`: Number of unique items viewed
- `content_types`: Breakdown by content type
- `first_searched_at`: When this query was first used
- `last_searched_at`: Most recent use
- `sample_views`: Array of recent views (max 3)

### New Resource: SearchViewResource

**File:** `app/Http/Resources/SearchViewResource.php`

**Purpose:** Format individual search-initiated views

**Structure:**
```
{
    "id": 45678,
    "search_query": "James v John",
    "viewed_at": "2025-10-21T09:15:00Z",
    "content": {
        "type": "case",
        "id": 123,
        "title": "James v John [2023] UKSC 42",
        "slug": "james-v-john-2023-uksc-42",
        "url": "/cases/james-v-john-2023-uksc-42"
    },
    "user": {
        "id": 456,
        "name": "John Doe"
    },
    "location": {
        "country": "United States",
        "city": "San Francisco"
    },
    "device": {
        "type": "desktop",
        "platform": "Windows",
        "browser": "Chrome"
    }
}
```

**Fields:**
- `id`: View ID
- `search_query`: The search query that led to this view
- `viewed_at`: Timestamp
- `content`: The viewed content details (polymorphic)
- `user`: User who performed the search (if authenticated)
- `location`: IP geolocation data
- `device`: Device information

### Updating Existing Resources

**Files:** `CaseResource.php`, `StatuteResource.php`, `NoteResource.php`, etc.

**No changes required** for existing resources. The view tracking is server-side only and doesn't affect the resource structure returned to the frontend.

However, if you want to expose search context in the response (optional):

**Add to resource:**
```
'last_view': [
    'viewed_at' => $this->last_view_at,
    'from_search' => $this->last_view_from_search,
    'search_query' => $this->last_view_search_query,
]
```

This would require adding accessor methods to the main models (CourtCase, Statute, etc.) via the HasViewTracking trait.

---

## Controllers

### New Controller: SearchHistoryController

**File:** `app/Http/Controllers/SearchHistoryController.php`

**Purpose:** Handle search history queries

**Important:** All paginated responses must include both `meta` and `links` following Laravel's standard pagination format. Use Laravel's `paginate()` method which automatically includes:
- `meta`: { current_page, from, last_page, per_page, to, total }
- `links`: { first, last, prev, next }

**Endpoints:**

**1. GET /search-history**

**Method:** `index()`

**Purpose:** Get aggregated search history for current user

**Authorization:**
- Authenticated users: see their own searches
- Guests: see searches from their session/IP

**Request Parameters:**
```
- page: integer (pagination)
- per_page: integer (1-50, default 15)
- date_from: date (Y-m-d format)
- date_to: date (Y-m-d format)
- content_type: string (case, statute, note, etc.)
- sort_by: enum (last_searched, first_searched, views_count, query)
- sort_order: enum (asc, desc)
- search: string (filter search queries containing this text)
```

**Response:**
```
{
    "data": [
        // Array of SearchHistoryResource
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 45,
        "last_page": 3
    },
    "stats": {
        "total_searches": 45,
        "total_views_from_search": 187,
        "unique_queries": 42,
        "date_range": {
            "from": "2025-01-01",
            "to": "2025-10-21"
        }
    }
}
```

**Logic:**
```
1. Get authenticated user or guest identifier
2. Query model_views where is_from_search = true
3. Filter by user_id (or session/IP for guests)
4. Apply date filters
5. Apply content_type filter (viewable_type)
6. Group by search_query
7. Aggregate: count(*), count(distinct viewable_id), min(viewed_at), max(viewed_at)
8. Apply search filter (WHERE search_query LIKE %search%)
9. Sort by requested field
10. Paginate results using Laravel's paginate() method
11. For each query, get sample views (limit 3)
12. Return SearchHistoryResource collection with pagination links
```

**Note:** Use Laravel's `ResourceCollection::collection($query->paginate($perPage))` which automatically includes:
- `meta`: Pagination metadata (current_page, from, last_page, per_page, to, total)
- `links`: Pagination URLs (first, last, prev, next)

**2. GET /search-history/views**

**Method:** `views()`

**Purpose:** Get individual views initiated from searches

**Authorization:** Same as index()

**Request Parameters:**
```
- page: integer
- per_page: integer (1-50, default 15)
- search_query: string (filter by specific search query)
- date_from: date
- date_to: date
- content_type: string
- sort_by: enum (viewed_at, query)
- sort_order: enum (asc, desc)
```

**Response:**
```
{
    "data": [
        // Array of SearchViewResource
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 187,
        "last_page": 13
    },
    "filters": {
        "search_query": "James v John",
        "content_type": null,
        "date_from": null,
        "date_to": null
    }
}
```

**Logic:**
```
1. Get authenticated user or guest identifier
2. Query model_views where is_from_search = true
3. Filter by user_id (or session/IP for guests)
4. Apply search_query filter (exact match if provided)
5. Apply date filters
6. Apply content_type filter
7. Eager load relationships: viewable (polymorphic), user
8. Sort by requested field
9. Paginate results using Laravel's paginate() method
10. Return SearchViewResource collection with pagination links
```

**Note:** Laravel's pagination automatically includes both `meta` and `links` in the response.

**3. GET /search-history/stats**

**Method:** `stats()`

**Purpose:** Get overall search statistics for current user

**Authorization:** Same as index()

**Request Parameters:**
```
- date_from: date
- date_to: date
```

**Response:**
```
{
    "total_searches": 45,
    "total_views_from_search": 187,
    "unique_queries": 42,
    "views_per_search_avg": 4.2,
    "most_searched_query": "contract law",
    "most_viewed_from_search": {
        "type": "case",
        "id": 123,
        "title": "James v John",
        "views": 15
    },
    "content_type_breakdown": {
        "cases": 120,
        "statutes": 45,
        "notes": 22
    },
    "period": {
        "from": "2025-01-01",
        "to": "2025-10-21",
        "days": 294
    }
}
```

**Logic:**
```
1. Get authenticated user or guest identifier
2. Query aggregated statistics from model_views
3. Calculate metrics
4. Return stats object
```

### Admin Controller (Optional)

**File:** `app/Http/Controllers/Admin/SearchAnalyticsController.php`

**Purpose:** Admin-level search analytics

**Endpoints:**

**1. GET /admin/search-analytics/popular**

**Purpose:** Get most popular searches across all users

**Authorization:** Admin, Researcher, Superadmin

**Response:**
```
{
    "data": [
        {
            "search_query": "contract law",
            "total_views": 1543,
            "unique_users": 87,
            "avg_views_per_user": 17.7,
            "first_searched": "2025-01-01T00:00:00Z",
            "last_searched": "2025-10-21T12:00:00Z"
        }
    ]
}
```

**2. GET /admin/search-analytics/ineffective**

**Purpose:** Find searches that led to few/no views

**Response:**
```
{
    "data": [
        {
            "search_query": "some obscure term",
            "total_searches": 45,
            "total_views": 2,
            "conversion_rate": 0.04,
            "suggests_content_gap": true
        }
    ]
}
```

This helps identify:
- Searches with no results
- Searches where users don't click anything
- Potential content gaps

---

## Routing

### New Routes

**File:** `routes/api.php`

**User Routes (require authentication or guest tracking):**

```php
Route::middleware(['optional.auth'])->group(function () {

    // Search History Routes
    Route::prefix('search-history')->group(function () {
        Route::get('/', [SearchHistoryController::class, 'index'])
            ->name('search-history.index');

        Route::get('/views', [SearchHistoryController::class, 'views'])
            ->name('search-history.views');

        Route::get('/stats', [SearchHistoryController::class, 'stats'])
            ->name('search-history.stats');
    });

});
```

**Admin Routes:**

```php
Route::middleware(['auth:sanctum', 'role:admin,researcher,superadmin'])->group(function () {

    Route::prefix('admin/search-analytics')->group(function () {
        Route::get('/popular', [SearchAnalyticsController::class, 'popular'])
            ->name('admin.search-analytics.popular');

        Route::get('/ineffective', [SearchAnalyticsController::class, 'ineffective'])
            ->name('admin.search-analytics.ineffective');

        Route::get('/trends', [SearchAnalyticsController::class, 'trends'])
            ->name('admin.search-analytics.trends');
    });

});
```

**Middleware:**
- `optional.auth`: Allow both authenticated and guest access
- `throttle:api`: Standard rate limiting
- `role:admin,researcher,superadmin`: Role-based access control for admin routes

**Route Naming:**
- Follows Laravel conventions
- Prefixed with `search-history` for user routes
- Prefixed with `admin.search-analytics` for admin routes

---

## Integration Points

### Frontend Integration

**How Frontend Passes Search Context:**

**1. User performs search:**
```
GET /api/cases?search=James+v+John
```

**2. User clicks on a result to view details:**
```
GET /api/cases/james-v-john-2023-uksc-42?search_query=James+v+John
```

**Key points:**
- Frontend includes `search_query` parameter when navigating from search results
- The query parameter contains the exact search string used
- URL encoding is handled by frontend (JavaScript `encodeURIComponent()`)
- Backend URL decodes automatically

**Example Frontend Code (React/Vue/Angular):**

```javascript
// When user clicks search result
const handleViewFromSearch = (caseSlug, searchQuery) => {
    // Navigate to detail page with search context
    router.push({
        path: `/cases/${caseSlug}`,
        query: { search_query: searchQuery }
    });
};

// API call includes search_query
fetch(`/api/cases/${caseSlug}?search_query=${encodeURIComponent(searchQuery)}`)
```

**Alternative: Referrer-Based Detection (Optional)**

If frontend cannot/doesn't send search_query parameter, backend could:
- Check Referer header
- Extract search query from referrer URL
- Example: Referer: `https://example.com/search?q=James+v+John`

However, explicit parameter is more reliable than Referer parsing.

### Backend Processing Flow

**Complete flow from search to view:**

```
1. User searches:
   Frontend: GET /api/cases?search=James+v+John
   Backend: Returns search results (CaseCollection)

2. User clicks result:
   Frontend: GET /api/cases/james-v-john-2023-uksc-42?search_query=James+v+John

3. Request hits ViewTrackingMiddleware:
   - handle() executes before response
   - Checks guest view limits
   - Returns response via next($request)

4. Response generated:
   - CaseController::show() loads case
   - Returns CaseResource

5. Response sent to client

6. ViewTrackingMiddleware::terminate() executes:
   - Calls ViewTrackingService::trackView($case, $request)

7. ViewTrackingService::trackView():
   - Calls extractViewData($case, $request)

8. ViewTrackingService::extractViewData():
   - Extracts search_query from $request->query('search_query')
   - URL decodes: "James v John"
   - Sanitizes and truncates
   - Sets is_from_search = true (because search_query exists)
   - Returns viewData array with search fields

9. ViewTrackingService::trackView() continues:
   - Checks canTrackView() for cooldown
   - Calls recordView($viewData)

10. ModelView::recordView():
    - Creates new model_views record
    - Includes search_query and is_from_search fields
    - Saves to database

11. Done!
    - View tracked with search context
    - Available in search history queries
```

### API Endpoints Affected

**All view-tracked endpoints support search_query parameter:**

- `GET /cases/{case}?search_query=...`
- `GET /statutes/{statute}?search_query=...`
- `GET /statutes/{statute}/divisions/{division}?search_query=...`
- `GET /statutes/{statute}/provisions/{provision}?search_query=...`
- `GET /statutes/{statute}/schedules/{schedule}?search_query=...`
- `GET /notes/{note}?search_query=...`
- `GET /folders/{folder}?search_query=...`
- `GET /comments/{comment}?search_query=...`

**No endpoint changes required** - just add optional query parameter support.

### Backward Compatibility

**Views without search_query:**
- Existing views have search_query = NULL
- Existing views have is_from_search = false
- Normal browsing (non-search) continues to work
- No migration of existing data needed

**API responses:**
- No breaking changes to existing responses
- New endpoints are additive
- Existing clients work unchanged

---

## Database Compatibility

### MySQL vs SQLite Differences

**Data Types:**

| Column | MySQL | SQLite |
|--------|-------|--------|
| search_query | TEXT | TEXT |
| is_from_search | TINYINT(1) | INTEGER |

**Migration Code:**

```php
// Works on both MySQL and SQLite
Schema::table('model_views', function (Blueprint $table) {
    $table->text('search_query')->nullable();
    $table->boolean('is_from_search')->default(false);
});
```

**How Laravel handles it:**
- `text()` → TEXT in both databases
- `boolean()` → TINYINT(1) in MySQL, INTEGER in SQLite
- Laravel's query builder abstracts the difference

**Index Creation:**

**MySQL:**
```php
// Prefix index (first 255 chars) for TEXT columns
$table->index([DB::raw('is_from_search, search_query(255)')], 'idx_model_views_search_query');
```

**SQLite:**
```php
// SQLite doesn't support prefix indexes, but indexes TEXT columns fine
$table->index(['is_from_search', 'search_query'], 'idx_model_views_search_query');
```

**Cross-compatible migration:**
```php
if (DB::getDriverName() === 'mysql') {
    DB::statement('CREATE INDEX idx_model_views_search_query ON model_views (is_from_search, search_query(255))');
} else {
    Schema::table('model_views', function (Blueprint $table) {
        $table->index(['is_from_search', 'search_query'], 'idx_model_views_search_query');
    });
}
```

**Query Compatibility:**

**Both databases support:**
- `WHERE search_query = 'exact match'` (exact match)
- `WHERE search_query LIKE '%partial%'` (partial match)
- `WHERE is_from_search = true` (boolean filter)
- `ORDER BY search_query ASC` (sorting)
- `GROUP BY search_query` (aggregation)

**Boolean handling:**
- MySQL: `WHERE is_from_search = 1` or `WHERE is_from_search = true`
- SQLite: `WHERE is_from_search = 1`
- Laravel query builder: `->where('is_from_search', true)` works on both

**Text encoding:**
- MySQL: Use `utf8mb4` charset for international characters
- SQLite: UTF-8 by default
- No special handling needed in application code

**Performance Considerations:**

**MySQL:**
- TEXT columns are stored off-page (not in main table)
- Prefix indexes limit index size
- LIKE queries on TEXT can be slow without prefix index
- Consider FULLTEXT index for complex search queries (future enhancement)

**SQLite:**
- TEXT stored inline
- No prefix index limitation
- LIKE queries on indexed TEXT columns are fast
- Consider FTS5 virtual table for full-text search (future enhancement)

**Migration Testing Strategy:**
1. Test migration on MySQL 8.0+ (local/staging)
2. Test migration on SQLite 3.35+ (testing database)
3. Verify indexes created successfully on both
4. Run sample queries to verify performance
5. Test rollback on both databases

---

## Testing Guidelines

### Unit Tests

**File:** `tests/Unit/Services/ViewTrackingServiceTest.php`

**Test Cases:**

**1. Test extractViewData with search_query**
- Given: Request with search_query parameter
- When: extractViewData() called
- Then: Returns array with search_query and is_from_search = true

**2. Test extractViewData without search_query**
- Given: Request without search_query parameter
- When: extractViewData() called
- Then: Returns array with search_query = null and is_from_search = false

**3. Test search_query sanitization**
- Given: Request with malicious search_query (SQL injection, XSS)
- When: extractViewData() called
- Then: Returns sanitized search_query

**4. Test search_query truncation**
- Given: Request with 1000-character search_query
- When: extractViewData() called
- Then: Returns search_query truncated to 500 characters

**5. Test URL decoding**
- Given: Request with URL-encoded search_query ("James%20v%20John")
- When: extractViewData() called
- Then: Returns decoded search_query ("James v John")

### Feature Tests

**File:** `tests/Feature/SearchHistoryTest.php`

**Test Cases:**

**1. Test view tracking with search query**
- Given: Authenticated user
- When: GET /cases/{case}?search_query=James+v+John
- Then: View recorded with search_query and is_from_search = true

**2. Test view tracking without search query**
- Given: Authenticated user
- When: GET /cases/{case}
- Then: View recorded with search_query = null and is_from_search = false

**3. Test search history endpoint**
- Given: User with multiple search-initiated views
- When: GET /search-history
- Then: Returns aggregated search history

**4. Test search views endpoint**
- Given: User with search-initiated views
- When: GET /search-history/views?search_query=James+v+John
- Then: Returns views filtered by search_query

**5. Test guest search tracking**
- Given: Guest user (not authenticated)
- When: Multiple views with search_query
- Then: Search history tracked by session/IP

**6. Test search history privacy**
- Given: Two different users
- When: User A requests search history
- Then: Only sees their own searches (not User B's)

**7. Test pagination**
- Given: User with 50+ search queries
- When: GET /search-history?per_page=15&page=2
- Then: Returns page 2 with 15 results

**8. Test filtering by date**
- Given: User with searches across multiple months
- When: GET /search-history?date_from=2025-10-01&date_to=2025-10-31
- Then: Returns only searches in October

**9. Test filtering by content type**
- Given: User searched and viewed cases and statutes
- When: GET /search-history/views?content_type=case
- Then: Returns only case views from searches

**10. Test admin analytics**
- Given: Admin user
- When: GET /admin/search-analytics/popular
- Then: Returns most popular searches across all users

### Integration Tests

**File:** `tests/Feature/SearchIntegrationTest.php`

**Test Cases:**

**1. Test full search-to-view flow**
- Step 1: Search for cases
- Step 2: Click result (view with search_query)
- Step 3: Check view was tracked
- Step 4: Check search appears in history

**2. Test multiple searches for same content**
- Given: User searches "James v John" and "contract law"
- When: Both searches lead to same case
- Then: Two separate views tracked with different search_queries

**3. Test search history aggregation**
- Given: User views 5 different cases from search "contract law"
- When: GET /search-history
- Then: Shows 1 search query with 5 views

### Database Tests

**File:** `tests/Feature/Database/SearchTrackingMigrationTest.php`

**Test Cases:**

**1. Test migration up**
- When: Run migration
- Then: Columns created, indexes created

**2. Test migration down**
- When: Run rollback
- Then: Columns dropped, indexes dropped

**3. Test MySQL compatibility**
- Given: MySQL database
- When: Run migration
- Then: Correct data types and indexes

**4. Test SQLite compatibility**
- Given: SQLite database
- When: Run migration
- Then: Correct data types and indexes

**5. Test data integrity**
- Given: Existing views in database
- When: Run migration
- Then: Existing views unchanged, new columns nullable

### Performance Tests

**File:** `tests/Feature/Performance/SearchHistoryPerformanceTest.php`

**Test Cases:**

**1. Test search history query performance**
- Given: 10,000 views with 1,000 unique search queries
- When: GET /search-history
- Then: Response time < 500ms

**2. Test search views query performance**
- Given: 10,000 search-initiated views
- When: GET /search-history/views?search_query=X
- Then: Response time < 300ms

**3. Test index effectiveness**
- Given: Large dataset
- When: Query using is_from_search and search_query
- Then: Query uses index (verify with EXPLAIN)

### Test Data Factory

**File:** `database/factories/ModelViewFactory.php`

**Add search-related states:**

```php
public function fromSearch(string $searchQuery = null): Factory
{
    return $this->state(function (array $attributes) use ($searchQuery) {
        return [
            'search_query' => $searchQuery ?? fake()->words(3, true),
            'is_from_search' => true,
        ];
    });
}

public function notFromSearch(): Factory
{
    return $this->state(function (array $attributes) {
        return [
            'search_query' => null,
            'is_from_search' => false,
        ];
    });
}
```

**Usage in tests:**
```php
// Create view from search
ModelView::factory()->fromSearch('James v John')->create();

// Create normal view
ModelView::factory()->notFromSearch()->create();

// Create 10 views from different searches
ModelView::factory()->count(10)->fromSearch()->create();
```

---

## API Documentation

### User Endpoints

**1. GET /api/search-history**

**Description:** Get aggregated search history for current user

**Authentication:** Optional (works for guests too)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| page | integer | No | 1 | Page number |
| per_page | integer | No | 15 | Items per page (max 50) |
| date_from | date | No | null | Start date (Y-m-d) |
| date_to | date | No | null | End date (Y-m-d) |
| content_type | string | No | null | Filter by type (case, statute, note) |
| sort_by | enum | No | last_searched | Sort field (last_searched, first_searched, views_count, query) |
| sort_order | enum | No | desc | asc or desc |
| search | string | No | null | Filter queries containing text |

**Response: 200 OK**

```json
{
    "data": [
        {
            "search_query": "James v John",
            "views_count": 15,
            "unique_content_count": 8,
            "content_types": {
                "cases": 12,
                "statutes": 2,
                "notes": 1
            },
            "first_searched_at": "2025-10-15T10:00:00Z",
            "last_searched_at": "2025-10-21T14:30:00Z",
            "sample_views": [
                {
                    "id": 123,
                    "type": "case",
                    "title": "James v John [2023] UKSC 42",
                    "slug": "james-v-john-2023-uksc-42",
                    "viewed_at": "2025-10-21T14:30:00Z"
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 42
    },
    "links": {
        "first": "https://rest.lawexa.com/api/search-history?page=1",
        "last": "https://rest.lawexa.com/api/search-history?page=3",
        "prev": null,
        "next": "https://rest.lawexa.com/api/search-history?page=2"
    },
    "stats": {
        "total_searches": 42,
        "total_views_from_search": 187,
        "unique_queries": 42
    }
}
```

**Response: 401 Unauthorized** (if guest limits exceeded)

---

**2. GET /api/search-history/views**

**Description:** Get individual views initiated from searches

**Authentication:** Optional

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| page | integer | No | 1 | Page number |
| per_page | integer | No | 15 | Items per page (max 50) |
| search_query | string | No | null | Filter by specific search query |
| date_from | date | No | null | Start date |
| date_to | date | No | null | End date |
| content_type | string | No | null | Filter by content type |
| sort_by | enum | No | viewed_at | Sort field (viewed_at, query) |
| sort_order | enum | No | desc | asc or desc |

**Response: 200 OK**

```json
{
    "data": [
        {
            "id": 45678,
            "search_query": "James v John",
            "viewed_at": "2025-10-21T14:30:00Z",
            "content": {
                "type": "case",
                "id": 123,
                "title": "James v John [2023] UKSC 42",
                "slug": "james-v-john-2023-uksc-42",
                "url": "/cases/james-v-john-2023-uksc-42"
            },
            "device": {
                "type": "desktop",
                "platform": "Windows",
                "browser": "Chrome"
            },
            "location": {
                "country": "United States",
                "city": "San Francisco"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 13,
        "per_page": 15,
        "to": 15,
        "total": 187
    },
    "links": {
        "first": "https://rest.lawexa.com/api/search-history/views?page=1",
        "last": "https://rest.lawexa.com/api/search-history/views?page=13",
        "prev": null,
        "next": "https://rest.lawexa.com/api/search-history/views?page=2"
    },
    "filters": {
        "search_query": null,
        "content_type": null,
        "date_from": null,
        "date_to": null
    }
}
```

---

**3. GET /api/search-history/stats**

**Description:** Get overall search statistics for current user

**Authentication:** Optional

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | null | Start date |
| date_to | date | No | null | End date |

**Response: 200 OK**

```json
{
    "total_searches": 42,
    "total_views_from_search": 187,
    "unique_queries": 42,
    "views_per_search_avg": 4.45,
    "most_searched_query": "contract law",
    "most_viewed_from_search": {
        "type": "case",
        "id": 123,
        "title": "James v John [2023] UKSC 42",
        "views": 15
    },
    "content_type_breakdown": {
        "cases": 145,
        "statutes": 32,
        "notes": 10
    },
    "period": {
        "from": "2025-01-01",
        "to": "2025-10-21",
        "days": 294
    }
}
```

### Admin Endpoints

**1. GET /api/admin/search-analytics/popular**

**Description:** Get most popular searches across all users

**Authentication:** Required (admin, researcher, superadmin)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| limit | integer | No | 20 | Number of results (max 100) |
| date_from | date | No | null | Start date |
| date_to | date | No | null | End date |
| content_type | string | No | null | Filter by content type |

**Response: 200 OK**

```json
{
    "data": [
        {
            "search_query": "contract law",
            "total_views": 1543,
            "unique_users": 87,
            "unique_content": 234,
            "avg_views_per_user": 17.7,
            "first_searched": "2025-01-01T00:00:00Z",
            "last_searched": "2025-10-21T14:00:00Z",
            "content_type_breakdown": {
                "cases": 1200,
                "statutes": 243,
                "notes": 100
            }
        }
    ],
    "meta": {
        "total_queries": 4523,
        "period": {
            "from": "2025-01-01",
            "to": "2025-10-21"
        }
    }
}
```

---

**2. GET /api/admin/search-analytics/ineffective**

**Description:** Find searches with low conversion (few views)

**Authentication:** Required (admin, researcher, superadmin)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| limit | integer | No | 20 | Number of results |
| min_searches | integer | No | 5 | Minimum search count |
| max_conversion | float | No | 0.1 | Maximum view conversion rate |

**Response: 200 OK**

```json
{
    "data": [
        {
            "search_query": "obscure legal term",
            "total_searches": 45,
            "total_views": 2,
            "conversion_rate": 0.04,
            "unique_users": 12,
            "suggests_content_gap": true,
            "last_searched": "2025-10-20T10:00:00Z"
        }
    ],
    "insights": {
        "total_ineffective_queries": 156,
        "potential_content_gaps": 89,
        "avg_conversion_rate": 0.06
    }
}
```

### Frontend Examples

**Example 1: Search and View**

```javascript
// Step 1: User searches
const searchResults = await fetch('/api/cases?search=James+v+John');

// Step 2: User clicks result
const caseSlug = 'james-v-john-2023-uksc-42';
const searchQuery = 'James v John';

// Navigate with search context
window.location.href = `/cases/${caseSlug}?search_query=${encodeURIComponent(searchQuery)}`;

// Or with API call
const caseDetails = await fetch(
    `/api/cases/${caseSlug}?search_query=${encodeURIComponent(searchQuery)}`
);
```

**Example 2: Get Search History**

```javascript
// Get user's search history
const searchHistory = await fetch('/api/search-history?per_page=20&sort_by=last_searched');

const data = await searchHistory.json();
console.log(data.data); // Array of search queries with stats
```

**Example 3: Filter Search Views**

```javascript
// Get all views from a specific search
const searchQuery = 'James v John';
const views = await fetch(
    `/api/search-history/views?search_query=${encodeURIComponent(searchQuery)}`
);

const data = await views.json();
console.log(data.data); // Array of views from this search
```

---

## Future Extensions

### Phase 1 Enhancements (Near-term)

**1. Search Suggestions**
- Use popular searches to suggest queries
- Autocomplete based on historical searches
- "People also searched for..." feature

**2. Search Result Ranking**
- Use click-through data to improve search ranking
- Boost content that gets clicked from searches
- Personalized search results based on history

**3. Search Analytics Dashboard**
- Visual charts of search trends
- Heatmap of popular search terms
- Conversion funnel (search → view → bookmark/download)

**4. User Search Management**
- Clear search history
- Delete individual searches
- Export search history

### Phase 2 Enhancements (Medium-term)

**1. Related Searches**
- "Users who searched for X also searched for Y"
- Find related legal topics
- Cluster similar searches

**2. Search Query Normalization**
- Detect duplicate searches with different wording
- Stemming and lemmatization
- Case-insensitive matching

**3. Failed Search Detection**
- Identify searches with zero results
- Identify searches where no results were clicked
- Prioritize content creation for high-demand failed searches

**4. Search-based Recommendations**
- Recommend content based on search history
- "Based on your searches, you might like..."
- Email notifications for new content matching past searches

### Phase 3 Enhancements (Long-term)

**1. Advanced Search Analytics**
- Machine learning on search patterns
- Predict user intent from search queries
- Identify trending legal topics

**2. Full-Text Search Integration**
- Migrate to Elasticsearch/Meilisearch for advanced search
- Integrate search history with full-text engine
- Support faceted search with history

**3. Search-based Content Gaps**
- Automatic identification of missing content
- Integration with Content Request System
- Auto-suggest content creation based on search demand

**4. Multi-language Search History**
- Support for searches in multiple languages
- Language detection and translation
- Cross-language search recommendations

### Database Optimizations (Future)

**1. Separate Search History Table**
- If search queries get very large, consider separate table
- `search_queries` table with one-to-many to `model_views`
- Reduces data duplication for repeated queries

**2. Full-Text Indexes**
- MySQL: FULLTEXT index on search_query
- SQLite: FTS5 virtual table
- Enables complex search query analysis

**3. Search Query Taxonomy**
- Categorize searches (case law, statutes, procedural, etc.)
- Tag searches with legal topics
- Enable filtering by search category

### Privacy Enhancements (Future)

**1. Anonymized Search Analytics**
- Strip user identifiers from old searches
- Aggregate data for analytics while preserving privacy
- GDPR compliance features

**2. Search History Retention Policies**
- Configurable retention period
- Automatic cleanup of old searches
- User control over data retention

**3. Opt-out Options**
- Allow users to disable search tracking
- Respect Do Not Track headers
- Transparent privacy controls

---

## Implementation Checklist

### Database
- [ ] Create migration file
- [ ] Add search_query column (TEXT, nullable)
- [ ] Add is_from_search column (BOOLEAN, default false)
- [ ] Add indexes (search_query, user_search, search_viewable)
- [ ] Test migration on MySQL
- [ ] Test migration on SQLite
- [ ] Test rollback on both databases

### Models
- [ ] Update ModelView fillable array
- [ ] Add search_query and is_from_search to casts
- [ ] Add fromSearch() scope
- [ ] Add notFromSearch() scope
- [ ] Add bySearchQuery() scope
- [ ] Update recordView() method
- [ ] Update factory with search states

### Services
- [ ] Update ViewTrackingService::extractViewData()
- [ ] Add search query extraction logic
- [ ] Add search query sanitization
- [ ] Add URL decoding
- [ ] Add truncation (500 chars)
- [ ] Test with various input formats

### Controllers
- [ ] Create SearchHistoryController
- [ ] Implement index() method (aggregated history)
- [ ] Implement views() method (individual views)
- [ ] Implement stats() method (statistics)
- [ ] Add validation for query parameters
- [ ] Add pagination
- [ ] Add filtering logic
- [ ] Create SearchAnalyticsController (admin)
- [ ] Implement popular() method
- [ ] Implement ineffective() method

### Resources
- [ ] Create SearchHistoryResource
- [ ] Create SearchViewResource
- [ ] Add proper data transformation
- [ ] Include related content details

### Routes
- [ ] Add /search-history routes
- [ ] Add /search-history/views route
- [ ] Add /search-history/stats route
- [ ] Add admin analytics routes
- [ ] Apply appropriate middleware
- [ ] Configure rate limiting

### Testing
- [ ] Write unit tests for ViewTrackingService
- [ ] Write feature tests for search tracking
- [ ] Write integration tests for full flow
- [ ] Write database tests for migration
- [ ] Write performance tests
- [ ] Test on MySQL
- [ ] Test on SQLite
- [ ] Test guest tracking
- [ ] Test authentication
- [ ] Test privacy (user isolation)

### Documentation
- [ ] Update API documentation
- [ ] Add frontend integration examples
- [ ] Document query parameters
- [ ] Document response structures
- [ ] Add Postman/Insomnia collection
- [ ] Update README

### Deployment
- [ ] Run migration on staging
- [ ] Verify indexes created
- [ ] Test endpoints on staging
- [ ] Monitor performance
- [ ] Run migration on production
- [ ] Monitor error logs
- [ ] Verify data collection

---

## Success Metrics

### Technical Metrics
- Migration completes without errors on both MySQL and SQLite
- View tracking performance impact < 5ms per request
- Search history queries return in < 500ms
- Database indexes used correctly (verify with EXPLAIN)
- No memory leaks or performance degradation

### Functional Metrics
- 90%+ of search-initiated views captured with search_query
- Search history data populates correctly
- User privacy maintained (no cross-user data leaks)
- Admin analytics provide actionable insights

### User Metrics
- Increased user engagement (re-visiting search history)
- Reduced repeated searches for same content
- Higher conversion from search to view
- Positive user feedback on search history feature

---

## Conclusion

The Search History System extends the existing view tracking infrastructure to capture search context. By recording which searches lead to actual content views, we enable:
- Better understanding of user behavior
- Improved search result ranking
- Identification of content gaps
- Personalized user experiences

The implementation is designed to be:
- **Minimal**: Few changes to existing code
- **Performant**: Negligible impact on view tracking
- **Compatible**: Works on MySQL and SQLite
- **Extensible**: Foundation for advanced search features
- **Privacy-respecting**: User data isolation

The system integrates seamlessly with existing view tracking middleware and requires minimal frontend changes - just adding a `search_query` parameter when navigating from search results.
