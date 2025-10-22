# Feedback API - Admin Documentation

## Overview
The Admin Feedback API allows administrators to view, manage, and respond to user feedback. Admins can review all feedback submissions, update their status, and move critical items to the issues tracking system.

## Base URL
```
https://rest.lawexa.com/api/admin
```
For local development:
```
http://localhost:8000/api/admin
```

## Authentication
All admin endpoints require admin, researcher, or superadmin role.

### Authentication Headers
```http
Authorization: Bearer {admin_access_token}
Accept: application/json
```

## Endpoints

### 1. Get All Feedback (Admin View)

Retrieve a comprehensive list of all user feedback with filtering and statistics.

**Endpoint:** `GET /admin/feedback`

**Access:** Admin, Researcher, Superadmin

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 15 | Items per page (max 100) |
| `status` | string | No | null | Filter by status (pending, under_review, resolved) |
| `content_type` | string | No | null | Filter by content type |
| `user_id` | integer | No | null | Filter by specific user |
| `moved_to_issues` | boolean | No | null | Filter by whether moved to issues |
| `search` | string | No | null | Search in feedback text |
| `date_from` | date | No | null | Filter from date (YYYY-MM-DD) |
| `date_to` | date | No | null | Filter to date (YYYY-MM-DD) |
| `sort_by` | string | No | created_at | Sort field |
| `sort_order` | string | No | desc | Sort order (asc, desc) |

**Example Request:**

```bash
curl -X GET "https://rest.lawexa.com/api/admin/feedback?status=pending&per_page=20" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
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
      "last_page": 5,
      "per_page": 20,
      "total": 95,
      "from": 1,
      "to": 20
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/feedback?page=1",
      "last": "https://rest.lawexa.com/api/admin/feedback?page=5",
      "prev": null,
      "next": "https://rest.lawexa.com/api/admin/feedback?page=2"
    },
    "stats": {
      "total": 95,
      "pending": 42,
      "under_review": 18,
      "resolved": 35,
      "moved_to_issues": 12
    }
  }
}
```

### 2. Get Single Feedback (Admin View)

View detailed information about a specific feedback submission.

**Endpoint:** `GET /admin/feedback/{id}`

**Access:** Admin, Researcher, Superadmin

**Path Parameters:**
- `id`: Feedback ID

**Example Request:**

```bash
curl -X GET "https://rest.lawexa.com/api/admin/feedback/1" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
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
      "feedback_text": "The case title appears to have a typo on page 3. It says 'Smtih' instead of 'Smith'.",
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
  }
}
```

### 3. Update Feedback Status

Update the status of a feedback submission.

**Endpoint:** `PATCH /admin/feedback/{id}/status`

**Access:** Admin, Researcher, Superadmin

**Path Parameters:**
- `id`: Feedback ID

**Request Body:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | Yes | New status (pending, under_review, resolved) |

**Example Request:**

```bash
# Mark as under review
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/1/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "under_review"
  }'

# Mark as resolved
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/1/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "resolved"
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Feedback status updated successfully",
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
      "images": [],
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

**Validation Error (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "status": ["Status must be one of: pending, under_review, resolved."]
  }
}
```

### 4. Move Feedback to Issues

Move critical feedback to the issues tracking system for prioritized action.

**Endpoint:** `POST /admin/feedback/{id}/move-to-issues`

**Access:** Admin, Researcher, Superadmin

**Path Parameters:**
- `id`: Feedback ID

**Request Body:** None required

**Example Request:**

```bash
curl -X POST "https://rest.lawexa.com/api/admin/feedback/1/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Feedback moved to issues successfully",
  "data": {
    "feedback": {
      "id": 1,
      "feedback_text": "Critical security vulnerability found in authentication system",
      "page": null,
      "status": "under_review",
      "status_name": "Under Review",
      "status_color": "blue",
      "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "content": null,
      "images": [],
      "resolved_by": null,
      "resolved_at": null,
      "moved_to_issues": true,
      "moved_by": {
        "id": 789,
        "name": "Admin User",
        "role": "admin"
      },
      "moved_at": "2025-10-22T15:00:00Z",
      "created_at": "2025-10-22T10:00:00Z",
      "updated_at": "2025-10-22T15:00:00Z"
    }
  }
}
```

**Error Response (422):**
```json
{
  "status": "error",
  "message": "This feedback has already been moved to issues",
  "data": null
}
```

