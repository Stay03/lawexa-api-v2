# Production Deployment Guide - Statute Lazy Loading System

## Overview
This guide provides step-by-step instructions for deploying the Statute Lazy Loading System to production after pulling the latest changes.

**Commit:** `df6a765` - Add Statute Lazy Loading System with hash-first navigation

---

## Pre-Deployment Checklist

Before starting deployment, ensure:
- [ ] Database backup is completed
- [ ] Redis or Memcached is available (recommended for production)
- [ ] You have SSH access to production server
- [ ] Application is in maintenance mode (if needed)

---

## Deployment Steps

### Step 1: Pull Latest Changes

```bash
# Navigate to application directory
cd /path/to/lawexa-api-v2

# Pull latest changes from main branch
git pull origin main

# Verify the commit
git log -1 --oneline
# Should show: df6a765 Add Statute Lazy Loading System with hash-first navigation
```

---

### Step 2: Install/Update Dependencies

```bash
# Update Composer dependencies (if any new packages were added)
composer install --no-dev --optimize-autoloader

# Note: This feature doesn't add new composer dependencies
```

---

### Step 3: Update Environment Configuration

Add the following configuration to your `.env` file:

```bash
# Statute Lazy Loading Configuration
STATUTE_LAZY_LOADING_ENABLED=true
STATUTE_DEFAULT_LIMIT=5
STATUTE_MAX_LIMIT=50
STATUTE_MAX_RANGE_SIZE=100

# Cache Configuration (IMPORTANT)
# Option 1: With Redis (RECOMMENDED for production)
CACHE_STORE=redis
STATUTE_CACHE_TAGS_ENABLED=true

# Option 2: Without Redis (fallback - cache invalidation won't work optimally)
# CACHE_STORE=database
# STATUTE_CACHE_TAGS_ENABLED=false

# Cache TTL Configuration (optional - defaults shown)
STATUTE_BREADCRUMB_TTL=3600      # 1 hour
STATUTE_POSITION_TTL=1800        # 30 minutes
STATUTE_TOTAL_ITEMS_TTL=3600     # 1 hour

# Order Index Configuration (optional - defaults shown)
STATUTE_ORDER_GAP_SIZE=100
STATUTE_MIN_GAP_THRESHOLD=2
STATUTE_REINDEX_STRATEGY=auto    # auto, manual, or scheduled
```

**Quick Command to Add Configuration:**
```bash
cat >> .env << 'EOF'

# Statute Lazy Loading Configuration
STATUTE_LAZY_LOADING_ENABLED=true
STATUTE_DEFAULT_LIMIT=5
STATUTE_MAX_LIMIT=50
STATUTE_MAX_RANGE_SIZE=100

# Cache Configuration
CACHE_STORE=redis
STATUTE_CACHE_TAGS_ENABLED=true
STATUTE_BREADCRUMB_TTL=3600
STATUTE_POSITION_TTL=1800
STATUTE_TOTAL_ITEMS_TTL=3600

# Order Index Configuration
STATUTE_ORDER_GAP_SIZE=100
STATUTE_MIN_GAP_THRESHOLD=2
STATUTE_REINDEX_STRATEGY=auto
EOF
```

---

### Step 4: Clear Configuration Cache

```bash
# Clear and recache configuration
php artisan config:clear
php artisan config:cache
```

---

### Step 5: Run Database Migrations

```bash
# Run the migration to add order_index columns
php artisan migrate --force

# Expected output:
# Running migrations...
# 2025_10_27_024638_add_order_index_to_statute_tables .... DONE
```

**What this migration does:**
- Adds `order_index` column to `statute_divisions` table (nullable, integer)
- Adds `order_index` column to `statute_provisions` table (nullable, integer)
- Creates composite indexes: `(statute_id, order_index, status)`

---

### Step 6: Populate Order Indices for Existing Content

This is the **most important step** - it populates the `order_index` values for all existing statute content.

```bash
# Populate order indices for all statutes
php artisan statutes:populate-order-index --all

# Expected output:
# Processing all statutes...
# Processing statute: Constitution of the Federal Republic of Nigeria (ID: 1)
# ✓ Indexed 523 items
# Processing statute: Evidence Act (ID: 2)
# ✓ Indexed 156 items
# ...
# Total: 5 statutes processed, 1,234 items indexed
```

**Options:**
```bash
# Populate for a specific statute
php artisan statutes:populate-order-index --statute-id=1

# Dry run (preview without making changes)
php artisan statutes:populate-order-index --all --dry-run

# Force reindex even if indices exist
php artisan statutes:populate-order-index --all --force
```

**⚠️ Important:** Without this step, the lazy loading endpoints will return errors!

---

### Step 7: Validate Order Indices (Optional but Recommended)

```bash
# Validate order index integrity for all statutes
php artisan statutes:validate-indices --all

# Expected output for valid indices:
# Validating all statutes...
# ✓ Constitution of the Federal Republic of Nigeria (ID: 1): Valid (523 items, avg gap: 98.5)
# ✓ Evidence Act (ID: 2): Valid (156 items, avg gap: 100.2)
```

