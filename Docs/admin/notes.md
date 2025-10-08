# Notes Management API

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
- **user** - Regular user (can manage own notes)
- **admin** - Administrator (full note management for all users)
- **researcher** - Research access (can view all notes, but cannot create/edit/delete)
- **superadmin** - Full system access (full note management for all users)

### Access Matrix

| Action | User | Admin | Researcher | Superadmin |
|--------|------|-------|------------|------------|
| View own notes | ✅ | ✅ | ✅ | ✅ |
| View all notes | ❌ | ✅ | ✅ | ✅ |
| Create own notes | ✅ | ✅ | ✅ | ✅ |
| Create notes for others | ❌ | ✅ | ❌ | ✅ |
| Edit own notes | ✅ | ✅ | ✅ | ✅ |
| Edit any notes | ❌ | ✅ | ❌ | ✅ |
| Delete own notes | ✅ | ✅ | ✅ | ✅ |
| Delete any notes | ❌ | ✅ | ❌ | ✅ |

### Routing Structure

**User Endpoints**: Use ID-based URLs for personal note management
- `/api/notes/{id}` - Numeric IDs for user operations

**Admin Endpoints**: Use ID-based URLs for administrative operations  
- `/api/admin/notes/{id}` - Numeric IDs for admin operations

---

## User Endpoints

### 1. Get Notes List (User)

**GET** `/notes`

Retrieves a paginated list of notes belonging to the authenticated user with filtering and search capabilities.

