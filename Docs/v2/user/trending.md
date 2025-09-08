# Trending Content API

The Trending API provides access to popular and trending content across all types of legal resources based on view tracking data. These endpoints analyze viewing patterns to identify content that is currently trending or has high engagement.

## Base URL
```
{base_url}/api/trending
```

## Authentication
All trending endpoints are **public** and do not require authentication.

## Supported Content Types

The trending API supports 7 different content types:
- **cases** - Court cases and legal decisions
- **statutes** - Legal statutes and acts
- **divisions** - Statute divisions and chapters
- **provisions** - Specific statute provisions and sections
- **notes** - Legal notes and commentary
- **folders** - User-created content folders
- **comments** - User comments and discussions

## Content Data Available

All trending endpoints return complete content data for each item, not just basic information. Here's what's included for each content type:

### Cases
**Full case content including:**
- `body` - Complete judgment text and legal reasoning
- `principles` - Key legal principles extracted from the case
- `report` - Case report information
- `course`, `topic`, `tag`, `level` - Academic categorization
- `court`, `date`, `citation`, `judges` - Court details
- `judicial_precedent` - Precedent information
- `creator` - User who added the case
- `files` - Attached documents

### Statutes  
**Complete statute information:**
- `title`, `description` - Full statute title and content
- `country`, `year` - Jurisdiction and year of enactment
- `creator` - User who added the statute
- `files` - Attached documents

### Notes
**Full note content:**
- `title`, `content` - Complete note title and text content
- `tags` - Associated tags
- `is_private` - Privacy setting
- `created_at`, `updated_at` - Timestamps
- `user` - Note author

### Divisions & Provisions
**Complete section content:**
- `title`, `content` - Full text of the division/provision
- `division_number`, `provision_number` - Section numbers
- Relationships to parent `statute` and `division`

### Folders & Comments
**Basic content as applicable:**
- Folders: name, description, privacy settings
- Comments: full comment text and relationships

## Endpoints

### 1. Get General Trending Content
Retrieve trending content across all content types, sorted by trending score.

**Endpoint:** `GET /trending`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `type` | string | No | `all` | Filter by content type (`cases`, `statutes`, `divisions`, `provisions`, `notes`, `folders`, `comments`, `all`) |
| `time_range` | string | No | `week` | Time period for trending analysis (`today`, `week`, `month`, `year`, `custom`) |
| `start_date` | date | No | - | Start date for custom range (required if `time_range=custom`) |
| `end_date` | date | No | - | End date for custom range (required if `time_range=custom`) |
| `country` | string | No | - | Filter by user's country profile |
| `university` | string | No | - | Filter by user's university profile |
| `level` | string | No | - | Filter by academic level (`undergraduate`, `graduate`, `postgraduate`, `phd`) |
| `per_page` | integer | No | `15` | Number of items per page (1-50) |
| `page` | integer | No | `1` | Page number for pagination |

**Example Request:**
```http
GET /api/trending?time_range=month&per_page=3
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Trending content retrieved successfully",
  "data": {
    "trending": [
      {
        "id": 18,
        "title": "The statute",
        "slug": "the-statute", 
        "country": "Nigeria",
        "year": null,
        "description": "Lorem ipsum sit dolor amet",
        "creator": {
          "id": 2,
          "name": "Stay Njokede"
        },
        "files": [],
        "content_type": "statutes",
        "trending_metrics": {
          "trending_score": 80.38,
          "total_views": 2,
          "unique_viewers": 2,
          "weighted_score": 2,
          "latest_view": "2025-08-26 17:36:09",
          "earliest_view": "2025-08-26 16:52:24"
        }
      },
      {
        "id": 11,
        "title": "Comments System Test Note",
        "slug": null,
        "content": "This is a test note for testing the comments functionality",
        "tags": null,
        "is_private": false,
        "created_at": "2025-08-19T12:09:39.000000Z",
        "updated_at": "2025-08-19T12:09:39.000000Z",
        "user": {
          "id": 82,
          "name": "Chidere"
        },
        "content_type": "notes",
        "trending_metrics": {
          "trending_score": 78.74,
          "total_views": 2,
          "unique_viewers": 2,
          "weighted_score": 2,
          "latest_view": "2025-08-27 01:27:12",
          "earliest_view": "2025-08-26 17:39:58"
        }
      },
      {
        "id": 148,
        "content_type": "provisions",
        "trending_metrics": {
          "trending_score": 68.77,
          "total_views": 2,
          "unique_viewers": 1,
          "weighted_score": 2,
          "latest_view": "2025-08-27 00:47:37",
          "earliest_view": "2025-08-26 21:05:33"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 3,
      "per_page": 3,
      "to": 3,
      "total": 7
    },
    "links": {
      "first": "http://localhost:8000/api/trending?page=1",
      "last": "http://localhost:8000/api/trending?page=3",
      "prev": null,
      "next": "http://localhost:8000/api/trending?page=2"
    }
  },
  "filters_applied": {
    "time_range": "month"
  },
  "stats": {
    "cases": 9,
    "statutes": 11,
    "divisions": 0,
    "provisions": 2,
    "notes": 5,
    "folders": 0,
    "comments": 0,
    "total": 27,
    "time_range": "month",
    "date_range": {
      "start": "2025-08-09",
      "end": "2025-09-08"
    }
  }
}
```

