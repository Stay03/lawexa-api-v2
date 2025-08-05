# Statutes API Endpoint - Technical Implementation Specs

## Overview
This document outlines the technical implementation specifications for the Statutes API endpoint, following the established patterns from the case API architecture.

## 1. Database Schema Implementation

### 1.1 Core Tables Migrations

#### statutes Table
```php
// Migration: create_statutes_table.php
Schema::create('statutes', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('title');
    $table->string('short_title')->nullable();
    $table->year('year_enacted')->nullable();
    $table->date('commencement_date')->nullable();
    $table->enum('status', ['active', 'repealed', 'amended', 'suspended'])->default('active');
    $table->date('repealed_date')->nullable();
    $table->foreignId('repealing_statute_id')->nullable()->constrained('statutes')->onDelete('set null');
    $table->foreignId('parent_statute_id')->nullable()->constrained('statutes')->onDelete('set null');
    $table->string('jurisdiction');
    $table->string('country');
    $table->string('state')->nullable();
    $table->string('local_government')->nullable();
    $table->string('citation_format')->nullable();
    $table->string('sector')->nullable();
    $table->json('tags')->nullable();
    $table->longText('description');
    $table->string('range')->nullable(); // Clarify usage
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    $table->timestamps();
    
    // Strategic indexes
    $table->index(['status', 'jurisdiction']);
    $table->index(['country', 'state']);
    $table->index(['sector']);
    $table->index(['year_enacted']);
    $table->index(['parent_statute_id']);
    $table->index(['created_by']);
});
```

#### statute_divisions Table
```php
// Migration: create_statute_divisions_table.php
Schema::create('statute_divisions', function (Blueprint $table) {
    $table->id();
    $table->string('slug');
    $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
    $table->foreignId('parent_division_id')->nullable()->constrained('statute_divisions')->onDelete('cascade');
    $table->enum('division_type', ['part', 'chapter', 'article', 'title', 'book', 'division', 'section', 'subsection']);
    $table->string('division_number');
    $table->string('division_title');
    $table->string('division_subtitle')->nullable();
    $table->longText('content')->nullable();
    $table->integer('sort_order')->default(0);
    $table->integer('level')->default(1);
    $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
    $table->date('effective_date')->nullable();
    $table->timestamps();
    
    // Composite unique constraint for slug within statute
    $table->unique(['statute_id', 'slug']);
    $table->index(['statute_id', 'parent_division_id']);
    $table->index(['division_type', 'status']);
    $table->index(['sort_order']);
});
```

#### statute_provisions Table
```php
// Migration: create_statute_provisions_table.php
Schema::create('statute_provisions', function (Blueprint $table) {
    $table->id();
    $table->string('slug');
    $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
    $table->foreignId('division_id')->nullable()->constrained('statute_divisions')->onDelete('cascade');
    $table->foreignId('parent_provision_id')->nullable()->constrained('statute_provisions')->onDelete('cascade');
    $table->enum('provision_type', ['section', 'subsection', 'paragraph', 'subparagraph', 'clause', 'subclause', 'item']);
    $table->string('provision_number');
    $table->string('provision_title')->nullable();
    $table->longText('provision_text');
    $table->text('marginal_note')->nullable();
    $table->text('interpretation_note')->nullable();
    $table->integer('sort_order')->default(0);
    $table->integer('level')->default(1);
    $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
    $table->date('effective_date')->nullable();
    $table->timestamps();
    
    // Composite unique constraint for slug within statute
    $table->unique(['statute_id', 'slug']);
    $table->index(['statute_id', 'division_id']);
    $table->index(['provision_type', 'status']);
    $table->index(['sort_order']);
});
```

#### statute_schedules Table
```php
// Migration: create_statute_schedules_table.php
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
    
    // Composite unique constraint for slug within statute
    $table->unique(['statute_id', 'slug']);
    $table->index(['statute_id', 'schedule_number']);
    $table->index(['sort_order']);
});
```

### 1.2 Relationship Tables

