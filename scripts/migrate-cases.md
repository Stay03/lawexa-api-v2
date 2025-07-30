# Case Migration Script

Migrates cases from `old_database.sqlite` (my_cases table) to `database.sqlite` (court_cases table) with comprehensive data validation, backup creation, and error handling.

## Overview

This script safely transfers 7,185+ legal cases from the legacy database structure to the new Laravel-based court cases system. It handles schema differences, creates backups, and provides detailed progress tracking.

## Usage

```bash
php scripts/migrate-cases.php [options]
```

### Command-Line Options

| Option | Description | Default |
|--------|-------------|---------|
| `--dry-run` | Preview migration without making changes | false |
| `--skip-backup` | Skip creating backup before migration | false |
| `--default-user=ID` | Set user ID for created_by field | 1 |

### Examples

#### Preview Migration (Recommended First)
```bash
php scripts/migrate-cases.php --dry-run
```

#### Full Migration with Backup
```bash
php scripts/migrate-cases.php
```

#### Migration with Custom User ID
```bash
php scripts/migrate-cases.php --default-user=2
```

#### Migration Without Backup (Not Recommended)
```bash
php scripts/migrate-cases.php --skip-backup
```

## Prerequisites

### Database Requirements
1. **Source Database**: `database/old_database.sqlite` must exist and contain `my_cases` table
2. **Target Database**: `database/database.sqlite` must exist with `court_cases` table
3. **User Validation**: Default user ID must exist in the `users` table

### System Requirements
- PHP 7.4+ with PDO SQLite extension
- Write permissions to `database/backups/` directory
- Sufficient disk space for backup files
- Memory limit appropriate for large datasets (7K+ records)

## Migration Process

### 1. Pre-Migration Validation
- Verifies both database files exist
- Checks target user exists in users table  
- Counts source records (7,185 cases expected)
- Identifies existing records in target database
- Scans for potential slug conflicts

### 2. Backup Creation
- Creates timestamped backup: `backup_migration_YYYY-MM-DD_HH-MM-SS.sqlite`
- Stored in `database/backups/` directory
- Full copy of target database before migration
- Skipped only with `--skip-backup` flag

### 3. Data Migration
- **Batch Processing**: 100 records per batch for memory efficiency
- **Transaction Safety**: Full rollback on any error
- **Duplicate Handling**: Skips records with existing slugs
- **Progress Tracking**: Real-time progress indicators
- **Error Logging**: Detailed error reporting with automatic stop after 10 errors

### 4. Post-Migration Verification
- Counts final records and validates totals
- Samples migrated data for verification
- Displays migration statistics and summary

## Field Mapping

### Direct Mappings
| Old Field (my_cases) | New Field (court_cases) | Notes |
|---------------------|------------------------|-------|
| title | title | Direct copy |
| body | body | Direct copy |
| report | report | VARCHAR → TEXT |
| course | course | Direct copy |
| topic | topic | Direct copy |
| tag | tag | Direct copy |
| principles | principles | CLOB → TEXT |
| level | level | Direct copy |
| slug | slug | Direct copy |
| court | court | Direct copy |
| date | date | Direct copy |
| country | country | Direct copy |
| citation | citation | Direct copy |
| judges | judges | VARCHAR → TEXT |
| judicial_precedent | judicial_precedent | VARCHAR → TEXT |
| created_at | created_at | Auto-generated if NULL |
| updated_at | updated_at | Auto-generated if NULL |

### New Fields
| Field | Value | Notes |
|-------|-------|-------|
| created_by | User ID from --default-user | Required foreign key |

### Excluded Fields
Legacy fields not migrated to new schema:
- `similar` - Deprecated functionality
- `similarID` - Deprecated functionality  
- `cases_cited` - Not in new schema
- `statutes_cited` - Not in new schema
- `full_report_id` - Deprecated
- `full_report_text` - Superseded by report field

## Sample Output

