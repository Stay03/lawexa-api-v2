# Admin User Management API Documentation

> **Version:** 2.0  
> **Base URL:** `http://localhost:8000/api`  
> **Authentication:** Required (`Bearer {admin-token}`)  
> **Roles:** `admin`, `researcher`, `superadmin`

## Overview

Admin user management endpoints provide comprehensive user administration capabilities including user listing, viewing, editing, and deletion. Access is restricted to users with admin-level roles.

---

## Authentication & Authorization

### Required Roles
- `admin` - Basic administrative access
- `researcher` - Research staff access
- `superadmin` - Full system access

### Role Hierarchy
```
superadmin > admin > researcher > user > guest
```

### Authorization Header
```http
Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2
Accept: application/json
```

---

## Endpoints

### 1. Admin Dashboard

Get admin dashboard overview with user statistics and permissions.

**Endpoint:** `GET /admin/dashboard`  
**Authorization:** Admin role required

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Dashboard data retrieved successfully",
  "data": {
    "role": "admin",
    "dashboard_data": {
      "total_users": 6,
      "message": "Admin dashboard - basic user management data",
      "permissions": [
        "view_users",
        "manage_basic_settings"
      ]
    },
    "permissions": [
      "view_users", 
      "manage_basic_settings"
    ],
    "user_access_level": "Basic administrative access"
  }
}
```

**Dashboard Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `role` | string | Current admin's role level |
| `total_users` | integer | Total number of users in system |
| `permissions` | array | List of available permissions |
| `user_access_level` | string | Description of access level |

---

### 2. List All Users

Get paginated list of all system users with filtering capabilities.

**Endpoint:** `GET /admin/users`  
**Authorization:** Admin role required

**Query Parameters:**
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Items per page (default: 10)
- `role` (optional): Filter by user role
- `search` (optional): Search by name or email

**Example Request:**
```http
GET /admin/users?page=1&per_page=10&role=user
Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Users filtered by admin permissions",
  "data": {
    "users": [
      {
        "id": 6,
        "name": "Admin User",
        "email": "lawexaadmin1756153454@mailinator.com",
        "role": "admin",
        "avatar": null,
        "google_id": null,
        "customer_code": null,
        "email_verified": false,
        "subscription_status": "inactive",
        "subscription_expiry": null,
        "has_active_subscription": false,
        "subscriptions": [],
        "email_verified_at": null,
        "created_at": "2025-08-25T20:24:24.000000Z",
        "updated_at": "2025-08-25T20:24:34.000000Z"
      },
      {
        "id": 4,
        "name": "Updated Test User",
        "email": "lawexatest1756153454@mailinator.com",
        "role": "user",
        "avatar": null,
        "google_id": null,
        "customer_code": null,
        "email_verified": false,
        "subscription_status": "inactive",
        "subscription_expiry": null,
        "has_active_subscription": false,
        "subscriptions": [],
        "email_verified_at": null,
        "created_at": "2025-08-25T20:24:15.000000Z",
        "updated_at": "2025-08-25T20:24:20.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 10,
      "to": 6,
      "total": 6
    },
    "links": {
      "first": "http://localhost:8000/api/admin/users?page=1",
      "last": "http://localhost:8000/api/admin/users?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

**Pagination Meta Fields:**

| Field | Description |
|-------|-------------|
| `current_page` | Current page number |
| `from` | First item number on current page |
| `last_page` | Last page number |
| `per_page` | Items per page |
| `to` | Last item number on current page |
| `total` | Total number of items |

---

### 3. Get User Statistics

Get comprehensive user statistics and system metrics.

**Endpoint:** `GET /admin/stats`  
**Authorization:** Admin role required

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User statistics retrieved successfully",
  "data": {
    "total_users": 6,
    "users_by_role": {
      "user": 3,
      "admin": 1,
      "guest": 2,
      "researcher": 0,
      "superadmin": 0
    },
    "email_verification_stats": {
      "verified": 0,
      "unverified": 6,
      "verification_rate": "0.00%"
    },
    "subscription_stats": {
      "active_subscriptions": 0,
      "inactive_users": 6,
      "subscription_rate": "0.00%"
    },
    "recent_activity": {
      "registrations_today": 6,
      "registrations_this_week": 6,
      "registrations_this_month": 6
    },
    "user_distribution": {
      "regular_users": 3,
      "oauth_users": 0,
      "guest_users": 2,
      "admin_users": 1
    }
  }
}
```

---

### 4. Get Specific User

Get detailed information about a specific user by ID.

**Endpoint:** `GET /admin/users/{id}`  
**Authorization:** Admin role required

**Path Parameters:**
- `id` (required): User ID (integer)

**Example Request:**
```http
GET /admin/users/4
Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User retrieved successfully",
  "data": {
    "user": {
      "id": 4,
      "name": "Updated Test User",
      "email": "lawexatest1756153454@mailinator.com",
      "role": "user",
      "avatar": null,
      "google_id": null,
      "customer_code": null,
      "email_verified": false,
      "subscription_status": "inactive",
      "subscription_expiry": null,
      "has_active_subscription": false,
      "plan": null,
      "plan_code": null,
      "formatted_amount": null,
      "amount": null,
      "interval": null,
      "active_subscription": null,
      "subscriptions": [],
      "email_verified_at": null,
      "created_at": "2025-08-25T20:24:15.000000Z",
      "updated_at": "2025-08-25T20:24:20.000000Z",
      "last_login_at": "2025-08-25T20:24:16.000000Z",
      "login_count": 1,
      "notes": [],
      "cases": [],
      "issues": []
    }
  }
}
```

**Error Response (404 - User Not Found):**
```json
{
  "status": "error",
  "message": "User not found",
  "data": null
}
```

---

### 5. Update User

Update user information and role assignments.

**Endpoint:** `PUT /admin/users/{id}`  
**Authorization:** Admin/Superadmin role required

**Path Parameters:**
- `id` (required): User ID (integer)

**Request Body:**
```json
{
  "name": "Updated User Name",
  "email": "updated@example.com",
  "role": "researcher"
}
```

**Validation Rules:**
- `name` (optional): string, max:255
- `email` (optional): email, unique (except current user), max:255  
- `role` (optional): enum - `user`, `guest`, `admin`, `researcher`, `superadmin`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "user": {
      "id": 4,
      "name": "Updated User Name", 
      "email": "updated@example.com",
      "role": "researcher",
      "avatar": null,
      "google_id": null,
      "customer_code": null,
      "email_verified": false,
      "subscription_status": "inactive",
      "subscription_expiry": null,
      "has_active_subscription": false,
      "created_at": "2025-08-25T20:24:15.000000Z",
      "updated_at": "2025-08-25T20:30:45.000000Z"
    }
  }
}
```

**Role Update Restrictions:**
- Only `superadmin` can assign `superadmin` role
- Only `admin` and `superadmin` can assign `admin` role
- Users cannot change their own role to higher privilege

---

### 6. Delete User

Delete a user account and all associated data.

**Endpoint:** `DELETE /admin/users/{id}`  
**Authorization:** Admin/Superadmin role required

**Path Parameters:**
- `id` (required): User ID (integer)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User deleted successfully",
  "data": {
    "deleted_user_id": 4,
    "cleanup_summary": {
      "subscriptions_cancelled": 0,
      "notes_deleted": 2,
      "cases_reassigned": 0,
      "files_deleted": 3
    }
  }
}
```

**Error Response (403 - Cannot Delete):**
```json
{
  "status": "error",
  "message": "Cannot delete user with active subscription",
  "data": null
}
```

**Deletion Restrictions:**
- Cannot delete users with active subscriptions
- Cannot delete the last remaining superadmin
- Admins cannot delete other admins (superadmin only)

---

## User Role Management

### Available Roles

| Role | Level | Capabilities |
|------|-------|-------------|
| `guest` | 1 | Read-only access, temporary (30 days), 20 view limit |
| `user` | 2 | Standard user features |
| `researcher` | 3 | Research features + user management view |
| `admin` | 4 | Full user management + admin features |
| `superadmin` | 5 | Full system access + admin management |

### Role Assignment Rules

```javascript
// Role assignment matrix
const canAssignRole = {
  admin: ['user', 'researcher', 'admin'],      // Can assign up to admin
  superadmin: ['user', 'researcher', 'admin', 'superadmin'] // Can assign any role
};