#### statute_amendments Table
```php
// Migration: create_statute_amendments_table.php
Schema::create('statute_amendments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('original_statute_id')->constrained('statutes')->onDelete('cascade');
    $table->foreignId('amending_statute_id')->constrained('statutes')->onDelete('cascade');
    $table->date('effective_date');
    $table->text('amendment_description')->nullable();
    $table->timestamps();
    
    $table->unique(['original_statute_id', 'amending_statute_id']);
    $table->index(['effective_date']);
});
```

#### statute_citations Table
```php
// Migration: create_statute_citations_table.php
Schema::create('statute_citations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('citing_statute_id')->constrained('statutes')->onDelete('cascade');
    $table->foreignId('cited_statute_id')->constrained('statutes')->onDelete('cascade');
    $table->string('citation_context')->nullable();
    $table->timestamps();
    
    $table->unique(['citing_statute_id', 'cited_statute_id']);
});
```

## 2. Model Implementation

### 2.1 Statute Model
```php
// app/Models/Statute.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Statute extends Model
{
    protected $fillable = [
        'slug', 'title', 'short_title', 'year_enacted', 'commencement_date',
        'status', 'repealed_date', 'repealing_statute_id', 'parent_statute_id',
        'jurisdiction', 'country', 'state', 'local_government',
        'citation_format', 'sector', 'tags', 'description', 'range', 'created_by'
    ];

    protected $casts = [
        'tags' => 'array',
        'commencement_date' => 'date',
        'repealed_date' => 'date',
        'year_enacted' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($statute) {
            if (empty($statute->slug)) {
                $statute->slug = Str::slug($statute->title);
            }
        });
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(StatuteDivision::class)->orderBy('sort_order');
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(StatuteProvision::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StatuteSchedule::class)->orderBy('sort_order');
    }

    public function parentStatute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'parent_statute_id');
    }

    public function childStatutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'parent_statute_id');
    }

    public function repealingStatute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'repealing_statute_id');
    }

    public function repealedStatutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'repealing_statute_id');
    }

    public function amendments(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_amendments', 'original_statute_id', 'amending_statute_id')
                    ->withPivot(['effective_date', 'amendment_description'])
                    ->withTimestamps();
    }

    public function amendedBy(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_amendments', 'amending_statute_id', 'original_statute_id')
                    ->withPivot(['effective_date', 'amendment_description'])
                    ->withTimestamps();
    }

    public function citedStatutes(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_citations', 'citing_statute_id', 'cited_statute_id')
                    ->withPivot(['citation_context'])
                    ->withTimestamps();
    }

    public function citingStatutes(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'statute_citations', 'cited_statute_id', 'citing_statute_id')
                    ->withPivot(['citation_context'])
                    ->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // Query Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                    ->orWhere('short_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('citation_format', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByJurisdiction($query, $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year_enacted', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

### 2.2 Related Models
```php
// app/Models/StatuteDivision.php
class StatuteDivision extends Model
{
    protected $fillable = [
        'slug', 'statute_id', 'parent_division_id', 'division_type',
        'division_number', 'division_title', 'division_subtitle',
        'content', 'sort_order', 'level', 'status', 'effective_date'
    ];

    protected $casts = [
        'effective_date' => 'date'
    ];

    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class);
    }

    public function parentDivision(): BelongsTo
    {
        return $this->belongsTo(StatuteDivision::class, 'parent_division_id');
    }

    public function childDivisions(): HasMany
    {
        return $this->hasMany(StatuteDivision::class, 'parent_division_id')->orderBy('sort_order');
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(StatuteProvision::class, 'division_id')->orderBy('sort_order');
    }
}

// Similar implementations for StatuteProvision and StatuteSchedule
```

## 3. Route Definition

### 3.1 Route Model Binding
```php
// routes/api.php

Route::bind('statute', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/statutes')) {
        return \App\Models\Statute::findOrFail($value);
    }
    return \App\Models\Statute::where('slug', $value)->firstOrFail();
});

