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

### Divisions
**Complete division content:**
- `title` - Division title (e.g., "Citizenship", "Fundamental Rights")
- `division_number` - Section number (e.g., "I", "II", "III")
- `division_type` - Type of division (e.g., "chapter", "part", "section", "article")
- `division_subtitle` - Additional subtitle if available
- `range` - Coverage range (e.g., "Sections 25-33")
- `content` - Full text content
- Relationship to parent `statute`

### Provisions
**Complete provision content:**
- `title` - Provision title if available
- `provision_number` - Section number (e.g., "26", "(1)", "1A")
- `provision_type` - Type of provision (e.g., "section", "subsection", "article")
- `provision_text` - Full text of the provision
- `marginal_note` - Side notes or annotations
- `content` - Additional content
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
| `country` | string | No | - | Filter by country. Use `yes` to auto-detect from IP address, or specify a country name/code |
| `student` | string | No | - | Filter by student profile. Use `yes` to auto-detect from authenticated user's university |
| `university` | string | No | - | Filter by user's university profile |
| `level` | string | No | - | Filter by academic level (any format, e.g., `100`, `100L`, `Level 100`, `undergraduate`, `graduate`, `postgraduate`, `phd`) |
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
        "title": "Citizenship by registration",
        "slug": "citizenship-by-registration-RQ41Trzf",
        "provision_number": "26",
        "provision_type": "section",
        "provision_text": "[Section has subsections]",
        "marginal_note": null,
        "content": null,
        "division": {
          "id": 168,
          "title": "Citizenship",
          "slug": "citizenship"
        },
        "statute": {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999"
        },
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

### Example: Trending Divisions with Full Content

**Request:**
```http
GET /api/trending/divisions?time_range=week
```

**Response (showing complete division data):**
```json
{
  "status": "success",
  "message": "Trending divisions retrieved successfully",
  "data": {
    "trending": [
      {
        "id": 168,
        "title": "Citizenship",
        "slug": "citizenship",
        "division_number": "III",
        "division_type": "chapter",
        "division_subtitle": null,
        "range": "Sections 25-33",
        "content": null,
        "statute": {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999"
        },
        "content_type": "divisions",
        "trending_metrics": {
          "trending_score": 26.25,
          "total_views": 5,
          "unique_viewers": 3,
          "weighted_score": 15,
          "latest_view": "2025-10-07 00:27:16",
          "earliest_view": "2025-10-07 00:18:47"
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
      "first": "https://rest.lawexa.com/api/trending/divisions?page=1",
      "last": "https://rest.lawexa.com/api/trending/divisions?page=1",
      "prev": null,
      "next": null
    }
  },
  "content_type": "divisions",
  "filters_applied": {
    "content_type": "divisions"
  },
  "stats": {
    "cases": 34,
    "statutes": 12,
    "divisions": 5,
    "provisions": 3,
    "notes": 0,
    "folders": 0,
    "comments": 0,
    "total": 54,
    "time_range": "week",
    "date_range": {
      "start": "2025-09-30",
      "end": "2025-10-07"
    }
  }
}
```

### Example: Trending Provisions with Full Content

**Request:**
```http
GET /api/trending/provisions?time_range=week&per_page=5
```