### 2. Get Trending Statistics
Get overview statistics for trending content across all types.

**Endpoint:** `GET /trending/stats`

**Query Parameters:**
Same filtering parameters as the general trending endpoint.

**Example Request:**
```http
GET /api/trending/stats
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Trending statistics retrieved successfully",
  "data": {
    "cases": 1,
    "statutes": 2,
    "divisions": 0,
    "provisions": 0,
    "notes": 2,
    "folders": 0,
    "comments": 0,
    "total": 5,
    "time_range": "week",
    "date_range": {
      "start": "2025-09-01",
      "end": "2025-09-08"
    }
  },
  "meta": {
    "filters_applied": []
  }
}
```

### 3. Get Content-Specific Trending

Get trending content for a specific content type.

**Available Endpoints:**
- `GET /trending/cases` - Trending court cases
- `GET /trending/statutes` - Trending statutes
- `GET /trending/divisions` - Trending statute divisions
- `GET /trending/provisions` - Trending statute provisions
- `GET /trending/notes` - Trending notes
- `GET /trending/folders` - Trending folders
- `GET /trending/comments` - Trending comments

**Query Parameters:**
All the same parameters as the general trending endpoint (except `type` which is automatically set).

**Example Request:**
```http
GET /api/trending/notes
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Trending notes retrieved successfully",
  "data": {
    "trending": [
      {
        "id": 14,
        "title": "xxxx",
        "slug": null,
        "content": "<p></p><p>...</p>",
        "tags": [],
        "is_private": false,
        "created_at": "2025-09-01T18:32:31.000000Z",
        "updated_at": "2025-09-01T18:51:51.000000Z",
        "user": {
          "id": 2,
          "name": "Stay Njokede"
        },
        "content_type": "notes",
        "trending_metrics": {
          "trending_score": 40.34,
          "total_views": 2,
          "unique_viewers": 1,
          "weighted_score": 2,
          "latest_view": "2025-09-01 18:51:52",
          "earliest_view": "2025-09-01 18:32:46"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 1,
      "total": 1
    },
    "links": {
      "first": "http://localhost:8000/api/trending/notes?page=1",
      "last": "http://localhost:8000/api/trending/notes?page=1",
      "prev": null,
      "next": null
    }
  },
  "content_type": "notes",
  "filters_applied": {
    "content_type": "notes"
  },
  "stats": {
    "cases": 1,
    "statutes": 2,
    "divisions": 0,
    "provisions": 0,
    "notes": 2,
    "folders": 0,
    "comments": 0,
    "total": 5,
    "time_range": "week",
    "date_range": {
      "start": "2025-09-01",
      "end": "2025-09-08"
    }
  }
}
```

### Example: Trending Cases with Full Content

**Request:**
```http
GET /api/trending/cases?time_range=month
```

