<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for ENUM, so we need to recreate the table
            $this->updateSqliteEnum();
        } else {
            // MySQL/PostgreSQL can use ALTER COLUMN
            DB::statement("ALTER TABLE statute_divisions MODIFY COLUMN division_type ENUM('part', 'chapter', 'article', 'title', 'book', 'division', 'section', 'subsection', 'schedule', 'order') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for ENUM, so we need to recreate the table
            $this->revertSqliteEnum();
        } else {
            // MySQL/PostgreSQL can use ALTER COLUMN
            DB::statement("ALTER TABLE statute_divisions MODIFY COLUMN division_type ENUM('part', 'chapter', 'article', 'title', 'book', 'division', 'section', 'subsection', 'schedule') NOT NULL");
        }
    }

    /**
     * Update SQLite enum by recreating table with new enum values
     */
    private function updateSqliteEnum(): void
    {
        Schema::table('statute_divisions', function (Blueprint $table) {
            // Create temporary table with new enum
            DB::statement('CREATE TABLE statute_divisions_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                slug VARCHAR NOT NULL,
                statute_id INTEGER NOT NULL,
                parent_division_id INTEGER,
                division_type VARCHAR CHECK (division_type IN (\'part\', \'chapter\', \'article\', \'title\', \'book\', \'division\', \'section\', \'subsection\', \'schedule\', \'order\')) NOT NULL,
                division_number VARCHAR NOT NULL,
                division_title VARCHAR NOT NULL,
                division_subtitle VARCHAR,
                content TEXT,
                sort_order INTEGER NOT NULL DEFAULT 0,
                level INTEGER NOT NULL DEFAULT 1,
                status VARCHAR CHECK (status IN (\'active\', \'repealed\', \'amended\')) NOT NULL DEFAULT \'active\',
                effective_date DATE,
                created_at DATETIME,
                updated_at DATETIME,
                range VARCHAR,
                FOREIGN KEY(statute_id) REFERENCES statutes(id) ON DELETE CASCADE,
                FOREIGN KEY(parent_division_id) REFERENCES statute_divisions(id) ON DELETE CASCADE
            )');
            
            // Copy data from original table
            DB::statement('INSERT INTO statute_divisions_temp SELECT * FROM statute_divisions');
            
            // Drop original table
            DB::statement('DROP TABLE statute_divisions');
            
            // Rename temporary table
            DB::statement('ALTER TABLE statute_divisions_temp RENAME TO statute_divisions');
            
            // Recreate indexes
            DB::statement('CREATE UNIQUE INDEX statute_divisions_statute_id_slug_unique ON statute_divisions (statute_id, slug)');
            DB::statement('CREATE INDEX statute_divisions_statute_id_parent_division_id_index ON statute_divisions (statute_id, parent_division_id)');
            DB::statement('CREATE INDEX statute_divisions_division_type_status_index ON statute_divisions (division_type, status)');
            DB::statement('CREATE INDEX statute_divisions_sort_order_index ON statute_divisions (sort_order)');
        });
    }

    /**
     * Revert SQLite enum by recreating table with original enum values
     */
    private function revertSqliteEnum(): void
    {
        Schema::table('statute_divisions', function (Blueprint $table) {
            // Create temporary table with original enum
            DB::statement('CREATE TABLE statute_divisions_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                slug VARCHAR NOT NULL,
                statute_id INTEGER NOT NULL,
                parent_division_id INTEGER,
                division_type VARCHAR CHECK (division_type IN (\'part\', \'chapter\', \'article\', \'title\', \'book\', \'division\', \'section\', \'subsection\', \'schedule\')) NOT NULL,
                division_number VARCHAR NOT NULL,
                division_title VARCHAR NOT NULL,
                division_subtitle VARCHAR,
                content TEXT,
                sort_order INTEGER NOT NULL DEFAULT 0,
                level INTEGER NOT NULL DEFAULT 1,
                status VARCHAR CHECK (status IN (\'active\', \'repealed\', \'amended\')) NOT NULL DEFAULT \'active\',
                effective_date DATE,
                created_at DATETIME,
                updated_at DATETIME,
                range VARCHAR,
                FOREIGN KEY(statute_id) REFERENCES statutes(id) ON DELETE CASCADE,
                FOREIGN KEY(parent_division_id) REFERENCES statute_divisions(id) ON DELETE CASCADE
            )');
            
            // Copy data from original table (excluding any 'order' types)
            DB::statement('INSERT INTO statute_divisions_temp SELECT * FROM statute_divisions WHERE division_type != \'order\'');
            
            // Drop original table
            DB::statement('DROP TABLE statute_divisions');
            
            // Rename temporary table
            DB::statement('ALTER TABLE statute_divisions_temp RENAME TO statute_divisions');
            
            // Recreate indexes
            DB::statement('CREATE UNIQUE INDEX statute_divisions_statute_id_slug_unique ON statute_divisions (statute_id, slug)');
            DB::statement('CREATE INDEX statute_divisions_statute_id_parent_division_id_index ON statute_divisions (statute_id, parent_division_id)');
            DB::statement('CREATE INDEX statute_divisions_division_type_status_index ON statute_divisions (division_type, status)');
            DB::statement('CREATE INDEX statute_divisions_sort_order_index ON statute_divisions (sort_order)');
        });
    }
};
