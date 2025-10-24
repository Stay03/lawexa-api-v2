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

Move critical feedback to the issues tracking system for prioritized action. This creates an actual Issue record with bidirectional linking and automatic status synchronization.

**Endpoint:** `POST /admin/feedback/{id}/move-to-issues`

**Access:** Admin, Researcher, Superadmin

**Path Parameters:**
- `id`: Feedback ID

**Request Body (All Optional):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | No | Issue type (bug, feature_request, improvement, other) |
| `severity` | string | No | Issue severity (low, medium, high, critical) |
| `priority` | string | No | Issue priority (low, medium, high, urgent) |
| `status` | string | No | Initial issue status (open, in_progress, resolved, closed, duplicate) |
| `area` | string | No | Affected area (frontend, backend, both, ai-ml-research) |
| `category` | string | No | Issue category (max 100 characters) |
| `assigned_to` | integer | No | User ID to assign issue to |
| `admin_notes` | string | No | Admin notes for the issue |

**Example Requests:**

```bash
# Basic move (uses defaults)
curl -X POST "https://rest.lawexa.com/api/admin/feedback/1/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"

# Move with custom parameters
curl -X POST "https://rest.lawexa.com/api/admin/feedback/4/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "bug",
    "severity": "high",
    "priority": "urgent",
    "status": "in_progress",
    "area": "frontend",
    "category": "UI/UX",
    "assigned_to": 5,
    "admin_notes": "Critical UI bug affecting case viewing"
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Feedback moved to issues successfully",
  "data": {
    "feedback": {
      "id": 4,
      "feedback_text": "The case title appears to have incorrect citation",
      "page": "case/123",
      "status": "under_review",
      "status_name": "Under Review",
      "status_color": "blue",
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
          "url": "https://s3.amazonaws.com/bucket/feedback/screenshot.jpg",
          "order": 0
        }
      ],
      "resolved_by": null,
      "resolved_at": null,
      "moved_to_issues": true,
      "moved_by": {
        "id": 789,
        "name": "Admin User",
        "role": "admin"
      },
      "moved_at": "2025-10-24T15:00:00Z",
      "issue": {
        "id": 104,
        "title": "Feedback: case/123",
        "status": "in_progress",
        "severity": "high",
        "type": "bug"
      },
      "created_at": "2025-10-22T10:00:00Z",
      "updated_at": "2025-10-24T15:00:00Z"
    },
    "issue": {
      "id": 104,
      "title": "Feedback: case/123",
      "description": "[User Feedback]\nThe case title appears to have incorrect citation\n\nRelated to: Case - Smith v Jones [2023] UKSC 42\nPage: case/123\nSubmitted by: John Doe (john@example.com)",
      "type": "bug",
      "severity": "high",
      "priority": "urgent",
      "status": "in_progress",
      "area": "frontend",
      "category": "UI/UX",
      "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      },
      "assigned_to": {
        "id": 5,
        "name": "Developer User",
        "role": "admin"
      },
      "resolved_by": null,
      "from_feedback": true,
      "feedback": {
        "id": 4
      },
      "files": [
        {
          "id": 15,
          "original_name": "screenshot.jpg",
          "s3_path": "issues/2025/10/104/screenshot.jpg",
          "mime_type": "image/jpeg"
        }
      ],
      "admin_notes": "Critical UI bug affecting case viewing",
      "resolved_at": null,
      "created_at": "2025-10-24T15:00:00Z",
      "updated_at": "2025-10-24T15:00:00Z"
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

#### What Happens When Moving Feedback to Issues

1. **Issue Creation**: A new Issue record is created in the issues tracking system
2. **Enhanced Description**: Issue description includes the original feedback text plus contextual information (case title, page URL, user details)
3. **Image Transfer**: All feedback images are copied to the issue as File attachments
4. **Bidirectional Linking**: Both records are linked (`feedback.issue_id` and `issue.feedback_id`)
5. **Status Tracking**: The feedback is marked as moved with `moved_by` and `moved_at` timestamps
6. **Status Sync**: Future status changes automatically sync between feedback and issue (see Bidirectional Status Sync below)

## Bidirectional Status Synchronization

When feedback is moved to issues, the two records become linked and their statuses automatically sync in **both directions**. This ensures consistency between the feedback and issue tracking systems.

### How It Works

#### Feedback → Issue Sync

When you update a feedback's status using `PATCH /admin/feedback/{id}/status`, if the feedback has been moved to issues (has an `issue_id`), the linked issue's status will automatically update.

**Status Mapping (Feedback → Issue):**

| Feedback Status | Issue Status | Notes |
|----------------|--------------|-------|
| `pending` | `open` | Feedback reopened or needs attention |
| `under_review` | `in_progress` | Admin actively working on the issue |
| `resolved` | `resolved` | Issue completed and closed |

**Example:**
```bash
# Update feedback to "pending"
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/4/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "pending"}'

