# Google OAuth Authentication Flow

## Overview
The Google signup/login flow uses a **secure 3-step process** with temporary codes to prevent token exposure in URLs.

## API Endpoints

### 1. Get Google OAuth URL
**Endpoint:** `GET /api/auth/google`

**Request:**
```http
GET /api/auth/google HTTP/1.1
Host: rest.lawexa.com
```

**Response:**
```json
{
  "url": "https://accounts.google.com/oauth/authorize?client_id=...&redirect_uri=...&response_type=code&scope=..."
}
```

### 2. Handle Google Callback (Internal)
**Endpoint:** `GET /api/auth/google/callback`

This endpoint is called by Google after user authorization. It:
- Receives the authorization code from Google
- Exchanges it for user data
- Creates/updates user in database
- Generates API token
- Creates temporary 32-character code (expires in 10 minutes)
- Redirects to frontend with temporary code

**Google Callback URL:** `https://rest.lawexa.com/api/auth/google/callback`

**Frontend Redirect (Success & Error):**
```
https://app.lawexa.com/auth/callback?code={temp_code}
```

*Note: Both success and error cases now use the same redirect pattern with a temporary code for consistent handling and improved security.*

### 3. Exchange Code for Token
**Endpoint:** `POST /api/auth/google/exchange`

**Request:**
```http
POST /api/auth/google/exchange HTTP/1.1
Host: rest.lawexa.com
Content-Type: application/json

{
  "code": "32-character-temporary-code"
}
```

**Success Response:**
```json
{
  "message": "Google authentication successful",
  "error": false,
  "token": "your-api-token-here",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "google_id": "1234567890",
    "avatar": "https://lh3.googleusercontent.com/...",
    "role": "user",
    "created_at": "2025-07-06T10:00:00.000000Z",
    "updated_at": "2025-07-06T10:00:00.000000Z"
  }
}
```

**Error Response (OAuth Error):**
```json
{
  "message": "Google authentication failed",
  "error": true,
  "error_code": "oauth_error",
  "error_message": "Detailed error description"
}
```

**Error Response (Invalid Code):**
```json
{
  "message": "Invalid or expired code"
}
```

## Complete Flow

1. **Frontend initiates**: Call `GET /api/auth/google` to get the Google OAuth URL
2. **User redirects**: Open the returned URL in browser/popup for Google login
3. **Backend handles callback**: Google redirects to `/api/auth/google/callback` which:
   - Gets user data from Google
   - Creates/updates user in database (GoogleAuthController.php:30-50)
   - On success: Generates API token and creates temporary code
   - On error: Creates temporary error code with error details
   - Always redirects to frontend: `https://app.lawexa.com/auth/callback?code={temp_code}`
4. **Frontend exchanges code**: Call `POST /api/auth/google/exchange` with the code to get:
   - Success: API token and user data object
   - Error: Error details with error code and message

## Security Features

- **Stateless OAuth**: No session storage required
- **Temporary codes**: 32-character codes expire in 10 minutes
- **Automatic cleanup**: Expired codes are automatically removed
- **Secure token exchange**: Prevents token exposure in URLs
- **Secure error handling**: Error details stored securely, not exposed in URLs
- **Consistent redirect pattern**: Both success and error cases use same URL pattern
- **User matching**: Links existing accounts by email or Google ID

## User Data Structure

The user object includes:
- `id`: Database user ID
- `name`: Full name from Google
- `email`: Email address from Google
- `google_id`: Google account ID
- `avatar`: Profile picture URL
- `role`: User role (default: "user")
- `created_at`: Account creation timestamp
- `updated_at`: Last update timestamp

## Environment Variables Required

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://your-api-domain.com/api/auth/google/callback
APP_FRONTEND_URL=https://your-frontend-domain.com
```

## Implementation Notes

- Users are created if they don't exist
- Existing users are linked to Google account if email matches
- Random password is generated for OAuth users
- Avatar URL is stored and updated from Google profile
- All new users get "user" role by default