Route::bind('statuteDivision', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/')) {
        return \App\Models\StatuteDivision::findOrFail($value);
    }
    return \App\Models\StatuteDivision::where('slug', $value)->firstOrFail();
});

Route::bind('statuteProvision', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/')) {
        return \App\Models\StatuteProvision::findOrFail($value);
    }
    return \App\Models\StatuteProvision::where('slug', $value)->firstOrFail();
});
```

### 3.2 User Routes (Slug-based)
```php
Route::middleware(['auth:sanctum'])->group(function () {
    // Statutes
    Route::get('/statutes', [StatuteController::class, 'index']);
    Route::get('/statutes/{statute}', [StatuteController::class, 'show']);
    
    // Statute Divisions
    Route::get('/statutes/{statute}/divisions', [StatuteController::class, 'divisions']);
    Route::get('/statutes/{statute}/divisions/{statuteDivision}', [StatuteController::class, 'showDivision']);
    
    // Statute Provisions
    Route::get('/statutes/{statute}/provisions', [StatuteController::class, 'provisions']);
    Route::get('/statutes/{statute}/provisions/{statuteProvision}', [StatuteController::class, 'showProvision']);
    
    // Statute Schedules
    Route::get('/statutes/{statute}/schedules', [StatuteController::class, 'schedules']);
    Route::get('/statutes/{statute}/schedules/{statuteSchedule}', [StatuteController::class, 'showSchedule']);
    
    // Search and filters
    Route::get('/statutes/search', [StatuteController::class, 'search']);
    Route::get('/statutes/filter', [StatuteController::class, 'filter']);
});
```

### 3.3 Admin Routes (ID-based)
```php
Route::middleware(['auth:sanctum', 'role:admin,researcher,superadmin'])->prefix('admin')->group(function () {
    // Statutes CRUD
    Route::apiResource('statutes', AdminStatuteController::class);
    Route::post('/statutes/{statute}/restore', [AdminStatuteController::class, 'restore']);
    
    // Divisions CRUD
    Route::apiResource('statutes.divisions', AdminStatuteDivisionController::class);
    
    // Provisions CRUD
    Route::apiResource('statutes.provisions', AdminStatuteProvisionController::class);
    
    // Schedules CRUD
    Route::apiResource('statutes.schedules', AdminStatuteScheduleController::class);
    
    // Relationships
    Route::post('/statutes/{statute}/amendments', [AdminStatuteController::class, 'addAmendment']);
    Route::delete('/statutes/{statute}/amendments/{amendment}', [AdminStatuteController::class, 'removeAmendment']);
    
    Route::post('/statutes/{statute}/citations', [AdminStatuteController::class, 'addCitation']);
    Route::delete('/statutes/{statute}/citations/{citation}', [AdminStatuteController::class, 'removeCitation']);
    
    // Bulk operations
    Route::post('/statutes/bulk-update', [AdminStatuteController::class, 'bulkUpdate']);
    Route::post('/statutes/bulk-delete', [AdminStatuteController::class, 'bulkDelete']);
    
    // Import/Export
    Route::post('/statutes/import', [AdminStatuteController::class, 'import']);
    Route::get('/statutes/export', [AdminStatuteController::class, 'export']);
});
```

## 4. Controller Implementation

### 4.1 StatuteController (User-facing)
```php
// app/Http/Controllers/StatuteController.php
<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Http\Resources\StatuteResource;
use App\Http\Resources\StatuteCollection;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class StatuteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Statute::with(['creator:id,name'])
                        ->active();
        
        // Apply filters
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        if ($request->has('jurisdiction')) {
            $query->byJurisdiction($request->jurisdiction);
        }
        
        if ($request->has('country')) {
            $query->byCountry($request->country);
        }
        
        if ($request->has('state')) {
            $query->byState($request->state);
        }
        
        if ($request->has('sector')) {
            $query->bySector($request->sector);
        }
        
        if ($request->has('year')) {
            $query->byYear($request->year);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'year_enacted');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $statutes = $query->paginate($perPage);
        
        return ApiResponse::success(
            new StatuteCollection($statutes),
            'Statutes retrieved successfully'
        );
    }
    
    public function show(Request $request, Statute $statute)
    {
        $includeRelated = $request->boolean('include_related', false);
        $includeAmendments = $request->boolean('include_amendments', false);
        $includeCitations = $request->boolean('include_citations', false);
        
        $with = ['creator:id,name', 'files'];
        
        if ($includeRelated) {
            $with = array_merge($with, [
                'parentStatute:id,title,slug',
                'childStatutes:id,title,slug',
                'repealingStatute:id,title,slug'
            ]);
        }
        
        if ($includeAmendments) {
            $with[] = 'amendments:id,title,slug';
            $with[] = 'amendedBy:id,title,slug';
        }
        
        if ($includeCitations) {
            $with[] = 'citedStatutes:id,title,slug';
            $with[] = 'citingStatutes:id,title,slug';
        }
        
        $statute->load($with);
        
        return ApiResponse::success(
            new StatuteResource($statute),
            'Statute retrieved successfully'
        );
    }
    
    public function divisions(Request $request, Statute $statute)
    {
        $divisions = $statute->divisions()
                            ->with(['parentDivision:id,division_title', 'childDivisions:id,division_title,parent_division_id'])
                            ->paginate($request->get('per_page', 50));
        
        return ApiResponse::success(
            $divisions,
            'Statute divisions retrieved successfully'
        );
    }
    
    public function provisions(Request $request, Statute $statute)
    {
        $provisions = $statute->provisions()
                             ->with(['division:id,division_title', 'parentProvision:id,provision_title'])
                             ->paginate($request->get('per_page', 100));
        
        return ApiResponse::success(
            $provisions,
            'Statute provisions retrieved successfully'
        );
    }
    
    public function schedules(Request $request, Statute $statute)
    {
        $schedules = $statute->schedules()
                            ->paginate($request->get('per_page', 20));
        
        return ApiResponse::success(
            $schedules,
            'Statute schedules retrieved successfully'
        );
    }
}
```

### 4.2 AdminStatuteController (Admin-facing)
```php
// app/Http/Controllers/AdminStatuteController.php
<?php

