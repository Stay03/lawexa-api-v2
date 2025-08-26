# User Profile Management API Documentation

> **Version:** 2.0  
> **Base URLs:**
> - **Local Development:** `http://localhost:8000/api`
> - **Production:** `https://rest.lawexa.com/api`  
> **Authentication:** Required (`Bearer {token}`)

## Overview

User profile management endpoints allow authenticated users to view and update their profile information, manage account settings, and access subscription details.

---

## Endpoints

### 1. Get User Profile

Retrieve the current user's complete profile information.

**Endpoint:** `GET /user/profile`  
**Authorization:** `Bearer {token}` (Required)

**Request Headers:**
```http
Authorization: Bearer 6|yNJdM2VvYccyhI1LhdCrEJMee7VIfuHT8jTDLhJ4bc3b4bb9
Accept: application/json
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User profile retrieved successfully",
  "data": {
    "user": {
      "id": 4,
      "name": "Test User",
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
      "updated_at": "2025-08-25T20:24:15.000000Z"
    }
  }
}
```

**Profile Fields Explained:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique user identifier |
| `name` | string | Full name of the user |
| `email` | string | User's email address (login credential) |
| `role` | string | User role: `user`, `guest`, `admin`, `researcher`, `superadmin` |
| `avatar` | string\|null | URL to user's profile picture |
| `google_id` | string\|null | Google OAuth ID (if registered via Google) |
| `customer_code` | string\|null | Payment provider customer reference |
| `email_verified` | boolean | Email verification status |
| `subscription_status` | string | Current subscription status |
| `subscription_expiry` | string\|null | ISO date of subscription expiry |
| `has_active_subscription` | boolean | Whether user has active paid subscription |
| `subscriptions` | array | List of user's subscription history |
| `email_verified_at` | string\|null | ISO timestamp of email verification |
| `created_at` | string | Account creation timestamp |
| `updated_at` | string | Last profile update timestamp |

---

### 2. Update User Profile

Update user profile information.

**Endpoint:** `PUT /user/profile`  
**Authorization:** `Bearer {token}` (Required)

**Request Headers:**
```http
Authorization: Bearer 6|yNJdM2VvYccyhI1LhdCrEJMee7VIfuHT8jTDLhJ4bc3b4bb9
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Updated Test User",
  "email": "lawexatest1756153454@mailinator.com"
}
```

**Validation Rules:**
- `name`: optional, string, max:255
- `email`: optional, email, max:255, unique (except current user)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
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
      "updated_at": "2025-08-25T20:24:20.000000Z"
    }
  }
}
```

**Error Response (422 - Validation Error):**
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "data": {
    "email": [
      "The email has already been taken."
    ],
    "name": [
      "The name may not be greater than 255 characters."
    ]
  }
}
```

---

## Subscription Information

When a user has an active subscription, the profile response includes additional subscription fields:

```json
{
  "user": {
    "id": 4,
    "name": "Test User",
    "email": "user@example.com",
    "subscription_status": "active",
    "subscription_expiry": "2025-09-25T20:24:15.000000Z",
    "has_active_subscription": true,
    "plan": "Professional Plan",
    "plan_code": "PRO_MONTHLY",
    "formatted_amount": "₦5,000",
    "amount": 5000,
    "interval": "monthly",
    "active_subscription": {
      "id": 1,
      "plan_id": 2,
      "status": "active",
      "paystack_subscription_code": "SUB_xyz123",
      "amount": 5000,
      "next_payment_date": "2025-09-25T20:24:15.000000Z",
      "created_at": "2025-08-25T20:24:15.000000Z",
      "updated_at": "2025-08-25T20:24:15.000000Z"
    },
    "subscriptions": [
      {
        "id": 1,
        "plan": "Professional Plan",
        "status": "active",
        "amount": 5000,
        "interval": "monthly",
        "started_at": "2025-08-25T20:24:15.000000Z",
        "expires_at": "2025-09-25T20:24:15.000000Z"
      }
    ]
  }
}
```