# The linked issue (e.g., #104) automatically updates to "open"
```

#### Issue → Feedback Sync

When you update an issue's status using `PUT /admin/issues/{id}`, if the issue came from feedback (has a `feedback_id`), the original feedback's status will automatically update.

**Status Mapping (Issue → Feedback):**

| Issue Status | Feedback Status | Notes |
|-------------|-----------------|-------|
| `open` | No change | Doesn't sync backwards to avoid loops |
| `in_progress` | `under_review` | Work has started |
| `resolved` | `resolved` | Issue completed |
| `closed` | `resolved` | Issue closed |
| `duplicate` | No change | Doesn't affect feedback status |

**Example:**
```bash
# Update issue to "in_progress"
curl -X PUT "https://rest.lawexa.com/api/admin/issues/104" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'

# The linked feedback (#4) automatically updates to "under_review"
```

### Synced Fields

When status changes sync, the following fields are also synchronized:

| Field | Syncs When | Direction |
|-------|-----------|-----------|
| `status` | Always on status change | Both directions |
| `resolved_by` | Status changes to "resolved" | Both directions |
| `resolved_at` | Status changes to "resolved" | Both directions |

**Example: Resolving an Issue**
```bash
# Resolve the issue
curl -X PUT "https://rest.lawexa.com/api/admin/issues/104" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "resolved"}'

# Both records now have:
# - status: "resolved"
# - resolved_by: {admin user who made the change}
# - resolved_at: {timestamp of the change}
```

### Sync Workflow Example

Here's a complete workflow showing bidirectional sync in action:

```bash
# Step 1: Move feedback to issues
curl -X POST "https://rest.lawexa.com/api/admin/feedback/4/move-to-issues" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "bug",
    "severity": "high",
    "status": "open"
  }'
# Creates Issue #104
# Feedback status: pending (unchanged initially)
# Issue status: open

# Step 2: Start working on the issue
curl -X PUT "https://rest.lawexa.com/api/admin/issues/104" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'
# Issue → Feedback sync triggers
# Feedback status: under_review
# Issue status: in_progress

# Step 3: Resolve the issue
curl -X PUT "https://rest.lawexa.com/api/admin/issues/104" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "resolved"}'
# Issue → Feedback sync triggers
# Feedback status: resolved (with resolved_by and resolved_at)
# Issue status: resolved (with resolved_by and resolved_at)

# Step 4: Reopen if needed (from feedback side)
curl -X PATCH "https://rest.lawexa.com/api/admin/feedback/4/status" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "pending"}'
# Feedback → Issue sync triggers
# Feedback status: pending
# Issue status: open
```

### Important Notes

1. **Automatic Sync**: No manual action required - status changes sync immediately
2. **Logged**: All sync operations are logged for debugging
3. **One-Way per Action**: Each status update only syncs once (no infinite loops)
4. **Error Handling**: If sync fails, the original status update still succeeds
5. **Timestamps Preserved**: When syncing resolved status, timestamps are copied exactly
6. **Admin Tracking**: The `resolved_by` field tracks which admin resolved the item in both systems

### Feedback Response with Issue Link

When feedback has been moved to issues, the response includes an `issue` object:

```json
{
  "id": 4,
  "feedback_text": "Bug report text",
  "status": "under_review",
  "moved_to_issues": true,
  "issue": {
    "id": 104,
    "title": "Feedback: case/123",
    "status": "in_progress",
    "severity": "high",
    "type": "bug"
  }
}
```

This allows you to:
- See which issue was created from the feedback
- Check the current issue status
- Navigate to the issue for more details

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

## Changelog

### 2025-10-24

#### Enhanced Move to Issues Functionality
- **Issue Creation**: The `POST /admin/feedback/{id}/move-to-issues` endpoint now creates actual Issue records in the issues tracking system
- **Optional Parameters**: Added support for customizing issue creation with optional parameters:
  - `type`, `severity`, `priority`, `status`, `area`, `category`
  - `assigned_to`, `admin_notes`
- **Enhanced Descriptions**: Issue descriptions now include contextual information from the original feedback:
  - Case title and content type
  - Page URL where feedback originated
  - User details (name and email)
- **Image Transfer**: Feedback images are automatically copied to the issue as File attachments

#### Bidirectional Status Synchronization
- **Automatic Sync**: Status changes now sync automatically between linked feedback and issues in both directions
- **Status Mapping**:
  - Feedback → Issue: pending→open, under_review→in_progress, resolved→resolved
  - Issue → Feedback: in_progress→under_review, resolved/closed→resolved
- **Timestamp Sync**: `resolved_by` and `resolved_at` timestamps sync when either record is marked as resolved
- **Loop Prevention**: Intelligent sync logic prevents infinite update loops
- **Error Handling**: Sync failures are logged but don't affect the primary status update

#### New Response Fields
- **`issue` object**: Feedback responses now include an `issue` object when moved to issues, showing:
  - Issue ID, title, status, severity, and type
  - Allows tracking issue details directly from feedback API
- **`issue_id` field**: Direct link to the created issue record

#### Database Changes
- Added `issue_id` foreign key to feedback table
- Added `feedback_id` foreign key to issues table
- Both fields are nullable with proper indexes and cascading deletes
