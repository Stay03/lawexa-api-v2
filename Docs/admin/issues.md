# Issues Management API

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
- **user** - Regular user (can manage their own issues)
- **admin** - Administrator (full issue management, AI analysis)
- **researcher** - Research access (full issue management, AI analysis)
- **superadmin** - Full system access (full issue management, delete issues)

### Access Matrix

| Action | User | Admin | Researcher | Superadmin |
|--------|------|-------|------------|------------|
| View own issues | ✅ | ✅ | ✅ | ✅ |
| Create issues | ✅ | ✅ | ✅ | ✅ |
| Edit own issues | ✅ | ✅ | ✅ | ✅ |
| Delete own issues | ✅ | ✅ | ✅ | ✅ |
| View all issues | ❌ | ✅ | ✅ | ✅ |
| Edit any issue | ❌ | ✅ | ✅ | ✅ |
| Delete any issue | ❌ | ❌ | ❌ | ✅ |
| AI Analysis | ❌ | ✅ | ✅ | ✅ |
| Issue Statistics | ❌ | ✅ | ✅ | ✅ |
| Assign Issues | ❌ | ✅ | ✅ | ✅ |

### Routing Structure

**User Endpoints**: Use ID-based URLs for user's own issues
- `/api/issues/{id}` - Numeric IDs with ownership validation

**Admin Endpoints**: Use ID-based URLs for admin operations  
- `/api/admin/issues/{id}` - Numeric IDs for admin operations

---

## User Endpoints

### 1. Get Issues List (User)

**GET** `/issues`

Retrieves a paginated list of the authenticated user's issues with filtering capabilities.

#### Required Permissions
- Authenticated user (any role)

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | - | Filter by status (open, in_progress, resolved, closed, duplicate) |
| `type` | string | No | - | Filter by type (bug, feature_request, improvement, other) |
| `severity` | string | No | - | Filter by severity (low, medium, high, critical) |
| `area` | string | No | - | Filter by area (frontend, backend, both) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /issues?area=both&severity=low
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issues retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Test Issue - Login Button Fixed (Updated)",
      "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
      "type": "bug",
      "severity": "low",
      "priority": "low",
      "status": "open",
      "area": "both",
      "category": "authentication",
      "browser_info": {
        "browser": "Chrome",
        "version": "118.0.0.0",
        "os": "Windows 11"
      },
      "environment_info": {
        "environment": "production",
        "version": "v2.1.0"
      },
      "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
      "expected_behavior": "Should redirect to dashboard after successful login",
      "actual_behavior": "Button click has no effect, stays on login page",
      "files": [],
      "screenshots": [],
      "resolved_at": null,
      "created_at": "2025-07-31T00:47:55.000000Z",
      "updated_at": "2025-07-31T00:49:05.000000Z"
    }
  ]
}
```

### 2. Create Issue (User)

**POST** `/issues`

Creates a new issue report with optional file attachments and comprehensive bug details.

#### Required Permissions
- Authenticated user (any role)

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:255 | Issue title |
| `description` | string | Yes | - | Issue description |
| `type` | string | No | in:bug,feature_request,improvement,other | Issue type (default: bug) |
| `severity` | string | No | in:low,medium,high,critical | Issue severity (default: medium) |
| `priority` | string | No | in:low,medium,high,urgent | Issue priority (default: medium) |
| `area` | string | No | in:frontend,backend,both | Issue area (optional) |
| `category` | string | No | max:100 | Issue category (optional) |
| `browser_info` | object | No | - | Browser information (optional) |
| `environment_info` | object | No | - | Environment information (optional) |
| `steps_to_reproduce` | string | No | - | Steps to reproduce the issue |
| `expected_behavior` | string | No | - | Expected behavior description |
| `actual_behavior` | string | No | - | Actual behavior description |
| `file_ids` | array | No | each must exist in files table | Array of file IDs to attach |

#### Example Request
```json
{
  "title": "Test Issue - Login Button Not Working",
  "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
  "type": "bug",
  "severity": "high",
  "area": "frontend",
  "category": "authentication",
  "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
  "expected_behavior": "Should redirect to dashboard after successful login",
  "actual_behavior": "Button click has no effect, stays on login page"
}
```

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Issue created successfully",
  "data": {
    "id": 1,
    "title": "Test Issue - Login Button Not Working",
    "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
    "type": "bug",
    "severity": "high",
    "priority": "medium",
    "status": "open",
    "area": "frontend",
    "category": "authentication",
    "browser_info": null,
    "environment_info": null,
    "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
    "expected_behavior": "Should redirect to dashboard after successful login",
    "actual_behavior": "Button click has no effect, stays on login page",
    "user": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "files": [],
    "screenshots": [],
    "resolved_at": null,
    "created_at": "2025-07-31T00:47:55.000000Z",
    "updated_at": "2025-07-31T00:47:55.000000Z"
  }
}
```