**Response (showing complete case data):**
```json
{
  "status": "success",
  "message": "Trending cases retrieved successfully",
  "data": {
    "trending": [
      {
        "id": 5112,
        "title": "Saraki v Soleye, (1972) 2 UILR 271",
        "body": "It was held that where defamatory words are published more extensively than the occasion requires, or maliciously published, the defence of privilege or fair comment are forfeited.",
        "report": "FALSE",
        "course": "Law of Torts",
        "topic": "Defamation", 
        "tag": "Defences to Defamation, Qualified Privilege, Fair Comment",
        "principles": "Where defamatory words are published more extensively than the occasion requires, or maliciously published, the defence of privilege or fair comment are forfeited.",
        "level": "300",
        "slug": "saraki-v-soleye-1972-2-uilr-271-5197",
        "court": null,
        "date": null,
        "country": null,
        "citation": null,
        "judges": null,
        "judicial_precedent": null,
        "creator": {
          "id": 1,
          "name": "Stay Njokede"
        },
        "files": [],
        "content_type": "cases",
        "trending_metrics": {
          "trending_score": 67.18,
          "total_views": 2,
          "unique_viewers": 1,
          "weighted_score": 2,
          "latest_view": "2025-08-27 10:04:57",
          "earliest_view": "2025-08-27 01:27:25"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 1,
      "total": 1
    },
    "links": {
      "first": "http://localhost:8000/api/trending/cases?page=1",
      "last": "http://localhost:8000/api/trending/cases?page=1",
      "prev": null,
      "next": null
    }
  },
  "content_type": "cases",
  "filters_applied": {
    "content_type": "cases",
    "time_range": "month"
  },
  "stats": {
    "cases": 9,
    "statutes": 11,
    "divisions": 0,
    "provisions": 2,
    "notes": 5,
    "folders": 0,
    "comments": 0,
    "total": 27,
    "time_range": "month",
    "date_range": {
      "start": "2025-08-09",
      "end": "2025-09-08"
    }
  }
}
```

## Advanced Filtering Examples

### Time Range Filtering

**Week Trending (Default):**
```http
GET /api/trending
```

**Today's Trending:**
```http
GET /api/trending?time_range=today
```

**Monthly Trending:**
```http
GET /api/trending?time_range=month
```

**Custom Date Range:**
```http
GET /api/trending?time_range=custom&start_date=2025-08-26&end_date=2025-08-27
```

### Demographic Filtering

**Filter by Country:**
```http
GET /api/trending/statutes?country=Nigeria
```

**Filter by University:**
```http
GET /api/trending?university=University%20of%20Lagos
```

**Filter by University and Academic Level:**
```http
GET /api/trending?university=University%20of%20Lagos&level=undergraduate
```

## Trending Metrics

Each trending item includes detailed metrics:

| Metric | Description |
|--------|-------------|
| `trending_score` | Calculated score based on views, recency, and engagement (higher = more trending) |
| `total_views` | Total number of views in the time period |
| `unique_viewers` | Number of unique users who viewed the content |
| `weighted_score` | Time-weighted view count (recent views weighted more heavily) |
| `latest_view` | Timestamp of the most recent view |
| `earliest_view` | Timestamp of the earliest view in the time period |

## Trending Algorithm

The trending score is calculated using:
- **View Count**: Total number of views
- **Recency Boost**: Recent views are weighted more heavily (3x for last 24 hours, 2x for last 3 days)
- **Engagement Diversity**: Higher scores for content viewed by multiple unique users
- **Time Decay**: Content viewed more recently gets higher trending scores

## Response Structure

All trending endpoints return data in this consistent structure:

```json
{
  "status": "success",
  "message": "...",
  "data": {
    "trending": [...],    // Array of trending items with full model data
    "meta": {...},        // Pagination metadata
    "links": {...}        // Pagination links
  },
  "content_type": "...",   // Present for content-specific endpoints
  "filters_applied": {...}, // Applied filters summary
  "stats": {...}          // Overall trending statistics
}
```

## Error Responses

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "time_range": ["The selected time_range is invalid."],
    "per_page": ["The per_page may not be greater than 50."]
  }
}
```

### Custom Date Range Validation Error
```json
{
  "status": "error", 
  "message": "Validation failed",
  "errors": {
    "level": ["The level filter requires a university to be specified."]
  }
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to retrieve trending content",
  "errors": {
    "error": "Database connection error"
  }
}
```

## Pagination

All trending endpoints support pagination with consistent metadata:
- `current_page`: Current page number
- `from`/`to`: Item range on current page  
- `last_page`: Total number of pages
- `per_page`: Items per page
- `total`: Total number of trending items
- Links for `first`, `last`, `prev`, `next` pages

## Empty Results

When no trending content matches the filters:

```json
{
  "status": "success",
  "message": "Trending content retrieved successfully",
  "data": {
    "trending": [],
    "meta": {
      "current_page": 1,
      "from": null,
      "last_page": 1, 
      "per_page": 15,
      "to": null,
      "total": 0
    },
    "links": {...}
  },
  "filters_applied": {...},
  "stats": {
    "total": 0,
    "time_range": "week",
    "date_range": {...}
  }
}
```

## Rate Limiting

No rate limiting is applied to trending endpoints as they are read-only and publicly accessible.

## Notes

- Trending calculations require a minimum of 2 views for content to be considered trending
- The trending algorithm updates in real-time based on current view data
- Content types with no recent views will not appear in trending results
- Geographic and demographic filtering is based on user profile data at the time of viewing