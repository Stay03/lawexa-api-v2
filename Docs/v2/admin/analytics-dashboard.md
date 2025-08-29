# Admin Analytics Dashboard API

These endpoints provide a comprehensive analytics dashboard and view management interface for administrators, researchers, and superadmins.

## Base URL
```
{base_url}/api/admin/views
```

## Authentication
All endpoints require bearer token authentication with admin, researcher, or superadmin role.

## Endpoints

### 1. Analytics Dashboard
Get comprehensive dashboard analytics with period-over-period comparisons, key metrics, and detailed breakdowns.

**Endpoint:** `GET /stats/dashboard`

**Query Parameters:**
- `time_filter` (optional, string): Time filter type - "today", "this_week", "this_month", "last_24h", "last_7d", "last_30d", "custom" (defaults to "last_7d")
- `start_date` (optional, date): Start date for custom time filter (required when time_filter is "custom")
- `end_date` (optional, date): End date for custom time filter (required when time_filter is "custom", must be after start_date)
- `model_type` (optional, string): Filter by content type (e.g., "App\\Models\\CourtCase")
- `country` (optional, string): Filter by country name (partial matching supported)

**Request Examples:**
```bash
# Default (Last 7 days)
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/dashboard"

# Today vs Yesterday comparison
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/dashboard?time_filter=today"

# Custom date range
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/dashboard?time_filter=custom&start_date=2025-08-01&end_date=2025-08-27"

# Filtered by country
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views/stats/dashboard?time_filter=last_30d&country=Nigeria"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Dashboard data retrieved successfully",
    "data": {
        "time_filter": {
            "type": "last_7d",
            "current_period": {
                "label": "Last 7d",
                "start": "2025-08-20",
                "end": "2025-08-27"
            },
            "comparison_period": {
                "label": "Previous 7d",
                "start": "2025-08-13",
                "end": "2025-08-20"
            }
        },
        "metrics": {
            "total_views": {
                "current": 5,
                "previous": 0,
                "change_percent": 100
            },
            "unique_users": {
                "current": 4,
                "previous": 0,
                "change_percent": 100
            },
            "guest_views": {
                "current": 1,
                "previous": 0,
                "change_percent": 100
            },
            "registered_views": {
                "current": 4,
                "previous": 0,
                "change_percent": 100
            }
        },
        "analytics": {
            "top_countries": [
                {
                    "country": "United States",
                    "views": 2
                },
                {
                    "country": "Nigeria",
                    "views": 1
                }
            ],
            "top_devices": [
                {
                    "device_type": "mobile",
                    "views": 1
                },
                {
                    "device_type": "desktop",
                    "views": 1
                }
            ],
            "top_content": [
                {
                    "type": "CourtCase",
                    "id": 5101,
                    "title": "Samuels v Stubbs, [1972] 4 SASR 200",
                    "views": 2
                },
                {
                    "type": "Statute",
                    "id": 18,
                    "title": "The statute",
                    "views": 2
                }
            ],
            "hourly_distribution": [
                {
                    "hour": 0,
                    "views": 1
                },
                {
                    "hour": 1,
                    "views": 1
                },
                {
                    "hour": 12,
                    "views": 3
                }
            ]
        }
    }
}
```

---

### 2. View Records Management
List and filter individual view records with detailed information and pagination.

**Endpoint:** `GET /`

**Query Parameters:**
- `user_id` (optional, integer): Filter by specific user ID (must exist in users table)
- `model_type` (optional, string): Filter by content type (e.g., "App\\Models\\CourtCase")
- `time_filter` (optional, string): Time filter type - "today", "this_week", "this_month", "last_24h", "last_7d", "last_30d", "custom" (takes precedence over start_date/end_date)
- `start_date` (optional, date): Filter views from this date onwards (ignored if time_filter is provided, required for time_filter=custom)
- `end_date` (optional, date): Filter views up to this date (ignored if time_filter is provided, required for time_filter=custom)
- `country` (optional, string): Filter by country name (partial matching supported)
- `ip_address` (optional, IP): Filter by specific IP address
- `search` (optional, string): Search across user agent, country, city, device type, platform, and browser (max 255 chars)
- `per_page` (optional, integer): Number of records per page (1-100, defaults to 15)

