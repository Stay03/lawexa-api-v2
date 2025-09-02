# Bookmark Management API

The Bookmark API allows users to bookmark various content types for easy access and organization. Users can bookmark court cases, notes, statutes, statute divisions, and statute provisions, with full CRUD operations and statistical insights.

## Response Structure

Bookmark responses include complete model data using the standard resource classes for each content type. This ensures consistency with other API endpoints and provides full access to all model properties without truncation or data loss.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All bookmark endpoints require authentication using Bearer tokens:
```
Authorization: Bearer {your_token}
```

**Note**: All bookmark operations also require email verification (`verified` middleware).

## Content Type
All requests should include:
```
Content-Type: application/json
Accept: application/json
```

## API Endpoints Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/bookmarks` | List user's bookmarks with pagination and filtering |
| POST | `/bookmarks` | Create a new bookmark |
| DELETE | `/bookmarks/{id}` | Remove a bookmark |
| GET | `/bookmarks/check` | Check if an item is bookmarked |
| GET | `/bookmarks/stats` | Get user's bookmark statistics |

---

## 1. List Bookmarks

### `GET /bookmarks`

Retrieve the current user's bookmarks with optional filtering, searching, and pagination.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `bookmarkable_type` | string | Filter by content type (e.g., "App\\Models\\CourtCase") |
| `search` | string | Search within bookmarked content titles/content |
| `per_page` | integer | Number of items per page (default: 15, max: 50) |
| `page` | integer | Page number for pagination (default: 1) |

#### Example Request

```bash
curl -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
     -H "Accept: application/json" \
     "http://localhost:8000/api/bookmarks"
```

#### Response (Empty List)