### Dry Run Example
```
=== CASE MIGRATION SCRIPT ===
Mode: DRY RUN
Found 7185 cases in old database
Found 2 existing cases in new database
Found 0 potential slug conflicts

=== DRY RUN SUMMARY ===
Would migrate: 7185 cases
Potential conflicts: 0 cases
Estimated batches: 72
```

### Live Migration Example
```
=== STARTING MIGRATION ===
Processing batch 1/72 (offset: 0)...
  Migrated: 50 cases...
  Migrated: 100 cases...
...
=== MIGRATION COMPLETE ===
Successfully migrated: 7185 cases
Skipped (duplicates): 0 cases
Errors encountered: 0 cases
✓ Data integrity check passed!
```

## Error Handling

### Common Issues

1. **Database File Not Found**
   ```
   Error: Old database file not found: database/old_database.sqlite
   ```
   **Solution**: Verify file paths and permissions

2. **User ID Not Found**
   ```
   Error: Default user ID 1 does not exist in the new database
   ```
   **Solution**: Use `--default-user=ID` with valid user ID

3. **Migration Errors**
   ```
   Error migrating case ID 123: SQLSTATE[23000]: Integrity constraint violation
   ```
   **Solution**: Check for foreign key conflicts or data validation issues

4. **Transaction Rollback**
   ```
   Too many errors encountered, stopping migration
   Migration failed. Database rolled back to original state.
   ```
   **Solution**: Review error logs, fix data issues, retry

### Recovery Process

If migration fails:
1. **Database State**: Automatically rolled back to original state
2. **Backup Available**: Restore from timestamped backup if needed
3. **Error Analysis**: Review error messages for specific issues
4. **Retry Strategy**: Fix underlying issues and re-run migration

## Performance Considerations

### Memory Usage
- **Batch Size**: 100 records per batch prevents memory exhaustion
- **Large Text Fields**: Some cases contain extensive legal text
- **Chunked Processing**: Progress indicators every 50 records

### Processing Time
- **Expected Duration**: ~2-5 minutes for 7,185 records
- **Progress Tracking**: Real-time batch progress display
- **Database I/O**: SQLite file-based operations

## File Structure

```
database/
├── database.sqlite           # Target database
├── old_database.sqlite      # Source database  
└── backups/
    └── backup_migration_*.sqlite  # Auto-created backups

scripts/
├── migrate-cases.php        # Migration script
└── migrate-cases.md        # This documentation
```

## Security Considerations

- **Backup Security**: Contains full database copy in backups directory
- **User Assignment**: All migrated cases assigned to single user ID
- **Data Validation**: No sensitive data sanitization performed
- **File Permissions**: Ensure backups directory is not web-accessible

## Technical Implementation

### Database Connections
- **PDO SQLite**: Direct database connections for both source and target
- **Error Mode**: Exception throwing enabled for proper error handling
- **Transaction Management**: Full ACID compliance with rollback support

### SQL Operations
- **Prepared Statements**: All queries use prepared statements for security
- **Batch Processing**: LIMIT/OFFSET for memory-efficient processing  
- **Conflict Detection**: EXISTS queries for duplicate prevention
- **Integrity Checks**: COUNT validation before and after migration

### Performance Optimizations
- **Single Transaction**: All changes wrapped in one transaction
- **Prepared Statement Reuse**: Statements prepared once, executed many times
- **Chunked Progress**: Minimal console output to avoid I/O overhead
- **Memory Management**: Batch processing prevents memory leaks

## Maintenance

### Regular Tasks
- **Backup Cleanup**: Remove old backup files periodically
- **Performance Monitoring**: Track migration times for large datasets
- **Schema Updates**: Update field mappings when schema changes

### Future Enhancements
- **Resume Capability**: Continue from failed batch
- **Custom Field Mapping**: Configuration-based field mapping
- **Parallel Processing**: Multi-threaded batch processing
- **Data Transformation**: Custom data transformation hooks