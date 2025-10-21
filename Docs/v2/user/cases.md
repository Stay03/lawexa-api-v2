# Cases API - User Endpoints

## Overview
The Cases API provides public and authenticated user access to legal case records in the Lawexa system. Users can browse, search, and view detailed case information.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
Most user endpoints work without authentication, but authenticated users get additional features like view tracking. Guest users can also access all user endpoints.

### Authentication Headers (Optional)
```http
Authorization: Bearer {access_token}
Accept: application/json
```

## Bot Detection & SEO Features

The Lawexa API includes intelligent bot detection to provide SEO-friendly access for search engines and web crawlers while maintaining security for human users.

### Bot Detection
The system automatically detects bots using:
- **User Agent Analysis**: Comprehensive patterns for known bots (Google, Bing, Facebook, etc.)
- **Header Detection**: Bot-specific HTTP headers
- **IP-Based Rules**: Configurable IP inclusion/exclusion lists

### SEO Benefits for Bots
- **No Authentication Required**: Search engines can access content without barriers
- **Content Filtering**: Sensitive content automatically filtered for bots
- **Enhanced Response Data**: Bot-specific metadata included in responses
- **Optimized Performance**: Bots bypass rate limiting and cooldown periods

### Bot Response Features
When accessed by bots, API responses include additional fields:
```json
{
  "isBot": true,
  "bot_info": {
    "bot_name": "Google Bot",
    "is_search_engine": true,
    "is_social_media": false
  }
  // Content may be filtered for bot consumption
}
```

### Supported Bot Types
- **Search Engines**: Google Bot, Bing Bot, Yandex Bot, DuckDuckGo Bot
- **Social Media**: Facebook External Hit, Twitter Bot, LinkedIn Bot
- **SEO Tools**: Ahrefs Bot, SEMrush Bot, Majestic Bot

## Endpoints

### Get Cases List
Retrieve a paginated list of cases with optional filtering and search capabilities.

**Endpoint:** `GET /cases`

**Access:** Public (no authentication required) or Authenticated users

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in case title, body, court, or citation |
| `country` | string | No | - | Filter by country |
| `court` | string | No | - | Filter by court |
| `topic` | string | No | - | Filter by topic (exact, case-insensitive match) |
| `level` | string | No | - | Filter by academic level |
| `course` | string | No | - | Filter by course (exact, case-insensitive match) |
| `tag` | string | No | - | Filter by tag (partial, case-insensitive LIKE match) |
| `date_from` | date | No | - | Filter cases from this date (YYYY-MM-DD) |
| `date_to` | date | No | - | Filter cases to this date (YYYY-MM-DD) |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |
| `include_similar_cases` | boolean | No | false | Include similar cases relationships |
| `include_cited_cases` | boolean | No | false | Include cited cases relationships |

**Example Request:**
```bash
# Basic filtering
curl -X GET "https://rest.lawexa.com/api/cases?search=property&country=Nigeria&per_page=10" \
  -H "Accept: application/json"

# Advanced filtering with tag, course, and topic
curl -X GET "https://rest.lawexa.com/api/cases?tag=Family%20Land&course=Land%20Law&per_page=5" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 5109,
        "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
        "body": "Legal case content...",
        "report": "reportDocs/filename.pdf",
        "course": "Land Law",
        "topic": "Family Land",
        "tag": "Right of Allotment,Family Land,Partition of Land",
        "principles": "Key legal principles...",
        "level": "400",
        "slug": "sanusi-v-makinde-5194",
        "court": "Court of Appeal",
        "date": "1994-03-30",
        "country": "Nigeria",
        "citation": "(1994) 5 NWLR (PT. 343) 214",
        "judges": "ALOMA MARIAM MUKHTAR JCA,ISA AYO SALAMI JCA,DAUDA AZAKI JCA",
        "judicial_precedent": null,
        "case_report_text": {},
        "creator": {
          "id": 1,
          "name": "Stay Njokede"
        },
        "files": [],
        "files_count": 0,
        "views_count": 0,
        "is_bookmarked": false,
        "bookmarks_count": 1,
        "similar_cases": {},
        "similar_cases_count": {},
        "cited_cases": {},
        "cited_cases_count": {},
        "created_at": "2025-07-30T15:26:52.000000Z",
        "updated_at": "2025-02-15T18:19:24.000000Z"
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1,
    "from": 1,
    "to": 1
  }
}
```

