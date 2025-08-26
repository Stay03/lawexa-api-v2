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
| `topic` | string | No | - | Filter by topic |
| `level` | string | No | - | Filter by academic level |
| `course` | string | No | - | Filter by course |
| `date_from` | date | No | - | Filter cases from this date (YYYY-MM-DD) |
| `date_to` | date | No | - | Filter cases to this date (YYYY-MM-DD) |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |
| `include_similar_cases` | boolean | No | false | Include similar cases relationships |
| `include_cited_cases` | boolean | No | false | Include cited cases relationships |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases?search=property&country=Nigeria&per_page=10" \
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

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/cases/sanusi-v-makinde-5194" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 5109,
      "title": "Sanusi v Makinde, (1994) 5 NWLR (PT. 343) 214",
      "body": "Complete case details...",
      "report": "reportDocs/filename.pdf",
      "course": "Land Law",
      "topic": "Family Land",
      "tag": "Right of Allotment,Family Land,Partition of Land",
      "principles": "Legal principles established...",
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
      "views_count": 1,
      "similar_cases": [
        {
          "id": 5108,
          "title": "Related Case Title",
          "slug": "related-case-slug",
          "court": "High Court",
          "date": "1993-05-15",
          "country": "Nigeria",
          "citation": "Citation details"
        }
      ],
      "similar_cases_count": 1,
      "cited_cases": [],
      "cited_cases_count": 0,
      "created_at": "2025-07-30T15:26:52.000000Z",
      "updated_at": "2025-02-15T18:19:24.000000Z"
    }
  }
}
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
- **Country:** Filter by jurisdiction (e.g., `country=Nigeria`)
- **Court:** Filter by specific court (e.g., `court=Supreme Court`)
- **Topic:** Filter by legal topic (e.g., `topic=Family Law`)
- **Level:** Filter by academic level (e.g., `level=400`)
- **Course:** Filter by course subject (e.g., `course=Land Law`)
- **Date Range:** Filter by case date (`date_from=1990-01-01&date_to=2000-12-31`)

### Combined Filtering
```bash
curl -X GET "https://rest.lawexa.com/api/cases?search=contract&country=Nigeria&level=400&per_page=5" \
  -H "Accept: application/json"
```

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
- `similar_cases`: Related similar cases
- `cited_cases`: Cases that cite or are cited by this case

### Computed Fields
- `views_count`: Number of times case has been viewed
- `files_count`: Number of attached files
- `similar_cases_count`: Number of similar cases
- `cited_cases_count`: Number of cited cases

## Features for Authenticated Users

### View Tracking
When authenticated users access case details, their views are automatically tracked:
- View count is incremented
- User activity is logged for analytics
- Personalized recommendations can be generated

### Enhanced Experience
Authenticated users get:
- View history tracking
- Personalized case recommendations
- Ability to bookmark cases (if implemented)
- Access to premium content (based on subscription)

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