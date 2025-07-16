# Authentication System Documentation

## Overview
This document describes a comprehensive authentication and authorization system built with Laravel Sanctum and Laravel Socialite, supporting both traditional email/password authentication and OAuth (Google) integration with role-based access control.

## Core Technologies

### Laravel Sanctum
- **API Token Authentication**: Provides lightweight token-based authentication
- **SPA Authentication**: Supports single-page application authentication
- **Mobile Authentication**: Ideal for mobile API authentication
- **Token Management**: Built-in token lifecycle management

### Laravel Socialite
- **OAuth Integration**: Seamless integration with OAuth providers
- **Google Provider**: Pre-configured Google OAuth support
- **Stateless Authentication**: API-friendly stateless OAuth flows
- **Profile Data Management**: Automatic user profile creation from OAuth data

## Authentication Methods

### 1. Traditional Authentication
- **Email/Password Registration**: Users register with name, email, and password
- **Email/Password Login**: Standard authentication using email credentials
- **Token-Based Authentication**: Laravel Sanctum provides API tokens for secure access
- **Profile Management**: Users can update their profile information

### 2. OAuth Authentication (Laravel Socialite)
- **Google OAuth Integration**: Users can authenticate using Google accounts
- **Stateless OAuth Flow**: API-friendly OAuth implementation using Socialite
- **OAuth State Management**: Secure temporary code exchange system
- **Profile Integration**: User profiles automatically populated from OAuth provider data
- **Account Linking**: Existing email accounts can be linked to OAuth providers

## OAuth Flow Implementation

### Socialite Integration
The system uses Laravel Socialite for OAuth authentication with the following flow:

1. **Initiate OAuth**: `Socialite::driver('google')->stateless()->redirect()`
2. **Handle Callback**: `Socialite::driver('google')->stateless()->user()`
3. **User Creation/Update**: Automatic user account management
4. **Token Generation**: Laravel Sanctum token creation
5. **Secure Exchange**: Temporary code system for frontend token exchange

### OAuth State Management
- **Temporary Codes**: 32-character secure codes for token exchange
- **Expiration Handling**: Automatic cleanup of expired OAuth states
- **Error Handling**: Secure error propagation through temporary codes
- **Frontend Integration**: Seamless integration with frontend applications

## Role-Based Access Control (RBAC)

### User Roles
The system implements a hierarchical role structure with three primary roles:

1. **User** (Default)
   - Basic application access
   - Profile management
   - Standard features
   - Default role for new registrations

2. **Admin**
   - User management for regular users
   - Cannot manage other admins or superadmins
   - Access to administrative dashboards
   - Basic system oversight and user statistics

3. **Superadmin**
   - Full system access
   - Can manage all user roles including other superadmins
   - Complete administrative control
   - System configuration capabilities
   - User deletion privileges

### Permission Model
The system uses role-based permissions with the following hierarchy:
- **Superadmin** > **Admin** > **User**
- Higher roles inherit permissions of lower roles
- Role-specific restrictions enforced at middleware and controller levels
- Dynamic permission checking based on user context

## Authentication Flow

### Registration Process
1. User submits registration form with credentials
2. System validates input (email uniqueness, password strength, role assignment)
3. User account created with specified role (default: 'user')
4. Password hashed using Laravel's secure hashing
5. API token generated using Laravel Sanctum
6. User profile returned with authentication token

### Login Process
1. User submits email/password credentials
2. System validates credentials using Laravel's authentication guard
3. Successful authentication generates new Sanctum API token
4. User profile loaded with relationships (subscriptions, etc.)
5. User resource and token returned for session management

### OAuth Flow (Laravel Socialite)
1. **Frontend Request**: Client requests Google OAuth URL
2. **Socialite Redirect**: `GoogleAuthController::redirectToGoogle()` returns OAuth URL
3. **User Authorization**: User authorizes application on Google
4. **Callback Handling**: `GoogleAuthController::handleGoogleCallback()` processes response
5. **User Lookup**: System checks for existing user by Google ID or email
6. **Account Creation/Update**: Creates new user or updates existing account
7. **Token Generation**: Laravel Sanctum token created
8. **Secure Exchange**: Temporary OAuth state created for secure token exchange
9. **Frontend Redirect**: User redirected to frontend with temporary code
10. **Token Exchange**: Frontend exchanges code for actual authentication token

## Security Features

### Token Management (Laravel Sanctum)
- **Secure Token Generation**: Cryptographically secure token creation
- **Token Validation**: Automatic token validation on protected routes
- **Token Expiration**: Configurable token lifetimes
- **Token Revocation**: Individual token revocation on logout
- **Multi-Device Support**: Multiple tokens per user for different devices

### Password Security
- **Bcrypt Hashing**: Secure password hashing using Laravel's default hasher
- **Password Validation**: Minimum 8 characters with confirmation requirement
- **Random Password Generation**: Auto-generated secure passwords for OAuth users
- **Password Update**: Secure password change functionality

### OAuth Security (Laravel Socialite)
- **Stateless Authentication**: No server-side session storage required
- **Secure State Management**: Temporary codes with expiration
- **CSRF Protection**: Built-in state validation
- **Provider Validation**: Verified OAuth provider responses
- **Error Handling**: Secure error propagation without sensitive data exposure