### Get Single Case
Retrieve detailed information about a specific case using its slug.

**Endpoint:** `GET /cases/{slug}`

**Access:** Public (no authentication required) or Authenticated users

**Note:** This endpoint automatically tracks views when accessed by authenticated users via the `track.views` middleware.

#### Search Tracking Support

This endpoint supports search tracking to help users understand which searches lead to specific content views. When a user views a case after performing a search, include the `search_query` parameter to track this relationship.

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search_query` | string | No | null | The search query that led to this case view (URL-encoded) |

**Example Requests:**
```bash
# Basic case view
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194" \
  -H "Accept: application/json"

# Case view with search tracking
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194?search_query=family+land" \
  -H "Accept: application/json"

# Authenticated user with search tracking
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194?search_query=right+of+allotment" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Important Notes for Search Tracking:**
- The `search_query` parameter should be URL-encoded by the frontend
- Search queries are limited to 500 characters (longer queries are automatically truncated)
- This enables search history tracking for users (see [Search History API](search-history.md))
- Views with `search_query` are classified as "search-initiated views"
- Views without `search_query` are classified as "direct/browsing views"
- Works for both authenticated and guest users

**Success Response (200) - Human User:**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 5109,
      "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
      "body": "The appellant, representing the Orisagunna branch of the Adesiyan family, sued the respondents, who represented the Adefegbe branch of the same family, over ownership and control of a piece of land at Inalende...",
      "report": "reportDocs/W8iXF7R0hkcyYdYcUOH2wwiGLHTCzJtIFQoPvNgb.pdf",
      "course": "Land Law",
      "topic": "Family Land",
      "tag": "Right of Allotment,Family Land,Partition of Land,Improvement on Family property",
      "principles": "When land is jointly owned by multiple branches of a family, it remains the collective property of all descendants. Both male and female children have equal inheritance rights under Yoruba customary law...",
      "level": "400",
      "slug": "sanusi-v-makinde-5194",
      "court": "Court of Appeal",
      "date": "1994-03-30",
      "country": "Nigeria",
      "citation": "(1994) 5 NWLR (PT. 343) 214",
      "judges": "ALOMA MARIAM MUKHTAR JCA,ISA AYO SALAMI JCA,DAUDA AZAKI JCA",
      "judicial_precedent": null,
      "case_report_text": "<p><strong>SANUSI V MAKINDE, (1994) 5 NWLR (PT. 343) 214</strong></p>\n<p>BETWEEN</p>\n<p><strong>LASISI EGBINOLA SANUSI</strong>...",
      "creator": {
        "id": 1,
        "name": "Stay Njokede"
      },
      "files": [],
      "files_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmarks_count": 1,
      "similar_cases": [
        {
          "id": 5208,
          "title": "Shelle v Asajon, (1957) 2 FSC 65; (1957) NSCC 55",
          "slug": "shelle-v-asajon-1957-2-fsc-65-5298",
          "court": null,
          "date": "1957-01-01",
          "country": "Nigeria",
          "citation": "(1957) 2 FSC 65; (1957) NSCC 55",
          "is_bookmarked": false,
          "bookmark_count": 0,
          "bookmark_id": null
        }
      ],
      "similar_cases_count": 2,
      "cited_cases": [],
      "cited_cases_count": 0,
      "created_at": "2025-07-30T15:26:52.000000Z",
      "updated_at": "2025-02-15T18:19:24.000000Z"
    }
  }
}
```

**Success Response (200) - Bot User (Google Bot):**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 5109,
      "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
      "course": "Land Law",
      "topic": "Family Land",
      "tag": "Right of Allotment,Family Land,Partition of Land,Improvement on Family property",
      "principles": "When land is jointly owned by multiple branches of a family, it remains the collective property of all descendants. Both male and female children have equal inheritance rights under Yoruba customary law...",
      "level": "400",
      "slug": "sanusi-v-makinde-5194",
      "court": "Court of Appeal",
      "date": "1994-03-30",
      "country": "Nigeria",
      "citation": "(1994) 5 NWLR (PT. 343) 214",
      "judges": "ALOMA MARIAM MUKHTAR JCA,ISA AYO SALAMI JCA,DAUDA AZAKI JCA",
      "judicial_precedent": null,
      "isBot": true,
      "bot_info": {
        "bot_name": "Google Bot",
        "is_search_engine": true,
        "is_social_media": false
      },
      "creator": {
        "id": 1,
        "name": "Stay Njokede"
      },
      "files_count": 0,
      "views_count": 7,
      "similar_cases": [
        {
          "id": 5208,
          "title": "Shelle v Asajon, (1957) 2 FSC 65; (1957) NSCC 55",
          "slug": "shelle-v-asajon-1957-2-fsc-65-5298",
          "court": "null",
          "date": "1957-01-01",
          "country": "Nigeria",
          "citation": "(1957) 2 FSC 65; (1957) NSCC 55"
        },
        {
          "id": 757,
          "title": "Bassey v Cobham, (1924) 5 NLR 90",
          "slug": "bassey-v-cobham-1924-5-nlr-90-775",
          "court": "null",
          "date": "1924-01-01",
          "country": "Nigeria",
          "citation": "(1924) 5 NLR 90"
        }
      ],
      "similar_cases_count": 2,
      "cited_cases": [],
      "cited_cases_count": 0,
      "created_at": "2025-07-30T15:26:52.000000Z",
      "updated_at": "2025-02-15T18:19:24.000000Z"
    }
  }
}
```

