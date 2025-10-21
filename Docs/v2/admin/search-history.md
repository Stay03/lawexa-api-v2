# Search Analytics API - Admin Endpoints

## Overview
The Search Analytics API provides administrators with powerful insights into user search behavior, content performance, and platform usage patterns. This data helps identify content gaps, improve user experience, and make data-driven decisions about content strategy.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
Admin search analytics endpoints require authentication with appropriate role permissions.

### Required Permissions
- **Admin**: Full access to all search analytics
- **Researcher**: Access to analytics, no user-level data
- **Superadmin**: Full access to all features

### Authentication Headers (Required)
```http
Authorization: Bearer {admin_access_token}
Accept: application/json
```

### Get Admin Token
```bash
# Using Laravel tinker
php artisan tinker
> $admin = User::where('role', 'admin')->first();
> $token = $admin->createToken('admin-analytics')->plainTextToken;
> echo $token;
```

## User Search History Management

### View Any User's Search History

Access search history data for specific users (admin only).

**Endpoint:** `GET /admin/users/{user_id}/search-history`

**Access:** Admin, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max 100) |
| `date_from` | date | No | null | Start date filter |
| `date_to` | date | No | null | End date filter |
| `content_type` | string | No | null | Filter by content type |
| `search` | string | No | null | Filter queries containing text |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/users/123/search-history?per_page=20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User search history retrieved successfully",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    },
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
        "last_searched_at": "2025-10-21T14:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 42,
      "per_page": 20
    }
  }
}
```

## Global Search Analytics

### Get Popular Searches

Retrieve the most popular search queries across all users.

**Endpoint:** `GET /admin/analytics/search/popular`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 20 | Number of results (max 100) |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |
| `content_type` | string | No | null | Filter by content type |
| `min_views` | integer | No | 1 | Minimum view count |

**Example Requests:**
```bash
# Get top 20 popular searches
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/popular" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Get top 10 popular case searches this month
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/popular?limit=10&content_type=case&date_from=2025-10-01" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Popular searches retrieved successfully",
  "data": [
    {
      "search_query": "contract law",
      "total_views": 1543,
      "unique_users": 87,
      "unique_content": 234,
      "avg_views_per_user": 17.7,
      "conversion_rate": 0.85,
      "first_searched": "2025-01-01T00:00:00Z",
      "last_searched": "2025-10-21T14:00:00Z",
      "content_type_breakdown": {
        "cases": 1200,
        "statutes": 243,
        "notes": 100
      },
      "trend": "increasing", // increasing, decreasing, stable
      "trend_percentage": 15.3
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

### Get Ineffective Searches

Identify searches that lead to few or no views (potential content gaps).

**Endpoint:** `GET /admin/analytics/search/ineffective`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 20 | Number of results |
| `min_searches` | integer | No | 5 | Minimum search count |
| `max_conversion` | float | No | 0.1 | Maximum view conversion rate |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |

**Example Request:**
```bash
# Find searches with low conversion rates
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/ineffective?min_searches=10&max_conversion=0.05" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Ineffective searches retrieved successfully",
  "data": [
    {
      "search_query": "obscure legal term",
      "total_searches": 45,
      "total_views": 2,
      "conversion_rate": 0.04,
      "unique_users": 12,
      "suggests_content_gap": true,
      "last_searched": "2025-10-20T10:00:00Z",
      "recommendations": [
        "Create content explaining this legal term",
        "Add synonyms and related terms to improve search",
        "Check if this is a commonly misunderstood concept"
      ]
    }
  ],
  "insights": {
    "total_ineffective_queries": 156,
    "potential_content_gaps": 89,
    "avg_conversion_rate": 0.06,
    "top_gap_categories": [
      "International Law",
      "Historical Cases",
      "Specialized Regulations"
    ]
  }
}
```

### Get Search Trends

Analyze search trends over time.

**Endpoint:** `GET /admin/analytics/search/trends`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | No | month | Time period (day, week, month, year) |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |
| `category` | string | No | null | Category filter |

**Example Request:**
```bash
# Get monthly search trends for this year
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/trends?period=month&date_from=2025-01-01" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Search trends retrieved successfully",
  "data": {
    "period": "month",
    "trends": [
      {
        "period": "2025-10",
        "total_searches": 2847,
        "unique_users": 892,
        "total_views": 12456,
        "top_queries": [
          "contract law",
          "property law",
          "criminal procedure"
        ],
        "content_type_distribution": {
          "cases": 65,
          "statutes": 25,
          "notes": 10
        }
      }
    ],
    "summary": {
      "total_period": 10,
      "avg_searches_per_period": 2341,
      "growth_rate": 12.5,
      "peak_month": "2025-03"
    }
  }
}
```

## Content Performance Analytics

### Get Most Searched Content

Find content that receives the most search traffic.

**Endpoint:** `GET /admin/analytics/content/most-searched`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 20 | Number of results |
| `content_type` | string | No | null | Filter by content type |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/content/most-searched?limit=10&content_type=case" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Most searched content retrieved successfully",
  "data": [
    {
      "content": {
        "type": "case",
        "id": 123,
        "title": "Smith v Jones [2023] UKSC 42",
        "slug": "smith-v-jones-2023-uksc-42"
      },
      "search_analytics": {
        "total_search_views": 245,
        "unique_search_queries": 89,
        "top_search_queries": [
          "contract breach",
          "damages calculation",
          "contract law principles"
        ],
        "conversion_from_search": 0.73,
        "avg_time_on_page": 456 // seconds
      },
      "overall_analytics": {
        "total_views": 1456,
        "total_bookmarks": 89,
        "total_downloads": 23
      }
    }
  ]
}
```

### Get Search to Content Mapping

Understand which searches lead to which content.

**Endpoint:** `GET /admin/analytics/search/content-mapping`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search_query` | string | No | null | Specific search query |
| `content_id` | integer | No | null | Specific content ID |
| `limit` | integer | No | 50 | Number of results |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/content-mapping?search_query=contract+law&limit=20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Search content mapping retrieved successfully",
  "data": {
    "search_query": "contract law",
    "total_views": 1543,
    "content_mapping": [
      {
        "content": {
          "type": "case",
          "id": 123,
          "title": "Smith v Jones [2023] UKSC 42"
        },
        "views_from_search": 245,
        "click_through_rate": 0.16,
        "avg_position": 1.2,
        "bounce_rate": 0.23
      }
    ],
    "insights": {
      "total_unique_content": 89,
      "diversity_score": 0.73, // How varied the content is
      "satisfaction_rate": 0.68 // Based on engagement metrics
    }
  }
}
```

## User Behavior Analytics

### Get User Search Patterns

Analyze how different users search and interact with content.

**Endpoint:** `GET /admin/analytics/users/search-patterns`

**Access:** Admin, Superadmin (Researcher gets aggregated data only)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `user_type` | string | No | all | Filter by user type (guest, authenticated, premium) |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |
| `limit` | integer | No | 20 | Number of results |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/users/search-patterns?user_type=authenticated" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User search patterns retrieved successfully",
  "data": {
    "user_segments": [
      {
        "segment": "authenticated_users",
        "total_users": 1250,
        "search_behavior": {
          "avg_searches_per_user": 12.4,
          "avg_views_per_search": 3.2,
          "search_frequency": "weekly",
          "peak_search_times": ["Monday", "Wednesday"],
          "peak_hours": ["10:00", "14:00"]
        },
        "top_search_categories": [
          "Contract Law",
          "Property Law",
          "Criminal Procedure"
        ]
      }
    ],
    "insights": {
      "most_active_users": [
        {
          "user_id": 456,
          "name": "John Doe",
          "total_searches": 156,
          "total_views": 892
        }
      ],
      "user_retention": {
        "new_users_this_month": 89,
        "returning_users": 456,
        "retention_rate": 0.84
      }
    }
  }
}
```

