# Content Requests API v2 - Admin Documentation

## Overview

The Content Requests Admin API provides administrators and content managers with comprehensive tools to manage user content requests. Admins can view, update, approve, reject, and analyze content requests from all users.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All endpoints require a valid Bearer token from an admin, researcher, or superadmin account:
```
Authorization: Bearer admin_token_here
```

## Required Permissions
- `admin`: Full access to all content request operations
- `researcher`: View and update content requests
- `superadmin`: Full access plus bulk operations

## Admin Endpoints

### 1. Get All Content Requests

**Endpoint:** `GET /admin/content-requests`

**Description:** Retrieves a paginated list of all content requests from all users.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `in_progress`, `fulfilled`, `rejected`)
- `type` (optional): Filter by type (`case`, `statute`, `provision`, `division`)
- `user_id` (optional): Filter by specific user ID
- `search` (optional): Search by title
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): Sort direction (`asc`, `desc`, default: `desc`)
- `per_page` (optional): Items per page (1-100, default: 15)
- `page` (optional): Page number

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/content-requests?status=pending&per_page=15" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
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
        "id": 335,
        "type": "case",
        "type_name": "Case",
        "title": "Test Case Request for Content Request System",
        "additional_notes": "Please include recent case law and precedents",
        "status": "fulfilled",
        "status_name": "Fulfilled",
        "user": {
          "id": 301,
          "name": "Test User",
          "role": "user",
          "avatar": null
        },
        "created_content": {
          "id": 7188,
          "title": "Contract Law Breach - Digital Services Agreement",
          "court": "Federal High Court",
          "created_at": "2025-10-21T03:47:03.000000Z"
        },
        "fulfilled_by": {
          "id": 355,
          "name": "Test Admin User"
        },
        "fulfilled_at": "2025-10-21T03:51:06.000000Z",
        "rejected_by": null,
        "rejected_at": null,
        "duplicate_count": 0,
        "can_edit": false,
        "can_delete": false,
        "created_at": "2025-10-21T03:44:49.000000Z",
        "updated_at": "2025-10-21T03:51:06.000000Z"
      },
      {
        "id": 319,
        "type": "statute",
        "type_name": "Statute",
        "title": "Companies Act 2020 - Digital Asset Regulations",
        "additional_notes": "Need provisions related to cryptocurrency and digital asset management in corporate settings.",
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
        "duplicate_count": 0,
        "can_edit": false,
        "can_delete": true,
        "created_at": "2025-10-21T01:41:21.000000Z",
        "updated_at": "2025-10-21T01:41:21.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 20,
      "per_page": 15,
      "total": 290,
      "from": 1,
      "to": 15
    },
    "links": {
      "first": "http://127.0.0.1:8000/api/admin/content-requests?page=1",
      "last": "http://127.0.0.1:8000/api/admin/content-requests?page=20",
      "prev": null,
      "next": "http://127.0.0.1:8000/api/admin/content-requests?page=2"
    }
  }
}
```

### 2. Get Single Content Request

**Endpoint:** `GET /admin/content-requests/{id}`

**Description:** Retrieves detailed information about a specific content request.

**Path Parameters:**
- `id`: Content request ID

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/content-requests/318" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
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
      "comments": [],
      "created_at": "2025-10-21T01:39:30.000000Z",
      "updated_at": "2025-10-21T01:39:30.000000Z"
    }
  }
}
```

### 3. Update Content Request

**Endpoint:** `PUT /admin/content-requests/{id}`

**Description:** Updates the status and details of a content request.

**Path Parameters:**
- `id`: Content request ID

**Request Body:**
- `status` (optional): New status (`pending`, `in_progress`, `fulfilled`, `rejected`)
- `created_content_type` (optional): Type of content created to fulfill request (`App\Models\CourtCase`, `App\Models\Statute`, etc.)
- `created_content_id` (optional): ID of the created content
- `rejection_reason` (optional): Reason for rejection (required when status is `rejected`)

**Example Request (Mark as In Progress):**
```bash
curl -X PUT "http://127.0.0.1:8000/api/admin/content-requests/318" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "status": "in_progress"
}'
```

**Example Request (Fulfill with Content):**
```bash
curl -X PUT "http://127.0.0.1:8000/api/admin/content-requests/318" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 123
}'
```

**Example Request (Reject with Reason):**
```bash
curl -X PUT "http://127.0.0.1:8000/api/admin/content-requests/318" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "status": "rejected",
  "rejection_reason": "This case already exists in our database. Please search for Smith v Jones [2023] EWCA Civ 123."
}'
```

