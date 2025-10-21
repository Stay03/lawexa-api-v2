# Content Requests API v2 - User Documentation

## Overview

The Content Requests API allows verified users to request specific legal cases that may not be available in the current database. Users can track the status of their requests and manage them accordingly.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All endpoints require a valid Bearer token in the Authorization header:
```
Authorization: Bearer your_token_here
```

## User Endpoints

### 1. Get User Content Requests

**Endpoint:** `GET /content-requests`

**Description:** Retrieves a paginated list of the current user's content requests.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `in_progress`, `fulfilled`, `rejected`)
- `search` (optional): Search by title
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): Sort direction (`asc`, `desc`, default: `desc`)
- `per_page` (optional): Items per page (1-100, default: 15)
- `page` (optional): Page number

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/content-requests?status=pending&per_page=5" \
-H "Authorization: Bearer 484|jtwSgflFnt5iThzY0JesEhF15XVpU9IjtRpIF6Ma14848ecc" \
-H "Content-Type: application/json" \
-H "Accept: application/json"
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Content requests retrieved successfully",
  "data": {
    "content_requests": [
      {
        "id": 318,
        "type": "case",
        "type_name": "Case",
        "title": "Request for landmark contract law case on breach of fiduciary duty",
        "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies.",
        "status": "pending",
        "status_name": "Pending",
        "user": {
          "id": 353,
          "name": "Test User",
          "role": "user",
          "avatar": null
        },
        "statute": null,
        "fulfilled_by": null,
        "fulfilled_at": null,
        "rejected_by": null,
        "rejected_at": null,
        "can_edit": false,
        "can_delete": true,
        "created_at": "2025-10-21T01:39:30.000000Z",
        "updated_at": "2025-10-21T01:39:30.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1,
      "from": 1,
      "to": 1
    },
    "links": {
      "first": "http://127.0.0.1:8000/api/content-requests?page=1",
      "last": "http://127.0.0.1:8000/api/content-requests?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 2. Create Content Request

**Endpoint:** `POST /content-requests`

**Description:** Creates a new content request.

**Request Body:**
- `title` (required): Request title (max: 255 characters)
- `additional_notes` (optional): Additional details about the request

**Example Request (Case):**
```bash
curl -X POST "http://127.0.0.1:8000/api/content-requests" \
-H "Authorization: Bearer 484|jtwSgflFnt5iThzY0JesEhF15XVpU9IjtRpIF6Ma14848ecc" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "title": "Request for landmark contract law case on breach of fiduciary duty",
  "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies."
}'
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Content request submitted successfully",
  "data": {
    "content_request": {
      "id": 318,
      "type": "case",
      "type_name": "Case",
      "title": "Request for landmark contract law case on breach of fiduciary duty",
      "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies.",
      "status": "pending",
      "status_name": "Pending",
      "user": {
        "id": 353,
        "name": "Test User",
        "role": "user",
        "avatar": null
      },
      "statute": null,
      "fulfilled_at": null,
      "rejected_at": null,
      "can_edit": false,
      "can_delete": true,
      "created_at": "2025-10-21T01:39:30.000000Z",
      "updated_at": "2025-10-21T01:39:30.000000Z"
    }
  }
}
```

**Validation Rules:**
- `title`: Required, max 255 characters
- `additional_notes`: Optional, max 2000 characters

**Error Response Example:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "title": ["The title field is required."]
  }
}
```

### 3. Get Single Content Request

**Endpoint:** `GET /content-requests/{id}`

**Description:** Retrieves detailed information about a specific content request.

**Path Parameters:**
- `id`: Content request ID

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/content-requests/318" \
-H "Authorization: Bearer 484|jtwSgflFnt5iThzY0JesEhF15XVpU9IjtRpIF6Ma14848ecc" \
-H "Content-Type: application/json" \
-H "Accept: application/json"
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Content request retrieved successfully",
  "data": {
    "content_request": {
      "id": 318,
      "type": "case",
      "type_name": "Case",
      "title": "Request for landmark contract law case on breach of fiduciary duty",
      "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies.",
      "status": "pending",
      "status_name": "Pending",
      "user": {
        "id": 353,
        "name": "Test User",
        "role": "user",
        "avatar": null
      },
      "statute": null,
      "parent_division": null,
      "parent_provision": null,
      "fulfilled_by": null,
      "fulfilled_at": null,
      "rejected_by": null,
      "rejected_at": null,
      "can_edit": false,
      "can_delete": true,
      "comments": [],
      "created_at": "2025-10-21T01:39:30.000000Z",
      "updated_at": "2025-10-21T01:39:30.000000Z"
    }
  }
}
```

### 4. Delete Content Request

**Endpoint:** `DELETE /content-requests/{id}`

**Description:** Deletes a content request. Only pending requests can be deleted by users.

**Path Parameters:**
- `id`: Content request ID

**Example Request:**
```bash
curl -X DELETE "http://127.0.0.1:8000/api/content-requests/319" \
-H "Authorization: Bearer 484|jtwSgflFnt5iThzY0JesEhF15XVpU9IjtRpIF6Ma14848ecc" \
-H "Content-Type: application/json" \
-H "Accept: application/json"
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Content request deleted successfully",
  "data": null
}
```

**Error Response (cannot delete non-pending request):**
```json
{
  "status": "error",
  "message": "Only pending requests can be deleted",
  "data": null
}
```

## Request Types

### Case Requests
For requesting specific court cases or judgments. All requests through this API are for case content.

## Status Flow

1. **pending**: Request submitted, awaiting review
2. **in_progress**: Request being processed by content team
3. **fulfilled**: Request completed and content added
4. **rejected**: Request declined (with reason)

## Permissions

- Users can only view their own content requests
- Users can only delete requests with `pending` status
- Users cannot edit requests after creation
- All requests require email verification

## Rate Limits

- Standard API rate limits apply
- Content request creation may have additional limits to prevent spam

## Common Error Codes

- `401`: Authentication required
- `403`: Insufficient permissions (trying to access another user's request)
- `404`: Content request not found
- `422`: Validation errors
- `500`: Server error

## Best Practices

1. **Use descriptive titles**: Be specific about what you need
2. **Provide detailed notes**: Include context, jurisdiction, time period, etc.
3. **Check for existing content**: Search the database before requesting
4. **Be specific**: Provide clear details about the case you need
5. **Monitor status**: Check back regularly for updates on your requests