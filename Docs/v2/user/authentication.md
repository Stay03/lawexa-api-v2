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
- **Password Reset & Recovery**
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

**Alternative: Password Recovery Flow**
1. **Forgot Password** → Request reset link via email
2. **Reset Password** → Use token from email to set new password
3. **Login** → Use new credentials

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

## Password Reset & Recovery

### 6. Forgot Password Request

Request a password reset link via email.

**Endpoint:** `POST /auth/forgot-password`  
**Rate Limited:** 5 requests per minute

**Request Body:**
```json
{
  "email": "passwordreset@test.com"
}
```

**Validation Rules:**
- `email`: required, email, exists in users table

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password reset link sent to your email address.",
  "data": null
}
```

**Error Response (422 - Non-existent Email):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "email": ["We could not find an account with that email address."]
  }
}
```

**Error Response (400 - Guest Account):**
```json
{
  "status": "error",
  "message": "Password reset is not available for guest accounts.",
  "data": null
}
```

**Error Response (429 - Rate Limited):**
```json
{
  "message": "Too Many Attempts.",
  "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

**Notes:**
- Password reset emails are queued for sending
- Tokens expire after 60 minutes
- Guest accounts cannot request password resets
- Email contains secure reset link with token

---

### 7. Reset Password

Reset user password using token from email.

**Endpoint:** `POST /auth/reset-password`  
**Rate Limited:** 5 requests per minute

**Request Body:**
```json
{
  "token": "abc123xyz789token_from_email",
  "email": "passwordreset@test.com",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Validation Rules:**
- `token`: required, string
- `email`: required, email, exists in users table
- `password`: required, string, min:8, confirmed
- `password_confirmation`: required, must match password

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password has been reset successfully. Please log in with your new password.",
  "data": null
}
```

**Error Response (400 - Invalid Token):**
```json
{
  "status": "error",
  "message": "Invalid or expired reset token.",
  "data": null
}
```

**Error Response (400 - Expired Token):**
```json
{
  "status": "error",
  "message": "Reset token has expired. Please request a new one.",
  "data": null
}
```

**Error Response (422 - Validation Error):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "password": ["The password confirmation does not match."]
  }
}
```

**Notes:**
- All user tokens are revoked after successful password reset
- Reset tokens are single-use and deleted after use
- Token validation includes expiration check (60 minutes)
- Password must meet security requirements (min 8 characters)

---

### 8. Validate Reset Token

Validate a password reset token before showing reset form.

**Endpoint:** `GET /auth/validate-reset-token`  
**Rate Limited:** 10 requests per minute

**Query Parameters:**
- `token`: Reset token from email
- `email`: User email address

**Example Request:**
```http
GET /auth/validate-reset-token?token=abc123xyz789token&email=passwordreset@test.com
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Reset token is valid.",
  "data": {
    "valid": true,
    "email": "passwordreset@test.com"
  }
}
```

**Error Response (400 - Invalid Token):**
```json
{
  "status": "error",
  "message": "Invalid or expired reset token.",
  "data": null
}
```

**Error Response (422 - Missing Parameters):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "email": ["The email field is required."],
    "token": ["The token field is required."]
  }
}
```

**Notes:**
- Use this endpoint to validate tokens before showing password reset form
- Helps provide better user experience by catching invalid tokens early
- Does not consume the token (token remains valid for actual reset)

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
    "guest_limits": {
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
- **Password reset requests: 5 requests per minute**
- **Reset password attempts: 5 requests per minute**
- **Token validation: 10 requests per minute**

### Security Logging
All authentication activities are logged including:
- Login attempts (success/failure)
- Registration events
- Logout events
- Email verification attempts
- Failed authentication attempts
- **Password reset requests (success/failure)**
- **Password reset completions**
- **Invalid token attempts**

### Password Reset Security
- **Token Expiration:** Reset tokens expire after 60 minutes
- **Single Use:** Tokens are deleted after successful password reset
- **Guest Protection:** Guest accounts cannot request password resets
- **Token Validation:** Secure hash-based token verification
- **Auto Cleanup:** Expired tokens are automatically removed
- **Session Revocation:** All user tokens revoked after password reset

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

### Complete Password Reset Flow

```javascript
// 1. Request password reset
const forgotResponse = await fetch('/api/auth/forgot-password', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com'
  })
});

const forgotResult = await forgotResponse.json();
// Response: {"status":"success","message":"Password reset link sent to your email address.","data":null}

// 2. User clicks link in email, frontend extracts token and validates it
const validateResponse = await fetch(`/api/auth/validate-reset-token?token=${resetToken}&email=user@example.com`);
const validateResult = await validateResponse.json();

if (validateResult.status === 'success') {
  // 3. Show password reset form, then submit new password
  const resetResponse = await fetch('/api/auth/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      token: resetToken,
      email: 'user@example.com',
      password: 'NewSecurePassword123!',
      password_confirmation: 'NewSecurePassword123!'
    })
  });
  
  const resetResult = await resetResponse.json();
  // Response: {"status":"success","message":"Password has been reset successfully. Please log in with your new password.","data":null}
  
  if (resetResult.status === 'success') {
    // 4. Redirect to login page - user must login with new password
    window.location.href = '/login';
  }
} else {
  // Handle invalid/expired token
  console.error('Invalid or expired reset token');
}
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

**Password Reset Test Data:**
```json
{
  "forgot_password": {
    "email": "passwordreset@test.com"
  },
  "reset_password": {
    "token": "token_from_email_link",
    "email": "passwordreset@test.com", 
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
  }
}
```

**Test Scenarios:**
- ✅ **Valid password reset request** → Returns success message
- ✅ **Invalid email address** → Returns validation error  
- ✅ **Guest account email** → Returns guest protection error
- ✅ **Rate limiting** → Returns 429 after 5 requests per minute
- ✅ **Invalid reset token** → Returns invalid token error
- ✅ **Expired token** → Returns token expired error  
- ✅ **Password validation** → Returns validation errors for weak passwords
- ✅ **Successful reset** → Password changed, tokens revoked, login required