**Success Response (200) - Bot User (Facebook Bot):**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 5109,
      "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
      "course": "Land Law",
      "topic": "Family Land",
      "tag": "Right of Allotment,Family Land,Partition of Land,Improvement on Family property",
      "principles": "When land is jointly owned by multiple branches of a family...",
      "level": "400",
      "slug": "sanusi-v-makinde-5194",
      "court": "Court of Appeal",
      "date": "1994-03-30",
      "country": "Nigeria",
      "citation": "(1994) 5 NWLR (PT. 343) 214",
      "judges": "ALOMA MARIAM MUKHTAR JCA,ISA AYO SALAMI JCA,DAUDA AZAKI JCA",
      "judicial_precedent": null,
      "isBot": true,
      "bot_info": {
        "bot_name": "Facebook Bot",
        "is_search_engine": false,
        "is_social_media": true
      },
      "creator": {
        "id": 1,
        "name": "Stay Njokede"
      },
      "files_count": 0,
      "views_count": 10,
      "similar_cases": [...],
      "similar_cases_count": 2,
      "cited_cases": [],
      "cited_cases_count": 0,
      "created_at": "2025-07-30T15:26:52.000000Z",
      "updated_at": "2025-02-15T18:19:24.000000Z"
    }
  }
}
```

### Key Differences: Bot vs Human Responses

| Feature | Human Response | Bot Response |
|---------|---------------|-------------|
| **Bot Identification** | No `isBot` field | `"isBot": true` |
| **Bot Information** | Not included | `bot_info` object with bot details |
| **Case Body Content** | ✅ Full `body` field | ❌ `body` field excluded |
| **File Attachments** | ✅ Full `files` array | ❌ `files` field excluded |
| **File Metadata** | ✅ `files_count` included | ✅ `files_count` included |
| **Sensitive Content** | ✅ `report`, `case_report_text` | ❌ Sensitive fields excluded |
| **Response Size** | Full response (~100% size) | Lightweight response (~30% size) |
| **Authentication** | May require authentication for some endpoints | No authentication required |
| **View Tracking** | Standard cooldown periods apply | Bypasses cooldown restrictions |
| **Performance** | Standard rate limiting | Optimized for bot consumption |

### Bot Testing Examples

**Test with Google Bot:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194" \
  -H "Accept: application/json" \
  -H "User-Agent: Googlebot/2.1 (+http://www.google.com/bot.html)"
```

**Test with Facebook Bot:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194" \
  -H "Accept: application/json" \
  -H "User-Agent: facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
```

**Test with Human Browser:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194" \
  -H "Accept: application/json" \
  -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
```

**Error Responses:**
- **404 Not Found:** Case not found
```json
{
  "status": "error",
  "message": "Case not found",
  "data": null
}
```

## Guest User Access

Guest users can access the same endpoints as unauthenticated users with the benefit of session tracking. Guest tokens are created via:

**Endpoint:** `POST /auth/guest-session`

