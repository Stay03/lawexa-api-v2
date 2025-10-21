# Court Management API - Admin Endpoints

## Overview
The Court Management Admin API provides comprehensive CRUD operations for court records. These endpoints are restricted to users with administrative privileges (admin, superadmin, or researcher roles).

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
- **admin**: Full access to court management
- **superadmin**: Full access to court management
- **researcher**: Full access to court management

### Authentication Headers
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

## Endpoints

### Get Admin Courts List
Retrieve a paginated list of all courts with administrative privileges.

**Endpoint:** `GET /admin/courts`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in court name |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courts?search=supreme&per_page=20" \
  -H "Authorization: Bearer {access_token}" \
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
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 5,
      "total": 5
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/courts?page=1",
      "last": "https://rest.lawexa.com/api/admin/courts?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

### Create Court
Create a new court record.

**Endpoint:** `POST /admin/courts`

**Access:** Admin, SuperAdmin, Researcher roles required

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Court name (max 255 characters) |

**Example Request:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/courts" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Federal High Court Lagos"
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Court created successfully",
  "data": {
    "court": {
      "id": 5,
      "name": "Federal High Court Lagos",
      "slug": "federal-high-court-lagos",
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
    "name": ["Court name is required"]
  }
}
```

---

### Get Court by ID
Retrieve detailed information about a specific court using its ID.

**Endpoint:** `GET /admin/courts/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The court ID |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courts/1" \
  -H "Authorization: Bearer {access_token}" \
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

### Update Court
Update an existing court record.

**Endpoint:** `PUT /admin/courts/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The court ID |

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Court name (max 255 characters) |

**Example Request:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/courts/1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Supreme Court of Nigeria - Abuja"
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Court updated successfully",
  "data": {
    "court": {
      "id": 1,
      "name": "Supreme Court of Nigeria - Abuja",
      "slug": "supreme-court-of-nigeria-abuja",
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
    "name": ["Court name is required"]
  }
}
```

**Not Found (404):**
```json
{
  "status": "error",
  "message": "Court not found"
}
```

---

### Delete Court
Delete a court record permanently.

**Endpoint:** `DELETE /admin/courts/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The court ID |

**Example Request:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/courts/1" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Court deleted successfully",
  "data": []
}
```

**Error Responses:**

**Not Found (404):**
```json
{
  "status": "error",
  "message": "Court not found"
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
    "name": ["Court name is required"]
  }
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to create court: [error details]"
}
```

---

## Common Use Cases

### 1. List All Courts
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courts" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

### 2. Search Courts
```bash
curl -X GET "https://rest.lawexa.com/api/admin/courts?search=appeal" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

### 3. Create a New Court
```bash
curl -X POST "https://rest.lawexa.com/api/admin/courts" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Court of Appeal Lagos Division"
  }'
```

### 4. Update Court Name
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/courts/5" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Court of Appeal Lagos Division - Main Branch"
  }'
```

### 5. Delete a Court
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/courts/5" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

---

## Notes
- All court slugs are automatically generated from the court name
- Updating the court name will automatically update the slug
- The `created_by` field is automatically set to the authenticated user's ID
- Courts can only be managed by users with admin, superadmin, or researcher roles
- Deleting a court will cascade delete related records due to foreign key constraints
