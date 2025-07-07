# Google OAuth Authentication Flow

## Overview
The Google signup/login flow uses a **secure 3-step process** with temporary codes to prevent token exposure in URLs.

## API Endpoints

### 1. Get Google OAuth URL
**Endpoint:** `GET /api/auth/google`

**Request:**
```http
GET /api/auth/google HTTP/1.1
Host: your-api-domain.com
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

**Google Callback URL:** `{API_URL}/api/auth/google/callback`

**Frontend Redirect:**
```
{FRONTEND_URL}/auth/callback?code={temp_code}
```

**Error Redirect:**
```
{FRONTEND_URL}/auth/callback?error={error_message}
```

### 3. Exchange Code for Token
**Endpoint:** `POST /api/auth/google/exchange`

**Request:**
```http
POST /api/auth/google/exchange HTTP/1.1
Host: your-api-domain.com
Content-Type: application/json

{
  "code": "32-character-temporary-code"
}
```

**Success Response:**
```json
{
  "message": "Google authentication successful",
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

**Error Response:**
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
   - Generates API token
   - Creates temporary 32-character code (expires in 10 minutes)
   - Redirects to frontend: `{FRONTEND_URL}/auth/callback?code={temp_code}`
4. **Frontend exchanges code**: Call `POST /api/auth/google/exchange` with the code to get:
   - API token for authenticated requests
   - User data object

## Security Features

- **Stateless OAuth**: No session storage required
- **Temporary codes**: 32-character codes expire in 10 minutes
- **Automatic cleanup**: Expired codes are automatically removed
- **Secure token exchange**: Prevents token exposure in URLs
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