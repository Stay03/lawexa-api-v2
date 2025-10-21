# Search History API - User Endpoints

## Overview
The Search History API allows users to track and retrieve their search activity and the content they've viewed from search results. This feature helps users rediscover content they found through searches and understand their research patterns.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
Search history endpoints work for both authenticated users and guests. However, authenticated users get persistent history across sessions, while guest users only have session-based history.

### Authentication Headers (Optional but Recommended)
```http
Authorization: Bearer {access_token}
Accept: application/json
```

## How Search Tracking Works

### Automatic Tracking
When you search for content and click on a result to view details, the system automatically tracks:
- The search query you used
- Which content you viewed from that search
- When you viewed it
- Your device and location information

### Search Query Parameter
To enable search tracking, frontend applications should include the `search_query` parameter when navigating from search results to content details:

```
GET /api/cases/{slug}?search_query=your+search+terms
GET /api/statutes/{id}?search_query=your+search+terms
GET /api/notes/{id}?search_query=your+search+terms
```

**Important:** The search query must be URL-encoded by the frontend.

## Endpoints

### Get Search History (Aggregated)

Retrieve a summary of your search queries with view counts and statistics.

**Endpoint:** `GET /search-history`

**Access:** Public (guest users get session-based history, authenticated users get persistent history)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 15 | Items per page (max 50) |
| `date_from` | date | No | null | Start date filter (YYYY-MM-DD) |
| `date_to` | date | No | null | End date filter (YYYY-MM-DD) |
| `content_type` | string | No | null | Filter by content type (case, statute, note) |
| `sort_by` | string | No | last_searched | Sort field (last_searched, first_searched, views_count, query) |
| `sort_order` | string | No | desc | Sort order (asc, desc) |
| `search` | string | No | null | Filter queries containing this text |

**Example Requests:**

```bash
# Basic search history
curl -X GET "https://rest.lawexa.com/api/search-history" \
  -H "Accept: application/json"

# With pagination and filtering
curl -X GET "https://rest.lawexa.com/api/search-history?per_page=10&sort_by=views_count&sort_order=desc" \
  -H "Accept: application/json"

# Filter by date range and content type
curl -X GET "https://rest.lawexa.com/api/search-history?date_from=2025-10-01&date_to=2025-10-31&content_type=case" \
  -H "Accept: application/json"

# Search within your search history
curl -X GET "https://rest.lawexa.com/api/search-history?search=contract" \
  -H "Accept: application/json"

# Authenticated user request
curl -X GET "https://rest.lawexa.com/api/search-history" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Search history retrieved successfully",
  "data": {
    "search_history": [
      {
        "search_query": "contract law",
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
            "title": "Smith v Jones [2023] UKSC 42",
            "slug": "smith-v-jones-2023-uksc-42",
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
}
```

### Get Individual Search Views

Retrieve detailed information about individual views initiated from searches.

**Endpoint:** `GET /search-history/views`

**Access:** Same as aggregated endpoint

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max 50) |
| `search_query` | string | No | null | Filter by specific search query |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |
| `content_type` | string | No | null | Filter by content type |
| `sort_by` | string | No | viewed_at | Sort field (viewed_at, query) |
| `sort_order` | string | No | desc | Sort order (asc, desc) |

**Example Requests:**

```bash
# Get all search views
curl -X GET "https://rest.lawexa.com/api/search-history/views" \
  -H "Accept: application/json"

# Filter by specific search query
curl -X GET "https://rest.lawexa.com/api/search-history/views?search_query=contract+law" \
  -H "Accept: application/json"

# Filter by content type and date
curl -X GET "https://rest.lawexa.com/api/search-history/views?content_type=case&date_from=2025-10-01" \
  -H "Accept: application/json"

# Sort by viewed_at (oldest first)
curl -X GET "https://rest.lawexa.com/api/search-history/views?sort_by=viewed_at&sort_order=asc" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Search views retrieved successfully",
  "data": {
    "search_views": [
      {
        "id": 45678,
        "search_query": "contract law",
        "viewed_at": "2025-10-21T14:30:00Z",
        "content": {
          "type": "case",
          "id": 123,
          "title": "Smith v Jones [2023] UKSC 42",
          "slug": "smith-v-jones-2023-uksc-42",
          "url": "/cases/smith-v-jones-2023-uksc-42"
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
}
```

### Get Search Statistics

Retrieve overall statistics about your search activity.

**Endpoint:** `GET /search-history/stats`

**Access:** Same as other endpoints

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `date_from` | date | No | null | Start date for statistics |
| `date_to` | date | No | null | End date for statistics |

**Example Requests:**