```json
{
  "status": "success",
  "message": "Bookmarks retrieved successfully",
  "data": {
    "data": [],
    "meta": {
      "current_page": 1,
      "from": null,
      "last_page": 1,
      "path": "http://localhost:8000/api/bookmarks",
      "per_page": 15,
      "to": null,
      "total": 0
    },
    "links": {
      "first": "http://localhost:8000/api/bookmarks?page=1",
      "last": "http://localhost:8000/api/bookmarks?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

#### Response (With Bookmarks)

```json
{
  "status": "success",
  "message": "Bookmarks retrieved successfully",
  "data": {
    "data": [
      {
        "id": 4,
        "bookmarkable_type": "Note",
        "bookmarkable_id": 21,
        "bookmarkable": {
          "id": 21,
          "title": "Employment Rights Summary",
          "content": "Important employee rights including fair dismissal, redundancy procedures, and workplace safety.",
          "is_private": true,
          "tags": ["employment", "rights", "summary"],
          "tags_list": "employment, rights, summary",
          "comments_count": 0,
          "views_count": 0,
          "created_at": "2025-09-01T20:24:56.000000Z",
          "updated_at": "2025-09-01T20:24:56.000000Z"
        },
        "created_at": "2025-09-01T21:44:49.000000Z",
        "updated_at": "2025-09-01T21:44:49.000000Z"
      },
      {
        "id": 3,
        "bookmarkable_type": "CourtCase",
        "bookmarkable_id": 7192,
        "bookmarkable": {
          "id": 7192,
          "title": "Brown v. Green Ltd - Employment Rights",
          "body": "A significant case establishing precedent for employee rights in termination procedures.",
          "report": null,
          "course": null,
          "topic": null,
          "tag": null,
          "principles": null,
          "level": null,
          "slug": "brown-v-green-ltd-employment-rights",
          "court": "Court of Appeal",
          "date": "2023-06-20",
          "country": "Nigeria",
          "citation": "[2023] CA 045",
          "judges": "Justice Wilson, Justice Taylor",
          "judicial_precedent": null,
          "views_count": 0,
          "created_at": "2025-09-01T20:24:56.000000Z",
          "updated_at": "2025-09-01T20:24:56.000000Z"
        },
        "created_at": "2025-09-01T21:44:43.000000Z",
        "updated_at": "2025-09-01T21:44:43.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "path": "http://localhost:8000/api/bookmarks",
      "per_page": 15,
      "to": 2,
      "total": 2
    },
    "links": {
      "first": "http://localhost:8000/api/bookmarks?page=1",
      "last": "http://localhost:8000/api/bookmarks?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

## 2. Create Bookmark

### `POST /bookmarks`

Create a new bookmark for the specified content item.

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `bookmarkable_type` | string | Yes | The model class name |
| `bookmarkable_id` | integer | Yes | The ID of the item to bookmark |

#### Supported Content Types

| Model Class | Description |
|-------------|-------------|
| `App\\Models\\CourtCase` | Court cases and legal judgments |
| `App\\Models\\Note` | User notes and annotations |
| `App\\Models\\Statute` | Legal statutes and acts |
| `App\\Models\\StatuteDivision` | Sections within statutes |
| `App\\Models\\StatuteProvision` | Individual provisions |

#### Example Request (Court Case)

```bash
curl -X POST \
  -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"bookmarkable_type":"App\\\\Models\\\\CourtCase","bookmarkable_id":7191}' \
  "http://localhost:8000/api/bookmarks"
```

#### Response (Success)

```json
{
  "status": "success",
  "message": "Item bookmarked successfully",
  "data": {
    "id": 3,
    "bookmarkable_type": "CourtCase",
    "bookmarkable_id": 7192,
    "bookmarkable": {
      "id": 7192,
      "title": "Brown v. Green Ltd - Employment Rights",
      "body": "A significant case establishing precedent for employee rights in termination procedures.",
      "report": null,
      "course": null,
      "topic": null,
      "tag": null,
      "principles": null,
      "level": null,
      "slug": "brown-v-green-ltd-employment-rights",
      "court": "Court of Appeal",
      "date": "2023-06-20",
      "country": "Nigeria",
      "citation": "[2023] CA 045",
      "judges": "Justice Wilson, Justice Taylor",
      "judicial_precedent": null,
      "views_count": 0,
      "created_at": "2025-09-01T20:24:56.000000Z",
      "updated_at": "2025-09-01T20:24:56.000000Z"
    },
    "created_at": "2025-09-01T21:44:43.000000Z",
    "updated_at": "2025-09-01T21:44:43.000000Z"
  }
}
```

#### Example Request (Note)

```bash
curl -X POST \
  -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"bookmarkable_type":"App\\\\Models\\\\Note","bookmarkable_id":20}' \
  "http://localhost:8000/api/bookmarks"
```

#### Response (Note Success)

```json
{
  "status": "success",
  "message": "Item bookmarked successfully",
  "data": {
    "id": 4,
    "bookmarkable_type": "Note",
    "bookmarkable_id": 21,
    "bookmarkable": {
      "id": 21,
      "title": "Employment Rights Summary",
      "content": "Important employee rights including fair dismissal, redundancy procedures, and workplace safety.",
      "is_private": true,
      "tags": ["employment", "rights", "summary"],
      "tags_list": "employment, rights, summary",
      "comments_count": 0,
      "views_count": 0,
      "created_at": "2025-09-01T20:24:56.000000Z",
      "updated_at": "2025-09-01T20:24:56.000000Z"
    },
    "created_at": "2025-09-01T21:44:49.000000Z",
    "updated_at": "2025-09-01T21:44:49.000000Z"
  }
}
```

#### Error Responses

##### Validation Error (Invalid Model Type)

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "bookmarkable_type": ["The selected item type is not supported for bookmarking."]
  }
}
```

##### Duplicate Bookmark Error

```json
{
  "status": "error",
  "message": "Item is already bookmarked",
  "data": null
}
```

##### Item Not Found Error

```json
{
  "status": "error",
  "message": "Item not found",
  "data": null
}
```

---

## 3. Delete Bookmark

### `DELETE /bookmarks/{id}`

Remove a bookmark by its ID. Users can only delete their own bookmarks.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | The bookmark ID to delete |

#### Example Request

```bash
curl -X DELETE \
  -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
  -H "Accept: application/json" \
  "http://localhost:8000/api/bookmarks/1"
```

#### Response (Success)

```json
{
  "status": "success",
  "message": "Bookmark removed successfully",
  "data": null
}
```

#### Error Response (Unauthorized)

```json
{
  "status": "error",
  "message": "Unauthorized to remove this bookmark",
  "data": null
}
```

---

## 4. Check Bookmark Status

### `GET /bookmarks/check`

Check if a specific item is bookmarked by the current user.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bookmarkable_type` | string | Yes | The model class name |
| `bookmarkable_id` | integer | Yes | The ID of the item to check |

#### Example Request

```bash
curl -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
     -H "Accept: application/json" \
     "http://localhost:8000/api/bookmarks/check?bookmarkable_type=App\\\\Models\\\\CourtCase&bookmarkable_id=7191"
```

#### Response

```json
{
  "status": "success",
  "message": "Bookmark status checked successfully",
  "data": {
    "is_bookmarked": true
  }
}
```

---

## 5. Bookmark Statistics

### `GET /bookmarks/stats`

Get statistics about the current user's bookmarks, including total count and breakdown by content type.

#### Example Request

```bash
curl -H "Authorization: Bearer 226|tOS05YhJWRuxGp4n2v2nXmjjYrZ3w9jVEZlq57N3ef89e2b9" \
     -H "Accept: application/json" \
     "http://localhost:8000/api/bookmarks/stats"
```

#### Response

```json
{
  "status": "success",
  "message": "Bookmark statistics retrieved successfully",
  "data": {
    "total_bookmarks": 2,
    "bookmarks_by_type": {
      "CourtCase": 1,
      "Note": 1
    }
  }
}
```

---

## Error Handling

### Authentication Errors

#### Missing Token

```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

#### Email Not Verified

```json
{
  "status": "error",
  "message": "Your email address is not verified. Please check your email for a verification link.",
  "data": null
}
```

### Validation Errors

#### Missing Required Fields

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "bookmarkable_type": ["The bookmarkable type field is required."],
    "bookmarkable_id": ["The item ID is required."]
  }
}
```

---

## Content Type Examples

### CourtCase Bookmark Response

```json
{
  "id": 3,
  "bookmarkable_type": "CourtCase",
  "bookmarkable_id": 7192,
  "bookmarkable": {
    "id": 7192,
    "title": "Brown v. Green Ltd - Employment Rights",
    "body": "A significant case establishing precedent for employee rights in termination procedures.",
    "report": null,
    "course": null,
    "topic": null,
    "tag": null,
    "principles": null,
    "level": null,
    "slug": "brown-v-green-ltd-employment-rights",
    "court": "Court of Appeal",
    "date": "2023-06-20",
    "country": "Nigeria",
    "citation": "[2023] CA 045",
    "judges": "Justice Wilson, Justice Taylor",
    "judicial_precedent": null,
    "views_count": 0,
    "created_at": "2025-09-01T20:24:56.000000Z",
    "updated_at": "2025-09-01T20:24:56.000000Z"
  },
  "created_at": "2025-09-01T21:44:43.000000Z",
  "updated_at": "2025-09-01T21:44:43.000000Z"
}
```

### Note Bookmark Response

```json
{
  "id": 4,
  "bookmarkable_type": "Note",
  "bookmarkable_id": 21,
  "bookmarkable": {
    "id": 21,
    "title": "Employment Rights Summary",
    "content": "Important employee rights including fair dismissal, redundancy procedures, and workplace safety.",
    "is_private": true,
    "tags": ["employment", "rights", "summary"],
    "tags_list": "employment, rights, summary",
    "comments_count": 0,
    "views_count": 0,
    "created_at": "2025-09-01T20:24:56.000000Z",
    "updated_at": "2025-09-01T20:24:56.000000Z"
  },
  "created_at": "2025-09-01T21:44:49.000000Z",
  "updated_at": "2025-09-01T21:44:49.000000Z"
}
```

### Statute Bookmark Response

```json
{
  "bookmarkable": {
    "id": 88,
    "type": "Statute",
    "title": "Companies and Allied Matters Act",
    "short_title": "CAMA",
    "slug": "companies-and-allied-matters-act",
    "year_enacted": 2020,
    "jurisdiction": "Federal"
  }
}
```

### StatuteDivision Bookmark Response

```json
{
  "bookmarkable": {
    "id": 1,
    "type": "StatuteDivision",
    "division_title": "Preliminary Provisions",
    "division_number": "I",
    "slug": "preliminary-provisions",
    "division_type": "Part"
  }
}
```

### StatuteProvision Bookmark Response

```json
{
  "bookmarkable": {
    "id": 1,
    "type": "StatuteProvision",
    "provision_title": "Interpretation",
    "provision_number": "1",
    "slug": "interpretation",
    "provision_text": "In this Act, unless the context otherwise requires..."
  }
}
```

---

## Usage Notes

1. **User Isolation**: Users can only see and manage their own bookmarks.

2. **Pagination**: The list endpoint supports standard Laravel pagination with `page` and `per_page` parameters.

3. **Search**: The search functionality works across title, name, content, and body fields depending on the content type.

4. **Duplicate Prevention**: The API prevents users from bookmarking the same item multiple times.

5. **Complete Data**: Bookmark responses include the full model data using standard resource classes, ensuring consistency with individual model endpoints.

6. **Type Safety**: All bookmarkable types are validated against a whitelist of supported models.

7. **Standard Resources**: Each bookmarkable item uses its corresponding resource class (CaseResource, NoteResource, StatuteResource, etc.) providing complete model data with proper formatting.

---

## Rate Limiting

Standard API rate limiting applies to all bookmark endpoints. Please refer to the main API documentation for rate limiting details.

---

## Changelog

- **v2.0.1**: Updated bookmark response structure for consistency
  - BookmarkResource now uses standard model resources (CaseResource, NoteResource, etc.)
  - Responses include complete model data without truncation
  - Added `bookmarkable_type` and `bookmarkable_id` fields to bookmark objects
  - Ensured consistency with other API endpoints

- **v2.0.0**: Initial bookmark API implementation with support for all content types
  - Added CRUD operations for bookmarks
  - Added bookmark status checking
  - Added statistics endpoint
  - Implemented user isolation and security measures