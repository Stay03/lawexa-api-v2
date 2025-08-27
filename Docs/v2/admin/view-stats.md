# Admin View Statistics API

These endpoints provide comprehensive view analytics and statistics for administrators, researchers, and superadmins.

## Base URL
```
{base_url}/api/admin/views/stats
```

## Authentication
All endpoints require bearer token authentication with admin, researcher, or superadmin role.

## Endpoints

### 1. Get Overview Statistics
Get comprehensive overview of view statistics including totals, breakdowns, and recent activity.

**Endpoint:** `GET /overview`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)
- `model_type` (optional, string): Filter by content type (e.g., "App\\Models\\CourtCase")

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/overview?start_date=2025-08-01"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Overview statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27",
            "days": 31
        },
        "totals": {
            "total_views": 4,
            "unique_users": 3,
            "unique_ips": 2,
            "guest_views": 1,
            "authenticated_views": 3
        },
        "daily_breakdown": [
            {
                "date": "2025-08-26",
                "total_views": 2,
                "unique_users": 2
            },
            {
                "date": "2025-08-27",
                "total_views": 2,
                "unique_users": 1
            }
        ],
        "top_models": [
            {
                "model_type": "CourtCase",
                "model_id": 5101,
                "model_title": "Samuels v Stubbs, [1972] 4 SASR 200",
                "total_views": 2,
                "unique_users": 2
            }
        ],
        "recent_activity": [
            {
                "id": 4,
                "model_type": "Statute",
                "model_id": 18,
                "user": {
                    "id": 1,
                    "name": "Stay Njokede",
                    "role": "admin"
                },
                "country": "United States",
                "device_type": "mobile",
                "viewed_at": "2025-08-27T01:16:52.000000Z"
            }
        ]
    }
}
```

---

### 2. Get Model Statistics
Get detailed statistics broken down by content type (models).

**Endpoint:** `GET /models`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/models"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Model statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "models": [
            {
                "model_type": "Statute",
                "full_model_type": "App\\Models\\Statute",
                "total_views": 2,
                "unique_users": 1,
                "unique_ips": 1,
                "first_viewed": "2025-08-27 00:46:54",
                "last_viewed": "2025-08-27 01:16:52"
            },
            {
                "model_type": "CourtCase",
                "full_model_type": "App\\Models\\CourtCase",
                "total_views": 2,
                "unique_users": 2,
                "unique_ips": 1,
                "first_viewed": "2025-08-26 12:47:05",
                "last_viewed": "2025-08-26 12:47:06"
            }
        ]
    }
}
```

---

### 3. Get User Statistics
Get detailed statistics about user viewing behavior.

**Endpoint:** `GET /users`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)
- `limit` (optional, integer): Number of top users to return (1-100, defaults to 50)

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/users?limit=5"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "User statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "top_users": [
            {
                "user": {
                    "id": 1,
                    "name": "Stay Njokede",
                    "email": "njokedestay@gmail.com",
                    "role": "admin",
                    "member_since": "2025-07-06T20:00:55.000000Z"
                },
                "total_views": 2,
                "unique_content_viewed": 1,
                "active_days": 1,
                "first_activity": "2025-08-27 00:46:54",
                "last_activity": "2025-08-27 01:16:52"
            }
        ],
        "role_breakdown": [
            {
                "role": "admin",
                "total_views": 3,
                "unique_users": 2
            },
            {
                "role": "guest",
                "total_views": 1,
                "unique_users": 1
            }
        ]
    }
}
```

---

### 4. Get Geographic Statistics
Get view statistics broken down by geographic location.

**Endpoint:** `GET /geography`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)
- `group_by` (optional, string): Geographic grouping - "country", "region", "city", "continent", or "timezone" (defaults to "country")

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/geography?group_by=country"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Geographic statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "group_by": "country",
        "geographic_data": [
            {
                "location": "United States",
                "location_code": "US",
                "total_views": 2,
                "unique_users": 1,
                "unique_ips": 1
            }
        ]
    }
}
```

---

### 5. Get Device Statistics
Get view statistics broken down by device information.

**Endpoint:** `GET /devices`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/devices"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Device statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "device_types": [
            {
                "device_type": "mobile",
                "total_views": 1,
                "unique_users": 1
            }
        ],
        "platforms": [
            {
                "device_platform": "iOS",
                "total_views": 1,
                "unique_users": 1
            }
        ],
        "browsers": [
            {
                "device_browser": "Safari",
                "total_views": 1,
                "unique_users": 1
            }
        ]
    }
}
```

---

### 6. Get Trend Statistics
Get view statistics over time with customizable intervals.

**Endpoint:** `GET /trends`

**Query Parameters:**
- `start_date` (optional, date): Start date for the period (defaults to 30 days ago)
- `end_date` (optional, date): End date for the period (defaults to today, must be after start_date)
- `interval` (optional, string): Time interval - "hour", "day", "week", or "month" (defaults to "day")
- `model_type` (optional, string): Filter by content type

**Request Example:**
```bash
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/trends?interval=day"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Trend statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-07-28",
            "end_date": "2025-08-27"
        },
        "interval": "day",
        "model_type": "all",
        "trends": [
            {
                "period": "2025-08-26",
                "total_views": 2,
                "unique_users": 2,
                "unique_ips": 1
            },
            {
                "period": "2025-08-27",
                "total_views": 2,
                "unique_users": 1,
                "unique_ips": 1
            }
        ]
    }
}
```

## Error Responses

**401 Unauthorized:**
```json
{
    "status": "error",
    "message": "Authentication required",
    "data": null
}
```

**403 Forbidden:**
```json
{
    "status": "error",
    "message": "Admin access required",
    "data": null
}
```

**422 Validation Error:**
```json
{
    "status": "error",
    "message": "The given data was invalid.",
    "errors": {
        "end_date": ["The end date must be a date after or equal to start date."],
        "group_by": ["The selected group by is invalid."]
    }
}
```

## Notes
- All endpoints require admin, researcher, or superadmin role
- All dates are in `YYYY-MM-DD` format
- Timestamps are in ISO 8601 format with UTC timezone
- Geographic data includes location codes when available (country and continent)
- Device detection is based on User-Agent parsing and may not always be accurate
- Trend data is formatted according to the selected interval
- All statistics exclude null/missing data from aggregations
- Results are limited to 50 items for geographic data and 20 items for browser data