### Get Geographic Search Analytics

Understand search patterns by geographic location.

**Endpoint:** `GET /admin/analytics/geographic/search-distribution`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `granularity` | string | No | country | Geographic level (country, city, region) |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/geographic/search-distribution?granularity=country" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Geographic search distribution retrieved successfully",
  "data": [
    {
      "country": "Nigeria",
      "country_code": "NG",
      "total_searches": 4567,
      "unique_users": 892,
      "top_search_queries": [
        "contract law nigeria",
        "landlord tenant act",
        "company registration"
      ],
      "content_preferences": {
        "cases": 0.65,
        "statutes": 0.25,
        "notes": 0.10
      }
    }
  ]
}
```

## System Performance Analytics

### Get Search System Health

Monitor the performance and health of the search system.

**Endpoint:** `GET /admin/analytics/system/health`

**Access:** Admin, Superadmin

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/system/health" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "System health retrieved successfully",
  "data": {
    "search_performance": {
      "avg_response_time": 145, // milliseconds
      "search_success_rate": 0.98,
      "index_size": "2.3GB",
      "last_index_update": "2025-10-21T14:00:00Z"
    },
    "tracking_performance": {
      "views_tracked_today": 1256,
      "tracking_success_rate": 0.99,
      "failed_tracking_count": 12
    },
    "storage_metrics": {
      "total_search_records": 156789,
      "storage_used": "456MB",
      "growth_rate": "12% per month"
    }
  }
}
```

