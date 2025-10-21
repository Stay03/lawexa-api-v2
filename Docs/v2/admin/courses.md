# Course Management API - Admin Endpoints

## Overview
The Course Management Admin API provides comprehensive CRUD operations for course records. These endpoints are restricted to users with administrative privileges (admin, superadmin, or researcher roles).

## Base URL
```
https://rest.lawexa.com/api/admin
```
For local development:
```
http://localhost:8000/api/admin
```

## Authentication
All admin endpoints require authentication with appropriate role permissions.

### Required Roles
- **admin**: Full access to course management
- **superadmin**: Full access to course management
- **researcher**: Full access to course management

### Authentication Headers
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

## Endpoints

### Get Admin Courses List
Retrieve a paginated list of all courses with administrative privileges.

**Endpoint:** `GET /admin/courses`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in course name |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courses?search=law&per_page=20" \
  -H "Authorization: Bearer {access_token}" \
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
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 10,
      "total": 10
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/courses?page=1",
      "last": "https://rest.lawexa.com/api/admin/courses?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

### Create Course
Create a new course record.

**Endpoint:** `POST /admin/courses`

**Access:** Admin, SuperAdmin, Researcher roles required

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Course name (max 255 characters) |

**Example Request:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/courses" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Contract Law"
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Course created successfully",
  "data": {
    "course": {
      "id": 5,
      "name": "Contract Law",
      "slug": "contract-law",
      "creator": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2025-10-21T16:00:00.000000Z",
      "updated_at": "2025-10-21T16:00:00.000000Z"
    }
  }
}
```

**Error Response (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["Course name is required"]
  }
}
```

---

### Get Course by ID
Retrieve detailed information about a specific course using its ID.

**Endpoint:** `GET /admin/courses/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The course ID |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courses/1" \
  -H "Authorization: Bearer {access_token}" \
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

### Update Course
Update an existing course record.

**Endpoint:** `PUT /admin/courses/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The course ID |

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Course name (max 255 characters) |

**Example Request:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/courses/1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Advanced Constitutional Law"
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Course updated successfully",
  "data": {
    "course": {
      "id": 1,
      "name": "Advanced Constitutional Law",
      "slug": "advanced-constitutional-law",
      "creator": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2025-10-21T15:30:00.000000Z",
      "updated_at": "2025-10-21T16:15:00.000000Z"
    }
  }
}
```

**Error Responses:**

**Validation Error (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["Course name is required"]
  }
}
```

**Not Found (404):**
```json
{
  "status": "error",
  "message": "Course not found"
}
```

---

### Delete Course
Delete a course record permanently.

**Endpoint:** `DELETE /admin/courses/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The course ID |

**Example Request:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/courses/1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Course deleted successfully",
  "data": []
}
```

**Error Responses:**

**Not Found (404):**
```json
{
  "status": "error",
  "message": "Course not found"
}
```

**Forbidden (403):**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

---

## Error Responses

### Unauthorized (401)
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "status": "error",
  "message": "Unauthorized. Admin access required."
}
```

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["Course name is required"]
  }
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to create course: [error details]"
}
```

---

## Common Use Cases

### 1. List All Courses
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courses" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

### 2. Search Courses
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courses?search=law" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

### 3. Create a New Course
```bash
curl -X POST "https://rest.lawexa.com/api/admin/courses" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Criminal Law"
  }'
```

### 4. Update Course Name
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/courses/5" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Advanced Criminal Law"
  }'
```

### 5. Delete a Course
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/courses/5" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

---

## Notes
- All course slugs are automatically generated from the course name
- Updating the course name will automatically update the slug
- The `created_by` field is automatically set to the authenticated user's ID
- Courses can only be managed by users with admin, superadmin, or researcher roles
- Deleting a course will cascade delete related records due to foreign key constraints
