# Authentication System API Documentation

> **Version:** 2.0  
> **Base URLs:**
> - **Local Development:** `http://localhost:8000/api`
> - **Production:** `https://rest.lawexa.com/api`  
> **Content-Type:** `application/json`  
> **Accept:** `application/json`

## Overview

The LawExa API v2 authentication system provides comprehensive user management including:

- **User Registration & Login**
- **Email Verification**  
- **Guest Session Management**
- **Profile Management**
- **Token-based Authentication (Laravel Sanctum)**
- **Role-based Access Control**

---

## Authentication Flow

1. **Register** → Get token + user data
2. **Verify Email** → Email verification (optional but recommended)
3. **Login** → Get fresh token
4. **Use API** → Include `Authorization: Bearer {token}` header
5. **Logout** → Revoke token

---

## Endpoints

### 1. User Registration

Register a new user account.

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "name": "Test User",
  "email": "user@mailinator.com",
  "password": "TestPassword123!",
  "password_confirmation": "TestPassword123!"
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `email`: required, email, unique, max:255
- `password`: required, string, min:8, confirmed
- `password_confirmation`: required, must match password

**Success Response (201):**
```json
{
  "status": "success",
  "message": "User registered successfully. Email verification required.",
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
    },
    "token": "5|LB6rHayc0hVC4qbZbiEGHIyb8SU9EnevMwhz8pUg2975ab48",
    "message": "Registration successful. Please check your email to verify your account."
  }
}
```

**Error Response (422 - Validation):**
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "data": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

### 2. User Login

Authenticate user and get access token.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "lawexatest1756153454@mailinator.com",
  "password": "TestPassword123!"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Login successful",
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
      "plan": null,
      "plan_code": null,
      "formatted_amount": null,
      "amount": null,
      "interval": null,
      "active_subscription": null,
      "subscriptions": [],
      "email_verified_at": null,
      "created_at": "2025-08-25T20:24:15.000000Z",
      "updated_at": "2025-08-25T20:24:15.000000Z"
    },
    "token": "6|yNJdM2VvYccyhI1LhdCrEJMee7VIfuHT8jTDLhJ4bc3b4bb9"
  }
}
```

**Error Response (401 - Invalid Credentials):**
```json
{
  "status": "error",
  "message": "Invalid credentials",
  "data": null
}
```

---

### 3. Get Current User

Get authenticated user information.

**Endpoint:** `GET /auth/me`  
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User retrieved successfully",
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
      "plan": null,
      "plan_code": null,
      "formatted_amount": null,
      "amount": null,
      "interval": null,
      "active_subscription": null,
      "subscriptions": [],
      "email_verified_at": null,
      "created_at": "2025-08-25T20:24:15.000000Z",
      "updated_at": "2025-08-25T20:24:15.000000Z"
    }
  }
}
```

**Error Response (401 - Unauthorized):**
```json
{
  "status": "error",
  "message": "Unauthenticated",
  "data": null
}
```

---

### 4. User Logout

Revoke the current access token.

