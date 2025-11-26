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

## Publication System

Notes support two publication states: **draft** and **published**.

### Status Behavior

| Status | Owner Access | Admin Access | Public Access | Privacy Controlled By |
|--------|-------------|--------------|---------------|----------------------|
| `draft` | ✅ Always | ✅ Always | ❌ Never | Status only (ignores `is_private`) |
| `published` | ✅ Always | ✅ Always | ✅ If `is_private=false` | Both `status` and `is_private` |

### Key Rules

1. **Draft Notes**:
   - Only visible to the note owner and admins/researchers
   - Privacy setting (`is_private`) is ignored for drafts
   - Useful for preparing content before public release

2. **Published Notes**:
   - Visibility controlled by `is_private` field
   - If `is_private=false`: Visible to everyone
   - If `is_private=true`: Only visible to owner and admins

3. **Default Behavior**:
   - New notes default to `status="draft"`
   - Prevents accidental publication of incomplete notes

### Common Use Cases

**Create a draft note**:
```json
{
  "title": "Work in Progress",
  "content": "Still editing...",
  "status": "draft"
}
```

**Publish a note publicly**:
```json
{
  "title": "Complete Guide",
  "content": "Finished content...",
  "status": "published",
  "is_private": false
}
```

**Publish a note privately**:
```json
{
  "title": "Personal Notes",
  "content": "Only for me...",
  "status": "published",
  "is_private": true
}
```

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
| `status` | string | No | - | Filter by status (draft/published) |
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
        "content_preview": null,
        "status": "published",
        "is_private": false,
        "tags": ["work", "important"],
        "tags_list": "work, important",
        "price_ngn": null,
        "price_usd": null,
        "is_free": true,
        "is_paid": false,
        "has_access": true,
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg",
          "is_creator": false
        },
        "comments_count": 0,
        "views_count": 5,
        "is_bookmarked": false,
        "bookmark_id": null,
        "bookmarks_count": 0,
        "created_at": "2025-07-27T02:09:05.000000Z",
        "updated_at": "2025-07-27T02:09:13.000000Z"
      },
      {
        "id": 2,
        "title": "Premium Legal Notes",
        "content_preview": "This is a preview of the premium content...",
        "status": "published",
        "is_private": false,
        "tags": ["legal", "premium"],
        "tags_list": "legal, premium",
        "price_ngn": "500.00",
        "price_usd": "5.00",
        "is_free": false,
        "is_paid": true,
        "has_access": false,
        "user": {
          "id": 3,
          "name": "Legal Creator",
          "email": "creator@example.com",
          "avatar": "https://example.com/avatars/user-3.jpg",
          "is_creator": true
        },
        "comments_count": 2,
        "views_count": 150,
        "is_bookmarked": true,
        "bookmark_id": 5,
        "bookmarks_count": 25,
        "created_at": "2025-07-27T02:10:00.000000Z",
        "updated_at": "2025-07-27T02:10:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 2,
      "total": 2
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
| `status` | string | No | - | Filter by status (draft/published) |
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
        "content_preview": null,
        "status": "published",
        "is_private": true,
        "tags": ["personal", "important"],
        "tags_list": "personal, important",
        "price_ngn": null,
        "price_usd": null,
        "is_free": true,
        "is_paid": false,
        "has_access": true,
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg",
          "is_creator": false
        },
        "comments_count": 0,
        "views_count": 3,
        "is_bookmarked": false,
        "bookmark_id": null,
        "bookmarks_count": 0,
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

Retrieves detailed information about a specific note. For paid notes, content is restricted based on access.

#### Required Permissions
- Authenticated user (can view own notes or public notes)
- Unauthenticated users can view public notes (with content restrictions for paid notes)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Note ID (numeric identifier) |

#### Example Request
```
GET /notes/1
```

#### Success Response - Free Note (200)
```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "note": {
      "id": 1,
      "title": "Free Public Note",
      "content": "This is the full content of the free note...",
      "content_preview": null,
      "is_private": false,
      "tags": ["work", "important"],
      "tags_list": "work, important",
      "price_ngn": null,
      "price_usd": null,
      "is_free": true,
      "is_paid": false,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": false
      },
      "comments_count": 0,
      "views_count": 10,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 2,
      "comments": [],
      "created_at": "2025-07-27T02:09:05.000000Z",
      "updated_at": "2025-07-27T02:09:13.000000Z"
    }
  }
}
```

#### Success Response - Paid Note (Owner or Purchased) (200)
```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "note": {
      "id": 2,
      "title": "Premium Legal Notes",
      "content": "This is the FULL premium content visible to the owner or purchaser...",
      "content_preview": null,
      "is_private": false,
      "tags": ["legal", "premium"],
      "tags_list": "legal, premium",
      "price_ngn": "500.00",
      "price_usd": "5.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": true
      },
      "comments_count": 5,
      "views_count": 150,
      "is_bookmarked": true,
      "bookmark_id": 3,
      "bookmarks_count": 50,
      "comments": [],
      "created_at": "2025-07-27T02:10:00.000000Z",
      "updated_at": "2025-07-27T02:10:00.000000Z"
    }
  }
}
```

#### Success Response - Paid Note (No Access) (200)
```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "note": {
      "id": 2,
      "title": "Premium Legal Notes",
      "content": null,
      "content_preview": "This is the first ~200 characters of the content shown as a preview...",
      "is_private": false,
      "tags": ["legal", "premium"],
      "tags_list": "legal, premium",
      "price_ngn": "500.00",
      "price_usd": "5.00",
      "is_free": false,
      "is_paid": true,
      "has_access": false,
      "user": {
        "id": 3,
        "name": "Legal Creator",
        "email": "creator@example.com",
        "avatar": "https://example.com/avatars/user-3.jpg",
        "is_creator": true
      },
      "comments_count": 5,
      "views_count": 150,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 50,
      "comments": [],
      "created_at": "2025-07-27T02:10:00.000000Z",
      "updated_at": "2025-07-27T02:10:00.000000Z"
    }
  }
}
```

### 4. Create Note (User)

**POST** `/notes`

Creates a new note for the authenticated user. Notes can be free or paid.

#### Required Permissions
- Authenticated user (any role)

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:255 | Note title |
| `content` | string | Yes | max:10,000,000 | Note content/body |
| `status` | string | No | draft, published | Publication status (default: draft) |
| `is_private` | boolean | No | - | Privacy setting (default: false) |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |
| `price_ngn` | decimal | No | min:0, max:99999999.99 | Price in Nigerian Naira (null = free) |
| `price_usd` | decimal | No | min:0, max:99999999.99 | Price in US Dollars (null = free) |
| `videos` | array | No | - | Array of video objects to attach to the note |
| `videos.*.video_url` | string | Yes* | url, max:500 | Video URL (required if videos array is provided) |
| `videos.*.thumbnail_url` | string | No | url, max:500 | Custom thumbnail URL |
| `videos.*.platform` | string | No | youtube, dailymotion, other | Video platform (auto-detected if not provided) |
| `videos.*.sort_order` | integer | No | min:0 | Display order (auto-assigned if not provided) |

#### Example Request - Free Note (Draft)
```json
{
  "title": "Meeting Notes",
  "content": "Important points from today's team meeting...",
  "status": "draft",
  "is_private": false,
  "tags": ["meeting", "work", "important"]
}
```

#### Example Request - Free Note (Published)
```json
{
  "title": "Meeting Notes",
  "content": "Important points from today's team meeting...",
  "status": "published",
  "is_private": false,
  "tags": ["meeting", "work", "important"]
}
```

#### Example Request - Paid Note
```json
{
  "title": "Premium Contract Law Guide",
  "content": "Comprehensive guide to contract law in Nigeria...",
  "is_private": false,
  "tags": ["legal", "contract", "premium"],
  "price_ngn": 500,
  "price_usd": 5
}
```

