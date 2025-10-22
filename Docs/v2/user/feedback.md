# Feedback API - User Documentation

## Overview
The Feedback API allows authenticated users to submit feedback about content (cases, statutes, provisions, divisions, notes) or general pages. Users can include text descriptions and attach up to 4 images to illustrate issues or suggestions.

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
All feedback endpoints require authentication. Users must be verified to submit feedback.

### Authentication Headers
```http
Authorization: Bearer {access_token}
Accept: application/json
```

## Endpoints

### 1. Get User's Feedback

Retrieve a list of all feedback submitted by the authenticated user.

**Endpoint:** `GET /feedback`

**Access:** Authenticated users only

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 15 | Items per page (max 100) |
| `status` | string | No | null | Filter by status (pending, under_review, resolved) |
| `content_type` | string | No | null | Filter by content type |
| `moved_to_issues` | boolean | No | null | Filter by whether moved to issues |
| `search` | string | No | null | Search in feedback text |
| `sort_by` | string | No | created_at | Sort field |
| `sort_order` | string | No | desc | Sort order (asc, desc) |

**Example Request:**

```bash
curl -X GET "https://rest.lawexa.com/api/feedback?status=pending&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Feedback retrieved successfully",
  "data": {
    "feedback": [
      {
        "id": 1,
        "feedback_text": "The case title appears to have a typo. It says 'Smtih' instead of 'Smith'.",
        "page": "3",
        "status": "pending",
        "status_name": "Pending",
        "status_color": "gray",
        "user": {
          "id": 123,
          "name": "John Doe",
          "email": "john@example.com",
          "role": "user"
        },
        "content": {
          "type": "Case",
          "id": 456,
          "title": "Smith v Jones [2023] UKSC 42"
        },
        "images": [
          {
            "id": 1,
            "url": "https://s3.amazonaws.com/bucket/feedback/2025/10/1/abc-123.jpg",
            "order": 0,
            "created_at": "2025-10-22T10:00:00Z"
          }
        ],
        "resolved_by": null,
        "resolved_at": null,
        "moved_to_issues": false,
        "moved_by": null,
        "moved_at": null,
        "created_at": "2025-10-22T10:00:00Z",
        "updated_at": "2025-10-22T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 10,
      "total": 25,
      "from": 1,
      "to": 10
    },
    "links": {
      "first": "https://rest.lawexa.com/api/feedback?page=1",
      "last": "https://rest.lawexa.com/api/feedback?page=3",
      "prev": null,
      "next": "https://rest.lawexa.com/api/feedback?page=2"
    }
  }
}
```

### 2. Submit New Feedback

Submit feedback about content or a page.

**Endpoint:** `POST /feedback`

**Access:** Authenticated and verified users only

**Request Headers:**
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `feedback_text` | string | Yes | Feedback description (10-5000 characters) |
| `content_type` | string | No | Full class name of content (e.g., "App\Models\CourtCase") |
| `content_id` | integer | No | ID of the content (required if content_type is provided) |
| `page` | string | No | Page reference (max 100 characters) |
| `images` | array | No | Array of image files (max 4 images) |
| `images.*` | file | No | Each image (JPEG, PNG, GIF, WebP, max 5MB each) |

**Valid Content Types:**
- `App\Models\CourtCase` - For cases
- `App\Models\Statute` - For statutes
- `App\Models\StatuteProvision` - For provisions
- `App\Models\StatuteDivision` - For divisions
- `App\Models\Note` - For notes

**Example Request (with images):**

```bash
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "feedback_text=The case title appears to have a typo on page 3" \
  -F "content_type=App\Models\CourtCase" \
  -F "content_id=456" \
  -F "page=3" \
  -F "images[]=@/path/to/screenshot1.jpg" \
  -F "images[]=@/path/to/screenshot2.jpg"
```

**Example Request (general page feedback without content):**

```bash
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "feedback_text=The search results page has a layout issue on mobile devices" \
  -F "images[]=@/path/to/screenshot.jpg"
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Feedback submitted successfully",
  "data": {
    "feedback": {
      "id": 1,
      "feedback_text": "The case title appears to have a typo on page 3",
      "page": "3",
      "status": "pending",
      "status_name": "Pending",
      "status_color": "gray",
      "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "content": {
        "type": "Case",
        "id": 456,
        "title": "Smith v Jones [2023] UKSC 42"
      },
      "images": [
        {
          "id": 1,
          "url": "https://s3.amazonaws.com/bucket/feedback/2025/10/1/abc-123.jpg",
          "order": 0,
          "created_at": "2025-10-22T10:00:00Z"
        },
        {
          "id": 2,
          "url": "https://s3.amazonaws.com/bucket/feedback/2025/10/1/def-456.jpg",
          "order": 1,
          "created_at": "2025-10-22T10:00:00Z"
        }
      ],
      "resolved_by": null,
      "resolved_at": null,
      "moved_to_issues": false,
      "moved_by": null,
      "moved_at": null,
      "created_at": "2025-10-22T10:00:00Z",
      "updated_at": "2025-10-22T10:00:00Z"
    }
  }
}
```

