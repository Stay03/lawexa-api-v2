# User Management API

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://127.0.0.1:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## User Roles & Permissions

### Role Hierarchy
- **user** - Regular user (lowest privilege)
- **admin** - Administrator 
- **researcher** - Research access
- **superadmin** - Full system access (highest privilege)

### Access Matrix

| Action | Admin | Researcher | Superadmin |
|--------|-------|------------|------------|
| View users with role 'user' | ✅ | ✅ | ✅ |
| View users with role 'admin' | ✅ | ✅ | ✅ |
| View users with role 'researcher' | ✅ | ❌ | ✅ |
| View users with role 'superadmin' | ❌ | ❌ | ✅ |
| Edit users with role 'user' | ✅ | ❌ | ✅ |
| Edit users with role 'admin' | ❌ | ❌ | ✅ |
| Edit users with role 'researcher' | ✅ | ❌ | ✅ |
| Edit users with role 'superadmin' | ❌ | ❌ | ✅ |

### Role Assignment Permissions

| Assigner Role | Can Assign Roles |
|---------------|------------------|
| **admin** | user, researcher |
| **superadmin** | user, researcher, admin, superadmin |

---

## Endpoints

### 1. Get Users List

**GET** `/admin/users`

Retrieves a paginated list of users with filtering and search capabilities.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search by name or email (max 255 chars) |
| `role` | string | No | - | Filter by role: `user`, `admin`, `researcher`, `superadmin` |
| `verified` | boolean | No | - | Filter by email verification status |
| `oauth` | boolean | No | - | Filter by OAuth users (Google login) |
| `created_from` | date | No | - | Filter users created from date (YYYY-MM-DD) |
| `created_to` | date | No | - | Filter users created up to date (YYYY-MM-DD) |
| `page` | integer | No | 1 | Page number (min: 1) |
| `per_page` | integer | No | 10 | Items per page (min: 1, max: 100) |
| `sort_by` | string | No | created_at | Sort field: `name`, `email`, `role`, `created_at`, `updated_at`, `email_verified_at` |
| `sort_direction` | string | No | desc | Sort direction: `asc`, `desc` |

#### Example Request
```
GET /admin/users?search=john&role=user&verified=true&page=1&per_page=20&sort_by=name&sort_direction=asc
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Users filtered by admin permissions",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user",
        "avatar": "https://example.com/avatar.jpg",
        "google_id": "1234567890",
        "email_verified_at": "2023-01-01T00:00:00.000000Z",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/admin/users?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost:8000/api/admin/users?page=5",
    "links": [],
    "next_page_url": "http://localhost:8000/api/admin/users?page=2",
    "path": "http://localhost:8000/api/admin/users",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 50
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins, researchers, and superadmins can view users."
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "per_page": ["The per page field must not be greater than 100."],
    "sort_by": ["The selected sort by is invalid."]
  }
}
```

---

### 2. Get Single User

**GET** `/admin/users/{id}`

Retrieves detailed information about a specific user.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | User ID |

#### Example Request
```
GET /admin/users/123
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "User details retrieved successfully",
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "avatar": "https://example.com/avatar.jpg",
    "google_id": "1234567890",
    "email_verified_at": "2023-01-01T00:00:00.000000Z",
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  }
}
```

#### Error Responses

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Unauthorized. Admins can only view regular users, researchers, and other admins."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "User not found"
}
```

---

### 3. Edit User

**PUT** `/admin/users/{id}`

Updates user information. Only admins and superadmins can edit users.

#### Required Permissions
- admin or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | User ID |

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | No | max:255 | User's display name |
| `email` | string | No | email, max:255, unique | User's email address |
| `role` | string | No | in:user,admin,researcher,superadmin | User's role |
| `avatar` | string | No | nullable, max:255 | URL to user's avatar image |

#### Permission Constraints

**Admin users can:**
- View users with role 'user', 'researcher', and 'admin'
- Only edit users with role 'user' and 'researcher'
- Only assign roles: 'user', 'researcher'

**Superadmin users can:**
- Edit any user
- Assign any role

#### Example Request
```json
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "role": "researcher",
  "avatar": "https://example.com/new-avatar.jpg"
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "id": 123,
    "name": "John Updated",
    "email": "john.updated@example.com",
    "role": "researcher",
    "avatar": "https://example.com/new-avatar.jpg",
    "google_id": "1234567890",
    "email_verified_at": "2023-01-01T00:00:00.000000Z",
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T12:00:00.000000Z"
  }
}
```

#### Error Responses

**403 Unauthorized - Role Access**
```json
{
  "status": "error",
  "message": "Unauthorized. Only admins and superadmins can edit users."
}
```

**403 Unauthorized - Target User**
```json
{
  "status": "error",
  "message": "Unauthorized. Admins can only edit regular users and researchers."
}
```

**403 Unauthorized - Role Assignment**
```json
{
  "status": "error",
  "message": "Unauthorized. You cannot assign this role."
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "User not found"
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "role": ["The selected role is invalid."],
    "name": ["The name field must not be greater than 255 characters."]
  }
}
```

---

## Data Models

### User Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique user identifier |
| `name` | string | No | User's display name |
| `email` | string | No | User's email address |
| `role` | string | No | User role: user, admin, researcher, superadmin |
| `avatar` | string | Yes | URL to user's profile picture |
| `google_id` | string | Yes | Google OAuth ID (if user signed up via Google) |
| `email_verified_at` | string | Yes | ISO timestamp of email verification |
| `created_at` | string | Yes | ISO timestamp of account creation |
| `updated_at` | string | Yes | ISO timestamp of last update |

### Pagination Object

| Field | Type | Description |
|-------|------|-------------|
| `current_page` | integer | Current page number |
| `data` | array | Array of user objects |
| `first_page_url` | string | URL to first page |
| `from` | integer | Starting record number |
| `last_page` | integer | Last page number |
| `last_page_url` | string | URL to last page |
| `next_page_url` | string\|null | URL to next page |
| `path` | string | Base URL path |
| `per_page` | integer | Records per page |
| `prev_page_url` | string\|null | URL to previous page |
| `to` | integer | Ending record number |
| `total` | integer | Total number of records |

---

## Common Use Cases

### Search Users
```
GET /admin/users?search=john@example.com
```

### Filter by Role
```
GET /admin/users?role=admin
```

### Get Verified Users Only
```
GET /admin/users?verified=true
```

### Get OAuth Users
```
GET /admin/users?oauth=true
```

### Date Range Filter
```
GET /admin/users?created_from=2023-01-01&created_to=2023-12-31
```

### Pagination with Sorting
```
GET /admin/users?page=2&per_page=50&sort_by=name&sort_direction=asc
```

### Combined Filters
```
GET /admin/users?search=admin&role=admin&verified=true&sort_by=created_at&sort_direction=desc
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |