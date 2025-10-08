# Folder Management API

The Folder API provides a comprehensive system for organizing and managing hierarchical folder structures with support for polymorphic item management, permission controls, and advanced querying capabilities.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All folder endpoints require authentication using Bearer tokens:
```
Authorization: Bearer {your_token}
```

**Note**: All folder operations also require email verification (`verified` middleware).

## Content Type
All requests should include:
```
Content-Type: application/json
Accept: application/json
```

## API Endpoints Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/folders` | List/search folders with filtering |
| GET | `/folders/mine` | Get current user's folders only |
| POST | `/folders` | Create a new folder |
| GET | `/folders/{slug}` | Get folder details with paginated items |
| PUT | `/folders/{slug}` | Update folder properties |
| DELETE | `/folders/{slug}` | Delete folder (with cascade) |
| GET | `/folders/{slug}/children` | Get child folders |
| POST | `/folders/{slug}/items` | Add item to folder |
| DELETE | `/folders/{slug}/items` | Remove item from folder |

---

## 1. List Folders

### `GET /folders`

Retrieve folders with optional filtering, searching, and pagination.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search folders by name or description |
| `is_public` | boolean | Filter by public/private status |
| `parent_id` | integer | Filter by parent folder ID (use 'null' for root folders) |
| `per_page` | integer | Number of folders per page (default: 15) |
| `page` | integer | Page number (default: 1) |

#### Example Request
```bash
curl -X GET \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders?search=Documentation&per_page=10"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Folders retrieved successfully",
  "data": {
    "folders": [
      {
        "id": 10,
        "name": "Updated Documentation Folder",
        "slug": "documentation-example-folder",
        "description": "Updated description with more details",
        "is_public": true,
        "sort_order": 1,
        "is_root": true,
        "has_children": true,
        "created_at": "2025-08-29T15:54:21.000000Z",
        "updated_at": "2025-08-29T15:55:50.000000Z",
        "views_count": 5,
        "is_bookmarked": false,
        "bookmarks_count": 2,
        "user": {
          "id": 93,
          "name": "Test User Alpha"
        },
        "children": []
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 1,
      "total": 4
    },
    "links": {
      "first": "http://127.0.0.1:8000/api/folders?page=1",
      "last": "http://127.0.0.1:8000/api/folders?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

## 2. My Folders

### `GET /folders/mine`

Get folders belonging to the current authenticated user only. Supports all the same filtering and pagination options as the main folders list.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search user's folders by name or description |
| `is_public` | boolean | Filter by public/private status |
| `parent_id` | integer | Filter by parent folder ID (use 'null' for root folders) |
| `per_page` | integer | Number of folders per page (default: 15) |
| `page` | integer | Page number (default: 1) |

#### Example Request
```bash
curl -X GET \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders/mine?per_page=5"
```

#### Example Response
```json
{
  "status": "success",
  "message": "My folders retrieved successfully",
  "data": {
    "folders": [
      {
        "id": 13,
        "name": "My Documents",
        "slug": "my-documents",
        "description": "Test folder for documentation",
        "is_public": false,
        "sort_order": 1,
        "is_root": true,
        "has_children": false,
        "created_at": "2025-09-01T15:57:12.000000Z",
        "updated_at": "2025-09-01T15:57:12.000000Z",
        "views_count": 3,
        "is_bookmarked": true,
        "bookmarks_count": 1,
        "user": {
          "id": 99,
          "name": "Test User Folders"
        },
        "children": []
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 5,
      "to": 1,
      "total": 2
    },
    "links": {
      "first": "http://127.0.0.1:8000/api/folders/mine?page=1",
      "last": "http://127.0.0.1:8000/api/folders/mine?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

## 3. Create Folder

### `POST /folders`

Create a new folder with optional parent relationship.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Folder name (max 255 characters) |
| `description` | string | No | Folder description (max 1000 characters) |
| `parent_id` | integer | No | Parent folder ID for hierarchical structure |
| `is_public` | boolean | No | Public visibility (default: false) |
| `sort_order` | integer | No | Sort order within parent (default: 0) |

#### Example Request
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Documentation Example Folder",
    "description": "Example folder for API documentation",
    "is_public": false,
    "sort_order": 1
  }' \
  "http://127.0.0.1:8000/api/folders"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Folder created successfully",
  "data": {
    "id": 10,
    "name": "Documentation Example Folder",
    "slug": "documentation-example-folder",
    "description": "Example folder for API documentation",
    "is_public": false,
    "sort_order": 1,
    "is_root": true,
    "has_children": false,
    "created_at": "2025-08-29T15:54:21.000000Z",
    "updated_at": "2025-08-29T15:54:21.000000Z",
    "views_count": 0,
    "is_bookmarked": false,
    "bookmarks_count": 0
  }
}
```

#### Validation Errors

**Empty Name:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "name": ["Folder name is required."]
  }
}
```

**Name Too Long:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "name": ["Folder name cannot exceed 255 characters."]
  }
}
```

**Invalid Parent:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "parent_id": ["Parent folder does not exist."]
  }
}
```

---

## 4. Get Folder Details

### `GET /folders/{slug}`

Retrieve detailed information about a specific folder including children and paginated items with filtering and sorting options.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Number of items per page (default: 15, max: 50) |
| `page` | integer | Page number (default: 1) |
| `item_type` | string | Filter items by type (comma-separated): `case`, `note`, `statute`, `statute_provision`, `statute_division` |
| `sort_by` | string | Sort items by: `created_at`, `updated_at`, `type` |
| `sort_order` | string | Sort direction: `asc`, `desc` (default: `desc`) |

#### Example Request - Basic
```bash
curl -X GET \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders/my-documents"
```

#### Example Request - With Filtering & Pagination
```bash
curl -X GET \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders/my-documents?item_type=case&per_page=5&sort_by=created_at&sort_order=desc"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Folder retrieved successfully",
  "data": {
    "folder": {
      "id": 13,
      "name": "My Documents",
      "slug": "my-documents",
      "description": "Test folder for documentation",
      "is_public": false,
      "sort_order": 1,
      "is_root": true,
      "has_children": false,
      "created_at": "2025-09-01T15:57:12.000000Z",
      "updated_at": "2025-09-01T15:57:12.000000Z",
      "user": {
        "id": 99,
        "name": "Test User Folders"
      },
      "parent": null,
      "children": [],
      "items_count": {},
      "ancestors": [],
      "views_count": 0,
      "is_bookmarked": false,
      "bookmarks_count": 0,
      "items": {
        "data": [
          {
            "id": 8,
            "folder_id": 13,
            "folderable_type": "App\\Models\\Note",
            "folderable_id": 16,
            "created_at": "2025-09-01T15:58:03.000000Z",
            "updated_at": "2025-09-01T15:58:03.000000Z",
            "folderable": {
              "id": 16,
              "title": "Research Notes on Contract Formation",
              "type": "Note",
              "content": "Key points about offer, acceptance, and consideration in contract law.",
              "user_id": 95,
              "is_private": false,
              "tags": ["contract", "formation", "research"],
              "created_at": "2025-09-01T15:08:14.000000Z",
              "updated_at": "2025-09-01T15:08:14.000000Z"
            }
          },
          {
            "id": 7,
            "folder_id": 13,
            "folderable_type": "App\\Models\\CourtCase",
            "folderable_id": 7189,
            "created_at": "2025-09-01T15:57:49.000000Z",
            "updated_at": "2025-09-01T15:57:49.000000Z",
            "folderable": {
              "id": 7189,
              "title": "Criminal Procedure Analysis",
              "type": "CourtCase",
              "body": "Analysis of search and seizure procedures under the Fourth Amendment.",
              "topic": "Criminal Law",
              "level": "Advanced",
              "slug": "criminal-procedure-analysis",
              "court": "Court of Appeals",
              "date": "2023-02-20T00:00:00.000000Z",
              "country": "United States",
              "citation": "State v. Defendant, 789 A.2d 123 (2023)",
              "created_by": 96,
              "created_at": "2025-09-01T15:08:14.000000Z",
              "updated_at": "2025-09-01T15:08:14.000000Z"
            }
          }
        ],
        "meta": {
          "current_page": 1,
          "from": 1,
          "last_page": 1,
          "per_page": 15,
          "to": 2,
          "total": 3
        },
        "links": {
          "first": "http://127.0.0.1:8000/api/folders/my-documents?page=1",
          "last": "http://127.0.0.1:8000/api/folders/my-documents?page=1",
          "prev": null,
          "next": null
        }
      }
    }
  }
}
```

---

## 4. Update Folder

### `PUT /folders/{slug}`

Update folder properties. Only folder owners can update their folders.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | No | Updated folder name |
| `description` | string | No | Updated description |
| `parent_id` | integer | No | New parent folder ID |
| `is_public` | boolean | No | Updated visibility |
| `sort_order` | integer | No | Updated sort order |

#### Example Request
```bash
curl -X PUT \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Documentation Folder",
    "description": "Updated description with more details",
    "is_public": true
  }' \
  "http://127.0.0.1:8000/api/folders/documentation-example-folder"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Folder updated successfully",
  "data": {
    "id": 10,
    "name": "Updated Documentation Folder",
    "slug": "documentation-example-folder",
    "description": "Updated description with more details",
    "is_public": true,
    "sort_order": 1,
    "is_root": true,
    "has_children": true,
    "created_at": "2025-08-29T15:54:21.000000Z",
    "updated_at": "2025-08-29T15:55:50.000000Z"
  }
}
```

---

## 5. Delete Folder

### `DELETE /folders/{slug}`

Delete a folder and all its children (cascade deletion). Only folder owners can delete their folders.

#### Example Request
```bash
curl -X DELETE \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders/documentation-example-folder"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Folder deleted successfully",
  "data": null
}
```

---

## 6. Get Child Folders

### `GET /folders/{slug}/children`

Retrieve direct child folders of a parent folder.

#### Example Request
```bash
curl -X GET \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/folders/documentation-example-folder/children"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Child folders retrieved successfully",
  "data": [
    {
      "id": 11,
      "name": "API Examples Subfolder",
      "slug": "api-examples-subfolder",
      "description": "Contains API usage examples",
      "is_public": true,
      "sort_order": 1,
      "is_root": false,
      "has_children": false,
      "created_at": "2025-08-29T15:55:11.000000Z",
      "updated_at": "2025-08-29T15:55:11.000000Z",
      "user": {
        "id": 93,
        "name": "Test User Alpha"
      }
    }
  ]
}
```

---

## 7. Add Item to Folder

### `POST /folders/{slug}/items`

Add various types of items to a folder using polymorphic relationships.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `item_type` | string | Yes | Type of item: `case`, `note`, `statute`, `statute_provision`, `statute_division` |
| `item_id` | integer | Yes | ID of the item to add |

#### Example Request
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "item_type": "note",
    "item_id": 15
  }' \
  "http://127.0.0.1:8000/api/folders/documentation-example-folder/items"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Item added to folder successfully",
  "data": null
}
```

#### Duplicate Prevention
```json
{
  "status": "error",
  "message": "Item already in folder",
  "data": null,
  "errors": 422
}
```

---

## 8. Remove Item from Folder

### `DELETE /folders/{slug}/items`

Remove an item from a folder.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `item_type` | string | Yes | Type of item to remove |
| `item_id` | integer | Yes | ID of the item to remove |

#### Example Request
```bash
curl -X DELETE \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "item_type": "note",
    "item_id": 15
  }' \
  "http://127.0.0.1:8000/api/folders/documentation-example-folder/items"