### 3. Get Single Issue (User)

**GET** `/issues/{id}`

Retrieves detailed information about a specific issue owned by the authenticated user.

#### Required Permissions
- Authenticated user (issue owner only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID (numeric identifier) |

#### Example Request
```
GET /issues/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue retrieved successfully",
  "data": {
    "id": 1,
    "title": "Test Issue - Login Button Fixed (Updated)",
    "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
    "type": "bug",
    "severity": "low",
    "priority": "medium",
    "status": "open",
    "area": "both",
    "category": "authentication",
    "browser_info": {
      "browser": "Chrome",
      "version": "118.0.0.0",
      "os": "Windows 11"
    },
    "environment_info": {
      "environment": "production",
      "version": "v2.1.0"
    },
    "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
    "expected_behavior": "Should redirect to dashboard after successful login",
    "actual_behavior": "Button click has no effect, stays on login page",
    "user": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "files": [],
    "screenshots": [],
    "resolved_at": null,
    "created_at": "2025-07-31T00:47:55.000000Z",
    "updated_at": "2025-07-31T00:49:05.000000Z"
  }
}
```

### 4. Update Issue (User)

**PUT** `/issues/{id}`

Updates an issue owned by the authenticated user. Users can only update limited fields on their own issues.

#### Required Permissions
- Authenticated user (issue owner only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID to update |

#### Request Body
All fields are optional for updates:

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `title` | string | max:255 | Issue title |
| `description` | string | - | Issue description |
| `type` | string | in:bug,feature_request,improvement,other | Issue type |
| `severity` | string | in:low,medium,high,critical | Issue severity |
| `priority` | string | in:low,medium,high,urgent | Issue priority |
| `area` | string | in:frontend,backend,both | Issue area |
| `category` | string | max:100 | Issue category |
| `browser_info` | object | - | Browser information |
| `environment_info` | object | - | Environment information |
| `steps_to_reproduce` | string | - | Steps to reproduce |
| `expected_behavior` | string | - | Expected behavior |
| `actual_behavior` | string | - | Actual behavior |
| `file_ids` | array | each must exist in files table | Array of file IDs |

#### Example Request
```json
{
  "title": "Test Issue - Login Button Fixed (Updated)",
  "severity": "low",
  "priority": "low",
  "area": "both",
  "browser_info": {
    "browser": "Chrome",
    "version": "118.0.0.0",
    "os": "Windows 11"
  },
  "environment_info": {
    "environment": "production",
    "version": "v2.1.0"
  }
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue updated successfully",
  "data": {
    "id": 1,
    "title": "Test Issue - Login Button Fixed (Updated)",
    "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
    "type": "bug",
    "severity": "low",
    "priority": "low",
    "status": "open",
    "area": "both",
    "category": "authentication",
    "browser_info": {
      "browser": "Chrome",
      "version": "118.0.0.0",
      "os": "Windows 11"
    },
    "environment_info": {
      "environment": "production",
      "version": "v2.1.0"
    },
    "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
    "expected_behavior": "Should redirect to dashboard after successful login",
    "actual_behavior": "Button click has no effect, stays on login page",
    "user": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "files": [],
    "screenshots": [],
    "resolved_at": null,
    "created_at": "2025-07-31T00:47:55.000000Z",
    "updated_at": "2025-07-31T00:49:05.000000Z"
  }
}
```

### 5. Delete Issue (User)

**DELETE** `/issues/{id}`

Permanently deletes an issue owned by the authenticated user.

#### Required Permissions
- Authenticated user (issue owner only)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID to delete |

#### Example Request
```
DELETE /issues/2
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue deleted successfully",
  "data": null
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
  "message": "You can only view your own issues",
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

### 6. Get Issues Statistics (Admin)

**GET** `/admin/issues/stats`

Retrieves comprehensive statistics and metrics about all issues for dashboard and analytics purposes.

#### Required Permissions
- admin, researcher, or superadmin

#### Example Request
```
GET /admin/issues/stats
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue statistics retrieved successfully",
  "data": {
    "total_issues": 1,
    "open_issues": 1,
    "in_progress_issues": 0,
    "resolved_issues": 0,
    "critical_issues": 0,
    "unassigned_issues": 1,
    "issues_by_area": {
      "both": 1
    },
    "issues_by_type": {
      "bug": 1
    },
    "recent_issues": [
      {
        "id": 1,
        "title": "Test Issue - Login Button Fixed (Updated)",
        "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
        "type": "bug",
        "severity": "low",
        "priority": "low",
        "status": "open",
        "area": "both",
        "category": "authentication",
        "browser_info": {
          "browser": "Chrome",
          "version": "118.0.0.0",
          "os": "Windows 11"
        },
        "environment_info": {
          "environment": "production",
          "version": "v2.1.0"
        },
        "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
        "expected_behavior": "Should redirect to dashboard after successful login",
        "actual_behavior": "Button click has no effect, stays on login page",
        "user": {
          "id": 1,
          "name": "Stay Njokede",
          "email": "njokedestay@gmail.com",
          "role": "admin",
          "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
          "google_id": "104759936895463122466",
          "customer_code": "CUS_a869auzperkrrie",
          "subscription_status": "expired",
          "subscription_expiry": "2025-07-30T02:45:40.000000Z",
          "has_active_subscription": false,
          "plan": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "formatted_amount": "1,500.00",
          "amount": 150000,
          "interval": "daily",
          "active_subscription": {
            "id": 7,
            "subscription_code": "SUB_7bv5cuwb9wzp9n4",
            "status": "active",
            "quantity": 1,
            "amount": 83000,
            "formatted_amount": "830.00",
            "currency": "NGN",
            "start_date": "2025-07-16T02:45:16.000000Z",
            "next_payment_date": "2025-07-30T02:45:40.000000Z",
            "cron_expression": "45 2 * * *",
            "invoice_limit": 0,
            "is_active": false,
            "is_expired": true,
            "can_be_cancelled": true,
            "plan": {
              "id": 14,
              "name": "Professional",
              "plan_code": "PLN_5bc1gpneuno684c",
              "description": null,
              "amount": 150000,
              "formatted_amount": "1,500.00",
              "currency": "NGN",
              "interval": "daily",
              "invoice_limit": 0,
              "send_invoices": true,
              "send_sms": false,
              "hosted_page": false,
              "is_active": true,
              "created_at": "2025-07-13T18:49:09.000000Z",
              "updated_at": "2025-07-18T18:55:20.000000Z"
            },
            "created_at": "2025-07-16T02:45:17.000000Z",
            "updated_at": "2025-07-29T02:45:40.000000Z"
          },
          "email_verified_at": null,
          "created_at": "2025-07-06T20:00:55.000000Z",
          "updated_at": "2025-07-16T02:45:17.000000Z"
        },
        "ai_analysis": null,
        "admin_notes": null,
        "resolved_at": null,
        "created_at": "2025-07-31T00:47:55.000000Z",
        "updated_at": "2025-07-31T00:49:05.000000Z"
      }
    ]
  }
}
```

### 7. Get Issues List (Admin)

**GET** `/admin/issues`

Retrieves a paginated list of all issues with advanced filtering, search, and sorting capabilities.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | - | Filter by status (open, in_progress, resolved, closed, duplicate) |
| `type` | string | No | - | Filter by type (bug, feature_request, improvement, other) |
| `severity` | string | No | - | Filter by severity (low, medium, high, critical) |
| `area` | string | No | - | Filter by area (frontend, backend, both) |
| `assigned_to` | string | No | - | Filter by assigned user ID or "unassigned" |
| `search` | string | No | - | Search in title, description, and user name/email |
| `sort_by` | string | No | created_at | Sort field (created_at, title, severity, status, etc.) |
| `sort_order` | string | No | desc | Sort order (asc, desc) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 20 | Items per page (max: 100) |

#### Example Request
```
GET /admin/issues?status=in_progress&severity=high&search=login&sort_by=created_at&sort_order=desc
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issues retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Test Issue - Login Button Fixed (Updated)",
      "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
      "type": "bug",
      "severity": "low",
      "priority": "high",
      "status": "in_progress",
      "area": "both",
      "category": "authentication",
      "browser_info": {
        "browser": "Chrome",
        "version": "118.0.0.0",
        "os": "Windows 11"
      },
      "environment_info": {
        "environment": "production",
        "version": "v2.1.0"
      },
      "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
      "expected_behavior": "Should redirect to dashboard after successful login",
      "actual_behavior": "Button click has no effect, stays on login page",
      "user": {
        "id": 1,
        "name": "Stay Njokede",
        "email": "njokedestay@gmail.com",
        "role": "admin",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
        "google_id": "104759936895463122466",
        "customer_code": "CUS_a869auzperkrrie",
        "subscription_status": "expired",
        "subscription_expiry": "2025-07-30T02:45:40.000000Z",
        "has_active_subscription": false,
        "plan": "Professional",
        "plan_code": "PLN_5bc1gpneuno684c",
        "formatted_amount": "1,500.00",
        "amount": 150000,
        "interval": "daily",
        "active_subscription": {
          "id": 7,
          "subscription_code": "SUB_7bv5cuwb9wzp9n4",
          "status": "active",
          "quantity": 1,
          "amount": 83000,
          "formatted_amount": "830.00",
          "currency": "NGN",
          "start_date": "2025-07-16T02:45:16.000000Z",
          "next_payment_date": "2025-07-30T02:45:40.000000Z",
          "cron_expression": "45 2 * * *",
          "invoice_limit": 0,
          "is_active": false,
          "is_expired": true,
          "can_be_cancelled": true,
          "plan": {
            "id": 14,
            "name": "Professional",
            "plan_code": "PLN_5bc1gpneuno684c",
            "description": null,
            "amount": 150000,
            "formatted_amount": "1,500.00",
            "currency": "NGN",
            "interval": "daily",
            "invoice_limit": 0,
            "send_invoices": true,
            "send_sms": false,
            "hosted_page": false,
            "is_active": true,
            "created_at": "2025-07-13T18:49:09.000000Z",
            "updated_at": "2025-07-18T18:55:20.000000Z"
          },
          "created_at": "2025-07-16T02:45:17.000000Z",
          "updated_at": "2025-07-29T02:45:40.000000Z"
        },
        "email_verified_at": null,
        "created_at": "2025-07-06T20:00:55.000000Z",
        "updated_at": "2025-07-16T02:45:17.000000Z"
      },
      "assigned_to": {
        "id": 1,
        "name": "Stay Njokede",
        "email": "njokedestay@gmail.com",
        "role": "admin",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
        "google_id": "104759936895463122466",
        "customer_code": "CUS_a869auzperkrrie",
        "subscription_status": "expired",
        "subscription_expiry": "2025-07-30T02:45:40.000000Z",
        "has_active_subscription": false,
        "plan": "Professional",
        "plan_code": "PLN_5bc1gpneuno684c",
        "formatted_amount": "1,500.00",
        "amount": 150000,
        "interval": "daily",
        "active_subscription": {
          "id": 7,
          "subscription_code": "SUB_7bv5cuwb9wzp9n4",
          "status": "active",
          "quantity": 1,
          "amount": 83000,
          "formatted_amount": "830.00",
          "currency": "NGN",
          "start_date": "2025-07-16T02:45:16.000000Z",
          "next_payment_date": "2025-07-30T02:45:40.000000Z",
          "cron_expression": "45 2 * * *",
          "invoice_limit": 0,
          "is_active": false,
          "is_expired": true,
          "can_be_cancelled": true,
          "plan": {
            "id": 14,
            "name": "Professional",
            "plan_code": "PLN_5bc1gpneuno684c",
            "description": null,
            "amount": 150000,
            "formatted_amount": "1,500.00",
            "currency": "NGN",
            "interval": "daily",
            "invoice_limit": 0,
            "send_invoices": true,
            "send_sms": false,
            "hosted_page": false,
            "is_active": true,
            "created_at": "2025-07-13T18:49:09.000000Z",
            "updated_at": "2025-07-18T18:55:20.000000Z"
          },
          "created_at": "2025-07-16T02:45:17.000000Z",
          "updated_at": "2025-07-29T02:45:40.000000Z"
        },
        "email_verified_at": null,
        "created_at": "2025-07-06T20:00:55.000000Z",
        "updated_at": "2025-07-16T02:45:17.000000Z"
      },
      "files": [],
      "screenshots": [],
      "resolved_at": null,
      "created_at": "2025-07-31T00:47:55.000000Z",
      "updated_at": "2025-07-31T00:50:24.000000Z"
    }
  ]
}
```

### 8. Get Single Issue (Admin)

**GET** `/admin/issues/{id}`

Retrieves detailed information about any issue with admin-specific fields including AI analysis and admin notes.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID (numeric identifier) |

#### Example Request
```
GET /admin/issues/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue retrieved successfully",
  "data": {
    "id": 1,
    "title": "Test Issue - Login Button Fixed (Updated)",
    "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
    "type": "bug",
    "severity": "low",
    "priority": "high",
    "status": "in_progress",
    "area": "both",
    "category": "authentication",
    "browser_info": {
      "browser": "Chrome",
      "version": "118.0.0.0",
      "os": "Windows 11"
    },
    "environment_info": {
      "environment": "production",
      "version": "v2.1.0"
    },
    "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
    "expected_behavior": "Should redirect to dashboard after successful login",
    "actual_behavior": "Button click has no effect, stays on login page",
    "user": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "assigned_to": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "files": [],
    "screenshots": [],
    "ai_analysis": "AI Analysis for Issue #1:\n\nTitle: Test Issue - Login Button Fixed (Updated)\nType: bug\nSeverity: low\nArea: both\n\nDescription Analysis:\nThis appears to be a bug issue affecting the both area. Based on the severity level (low), this can be addressed when resources are available.\n\nRecommended Actions:\n1. Verify the issue reproduction steps\n2. Check similar past issues for patterns\n3. Assign to appropriate team member based on area\n4. Set up monitoring if this is a recurring issue\n",
    "admin_notes": "Issue has been assigned to the frontend team. Will investigate the login button click event handlers.",
    "resolved_at": null,
    "created_at": "2025-07-31T00:47:55.000000Z",
    "updated_at": "2025-07-31T00:50:37.000000Z"
  }
}
```

### 9. Update Issue (Admin)

**PUT** `/admin/issues/{id}`

Updates any issue with full admin capabilities including status management, assignment, and admin notes.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID to update |

#### Request Body
All fields are optional for updates:

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `title` | string | max:255 | Issue title |
| `description` | string | - | Issue description |
| `type` | string | in:bug,feature_request,improvement,other | Issue type |
| `severity` | string | in:low,medium,high,critical | Issue severity |
| `priority` | string | in:low,medium,high,urgent | Issue priority |
| `status` | string | in:open,in_progress,resolved,closed,duplicate | Issue status |
| `area` | string | in:frontend,backend,both | Issue area |
| `category` | string | max:100 | Issue category |
| `browser_info` | object | - | Browser information |
| `environment_info` | object | - | Environment information |
| `steps_to_reproduce` | string | - | Steps to reproduce |
| `expected_behavior` | string | - | Expected behavior |
| `actual_behavior` | string | - | Actual behavior |
| `assigned_to` | integer | exists:users,id | Assign to user ID |
| `ai_analysis` | string | - | AI analysis text |
| `admin_notes` | string | - | Admin notes |
| `file_ids` | array | each must exist in files table | Array of file IDs |

#### Example Request
```json
{
  "status": "in_progress",
  "assigned_to": 1,
  "admin_notes": "Issue has been assigned to the frontend team. Will investigate the login button click event handlers.",
  "priority": "high"
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue updated successfully",
  "data": {
    "id": 1,
    "title": "Test Issue - Login Button Fixed (Updated)",
    "description": "When I click the login button, nothing happens. The page doesn't redirect and no error message is shown.",
    "type": "bug",
    "severity": "low",
    "priority": "high",
    "status": "in_progress",
    "area": "both",
    "category": "authentication",
    "browser_info": {
      "browser": "Chrome",
      "version": "118.0.0.0",
      "os": "Windows 11"
    },
    "environment_info": {
      "environment": "production",
      "version": "v2.1.0"
    },
    "steps_to_reproduce": "1. Go to login page\n2. Enter valid credentials\n3. Click login button\n4. Nothing happens",
    "expected_behavior": "Should redirect to dashboard after successful login",
    "actual_behavior": "Button click has no effect, stays on login page",
    "user": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "assigned_to": {
      "id": 1,
      "name": "Stay Njokede",
      "email": "njokedestay@gmail.com",
      "role": "admin",
      "avatar": "https://lh3.googleusercontent.com/a/ACg8ocKC_f_xaqhTn0S44tbuCckQV-TKQLe2IbZgJ_TJXVAEj6w5Qw=s96-c",
      "google_id": "104759936895463122466",
      "customer_code": "CUS_a869auzperkrrie",
      "subscription_status": "expired",
      "subscription_expiry": "2025-07-30T02:45:40.000000Z",
      "has_active_subscription": false,
      "plan": "Professional",
      "plan_code": "PLN_5bc1gpneuno684c",
      "formatted_amount": "1,500.00",
      "amount": 150000,
      "interval": "daily",
      "active_subscription": {
        "id": 7,
        "subscription_code": "SUB_7bv5cuwb9wzp9n4",
        "status": "active",
        "quantity": 1,
        "amount": 83000,
        "formatted_amount": "830.00",
        "currency": "NGN",
        "start_date": "2025-07-16T02:45:16.000000Z",
        "next_payment_date": "2025-07-30T02:45:40.000000Z",
        "cron_expression": "45 2 * * *",
        "invoice_limit": 0,
        "is_active": false,
        "is_expired": true,
        "can_be_cancelled": true,
        "plan": {
          "id": 14,
          "name": "Professional",
          "plan_code": "PLN_5bc1gpneuno684c",
          "description": null,
          "amount": 150000,
          "formatted_amount": "1,500.00",
          "currency": "NGN",
          "interval": "daily",
          "invoice_limit": 0,
          "send_invoices": true,
          "send_sms": false,
          "hosted_page": false,
          "is_active": true,
          "created_at": "2025-07-13T18:49:09.000000Z",
          "updated_at": "2025-07-18T18:55:20.000000Z"
        },
        "created_at": "2025-07-16T02:45:17.000000Z",
        "updated_at": "2025-07-29T02:45:40.000000Z"
      },
      "email_verified_at": null,
      "created_at": "2025-07-06T20:00:55.000000Z",
      "updated_at": "2025-07-16T02:45:17.000000Z"
    },
    "files": [],
    "screenshots": [],
    "ai_analysis": null,
    "admin_notes": "Issue has been assigned to the frontend team. Will investigate the login button click event handlers.",
    "resolved_at": null,
    "created_at": "2025-07-31T00:47:55.000000Z",
    "updated_at": "2025-07-31T00:50:24.000000Z"
  }
}
```

### 10. Generate AI Analysis (Admin)

**POST** `/admin/issues/{id}/ai-analyze`

Generates AI-powered analysis and recommendations for an issue, providing insights and suggested actions.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID to analyze |

#### Example Request
```
POST /admin/issues/1/ai-analyze
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "AI analysis completed successfully",
  "data": {
    "ai_analysis": "AI Analysis for Issue #1:\n\nTitle: Test Issue - Login Button Fixed (Updated)\nType: bug\nSeverity: low\nArea: both\n\nDescription Analysis:\nThis appears to be a bug issue affecting the both area. Based on the severity level (low), this can be addressed when resources are available.\n\nRecommended Actions:\n1. Verify the issue reproduction steps\n2. Check similar past issues for patterns\n3. Assign to appropriate team member based on area\n4. Set up monitoring if this is a recurring issue\n"
  }
}
```

### 11. Delete Issue (Admin)

**DELETE** `/admin/issues/{id}`

Permanently deletes any issue. This action is restricted to superadmin users only.

#### Required Permissions
- superadmin only

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Issue ID to delete |

#### Example Request
```
DELETE /admin/issues/3
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Issue deleted successfully",
  "data": null
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
    "title": ["Issue title is required."],
    "description": ["Issue description is required."],
    "type": ["Invalid issue type selected."],
    "severity": ["Invalid severity level selected."],
    "priority": ["Invalid priority level selected."],
    "area": ["Invalid area selected."],
    "assigned_to": ["The selected user does not exist."],
    "file_ids.0": ["One or more selected files do not exist."]
  }
}
```

---

## Data Models

### Issue Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Issue unique identifier |
| `title` | string | No | Issue title |
| `description` | string | No | Issue description |
| `type` | string | No | Issue type (bug, feature_request, improvement, other) |
| `severity` | string | No | Issue severity (low, medium, high, critical) |
| `priority` | string | No | Issue priority (low, medium, high, urgent) |
| `status` | string | No | Issue status (open, in_progress, resolved, closed, duplicate) |
| `area` | string | Yes | Issue area (frontend, backend, both) |
| `category` | string | Yes | Issue category |
| `browser_info` | object | Yes | Browser information (JSON object) |
| `environment_info` | object | Yes | Environment information (JSON object) |
| `steps_to_reproduce` | string | Yes | Steps to reproduce the issue |
| `expected_behavior` | string | Yes | Expected behavior description |
| `actual_behavior` | string | Yes | Actual behavior description |
| `user` | object | No | Issue reporter user information |
| `assigned_to` | object | Yes | Assigned user information (admin only) |
| `files` | array | No | Array of attached files |
| `screenshots` | array | No | Array of attached image files |
| `ai_analysis` | string | Yes | AI-generated analysis (admin only) |
| `admin_notes` | string | Yes | Admin notes (admin only) |
| `resolved_at` | string | Yes | ISO timestamp when issue was resolved |
| `created_at` | string | No | ISO timestamp of creation |
| `updated_at` | string | No | ISO timestamp of last update |

### User Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | User ID |
| `name` | string | No | User full name |
| `email` | string | No | User email address |
| `role` | string | No | User role (user, admin, researcher, superadmin) |
| `avatar` | string | Yes | User avatar URL |
| `google_id` | string | Yes | Google OAuth ID |
| `customer_code` | string | Yes | Payment provider customer code |
| `subscription_status` | string | No | Current subscription status |
| `subscription_expiry` | string | Yes | Subscription expiry date |
| `has_active_subscription` | boolean | No | Whether user has active subscription |
| `plan` | string | Yes | Current subscription plan name |
| `plan_code` | string | Yes | Current subscription plan code |
| `formatted_amount` | string | Yes | Formatted subscription amount |
| `amount` | integer | Yes | Subscription amount in kobo/cents |
| `interval` | string | Yes | Subscription billing interval |
| `active_subscription` | object | Yes | Active subscription details |
| `email_verified_at` | string | Yes | Email verification timestamp |
| `created_at` | string | No | User creation timestamp |
| `updated_at` | string | No | User last update timestamp |

### File Object

**Note**: Files attached to issues are screenshots and documents that supplement the issue report.

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | File ID |
| `name` | string | No | Original filename |
| `filename` | string | No | UUID-based stored filename |
| `size` | integer | No | File size in bytes |
| `human_size` | string | No | Human-readable file size |
| `mime_type` | string | No | MIME type of the file |
| `extension` | string | No | File extension |
| `category` | string | No | File category (for issues: "general" or "case_reports") |
| `url` | string | No | Direct access URL |
| `download_url` | string | No | Signed download URL (expires in 1 hour) |
| `is_image` | boolean | No | Whether file is an image |
| `is_document` | boolean | No | Whether file is a document |
| `disk` | string | No | Storage disk (always "s3" for new uploads) |
| `metadata` | object | No | Upload metadata |
| `attached_to` | object | No | Parent issue information |
| `uploaded_by` | object | No | User who uploaded the file |
| `created_at` | string | No | File upload timestamp |
| `updated_at` | string | No | File last update timestamp |

### Statistics Object

| Field | Type | Description |
|-------|------|-------------|
| `total_issues` | integer | Total number of issues |
| `open_issues` | integer | Number of open issues |
| `in_progress_issues` | integer | Number of in-progress issues |
| `resolved_issues` | integer | Number of resolved issues |
| `critical_issues` | integer | Number of critical severity issues |
| `unassigned_issues` | integer | Number of unassigned issues |
| `issues_by_area` | object | Issue count breakdown by area |
| `issues_by_type` | object | Issue count breakdown by type |
| `recent_issues` | array | Array of 5 most recent issues |

---

## Common Use Cases

### Search Issues
```
GET /issues?search=login
GET /admin/issues?search=login
```

### Filter by Area
```
GET /issues?area=frontend
GET /admin/issues?area=backend
```

### Filter by Severity
```
GET /issues?severity=critical
GET /admin/issues?severity=high
```

### Filter by Status
```
GET /issues?status=open
GET /admin/issues?status=in_progress
```

### Filter by Assignment (Admin Only)
```
GET /admin/issues?assigned_to=1
GET /admin/issues?assigned_to=unassigned
```

### Combined Filters
```
GET /issues?area=frontend&severity=high&status=open
GET /admin/issues?area=backend&assigned_to=unassigned&severity=critical&sort_by=created_at&sort_order=desc
```

### Issue Management Workflow
```bash
# 1. User creates an issue
curl -X POST "http://127.0.0.1:8000/api/issues" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "API Response Time Slow",
    "description": "The API responses are taking longer than 5 seconds",
    "type": "bug",
    "severity": "critical",
    "area": "backend",
    "category": "performance"
  }'