**Example Request:**
```bash
curl -X POST "https://rest.lawexa.com/api/auth/guest-session" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

**Success Response:**
```json
{
  "status": "success",
  "message": "Guest session created successfully",
  "data": {
    "token": "199|2adA05neQmnJ33RaIxsJSiZYyTPWG4OChEBIP59Ja6c37aa8",
    "guest_id": 86,
    "expires_at": "2025-09-25T12:47:03.000000Z"
  }
}
```

## Search and Filtering Features

### Text Search
Use the `search` parameter to search across:
- Case titles
- Case body content
- Court names
- Legal citations

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?search=property rights" \
  -H "Accept: application/json"
```

### Filtering Options
- **Tag:** Filter by tags (partial match, case-insensitive) (e.g., `tag=Criminal`, `tag=Family%20Land`)
- **Course:** Filter by course subject (exact match, case-insensitive) (e.g., `course=Land%20Law`)
- **Topic:** Filter by legal topic (exact match, case-insensitive) (e.g., `topic=Family%20Land`)
- **Country:** Filter by jurisdiction (e.g., `country=Nigeria`)
- **Court:** Filter by specific court (e.g., `court=Supreme Court`)
- **Level:** Filter by academic level (e.g., `level=400`)
- **Date Range:** Filter by case date (`date_from=1990-01-01&date_to=2000-12-31`)

**Note:** See the "Advanced Filtering with Tag, Course, and Topic" section below for detailed examples and implementation details.

### Combined Filtering
```bash
curl -X GET "https://rest.lawexa.com/api/cases?search=contract&country=Nigeria&level=400&per_page=5" \
  -H "Accept: application/json"
```

## Advanced Filtering with Tag, Course, and Topic

The Cases API now supports powerful filtering capabilities using `tag`, `course`, and `topic` parameters. These filters can be used individually or combined for precise case discovery.

### Filter Matching Behavior

| Filter Type | Matching Method | Case Sensitivity | Example |
|-------------|----------------|------------------|---------|
| **Tag** | Partial match (LIKE) | Case-insensitive | `tag=Criminal` matches "Criminal Damage", "Criminal Law" |
| **Course** | Exact match | Case-insensitive | `course=Land Law` matches "Land Law" only |
| **Topic** | Exact match | Case-insensitive | `topic=Family Land` matches "Family Land" only |

### URL Encoding for Spaces

When filter values contain spaces, they must be URL-encoded:
- `Land Law` → `Land%20Law`
- `Family Land` → `Family%20Land`
- `Property Rights` → `Property%20Rights`

### Filtering Examples

#### Single Tag Filtering
**Find cases with "Criminal" in tags:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?tag=Criminal&per_page=3" \
  -H "Accept: application/json"
```
*Result: 107 cases found*

**Sample Response:**
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 5101,
        "title": "Samuels v Stubbs, [1972] 4 SASR 200",
        "course": "Criminal Law",
        "topic": "Malicious Damage To Property",
        "tag": "Criminal Damage",
        // ... other fields
      },
      {
        "id": 5723,
        "title": "Ozana Ubierho v The State, [2005] 5 NWLR (Pt. 919) 644",
        "course": "Criminal Law",
        "topic": "Parties to an Offence",
        "tag": "Common Intention,Criminal Conspiracy,Murder",
        // ... other fields
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 36,
      "per_page": 3,
      "total": 107
    }
  }
}
```

#### Course Filtering
**Find Land Law cases:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?course=Land%20Law&per_page=3" \
  -H "Accept: application/json"
```
*Result: 474 cases found*

#### Case Insensitive Filtering
**Case insensitive course filtering works:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?course=land%20law&per_page=2" \
  -H "Accept: application/json"
```
*Result: 474 cases found (same as uppercase)*

#### Combined Tag and Course Filtering
**Find cases about Family Land in Land Law:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?tag=Family%20Land&course=Land%20Law&per_page=3" \
  -H "Accept: application/json"
```
*Result: 80 cases found*

**Sample Combined Response:**
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 5107,
        "title": "Santeng v Darkwa, (1976) 3 OYSHC (PT 1) 127",
        "course": "Land Law",
        "topic": "",
        "tag": "Family Land, Will, Customary Land Tenure System",
        "principles": "A building erected on family land could be passed to beneficiaries under a person's will, separate and distinct from the land which remained family property."
      },
      {
        "id": 5109,
        "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
        "course": "Land Law",
        "topic": "Family Land",
        "tag": "Right of Allotment,Family Land,Partition of Land,Improvement on Family property",
        "principles": "When land is jointly owned by multiple branches of a family, it remains the collective property of all descendants."
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 27,
      "per_page": 3,
      "total": 80
    }
  }
}
```