**Options:**
```bash
# Validate specific statute
php artisan statutes:validate-indices --statute-id=1

# Show detailed report
php artisan statutes:validate-indices --all --verbose
```

---

### Step 8: Clear Application Cache

```bash
# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Recache routes (optional, for performance)
php artisan route:cache
```

---

### Step 9: Restart Application Services

Depending on your server setup:

**For PHP-FPM:**
```bash
sudo systemctl restart php8.2-fpm
# or
sudo service php8.2-fpm restart
```

**For Laravel Octane (if used):**
```bash
php artisan octane:reload
```

**For Queue Workers (if running):**
```bash
php artisan queue:restart
```

---

### Step 10: Verify Deployment

#### Test the Endpoints

**1. Get a test token:**
```bash
# Get an existing user token from database or create one via tinker
php artisan tinker
>>> $user = App\Models\User::first();
>>> $token = $user->createToken('production-test')->plainTextToken;
>>> echo $token;
# Copy the token output
>>> exit
```

**2. Test Universal Content Lookup:**
```bash
# Replace {TOKEN} with your actual token
# Replace {statute-slug} and {content-slug} with actual values from your database
curl -X GET "https://rest.lawexa.com/api/statutes/{statute-slug}/content/{content-slug}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {TOKEN}" | jq
```

**3. Test Sequential Navigation:**
```bash
curl -X GET "https://rest.lawexa.com/api/statutes/{statute-slug}/content/sequential?from_order=100&direction=after&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {TOKEN}" | jq
```

**4. Test Range Loading:**
```bash
curl -X GET "https://rest.lawexa.com/api/statutes/{statute-slug}/content/range?start_order=100&end_order=500" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {TOKEN}" | jq
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "division",
    "content": { ... },
    "position": { ... },
    "breadcrumb": [ ... ],
    "children": [ ... ]
  }
}
```

---

## Verification Checklist

After deployment, verify:

- [ ] Migration completed successfully (`order_index` columns exist)
- [ ] Order indices populated for all statutes (run populate command)
- [ ] Validation shows no errors (run validate command)
- [ ] API endpoints return successful responses (test with curl)
- [ ] Cache is working (check Redis/Memcached connection)
- [ ] No errors in application logs
- [ ] Frontend integration working (if deployed)

---

## Monitoring Commands

### Check Order Index Status
```bash
# See how many items have order_index populated
php artisan tinker
>>> use App\Models\StatuteDivision;
>>> use App\Models\StatuteProvision;
>>> echo "Divisions with order_index: " . StatuteDivision::whereNotNull('order_index')->count();
>>> echo "Provisions with order_index: " . StatuteProvision::whereNotNull('order_index')->count();
>>> exit
```

### Check Cache Status
```bash
# If using Redis
redis-cli ping
redis-cli info stats

# Check Laravel cache
php artisan tinker
>>> echo "Cache driver: " . config('cache.default');
>>> echo "Cache tags enabled: " . (config('statute.cache.tags_enabled') ? 'Yes' : 'No');
>>> exit
```

### Monitor Logs
```bash
# Watch application logs
tail -f storage/logs/laravel.log

# Watch for errors
tail -f storage/logs/laravel.log | grep -i error

# Watch for statute lazy loading activity
tail -f storage/logs/laravel.log | grep -i "statute"
```

---

## Rollback Plan (If Needed)

If you encounter critical issues:

### Quick Rollback
```bash
# Revert to previous commit
git revert df6a765 --no-edit

# Rollback migration
php artisan migrate:rollback --step=1 --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Restart services
sudo systemctl restart php8.2-fpm
```

### Database Rollback Only
```bash
# If you only need to rollback the database changes
php artisan migrate:rollback --step=1 --force

# This will remove the order_index columns
```

---

## Performance Optimization Tips

### 1. Enable Redis Cache (Highly Recommended)

**Why Redis?**
- Supports cache tags for efficient invalidation
- 10-100x faster than database cache
- Handles high concurrent load better

**Setup Redis:**
```bash
# Update .env
CACHE_STORE=redis
STATUTE_CACHE_TAGS_ENABLED=true

# Test Redis connection
php artisan tinker
>>> Cache::store('redis')->put('test', 'value', 60);
>>> echo Cache::store('redis')->get('test');
>>> exit
```

### 2. Optimize Order Index Gap Size

**Current default:** 100 (good for most cases)

**Adjust if needed:**
```bash
# For statutes that are frequently updated, increase gap size
STATUTE_ORDER_GAP_SIZE=500

# For mostly static statutes, can use smaller gap
STATUTE_ORDER_GAP_SIZE=50
```

### 3. Monitor Reindexing Needs

```bash
# Check if any statute needs reindexing
php artisan statutes:validate-indices --all

# If average gap is below threshold, reindex
php artisan statutes:populate-order-index --statute-id=X --force
```

---

## Troubleshooting

### Issue: "This cache store does not support tagging"

**Cause:** Using database cache driver with cache tags enabled

**Solution:**
```bash
# Option 1: Enable Redis
CACHE_STORE=redis
STATUTE_CACHE_TAGS_ENABLED=true

# Option 2: Disable cache tags (less efficient)
CACHE_STORE=database
STATUTE_CACHE_TAGS_ENABLED=false

# Clear and recache config
php artisan config:clear
php artisan config:cache
```