# 2. Admin assigns the issue
curl -X PUT "http://127.0.0.1:8000/api/admin/issues/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress",
    "assigned_to": 5,
    "priority": "urgent",
    "admin_notes": "Critical performance issue. Assigned to backend team for immediate investigation."
  }'

# 3. Generate AI analysis
curl -X POST "http://127.0.0.1:8000/api/admin/issues/1/ai-analyze" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# 4. Mark as resolved
curl -X PUT "http://127.0.0.1:8000/api/admin/issues/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "resolved",
    "admin_notes": "Performance issue resolved by optimizing database queries and adding caching layer. Response times now under 2 seconds."
  }'
```

### File Upload Integration
```bash
# Create issue with file attachment (after uploading files via direct upload)
curl -X POST "http://127.0.0.1:8000/api/issues" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "UI Layout Issue with Screenshots",
    "description": "The layout breaks on mobile devices as shown in the attached screenshots",
    "type": "bug",
    "severity": "medium",
    "area": "frontend",
    "category": "ui",
    "file_ids": [15, 16, 17]
  }'

# Update issue to add more files
curl -X PUT "http://127.0.0.1:8000/api/issues/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "file_ids": [15, 16, 17, 18, 19]
  }'
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |

---

## Notes

### Issue Lifecycle
Issues follow a standard lifecycle:
1. **open** - Newly created, awaiting triage
2. **in_progress** - Assigned and being worked on
3. **resolved** - Fixed and ready for verification
4. **closed** - Verified and completed
5. **duplicate** - Marked as duplicate of another issue

