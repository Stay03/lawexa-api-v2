# Courses API - User Endpoints

## Overview
The Courses API provides public and authenticated user access to course records in the Lawexa system. Users can browse, search, and view course information.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
Course endpoints work without authentication, making them publicly accessible. Authenticated users can access the same endpoints with their credentials.

### Authentication Headers (Optional)
```http
Authorization: Bearer {access_token}
Accept: application/json
```

## Endpoints

### Get Courses List
Retrieve a paginated list of courses with optional search capabilities.

**Endpoint:** `GET /courses`

**Access:** Public (no authentication required)

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in course name |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
# Get all courses
curl -X GET "https://rest.lawexa.com/api/courses" \
  -H "Accept: application/json"

# Search courses
curl -X GET "https://rest.lawexa.com/api/courses?search=law&per_page=10" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Courses retrieved successfully",
  "data": {
    "courses": [
      {
        "id": 1,
        "name": "Constitutional Law",
        "slug": "constitutional-law",
        "creator": {
          "id": 1,
          "name": "Admin User"
        },
        "created_at": "2025-10-21T15:30:00.000000Z",
        "updated_at": "2025-10-21T15:30:00.000000Z"
      },
      {
        "id": 2,
        "name": "Criminal Law",
        "slug": "criminal-law",
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
      "last_page": 3,
      "per_page": 15,
      "to": 15,
      "total": 42
    },
    "links": {
      "first": "https://rest.lawexa.com/api/courses?page=1",
      "last": "https://rest.lawexa.com/api/courses?page=3",
      "prev": null,
      "next": "https://rest.lawexa.com/api/courses?page=2"
    }
  }
}
```

---

### Get Single Course
Retrieve detailed information about a specific course by its slug.

**Endpoint:** `GET /courses/{slug}`

**Access:** Public (no authentication required)

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | The course slug (URL-friendly version of the name) |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/courses/constitutional-law" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Course retrieved successfully",
  "data": {
    "course": {
      "id": 1,
      "name": "Constitutional Law",
      "slug": "constitutional-law",
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
  "message": "Course not found"
}
```

---

## Response Fields

### Course Object
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique course identifier |
| `name` | string | Course name |
| `slug` | string | URL-friendly version of the course name |
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
  "message": "Course not found"
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

### 1. Browse All Courses
```bash
curl -X GET "https://rest.lawexa.com/api/courses" \
  -H "Accept: application/json"
```

### 2. Search for Specific Courses
```bash
curl -X GET "https://rest.lawexa.com/api/courses?search=criminal" \
  -H "Accept: application/json"
```

### 3. Get Course Details
```bash
curl -X GET "https://rest.lawexa.com/api/courses/criminal-law" \
  -H "Accept: application/json"
```

### 4. Pagination
```bash
curl -X GET "https://rest.lawexa.com/api/courses?page=2&per_page=20" \
  -H "Accept: application/json"
```

---

## Notes
- All course slugs are automatically generated from the course name
- Slugs are unique and URL-safe
- Public endpoints do not require authentication
- Courses are ordered by creation date (newest first)