#### Triple Parameter Filtering
**Find cases matching all three criteria:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?tag=Family%20Land&course=Land%20Law&topic=Property%20Rights&per_page=3" \
  -H "Accept: application/json"
```
*Result: 0 cases found (no cases match all three criteria exactly)*

**Empty Response Example:**
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 3,
      "total": 0,
      "from": null,
      "to": null
    }
  }
}
```

#### Filtering with Search
**Combine text search with tag filtering:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?search=contract&tag=Damages&per_page=5" \
  -H "Accept: application/json"
```

#### Pagination with Filtering
**Navigate through filtered results:**
```bash
# Page 1
curl -X GET "https://rest.lawexa.com/api/cases?course=Land%20Law&per_page=2&page=1" \
  -H "Accept: application/json"

# Page 2
curl -X GET "https://rest.lawexa.com/api/cases?course=Land%20Law&per_page=2&page=2" \
  -H "Accept: application/json"
```

### Implementation Details

#### Tag Filtering (LIKE Query)
- Uses SQL `LIKE` operator with wildcards
- Matches any tag containing the search term
- Case-insensitive comparison
- Multiple tags are searched (comma-separated values)

**Examples:**
- `tag=Criminal` matches: "Criminal Damage", "Criminal Law", "Criminal Conspiracy"
- `tag=Family` matches: "Family Land", "Family Property", "Customary Family Law"

#### Course & Topic Filtering (Exact Match)
- Uses SQL `=` operator with case-insensitive comparison
- Requires exact match to course/topic value
- More precise than tag filtering

**Examples:**
- `course=Land Law` matches: "Land Law" only
- `course=land law` matches: "Land Law" (case-insensitive)
- `course=Land` matches: nothing (exact match required)

#### Filter Combination Logic
When multiple filters are applied, they use **AND** logic:
- `?tag=Family&course=Land Law` = cases where tag contains "Family" **AND** course equals "Land Law"
- `?tag=Family&course=Land Law&topic=Property Rights` = cases where all three conditions are true

#### Performance Considerations
- Tag filtering (LIKE) may be slower on large datasets
- Course and topic filtering (exact match) are optimized for performance
- Combined filters reduce result sets progressively
- Use pagination for large result sets

### Pagination
- **per_page:** Number of results per page (default: 15, max: 100)
- **page:** Page number to retrieve (default: 1)

### Related Cases
Include related case information:
- **include_similar_cases:** Include similar cases (boolean)
- **include_cited_cases:** Include cited cases (boolean)

## Case Data Structure

### Core Fields
- `id`: Unique identifier
- `title`: Case title/name
- `body`: Main case content
- `report`: Additional report information
- `course`: Associated course
- `topic`: Legal topic/area
- `tag`: Comma-separated tags
- `principles`: Legal principles established
- `level`: Academic level (300, 400, 500, etc.)
- `slug`: SEO-friendly URL slug
- `court`: Court that decided the case
- `date`: Date of case decision
- `country`: Country jurisdiction
- `citation`: Legal citation
- `judges`: Presiding judges
- `judicial_precedent`: Precedent information

### Relationships
- `creator`: User who created the case
- `files`: Associated file attachments
- `similar_cases`: Related similar cases with bookmark information for authenticated users
- `cited_cases`: Cases that cite or are cited by this case

### Computed Fields
- `views_count`: Number of times case has been viewed
- `files_count`: Number of attached files
- `similar_cases_count`: Number of similar cases
- `cited_cases_count`: Number of cited cases
- `is_bookmarked`: Boolean indicating if the current authenticated user has bookmarked this case
- `bookmarks_count`: Total number of users who have bookmarked this case
- `bookmark_id`: ID of the current user's bookmark for this case (null if not bookmarked)

### Similar Cases Bookmark Fields (Authenticated Users Only)
When accessed by authenticated users, each similar case includes additional bookmark information:
- `is_bookmarked`: Boolean indicating if the current user has bookmarked this similar case
- `bookmark_count`: Total number of users who have bookmarked this similar case
- `bookmark_id`: ID of the current user's bookmark for this similar case (null if not bookmarked)

### Bot-Specific Fields (when accessed by bots)
- `isBot`: Boolean indicating if request came from a bot
- `bot_info`: Object containing bot details:
  - `bot_name`: Name of the detected bot (e.g., "Google Bot", "Facebook Bot")
  - `is_search_engine`: Boolean indicating if bot is a search engine
  - `is_social_media`: Boolean indicating if bot is a social media crawler

## Features for Authenticated Users

### View Tracking
When authenticated users access case details, their views are automatically tracked:
- View count is incremented
- User activity is logged for analytics
- Personalized recommendations can be generated

### Bookmarking Cases
Authenticated users can bookmark cases for quick access later. Case responses include comprehensive bookmark status information:

**Main Case Bookmark Information:**
- `is_bookmarked`: Shows if the current user has bookmarked this case
- `bookmarks_count`: Shows total number of bookmarks across all users
- `bookmark_id`: ID of the current user's bookmark (null if not bookmarked)

**Similar Cases Bookmark Information:**
When viewing a case, authenticated users also see bookmark information for related similar cases:
- Each similar case includes `is_bookmarked`, `bookmark_count`, and `bookmark_id`
- This helps users quickly identify which related cases they've already bookmarked
- Enables efficient navigation and organization of related legal content

**Example Case Response with Bookmark Info:**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 5101,
      "title": "Samuels v Stubbs, [1972] 4 SASR 200",
      // ... other case fields
      "views_count": 37,
      "is_bookmarked": true,
      "bookmark_id": 15,
      "bookmarks_count": 2,
      "similar_cases": [
        {
          "id": 5208,
          "title": "Shelle v Asajon, (1957) 2 FSC 65; (1957) NSCC 55",
          "slug": "shelle-v-asajon-1957-2-fsc-65-5298",
          "court": null,
          "date": "1957-01-01",
          "country": "Nigeria",
          "citation": "(1957) 2 FSC 65; (1957) NSCC 55",
          "is_bookmarked": false,
          "bookmark_count": 0,
          "bookmark_id": null
        },
        {
          "id": 757,
          "title": "Bassey v Cobham, (1924) 5 NLR 90",
          "slug": "bassey-v-cobham-1924-5-nlr-90-775",
          "court": null,
          "date": "1924-01-01",
          "country": "Nigeria",
          "citation": "(1924) 5 NLR 90",
          "is_bookmarked": true,
          "bookmark_count": 3,
          "bookmark_id": 23
        }
      ],
      // ... rest of case data
    }
  }
}
```