**Request Examples:**
```bash
# Default listing
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views"

# Time filter (matches dashboard periods)
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?time_filter=last_7d&per_page=20"

# Today's views only
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?time_filter=today"

# Custom date range
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?time_filter=custom&start_date=2025-08-01&end_date=2025-08-27"

# Filtered by country with time period
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?time_filter=last_30d&country=Nigeria&per_page=5"

# User-specific views (legacy date filtering)
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?user_id=123&start_date=2025-08-01"

# Search across device information
curl -H "Authorization: Bearer {admin_token}" \
     -H "Accept: application/json" \
     "{base_url}/api/admin/views?search=mobile&per_page=10"
```

**Response Example:**
```json
{
    "status": "success",
    "message": "Views retrieved successfully",
    "data": {
        "views": [
            {
                "id": 5,
                "viewable_type": "App\\Models\\CourtCase",
                "viewable_id": 1,
                "user_id": 90,
                "session_id": null,
                "ip_address": "127.0.0.1",
                "user_agent_hash": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08",
                "viewed_at": "2025-08-27T12:35:54.000000Z",
                "created_at": "2025-08-27T12:35:54.000000Z",
                "updated_at": "2025-08-27T12:35:54.000000Z",
                "user_agent": "Test Agent",
                "ip_country": "Nigeria",
                "ip_country_code": null,
                "ip_continent": null,
                "ip_continent_code": null,
                "ip_region": null,
                "ip_city": null,
                "ip_timezone": null,
                "device_type": "desktop",
                "device_platform": null,
                "device_browser": null,
                "user": {
                    "id": 90,
                    "name": "Test Admin User",
                    "email": "admin@test.com",
                    "role": "admin"
                },
                "viewable": {
                    "id": 1,
                    "title": "4 Eng Ltd v Harper & Anor, (2008) 3 WLR 892",
                    "slug": "4-eng-ltd-v-harper-anor-2008-3-wlr-892-1",
                    "court": "High Court",
                    "date": "2008-04-29T00:00:00.000000Z",
                    "country": "United Kingdom",
                    "citation": "(2008) 3 WLR 892"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 2,
            "total": 5,
            "from": 1,
            "to": 2
        }
    }
}
```

---

## Time Filter Types

The dashboard endpoint supports various time filter types with automatic period comparison:

### Calendar-Based Filters
- **`today`**: Current day vs previous day
  - Labels: "Today" vs "Yesterday"
- **`this_week`**: Current week (from Monday) vs previous week
  - Labels: "This Week" vs "Last Week"  
- **`this_month`**: Current month vs previous month
  - Labels: "This Month" vs "Last Month"

### Rolling Period Filters
- **`last_24h`**: Last 24 hours vs previous 24 hours
  - Labels: "Last 24h" vs "Previous 24h"
- **`last_7d`**: Last 7 days vs previous 7 days
  - Labels: "Last 7d" vs "Previous 7d"
- **`last_30d`**: Last 30 days vs previous 30 days
  - Labels: "Last 30d" vs "Previous 30d"

### Custom Range Filter
- **`custom`**: User-defined date range vs equivalent previous period
  - Labels: "Selected Period" vs "Previous Period"
  - Requires `start_date` and `end_date` parameters

---

## Dashboard Metrics

### Key Performance Indicators
- **Total Views**: Count of all view records in the period
- **Unique Users**: Count of distinct users who viewed content
- **Guest Views**: Views by users with `role = 'guest'`
- **Registered Views**: Views by users with roles other than guest

