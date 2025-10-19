# Divisions API - Global Search Endpoint

## Overview
The Divisions API provides global search functionality across all statute divisions in the Lawexa system. Users can search, filter, and browse divisions from all statutes with comprehensive hierarchical data.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
**Authentication is REQUIRED** for this endpoint. Users must provide a valid bearer token.

### Authentication Headers
```http
Authorization: Bearer {access_token}
Accept: application/json
```

### Guest User Access
Guest users can access this endpoint using a guest session token:
```bash
curl -X POST "https://rest.lawexa.com/api/auth/guest-session" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

## Endpoint

### Get Global Divisions
Retrieve a paginated list of divisions from all statutes with optional filtering and search capabilities.

**Endpoint:** `GET /divisions`

**Authentication:** Required

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in division title, content, or division number |
| `division_type` | string | No | - | Filter by division type (chapter, part, section, etc.) |
| `sort_by` | string | No | division_title | Sort field: division_title, division_number, created_at, statute_title |
| `sort_order` | string | No | asc | Sort order: asc, desc |
| `per_page` | integer | No | 15 | Number of items per page (max 50) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?search=administration&division_type=part&sort_by=division_title&sort_order=desc&per_page=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Divisions retrieved successfully",
  "data": {
    "divisions": [
      {
        "id": 104,
        "title": "THE ADMINISTRATION OF CRIMINAL JUSTICE MONITORING COMMITTEE",
        "slug": "the-administration-of-criminal-justice-monitoring-committee",
        "division_number": "46",
        "division_type": "part",
        "division_subtitle": null,
        "range": null,
        "content": null,
        "statute": {
          "id": 20,
          "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
          "slug": "administration-of-criminal-justice-act-2015"
        },
        "path": [],
        "immediate_parent": null,
        "is_bookmarked": false,
        "bookmark_id": null,
        "bookmarks_count": 0,
        "content_type": "divisions",
        "trending_metrics": {
          "trending_score": 0,
          "total_views": 0,
          "unique_viewers": 0,
          "weighted_score": 0,
          "latest_view": null,
          "earliest_view": null
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 10,
      "to": 5,
      "total": 5
    },
    "links": {
      "first": "https://rest.lawexa.com/api/divisions?page=1",
      "last": "https://rest.lawexa.com/api/divisions?page=1",
      "prev": null,
      "next": null
    },
    "content_type": "divisions"
  }
}
```

## Division Data Structure

### Core Fields
- `id`: Unique identifier
- `title`: Division title
- `slug`: SEO-friendly URL slug
- `division_number`: Division number/identifier
- `division_type`: Type of division (chapter, part, section, order, etc.)
- `division_subtitle`: Optional subtitle
- `range`: Page or section range
- `content`: Division content (if available)

### Relationships
- `statute`: Parent statute information
  - `id`: Statute ID
  - `title`: Statute title
  - `slug`: Statute slug
- `path`: Array of parent divisions forming the hierarchical path
- `immediate_parent`: Direct parent division (if any)
  - `id`: Parent division ID
  - `type`: Always "division"
  - `title`: Parent division title
  - `number`: Parent division number
  - `structural_type`: Parent division type
  - `slug`: Parent division slug

### Computed Fields
- `is_bookmarked`: Boolean indicating if the current user has bookmarked this division
- `bookmark_id`: Bookmark ID if bookmarked by current user
- `bookmarks_count`: Total number of bookmarks across all users
- `content_type`: Always "divisions"
- `trending_metrics`: Trending and view statistics
  - `trending_score`: Calculated trending score
  - `total_views`: Total view count
  - `unique_viewers`: Number of unique viewers
  - `weighted_score`: Weighted engagement score
  - `latest_view`: Most recent view timestamp
  - `earliest_view`: Earliest view timestamp

## Search and Filtering Features

