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
- `POST /api/auth/email/verification-notification` (verification.send)

## Troubleshooting

### Route Not Found Error
If you get "Route [verification.verify] not defined" error:

1. **Clear route cache**: `php artisan route:clear`
2. **Rebuild cache**: `php artisan route:cache`
3. **Check routes exist**: `php artisan route:list --name=verification`

### Email Not Sending
If verification emails aren't being sent:

1. **Check queue workers**: `php artisan queue:work`
2. **Check Mailgun settings**: Verify `MAILGUN_DOMAIN`, `MAILGUN_SECRET` in .env
3. **Check logs**: `tail -f storage/logs/laravel.log`

### URL Generation Issues
The VerifyEmailMailable now includes fallback URL generation if routes aren't properly cached.

## Environment Variables
Make sure these are set in your production .env:

```env
# Email Verification
EMAIL_VERIFICATION_EXPIRE=60

# Mail Configuration  
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain
MAILGUN_SECRET=your-secret
MAIL_FROM_ADDRESS=verify@lawexa.com
MAIL_FROM_NAME="Lawexa Team"
```

## Testing Verification Flow

1. **Register new user**: `POST /api/auth/register`
2. **Check email queue**: Verification email should be queued
3. **Process queue**: `php artisan queue:work` (or ensure queue workers running)
4. **Click verification link**: Should verify email and send welcome email
5. **Test protected routes**: Should now work for verified users