#### Example Request - Note with Videos
```json
{
  "title": "Contract Law Video Tutorial",
  "content": "Comprehensive video-based guide to contract law...",
  "is_private": false,
  "tags": ["legal", "video", "tutorial"],
  "videos": [
    {
      "video_url": "https://www.youtube.com/watch?v=abc123",
      "thumbnail_url": "https://img.youtube.com/vi/abc123/maxresdefault.jpg"
    },
    {
      "video_url": "https://www.dailymotion.com/video/xyz789",
      "sort_order": 1
    }
  ]
}
```

#### Success Response - Free Note (201)
```json
{
  "status": "success",
  "message": "Note created successfully",
  "data": {
    "note": {
      "id": 2,
      "title": "Meeting Notes",
      "content": "Important points from today's team meeting...",
      "content_preview": null,
      "is_private": false,
      "tags": ["meeting", "work", "important"],
      "tags_list": "meeting, work, important",
      "price_ngn": null,
      "price_usd": null,
      "is_free": true,
      "is_paid": false,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": false
      },
      "comments_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0,
      "videos": [],
      "videos_count": 0,
      "created_at": "2025-07-27T02:15:00.000000Z",
      "updated_at": "2025-07-27T02:15:00.000000Z"
    }
  }
}
```

#### Success Response - Paid Note (201)
```json
{
  "status": "success",
  "message": "Note created successfully",
  "data": {
    "note": {
      "id": 3,
      "title": "Premium Contract Law Guide",
      "content": "Comprehensive guide to contract law in Nigeria...",
      "content_preview": null,
      "is_private": false,
      "tags": ["legal", "contract", "premium"],
      "tags_list": "legal, contract, premium",
      "price_ngn": "500.00",
      "price_usd": "5.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": true
      },
      "comments_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0,
      "videos": [],
      "videos_count": 0,
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

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | No | max:255 | Note title |
| `content` | string | No | max:10,000,000 | Note content/body |
| `is_private` | boolean | No | - | Privacy setting |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |
| `price_ngn` | decimal | No | min:0, max:99999999.99 | Price in Nigerian Naira |
| `price_usd` | decimal | No | min:0, max:99999999.99 | Price in US Dollars |
| `videos` | array | No | - | Array of video objects (replaces all existing videos) |
| `videos.*.video_url` | string | Yes* | url, max:500 | Video URL (required if videos array is provided) |
| `videos.*.thumbnail_url` | string | No | url, max:500 | Custom thumbnail URL |
| `videos.*.platform` | string | No | youtube, dailymotion, other | Video platform (auto-detected if not provided) |
| `videos.*.sort_order` | integer | No | min:0 | Display order (auto-assigned if not provided) |

#### Example Request - Update Pricing
```json
{
  "title": "Updated Meeting Notes",
  "price_ngn": 1000,
  "price_usd": 10
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
      "content": "Important points from today's team meeting...",
      "content_preview": null,
      "is_private": false,
      "tags": ["meeting", "work", "important"],
      "tags_list": "meeting, work, important",
      "price_ngn": "1000.00",
      "price_usd": "10.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": false
      },
      "comments_count": 0,
      "views_count": 5,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0,
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
| `status` | string | No | - | Filter by status (draft/published) |
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
        "content_preview": null,
        "status": "published",
        "is_private": false,
        "tags": ["work", "important"],
        "tags_list": "work, important",
        "price_ngn": null,
        "price_usd": null,
        "is_free": true,
        "is_paid": false,
        "has_access": true,
        "user": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com",
          "avatar": "https://example.com/avatars/user-2.jpg",
          "is_creator": false
        },
        "comments_count": 0,
        "views_count": 10,
        "is_bookmarked": false,
        "bookmark_id": null,
        "bookmarks_count": 0,
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
      "content_preview": null,
      "is_private": false,
      "tags": ["work", "important"],
      "tags_list": "work, important",
      "price_ngn": "500.00",
      "price_usd": "5.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com",
        "avatar": "https://example.com/avatars/user-2.jpg",
        "is_creator": true
      },
      "comments_count": 0,
      "views_count": 15,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 3,
      "comments": [],
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
| `content` | string | Yes | max:10,000,000 | Note content/body |
| `user_id` | integer | Yes | exists:users,id | ID of user who will own the note |
| `is_private` | boolean | No | - | Privacy setting (default: false) |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |
| `price_ngn` | decimal | No | min:0, max:99999999.99 | Price in Nigerian Naira |
| `price_usd` | decimal | No | min:0, max:99999999.99 | Price in US Dollars |