// Self-role change restrictions
const cannotSelfPromote = true; // Users cannot promote themselves
const cannotSelfDemote = {
  admin: false,      // Admins can demote themselves
  superadmin: true   // Superadmins cannot demote themselves (last protection)
};
```

---

## Guest User Management

### Guest View Limits

Guest users are automatically limited to prevent system abuse while providing reasonable evaluation access.

#### View Limit Configuration

- **Default Limit:** 20 views per guest session
- **Scope:** All trackable content (statutes, cases, notes, comments)
- **Reset:** Automatic when guest expires (30 days)
- **Environment Variable:** `GUEST_TOTAL_VIEW_LIMIT=20`

#### Admin Monitoring of Guest Views

When viewing guest user details, admins can see view limit information:

**Enhanced User Response for Guests:**
```json
{
  "user": {
    "id": 5,
    "name": "Guest User",
    "role": "guest",
    "view_statistics": {
      "total_views": 18,
      "remaining_views": 2,
      "view_limit": 20,
      "limit_reached": false
    },
    "guest_expires_at": "2025-09-25T20:24:18.000000Z",
    "last_activity_at": "2025-08-25T22:15:30.000000Z"
  }
}
```

#### View Limit Enforcement

1. **Tracking:** Each content view is recorded in `model_views` table
2. **Counting:** System counts total views per guest user
3. **Blocking:** HTTP 403 returned when limit exceeded
4. **Cooldown:** 2-hour cooldown between views of same content

#### Admin Dashboard Statistics

The admin dashboard includes guest view limit statistics:

```json
{
  "guest_statistics": {
    "total_guests": 12,
    "guests_near_limit": 3,
    "guests_at_limit": 1,
    "average_views_per_guest": 8.2
  },
  "view_limit_metrics": {
    "limit_violations_today": 5,
    "limit_violations_this_week": 23
  }
}
```

#### Managing View Limits

Admins can:

- **Monitor Usage:** View guest view statistics in user details
- **Track Patterns:** See which guests hit limits frequently  
- **Configure Limits:** Adjust via environment variables
- **View Analytics:** Dashboard shows limit-related metrics

#### Common Administrative Tasks

**Check Guest View Status:**
```bash
# Get guest user with view statistics
curl -X GET "http://localhost:8000/api/admin/users/{guest-id}" \
  -H "Authorization: Bearer {admin-token}"