### Issue: "Content has no order_index"

**Cause:** Order indices not populated for content

**Solution:**
```bash
# Populate order indices
php artisan statutes:populate-order-index --all
```

### Issue: Endpoints return 404

**Cause:** Routes not registered or cache issue

**Solution:**
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Verify routes exist
php artisan route:list | grep statute
```

### Issue: Slow Performance

**Possible causes and solutions:**

1. **No caching:**
   ```bash
   # Enable Redis
   CACHE_STORE=redis
   STATUTE_CACHE_TAGS_ENABLED=true
   ```

2. **Missing database indexes:**
   ```bash
   # Verify migration ran successfully
   php artisan migrate:status
   ```

3. **Large result sets:**
   ```bash
   # Reduce default limit
   STATUTE_DEFAULT_LIMIT=3
   STATUTE_MAX_LIMIT=25
   ```

---

## Maintenance

### Regular Tasks

**Weekly:**
- Check application logs for errors
- Monitor cache hit rates (if Redis)
- Verify order index integrity

**Monthly:**
- Review and optimize cache TTLs
- Check for statutes needing reindexing
- Monitor API response times

**After Content Updates:**
- Verify cache invalidation is working
- Check order index gaps remain sufficient
- Run validation if major updates

### Commands for Maintenance

```bash
# Weekly health check
php artisan statutes:validate-indices --all

# Check cache status
php artisan tinker
>>> echo "Breadcrumb cache size: " . Cache::tags(['statute:1'])->get('total_items:1');

# Clear specific statute cache
php artisan tinker
>>> Cache::tags(['statute:1'])->flush();

# Monitor database
# Check order_index distribution
SELECT
    statute_id,
    COUNT(*) as total_items,
    MIN(order_index) as min_idx,
    MAX(order_index) as max_idx,
    AVG(order_index) as avg_idx
FROM statute_divisions
WHERE order_index IS NOT NULL
GROUP BY statute_id;
```

---

## Production Configuration Best Practices

### Recommended Production Settings

```env
# .env production configuration

# Enable lazy loading
STATUTE_LAZY_LOADING_ENABLED=true

# Conservative limits for production
STATUTE_DEFAULT_LIMIT=5
STATUTE_MAX_LIMIT=50
STATUTE_MAX_RANGE_SIZE=100

# Redis cache (required for optimal performance)
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Enable cache tags
STATUTE_CACHE_TAGS_ENABLED=true

# Balanced cache TTLs
STATUTE_BREADCRUMB_TTL=3600      # 1 hour
STATUTE_POSITION_TTL=1800        # 30 minutes
STATUTE_TOTAL_ITEMS_TTL=3600     # 1 hour

# Generous gap size for production
STATUTE_ORDER_GAP_SIZE=100
STATUTE_MIN_GAP_THRESHOLD=2

# Auto reindexing (safe for production)
STATUTE_REINDEX_STRATEGY=auto
```

---

## Support and Documentation

### Documentation Files
- **API Documentation:** `Docs/v2/user/statute-lazyload.md`
- **Implementation Guide:** `statute-lazy-loading-implementation.md`
- **API Specification:** `statute-lazy-loading-api-spec.md`
- **Test Documentation:** `STATUTE_LAZY_LOADING_TESTS.md`
- **Frontend Guide:** `Docs/v2/frontend/STATUTE_FRONTEND_IMPLEMENTATION.md`

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --filter=StatuteContentLookupTest

# Run with coverage
php artisan test --coverage
```

### Need Help?
- Review test suite for examples
- Check application logs: `storage/logs/laravel.log`
- Contact backend team for technical support

---

## Success Criteria

Deployment is successful when:
- ✅ All migrations completed without errors
- ✅ Order indices populated for all statutes
- ✅ Validation shows no integrity issues
- ✅ All three API endpoints return successful responses
- ✅ Cache is working (Redis recommended)
- ✅ No errors in application logs
- ✅ Response times are fast (<500ms for content lookup)
- ✅ Frontend integration working (if applicable)

---

## Quick Reference

### Essential Commands (Copy-Paste Ready)

```bash
# Full deployment sequence
cd /path/to/lawexa-api-v2
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan config:cache
php artisan migrate --force
php artisan statutes:populate-order-index --all
php artisan statutes:validate-indices --all
php artisan cache:clear
php artisan route:clear && php artisan route:cache
sudo systemctl restart php8.2-fpm

# Verify deployment
php artisan route:list | grep statute
tail -n 50 storage/logs/laravel.log
```

---

## Deployment Complete ✅

After completing all steps, the Statute Lazy Loading System should be live and operational in production.

**Next Steps:**
1. Monitor logs for the first few hours
2. Check API response times
3. Gather user feedback on performance improvements
4. Update frontend to use new lazy loading endpoints (if not already done)

**Expected Results:**
- 10x faster perceived load times for hash-based navigation
- 90% reduction in initial API calls
- Better user experience for large statutes
- More accurate view analytics