#### Example Request
```json
{
  "title": "Admin Created Paid Note",
  "content": "This note was created by an administrator for the user with premium content.",
  "user_id": 3,
  "is_private": false,
  "tags": ["admin", "system", "premium"],
  "price_ngn": 2000,
  "price_usd": 20
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
      "title": "Admin Created Paid Note",
      "content": "This note was created by an administrator for the user with premium content.",
      "content_preview": null,
      "is_private": false,
      "tags": ["admin", "system", "premium"],
      "tags_list": "admin, system, premium",
      "price_ngn": "2000.00",
      "price_usd": "20.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 3,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://example.com/avatars/user-3.jpg",
        "is_creator": true
      },
      "comments_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0,
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

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | No | max:255 | Note title |
| `content` | string | No | max:10,000,000 | Note content/body |
| `user_id` | integer | No | exists:users,id | Change note owner |
| `is_private` | boolean | No | - | Privacy setting |
| `tags` | array | No | max:10 items, each max:50 chars | Array of tags |
| `price_ngn` | decimal | No | min:0, max:99999999.99 | Price in Nigerian Naira |
| `price_usd` | decimal | No | min:0, max:99999999.99 | Price in US Dollars |

#### Example Request
```json
{
  "title": "Updated Admin Note",
  "content": "This note has been updated by an administrator.",
  "price_ngn": 1500,
  "price_usd": 15,
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
      "content_preview": null,
      "is_private": false,
      "tags": ["admin", "system", "updated"],
      "tags_list": "admin, system, updated",
      "price_ngn": "1500.00",
      "price_usd": "15.00",
      "is_free": false,
      "is_paid": true,
      "has_access": true,
      "user": {
        "id": 3,
        "name": "John Doe",
        "email": "john@example.com",
        "avatar": "https://example.com/avatars/user-3.jpg",
        "is_creator": true
      },
      "comments_count": 0,
      "views_count": 5,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 1,
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
    "user_id": ["The specified user does not exist"],
    "price_ngn": ["Price in Naira must be a number"],
    "price_usd": ["Price in USD cannot be negative"]
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
| `content` | string | Yes | Note content/body (max: 10,000,000 characters). `null` for paid notes without access |
| `content_preview` | string | Yes | First ~200 characters of content (shown for paid notes without access) |
| `is_private` | boolean | No | Privacy setting (true = private, false = public) |
| `tags` | array | Yes | Array of tags associated with the note |
| `tags_list` | string | No | Comma-separated string of tags (computed attribute) |
| `price_ngn` | decimal | Yes | Price in Nigerian Naira (null = free) |
| `price_usd` | decimal | Yes | Price in US Dollars (null = free) |
| `is_free` | boolean | No | Whether the note is free (computed: true if both prices are null/0) |
| `is_paid` | boolean | No | Whether the note is paid (computed: opposite of is_free) |
| `has_access` | boolean | No | Whether current user has access to full content |
| `user` | object | No | User who owns the note |
| `comments_count` | integer | No | Number of comments on the note |
| `views_count` | integer | No | Number of views the note has received |
| `is_bookmarked` | boolean | No | Whether current user has bookmarked the note |
| `bookmark_id` | integer | Yes | Bookmark ID if bookmarked by current user |
| `bookmarks_count` | integer | No | Total number of bookmarks |
| `comments` | array | Yes | Array of comments (only in single note response) |
| `videos` | array | Yes | Array of video objects attached to the note (only in single note response) |
| `videos_count` | integer | No | Number of videos attached to the note |
| `created_at` | string | No | ISO timestamp of creation |
| `updated_at` | string | No | ISO timestamp of last update |

### NoteVideo Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique video identifier |
| `video_url` | string | No | URL to the video (YouTube, Dailymotion, or other) |
| `thumbnail_url` | string | Yes | Custom thumbnail URL for the video |
| `platform` | string | Yes | Video platform: `youtube`, `dailymotion`, `other`, or `null` (auto-detected) |
| `sort_order` | integer | No | Display order of videos within the note |

### User Object (Note Owner)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | User ID |
| `name` | string | No | User name |
| `email` | string | No | User email address |
| `avatar` | string | Yes | User avatar URL |
| `is_creator` | boolean | No | Whether user is a content creator |

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

### Create a Free Note
```json
POST /notes
{
  "title": "Free Study Notes",
  "content": "These are free study notes available to everyone...",
  "tags": ["study", "free"]
}
```

### Create a Paid Note
```json
POST /notes
{
  "title": "Premium Legal Analysis",
  "content": "In-depth analysis of recent Supreme Court rulings...",
  "tags": ["legal", "premium", "supreme-court"],
  "price_ngn": 1000,
  "price_usd": 10
}
```

### Convert Free Note to Paid
```json
PUT /notes/5
{
  "price_ngn": 500,
  "price_usd": 5
}
```

### Convert Paid Note to Free
```json
PUT /notes/5
{
  "price_ngn": null,
  "price_usd": null
}
```

### Create Note with Videos
```json
POST /notes
{
  "title": "Video Tutorial Series",
  "content": "This note includes multiple tutorial videos...",
  "tags": ["video", "tutorial"],
  "videos": [
    {
      "video_url": "https://www.youtube.com/watch?v=lesson1"
    },
    {
      "video_url": "https://www.youtube.com/watch?v=lesson2"
    }
  ]
}
```

### Add/Update Videos on Existing Note
```json
PUT /notes/5
{
  "videos": [
    {
      "video_url": "https://www.youtube.com/watch?v=newvideo",
      "thumbnail_url": "https://example.com/custom-thumb.jpg",
      "platform": "youtube",
      "sort_order": 0
    }
  ]
}
```

### Remove All Videos from Note
```json
PUT /notes/5
{
  "videos": []
}
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
- Regular users can only view their own notes and public notes from others

### Pricing System
- Notes support dual-currency pricing: Nigerian Naira (`price_ngn`) and US Dollars (`price_usd`)
- A note is considered **free** if both prices are `null` or `0`
- A note is considered **paid** if either price is greater than `0`
- Prices are stored as decimal with 2 decimal places (e.g., "500.00")
- Currently, prices are informational - payment integration will be added later

### Content Access Control
- **Free Notes**: Full content visible to everyone
- **Paid Notes - Owner**: Full content always visible
- **Paid Notes - Others Without Purchase**:
  - `content` field is `null`
  - `content_preview` shows first ~200 characters
  - `has_access` is `false`
- **Paid Notes - Purchasers**: Full content visible (to be implemented with payment system)
- The `has_access` field indicates whether the current user can see the full content

### Content Creator System
- Users can be marked as content creators via the `is_creator` field
- Content creators can monetize their notes by setting prices
- The `is_creator` flag appears in user objects within note responses
- Admin can manage creator status through user management endpoints

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
- All endpoints require authentication (except public note viewing)
- Role-based access control enforced

### Content Limits
- Title: Maximum 255 characters
- Content: Maximum 10,000,000 characters (MEDIUMTEXT field)
- Tags: Maximum 10 tags, each up to 50 characters
- Price: Maximum 99,999,999.99 (per currency)
- Video URL: Maximum 500 characters
- Thumbnail URL: Maximum 500 characters

### Video System
- Notes can have multiple videos attached (0, 1, or many)
- Videos support YouTube, Dailymotion, and other platforms
- Platform is auto-detected from URL patterns:
  - **YouTube**: URLs containing `youtube.com`, `youtu.be`, or `youtube-nocookie.com`
  - **Dailymotion**: URLs containing `dailymotion.com` or `dai.ly`
  - **Other**: All other URLs (platform set to `null` unless manually specified)
- Users can manually override platform detection by providing the `platform` field
- Videos are ordered by `sort_order` (auto-assigned based on array order if not provided)
- When updating a note with videos, the entire videos array is replaced (delete + recreate)
- To remove all videos from a note, send `"videos": []` in the update request
- Videos are automatically deleted when the parent note is deleted (cascade)
