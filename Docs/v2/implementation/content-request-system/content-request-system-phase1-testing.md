# Content Request System - Phase 1 Testing Guide

**Version**: 1.0
**Date**: October 20, 2025
**Phase**: 1 - Cases Only
**Status**: Ready for Testing

---

## Table of Contents

1. [Overview](#overview)
2. [System Context](#system-context)
3. [Prerequisites](#prerequisites)
4. [API Endpoints Reference](#api-endpoints-reference)
5. [Testing Checklist](#testing-checklist)
6. [Sample Test Scenarios](#sample-test-scenarios)
7. [Test Data Examples](#test-data-examples)
8. [Expected Behaviors](#expected-behaviors)
9. [Verification Methods](#verification-methods)
10. [Common Issues & Troubleshooting](#common-issues--troubleshooting)

---

## Overview

### What is Phase 1?

Phase 1 implements the **Content Request System for Cases Only**. This system allows users to request missing legal cases that they want added to the Lawexa database. Admins and researchers can then review, manage, and fulfill these requests.

### Key Features Implemented

- ✅ Users can submit content requests for missing cases
- ✅ Users can view, filter, and search their own requests
- ✅ Users can delete pending requests
- ✅ Admins can view all requests with advanced filtering
- ✅ Admins can update request status and link created content
- ✅ Admins can reject requests with reasons
- ✅ Admins can view statistics and find duplicates
- ✅ Complete authorization and validation
- ✅ Database integrity with foreign keys and indexes

### What's NOT in Phase 1

- ❌ Email notifications (system works without them)
- ❌ Auto-linking when creating cases
- ❌ Requests for statutes, provisions, or divisions (Phase 2)

---

## System Context

### How It Works

```
┌─────────────────────────────────────────────────────────────┐
│                    Content Request Lifecycle                │
└─────────────────────────────────────────────────────────────┘

1. USER SUBMISSION
   User → Submit Request → Database (status: pending)

2. ADMIN REVIEW
   Admin → View Requests → Filter/Search → Select Request

3. ADMIN ACTION (Option A: Fulfill)
   Admin → Create Case → Update Request (link case) → Status: fulfilled

4. ADMIN ACTION (Option B: Reject)
   Admin → Review Request → Reject with Reason → Status: rejected

5. USER MANAGEMENT
   User → View Status → (Delete if pending)
```

### Database Relationships

```
content_requests table
├── user_id → users.id (who requested)
├── created_content_type → polymorphic (App\Models\CourtCase)
├── created_content_id → polymorphic (case ID when fulfilled)
├── fulfilled_by → users.id (admin who fulfilled)
├── rejected_by → users.id (admin who rejected)
└── statute_id → statutes.id (for future: provisions/divisions)
```

### User Roles

- **Regular User**: Can create, view own requests, delete own pending requests
- **Admin/Researcher/Superadmin**: Can view all requests, update status, link content, reject, view stats

---

## Prerequisites

### Before Testing

- [ ] Database migration applied (`content_requests` table exists)
- [ ] Routes registered (`php artisan route:list | grep content-request`)
- [ ] Authentication working (can obtain valid tokens)
- [ ] At least one verified user account
- [ ] At least one admin/researcher account
- [ ] Test database or development environment

### Environment Setup

1. **Verify Migration**:
   ```bash
   php artisan migrate:status
   ```
   Look for: `2025_10_20_202518_create_content_requests_table`

2. **Verify Routes**:
   ```bash
   php artisan route:list --name=content-request
   ```
   Should show 10 routes (4 user + 6 admin)

3. **Get Authentication Tokens**:
   - User token: Login as verified user via `/api/auth/login`
   - Admin token: Login as admin/researcher via `/api/auth/login`

---

## API Endpoints Reference

### User Endpoints

All user endpoints require:
- Middleware: `auth:sanctum`, `verified`
- Base URL: `/api/content-requests`

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/content-requests` | List user's content requests | User Token |
| POST | `/api/content-requests` | Create new content request | User Token (verified) |
| GET | `/api/content-requests/{id}` | View single content request | User Token (owner only) |
| DELETE | `/api/content-requests/{id}` | Delete content request | User Token (owner, pending only) |

#### GET /api/content-requests

**Query Parameters**:
- `status` (optional): Filter by status (`pending`, `in_progress`, `fulfilled`, `rejected`)
- `type` (optional): Filter by type (`case`, `statute`, `provision`, `division`)
- `search` (optional): Search by title
- `sort_by` (optional): Sort field (default: `created_at`)
- `sort_order` (optional): `asc` or `desc` (default: `desc`)
- `per_page` (optional): Items per page (default: 15, max: 100)

**Response Structure**:
```json
{
  "status": "success",
  "message": "Content requests retrieved successfully",
  "data": {
    "content_requests": [...],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 5,
      "from": 1,
      "to": 5
    }
  }
}
```

#### POST /api/content-requests

**Request Body**:
```json
{
  "type": "case",
  "title": "Smith v Jones [2023] UKSC 45",
  "additional_notes": "Need this case for my dissertation on contract law"
}
```

**Required Fields**:
- `type`: Must be one of: `case`, `statute`, `provision`, `division`
- `title`: String, max 500 characters

**Optional Fields**:
- `additional_notes`: String, max 2000 characters
- `statute_id`: Required if type is `provision` or `division`
- `parent_division_id`: For nested divisions
- `parent_provision_id`: For nested provisions

**Response**: 201 Created with content request object

#### GET /api/content-requests/{id}

**Path Parameters**:
- `id`: Content request ID

**Authorization**: User must own the request

**Response**: Content request object with full details

#### DELETE /api/content-requests/{id}

**Path Parameters**:
- `id`: Content request ID

**Authorization**:
- User must own the request
- Request must be `pending` status

**Response**: 200 OK with success message

---

### Admin Endpoints

All admin endpoints require:
- Middleware: `auth:sanctum`, `role:admin,researcher,superadmin`
- Base URL: `/api/admin/content-requests`

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/admin/content-requests/stats` | Get statistics | Admin Token |
| GET | `/api/admin/content-requests/duplicates` | Find duplicate requests | Admin Token |
| GET | `/api/admin/content-requests` | List all content requests | Admin Token |
| GET | `/api/admin/content-requests/{id}` | View any content request | Admin Token |
| PUT | `/api/admin/content-requests/{id}` | Update content request | Admin Token |
| DELETE | `/api/admin/content-requests/{id}` | Delete any content request | Admin Token |

#### GET /api/admin/content-requests/stats

**Query Parameters**: None

**Response Structure**:
```json
{
  "status": "success",
  "message": "Statistics retrieved successfully",
  "data": {
    "total": 150,
    "by_status": {
      "pending": 45,
      "in_progress": 12,
      "fulfilled": 78,
      "rejected": 15
    },
    "by_type": {
      "case": 150,
      "statute": 0,
      "provision": 0,
      "division": 0
    },
    "recent_activity": {
      "last_7_days": 23,
      "last_30_days": 87
    },
    "fulfillment_rate": 52.0
  }
}
```

#### GET /api/admin/content-requests/duplicates

**Query Parameters**: None

**Response**: List of duplicate requests grouped by title and type

**Response Structure**:
```json
{
  "status": "success",
  "message": "Duplicate requests retrieved successfully",
  "data": {
    "duplicates": [
      {
        "title": "Smith v Jones",
        "type": "case",
        "request_count": 3,
        "requests": [...]
      }
    ]
  }
}
```

#### GET /api/admin/content-requests

**Query Parameters**:
- `status` (optional): Filter by status
- `type` (optional): Filter by type
- `user_id` (optional): Filter by user
- `search` (optional): Search by title
- `sort_by` (optional): Sort field
- `sort_order` (optional): `asc` or `desc`
- `per_page` (optional): Items per page

**Response**: Paginated list of all content requests

#### GET /api/admin/content-requests/{id}

**Path Parameters**:
- `id`: Content request ID

**Response**: Content request object with full details

#### PUT /api/admin/content-requests/{id}

**Path Parameters**:
- `id`: Content request ID

**Request Body** (all fields optional):
```json
{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 5109,
  "rejection_reason": "Duplicate request, already exists in database"
}
```

**Fields**:
- `status`: One of `pending`, `in_progress`, `fulfilled`, `rejected`
- `created_content_type`: Polymorphic model class (required if linking content)
- `created_content_id`: ID of created content (required if linking content)
- `rejection_reason`: String, max 2000 chars (optional, for rejected status)

**Business Rules**:
- If status = `fulfilled`, must provide `created_content_type` and `created_content_id`
- If status = `rejected`, can optionally provide `rejection_reason`
- Content type must be one of: `App\Models\CourtCase`, `App\Models\Statute`, `App\Models\StatuteProvision`, `App\Models\StatuteDivision`

**Response**: Updated content request object

#### DELETE /api/admin/content-requests/{id}

**Path Parameters**:
- `id`: Content request ID

**Response**: 200 OK with success message

---

## Testing Checklist

### A. Pre-Testing Verification

- [ ] **Database Check**: Verify `content_requests` table exists
  ```sql
  SHOW TABLES LIKE 'content_requests';
  SELECT * FROM content_requests LIMIT 1;
  ```

- [ ] **Route Check**: Verify all 10 routes registered
  ```bash
  php artisan route:list --name=content-request
  ```

- [ ] **Authentication**: Obtain valid tokens
  - [ ] User token (verified account)
  - [ ] Admin token (admin/researcher role)

- [ ] **Test Users**: Have at least 2 user accounts and 1 admin account

---

### B. User Endpoint Tests

#### Test 1: Create Content Request (Happy Path)
- [ ] Send POST request to `/api/content-requests` with valid user token
- [ ] Use valid payload (type=case, title present)
- [ ] Expect: 201 Created
- [ ] Verify: Response contains content request with status=`pending`
- [ ] Verify: Database has new record with correct user_id

#### Test 2: Create Request - Validation Errors
- [ ] Send POST without `title` field
  - Expect: 422 Validation Error
  - Error message: "Please provide a title for the requested content."

- [ ] Send POST with title > 500 characters
  - Expect: 422 Validation Error
  - Error message: "Title must not exceed 500 characters."

- [ ] Send POST with invalid `type` value
  - Expect: 422 Validation Error
  - Error message: "Invalid content type."

#### Test 3: Create Request - Authentication
- [ ] Send POST without auth token
  - Expect: 401 Unauthorized

- [ ] Send POST with unverified user token
  - Expect: 403 Forbidden (email not verified)

#### Test 4: List User's Requests
- [ ] Send GET to `/api/content-requests` with user token
- [ ] Expect: 200 OK
- [ ] Verify: Returns only requests created by this user
- [ ] Verify: Pagination metadata present

#### Test 5: List with Filters
- [ ] Create requests with different statuses
- [ ] Send GET with `?status=pending`
  - Verify: Returns only pending requests

- [ ] Send GET with `?search=Smith`
  - Verify: Returns only requests with "Smith" in title

#### Test 6: View Single Request (Owner)
- [ ] Send GET to `/api/content-requests/{id}` with owner's token
- [ ] Expect: 200 OK
- [ ] Verify: Full request details returned

#### Test 7: View Single Request (Not Owner)
- [ ] Send GET to `/api/content-requests/{id}` with different user's token
- [ ] Expect: 403 Forbidden
- [ ] Error message: "You can only view your own content requests"

#### Test 8: Delete Pending Request
- [ ] Create a pending request
- [ ] Send DELETE to `/api/content-requests/{id}` with owner's token
- [ ] Expect: 200 OK
- [ ] Verify: Request deleted from database

#### Test 9: Delete Non-Pending Request
- [ ] Create a request and update status to `fulfilled` (via database or admin)
- [ ] Send DELETE with owner's token
- [ ] Expect: 422 Unprocessable Entity
- [ ] Error message: "Only pending requests can be deleted"

#### Test 10: Delete Not Owner
- [ ] Send DELETE to another user's request
- [ ] Expect: 403 Forbidden

---

### C. Admin Endpoint Tests

#### Test 11: View Statistics
- [ ] Create multiple requests with different statuses
- [ ] Send GET to `/api/admin/content-requests/stats` with admin token
- [ ] Expect: 200 OK
- [ ] Verify: `total` count is accurate
- [ ] Verify: `by_status` counts match database
- [ ] Verify: `by_type.case` equals total (Phase 1)
- [ ] Verify: `fulfillment_rate` is calculated correctly

#### Test 12: Statistics - No Admin Access
- [ ] Send GET to stats endpoint with regular user token
- [ ] Expect: 403 Forbidden

#### Test 13: Find Duplicates
- [ ] Create 3+ requests with same title
- [ ] Send GET to `/api/admin/content-requests/duplicates` with admin token
- [ ] Expect: 200 OK
- [ ] Verify: Duplicate group appears in response
- [ ] Verify: `request_count` matches actual count

#### Test 14: List All Requests
- [ ] Create requests from multiple users
- [ ] Send GET to `/api/admin/content-requests` with admin token
- [ ] Expect: 200 OK
- [ ] Verify: Returns requests from ALL users
- [ ] Verify: Can see other users' requests

#### Test 15: List with Admin Filters
- [ ] Send GET with `?user_id={userId}`
  - Verify: Returns only that user's requests

- [ ] Send GET with `?status=pending&type=case`
  - Verify: Returns only pending case requests

#### Test 16: View Any Request (Admin)
- [ ] Send GET to `/api/admin/content-requests/{id}` for any user's request
- [ ] Expect: 200 OK
- [ ] Verify: Can view requests from any user

#### Test 17: Update Status to In Progress
- [ ] Create a pending request
- [ ] Send PUT with `{"status": "in_progress"}`
- [ ] Expect: 200 OK
- [ ] Verify: Database status updated
- [ ] Verify: Response shows new status

#### Test 18: Fulfill Request (Link Content)
- [ ] Create a pending request
- [ ] Create a court case (via admin endpoint or database)
- [ ] Send PUT with:
  ```json
  {
    "status": "fulfilled",
    "created_content_type": "App\\Models\\CourtCase",
    "created_content_id": {caseId}
  }
  ```
- [ ] Expect: 200 OK
- [ ] Verify: status = `fulfilled`
- [ ] Verify: `created_content_type` and `created_content_id` saved
- [ ] Verify: `fulfilled_by` = admin's ID
- [ ] Verify: `fulfilled_at` timestamp present

#### Test 19: Fulfill Without Content Link (Should Fail)
- [ ] Send PUT with `{"status": "fulfilled"}` only (no content link)
- [ ] Expect: 422 Validation Error
- [ ] Error message: "You must link created content when marking request as fulfilled"

#### Test 20: Reject Request
- [ ] Create a pending request
- [ ] Send PUT with:
  ```json
  {
    "status": "rejected",
    "rejection_reason": "This case already exists in our database"
  }
  ```
- [ ] Expect: 200 OK
- [ ] Verify: status = `rejected`
- [ ] Verify: `rejection_reason` saved
- [ ] Verify: `rejected_by` = admin's ID
- [ ] Verify: `rejected_at` timestamp present

#### Test 21: Reject Without Reason
- [ ] Send PUT with `{"status": "rejected"}` only
- [ ] Expect: 200 OK (rejection reason is optional)
- [ ] Verify: status = `rejected`, reason = null

#### Test 22: Invalid Content Type
- [ ] Send PUT with `{"created_content_type": "InvalidModel"}`
- [ ] Expect: 422 Validation Error

#### Test 23: Non-Existent Content ID
- [ ] Send PUT with valid type but non-existent ID
  ```json
  {
    "created_content_type": "App\\Models\\CourtCase",
    "created_content_id": 999999
  }
  ```
- [ ] Expect: 422 Validation Error
- [ ] Error message: "The specified content does not exist"

#### Test 24: Admin Delete Any Request
- [ ] Create request as User A
- [ ] Send DELETE with admin token
- [ ] Expect: 200 OK
- [ ] Verify: Request deleted

---

### D. Business Logic Tests

#### Test 25: Request Lifecycle - Fulfill Path
- [ ] Create pending request (User)
- [ ] Update to in_progress (Admin)
- [ ] Create case (Admin)
- [ ] Link case and fulfill (Admin)
- [ ] Verify complete lifecycle in database

#### Test 26: Request Lifecycle - Reject Path
- [ ] Create pending request (User)
- [ ] Reject with reason (Admin)
- [ ] Verify user cannot delete rejected request
- [ ] Verify rejection visible to user

#### Test 27: Ownership Rules
- [ ] User A creates request
- [ ] User B tries to view/delete
- [ ] Expect: 403 Forbidden for both
- [ ] Admin can view and modify

#### Test 28: Deletion Rules
- [ ] Create request in each status (pending, in_progress, fulfilled, rejected)
- [ ] User can only delete pending
- [ ] Admin can delete any

#### Test 29: Duplicate Detection Accuracy
- [ ] Create 5 requests with titles:
  - "Smith v Jones"
  - "Smith v Jones" (exact duplicate)
  - "Smith v Jones Ltd"
  - "Johnson v Smith"
  - "Smith v Jones" (third duplicate)
- [ ] Call `/api/admin/content-requests/duplicates`
- [ ] Verify: "Smith v Jones" group shows count = 3
- [ ] Verify: Other requests not flagged

#### Test 30: Pagination
- [ ] Create 25 content requests
- [ ] Send GET with `?per_page=10`
  - Verify: Returns 10 items
  - Verify: `last_page` = 3

- [ ] Send GET with `?per_page=150`
  - Verify: Returns max 100 items (system limit)

---

### E. Data Integrity Tests

#### Test 31: Foreign Key - User Deletion
- [ ] Create content request
- [ ] Attempt to delete user (via database)
- [ ] Expect: Cascade delete (request also deleted)
- [ ] OR Verify: Foreign key constraint prevents deletion

#### Test 32: Polymorphic Relationship
- [ ] Fulfill request with case
- [ ] Query request's `createdContent` relationship
- [ ] Verify: Returns CourtCase model instance
- [ ] Verify: Case data accessible

#### Test 33: Transaction Rollback
- [ ] Simulate error during request creation (mock service failure)
- [ ] Verify: No partial data in database
- [ ] Verify: Transaction rolled back

#### Test 34: Concurrent Updates
- [ ] Two admins update same request simultaneously
- [ ] Verify: No data corruption
- [ ] Verify: Last update wins (expected behavior)

---

## Sample Test Scenarios

### Scenario 1: User Journey - Successful Request

**Goal**: Test complete user flow from submission to fulfillment

**Steps**:

1. **User Submits Request**
   - Login as verified user
   - POST `/api/content-requests`
   ```json
   {
     "type": "case",
     "title": "Brown v Board of Education [1954] US Supreme Court",
     "additional_notes": "Need for civil rights law research"
   }
   ```
   - Save the returned `id`

2. **User Views Request**
   - GET `/api/content-requests/{id}`
   - Verify: status = `pending`

3. **Admin Reviews**
   - Login as admin
   - GET `/api/admin/content-requests?status=pending`
   - Locate the request

4. **Admin Creates Case**
   - POST `/api/admin/cases`
   - Create the case in database
   - Save the case `id`

5. **Admin Fulfills Request**
   - PUT `/api/admin/content-requests/{requestId}`
   ```json
   {
     "status": "fulfilled",
     "created_content_type": "App\\Models\\CourtCase",
     "created_content_id": {caseId}
   }
   ```

6. **User Sees Fulfilled**
   - As user, GET `/api/content-requests/{id}`
   - Verify: status = `fulfilled`
   - Verify: `created_content` object present

**Expected Results**:
- ✅ Request created successfully
- ✅ User can only see own request
- ✅ Admin can see and update request
- ✅ Status changes reflected immediately
- ✅ Content linked correctly

---

### Scenario 2: Admin Journey - Reject Duplicate

**Goal**: Admin identifies and rejects duplicate request

**Steps**:

1. **Multiple Users Request Same Case**
   - User A: POST request for "Smith v Jones"
   - User B: POST request for "Smith v Jones"
   - User C: POST request for "Smith v Jones"

2. **Admin Checks Duplicates**
   - GET `/api/admin/content-requests/duplicates`
   - Verify: "Smith v Jones" shows count = 3

3. **Admin Fulfills One**
   - Create case for "Smith v Jones"
   - Fulfill User A's request with link to case

4. **Admin Rejects Duplicates**
   - Reject User B's request:
   ```json
   {
     "status": "rejected",
     "rejection_reason": "This case has already been added to the database. See case ID 5109."
   }
   ```
   - Reject User C's request with same reason

5. **Users Check Status**
   - User A sees: fulfilled with link to case
   - User B sees: rejected with reason
   - User C sees: rejected with reason

**Expected Results**:
- ✅ Duplicate detection works
- ✅ Admin can fulfill one and reject others
- ✅ Rejection reasons visible to users
- ✅ No duplicate content created

---

### Scenario 3: Security - Unauthorized Access

**Goal**: Verify authorization works correctly

**Steps**:

1. **User A Creates Request**
   - Login as User A
   - POST `/api/content-requests`
   - Save request ID

2. **User B Tries to View**
   - Login as User B
   - GET `/api/content-requests/{userARequestId}`
   - Expect: 403 Forbidden

3. **User B Tries to Delete**
   - DELETE `/api/content-requests/{userARequestId}`
   - Expect: 403 Forbidden

4. **Regular User Tries Admin Endpoint**
   - Login as regular user (not admin)
   - GET `/api/admin/content-requests/stats`
   - Expect: 403 Forbidden

5. **Admin Can Access**
   - Login as admin
   - GET `/api/admin/content-requests/{userARequestId}`
   - Expect: 200 OK

**Expected Results**:
- ✅ Users isolated to own requests
- ✅ Admin endpoints require admin role
- ✅ Proper 403 Forbidden responses
- ✅ Admin has full access

---

### Scenario 4: Validation - Invalid Data

**Goal**: Test validation rules comprehensively

**Steps**:

1. **Missing Required Field**
   - POST `/api/content-requests` with `{"type": "case"}` only
   - Expect: 422 with error on `title`

2. **Invalid Type**
   - POST with `{"type": "invalid", "title": "Test"}`
   - Expect: 422 with error message about valid types

3. **Title Too Long**
   - POST with title = 600 character string
   - Expect: 422 with "must not exceed 500 characters"

4. **Notes Too Long**
   - POST with additional_notes = 2500 character string
   - Expect: 422 with "must not exceed 2000 characters"

5. **XSS Attempt**
   - POST with title = `"<script>alert('xss')</script>Test Case"`
   - Verify: Script tags stripped, saved as "Test Case"

6. **Admin Update - Missing Content on Fulfill**
   - PUT with `{"status": "fulfilled"}` only
   - Expect: 422 with error about required content link

**Expected Results**:
- ✅ All validation rules enforced
- ✅ Clear error messages
- ✅ Input sanitization works
- ✅ XSS prevention active

---

## Test Data Examples

### Valid Case Request
```json
{
  "type": "case",
  "title": "Smith v Jones [2023] EWCA Civ 123",
  "additional_notes": "Key case on contract formation, needed for dissertation"
}
```

### Valid Statute Request (Future Phase)
```json
{
  "type": "statute",
  "title": "Data Protection Act 2024",
  "additional_notes": "New legislation, not yet in database"
}
```

### Valid Provision Request (Future Phase)
```json
{
  "type": "provision",
  "title": "Section 5 - Right to Privacy",
  "statute_id": 42,
  "additional_notes": "Specific section needed for research"
}
```

### Admin Fulfill Update
```json
{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 5109
}
```

### Admin Reject Update
```json
{
  "status": "rejected",
  "rejection_reason": "This case already exists in our database under a different citation. See Smith v Jones Ltd [2023] EWCA Civ 100."
}
```

### Admin Status Change
```json
{
  "status": "in_progress"
}
```

---

## Expected Behaviors

### What Should Work

#### User Operations
- ✅ Create case requests with valid data
- ✅ View own requests (list and single)
- ✅ Filter and search own requests
- ✅ Delete own pending requests
- ✅ See rejection reasons for own rejected requests
- ✅ Pagination through many requests

#### Admin Operations
- ✅ View all requests from all users
- ✅ Advanced filtering (by user, status, type)
- ✅ Update request status (pending → in_progress)
- ✅ Fulfill requests by linking cases
- ✅ Reject requests with optional reasons
- ✅ Delete any request
- ✅ View accurate statistics
- ✅ Identify duplicate requests
- ✅ Calculate fulfillment rates

#### System Behavior
- ✅ Input validation on all endpoints
- ✅ Authorization checks (ownership, role-based)
- ✅ Database transactions (rollback on error)
- ✅ Foreign key constraints
- ✅ Polymorphic relationships
- ✅ XSS protection (input sanitization)
- ✅ Error logging
- ✅ Proper HTTP status codes

### What Won't Work (Not Implemented)

#### Email Notifications
- ❌ User doesn't receive confirmation email on request submission
- ❌ Admins don't receive email about new requests
- ❌ User doesn't receive email on fulfillment
- ❌ User doesn't receive email on rejection
- ❌ No status change notifications

**Workaround**: Users must manually check request status via API

#### Auto-Linking
- ❌ Creating a case doesn't automatically link to pending request
- ❌ Admin must manually fulfill request after creating case

**Workaround**: Two-step process (create case, then update request)

#### Phase 2 Features
- ❌ Cannot request statutes, provisions, or divisions yet
- ❌ `statute_id` field not actively used (reserved for Phase 2)

---

## Verification Methods

### Database Verification

**Check Request Created**:
```sql
SELECT * FROM content_requests
WHERE user_id = {userId}
ORDER BY created_at DESC
LIMIT 1;
```

**Check Status Updated**:
```sql
SELECT id, status, fulfilled_by, fulfilled_at, rejected_by, rejected_at
FROM content_requests
WHERE id = {requestId};
```

**Check Content Linked**:
```sql
SELECT created_content_type, created_content_id
FROM content_requests
WHERE id = {requestId};
```

**Verify Statistics**:
```sql
-- Total count
SELECT COUNT(*) FROM content_requests;

-- By status
SELECT status, COUNT(*)
FROM content_requests
GROUP BY status;

-- Fulfillment rate
SELECT
  COUNT(CASE WHEN status = 'fulfilled' THEN 1 END) * 100.0 / COUNT(*) as rate
FROM content_requests;
```

### API Response Verification

**Check Response Structure**:
```json
{
  "status": "success",
  "message": "...",
  "data": {
    "content_request": {
      "id": 1,
      "type": "case",
      "type_name": "Case",
      "title": "...",
      "status": "pending",
      "status_name": "Pending",
      "user": {...},
      "can_edit": false,
      "can_delete": true,
      "created_at": "2025-10-20T10:30:00.000000Z",
      "updated_at": "2025-10-20T10:30:00.000000Z"
    }
  }
}
```

**Verify Permissions**:
- `can_edit`: Should always be `false` (requests are immutable)
- `can_delete`: `true` only if status = `pending` and user is owner

### Log Verification

Check Laravel logs for errors:
```bash
tail -f storage/logs/laravel.log
```

Look for:
- `Error creating content request:` - Creation failures
- `Error retrieving content requests:` - Query failures
- `Admin error updating content request:` - Update failures

---

## Common Issues & Troubleshooting

### Issue: 401 Unauthorized

**Symptom**: All requests return 401

**Causes**:
- No authorization token provided
- Token expired
- Token invalid

**Solutions**:
1. Verify token included in request header: `Authorization: Bearer {token}`
2. Re-login to get fresh token
3. Check token format (should be long string, ~200 characters)

**Test**:
```bash
# Verify token works
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/user/profile
```

---

### Issue: 403 Forbidden

**Symptom**: Request rejected with "You can only view your own content requests"

**Causes**:
- Trying to access another user's request
- User account not verified
- User doesn't have required role (for admin endpoints)

**Solutions**:
1. Verify ownership: Check `user_id` in database matches token user
2. Verify email: Check user's `email_verified_at` is not null
3. Check role: Admin endpoints require `admin`, `researcher`, or `superadmin` role

**Test**:
```bash
# Check user details
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/user/profile
```

---

### Issue: 422 Validation Error

**Symptom**: "Please provide a title for the requested content"

**Causes**:
- Missing required fields
- Invalid field values
- Field exceeds max length

**Solutions**:
1. Check request body includes all required fields
2. Verify field types match (string, integer, etc.)
3. Check field lengths (title max 500, notes max 2000)

**Required Fields**:
- `type`: Must be `case`, `statute`, `provision`, or `division`
- `title`: String, 1-500 characters

---

### Issue: "Table 'content_requests' doesn't exist"

**Symptom**: Database error when creating request

**Causes**:
- Migration not run
- Wrong database selected
- Migration failed silently

**Solutions**:
```bash
# Check migration status
php artisan migrate:status

# Run migrations
php artisan migrate

# If already run, check database
mysql -u root -p your_database -e "SHOW TABLES LIKE 'content_requests';"
```

---

### Issue: Routes Not Found

**Symptom**: 404 Not Found for content request endpoints

**Causes**:
- Routes not registered
- Route cache outdated
- Wrong URL path

**Solutions**:
```bash
# Clear route cache
php artisan route:clear

# Verify routes exist
php artisan route:list | grep content-request

# Should show 10 routes
```

---

### Issue: Statistics Show Zero

**Symptom**: `/api/admin/content-requests/stats` returns all zeros

**Causes**:
- No requests in database
- Query error
- Wrong database connection

**Solutions**:
1. Create test request first
2. Check database directly:
   ```sql
   SELECT COUNT(*) FROM content_requests;
   ```
3. Verify database connection in `.env`

---

### Issue: "Only pending requests can be deleted"

**Symptom**: Cannot delete fulfilled/rejected request

**Causes**:
- This is expected behavior (not a bug)

**Solutions**:
- Only pending requests can be deleted by users
- Admins can delete any request via admin endpoint
- To delete non-pending: Use admin endpoint or update status to pending first (not recommended)

---

### Issue: Duplicate Count Incorrect

**Symptom**: `/duplicates` endpoint shows wrong counts

**Causes**:
- Title matching is case-sensitive
- Partial matches not counted
- Only exact title matches

**Solutions**:
- Duplicates are exact title matches only
- Case-sensitive comparison
- Type must also match (case != statute)

**Expected Behavior**:
- "Smith v Jones" and "Smith v Jones" = duplicates ✓
- "Smith v Jones" and "smith v jones" = NOT duplicates (case)
- "Smith v Jones" and "Smith v Jones Ltd" = NOT duplicates (different text)

---

### Issue: Email Notifications Not Working

**Symptom**: No emails received

**Causes**:
- Email notification system not implemented yet (Phase 1)

**Solutions**:
- This is expected - emails are not part of Phase 1
- System works without email notifications
- Users check status manually via API
- Future implementation will add email support

---

## Testing Tools Recommendations

### API Testing
- **Postman**: Create collection with all endpoints, save test data
- **Insomnia**: Lightweight alternative to Postman
- **cURL**: Command-line testing, good for automation
- **Laravel Tinker**: Quick database checks

### Database Inspection
- **TablePlus**: Visual database client (Mac/Windows)
- **MySQL Workbench**: Free MySQL client
- **DBeaver**: Universal database tool
- **phpMyAdmin**: Web-based MySQL management

### Log Monitoring
- **Laravel Telescope**: Real-time debugging (if installed)
- **tail -f storage/logs/laravel.log**: Real-time log viewing
- **Laravel Log Viewer**: Web-based log viewer

### Load Testing (Optional)
- **Apache Bench (ab)**: Simple load testing
- **Postman Runner**: Automated test runs
- **K6**: Modern load testing tool

---

## Quick Start Testing Script

Here's a minimal test sequence to verify basic functionality:

```bash
# 1. Verify setup
php artisan route:list | grep content-request
php artisan migrate:status | grep content_requests

# 2. Get tokens (replace with your auth endpoint)
USER_TOKEN=$(curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  | jq -r '.data.token')

ADMIN_TOKEN=$(curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.data.token')

# 3. Create request (User)
REQUEST_ID=$(curl -X POST http://localhost:8000/api/content-requests \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"case","title":"Test Case [2025]"}' \
  | jq -r '.data.content_request.id')

echo "Created request ID: $REQUEST_ID"

# 4. View request (User)
curl -X GET http://localhost:8000/api/content-requests/$REQUEST_ID \
  -H "Authorization: Bearer $USER_TOKEN"

# 5. View stats (Admin)
curl -X GET http://localhost:8000/api/admin/content-requests/stats \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 6. List all (Admin)
curl -X GET "http://localhost:8000/api/admin/content-requests?per_page=5" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

echo "✅ Basic tests complete!"
```

---

## Final Notes

### Test Coverage

This guide covers:
- ✅ All 10 API endpoints
- ✅ Authentication and authorization
- ✅ Validation rules
- ✅ Business logic (status changes, linking, etc.)
- ✅ Database integrity
- ✅ Error handling

### Not Covered (Future)

- ⏳ Email delivery testing
- ⏳ Performance/load testing
- ⏳ Integration with frontend
- ⏳ Automated test suite (PHPUnit)

### Reporting Issues

When reporting bugs, include:
1. Endpoint tested
2. Request payload (sanitize sensitive data)
3. Expected result
4. Actual result
5. HTTP status code
6. Error message (if any)
7. Database state (if relevant)

### Success Criteria

Phase 1 testing is successful when:
- ✅ All 10 endpoints respond correctly
- ✅ Users can create and manage own requests
- ✅ Admins can manage all requests
- ✅ Statistics are accurate
- ✅ Authorization works correctly
- ✅ Validation prevents invalid data
- ✅ Database maintains integrity

---

**Happy Testing! 🚀**