### Period-over-Period Comparisons
All metrics include:
- `current`: Value for the selected period
- `previous`: Value for the comparison period
- `change_percent`: Percentage change (positive = growth, negative = decline)

### Analytics Breakdowns
- **Top Countries**: Geographic distribution of views (top 10)
- **Top Devices**: Device type distribution (desktop, mobile, tablet)
- **Top Content**: Most viewed content with titles (top 10)
- **Hourly Distribution**: View patterns by hour of day (0-23)

---

## Data Relationships

### User Relationship
Each view record includes the associated user with:
- Basic profile information (id, name, email, role)
- User role differentiation (guest vs registered)

### Viewable Relationship  
Each view record includes the viewed content with:
- Content type and ID
- Title/name of the content
- Additional metadata based on content type (court cases, statutes, etc.)

---

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
        "time_filter": ["The selected time filter is invalid."],
        "user_id": ["The selected user id is invalid."],
        "per_page": ["The per page must be between 1 and 100."]
    }
}
```

---

## Synchronized Time Filtering

Both the dashboard and views list endpoints support identical `time_filter` parameters, enabling synchronized frontend filtering:

### **Frontend Integration Example:**
```javascript
// Single time filter controls both endpoints
const timeFilter = 'last_7d';

// Dashboard shows metrics for the period
const dashboardResponse = await fetch(`/api/admin/views/stats/dashboard?time_filter=${timeFilter}`);

// Views list shows individual records for the same period  
const viewsResponse = await fetch(`/api/admin/views?time_filter=${timeFilter}&per_page=20`);

// Both return data for identical time ranges
console.log('Dashboard period:', dashboardResponse.data.time_filter.current_period);
console.log('Views from same period:', viewsResponse.data.pagination.total);
```

### **Time Filter Priority:**
- `time_filter` parameter takes **precedence** over `start_date`/`end_date`
- When `time_filter=custom`, then `start_date` and `end_date` are **required**
- Legacy date filtering still works when `time_filter` is not provided

### **Period Consistency:**
Both endpoints use identical time calculation logic, ensuring:
- Dashboard metrics and view records always match the same time period
- No discrepancies between summary stats and detailed records
- Consistent user experience across different views

---

## Use Cases

### Executive Dashboard
```bash
# High-level metrics with growth trends
GET /stats/dashboard?time_filter=this_month
```

### Content Performance Analysis
```bash
# See which content types are performing best
GET /stats/dashboard?model_type=App\Models\CourtCase&time_filter=last_30d
```

### Geographic Analysis
```bash
# Focus on specific market performance
GET /stats/dashboard?country=Nigeria&time_filter=last_7d
```

### View Record Investigation
```bash
# Investigate specific user behavior
GET /?user_id=123&start_date=2025-08-01&per_page=50

# Analyze suspicious activity
GET /?ip_address=192.168.1.1&search=bot
```

### Synchronized Dashboard + Details View
```bash
# Frontend dashboard: Show metrics and detailed records for same period
GET /stats/dashboard?time_filter=last_7d
GET /?time_filter=last_7d&per_page=50

# Both return data for identical time range (last 7 days)
# Dashboard shows: 142 total views, 89 unique users
# Views list shows: 142 individual records with full details
```

---

## Notes
- All endpoints require admin, researcher, or superadmin role
- All dates are in `YYYY-MM-DD` format  
- Timestamps are in ISO 8601 format with UTC timezone
- The `session_id` field is deprecated and will always be null (legacy from pre-guest user system)
- Geographic data depends on IP geolocation accuracy
- Device detection is based on User-Agent parsing
- Guest users are identified by `role = 'guest'` in the users table
- Period comparisons automatically calculate equivalent previous periods
- Percentage changes show 100% when previous period has zero views
- Search functionality performs partial matching across multiple fields
- All results are sorted by latest activity first (most recent views first)
- Both endpoints support identical `time_filter` parameters for synchronized filtering
- Time filters use consistent logic ensuring dashboard metrics match view record counts