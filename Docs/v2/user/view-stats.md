# User View Statistics API

These endpoints allow authenticated users to retrieve view statistics and popular content.

## Base URL
```
{base_url}/api/views/stats
```

## Authentication
All endpoints require bearer token authentication.

## Endpoints

### 1. Get My Activity
Retrieve the authenticated user's viewing activity and statistics.

**Endpoint:** `GET /my-activity`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)
- `limit` (optional, integer): Number of recent views to return (1-100, defaults to 50)

**Request Example:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "{base_url}/api/views/stats/my-activity?start_date=2025-08-01&limit=10"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "User activity retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "stats": {
            "total_views": 5,
            "unique_content": 3,
            "most_viewed_model": {
                "viewable_type": "App\\Models\\CourtCase",
                "count": "3"
            }
        },
        "recent_views": [
            {
                "id": 1,
                "model_type": "CourtCase",
                "model_id": 5101,
                "model_title": "Samuels v Stubbs, [1972] 4 SASR 200",
                "viewed_at": "2025-08-27T01:16:52.000000Z"
            }
        ]
    }
}
```

**Response Fields:**
- `period`: Date range for the statistics
- `stats`: Summary statistics for the user
  - `total_views`: Total number of views in the period
  - `unique_content`: Number of unique pieces of content viewed
  - `most_viewed_model`: The type of content most frequently viewed
- `recent_views`: Array of recent viewing activity
  - `model_type`: Type of content (CourtCase, Statute, etc.)
  - `model_id`: ID of the viewed content
  - `model_title`: Title/name of the viewed content
  - `viewed_at`: Timestamp of when it was viewed

---

### 2. Get Popular Content
Retrieve popular content based on view statistics.

**Endpoint:** `GET /popular`

**Query Parameters:**
- `model_type` (optional, string): Filter by content type (e.g., "App\\Models\\CourtCase")
- `limit` (optional, integer): Number of results to return (1-50, defaults to 20)
- `period` (optional, string): Time period - "today", "week", "month", or "all" (defaults to "week")

**Request Example:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "{base_url}/api/views/stats/popular?period=week&limit=5"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Popular content retrieved successfully",
    "data": {
        "period": "week",
        "model_type": "all",
        "popular_content": [
            {
                "model_type": "CourtCase",
                "model_id": 5101,
                "model_title": "Samuels v Stubbs, [1972] 4 SASR 200",
                "total_views": 2,
                "unique_users": 2,
                "last_viewed": "2025-08-26 12:47:06"
            },
            {
                "model_type": "Statute",
                "model_id": 18,
                "model_title": "The statute",
                "total_views": 2,
                "unique_users": 1,
                "last_viewed": "2025-08-27 01:16:52"
            }
        ]
    }
}
```

**Response Fields:**
- `period`: The time period used for the query
- `model_type`: The content type filter applied (or "all")
- `popular_content`: Array of popular content items
  - `model_type`: Type of content
  - `model_id`: ID of the content
  - `model_title`: Title/name of the content
  - `total_views`: Total number of views
  - `unique_users`: Number of unique users who viewed it
  - `last_viewed`: Timestamp of most recent view

## Error Responses

**401 Unauthorized:**
```json
{
    "status": "error",
    "message": "Authentication required",
    "data": null
}
```

**422 Validation Error:**
```json
{
    "status": "error",
    "message": "The given data was invalid.",
    "errors": {
        "end_date": ["The end date must be a date after or equal to start date."]
    }
}
```

## Notes
- All dates are in `YYYY-MM-DD` format
- Timestamps are in ISO 8601 format with UTC timezone
- Both endpoints require user authentication
- The `my-activity` endpoint only returns data for the authenticated user
- The `popular` endpoint shows system-wide popular content