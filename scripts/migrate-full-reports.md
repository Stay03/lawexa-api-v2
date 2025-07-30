# Full Report Text Migration Script

Migrates `full_report_text` from `old_database.sqlite` (my_cases table) to `database.sqlite` (case_reports table) with comprehensive data validation, backup creation, and error handling.

## Overview

This script safely transfers full report text data from the legacy database to a new dedicated `case_reports` table in the Laravel-based system. It matches records by title since IDs differ between databases, creates backups, and provides detailed progress tracking with comprehensive logging.

## Usage

```bash
php scripts/migrate-full-reports.php [options]
```

### Command-Line Options

| Option | Description | Default |
|--------|-------------|---------|
| `--dry-run` | Preview migration without making changes | false |
| `--skip-backup` | Skip creating backup before migration | false |

### Examples

#### Preview Migration (Recommended First)
```bash
php scripts/migrate-full-reports.php --dry-run
```

#### Full Migration with Backup
```bash
php scripts/migrate-full-reports.php
```

#### Migration Without Backup (Not Recommended)
```bash
php scripts/migrate-full-reports.php --skip-backup
```

## Prerequisites

### Database Requirements
1. **Source Database**: `database/old_database.sqlite` must exist with `my_cases` table containing `full_report_text`
2. **Target Database**: `database/database.sqlite` must exist with both `court_cases` and `case_reports` tables
3. **Migration**: `case_reports` table must be created first with `php artisan migrate`

### System Requirements
- PHP 7.4+ with PDO SQLite extension
- Write permissions to `database/backups/` directory
- Sufficient disk space for backup files and log files

## Migration Process

### 1. Pre-Migration Validation
- Verifies both database files exist
- Checks `case_reports` table exists in target database
- Counts source records with non-empty `full_report_text`
- Identifies existing records in target database
- In dry-run mode: samples cases to test title matching

### 2. Backup Creation
- Creates timestamped backup: `backup_full_reports_migration_YYYY-MM-DD_HH-MM-SS.sqlite`
- Stored in `database/backups/` directory
- Full copy of target database before migration
- Skipped only with `--skip-backup` flag

### 3. Data Migration
- **Title-Based Matching**: Links records by matching `title` field between databases
- **Batch Processing**: 100 records per batch for memory efficiency
- **Transaction Safety**: Full rollback on critical errors
- **Duplicate Handling**: Skips records that already have reports
- **Progress Tracking**: Real-time progress indicators
- **Error Logging**: Detailed error reporting with limit of 10 errors before stopping

### 4. Post-Migration Verification
- Counts final records and validates totals
- Displays migration statistics and summary
- Creates comprehensive log of all operations

## Field Mapping

### Source to Target
| Old Field (my_cases) | New Field (case_reports) | Notes |
|------------------|-------------------------|-------|
| title | Used for matching only | Links to court_cases.title |
| full_report_text | full_report_text | Direct copy as longText |

### Generated Fields
| Field | Value | Notes |
|-------|-------|-------|
| case_id | Found via title match | Foreign key to court_cases.id |
| created_at | Current timestamp | Auto-generated |
| updated_at | Current timestamp | Auto-generated |

## Logging

### Log File Location
- `database/backups/full_reports_migration_log_YYYY-MM-DD_HH-MM-SS.txt`
- Timestamped entries for all operations
- Includes success, skip, and error details

### Log Contents
- Migration start/end times
- Database connection status
- Batch processing progress
- Individual case processing results
- Error details and stack traces
- Final statistics and counts

## Sample Output

### Dry Run Example
```
=== FULL REPORT TEXT MIGRATION SCRIPT ===
Old DB: database/old_database.sqlite
New DB: database/database.sqlite
Mode: DRY RUN

✓ Connected to both databases
Found 1250 cases with full_report_text in old database
Found 0 existing case reports in new database

=== DRY RUN ANALYSIS ===
Sample cases with full_report_text:
  - Title: ABACHA V. FAWEHINMI... (Text: 15234 chars) - ✓ Match found (ID: 42)
  - Title: OKOGIE V. THE ATTORNEY GENERAL... (Text: 8756 chars) - ✓ Match found (ID: 156)

=== DRY RUN SUMMARY ===
Would process: 1250 cases
Estimated batches: 13
```

### Live Migration Example
```
=== STARTING MIGRATION ===
Processing batch 1/13 (offset: 0)...
  Migrated: 50 cases...
  Migrated: 100 cases...
Processing batch 2/13 (offset: 100)...
...
=== MIGRATION COMPLETE ===
Successfully migrated: 1185 cases
Skipped (no match/duplicate): 65 cases
Errors encountered: 0 cases
Total case reports in database: 1185
✓ Migration completed successfully!
```

## Error Handling

### Common Issues

1. **Missing case_reports Table**
   ```
   Error: case_reports table does not exist in new database
   ```
   **Solution**: Run `php artisan migrate` first

2. **No Title Matches**
   ```
   SKIPPED: No matching case found for title: [title]
   ```
   **Solution**: Normal for cases that don't exist in new database

3. **Database Connection Issues**
   ```
   Error: Old database file not found
   ```
   **Solution**: Verify file paths and permissions

4. **Transaction Rollback**
   ```
   Too many errors encountered, stopping migration
   Transaction rolled back due to error
   ```
   **Solution**: Review log file for specific errors

### Recovery Process

If migration fails:
1. **Database State**: Automatically rolled back to original state
2. **Backup Available**: Restore from timestamped backup if needed
3. **Log Analysis**: Review detailed log file for error patterns
4. **Retry Strategy**: Fix underlying issues and re-run migration

## Performance Considerations

### Memory Usage
- **Batch Size**: 100 records per batch prevents memory exhaustion
- **Large Text Fields**: Full report texts can be very large
- **Chunked Progress**: Progress indicators every 50 records

### Processing Time
- **Expected Duration**: Varies based on text size and case count
- **Progress Tracking**: Real-time batch progress display
- **Database I/O**: SQLite file-based operations

## File Structure

```
database/
├── database.sqlite                    # Target database
├── old_database.sqlite               # Source database (read-only)
└── backups/
    ├── backup_full_reports_migration_*.sqlite  # Auto-created backups
    └── full_reports_migration_log_*.txt        # Migration logs

scripts/
├── migrate-full-reports.php         # Migration script
└── migrate-full-reports.md          # This documentation
```

## Security Considerations

- **Read-Only Access**: Old database is never modified
- **Backup Security**: Contains full database copy in backups directory
- **Data Integrity**: No sensitive data sanitization performed
- **File Permissions**: Ensure backups directory is not web-accessible
- **Transaction Safety**: Full rollback on any critical errors

## Technical Implementation

### Database Operations
- **PDO SQLite**: Direct database connections with exception handling
- **Transaction Management**: ACID compliance with rollback support
- **Prepared Statements**: All queries use prepared statements for security

### Matching Strategy
- **Title-Based Linking**: Uses exact title match between databases
- **Duplicate Prevention**: Checks for existing reports before insertion
- **Error Tolerance**: Continues processing despite individual failures

### Performance Optimizations
- **Single Transaction**: All changes wrapped in one transaction
- **Prepared Statement Reuse**: Statements prepared once, executed many times
- **Batch Processing**: Memory-efficient processing of large datasets
- **Progress Indicators**: Minimal console output to reduce I/O overhead