**Bookmark Management:**
For complete bookmark management API documentation, including how to bookmark/unbookmark cases, see the [Bookmarks API Documentation](./bookmarks.md).

### Enhanced Experience
Authenticated users get:
- View history tracking
- Personalized case recommendations
- Ability to bookmark and organize cases
- Bookmark information for similar cases to easily track related content
- Access to premium content (based on subscription)
- User-specific case recommendations based on viewing history

## Error Responses

### Common Errors
**404 Not Found:**
```json
{
  "status": "error",
  "message": "Case not found",
  "data": null
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "Invalid parameters",
  "data": {
    "errors": {
      "per_page": ["The per_page must be between 1 and 100."]
    }
  }
}
```

**500 Server Error:**
```json
{
  "status": "error",
  "message": "An error occurred while retrieving cases",
  "data": null
}
```

## SEO Features
- **Slug-based URLs:** SEO-friendly case URLs using slugs
- **Structured Data:** Rich snippets for search engines
- **Metadata:** Comprehensive case metadata for indexing

## Rate Limiting
API requests are subject to rate limiting:
- **Unauthenticated users:** Standard rate limits
- **Authenticated users:** Higher rate limits
- **Guest users:** Same as authenticated users during session

## Best Practices

### Efficient Querying
- Use pagination for large result sets
- Include only necessary related data
- Cache frequently accessed cases

### Search Optimization
- Use specific search terms for better results
- Combine search with filters for precision
- Utilize date ranges for time-specific searches

### Performance Tips
- Request smaller page sizes for faster responses
- Use conditional requests when possible
- Implement client-side caching for frequently accessed cases