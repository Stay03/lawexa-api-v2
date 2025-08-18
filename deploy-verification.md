# Email Verification Deployment Instructions

## Production Deployment Commands

Run these commands on your production server to properly deploy the email verification system:

```bash
# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Cache for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify routes are properly registered
php artisan route:list --name=verification
```

## Expected Routes
After deployment, you should see these routes:
- `GET /api/auth/email/verify/{id}/{hash}` (verification.verify)
- `GET /api/auth/email/verify-debug/{id}/{hash}` (verification.debug) - for debugging
- `POST /api/auth/email/verification-notification` (verification.send)

## Troubleshooting "Forbidden" Error

### Step 1: Use Debug Endpoint
Replace your verification URL with the debug endpoint to diagnose the issue:

**Original URL:**
```
https://rest.lawexa.com/api/auth/email/verify/63/a7dc88b87d2574481c409200b1fefbfba8a793a6?expires=1755535009&signature=c30e...
```

**Debug URL:**
```
https://rest.lawexa.com/api/auth/email/verify-debug/63/a7dc88b87d2574481c409200b1fefbfba8a793a6?expires=1755535009&signature=c30e...
```

This will show you detailed debug information including:
- Whether the user exists
- If the hash matches
- If the signature is valid
- App key configuration status

### Step 2: Check Common Issues

1. **APP_KEY not set or different**: 
   ```bash
   php artisan config:show app.key
   ```
   The APP_KEY must be the same as when the verification URL was generated.

2. **Route cache issues**:
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

3. **Signature validation failing**: Check if APP_URL matches the domain making the request

### Step 3: Manual Fix
If the debug shows the signature is invalid but everything else is correct, you can use the debug endpoint which will still verify the email if the hash matches.

## Environment Variables
Make sure these are set in your production .env:

```env
# Application
APP_URL=https://rest.lawexa.com
APP_KEY=your-32-character-key

# Frontend (for redirects after verification)
FRONTEND_URL=https://your-frontend-domain.com

# Email Verification
EMAIL_VERIFICATION_EXPIRE=60

# Mail Configuration  
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain
MAILGUN_SECRET=your-secret
MAIL_FROM_ADDRESS=verify@lawexa.com
MAIL_FROM_NAME="Lawexa Team"
```

## User Experience Flow

### For Browser Requests:
1. User clicks verification link in email
2. API verifies email and redirects to frontend: `{FRONTEND_URL}/email-verification?status=success&message=Email verified successfully`
3. Frontend shows success/error page

### For API Requests:
1. API client makes request with `Accept: application/json` header
2. Returns JSON response with verification result

## Testing Verification Flow

1. **Register new user**: `POST /api/auth/register`
2. **Get verification email**: Check email or queue
3. **Test debug endpoint first**: Use the debug URL to diagnose issues
4. **Use main endpoint**: Once debug shows everything is working
5. **Verify redirect**: Should redirect to frontend success page
6. **Test protected routes**: Should now work for verified users

## Remove Debug Route (After Fixing)
Once verification is working, remove the debug route from `routes/api.php`:
```php
// Remove this line after debugging is complete
Route::get('email/verify-debug/{id}/{hash}', [AuthController::class, 'debugVerifyEmail'])
    ->name('verification.debug');
```