namespace App\Http\Controllers;

use App\Models\Statute;
use App\Http\Requests\CreateStatuteRequest;
use App\Http\Requests\UpdateStatuteRequest;
use App\Http\Resources\StatuteResource;
use App\Http\Resources\StatuteCollection;
use App\Http\Responses\ApiResponse;
use App\Traits\HandlesDirectS3Uploads;
use Illuminate\Http\Request;

class AdminStatuteController extends Controller
{
    use HandlesDirectS3Uploads;
    
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 15), 100);
        
        $query = Statute::with(['creator:id,name']);
        
        // Admin can see all statuses
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        // Apply other filters
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // ... other filters similar to user controller
        
        $statutes = $query->paginate($perPage);
        
        return ApiResponse::success(
            new StatuteCollection($statutes),
            'Statutes retrieved successfully'
        );
    }
    
    public function store(CreateStatuteRequest $request)
    {
        $statute = Statute::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));
        
        // Handle file uploads
        if ($request->hasFile('files')) {
            $this->handleDirectS3FileUploads(
                $request, 
                $statute, 
                'files', 
                'statute_documents', 
                $request->user()->id
            );
        }
        
        $statute->load(['creator:id,name', 'files']);
        
        return ApiResponse::success(
            new StatuteResource($statute),
            'Statute created successfully',
            201
        );
    }
    
    public function show(Statute $statute)
    {
        $statute->load([
            'creator:id,name',
            'files',
            'divisions',
            'provisions',
            'schedules',
            'parentStatute:id,title',
            'childStatutes:id,title',
            'amendments:id,title',
            'amendedBy:id,title',
            'citedStatutes:id,title',
            'citingStatutes:id,title'
        ]);
        
        return ApiResponse::success(
            new StatuteResource($statute),
            'Statute retrieved successfully'
        );
    }
    
    public function update(UpdateStatuteRequest $request, Statute $statute)
    {
        $statute->update($request->validated());
        
        // Handle file uploads
        if ($request->hasFile('files')) {
            $this->handleDirectS3FileUploads(
                $request, 
                $statute, 
                'files', 
                'statute_documents', 
                $request->user()->id
            );
        }
        
        $statute->load(['creator:id,name', 'files']);
        
        return ApiResponse::success(
            new StatuteResource($statute),
            'Statute updated successfully'
        );
    }
    
    public function destroy(Statute $statute)
    {
        $statute->delete();
        
        return ApiResponse::success(
            null,
            'Statute deleted successfully'
        );
    }
    
    public function addAmendment(Request $request, Statute $statute)
    {
        $request->validate([
            'amending_statute_id' => 'required|exists:statutes,id',
            'effective_date' => 'required|date',
            'amendment_description' => 'nullable|string'
        ]);
        
        $statute->amendments()->attach($request->amending_statute_id, [
            'effective_date' => $request->effective_date,
            'amendment_description' => $request->amendment_description
        ]);
        
        return ApiResponse::success(
            null,
            'Amendment added successfully'
        );
    }
    
    public function addCitation(Request $request, Statute $statute)
    {
        $request->validate([
            'cited_statute_id' => 'required|exists:statutes,id',
            'citation_context' => 'nullable|string'
        ]);
        
        $statute->citedStatutes()->attach($request->cited_statute_id, [
            'citation_context' => $request->citation_context
        ]);
        
        return ApiResponse::success(
            null,
            'Citation added successfully'
        );
    }
}
```

## 5. Resource Implementation

### 5.1 StatuteResource
```php
// app/Http/Resources/StatuteResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatuteResource extends JsonResource
{
    public static $useSimplifiedFiles = false;
    
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_title' => $this->short_title,
            'year_enacted' => $this->year_enacted,
            'commencement_date' => $this->commencement_date?->format('Y-m-d'),
            'status' => $this->status,
            'repealed_date' => $this->repealed_date?->format('Y-m-d'),
            'jurisdiction' => $this->jurisdiction,
            'country' => $this->country,
            'state' => $this->state,
            'local_government' => $this->local_government,
            'citation_format' => $this->citation_format,
            'sector' => $this->sector,
            'tags' => $this->tags,
            'description' => $this->description,
            'range' => $this->range,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name
                ];
            }),
            
            'parent_statute' => $this->whenLoaded('parentStatute', function () {
                return [
                    'id' => $this->parentStatute->id,
                    'title' => $this->parentStatute->title,
                    'slug' => $this->parentStatute->slug
                ];
            }),
            
            'child_statutes' => $this->whenLoaded('childStatutes', function () {
                return $this->childStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug
                    ];
                });
            }),
            
            'repealing_statute' => $this->whenLoaded('repealingStatute', function () {
                return [
                    'id' => $this->repealingStatute->id,
                    'title' => $this->repealingStatute->title,
                    'slug' => $this->repealingStatute->slug
                ];
            }),
            
            'amendments' => $this->whenLoaded('amendments', function () {
                return $this->amendments->map(function ($amendment) {
                    return [
                        'id' => $amendment->id,
                        'title' => $amendment->title,
                        'slug' => $amendment->slug,
                        'effective_date' => $amendment->pivot->effective_date,
                        'description' => $amendment->pivot->amendment_description
                    ];
                });
            }),
            
            'amended_by' => $this->whenLoaded('amendedBy', function () {
                return $this->amendedBy->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'effective_date' => $statute->pivot->effective_date,
                        'description' => $statute->pivot->amendment_description
                    ];
                });
            }),
            
            'cited_statutes' => $this->whenLoaded('citedStatutes', function () {
                return $this->citedStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'context' => $statute->pivot->citation_context
                    ];
                });
            }),
            
            'citing_statutes' => $this->whenLoaded('citingStatutes', function () {
                return $this->citingStatutes->map(function ($statute) {
                    return [
                        'id' => $statute->id,
                        'title' => $statute->title,
                        'slug' => $statute->slug,
                        'context' => $statute->pivot->citation_context
                    ];
                });
            }),
            
            'divisions' => $this->whenLoaded('divisions', function () {
                return StatuteDivisionResource::collection($this->divisions);
            }),
            
            'provisions' => $this->whenLoaded('provisions', function () {
                return StatuteProvisionResource::collection($this->provisions);
            }),
            
            'schedules' => $this->whenLoaded('schedules', function () {
                return StatuteScheduleResource::collection($this->schedules);
            }),
            
            'files' => $this->whenLoaded('files', function () {
                if (static::$useSimplifiedFiles) {
                    return $this->files->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'name' => $file->original_name,
                            'size' => $file->size,
                            'type' => $file->file_type
                        ];
                    });
                }
                return FileResource::collection($this->files);
            })
        ];
    }
}
```

### 5.2 StatuteCollection
```php
// app/Http/Resources/StatuteCollection.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StatuteCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        // Use simplified files for collection to improve performance
        StatuteResource::$useSimplifiedFiles = true;
        
        $statutes = $this->collection->map(function ($statute) {
            return new StatuteResource($statute);
        });
        
        // Reset for individual resources
        StatuteResource::$useSimplifiedFiles = false;
        
        return [
            'statutes' => $statutes,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }
}
```

## 6. Request Validation

### 6.1 CreateStatuteRequest
```php
// app/Http/Requests/CreateStatuteRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class CreateStatuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAdminAccess();
    }
    
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_title' => 'nullable|string|max:255',
            'year_enacted' => 'nullable|integer|min:1800|max:' . (date('Y') + 10),
            'commencement_date' => 'nullable|date',
            'status' => ['required', Rule::in(['active', 'repealed', 'amended', 'suspended'])],
            'repealed_date' => 'nullable|date|required_if:status,repealed',
            'repealing_statute_id' => 'nullable|exists:statutes,id|required_if:status,repealed',
            'parent_statute_id' => 'nullable|exists:statutes,id',
            'jurisdiction' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'local_government' => 'nullable|string|max:100',
            'citation_format' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:100',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
            'description' => 'required|string',
            'range' => 'nullable|string|max:255',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,doc,docx,txt|max:10240' // 10MB max
        ];
    }
    
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Custom validation logic
            if ($this->parent_statute_id && $this->parent_statute_id == $this->statute?->id) {
                $validator->errors()->add('parent_statute_id', 'A statute cannot be its own parent.');
            }
            
            if ($this->repealing_statute_id && $this->repealing_statute_id == $this->statute?->id) {
                $validator->errors()->add('repealing_statute_id', 'A statute cannot repeal itself.');
            }
            
            // Validate commencement_date is not before year_enacted
            if ($this->year_enacted && $this->commencement_date) {
                $commencementYear = date('Y', strtotime($this->commencement_date));
                if ($commencementYear < $this->year_enacted) {
                    $validator->errors()->add('commencement_date', 'Commencement date cannot be before the year enacted.');
                }
            }
        });
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'The statute title is required.',
            'status.required' => 'The statute status is required.',
            'jurisdiction.required' => 'The jurisdiction is required.',
            'country.required' => 'The country is required.',
            'description.required' => 'The statute description is required.',
            'repealed_date.required_if' => 'The repealed date is required when status is repealed.',
            'repealing_statute_id.required_if' => 'The repealing statute is required when status is repealed.',
        ];
    }
}
```

### 6.2 UpdateStatuteRequest
```php
// app/Http/Requests/UpdateStatuteRequest.php
<?php

