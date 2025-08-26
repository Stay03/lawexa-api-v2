<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Only add constraints for databases that support them
        if (in_array($driver, ['mysql', 'pgsql'])) {
            // MySQL and PostgreSQL support CHECK constraints via ALTER TABLE
            
            if ($driver === 'mysql') {
                // MySQL syntax for CHECK constraints (MySQL 8.0.16+)
                DB::statement("ALTER TABLE users ADD CONSTRAINT check_guest_expiry CHECK ((role != 'guest') OR (guest_expires_at IS NOT NULL))");
                DB::statement("ALTER TABLE users ADD CONSTRAINT check_guest_activity CHECK ((role != 'guest') OR (last_activity_at IS NOT NULL))");
            } else {
                // PostgreSQL syntax for CHECK constraints
                DB::statement("ALTER TABLE users ADD CONSTRAINT check_guest_expiry CHECK ((role != 'guest') OR (guest_expires_at IS NOT NULL))");
                DB::statement("ALTER TABLE users ADD CONSTRAINT check_guest_activity CHECK ((role != 'guest') OR (last_activity_at IS NOT NULL))");
            }
        } else {
            // For SQLite and other databases, we rely on application-level validation
            // SQLite doesn't support adding CHECK constraints via ALTER TABLE
            // These constraints would need to be added during initial table creation
            
            // Log a warning for developers
            Log::warning('Guest account CHECK constraints not added for database driver: ' . $driver . '. Relying on application-level validation.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (in_array($driver, ['mysql', 'pgsql'])) {
            // Drop the CHECK constraints
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS check_guest_expiry");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS check_guest_activity");
        }
        // For SQLite, nothing to rollback since constraints weren't added
    }
};