### Default Values
- **type**: defaults to "bug"
- **severity**: defaults to "medium"  
- **priority**: defaults to "medium"
- **status**: defaults to "open"
- **area**: optional field, can be null

### File Attachments
- Issues support file attachments via the existing polymorphic file system
- Screenshots and documents can be attached using the `file_ids` array
- Files must be uploaded via the Direct Upload system first
- Supported formats: images (PNG, JPG, JPEG, GIF, WebP), documents (PDF, DOC, DOCX, TXT)

### AI Analysis Feature
- **Purpose**: Provides automated insights and recommendations for issue resolution
- **Availability**: Admin, researcher, and superadmin users only
- **Content**: Analyzes issue type, severity, area, and description to provide actionable recommendations
- **Storage**: AI analysis is stored in the `ai_analysis` field and persists until manually updated
- **Use Cases**: Helps prioritize issues, suggests team assignments, identifies patterns

### Search Functionality
- **User Search**: Searches across title and description fields for user's own issues
- **Admin Search**: Searches across title, description, and user name/email for all issues
- **Case-insensitive**: All searches are case-insensitive
- **Partial matching**: Supports partial word matching

### Assignment System
- **Admin Feature**: Only admins can assign issues to team members
- **User Validation**: Assigned users must exist in the system
- **Unassigned Filter**: Use `assigned_to=unassigned` to find unassigned issues
- **Self-Assignment**: Admins can assign issues to themselves

### Security
- **Ownership Validation**: Users can only view/edit/delete their own issues
- **Admin Override**: Admins can access and modify any issue
- **Role-based Access**: Different permissions based on user roles
- **Token Authentication**: All endpoints require valid Bearer token

### Performance Considerations
- **Pagination**: All list endpoints are paginated (default 15-20 items per page)
- **Selective Loading**: User relationships and files are loaded efficiently
- **Indexing**: Database indexes on status, severity, area, user_id, assigned_to, and created_at
- **Filtering**: Multiple filter combinations supported without performance impact