```

**Monitor Limit Violations:**
- Review admin dashboard for guests hitting limits
- Check system logs for view limit blocks
- Monitor conversion rates (guests upgrading to users)

---

## Security Features

### Access Control

All admin endpoints verify:
1. **Authentication:** Valid Bearer token
2. **Authorization:** Minimum role requirements
3. **Permission Scope:** Role-based action permissions
4. **Data Filtering:** Users see only data their role allows

### Audit Logging

All admin actions are logged:
- User role changes
- User data modifications
- User deletions
- Permission elevation attempts

### Rate Limiting

Admin endpoints have stricter rate limits:
- User listing: 60 requests/hour
- User modifications: 30 requests/hour  
- User deletions: 10 requests/hour

---

## Error Responses

### Authorization Errors

**403 Forbidden (Insufficient Role):**
```json
{
  "status": "error",
  "message": "Insufficient permissions for this action",
  "data": {
    "required_role": "admin",
    "current_role": "user"
  }
}
```

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Unauthenticated", 
  "data": null
}
```

### Validation Errors

**422 Unprocessable Entity:**
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "data": {
    "email": ["The email has already been taken."],
    "role": ["The selected role is invalid."]
  }
}
```

---

## Usage Examples

### JavaScript/Fetch Example

```javascript
// Get all users with pagination
async function getUsers(page = 1, role = null) {
  const params = new URLSearchParams({ page: page.toString() });
  if (role) params.append('role', role);
  
  const response = await fetch(`/api/admin/users?${params}`, {
    headers: {
      'Authorization': 'Bearer ' + adminToken,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Update user role
async function updateUserRole(userId, newRole) {
  const response = await fetch(`/api/admin/users/${userId}`, {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer ' + adminToken,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      role: newRole
    })
  });
  
  return await response.json();
}

// Get user statistics
async function getUserStats() {
  const response = await fetch('/api/admin/stats', {
    headers: {
      'Authorization': 'Bearer ' + adminToken,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}
```

### cURL Examples

```bash
# Get admin dashboard
curl -X GET "http://localhost:8000/api/admin/dashboard" \
  -H "Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2" \
  -H "Accept: application/json"

# List users with filtering
curl -X GET "http://localhost:8000/api/admin/users?role=user&page=1" \
  -H "Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2" \
  -H "Accept: application/json"

# Update user role
curl -X PUT "http://localhost:8000/api/admin/users/4" \
  -H "Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "role": "researcher",
    "name": "Updated Name"
  }'

# Delete user
curl -X DELETE "http://localhost:8000/api/admin/users/4" \
  -H "Authorization: Bearer 9|hxE1lEJlz9XVLMn8EYoHWHm0jtUdJG8vWx4nZH1mf8a4c7e2" \
  -H "Accept: application/json"
```

---

## Best Practices

### Role Assignment
- Always use the principle of least privilege
- Regularly audit role assignments
- Document role change reasons
- Implement approval workflows for sensitive role changes

### User Management
- Verify user identity before role elevation
- Monitor admin activity logs
- Implement session timeouts for admin accounts
- Use 2FA for admin accounts (when available)

### Data Protection
- Never expose sensitive user data in logs
- Implement proper data retention policies
- Ensure GDPR compliance for user deletions
- Backup user data before bulk operations

---

## Related Endpoints

- [Authentication](/docs/v2/user/authentication.md) - Admin login and authentication
- [Subscription Management](/docs/v2/admin/subscriptions.md) - Manage user subscriptions
- [System Settings](/docs/v2/admin/settings.md) - System configuration
- [Audit Logs](/docs/v2/admin/audit.md) - Admin activity monitoring