#### Required Permissions
- Authenticated user (any role)

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in title and content (excludes private notes from other users) |
| `tag` | string | No | - | Filter by specific tag |
| `is_private` | boolean | No | - | Filter by privacy (true/false) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /notes?search=meeting&tag=work&is_private=false&page=1&per_page=10
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Notes retrieved successfully",
  "data": {
    "notes": [
      {
        "id": 1,
        "title": "Updated Test Note",
        "content": "This is an updated test note",
        "is_private": false,
        "tags": ["work", "important"],
        "tags_list": "work, important",
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg"
        },
        "created_at": "2025-07-27T02:09:05.000000Z",
        "updated_at": "2025-07-27T02:09:13.000000Z"
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
      "first": "http://localhost:8000/api/notes?page=1",
      "last": "http://localhost:8000/api/notes?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 2. Get My Notes (User)

**GET** `/notes/my-notes`

Retrieves a paginated list of notes that belong exclusively to the authenticated user with filtering and search capabilities.

#### Required Permissions
- Authenticated user (any role)

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in title and content |
| `tag` | string | No | - | Filter by specific tag |
| `is_private` | boolean | No | - | Filter by privacy (true/false) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /notes/my-notes?search=meeting&tag=work&page=1&per_page=10
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "My notes retrieved successfully",
  "data": {
    "notes": [
      {
        "id": 1,
        "title": "My Personal Note",
        "content": "This is my personal note content",
        "is_private": true,
        "tags": ["personal", "important"],
        "tags_list": "personal, important",
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg"
        },
        "created_at": "2025-07-27T02:09:05.000000Z",
        "updated_at": "2025-07-27T02:09:13.000000Z"
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
      "first": "http://localhost:8000/api/notes/my-notes?page=1",
      "last": "http://localhost:8000/api/notes/my-notes?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

#### Key Differences from `/notes` Endpoint
- **`/notes`**: Returns notes owned by the user AND public notes from other users
- **`/notes/my-notes`**: Returns ONLY notes owned by the authenticated user (both private and public)

This endpoint is useful when users want to view exclusively their own notes, regardless of privacy settings.

### 3. Get Single Note (User)

**GET** `/notes/{id}`

Retrieves detailed information about a specific note owned by the authenticated user.

#### Required Permissions
- Authenticated user (can only view own notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID (numeric identifier) |

#### Example Request
```
GET /notes/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "note": {
      "id": 1,
      "title": "Updated Test Note",
      "content": "This is an updated test note",
      "is_private": false,
      "tags": ["work", "important"],
      "tags_list": "work, important",
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com"
      },
      "created_at": "2025-07-27T02:09:05.000000Z",
      "updated_at": "2025-07-27T02:09:13.000000Z"
    }
  }
}
```

### 4. Create Note (User)

**POST** `/notes`

Creates a new note for the authenticated user.

#### Required Permissions
- Authenticated user (any role)

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:255 | Note title |
| `content` | string | Yes | max:65535 | Note content/body |
| `is_private` | boolean | No | - | Privacy setting (default: false) |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |

#### Example Request
```json
{
  "title": "Meeting Notes",
  "content": "Important points from today's team meeting...",
  "is_private": false,
  "tags": ["meeting", "work", "important"]
}
```

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Note created successfully",
  "data": {
    "note": {
      "id": 2,
      "title": "Meeting Notes",
      "content": "Important points from today's team meeting...",
      "is_private": false,
      "tags": ["meeting", "work", "important"],
      "tags_list": "meeting, work, important",
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com"
      },
      "created_at": "2025-07-27T02:15:00.000000Z",
      "updated_at": "2025-07-27T02:15:00.000000Z"
    }
  }
}
```

### 5. Update Note (User)

**PUT** `/notes/{id}`

Updates an existing note owned by the authenticated user.

#### Required Permissions
- Authenticated user (can only update own notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID to update |

#### Request Body
Same as Create Note endpoint (all fields optional for updates).

#### Example Request
```json
{
  "title": "Updated Meeting Notes",
  "content": "Updated content with additional points...",
  "tags": ["meeting", "work", "important", "updated"]
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note updated successfully",
  "data": {
    "note": {
      "id": 2,
      "title": "Updated Meeting Notes",
      "content": "Updated content with additional points...",
      "is_private": false,
      "tags": ["meeting", "work", "important", "updated"],
      "tags_list": "meeting, work, important, updated",
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com"
      },
      "created_at": "2025-07-27T02:15:00.000000Z",
      "updated_at": "2025-07-27T02:20:00.000000Z"
    }
  }
}
```

### 6. Delete Note (User)

**DELETE** `/notes/{id}`

Permanently deletes a note owned by the authenticated user.

#### Required Permissions
- Authenticated user (can only delete own notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID to delete |

#### Example Request
```
DELETE /notes/2
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note deleted successfully",
  "data": []
}
```

#### Error Responses

**401 Unauthenticated**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

**403 Forbidden**
```json
{
  "status": "error",
  "message": "You can only view your own notes",
  "data": null
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Endpoint not found",
  "data": null
}
```

---

## Admin Endpoints

### 7. Get Notes List (Admin)

**GET** `/admin/notes`

Retrieves a paginated list of all notes in the system with admin-specific filtering options.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in title and content |
| `tag` | string | No | - | Filter by specific tag |
| `user_id` | integer | No | - | Filter by note owner user ID (admin only) |
| `is_private` | boolean | No | - | Filter by privacy (true/false) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /admin/notes?search=meeting&user_id=2&page=1&per_page=15
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Notes retrieved successfully",
  "data": {
    "notes": [
      {
        "id": 1,
        "title": "Updated Test Note",
        "content": "This is an updated test note",
        "is_private": false,
        "tags": ["work", "important"],
        "tags_list": "work, important",
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg"
        },
        "created_at": "2025-07-27T02:09:05.000000Z",
        "updated_at": "2025-07-27T02:09:13.000000Z"
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
      "first": "http://localhost:8000/api/admin/notes?page=1",
      "last": "http://localhost:8000/api/admin/notes?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 8. Get Single Note (Admin)

**GET** `/admin/notes/{id}`

Retrieves detailed information about any note in the system using its ID.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID (numeric identifier) |

#### Example Request
```
GET /admin/notes/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "note": {
      "id": 1,
      "title": "Updated Test Note",
      "content": "This is an updated test note",
      "is_private": false,
      "tags": ["work", "important"],
      "tags_list": "work, important",
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com"
      },
      "created_at": "2025-07-27T02:09:05.000000Z",
      "updated_at": "2025-07-27T02:09:13.000000Z"
    }
  }
}
```

### 9. Create Note (Admin)

**POST** `/admin/notes`

Creates a new note for any user in the system.

#### Required Permissions
- admin or superadmin (researchers cannot create notes)

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:255 | Note title |
| `content` | string | Yes | max:65535 | Note content/body |
| `user_id` | integer | Yes | exists:users,id | ID of user who will own the note |
| `is_private` | boolean | No | - | Privacy setting (default: false) |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |

#### Example Request
```json
{
  "title": "Admin Created Note",
  "content": "This note was created by an administrator for the user.",
  "user_id": 3,
  "is_private": true,
  "tags": ["admin", "system", "important"]
}
```

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Note created successfully",
  "data": {
    "note": {
      "id": 3,
      "title": "Admin Created Note",
      "content": "This note was created by an administrator for the user.",
      "is_private": true,
      "tags": ["admin", "system", "important"],
      "tags_list": "admin, system, important",
      "user": {
        "id": 3,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://example.com/avatars/user-3.jpg"
      },
      "created_at": "2025-07-27T02:25:00.000000Z",
      "updated_at": "2025-07-27T02:25:00.000000Z"
    }
  }
}
```

### 10. Update Note (Admin)

