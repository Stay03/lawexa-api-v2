# Provisions API - Global Search Endpoint

## Overview
The Provisions API provides global search functionality across all statute provisions in the Lawexa system. Users can search, filter, and browse provisions from all statutes with comprehensive hierarchical data and content.

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

### Get Global Provisions
Retrieve a paginated list of provisions from all statutes with optional filtering and search capabilities.

**Endpoint:** `GET /provisions`

**Authentication:** Required

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in provision title, text, marginal note, or provision number |
| `provision_type` | string | No | - | Filter by provision type (section, subsection, clause, etc.) |
| `statute_id` | integer | No | - | Filter by specific statute ID |
| `division_id` | integer | No | - | Filter by specific division ID |
| `sort_by` | string | No | provision_number | Sort field: provision_title, provision_number, created_at, statute_title |
| `sort_order` | string | No | asc | Sort order: asc, desc |
| `per_page` | integer | No | 15 | Number of items per page (max 50) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?search=constitution&provision_type=section&sort_by=provision_number&per_page=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Provisions retrieved successfully",
  "data": {
    "provisions": [
      {
        "id": 149,
        "title": "Supremacy of constitution",
        "slug": "supremacy-of-constitution-PjVkiYCb",
        "provision_number": "1",
        "provision_type": "section",
        "provision_text": "This Constitution is supreme and its provisions shall have binding force on the authorities and persons throughout the Federal Republic of Nigeria.",
        "marginal_note": null,
        "content": null,
        "division": {
          "id": 378,
          "title": "Federal Republic of Nigeria",
          "slug": "federal-republic-of-nigeria-7rHPWlyB"
        },
        "statute": {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999"
        },
        "path": [
          {
            "id": 47,
            "type": "division",
            "title": "General provisions; Federal Republic of Nigeria; Powers of the Federal Republic of Nigeria",
            "number": "I",
            "structural_type": "chapter",
            "slug": "general-provisions-federal-republic-of-nigeria-powers-of-the-federal-republic-of-nigeria"
          },
          {
            "id": 378,
            "type": "division",
            "title": "Federal Republic of Nigeria",
            "number": "I",
            "structural_type": "part",
            "slug": "federal-republic-of-nigeria-7rHPWlyB"
          }
        ],
        "immediate_parent": {
          "id": 378,
          "type": "division",
          "title": "Federal Republic of Nigeria",
          "number": "I",
          "structural_type": "part",
          "slug": "federal-republic-of-nigeria-7rHPWlyB"
        },
        "is_bookmarked": false,
        "bookmark_id": null,
        "bookmarks_count": 0,
        "content_type": "provisions",
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
      "to": 6,
      "total": 6
    },
    "links": {
      "first": "https://rest.lawexa.com/api/provisions?page=1",
      "last": "https://rest.lawexa.com/api/provisions?page=1",
      "prev": null,
      "next": null
    },
    "content_type": "provisions"
  }
}
```

## Provision Data Structure

### Core Fields
- `id`: Unique identifier
- `title`: Provision title (may be null for subsections)
- `slug`: SEO-friendly URL slug
- `provision_number`: Provision number/identifier
- `provision_type`: Type of provision (section, subsection, clause, etc.)
- `provision_text`: Full text content of the provision
- `marginal_note`: Marginal note or summary (if available)
- `content`: Additional content (if available)

### Relationships
- `statute`: Parent statute information
  - `id`: Statute ID
  - `title`: Statute title
  - `slug`: Statute slug
- `division`: Parent division information (if provision belongs to a division)
  - `id`: Division ID
  - `title`: Division title
  - `slug`: Division slug
- `path`: Array of parent relationships forming the complete hierarchical path
- `immediate_parent`: Direct parent (can be provision or division)
  - `id`: Parent ID
  - `type`: Either "provision" or "division"
  - `title`: Parent title
  - `number`: Parent number
  - `structural_type`: Parent type
  - `slug`: Parent slug

### Computed Fields
- `is_bookmarked`: Boolean indicating if the current user has bookmarked this provision
- `bookmark_id`: Bookmark ID if bookmarked by current user
- `bookmarks_count`: Total number of bookmarks across all users
- `content_type`: Always "provisions"
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
- Provision titles
- Provision text content
- Marginal notes
- Provision numbers

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?search=constitution" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Type Filtering
Filter by specific provision types:
- `section`: Main sections
- `subsection`: Subsections within sections
- `clause`: Clauses within provisions
- `paragraph`: Paragraphs
- `schedule`: Scheduled provisions

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?provision_type=section" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Statute and Division Filtering
Filter by specific statutes or divisions:
- `statute_id`: Filter provisions from a specific statute
- `division_id`: Filter provisions from a specific division

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?statute_id=19&division_id=378" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Sorting Options
- `provision_title`: Sort alphabetically by title
- `provision_number`: Sort by provision number
- `created_at`: Sort by creation date
- `statute_title`: Sort by parent statute title

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?sort_by=provision_number&sort_order=asc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Combined Filtering
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?search=constitution&provision_type=section&statute_id=19&sort_by=provision_number&per_page=10&page=2" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Pagination
- **per_page**: Number of results per page (default: 15, max: 50)
- **page**: Page number to retrieve (default: 1)

**Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?per_page=20&page=3" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

## Hierarchical Structure

Provisions support complex hierarchical relationships where provisions can have parent provisions and belong to divisions within statutes.

### Path Structure
The `path` array shows the complete hierarchical path from the statute root to the current provision:
```json
"path": [
  {
    "id": 47,
    "type": "division",
    "title": "General provisions; Federal Republic of Nigeria; Powers of the Federal Republic of Nigeria",
    "number": "I",
    "structural_type": "chapter",
    "slug": "general-provisions-federal-republic-of-nigeria-powers-of-the-federal-republic-of-nigeria"
  },
  {
    "id": 378,
    "type": "division",
    "title": "Federal Republic of Nigeria",
    "number": "I",
    "structural_type": "part",
    "slug": "federal-republic-of-nigeria-7rHPWlyB"
  },
  {
    "id": 79,
    "type": "provision",
    "title": "Supremacy of constitution",
    "number": "1",
    "structural_type": "section",
    "slug": "supremacy-of-constitution-I5ENJmXK"
  }
]
```

### Immediate Parent
The `immediate_parent` field shows only the direct parent (can be provision or division):
```json
"immediate_parent": {
  "id": 79,
  "type": "provision",
  "title": "Supremacy of constitution",
  "number": "1",
  "structural_type": "section",
  "slug": "supremacy-of-constitution-I5ENJmXK"
}
```

## Content Types and Structure

### Section vs Subsection
- **Sections**: Main provisions with titles and numbers (e.g., "1. Supremacy of constitution")
- **Subsections**: Numbered provisions under sections (e.g., "(1) This Constitution is supreme...")

### Provision Text
- `provision_text`: Full legal text of the provision
- `marginal_note`: Brief summary or explanatory note
- `content`: Additional formatted content when available

## Features for Authenticated Users

### Bookmarking Provisions
Provision responses include bookmark status information:
- `is_bookmarked`: Shows if the current user has bookmarked this provision
- `bookmark_id`: The bookmark ID if bookmarked
- `bookmarks_count`: Total number of bookmarks across all users

**Example Provision Response with Bookmark Info:**
```json
{
  "id": 79,
  "title": "Supremacy of constitution",
  // ... other fields
  "is_bookmarked": true,
  "bookmark_id": 456,
  "bookmarks_count": 12,
  // ... rest of provision data
}
```

**Bookmark Management:**
For complete bookmark management API documentation, see the [Bookmarks API Documentation](./bookmarks.md).

### View Tracking and Analytics
Each provision includes comprehensive view metrics:
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
      "sort_by": ["The selected sort by is invalid."],
      "statute_id": ["The selected statute id is invalid."]
    }
  }
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to retrieve provisions",
  "data": {
    "error": "Database connection error"
  }
}
```