**Example Response (Mark as In Progress):**
```json
{
  "status": "success",
  "message": "Content request updated successfully",
  "data": {
    "content_request": {
      "id": 318,
      "type": "case",
      "type_name": "Case",
      "title": "Request for landmark contract law case on breach of fiduciary duty",
      "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies.",
      "status": "in_progress",
      "status_name": "In Progress",
      "user": {
        "id": 353,
        "name": "Test User",
        "role": "user",
        "avatar": null
      },
      "fulfilled_by": null,
      "fulfilled_at": null,
      "rejected_by": null,
      "rejected_at": null,
      "created_at": "2025-10-21T01:39:30.000000Z",
      "updated_at": "2025-10-21T01:42:48.000000Z"
    }
  }
}
```

**Example Response (Fulfilled with Content):**
```json
{
  "status": "success",
  "message": "Content request updated successfully",
  "data": {
    "content_request": {
      "id": 318,
      "type": "case",
      "type_name": "Case",
      "title": "Request for landmark contract law case on breach of fiduciary duty",
      "additional_notes": "Looking for a case that establishes precedent for director liability in corporate governance disputes, particularly cases involving technology companies.",
      "status": "fulfilled",
      "status_name": "Fulfilled",
      "user": {
        "id": 353,
        "name": "Test User",
        "role": "user",
        "avatar": null
      },
      "created_content": {
        "id": 7188,
        "title": "Contract Law Breach - Digital Services Agreement",
        "court": "Federal High Court",
        "jurisdiction": "Federal",
        "decision_date": "2024-03-15",
        "body": "This case involves a dispute between a software development company and a client regarding the delivery and quality of a custom software solution. The plaintiff alleges that the defendant failed to meet agreed-upon specifications and timelines, resulting in significant business losses...",
        "created_at": "2025-10-21T03:47:03.000000Z",
        "updated_at": "2025-10-21T03:47:03.000000Z"
      },
      "fulfilled_by": {
        "id": 355,
        "name": "Test Admin User",
        "role": "admin"
      },
      "fulfilled_at": "2025-10-21T03:51:06.000000Z",
      "rejected_by": null,
      "rejected_at": null,
      "created_at": "2025-10-21T01:39:30.000000Z",
      "updated_at": "2025-10-21T03:51:06.000000Z"
    }
  }
}
```

### 4. Delete Content Request

**Endpoint:** `DELETE /admin/content-requests/{id}`

**Description:** Permanently deletes a content request. This action cannot be undone.

**Path Parameters:**
- `id`: Content request ID

**Example Request:**
```bash
curl -X DELETE "http://127.0.0.1:8000/api/admin/content-requests/319" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
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

### 5. Get Content Request Statistics

**Endpoint:** `GET /admin/content-requests/stats`

**Description:** Retrieves comprehensive statistics about content requests.

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/content-requests/stats" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
-H "Content-Type: application/json" \
-H "Accept: application/json"
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Statistics retrieved successfully",
  "data": {
    "total": 290,
    "by_status": {
      "pending": 234,
      "in_progress": 14,
      "fulfilled": 14,
      "rejected": 28
    },
    "by_type": {
      "case": 288,
      "statute": 2,
      "provision": 0,
      "division": 0
    },
    "recent_activity": {
      "last_7_days": 290,
      "last_30_days": 290
    },
    "fulfillment_rate": 4.83
  }
}
```

### 6. Get Duplicate Requests

**Endpoint:** `GET /admin/content-requests/duplicates`