**Endpoint:** `POST /auth/logout`  
**Authorization:** `Bearer {token}` (Required)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Logged out successfully",
  "data": null
}
```

---

### 5. Create Guest Session

Create temporary guest access for unauthenticated users.

**Endpoint:** `POST /auth/guest-session`

**Request Body:**
```json
{
  "email": "guesttest1756153454@mailinator.com",
  "name": "Guest User Test"
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Guest session created successfully",
  "data": {
    "user": {
      "id": 5,
      "name": "Guest User Test",
      "email": "guesttest1756153454@mailinator.com",
      "role": "guest",
      "avatar": null,
      "google_id": null,
      "customer_code": null,
      "email_verified": true,
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
      "created_at": "2025-08-25T20:24:18.000000Z",
      "updated_at": "2025-08-25T20:24:18.000000Z"
    },
    "token": "7|cQta5P2PjKg7HyRinKw3gcZXOg4DPi6O4cRqGV5Ve2ad4b82",
    "message": "Guest session created successfully. Limited access granted."
  }
}
```

**Notes:**
- Guest sessions have limited access and expire after 30 days
- **View Limit:** Guests can view maximum 20 items across all content (statutes, cases, notes, etc.)
- Once limit is reached, further view requests will be blocked until guest expires
- Guests are automatically cleaned up after expiration or 30 days of inactivity
- Guest tokens can access read-only endpoints

---

## Guest View Limits

Guest users are subject to content viewing limits to prevent abuse while maintaining reasonable access for evaluation purposes.

### View Limit Rules

- **Total Limit:** 20 views across all content types
- **Scope:** Applies to all viewable content including:
  - Statutes and legal documents
  - Court cases
  - User notes
  - Comments
  - Any other tracked content
- **Enforcement:** Tracked per guest user session
- **Reset:** Limit resets when guest session expires (30 days)

### How View Limiting Works

1. **Tracking:** Each time a guest views content, it's recorded in the system
2. **Counting:** Views are counted cumulatively across all content types
3. **Blocking:** Once 20 views are reached, further content requests return `403 Forbidden`
4. **Cooldown:** Individual content has cooldown periods (2 hours) to prevent rapid re-viewing

### Error Response When Limit Reached

When a guest user exceeds their view limit:

**Response (403 Forbidden):**
```json
{
  "status": "error",
  "message": "View limit exceeded. Guest users can view maximum 20 items.",
  "data": {
    "limit": 20,
    "current_views": 20,
    "remaining_views": 0,
    "suggestion": "Create an account for unlimited access"
  }
}
```

### Checking Remaining Views

Guest users can check their remaining views through the user profile endpoint:

**Request:**
```http
GET /auth/me
Authorization: Bearer {guest-token}
```

**Response includes view limit information:**
```json
{
  "user": {
    "role": "guest",
    "view_stats": {
      "total_views": 15,
      "remaining_views": 5,
      "view_limit": 20
    }
  }
}
```

---

## Email Verification

### Send Verification Email

Resend email verification link to user.

**Endpoint:** `POST /auth/email/verification-notification`  
**Authorization:** `Bearer {token}` (Required)  
**Rate Limited:** 6 requests per minute

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Verification email sent successfully",
  "data": null
}
```

### Verify Email

Verify user email address using signed URL from email.

**Endpoint:** `GET /auth/email/verify/{id}/{hash}`  
**Middleware:** signed, throttle:6,1
**Parameters:** 
- `id`: User ID
- `hash`: Verification hash
- `expires`: URL expiration timestamp
- `signature`: URL signature

**Success Response:** Redirect to frontend with verification status

---

## User Roles

| Role | Description | Access Level |
|------|------------|--------------|
| `guest` | Temporary users | Read-only, expires in 30 days, 20 view limit |
| `user` | Regular users | Full user features |
| `admin` | Administrators | User management + admin features |
| `researcher` | Research staff | Admin features without user management |
| `superadmin` | Super administrators | Full system access |

---

## Authentication Headers

For all protected endpoints, include the authorization header:

```http
Authorization: Bearer {your-token-here}
Content-Type: application/json
Accept: application/json
```

---

## Security Features

### Rate Limiting
- Login attempts: Standard Laravel throttling
- Email verification: 6 requests per minute
- Registration: Standard Laravel throttling

### Security Logging
All authentication activities are logged including:
- Login attempts (success/failure)
- Registration events
- Logout events
- Email verification attempts
- Failed authentication attempts

### Token Management
- Tokens are generated using Laravel Sanctum
- Tokens are revoked on logout
- Expired guest users are automatically cleaned up
- Token format: `{id}|{token}`

---

## Error Responses

### Common HTTP Status Codes

| Status | Meaning | Description |
|--------|---------|-------------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 401 | Unauthorized | Invalid or missing token |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal server error |

### Error Response Format

```json
{
  "status": "error",
  "message": "Error description",
  "data": {
    "field": ["Validation error message"]
  }
}
```

---

## Examples

### Complete Registration Flow

```javascript
// 1. Register
const registerResponse = await fetch('/api/auth/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com',
    password: 'SecurePassword123!',
    password_confirmation: 'SecurePassword123!'
  })
});

const { data } = await registerResponse.json();
const token = data.token;

// 2. Use authenticated endpoints
const userResponse = await fetch('/api/auth/me', {
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

// 3. Logout
await fetch('/api/auth/logout', {
  method: 'POST',
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

---

## Testing Information

**Test Environment:**
- Local Server: `http://localhost:8000/api`
- Production Server: `https://rest.lawexa.com/api`
- Test emails: Use mailinator.com for email verification testing
- Test user: `lawexatest1756153454@mailinator.com`
- Mailinator inbox: https://www.mailinator.com/v4/public/inboxes.jsp?to=lawexatest1756153454@mailinator.com

**Sample Test Data:**
```json
{
  "name": "Test User",
  "email": "test@mailinator.com",
  "password": "TestPassword123!",
  "password_confirmation": "TestPassword123!"
}
```