## Middleware Protection

### Authentication Middleware
- **auth:sanctum**: Laravel Sanctum authentication guard for API protection
- **Automatic Validation**: Token validation on every protected request
- **User Context**: Authenticated user available via `$request->user()`

### Authorization Middleware
- **RoleMiddleware**: Custom middleware for role-based access control
- **Dynamic Role Checking**: Supports multiple role requirements per route
- **Permission Validation**: Real-time permission checking
- **Error Responses**: Clear error messages for unauthorized access

### Role Middleware Implementation
```php
Route::middleware('role:admin,superadmin')->group(function () {
    // Admin-only routes
});
```

## User Profile Management

### Profile Fields
- **Basic Information**: Name, email, avatar URL
- **Authentication Data**: Google ID, email verification status
- **Role Information**: Current role and associated permissions
- **OAuth Integration**: Avatar and profile data from OAuth providers
- **Timestamps**: Account creation and modification tracking

### Profile Operations
- **View Profile**: Authenticated users can view their complete profile
- **Update Profile**: Users can modify name, email, and password
- **Avatar Management**: Support for profile pictures from OAuth providers
- **Account Verification**: Email verification workflows
- **OAuth Linking**: Link existing accounts to OAuth providers

## Database Schema

### Users Table
```sql
- id (Primary Key)
- name (String)
- email (String, Unique)
- email_verified_at (Timestamp, Nullable)
- password (String, Hashed)
- google_id (String, Nullable) -- OAuth ID from Google
- avatar (String, Nullable) -- Profile picture URL
- role (Enum: user, admin, superadmin)
- customer_code (String, Nullable) -- For subscription management
- remember_token (String, Nullable)
- timestamps (created_at, updated_at)
```

### OAuth States Table
```sql
- id (Primary Key)
- code (String, 32 characters, Unique) -- Temporary exchange code
- token (Text, Nullable) -- Sanctum token
- user_data (JSON, Nullable) -- User profile data
- is_error (Boolean, Default: false)
- error_code (String, Nullable)
- error_message (Text, Nullable)
- expires_at (Timestamp)
- timestamps (created_at, updated_at)
```

## Configuration

### Environment Variables
```env
# Socialite Google Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

# Frontend Configuration
APP_FRONTEND_URL=http://localhost:3000
```

### Services Configuration
```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

## API Endpoints

### Authentication Routes
```
POST /api/auth/register        - User registration
POST /api/auth/login          - User login
POST /api/auth/logout         - User logout (requires auth)
GET  /api/auth/me             - Get current user profile (requires auth)
PUT  /api/user/profile        - Update user profile (requires auth)
```

### OAuth Routes (Laravel Socialite)
```
GET  /api/auth/google          - Get Google OAuth URL
GET  /api/auth/google/callback - Handle Google OAuth callback
POST /api/auth/google/exchange - Exchange temporary code for token
```

### Admin Routes
```
GET  /api/admin/dashboard      - Admin dashboard (requires admin+ role)
GET  /api/admin/users          - List users (requires admin+ role)
GET  /api/admin/users/{id}     - Get specific user (requires admin+ role)
PUT  /api/admin/users/{id}     - Update user (requires admin+ role)
DELETE /api/admin/users/{id}   - Delete user (requires superadmin role)
```

## Error Handling

### Authentication Errors
- **Invalid Credentials**: Clear error messages for failed login attempts
- **Token Validation**: Proper error responses for invalid/expired tokens
- **OAuth Failures**: Graceful handling of Socialite OAuth provider errors
- **Validation Errors**: Detailed validation error responses

### Authorization Errors
- **Insufficient Permissions**: HTTP 403 responses with clear role requirements
- **Role Validation**: Detailed permission requirement information
- **Access Denied**: User-friendly error messages with required roles

## Best Practices

### Security Recommendations
1. **HTTPS Only**: Always use HTTPS in production for OAuth callbacks
2. **Token Security**: Store tokens securely on client-side (httpOnly cookies recommended)
3. **Regular Rotation**: Implement token rotation strategies
4. **Rate Limiting**: Apply rate limits to authentication endpoints
5. **Audit Logging**: Log authentication and authorization events
6. **OAuth Validation**: Always validate OAuth provider responses

### Implementation Guidelines
1. **Validation**: Always validate user input using Laravel's validation
2. **Error Handling**: Provide consistent error responses across all endpoints
3. **Testing**: Comprehensive test coverage for both traditional and OAuth flows
4. **Documentation**: Keep API documentation updated with examples
5. **Monitoring**: Monitor authentication patterns and OAuth failures

## Integration Points

### Frontend Integration
- **Token Storage**: Secure storage of Sanctum tokens
- **OAuth Flow**: Handle OAuth redirects and code exchange
- **Role-Based UI**: Conditional rendering based on user roles
- **Error Handling**: Graceful handling of authentication errors

### Laravel Socialite Integration
- **Provider Configuration**: Easy addition of new OAuth providers
- **Stateless Design**: API-friendly OAuth implementation
- **Error Recovery**: Robust error handling for OAuth failures
- **User Mapping**: Flexible user profile mapping from OAuth data

This authentication system provides a robust foundation for secure application access with flexible role management, traditional authentication, and modern OAuth integration using Laravel's best-in-class packages.