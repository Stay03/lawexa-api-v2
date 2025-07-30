# Scripts Directory

Utility scripts for database management, testing, and data migration operations.

## Available Scripts

### Database Management

#### [db-switch.php](db-switch.md)
Switch between SQLite and MySQL databases with automatic data migration and backup creation.
```bash
php scripts/db-switch.php <target_db> [options]
```
**Key Features**: Database switching, automatic backups, data migration, environment configuration

#### [migrate-cases.php](migrate-cases.md)  
Migrate legal cases from legacy database to new Laravel-based system.
```bash
php scripts/migrate-cases.php [--dry-run] [--skip-backup] [--default-user=ID]
```
**Key Features**: Batch processing, conflict detection, transaction safety, comprehensive validation

### Testing & Diagnostics

#### [test-export.php](test-export.md)
Test SQLite database connectivity and display table information.
```bash
php scripts/test-export.php
```
**Key Features**: Connection testing, table discovery, record counting, troubleshooting

## Quick Start

### Check Database Status
```bash
php scripts/test-export.php
```

### Switch Database (with migration)
```bash
php scripts/db-switch.php mysql --migrate-before --verbose
```

### Migrate Legacy Cases (preview first)
```bash
php scripts/migrate-cases.php --dry-run
php scripts/migrate-cases.php
```

## Prerequisites

- PHP 7.4+ with PDO SQLite extension
- Laravel project with Illuminate Database
- Composer dependencies installed
- Appropriate database files and permissions

## File Structure

```
scripts/
├── README.md           # This index
├── db-switch.php       # Database switching utility
├── db-switch.md        # Database switching documentation
├── migrate-cases.php   # Case migration utility  
├── migrate-cases.md    # Case migration documentation
├── test-export.php     # Database testing utility
└── test-export.md      # Database testing documentation
```

## Support

For detailed usage instructions, troubleshooting, and examples, see the individual documentation files linked above.