**Description:** Identifies duplicate content requests (same title and type from different users).

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/admin/content-requests/duplicates" \
-H "Authorization: Bearer 485|QFGRBsQnJtQ2WEO4jaP3t6kj72nRTRNCDDRQNd6i89bd05a4" \
-H "Content-Type: application/json" \
-H "Accept: application/json"
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Duplicate requests retrieved successfully",
  "data": {
    "duplicates": [
      {
        "title": "Duplicate Test Case [2025]",
        "type": "case",
        "request_count": 72,
        "requests": [
          {
            "id": 7,
            "type": "case",
            "type_name": "Case",
            "title": "Duplicate Test Case [2025]",
            "additional_notes": null,
            "status": "pending",
            "status_name": "Pending",
            "user": {
              "id": 348,
              "name": "Test User Content Request",
              "role": "user",
              "avatar": null
            },
            "fulfilled_at": null,
            "rejected_at": null,
            "duplicate_count": 71,
            "can_edit": false,
            "can_delete": true,
            "created_at": "2025-10-20T21:22:05.000000Z",
            "updated_at": "2025-10-20T21:22:05.000000Z"
          }
        ]
      }
    ]
  }
}
```

## Status Management

### Status Transitions

1. **pending** → **in_progress**: When admin starts working on the request
2. **pending** → **rejected**: When request cannot be fulfilled
3. **in_progress** → **fulfilled**: When content is successfully created
4. **in_progress** → **rejected**: When fulfillment is not possible
5. **pending** → **fulfilled**: Direct fulfillment without in-progress status

### Status Actions

**Mark as In Progress:**
```json
{
  "status": "in_progress"
}
```

**Fulfill Request:**
```json
{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 123
}
```

**Reject Request:**
```json
{
  "status": "rejected",
  "rejection_reason": "This case already exists in our database."
}
```

## Content Linking

When fulfilling requests, you can link to existing content:

**Supported Content Types:**
- `App\Models\CourtCase`: Court cases and judgments
- `App\Models\Statute`: Legislation and statutes
- `App\Models\StatuteProvision`: Statute provisions
- `App\Models\StatuteDivision`: Statute divisions

**Example:**
```json
{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 456
}
```

### created_content Field (Fulfilled Requests Only)

When a request is successfully fulfilled, the API response will include a `created_content` field containing the full details of the content that was linked to fulfill the request. This allows you to:

1. **Verify the correct content was linked** - Confirm the case, statute, or provision matches the user's request
2. **View complete content details** - Access all content fields without making additional API calls
3. **Validate content quality** - Ensure the created content meets standards before notifying users

**Example created_content for Case:**
```json
"created_content": {
  "id": 7188,
  "title": "Contract Law Breach - Digital Services Agreement",
  "court": "Federal High Court",
  "jurisdiction": "Federal",
  "decision_date": "2024-03-15",
  "body": "Full case content here...",
  "created_at": "2025-10-21T03:47:03.000000Z",
  "updated_at": "2025-10-21T03:47:03.000000Z"
}
```

**Example created_content for Statute:**
```json
"created_content": {
  "id": 123,
  "title": "Companies Act 2020",
  "jurisdiction": "Federal",
  "year": "2020",
  "description": "An Act to provide for the incorporation...",
  "created_at": "2025-10-21T02:15:30.000000Z"
}
```

## Bulk Operations

Admins can perform bulk operations using standard API patterns:

**Bulk Update (Example):**
```bash
# Update multiple requests to in_progress status
for id in 318 319 320; do
  curl -X PUT "http://127.0.0.1:8000/api/admin/content-requests/$id" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'
done
```

## Permissions Matrix

| Action | admin | researcher | superadmin |
|--------|-------|------------|------------|
| View all requests | ✅ | ✅ | ✅ |
| Update status | ✅ | ✅ | ✅ |
| Delete requests | ✅ | ❌ | ✅ |
| View statistics | ✅ | ✅ | ✅ |
| View duplicates | ✅ | ✅ | ✅ |
| Bulk operations | ✅ | ❌ | ✅ |

## Analytics and Reporting

### Key Metrics
- **Fulfillment Rate**: Percentage of requests successfully fulfilled
- **Average Processing Time**: Time from pending to fulfillment
- **Duplicate Rate**: Percentage of duplicate requests
- **User Engagement**: Requests per user

### Common Filters
- Status distribution
- Request type breakdown
- Time-based analysis (last 7/30 days)
- User-specific metrics

## Best Practices

1. **Regular Review**: Check pending requests daily
2. **Duplicate Management**: Review duplicates before creating new content
3. **Clear Communication**: Provide specific rejection reasons
4. **Status Updates**: Keep users informed by updating status promptly
5. **Quality Control**: Verify content exists before marking as fulfilled
6. **Documentation**: Document fulfillment patterns for better service

## Error Handling

**Common Error Responses:**

- `401`: Authentication required or invalid token
- `403`: Insufficient permissions for the requested action
- `404`: Content request not found
- `422`: Validation errors in request body
- `500`: Internal server error

**Validation Error Example:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "rejection_reason": ["Rejection reason is required when status is rejected."],
    "created_content_id": ["Content ID is required when fulfilling requests."]
  }
}
```

## Webhooks and Notifications

The system supports automatic notifications (Phase 2 feature):
- **Request Created**: Notify admins of new requests
- **Status Updated**: Notify users of status changes
- **Request Fulfilled**: Notify users when content is available
- **Request Rejected**: Notify users with rejection reason