**Response (showing complete provision data):**
```json
{
  "status": "success",
  "message": "Trending provisions retrieved successfully",
  "data": {
    "trending": [
      {
        "id": 266,
        "title": "Citizenship by registration",
        "slug": "citizenship-by-registration-RQ41Trzf",
        "provision_number": "26",
        "provision_type": "section",
        "provision_text": "[Section has subsections]",
        "marginal_note": null,
        "content": null,
        "division": {
          "id": 168,
          "title": "Citizenship",
          "slug": "citizenship"
        },
        "statute": {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999"
        },
        "content_type": "provisions",
        "trending_metrics": {
          "trending_score": 22.02,
          "total_views": 4,
          "unique_viewers": 2,
          "weighted_score": 12,
          "latest_view": "2025-10-07 01:33:49",
          "earliest_view": "2025-10-07 00:18:49"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 5,
      "to": 1,
      "total": 1
    },
    "links": {
      "first": "https://rest.lawexa.com/api/trending/provisions?page=1",
      "last": "https://rest.lawexa.com/api/trending/provisions?page=1",
      "prev": null,
      "next": null
    }
  },
  "content_type": "provisions",
  "filters_applied": {
    "content_type": "provisions"
  },
  "stats": {
    "cases": 34,
    "statutes": 12,
    "divisions": 5,
    "provisions": 4,
    "notes": 0,
    "folders": 0,
    "comments": 0,
    "total": 55,
    "time_range": "week",
    "date_range": {
      "start": "2025-09-30",
      "end": "2025-10-07"
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
GET /api/trending?university=University%20of%20Lagos&level=100
GET /api/trending?university=Central%20University%20Ghana&level=100L
GET /api/trending?university=University%20of%20Lagos&level=undergraduate
```

**Note**: Level filtering supports flexible formats including numeric levels (`100`, `200`, `300L`), descriptive levels (`Level 100`, `First Year`), and traditional academic levels (`undergraduate`, `graduate`, `postgraduate`, `phd`).

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

## IP-Based Country Detection

The trending API supports automatic country detection from the user's IP address. This feature allows you to get trending content specific to the user's geographic location without requiring them to specify a country.

### Usage

To enable IP-based country detection, use `country=yes` in your request:

**Example Request:**
```http
GET /api/trending?country=yes
GET /api/trending/cases?country=yes
```

### Response with Detected Country

When IP detection is successful, the response includes additional country information:

