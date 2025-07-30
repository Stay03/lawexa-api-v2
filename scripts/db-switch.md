# Database Switching Script

This script allows you to switch between SQLite and MySQL databases while preserving your data through export/import functionality.

## Features

- Switch between SQLite and MySQL databases
- Automatic data export/import with migration options
- Backup creation before switching
- Environment file (.env) manipulation
- Progress indicators and detailed logging
- Error handling and rollback support

## Prerequisites

1. Ensure you have both SQLite and MySQL PHP extensions installed
2. For MySQL switching, ensure MySQL server is running and accessible
3. Have valid MySQL credentials configured in your .env file (can be commented out initially)

## Usage

```bash
php scripts/db-switch.php <target_db> [options]
```

### Arguments
- `target_db`: Target database type (`sqlite` or `mysql`)

### Options
- `--migrate-before`: Export current data before switching, then import after migrations
- `--migrate-after`: Switch database first, run migrations, then import from latest backup
- `--verbose, -v`: Show detailed output during operations

## Examples

### Switch to MySQL and migrate data
```bash
php scripts/db-switch.php mysql --migrate-before
```

### Switch to SQLite with verbose output
```bash
php scripts/db-switch.php sqlite --migrate-before --verbose
```

### Switch to MySQL without data migration (backup only)
```bash
php scripts/db-switch.php mysql
```

## What the Script Does

### 1. Database Detection
- Reads current database connection from `.env` file
- Validates target database is different from current

### 2. Environment Configuration
When switching to MySQL:
- Sets `DB_CONNECTION=mysql`
- Uncomments MySQL configuration lines in .env
- Comments out SQLite-specific configurations

When switching to SQLite:
- Sets `DB_CONNECTION=sqlite`
- Comments MySQL configuration lines
- Enables SQLite-specific configurations

### 3. Data Migration Process

#### `--migrate-before` option:
1. Export all data from current database to JSON backup
2. Switch database connection in .env
3. Run Laravel migrations on new database
4. Import data from backup

#### `--migrate-after` option:
1. Switch database connection in .env
2. Run Laravel migrations on new database  
3. Import data from the most recent backup file

#### No migration flags:
1. Create backup of current data
2. Switch database connection
3. Run migrations (empty database)

### 4. Backup System
- Backups stored in `database/backups/` directory
- Filename format: `backup_{source_db}_{timestamp}.json`
- Includes all tables except system tables (migrations)
- JSON format for cross-database compatibility

## MySQL Configuration

Before switching to MySQL, ensure your .env file has MySQL credentials configured:

```env
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306  
# DB_DATABASE=lawexa_api_dev
# DB_USERNAME=root  
# DB_PASSWORD=your_password
```

The script will automatically uncomment these lines when switching to MySQL.

## Troubleshooting

### Common Issues

1. **MySQL Connection Failed**
   - Verify MySQL server is running
   - Check MySQL credentials in .env file
   - Ensure database exists or user has CREATE privileges

2. **Migration Errors**
   - Verify Laravel migrations are up to date
   - Check database user has required permissions
   - Review migration files for database-specific syntax

3. **Import Errors**
   - Large datasets may need memory limit adjustments
   - Check for foreign key constraint issues
   - Verify backup file integrity

### Recovery

If something goes wrong:
1. Check the `database/backups/` folder for recent backups
2. Manually restore .env file from backup
3. Use Laravel's migration rollback if needed
4. Import backup data manually if required

## File Structure

```
scripts/
├── db-switch.php       # Main switching script
├── db-switch.md        # This documentation
├── test-export.php     # Database connection test
└── README.md          # Scripts index

database/
├── backups/           # Backup storage directory
└── database.sqlite    # SQLite database file
```

## Technical Details

- Uses Laravel's Illuminate Database (Eloquent) for database operations
- Supports chunked data import for large datasets (100 records per chunk)
- Temporarily disables foreign key checks during import
- Handles both object and array data structures in backups
- Cross-platform compatible (Windows/Linux/Mac)

## Security Notes

- Backup files contain all your data in JSON format
- Ensure `database/backups/` directory is not web-accessible
- Consider encrypting backups for sensitive data
- Regularly clean old backup files to save disk space