```

#### Example Response
```json
{
  "status": "success",
  "message": "Item removed from folder successfully",
  "data": null
}
```

---

## Permission Model

### Folder Access Rules

1. **Private Folders**: Only accessible by the folder owner
2. **Public Folders**: Accessible by all authenticated users (read-only for non-owners)
3. **Modifications**: Only folder owners can create, update, delete, or manage items in their folders

### Permission Error Response
```json
{
  "status": "error",
  "message": "Folder not found",
  "data": null,
  "errors": 404
}
```

---

## Authentication Errors

### Missing Authentication
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

### Invalid Token
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

---

## View Tracking

Folders now support view tracking just like other models (cases, notes, statutes). Views are automatically tracked when accessing folder details.

### How View Tracking Works

- **Automatic Tracking**: Views are tracked automatically when accessing `GET /folders/{slug}`
- **Cooldown Period**: Prevents multiple views from the same user within a time window
- **User-Specific**: Each user's views are tracked separately
- **Guest Limits**: Guest users have view limits to prevent abuse

### View Statistics

Folders with the view tracking feature can provide:

- **Total Views**: `folder.viewsCount()`
- **Views Today**: `folder.getViewsToday()`
- **Views This Week**: `folder.getViewsThisWeek()`
- **Views This Month**: `folder.getViewsThisMonth()`
- **Unique Viewers**: `folder.getUniqueViewersCount()`

### Privacy

- View tracking respects folder privacy settings
- Only authenticated users can trigger view tracking
- Private folders only track views from the owner or users with access
- Public folders track views from all authenticated users

---

## Folder Bookmarking

Folders support the same bookmarking functionality as other content types (cases, notes, statutes). Users can bookmark folders for quick access and organization.

### Bookmark Status Fields

Folder responses include bookmark status information:

- `is_bookmarked`: Boolean indicating if the current authenticated user has bookmarked this folder
- `bookmarks_count`: Total number of users who have bookmarked this folder

### Example Folder Response with Bookmark Info

```json
{
  "status": "success",
  "message": "Folder retrieved successfully",
  "data": {
    "folder": {
      "id": 2,
      "name": "Test Folder",
      "slug": "test-folder",
      "description": "A test folder created for API testing",
      "is_public": true,
      "sort_order": 0,
      "is_root": true,
      "has_children": false,
      "created_at": "2025-10-08T11:45:23.000000Z",
      "updated_at": "2025-10-08T11:45:23.000000Z",
      "user": {
        "id": 339,
        "name": "Test User"
      },
      "views_count": 2,
      "is_bookmarked": true,
      "bookmarks_count": 1,
      // ... other folder fields
    }
  }
}
```

### Bookmark Management

Folder bookmarking uses the same Bookmarks API as other content types:

**Bookmark a Folder:**
```bash
curl -X POST "http://127.0.0.1:8000/api/bookmarks" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "bookmarkable_type=App%5CModels%5CFolder&bookmarkable_id=2"
```

**Unbookmark a Folder:**
```bash
curl -X DELETE "http://127.0.0.1:8000/api/bookmarks/{bookmark_id}" \
  -H "Authorization: Bearer {token}"
```

**For complete bookmark management documentation**, see the [Bookmarks API Documentation](./bookmarks.md).

---

## Key Features

### 🔐 **Security & Permissions**
- User-based ownership validation
- Public/private folder access control
- Authentication required for all operations
- Email verification required

### 🌳 **Hierarchical Structure**
- Unlimited nesting depth support
- Parent-child relationships
- Cascade deletion (deleting parent removes all children)
- Circular reference prevention

### 📁 **Polymorphic Item Management**
- Support for multiple item types: `case`, `note`, `statute`, `statute_provision`, `statute_division`
- Duplicate prevention
- Item relationship tracking with timestamps

### 🔍 **Advanced Querying**
- Full-text search by name and description
- Filtering by public/private status
- Parent-based filtering
- Pagination support
- Custom sorting options
- **NEW**: "My Folders" endpoint for user-specific folder lists
- **NEW**: Enhanced folder details with item pagination and filtering

### 🏷️ **Slug-Based Routing**
- SEO-friendly URLs using auto-generated slugs
- Unique slug generation with conflict resolution
- Slug-based resource identification

### 📊 **Rich Metadata**
- Creation and modification timestamps
- User ownership tracking
- Children count and relationship data
- Item count and detailed listings
- **NEW**: View tracking and analytics support

### 📈 **View Tracking & Analytics**
- Automatic view tracking for folder access
- Cooldown protection against spam views
- User-specific view statistics
- Privacy-respecting tracking (respects folder permissions)

### 🔖 **Bookmarking Support**
- Users can bookmark folders for quick access
- Real-time bookmark status in folder responses
- Integrated with the main Bookmarks API
- Folder responses include `is_bookmarked` and `bookmarks_count` fields

### 🎯 **API Consistency**
- **IMPROVED**: Folder details endpoint now follows the same response structure as other detail endpoints
- **IMPROVED**: Items pagination uses proper `meta` and `links` structure
- **CONSISTENT**: All folder responses follow the standard `{ "data": { "resource": {...} } }` pattern
- **RELIABLE**: Predictable response structures across all endpoints

---

## Error Handling

The API returns consistent error responses with appropriate HTTP status codes:

- **400**: Bad Request (validation errors)
- **401**: Unauthorized (authentication required)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found (resource doesn't exist)
- **422**: Unprocessable Entity (business logic violations)

All error responses follow the format:
```json
{
  "status": "error",
  "message": "Error description",
  "data": null,
  "errors": "Detailed error information"
}
```