**Validation Errors (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "feedback_text": ["Feedback must be at least 10 characters."],
    "images": ["You can upload a maximum of 4 images."],
    "images.0": ["Each image must not exceed 5MB."]
  }
}
```

### 3. Get Single Feedback

Retrieve details of a specific feedback submission.

**Endpoint:** `GET /feedback/{id}`

**Access:** Users can only view their own feedback

**Path Parameters:**
- `id`: Feedback ID

**Example Request:**

```bash
curl -X GET "https://rest.lawexa.com/api/feedback/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Feedback retrieved successfully",
  "data": {
    "feedback": {
      "id": 1,
      "feedback_text": "The case title appears to have a typo on page 3",
      "page": "3",
      "status": "resolved",
      "status_name": "Resolved",
      "status_color": "green",
      "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "content": {
        "type": "Case",
        "id": 456,
        "title": "Smith v Jones [2023] UKSC 42"
      },
      "images": [
        {
          "id": 1,
          "url": "https://s3.amazonaws.com/bucket/feedback/2025/10/1/abc-123.jpg",
          "order": 0,
          "created_at": "2025-10-22T10:00:00Z"
        }
      ],
      "resolved_by": {
        "id": 789,
        "name": "Admin User",
        "role": "admin"
      },
      "resolved_at": "2025-10-22T14:30:00Z",
      "moved_to_issues": false,
      "moved_by": null,
      "moved_at": null,
      "created_at": "2025-10-22T10:00:00Z",
      "updated_at": "2025-10-22T14:30:00Z"
    }
  }
}
```

**Error Response (403):**
```json
{
  "status": "error",
  "message": "You can only view your own feedback",
  "data": null
}
```

## Feedback Status Flow

Feedback goes through the following status transitions:

1. **pending** - Initial status when feedback is submitted
2. **under_review** - Admin is reviewing the feedback
3. **resolved** - Feedback has been addressed and resolved

## Image Upload Guidelines

### Accepted Formats
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### Size Limits
- Maximum 4 images per feedback
- Each image must not exceed 5MB
- Images are stored on Amazon S3

### Best Practices
1. **Use screenshots** to illustrate issues clearly
2. **Highlight the problem** area in your images
3. **Include context** - capture enough of the page to show where the issue is
4. **Compress images** before uploading to reduce file size
5. **Use clear, high-quality** images for better visibility

## Use Cases

### 1. Report Content Errors

```bash
# Report a typo in a case
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "feedback_text=The defendant's name is misspelled as 'Jonse' instead of 'Jones' on page 5" \
  -F "content_type=App\Models\CourtCase" \
  -F "content_id=123" \
  -F "page=5" \
  -F "images[]=@screenshot.jpg"
```

### 2. Report Missing Information

```bash
# Report missing provision
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "feedback_text=Section 15(3) is missing from this statute. It should appear between sections 15(2) and 15(4)" \
  -F "content_type=App\Models\Statute" \
  -F "content_id=456"
```

### 3. Report Page Issues

```bash
# Report general page issue
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "feedback_text=The mobile navigation menu doesn't close when clicking outside of it" \
  -F "images[]=@mobile-issue.jpg"
```

### 4. Suggest Improvements

```bash
# Suggest content improvement
curl -X POST "https://rest.lawexa.com/api/feedback" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "feedback_text=It would be helpful to add cross-references to related cases at the bottom of this judgment" \
  -F "content_type=App\Models\CourtCase" \
  -F "content_id=789"
```

## Response Codes

| Code | Description |
|------|-------------|
| 200 | Success - Data retrieved |
| 201 | Created - Feedback submitted successfully |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Cannot access this feedback |
| 422 | Validation Error - Invalid input data |
| 500 | Server Error - Temporary server issue |

## Permissions

- Users must be authenticated to submit or view feedback
- Users must be verified to submit new feedback
- Users can only view their own feedback
- Users cannot edit or delete feedback after submission
- Users can track the status of their submitted feedback

## Best Practices

1. **Be specific** - Clearly describe the issue or suggestion
2. **Include details** - Mention page numbers, section references, or specific locations
3. **Attach images** - Visual evidence helps resolve issues faster
4. **One issue per feedback** - Submit separate feedback for different issues
5. **Check existing feedback** - Avoid submitting duplicate feedback
6. **Provide context** - Explain why the change or fix is needed
7. **Be constructive** - Focus on improvements rather than complaints

## Privacy

- Feedback is only visible to the user who submitted it and admin users
- Images are stored securely on Amazon S3
- User information is protected according to privacy policies
- Feedback data is used solely to improve content quality