## Data Exports

### Export Search Analytics Data

Download search analytics data in various formats.

**Endpoint:** `GET /admin/analytics/export/search-data`

**Access:** Admin, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `format` | string | No | csv | Export format (csv, xlsx, json) |
| `type` | string | No | popular | Data type (popular, ineffective, trends) |
| `date_from` | date | No | null | Start date |
| `date_to` | date | No | null | End date |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/analytics/export/search-data?format=csv&type=popular&date_from=2025-10-01" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/csv" \
  -o "popular_searches_october.csv"
```

## Response Codes

| Code | Description |
|------|-------------|
| 200 | Success - Data retrieved |
| 401 | Unauthorized - Invalid or missing admin token |
| 403 | Forbidden - Insufficient permissions |
| 422 | Validation Error - Invalid parameters |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error - Temporary server issue |

## Use Cases for Admin Analytics

### 1. Content Strategy
- **Identify Content Gaps**: Use ineffective searches to find missing content
- **Prioritize Content Creation**: Focus on high-demand topics
- **Optimize Existing Content**: Improve content that performs poorly in search

### 2. User Experience Improvement
- **Understand User Intent**: Analyze what users are looking for
- **Improve Search Results**: Identify and fix search quality issues
- **Personalize Recommendations**: Use search history for better recommendations

### 3. Business Intelligence
- **Track Platform Growth**: Monitor search volume trends
- **Identify Power Users**: Find your most engaged researchers
- **Geographic Expansion**: Understand regional content needs

### 4. Performance Monitoring
- **System Health**: Monitor search system performance
- **Data Quality**: Ensure search tracking is working properly
- **Resource Planning**: Plan infrastructure based on growth patterns

## Best Practices

1. **Data Privacy**: Always anonymize user data when sharing reports
2. **Regular Monitoring**: Set up alerts for unusual search patterns
3. **Actionable Insights**: Focus on data that leads to actionable improvements
4. **Trend Analysis**: Look at data over time, not just snapshots
5. **Cross-Reference**: Combine search data with other analytics (views, bookmarks, etc.)
6. **Automated Reports**: Set up regular exports for stakeholders

## Example Workflows

### Content Gap Analysis Workflow
```bash
# 1. Get ineffective searches
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/ineffective?min_searches=10" \
  -H "Authorization: Bearer $TOKEN" > ineffective.json

# 2. Get content requests for the same period
curl -X GET "https://rest.lawexa.com/api/admin/content-requests?status=pending" \
  -H "Authorization: Bearer $TOKEN" > content_requests.json

# 3. Cross-reference and prioritize content creation
# (This would be done in your analytics tool or spreadsheet)
```

### Monthly Performance Report
```bash
# 1. Get monthly trends
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/trends?period=month" \
  -H "Authorization: Bearer $TOKEN" > trends.json

# 2. Get most popular searches
curl -X GET "https://rest.lawexa.com/api/admin/analytics/search/popular?limit=50" \
  -H "Authorization: Bearer $TOKEN" > popular.json

# 3. Export data for report generation
curl -X GET "https://rest.lawexa.com/api/admin/analytics/export/search-data?format=csv&type=popular" \
  -H "Authorization: Bearer $TOKEN" > monthly_report.csv
```

## Rate Limiting

Admin analytics endpoints have generous but not unlimited rate limits:
- **Standard requests**: 1000 requests per hour
- **Export requests**: 10 requests per hour
- **Heavy analytics**: 100 requests per hour

Rate limit information is included in response headers:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1635148800
```