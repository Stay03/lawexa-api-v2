# Deployment Commands for Live Server

## 🚀 Step-by-Step Deployment Guide

After pulling the latest changes on your live server, run these commands in order:

---

## 1. Pull Latest Changes

```bash
cd /path/to/your/lawexa-api-v2
git pull origin main
```

**Expected output:**
```
From https://github.com/Stay03/lawexa-api-v2
   c7a8aa8..9b7424a  main -> main
Updating c7a8aa8..9b7424a
Fast-forward
 12 files changed, 2883 insertions(+), 2 deletions(-)
```

---

## 2. Clear Route Cache

**Required:** This ensures the new route is registered.

```bash
php artisan route:clear
```

**Expected output:**
```
Route cache cleared successfully.
```

---

## 3. Clear Application Cache (Optional but Recommended)

```bash
php artisan cache:clear
```

**Expected output:**
```
Application cache cleared successfully.
```

---

## 4. Clear Config Cache (If you use config caching)

```bash
php artisan config:clear
```

**Expected output:**
```
Configuration cache cleared successfully.
```

---

## 5. Cache Routes for Production (Recommended)

**Important:** Only do this after clearing caches above.

```bash
php artisan route:cache
```

**Expected output:**
```
Routes cached successfully.
```

---

## 6. Optimize Application (Optional - for Production Performance)

```bash
php artisan optimize
```

**This command runs:**
- Config caching
- Route caching
- View caching

**Expected output:**
```
Configuration cached successfully.
Routes cached successfully.
Files cached successfully.
```

---

## 7. Verify the New Route is Registered

```bash
php artisan route:list | grep "sequential-pure"
```

**Expected output:**
```
GET|HEAD  api/statutes/{statute}/content/sequential-pure ......... StatuteContentController@sequentialPure
```

---

## 8. Test the Endpoint

### Quick Test (Replace with your actual token and statute slug)

```bash
curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure?from_order=0&direction=after&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected:** JSON response with `"format": "sequential_pure"` in the meta

---

## 9. Restart PHP-FPM (If using PHP-FPM)

**Choose the correct command for your setup:**

### For Ubuntu/Debian with PHP 8.2:
```bash
sudo systemctl restart php8.2-fpm
```

### For Ubuntu/Debian with PHP 8.1:
```bash
sudo systemctl restart php8.1-fpm
```

### For CentOS/RHEL:
```bash
sudo systemctl restart php-fpm
```

### Check PHP-FPM Status:
```bash
sudo systemctl status php8.2-fpm
```

---

## 10. Restart Queue Workers (If you use queues)

```bash
php artisan queue:restart
```

**Or if using supervisor:**
```bash
sudo supervisorctl restart all
```

---

## Complete Deployment Script

Copy and run this complete script (adjust PHP version if needed):

```bash
#!/bin/bash

echo "🚀 Deploying Sequential Pure API..."

# 1. Pull changes
echo "📥 Pulling latest changes..."
git pull origin main

# 2. Clear caches
echo "🧹 Clearing caches..."
php artisan route:clear
php artisan cache:clear
php artisan config:clear

# 3. Optimize for production
echo "⚡ Optimizing application..."
php artisan optimize

# 4. Verify route registered
echo "✅ Verifying new route..."
php artisan route:list | grep "sequential-pure"

# 5. Restart PHP-FPM (adjust version if needed)
echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

# 6. Check PHP-FPM status
echo "📊 Checking PHP-FPM status..."
sudo systemctl status php8.2-fpm --no-pager

# 7. Restart queue workers if using queues
if command -v supervisorctl &> /dev/null; then
    echo "🔄 Restarting queue workers..."
    sudo supervisorctl restart all
fi

echo "✅ Deployment complete!"
echo ""
echo "🧪 Test the endpoint:"
echo 'curl -X GET "https://rest.lawexa.com/api/statutes/YOUR_SLUG/content/sequential-pure?from_order=0&direction=after&limit=5" -H "Authorization: Bearer YOUR_TOKEN"'
```

---

## Troubleshooting

### Issue: Route not found (404)

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
php artisan config:clear
sudo systemctl restart php8.2-fpm
```

### Issue: Cache issues (old data showing)

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Permission errors

**Solution:**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Fix cache permissions
sudo chown -R www-data:www-data bootstrap/cache
sudo chmod -R 775 bootstrap/cache
```

### Issue: PHP-FPM not restarting

**Check logs:**
```bash
sudo journalctl -u php8.2-fpm -n 50
```

**Check PHP-FPM pool configuration:**
```bash
sudo php-fpm8.2 -t
```

---

## Post-Deployment Verification

### 1. Check Application Logs
```bash
tail -f storage/logs/laravel.log
```

### 2. Test All Scenarios

```bash
# Test 1: Load from beginning
curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure?from_order=0&direction=after&limit=5" \
  -H "Authorization: Bearer TOKEN"

# Test 2: Load with no breadcrumb
curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure?from_order=300&direction=after&limit=5&include_breadcrumb=false" \
  -H "Authorization: Bearer TOKEN"

# Test 3: Load before
curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure?from_order=500&direction=before&limit=5" \
  -H "Authorization: Bearer TOKEN"

# Test 4: Validation error (should return 422)
curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure" \
  -H "Authorization: Bearer TOKEN"
```

### 3. Monitor Performance

```bash
# Check response times
time curl -X GET "https://rest.lawexa.com/api/statutes/constitution-1999/content/sequential-pure?from_order=0&direction=after&limit=15" \
  -H "Authorization: Bearer TOKEN" > /dev/null

# Should be under 500ms for good performance
```

---

## Environment-Specific Notes

### If using Laravel Octane:
```bash
php artisan octane:reload
```

### If using Laravel Vapor:
```bash
vapor deploy production
```

### If using Forge:
- Go to your site in Forge
- Click "Deploy Now"
- Or set up auto-deployment from GitHub

### If using Docker:
```bash
docker-compose down
docker-compose up -d --build
```

---

## Rollback Plan (If Issues Occur)

### Quick Rollback:
```bash
git reset --hard c7a8aa8  # Previous commit hash
php artisan route:clear
php artisan cache:clear
php artisan optimize
sudo systemctl restart php8.2-fpm
```

### Then push rollback:
```bash
git push origin main --force
```

---

## Success Indicators

✅ Route appears in `php artisan route:list`
✅ Endpoint returns JSON with `"format": "sequential_pure"`
✅ Response time is < 500ms
✅ No errors in `storage/logs/laravel.log`
✅ PHP-FPM status is active and running
✅ Validation errors return proper 422 responses
✅ Invalid statutes return 404

---

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log`
2. Check PHP-FPM logs: `sudo journalctl -u php8.2-fpm -n 100`
3. Verify route cache: `php artisan route:list | grep sequential`
4. Test with curl to isolate frontend vs backend issues
5. Check server resources: `htop` or `free -h`

---

**Deployment Guide Version:** 1.0.0
**Last Updated:** 2025-01-30
**Status:** Production Ready ✅
