# Database Connection Test Script

A simple utility script to test SQLite database connectivity and display table information for the Laravel application.

## Overview

This script validates that the SQLite database connection is working properly and provides a quick overview of all tables and their record counts. It's useful for troubleshooting database issues and getting a quick snapshot of your data.

## Usage

```bash
php scripts/test-export.php
```

No command-line arguments or options required.

## What It Does

### 1. Database Connection
- Loads environment variables from `.env` file
- Establishes connection to `database/database.sqlite`
- Uses Laravel's Illuminate Database (Eloquent) for consistency
- Tests connection by querying the SQLite master table

### 2. Table Discovery
- Retrieves all user tables (excludes SQLite system tables)
- Counts records in each table
- Displays results in a formatted list

### 3. Output Information
- Connection status (success/failure)
- Total number of tables found
- Each table name with record count

## Sample Output

### Successful Connection
```
Connected to SQLite database successfully!
Found 15 tables:
  - users: 35 records
  - court_cases: 7187 records
  - files: 24 records
  - notes: 156 records
  - subscriptions: 12 records
  - plans: 3 records
  - subscription_invoices: 45 records
  - personal_access_tokens: 8 records
  - oauth_states: 2 records
  - cache: 0 records
  - cache_locks: 0 records
  - jobs: 0 records
  - job_batches: 0 records
  - failed_jobs: 0 records
  - migrations: 18 records
```

### Connection Error
```
Error: SQLSTATE[HY000] [14] unable to open database file
```

## Prerequisites

### System Requirements
- PHP 7.4+ with PDO SQLite extension
- Composer dependencies installed (`vendor/autoload.php`)
- Valid `.env` file in project root

### File Dependencies
- `database/database.sqlite` must exist
- `.env` file must be present (though SQLite path is hardcoded)
- Laravel vendor dependencies must be installed

## Use Cases

### 1. Database Health Check
Quick verification that your database is accessible and contains expected data:
```bash
php scripts/test-export.php
```

### 2. Migration Verification
After running migrations, verify tables were created:
```bash
# Run migrations
php artisan migrate

# Verify tables exist
php scripts/test-export.php
```

### 3. Development Setup
When setting up a new development environment:
```bash
# Copy database
cp database/database.sqlite.backup database/database.sqlite

# Verify setup
php scripts/test-export.php
```

### 4. Troubleshooting
When experiencing database connection issues:
- Verify file permissions
- Check database file integrity
- Confirm table structure

## Technical Details

### Database Connection
- **Driver**: SQLite via PDO
- **Path**: Hardcoded to `../database/database.sqlite`
- **Connection Method**: Laravel Illuminate Database Capsule
- **Environment**: Loads from `.env` but uses hardcoded SQLite path

### Query Operations
- **Table Discovery**: Queries `sqlite_master` system table
- **Record Counting**: Uses Laravel's `count()` method on each table
- **Error Handling**: Catches and displays connection exceptions

### Dependencies
```php
- illuminate/database (Laravel Eloquent)
- vlucas/phpdotenv (Environment variable loading)
```

## Limitations

### Fixed Configuration
- Only tests SQLite database (not MySQL)
- Database path is hardcoded
- Cannot test different database connections

### Error Handling
- Basic exception catching only
- No detailed error categorization
- Limited troubleshooting information

### Output Format
- Plain text output only
- No JSON or structured output options
- Fixed formatting

## Common Issues

### 1. Database File Not Found
```
Error: SQLSTATE[HY000] [14] unable to open database file
```
**Solutions**:
- Verify `database/database.sqlite` exists
- Check file permissions (readable by web server)
- Ensure correct file path

### 2. Permission Denied
```
Error: SQLSTATE[HY000] [14] unable to open database file
```
**Solutions**:
- Set proper file permissions: `chmod 664 database/database.sqlite`
- Ensure directory permissions: `chmod 755 database/`
- Check ownership settings

### 3. Missing Dependencies
```
Error: Class 'Illuminate\Database\Capsule\Manager' not found
```
**Solutions**:
- Run `composer install`
- Verify `vendor/autoload.php` exists
- Check composer.json dependencies

### 4. Empty Database
```
Connected to SQLite database successfully!
Found 1 tables:
  - migrations: 0 records
```
**Solutions**:
- Run `php artisan migrate`
- Import data if needed
- Verify database is not corrupted

## Enhancement Opportunities

### Potential Improvements
1. **Multi-Database Support**: Test both SQLite and MySQL
2. **Configuration Options**: Command-line database path option
3. **Detailed Diagnostics**: More comprehensive connection testing
4. **Output Formats**: JSON/CSV output options
5. **Table Schema**: Display column information
6. **Performance Metrics**: Connection timing information

### Related Scripts
- **db-switch.php**: For switching between database types
- **migrate-cases.php**: For data migration operations

## File Structure
```
scripts/
├── test-export.php     # This script
├── test-export.md      # This documentation
└── README.md          # Scripts index

database/
└── database.sqlite    # Target database file
```

## Maintenance Notes

### Regular Usage
- Run before/after major database operations
- Include in deployment verification procedures
- Use for environment setup validation

### Monitoring
- Track table growth over time
- Identify empty or unused tables
- Verify migration success