namespace App\Http\Requests;

class UpdateStatuteRequest extends CreateStatuteRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        // Make some fields optional for updates
        $rules['title'] = 'sometimes|required|string|max:255';
        $rules['status'] = ['sometimes', 'required', Rule::in(['active', 'repealed', 'amended', 'suspended'])];
        $rules['jurisdiction'] = 'sometimes|required|string|max:100';
        $rules['country'] = 'sometimes|required|string|max:100';
        $rules['description'] = 'sometimes|required|string';
        
        return $rules;
    }
}
```

## 7. API Response Examples

### 7.1 Statute List Response
```json
{
    "status": "success",
    "message": "Statutes retrieved successfully",
    "data": {
        "statutes": [
            {
                "id": 1,
                "slug": "companies-and-allied-matters-act-2020",
                "title": "Companies and Allied Matters Act",
                "short_title": "CAMA 2020",
                "year_enacted": 2020,
                "commencement_date": "2020-08-07",
                "status": "active",
                "jurisdiction": "Federal",
                "country": "Nigeria",
                "state": null,
                "local_government": null,
                "citation_format": "CAMA 2020",
                "sector": "Corporate Law",
                "tags": ["companies", "corporate governance", "business"],
                "description": "An Act to repeal the Companies and Allied Matters Act...",
                "created_at": "2024-01-15 10:30:00",
                "updated_at": "2024-01-15 10:30:00",
                "creator": {
                    "id": 1,
                    "name": "John Doe"
                }
            }
        ],
        "meta": {
            "current_page": 1,
            "from": 1,
            "last_page": 10,
            "per_page": 15,
            "to": 15,
            "total": 145
        },
        "links": {
            "first": "http://api.example.com/statutes?page=1",
            "last": "http://api.example.com/statutes?page=10",
            "prev": null,
            "next": "http://api.example.com/statutes?page=2"
        }
    }
}
```

### 7.2 Single Statute Response
```json
{
    "status": "success",
    "message": "Statute retrieved successfully",
    "data": {
        "id": 1,
        "slug": "companies-and-allied-matters-act-2020",
        "title": "Companies and Allied Matters Act",
        "short_title": "CAMA 2020",
        "year_enacted": 2020,
        "commencement_date": "2020-08-07",
        "status": "active",
        "repealed_date": null,
        "jurisdiction": "Federal",
        "country": "Nigeria",
        "state": null,
        "local_government": null,
        "citation_format": "CAMA 2020",
        "sector": "Corporate Law",
        "tags": ["companies", "corporate governance", "business"],
        "description": "An Act to repeal the Companies and Allied Matters Act...",
        "range": null,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "creator": {
            "id": 1,
            "name": "John Doe"
        },
        "parent_statute": null,
        "child_statutes": [],
        "repealing_statute": null,
        "amendments": [
            {
                "id": 5,
                "title": "Companies and Allied Matters (Amendment) Act 2023",
                "slug": "companies-and-allied-matters-amendment-act-2023",
                "effective_date": "2023-12-01",
                "description": "Amendment to section 18 regarding company registration"
            }
        ],
        "amended_by": [],
        "cited_statutes": [],
        "citing_statutes": [],
        "files": [
            {
                "id": 10,
                "name": "CAMA_2020_Full_Text.pdf",
                "size": 2048576,
                "type": "application/pdf"
            }
        ]
    }
}
```

## 8. Performance Considerations

### 8.1 Database Indexing Strategy
```sql
-- Core performance indexes
CREATE INDEX idx_statutes_status_jurisdiction ON statutes(status, jurisdiction);
CREATE INDEX idx_statutes_country_state ON statutes(country, state);
CREATE INDEX idx_statutes_sector ON statutes(sector);
CREATE INDEX idx_statutes_year_enacted ON statutes(year_enacted);
CREATE INDEX idx_statutes_slug ON statutes(slug);