## Real Examples

### Example 1: Search for Constitution-related Provisions
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?search=constitution" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Example 2: Get All Sections from a Specific Statute
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?statute_id=19&provision_type=section" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Example 3: Filter by Division and Sort by Number
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?division_id=378&sort_by=provision_number&sort_order=asc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

### Example 4: Complex Search with Multiple Filters
```bash
curl -X GET "https://rest.lawexa.com/api/provisions?search=fundamental&provision_type=section&statute_id=19&per_page=5&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```

## Best Practices

### Efficient Querying
- Use statute_id and division_id for targeted searches within specific legislation
- Combine search terms with provision types for precise results
- Use pagination for large result sets
- Sort by provision_number for logical legislative order

### Search Optimization
- Use specific legal terms from provision text
- Try different provision types for comprehensive coverage
- Utilize marginal notes for concept-based searches
- Combine multiple filters for targeted research

### Performance Tips
- Request smaller page sizes for faster responses (15-25 items)
- Cache frequently accessed provisions
- Use specific statute_id when researching specific legislation
- Implement client-side pagination for better user experience

### Research Strategies
- Start broad with search terms, then narrow with filters
- Use provision_number sorting to read legislation in order
- Follow the path array to understand legislative context
- Use immediate_parent to navigate hierarchical relationships

## Rate Limiting
API requests are subject to rate limiting:
- **Authenticated users**: Standard rate limits apply
- **Guest users**: Same limits as authenticated users during session
- **Requests per minute**: Configurable based on user tier
- **Complex searches**: May count as multiple requests depending on server load