---

## Google OAuth Users

For users who registered via Google OAuth, additional fields are populated:

```json
{
  "user": {
    "id": 4,
    "name": "John Doe",
    "email": "john@gmail.com",
    "google_id": "1234567890",
    "avatar": "https://lh3.googleusercontent.com/a/...",
    "email_verified": true,
    "email_verified_at": "2025-08-25T20:24:15.000000Z"
  }
}
```

---

## Account Security

### Email Verification Status

Users can check their email verification status in the profile response:

```json
{
  "email_verified": false,
  "email_verified_at": null
}
```

To verify email, users should:
1. Check their inbox for verification email
2. Click verification link
3. Or use `POST /auth/email/verification-notification` to resend

### Role-Based Access

User roles determine API access levels:

| Role | Profile Access | Additional Access |
|------|---------------|-------------------|
| `guest` | Read-only | Expires after 24h |
| `user` | Full profile management | All user endpoints |
| `admin` | Full profile management | Admin endpoints |
| `researcher` | Full profile management | Research endpoints |
| `superadmin` | Full profile management | All system endpoints |

---

## Error Handling

### Authentication Errors

**401 Unauthorized:**
```json
{
  "status": "error", 
  "message": "Unauthenticated",
  "data": null
}
```

**Token Expired/Invalid:**
```json
{
  "status": "error",
  "message": "Token has expired",
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
    "field_name": [
      "Specific validation error message"
    ]
  }
}
```

---

## Usage Examples

### JavaScript/Fetch Example

```javascript
// Get user profile
async function getUserProfile() {
  const baseUrl = process.env.NODE_ENV === 'production' 
    ? 'https://rest.lawexa.com/api'
    : 'http://localhost:8000/api';
    
  const response = await fetch(`${baseUrl}/user/profile`, {
    method: 'GET',
    headers: {
      'Authorization': 'Bearer ' + userToken,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data.user;
}

// Update user profile
async function updateProfile(name, email) {
  const baseUrl = process.env.NODE_ENV === 'production' 
    ? 'https://rest.lawexa.com/api'
    : 'http://localhost:8000/api';
    
  const response = await fetch(`${baseUrl}/user/profile`, {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer ' + userToken,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      name: name,
      email: email
    })
  });
  
  return await response.json();
}
```

### cURL Examples

```bash
# Get user profile (Local)
curl -X GET "http://localhost:8000/api/user/profile" \
  -H "Authorization: Bearer 6|yNJdM2VvYccyhI1LhdCrEJMee7VIfuHT8jTDLhJ4bc3b4bb9" \
  -H "Accept: application/json"

# Get user profile (Production)
curl -X GET "https://rest.lawexa.com/api/user/profile" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"

# Update user profile (Local)
curl -X PUT "http://localhost:8000/api/user/profile" \
  -H "Authorization: Bearer 6|yNJdM2VvYccyhI1LhdCrEJMee7VIfuHT8jTDLhJ4bc3b4bb9" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Name",
    "email": "newemail@example.com"
  }'

# Update user profile (Production)
curl -X PUT "https://rest.lawexa.com/api/user/profile" \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Name",
    "email": "newemail@example.com"
  }'
```

---

## Notes

- **Profile updates** may require email re-verification if email is changed
- **Avatar uploads** are handled via separate file upload endpoints
- **Password changes** are handled via dedicated password management endpoints
- **Account deletion** requires contacting support or using dedicated deletion endpoints
- **Subscription management** has separate endpoints under `/subscriptions`

---

## Related Endpoints

- [Authentication](/docs/v2/user/authentication.md) - Login, registration, logout
- [Subscriptions](/docs/v2/user/subscriptions.md) - Subscription management
- [File Uploads](/docs/v2/user/uploads.md) - Profile picture uploads