```bash
# Get overall statistics
curl -X GET "https://rest.lawexa.com/api/search-history/stats" \
  -H "Accept: application/json"

# Get statistics for specific date range
curl -X GET "https://rest.lawexa.com/api/search-history/stats?date_from=2025-10-01&date_to=2025-10-31" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Search statistics retrieved successfully",
  "data": {
    "total_searches": 42,
    "total_views_from_search": 187,
    "unique_queries": 42,
    "views_per_search_avg": 4.45,
    "most_searched_query": "contract law",
    "most_viewed_from_search": {
      "type": "case",
      "id": 123,
      "title": "Smith v Jones [2023] UKSC 42",
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
}
```

## Frontend Integration Guide

### Basic Implementation

**1. When user performs search:**
```javascript
// User searches for "contract law"
const searchResults = await fetch('/api/cases?search=contract+law');
```

**2. When user clicks on a search result:**
```javascript
// Navigate to content detail with search context
const handleSearchResultClick = (item, searchQuery) => {
    // Include the search_query parameter
    window.location.href = `${item.url}?search_query=${encodeURIComponent(searchQuery)}`;

    // Or use router/SPA navigation
    router.push({
        path: item.url,
        query: { search_query: searchQuery }
    });
};
```

**3. Display user's search history:**
```javascript
// Get user's search history
const getSearchHistory = async () => {
    const response = await fetch('/api/search-history?per_page=20&sort_by=last_searched');
    const data = await response.json();
    return data.data.search_history;
};

// Format for display
const formatSearchHistory = (history) => {
    return history.map(item => ({
        query: item.search_query,
        lastUsed: new Date(item.last_searched_at).toLocaleDateString(),
        viewCount: item.views_count,
        sampleContent: item.sample_views[0]?.title
    }));
};
```

### Advanced Features

**Search within search history:**
```javascript
const searchWithinHistory = async (searchTerm) => {
    const response = await fetch(`/api/search-history?search=${encodeURIComponent(searchTerm)}`);
    return await response.json();
};
```

**Filter by content type:**
```javascript
const getCaseSearchHistory = async () => {
    const response = await fetch('/api/search-history?content_type=case');
    return await response.json();
};
```

**Get specific search query views:**
```javascript
const getViewsForQuery = async (searchQuery) => {
    const response = await fetch(`/api/search-history/views?search_query=${encodeURIComponent(searchQuery)}`);
    return await response.json();
};
```

## Privacy and Data Management

### Guest Users
- Search history is tracked only for the current session
- History is lost when the session ends or browser closes
- No persistent storage across different sessions

### Authenticated Users
- Search history is permanently stored and linked to your account
- You can access your search history across different devices
- History respects your account privacy settings

### Data Privacy
- Users can only see their own search history
- Search queries are stored exactly as entered
- No search data is shared with other users
- Admin users may access aggregated, anonymized search analytics

## Use Cases

### 1. Research Continuity
- "I found a great case about contract formation last week, what was that search term?"
- Find content you've previously discovered but forgotten the exact details

### 2. Study Pattern Analysis
- Understand which legal topics you research most frequently
- Identify gaps in your research areas
- Track your research efficiency over time

### 3. Content Rediscovery
- Quickly find that perfect case or statute you found through searching
- Revisit content that was helpful for previous research
- Build on previous research findings

### 4. Search Optimization
- Learn which search terms give you the best results
- Refine your search strategies based on past success
- Discover related content through your search patterns

## Response Codes

| Code | Description |
|------|-------------|
| 200 | Success - Data retrieved |
| 401 | Unauthorized - Invalid or missing token (rare for these endpoints) |
| 422 | Validation Error - Invalid parameters |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error - Temporary server issue |

## Best Practices

1. **Always URL-encode search queries** when including them in URLs
2. **Include search_query parameter** consistently for all navigation from search results
3. **Handle empty results gracefully** - users may not have any search history yet
4. **Cache search history data** appropriately to improve performance
5. **Implement progressive enhancement** - the API works without authentication, but provides better experience with it
6. **Use pagination** for large search histories to avoid slow responses
7. **Implement search within history** to help users find specific past searches

## Error Handling

### Common Error Responses

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Unauthenticated.",
  "data": null
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["The per page must be between 1 and 50."]
  }
}
```

**429 Rate Limit:**
```json
{
  "status": "error",
  "message": "Too many attempts. Please try again later.",
  "retry_after": 60
}
```

### Client-Side Error Handling Example
```javascript
const handleApiError = (response) => {
    switch (response.status) {
        case 401:
            // User needs to authenticate
            redirectToLogin();
            break;
        case 422:
            // Validation error - show user-friendly message
            const errors = response.data.errors;
            showValidationErrors(errors);
            break;
        case 429:
            // Rate limited - retry after specified time
            const retryAfter = response.headers['retry-after'];
            setTimeout(() => retryRequest(), retryAfter * 1000);
            break;
        default:
            // Generic error
            showGenericError('Something went wrong. Please try again.');
    }
};
```