-- Full-text search indexes
CREATE FULLTEXT INDEX idx_statutes_search ON statutes(title, short_title, description);

-- Relationship indexes
CREATE INDEX idx_statute_divisions_statute_parent ON statute_divisions(statute_id, parent_division_id);
CREATE INDEX idx_statute_provisions_statute_division ON statute_provisions(statute_id, division_id);
```

### 8.2 Query Optimization
- Use eager loading for relationships
- Implement query scopes for common filters
- Use pagination for large datasets
- Implement caching for frequently accessed data
- Use database views for complex queries

### 8.3 Caching Strategy
```php
// app/Services/StatuteCacheService.php
class StatuteCacheService
{
    public function getPopularStatutes($limit = 10)
    {
        return Cache::remember('popular_statutes', 3600, function () use ($limit) {
            return Statute::with(['creator:id,name'])
                          ->active()
                          ->orderBy('views_count', 'desc')
                          ->limit($limit)
                          ->get();
        });
    }
    
    public function getStatutesByJurisdiction($jurisdiction)
    {
        return Cache::remember("statutes_jurisdiction_{$jurisdiction}", 1800, function () use ($jurisdiction) {
            return Statute::byJurisdiction($jurisdiction)
                          ->active()
                          ->select(['id', 'title', 'slug', 'year_enacted'])
                          ->get();
        });
    }
}
```

## 9. Testing Strategy

### 9.1 Feature Tests
```php
// tests/Feature/StatuteApiTest.php
class StatuteApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_list_statutes()
    {
        $user = User::factory()->create();
        $statutes = Statute::factory()->count(5)->create();
        
        $response = $this->actingAs($user)
                        ->getJson('/api/statutes');
        
        $response->assertOk()
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'statutes' => [
                            '*' => ['id', 'slug', 'title', 'status']
                        ],
                        'meta',
                        'links'
                    ]
                ]);
    }
    
    public function test_admin_can_create_statute()
    {
        $admin = User::factory()->admin()->create();
        
        $statuteData = [
            'title' => 'Test Statute',
            'status' => 'active',
            'jurisdiction' => 'Federal',
            'country' => 'Nigeria',
            'description' => 'Test description'
        ];
        
        $response = $this->actingAs($admin)
                        ->postJson('/api/admin/statutes', $statuteData);
        
        $response->assertCreated()
                ->assertJsonFragment(['title' => 'Test Statute']);
        
        $this->assertDatabaseHas('statutes', ['title' => 'Test Statute']);
    }
}
```

### 9.2 Unit Tests
```php
// tests/Unit/StatuteModelTest.php
class StatuteModelTest extends TestCase
{
    public function test_statute_generates_slug_automatically()
    {
        $statute = new Statute(['title' => 'Test Statute Title']);
        $statute->save();
        
        $this->assertEquals('test-statute-title', $statute->slug);
    }
    