### Text Search
Use the `search` parameter to search across:
- Division titles
- Division content
- Division numbers

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?search=administration" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Type Filtering
Filter by specific division types:
- `chapter`: Main chapters
- `part`: Parts or sections
- `section`: Individual sections
- `order`: Orders or rules
- `schedule`: Schedules

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?division_type=part" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Sorting Options
- `division_title`: Sort alphabetically by title
- `division_number`: Sort by division number
- `created_at`: Sort by creation date
- `statute_title`: Sort by parent statute title

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?sort_by=division_title&sort_order=desc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Combined Filtering
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?search=administration&division_type=part&sort_by=division_number&per_page=5&page=2" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Pagination
- **per_page**: Number of results per page (default: 15, max: 50)
- **page**: Page number to retrieve (default: 1)

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?per_page=5&page=3" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

## Hierarchical Structure

Divisions support hierarchical relationships where divisions can have parent divisions and child divisions.

### Path Structure
The `path` array shows the complete hierarchical path from the root to the current division:
```json
"path": [
  {
    "id": 1,
    "type": "division",
    "title": "General Provisions",
    "number": "I",
    "structural_type": "chapter",
    "slug": "general-provisions"
  },
  {
    "id": 2,
    "type": "division",
    "title": "Federal Republic of Nigeria",
    "number": "I",
    "structural_type": "part",
    "slug": "federal-republic-of-nigeria"
  }
]
```

### Immediate Parent
The `immediate_parent` field shows only the direct parent:
```json
"immediate_parent": {
  "id": 2,
  "type": "division",
  "title": "Federal Republic of Nigeria",
  "number": "I",
  "structural_type": "part",
  "slug": "federal-republic-of-nigeria"
}
```

## Features for Authenticated Users

### Bookmarking Divisions
Division responses include bookmark status information:
- `is_bookmarked`: Shows if the current user has bookmarked this division
- `bookmark_id`: The bookmark ID if bookmarked
- `bookmarks_count`: Total number of bookmarks across all users

**Example Division Response with Bookmark Info:**
```json
{
  "id": 104,
  "title": "THE ADMINISTRATION OF CRIMINAL JUSTICE MONITORING COMMITTEE",
  // ... other fields
  "is_bookmarked": true,
  "bookmark_id": 123,
  "bookmarks_count": 5,
  // ... rest of division data
}
```

**Bookmark Management:**
For complete bookmark management API documentation, see the [Bookmarks API Documentation](./bookmarks.md).

### View Tracking and Analytics
Each division includes comprehensive view metrics:
- Trending scores based on recent activity
- Total view counts
- Unique viewer statistics
- Weighted engagement scores
- View timeline data

## Error Responses

### Authentication Error (401)
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": {
    "errors": {
      "per_page": ["The per_page must be between 1 and 50."],
      "sort_by": ["The selected sort by is invalid."]
    }
  }
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to retrieve divisions",
  "data": {
    "error": "Database connection error"
  }
}
```

## Real Examples

### Example 1: Search for Administration-related Divisions
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?search=administration" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Example 2: Get All Parts with Pagination
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?division_type=part&per_page=20&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Example 3: Sort by Division Number
```bash
curl -X GET "https://rest.lawexa.com/api/divisions?sort_by=division_number&sort_order=asc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

## Best Practices

### Efficient Querying
- Use pagination for large result sets
- Be specific with search terms for better results
- Combine search with type filters for precision
- Use appropriate page sizes (15-50) for optimal performance

### Search Optimization
- Use specific terms from division titles
- Try different division types for comprehensive results
- Utilize sorting to organize results effectively
- Combine multiple filters for targeted searches

### Performance Tips
- Request smaller page sizes for faster responses
- Cache frequently accessed divisions
- Use specific search terms to reduce result sets
- Implement client-side pagination for better UX

## Rate Limiting
API requests are subject to rate limiting:
- **Authenticated users**: Standard rate limits apply
- **Guest users**: Same limits as authenticated users during session
- **Requests per minute**: Configurable based on user tier