**PUT** `/admin/notes/{id}`

Updates any note in the system.

#### Required Permissions
- admin or superadmin (researchers cannot edit notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID to update |

#### Request Body
Same as Admin Create Note endpoint (all fields optional for updates except user_id).

#### Example Request
```json
{
  "title": "Updated Admin Note",
  "content": "This note has been updated by an administrator.",
  "tags": ["admin", "system", "updated"]
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note updated successfully",
  "data": {
    "note": {
      "id": 3,
      "title": "Updated Admin Note",
      "content": "This note has been updated by an administrator.",
      "is_private": true,
      "tags": ["admin", "system", "updated"],
      "tags_list": "admin, system, updated",
      "user": {
        "id": 3,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://example.com/avatars/user-3.jpg"
      },
      "created_at": "2025-07-27T02:25:00.000000Z",
      "updated_at": "2025-07-27T02:30:00.000000Z"
    }
  }
}
```

### 11. Delete Note (Admin)

**DELETE** `/admin/notes/{id}`

Permanently deletes any note in the system.

#### Required Permissions
- admin or superadmin (researchers cannot delete notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID to delete |

#### Example Request
```
DELETE /admin/notes/3
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Note deleted successfully",
  "data": []
}
```

#### Error Responses (All Endpoints)

**401 Unauthenticated**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Access denied",
  "data": null
}
```

**403 Researcher Restriction**
```json
{
  "status": "error",
  "message": "Researchers cannot create notes",
  "data": null
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Endpoint not found",
  "data": null
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "title": ["Note title is required"],
    "content": ["Note content is required"],
    "user_id": ["The specified user does not exist"]
  }
}
```

---

## Data Models

### Note Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique note identifier |
| `title` | string | No | Note title (max: 255 characters) |
| `content` | string | No | Note content/body (max: 65535 characters) |
| `is_private` | boolean | No | Privacy setting (true = private, false = public) |
| `tags` | array | Yes | Array of tags associated with the note |
| `tags_list` | string | No | Comma-separated string of tags (computed attribute) |
| `user` | object | No | User who owns the note |
| `created_at` | string | No | ISO timestamp of creation |
| `updated_at` | string | No | ISO timestamp of last update |

### User Object (Note Owner)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | User ID |
| `name` | string | No | User name |
| `email` | string | No | User email address |
| `avatar` | string | Yes | User avatar URL |

### Pagination Meta Object

| Field | Type | Description |
|-------|------|-------------|
| `current_page` | integer | Current page number |
| `from` | integer | Starting record number |
| `last_page` | integer | Last page number |
| `per_page` | integer | Records per page |
| `to` | integer | Ending record number |
| `total` | integer | Total number of records |

### Pagination Links Object

| Field | Type | Description |
|-------|------|-------------|
| `first` | string | URL to first page |
| `last` | string | URL to last page |
| `prev` | string\|null | URL to previous page |
| `next` | string\|null | URL to next page |

---

## Common Use Cases

### Get Only My Notes
```
GET /notes/my-notes
GET /notes/my-notes?is_private=true
```

### Search Notes
```
GET /notes?search=meeting
GET /admin/notes?search=meeting
```

### Filter by Tag
```
GET /notes?tag=work
GET /admin/notes?tag=work
```

### Filter by Privacy
```
GET /notes?is_private=true
GET /admin/notes?is_private=false
```

### Filter by User (Admin Only)
```
GET /admin/notes?user_id=2
```

### Combined Filters
```
GET /notes?search=project&tag=work&is_private=false
GET /admin/notes?search=important&user_id=2&tag=system&page=2&per_page=25
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions or ownership) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |

---

## Notes

### Privacy System
- Notes can be marked as private (visible only to owner) or public
- Default privacy setting is `false` (public)
- Admins and superadmins can view all notes regardless of privacy setting
- Regular users can only view their own notes

### Tagging System
- Notes support multiple tags for categorization
- Maximum 10 tags per note
- Each tag can be up to 50 characters
- Tags are stored as JSON array in database
- `tags_list` provides comma-separated string representation

### Search Functionality
- Searches across title and content fields
- Case-insensitive search
- Partial word matching supported
- **Important**: When using the search parameter on `/notes` endpoint, only public notes are returned (private notes from other users are excluded for privacy)

### Ownership & Security
- Users can only view, edit, and delete their own notes
- Admin endpoints allow management of any user's notes
- Researchers have read-only access (cannot create, edit, or delete)
- All endpoints require authentication
- Role-based access control enforced

### Content Limits
- Title: Maximum 255 characters
- Content: Maximum 65,535 characters (TEXT field)
- Tags: Maximum 10 tags, each up to 50 characters