    public function test_statute_search_scope()
    {
        $statute1 = Statute::factory()->create(['title' => 'Companies Act']);
        $statute2 = Statute::factory()->create(['title' => 'Labor Law']);
        
        $results = Statute::search('Companies')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($statute1->id, $results->first()->id);
    }
}
```

## 10. Security Considerations

### 10.1 Authorization
- Role-based access control for admin operations
- Proper validation of user permissions
- Protection against unauthorized modifications

### 10.2 Input Validation
- Comprehensive validation rules
- File upload security
- Protection against SQL injection
- XSS prevention in text fields

### 10.3 Rate Limiting
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // User routes with standard rate limiting
});

Route::middleware(['auth:sanctum', 'role:admin', 'throttle:120,1'])->group(function () {
    // Admin routes with higher rate limits
});
```

## 11. Implementation Timeline

### Phase 1: Core Foundation (Week 1-2)
- Database migrations and models
- Basic CRUD operations
- User-facing read-only endpoints
- Basic validation

### Phase 2: Advanced Features (Week 3-4)
- Admin functionality
- Relationship management
- File upload handling
- Advanced filtering and search

### Phase 3: Optimization & Testing (Week 5-6)
- Performance optimization
- Comprehensive testing
- Caching implementation
- Documentation completion

### Phase 4: Integration & Deployment (Week 7-8)
- Integration testing
- Security audit
- Performance testing
- Production deployment

This implementation specification provides a comprehensive foundation for building a robust, scalable, and maintainable Statutes API endpoint that follows Laravel best practices and established patterns from the existing codebase.