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
        // Step 1: For SQLite, we can't modify enum, but we can add the 'schedule' value directly
        // SQLite doesn't enforce enum constraints, so we can insert 'schedule' values
        
        // Step 2: Migrate data from statute_schedules to statute_divisions
        if (Schema::hasTable('statute_schedules')) {
            $schedules = DB::table('statute_schedules')->get();
            
            foreach ($schedules as $schedule) {
                DB::table('statute_divisions')->insert([
                    'slug' => $schedule->slug,
                    'statute_id' => $schedule->statute_id,
                    'parent_division_id' => null,
                    'division_type' => 'schedule',
                    'division_number' => $schedule->schedule_number,
                    'division_title' => $schedule->schedule_title,
                    'division_subtitle' => null,
                    'content' => $schedule->content,
                    'sort_order' => $schedule->sort_order,
                    'level' => 1, // Schedules are typically top-level
                    'status' => $schedule->status,
                    'effective_date' => $schedule->effective_date,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ]);
            }
            
            // Step 3: Drop the statute_schedules table
            Schema::dropIfExists('statute_schedules');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate statute_schedules table
        Schema::create('statute_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
            $table->string('schedule_number');
            $table->string('schedule_title');
            $table->longText('content');
            $table->string('schedule_type')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
            $table->date('effective_date')->nullable();
            $table->timestamps();
            
            $table->unique(['statute_id', 'slug']);
            $table->index(['statute_id', 'schedule_number']);
            $table->index(['sort_order']);
        });
        
        // Migrate schedule divisions back to statute_schedules
        $scheduleDivisions = DB::table('statute_divisions')
            ->where('division_type', 'schedule')
            ->get();
            
        foreach ($scheduleDivisions as $division) {
            DB::table('statute_schedules')->insert([
                'slug' => $division->slug,
                'statute_id' => $division->statute_id,
                'schedule_number' => $division->division_number,
                'schedule_title' => $division->division_title,
                'content' => $division->content,
                'schedule_type' => null,
                'sort_order' => $division->sort_order,
                'status' => $division->status,
                'effective_date' => $division->effective_date,
                'created_at' => $division->created_at,
                'updated_at' => $division->updated_at,
            ]);
        }
        
        // Remove schedule divisions
        DB::table('statute_divisions')->where('division_type', 'schedule')->delete();
        
        // For SQLite, we can't remove enum values, but that's fine for rollback
        // The 'schedule' values will just remain as valid division_type values
    }
};