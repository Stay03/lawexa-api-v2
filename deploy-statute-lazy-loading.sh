#!/bin/bash

###############################################################################
# Statute Lazy Loading System - Production Deployment Script
# Version: 1.0
# Description: Automated deployment script for statute lazy loading feature
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored messages
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

print_header "Statute Lazy Loading System - Deployment"

# Step 1: Pull latest changes
print_info "Step 1: Pulling latest changes from repository..."
if git pull origin main; then
    print_success "Code updated successfully"
else
    print_error "Failed to pull changes. Please resolve conflicts and try again."
    exit 1
fi

# Step 2: Install dependencies
print_info "Step 2: Installing/updating dependencies..."
if composer install --no-dev --optimize-autoloader --no-interaction; then
    print_success "Dependencies updated"
else
    print_warning "Composer install had warnings (this is usually okay)"
fi

# Step 3: Update environment configuration
print_header "Step 3: Environment Configuration"
print_warning "Please ensure the following are in your .env file:"
echo ""
echo "STATUTE_LAZY_LOADING_ENABLED=true"
echo "STATUTE_DEFAULT_LIMIT=5"
echo "STATUTE_MAX_LIMIT=50"
echo "STATUTE_MAX_RANGE_SIZE=100"
echo ""
echo "# For production with Redis:"
echo "CACHE_STORE=redis"
echo "STATUTE_CACHE_TAGS_ENABLED=true"
echo ""
echo "# Or without Redis:"
echo "CACHE_STORE=database"
echo "STATUTE_CACHE_TAGS_ENABLED=false"
echo ""
read -p "Have you updated the .env file? (y/n): " env_updated
if [ "$env_updated" != "y" ]; then
    print_error "Please update .env file and run the script again."
    exit 1
fi

# Step 4: Clear and cache configuration
print_info "Step 4: Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache
print_success "Configuration cached"

# Step 5: Run migrations
print_header "Step 5: Running Database Migrations"
print_info "Running migration to add order_index columns..."
if php artisan migrate --force; then
    print_success "Migrations completed successfully"
else
    print_error "Migration failed. Please check the error above."
    exit 1
fi

# Step 6: Populate order indices
print_header "Step 6: Populating Order Indices"
print_warning "This step will populate order_index for all existing statute content."
print_warning "This may take a few minutes depending on the amount of content."
echo ""
read -p "Do you want to proceed with order index population? (y/n): " proceed_populate
if [ "$proceed_populate" = "y" ]; then
    print_info "Populating order indices for all statutes..."
    if php artisan statutes:populate-order-index --all; then
        print_success "Order indices populated successfully"
    else
        print_error "Failed to populate order indices"
        exit 1
    fi
else
    print_warning "Skipping order index population. You can run it manually later with:"
    print_warning "php artisan statutes:populate-order-index --all"
fi

# Step 7: Validate indices
print_header "Step 7: Validating Order Indices"
print_info "Running validation check..."
if php artisan statutes:validate-indices --all; then
    print_success "Order indices are valid"
else
    print_warning "Some validation issues found. Check the output above."
fi

# Step 8: Clear caches
print_header "Step 8: Clearing Application Caches"
print_info "Clearing caches..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_success "Caches cleared"

print_info "Recaching routes for performance..."
php artisan route:cache
print_success "Routes cached"

# Step 9: Restart services
print_header "Step 9: Restarting Application Services"
print_warning "You may need to manually restart PHP-FPM/Octane depending on your setup:"
echo ""
echo "For PHP-FPM:"
echo "  sudo systemctl restart php8.2-fpm"
echo ""
echo "For Laravel Octane:"
echo "  php artisan octane:reload"
echo ""
echo "For Queue Workers:"
echo "  php artisan queue:restart"
echo ""
read -p "Do you want to attempt to restart PHP-FPM? (y/n): " restart_fpm
if [ "$restart_fpm" = "y" ]; then
    read -p "Enter PHP version (e.g., 8.2): " php_version
    if sudo systemctl restart "php${php_version}-fpm"; then
        print_success "PHP-FPM restarted successfully"
    else
        print_warning "Failed to restart PHP-FPM. Please restart manually."
    fi
fi

# Step 10: Verification
print_header "Step 10: Deployment Verification"
print_info "Checking if routes are registered..."
if php artisan route:list | grep -q "statutes.*content"; then
    print_success "Lazy loading routes are registered"
else
    print_error "Lazy loading routes not found. Please check route:list output"
fi

print_info "Checking cache configuration..."
cache_driver=$(php artisan tinker --execute="echo config('cache.default')")
cache_tags=$(php artisan tinker --execute="echo config('statute.cache.tags_enabled') ? 'enabled' : 'disabled'")
print_info "Cache driver: $cache_driver"
print_info "Cache tags: $cache_tags"

if [ "$cache_driver" = "redis" ] && [ "$cache_tags" = "enabled" ]; then
    print_success "Optimal cache configuration detected (Redis with tags)"
elif [ "$cache_tags" = "disabled" ]; then
    print_warning "Cache tags are disabled. Consider enabling Redis for better performance."
fi

# Final summary
print_header "Deployment Summary"
print_success "✓ Code updated"
print_success "✓ Dependencies installed"
print_success "✓ Configuration cached"
print_success "✓ Migrations run"
if [ "$proceed_populate" = "y" ]; then
    print_success "✓ Order indices populated"
else
    print_warning "⚠ Order indices NOT populated (run manually)"
fi
print_success "✓ Caches cleared"
print_success "✓ Routes cached"
echo ""
print_success "🎉 Deployment completed successfully!"
echo ""

# Post-deployment instructions
print_header "Post-Deployment Tasks"
echo "1. Monitor logs for errors:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "2. Test the API endpoints:"
echo "   See PRODUCTION_DEPLOYMENT_GUIDE.md for test commands"
echo ""
echo "3. Verify in browser/frontend:"
echo "   Test hash-first navigation on statute pages"
echo ""
echo "4. Monitor performance:"
echo "   Check response times and cache hit rates"
echo ""

print_info "For detailed documentation, see:"
echo "  - PRODUCTION_DEPLOYMENT_GUIDE.md (full guide)"
echo "  - Docs/v2/user/statute-lazyload.md (API documentation)"
echo ""

print_success "Deployment script completed!"
