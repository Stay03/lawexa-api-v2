# Courts API - User Endpoints

## Overview
The Courts API provides public and authenticated user access to court records in the Lawexa system. Users can browse, search, and view court information.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
Court endpoints work without authentication, making them publicly accessible. Authenticated users can access the same endpoints with their credentials.

### Authentication Headers (Optional)
```http
Authorization: Bearer {access_token}
Accept: application/json
```

## Endpoints

### Get Courts List
Retrieve a paginated list of courts with optional search capabilities.

**Endpoint:** `GET /courts`

**Access:** Public (no authentication required)

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in court name |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
# Get all courts
curl -X GET "https://rest.lawexa.com/api/courts" \
  -H "Accept: application/json"

# Search courts
curl -X GET "https://rest.lawexa.com/api/courts?search=supreme&per_page=10" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Courts retrieved successfully",
  "data": {
    "courts": [
      {
        "id": 1,
        "name": "Supreme Court of Nigeria",
        "slug": "supreme-court-of-nigeria",
        "creator": {
          "id": 1,
          "name": "Admin User"
        },
        "created_at": "2025-10-21T15:30:00.000000Z",
        "updated_at": "2025-10-21T15:30:00.000000Z"
      },
      {
        "id": 2,
        "name": "Federal High Court",
        "slug": "federal-high-court",
        "creator": {
          "id": 1,
          "name": "Admin User"
        },
        "created_at": "2025-10-21T15:35:00.000000Z",
        "updated_at": "2025-10-21T15:35:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 2,
      "per_page": 15,
      "to": 15,
      "total": 25
    },
    "links": {
      "first": "https://rest.lawexa.com/api/courts?page=1",
      "last": "https://rest.lawexa.com/api/courts?page=2",
      "prev": null,
      "next": "https://rest.lawexa.com/api/courts?page=2"
    }
  }
}
```

---

### Get Single Court
Retrieve detailed information about a specific court by its slug.

**Endpoint:** `GET /courts/{slug}`

**Access:** Public (no authentication required)

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | The court slug (URL-friendly version of the name) |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/courts/supreme-court-of-nigeria" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Court retrieved successfully",
  "data": {
    "court": {
      "id": 1,
      "name": "Supreme Court of Nigeria",
      "slug": "supreme-court-of-nigeria",
      "creator": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2025-10-21T15:30:00.000000Z",
      "updated_at": "2025-10-21T15:30:00.000000Z"
    }
  }
}
```

**Error Response (404):**
```json
{
  "status": "error",
  "message": "Court not found"
}
```

---

## Response Fields

### Court Object
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique court identifier |
| `name` | string | Court name |
| `slug` | string | URL-friendly version of the court name |
| `creator` | object | Creator information (id, name) |
| `created_at` | string | ISO 8601 timestamp of creation |
| `updated_at` | string | ISO 8601 timestamp of last update |

---

## Error Responses

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "per_page": ["The per page must not be greater than 100."]
  }
}
```

### Not Found (404)
```json
{
  "status": "error",
  "message": "Court not found"
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "An error occurred while processing your request"
}
```

---

## Common Use Cases

### 1. Browse All Courts
```bash
curl -X GET "https://rest.lawexa.com/api/courts" \
  -H "Accept: application/json"
```

### 2. Search for Specific Courts
```bash
curl -X GET "https://rest.lawexa.com/api/courts?search=appeal" \
  -H "Accept: application/json"
```

### 3. Get Court Details
```bash
curl -X GET "https://rest.lawexa.com/api/courts/supreme-court-of-nigeria" \
  -H "Accept: application/json"
```

### 4. Pagination
```bash
curl -X GET "https://rest.lawexa.com/api/courts?page=2&per_page=20" \
  -H "Accept: application/json"
```

---

## Notes
- All court slugs are automatically generated from the court name
- Slugs are unique and URL-safe
- Public endpoints do not require authentication
- Courts are ordered by creation date (newest first)