## Feedback Management Workflow

### 1. Review New Feedback

```bash
# Get all pending feedback
curl -X GET "https://rest.lawexa.com/api/admin/feedback?status=pending&sort_by=created_at&sort_order=asc" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### 2. Prioritize Critical Issues

```bash
# Move critical feedback to issues
curl -X POST "https://rest.lawexa.com/api/admin/feedback/5/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### 3. Process Feedback

```bash
# Mark as under review while working on it
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/1/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "under_review"}'

# After fixing the issue, mark as resolved
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/1/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "resolved"}'
```

### 4. Track Statistics

```bash
# Get feedback statistics
curl -X GET "https://rest.lawexa.com/api/admin/feedback?per_page=1" \
  -H "Authorization: Bearer ADMIN_TOKEN"

# The response includes a stats object with counts
```

## Filter Examples

### By Content Type

```bash
# Get all feedback for cases
curl -X GET "https://rest.lawexa.com/api/admin/feedback?content_type=App\Models\CourtCase" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### By Date Range

```bash
# Get feedback from last month
curl -X GET "https://rest.lawexa.com/api/admin/feedback?date_from=2025-10-01&date_to=2025-10-31" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### By User

```bash
# Get all feedback from specific user
curl -X GET "https://rest.lawexa.com/api/admin/feedback?user_id=123" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### Moved to Issues

```bash
# Get all feedback that's been moved to issues
curl -X GET "https://rest.lawexa.com/api/admin/feedback?moved_to_issues=true" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

## Status Transitions

### Valid Status Flow

```
pending → under_review → resolved
   ↓           ↓
   └─────→─────┘
```

- **pending**: New feedback awaiting review
- **under_review**: Admin is actively working on the feedback
- **resolved**: Issue has been addressed and resolved

**Note:** Feedback can also move from `under_review` back to `pending` if needed.

## Move to Issues Feature

### When to Move Feedback to Issues

Move feedback to issues when:

1. **Critical bugs** that need immediate attention
2. **Security vulnerabilities** that pose risks
3. **Major content errors** affecting multiple users
4. **Feature requests** that require development work
5. **Complex issues** requiring team collaboration

### Example Scenarios

**Scenario 1: Critical Bug**
```bash
# User reports authentication failure
curl -X POST "https://rest.lawexa.com/api/admin/feedback/15/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Scenario 2: Content Error Affecting Many Users**
```bash
# Multiple users report same statute missing provisions
curl -X POST "https://rest.lawexa.com/api/admin/feedback/23/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

## Best Practices

### For Reviewing Feedback

1. **Prioritize by status** - Review pending feedback first
2. **Check images** - Always view attached screenshots
3. **Verify content references** - Ensure the content referenced actually exists
4. **Update status promptly** - Keep users informed of progress
5. **Move critical items** - Don't let important issues sit in feedback

### For Managing Workflow

1. **Daily Review** - Check new feedback daily
2. **Quick Wins** - Resolve simple typos immediately
3. **Categorize** - Use content_type filters to batch similar issues
4. **Track Progress** - Monitor statistics regularly
5. **Close Loop** - Always mark feedback as resolved when fixed

### For Communication

1. **Timely Updates** - Update status within 24-48 hours
2. **Be Thorough** - Review all details before marking resolved
3. **Document Actions** - The resolved_by field tracks who handled it
4. **Follow Up** - Verify fixes after deployment

## Response Codes

| Code | Description |
|------|-------------|
| 200 | Success - Data retrieved or updated |
| 401 | Unauthorized - Invalid or missing admin token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Feedback doesn't exist |
| 422 | Validation Error - Invalid input |
| 500 | Server Error - Temporary issue |

## Statistics and Reporting

The `stats` object in the list endpoint provides:

- **total** - Total feedback count (with filters applied)
- **pending** - Count of pending feedback
- **under_review** - Count of feedback under review
- **resolved** - Count of resolved feedback
- **moved_to_issues** - Count of feedback moved to issues

Use these stats to:
- Monitor workload
- Track resolution rates
- Identify trends
- Plan resource allocation

## Security Considerations

- Only admins can view all feedback
- Regular users can only see their own submissions
- Status changes are audited (resolved_by, moved_by tracking)
- Images are securely stored on S3
- All actions are logged for accountability