**Example Response:**
```json
{
  "status": "success",
  "message": "Trending content retrieved successfully",
  "data": {
    "trending": [...],
    "detected_country": {
      "name": "Nigeria",
      "code": "NG",
      "region": "Lagos",
      "city": "Lagos",
      "timezone": "Africa/Lagos",
      "ip_address": "197.210.65.89"
    },
    "filters_applied": {
      "country": "Nigeria (detected from IP)"
    },
    "country_detection_status": "success",
    "stats": {...}
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `detected_country` | object | Country information detected from IP (only when detection succeeds) |
| `detected_country.name` | string | Country name |
| `detected_country.code` | string | Two-letter country code |
| `detected_country.region` | string | State/region (if available) |
| `detected_country.city` | string | City (if available) |
| `detected_country.timezone` | string | Timezone (if available) |
| `detected_country.ip_address` | string | The IP address that was geolocated |
| `country_detection_status` | string | Detection status: `success`, `failed`, or absent if not used |

### Error Handling

If IP geolocation fails, the API will:
- Return `country_detection_status: "failed"`
- Omit the `detected_country` field
- Show trending content for all countries (no country filter applied)
- Continue processing the request normally

**Failed Detection Example:**
```json
{
  "status": "success",
  "message": "Trending content retrieved successfully",
  "data": {
    "trending": [...],
    "filters_applied": {},
    "country_detection_status": "failed",
    "stats": {...}
  }
}
```

### Caching

IP geolocation results are cached for 24 hours to minimize external API calls and improve performance. The cache key is based on the IP address MD5 hash.

### Notes

- IP detection works for all trending endpoints (general and content-specific)
- The feature gracefully falls back if geolocation services are unavailable
- Local development IPs (127.0.0.1, ::1) are skipped and will result in detection failure
- The system uses multiple geolocation providers with automatic failover

## Student-Based University Detection

The trending API supports automatic university detection from authenticated user profiles. This feature allows students to see trending content specific to their academic community, with intelligent level filtering based on content availability.

### Usage

To enable student-based university detection, use `student=yes` in your request:

**Example Request:**
```http
GET /api/trending?student=yes
GET /api/trending/cases?student=yes
GET /api/trending/statutes?student=yes&level=100
```

**Requirements:**
- User must be authenticated (valid Bearer token required)
- User must have `is_student = true` in their profile
- User must have a university specified in their profile

### Smart Level Filtering

The system implements intelligent academic level filtering:

1. **Primary Filtering**: Uses both university + user's academic level
2. **Content Check**: Requires at least 3 items with level filtering
3. **Automatic Fallback**: If insufficient content, uses university-only filtering
4. **Transparency**: Shows whether level filtering was applied and why

**Response Examples:**

**Level Filtering Applied (sufficient content):**
```json
{
  "detected_university": {
    "name": "University of Lagos",
    "level": "undergraduate",
    "user_id": 215
  },
  "student_detection_status": "success",
  "level_filtering_applied": true,
  "level_filtering_reason": "sufficient_content",
  "filters_applied": {
    "university": "University of Lagos + undergraduate (detected)",
    "level": "undergraduate (detected from student profile)"
  }
}
```

**Level Filtering Skipped (insufficient content):**
```json
{
  "detected_university": {
    "name": "University of Lagos",
    "level": "undergraduate",
    "user_id": 215
  },
  "student_detection_status": "success",
  "level_filtering_applied": false,
  "level_filtering_reason": "insufficient_content",
  "filters_applied": {
    "university": "University of Lagos (detected from student profile)"
  }
}
```

**No Level in Profile:**
```json
{
  "detected_university": {
    "name": "University of Lagos",
    "level": null,
    "user_id": 215
  },
  "student_detection_status": "success",
  "level_filtering_applied": false,
  "level_filtering_reason": "no_level_in_profile",
  "filters_applied": {
    "university": "University of Lagos (detected from student profile)"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `detected_university` | object | University information detected from user profile |
| `detected_university.name` | string | University name |
| `detected_university.level` | string | Academic level (null if not set) |
| `detected_university.user_id` | integer | User ID of the authenticated student |
| `student_detection_status` | string | Detection status: `success` or `failed` |
| `level_filtering_applied` | boolean | Whether level filtering was used |
| `level_filtering_reason` | string | Why level was/wasn't applied: `sufficient_content`, `insufficient_content`, `no_level_in_profile` |

### Error Handling

If student detection fails, the API will:
- Return `student_detection_status: "failed"`
- Include `student_detection_error` with specific reason
- Show trending content for all users (no university filter applied)
- Continue processing the request normally

**Error Examples:**

**Not Authenticated:**
```json
{
  "student_detection_status": "failed",
  "student_detection_error": {
    "error": "authentication_required",
    "message": "User must be authenticated to use student detection"
  }
}
```

**Not a Student:**
```json
{
  "student_detection_status": "failed",
  "student_detection_error": {
    "error": "not_a_student",
    "message": "User is not registered as a student"
  }
}
```

**No University:**
```json
{
  "student_detection_status": "failed",
  "student_detection_error": {
    "error": "no_university",
    "message": "Student profile has no university specified"
  }
}
```

### Level Override

Users can manually specify a level to override the automatic detection:

```http
GET /api/trending?student=yes&level=200
```

This will:
- Auto-detect the user's university
- Manually filter by the specified level
- Still show detection status in response

### Combined Filtering

Student detection works with other filters:

```http
GET /api/trending?student=yes&country=Nigeria&time_range=month
```

This shows trending content for:
- User's detected university
- Academic level (if sufficient content)
- Nigeria (country-based)
- Last month (time range)

### Notes

- Student detection requires authentication and works only for student accounts
- Smart level filtering ensures users always see relevant content
- Level filtering is skipped if it would return fewer than 3 items
- The feature respects existing privacy and data filtering settings
- All trending endpoints support student detection

## Rate Limiting

No rate limiting is applied to trending endpoints as they are read-only and publicly accessible.

## Notes

- Trending calculations require a minimum of 2 views for content to be considered trending
- The trending algorithm updates in real-time based on current view data
- Content types with no recent views will not appear in trending results
- Geographic and demographic filtering is based on IP geolocation data at the time of viewing, with automatic detection support