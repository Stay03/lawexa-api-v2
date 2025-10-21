# Content Request System - Implementation Documentation

## Table of Contents
1. [Overview](#overview)
2. [Requirements & Business Rules](#requirements--business-rules)
3. [Database Design](#database-design)
4. [Model Implementation](#model-implementation)
5. [Validation Layer](#validation-layer)
6. [API Resources](#api-resources)
7. [Controllers](#controllers)
8. [Email Notifications](#email-notifications)
9. [Service Layer](#service-layer)
10. [Routing](#routing)
11. [Integration Points](#integration-points)
12. [Testing Guidelines](#testing-guidelines)
13. [API Documentation](#api-documentation)
14. [Future Extensions](#future-extensions)

---

## Overview

### Purpose
The Content Request System allows users to request missing legal content (cases, statutes, provisions, divisions) that they want added to the Lawexa database. Admins/researchers can then review these requests and fulfill them by creating the actual content.

### User Stories

**As a User:**
- I want to request a case that's missing from the database
- I want to track the status of my requests
- I want to receive email notifications when my request is fulfilled or rejected
- I want to delete my pending requests if I made a mistake

**As an Admin/Researcher:**
- I want to see all content requests from users
- I want to see statistics about pending vs fulfilled requests
- I want to manually link created content to requests
- I want to reject requests with a reason
- I want to receive notifications when new requests are submitted

### Key Features
- ✅ Multi-type support (cases, statutes, provisions, divisions)
- ✅ Manual fulfillment workflow (no auto-linking)
- ✅ Immutable requests (users can't edit after submission)
- ✅ Email notifications at each stage
- ✅ Polymorphic relationship to created content
- ✅ Support for nested provisions/divisions
- ✅ Admin rejection with optional reason

---

## Requirements & Business Rules

### Functional Requirements

1. **Request Creation**
   - Users must be authenticated and verified
   - Users can only request one type per request (case, statute, provision, division)
   - Title is required (max 500 characters)
   - Additional notes are optional (max 2000 characters)
   - Requests for provisions/divisions require statute_id
   - Nested provisions/divisions require parent_division_id or parent_provision_id

2. **Request Management (User)**
   - Users can only view their own requests
   - Users cannot edit requests after submission (immutable)
   - Users can delete only pending requests
   - Users receive email confirmations for all status changes

3. **Request Management (Admin)**
   - Admins can view all requests
   - Admins can change status: pending → in_progress → fulfilled/rejected
   - Admins must manually link created content to requests
   - Admins can provide rejection reason
   - Admins receive email when new requests are created

4. **Duplicate Handling**
   - No automatic duplicate detection
   - Admins manually choose which requests to fulfill
   - Multiple users can request the same content
   - Each request is independent

5. **Fulfillment Rules**
   - Admin creates content separately (case, statute, etc.)
   - Admin manually links created content to request
   - Request title and created content title don't have to match exactly
   - Only admins, researchers, and superadmins can fulfill requests
   - Fulfillment triggers email to requester with link to created content

6. **Rejection Rules**
   - Optional rejection reason (sent to user via email)
   - Rejected requests remain in system for record-keeping
   - Users can see rejection reason for their own requests

### Non-Functional Requirements

1. **Performance**
   - Requests should be paginated (default: 15 per page)
   - Database indexes on frequently queried fields
   - Email notifications should be queued

2. **Security**
   - Users can only access their own requests
   - Role-based access control for admin endpoints
   - Input validation and sanitization
   - XSS protection on user-generated content

3. **Scalability**
   - Polymorphic design supports future content types
   - Single table for all request types
   - Extensible status enum

---

## Database Design

### Migration: `create_content_requests_table.php`

**Location:** `database/migrations/YYYY_MM_DD_HHMMSS_create_content_requests_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_requests', function (Blueprint $table) {
            $table->id();

            // User who made the request
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Request type: case, statute, provision, division
            $table->enum('type', ['case', 'statute', 'provision', 'division'])
                ->default('case');

            // What the user is requesting
            $table->string('title', 500);
            $table->text('additional_notes')->nullable();

            // Polymorphic relationship to created content
            // e.g., created_content_type = 'App\Models\CourtCase'
            //       created_content_id = 5109
            $table->string('created_content_type')->nullable();
            $table->unsignedBigInteger('created_content_id')->nullable();

            // For provisions/divisions: which statute they belong to
            $table->foreignId('statute_id')
                ->nullable()
                ->constrained('statutes')
                ->onDelete('cascade');

            // For nested divisions (Division under Division)
            $table->foreignId('parent_division_id')
                ->nullable()
                ->constrained('statute_divisions')
                ->onDelete('cascade');

            // For nested provisions (Provision under Provision)
            $table->foreignId('parent_provision_id')
                ->nullable()
                ->constrained('statute_provisions')
                ->onDelete('cascade');

            // Request status
            $table->enum('status', ['pending', 'in_progress', 'fulfilled', 'rejected'])
                ->default('pending');

            // Fulfillment tracking
            $table->foreignId('fulfilled_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('fulfilled_at')->nullable();

            // Rejection tracking
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index(['created_content_type', 'created_content_id']);
            $table->index('statute_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_requests');
    }
};
```

### Field Descriptions

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | bigint | No | Primary key |
| `user_id` | bigint | No | FK to users table - who made the request |
| `type` | enum | No | Type of content: case, statute, provision, division |
| `title` | varchar(500) | No | Title of requested content |
| `additional_notes` | text | Yes | Optional context/notes from user |
| `created_content_type` | varchar | Yes | Polymorphic type (e.g., App\Models\CourtCase) |
| `created_content_id` | bigint | Yes | Polymorphic ID of created content |
| `statute_id` | bigint | Yes | FK to statutes - for provisions/divisions |
| `parent_division_id` | bigint | Yes | FK to statute_divisions - for nested divisions |
| `parent_provision_id` | bigint | Yes | FK to statute_provisions - for nested provisions |
| `status` | enum | No | pending, in_progress, fulfilled, rejected |
| `fulfilled_by` | bigint | Yes | FK to users - admin who fulfilled |
| `fulfilled_at` | timestamp | Yes | When request was fulfilled |
| `rejected_by` | bigint | Yes | FK to users - admin who rejected |
| `rejected_at` | timestamp | Yes | When request was rejected |
| `rejection_reason` | text | Yes | Why request was rejected |
| `created_at` | timestamp | No | When request was created |
| `updated_at` | timestamp | No | Last update time |

### Field Usage Matrix

| Request Type | title | statute_id | parent_division_id | parent_provision_id | created_content_type |
|-------------|-------|------------|-------------------|-------------------|---------------------|
| **Case** | "Smith v Jones, [2020] UKSC 15" | NULL | NULL | NULL | App\Models\CourtCase |
| **Statute** | "Criminal Code Act 2024" | NULL | NULL | NULL | App\Models\Statute |
| **Provision (top-level)** | "Section 5: Murder" | 123 | NULL | NULL | App\Models\StatuteProvision |
| **Provision (under division)** | "Section 5.1" | 123 | 456 | NULL | App\Models\StatuteProvision |
| **Provision (under provision)** | "Sub-section 5.1(a)" | 123 | NULL | 789 | App\Models\StatuteProvision |
| **Division (top-level)** | "Part II: Offences" | 123 | NULL | NULL | App\Models\StatuteDivision |
| **Division (nested)** | "Chapter 3" | 123 | 456 | NULL | App\Models\StatuteDivision |

### Database Indexes Rationale

1. **`user_id`**: Users frequently query their own requests
2. **`status`**: Admins filter by status (pending, fulfilled, etc.)
3. **`type`**: Filtering by content type
4. **`created_content_type + created_content_id`**: Composite index for polymorphic lookups
5. **`statute_id`**: Lookups for provision/division requests
6. **`created_at`**: Sorting by newest/oldest requests

---

## Model Implementation

### ContentRequest Model

**Location:** `app/Models/ContentRequest.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\Commentable;

class ContentRequest extends Model
{
    use HasFactory, Commentable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'additional_notes',
        'created_content_type',
        'created_content_id',
        'statute_id',
        'parent_division_id',
        'parent_provision_id',
        'status',
        'fulfilled_by',
        'fulfilled_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fulfilled_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the created content (polymorphic).
     * This could be a CourtCase, Statute, StatuteProvision, or StatuteDivision.
     */
    public function createdContent(): MorphTo
    {
        return $this->morphTo('created_content');
    }

    /**
     * Get the statute (for provision/division requests).
     */
    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class, 'statute_id');
    }

    /**
     * Get the parent division (for nested divisions/provisions).
     */
    public function parentDivision(): BelongsTo
    {
        return $this->belongsTo(StatuteDivision::class, 'parent_division_id');
    }

    /**
     * Get the parent provision (for nested provisions).
     */
    public function parentProvision(): BelongsTo
    {
        return $this->belongsTo(StatuteProvision::class, 'parent_provision_id');
    }

    /**
     * Get the admin who fulfilled this request.
     */
    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * Get the admin who rejected this request.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Scope: Filter by request type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter case requests only.
     */
    public function scopeCases($query)
    {
        return $query->where('type', 'case');
    }

    /**
     * Scope: Filter statute requests only.
     */
    public function scopeStatutes($query)
    {
        return $query->where('type', 'statute');
    }

    /**
     * Scope: Filter provision requests only.
     */
    public function scopeProvisions($query)
    {
        return $query->where('type', 'provision');
    }

    /**
     * Scope: Filter division requests only.
     */
    public function scopeDivisions($query)
    {
        return $query->where('type', 'division');
    }

    /**
     * Scope: Filter pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter in-progress requests.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Filter fulfilled requests.
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    /**
     * Scope: Filter rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Filter requests for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Search by title.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'like', "%{$search}%");
    }

    /**
     * Mark request as fulfilled.
     *
     * @param Model $createdContent The created CourtCase, Statute, etc.
     * @param int $adminId ID of the admin fulfilling the request
     * @return bool
     */
    public function markAsFulfilled(Model $createdContent, int $adminId): bool
    {
        $this->created_content_type = get_class($createdContent);
        $this->created_content_id = $createdContent->id;
        $this->status = 'fulfilled';
        $this->fulfilled_by = $adminId;
        $this->fulfilled_at = now();

        return $this->save();
    }

    /**
     * Mark request as rejected.
     *
     * @param int $adminId ID of the admin rejecting the request
     * @param string|null $reason Optional rejection reason
     * @return bool
     */
    public function markAsRejected(int $adminId, ?string $reason = null): bool
    {
        $this->status = 'rejected';
        $this->rejected_by = $adminId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Mark request as in progress.
     *
     * @return bool
     */
    public function markAsInProgress(): bool
    {
        $this->status = 'in_progress';
        return $this->save();
    }

    /**
     * Check if request is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is fulfilled.
     *
     * @return bool
     */
    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    /**
     * Check if request is rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request can be edited by user.
     * Users cannot edit after submission (immutable).
     *
     * @return bool
     */
    public function canBeEditedByUser(): bool
    {
        return false; // Requests are immutable after submission
    }

    /**
     * Check if request can be deleted by user.
     * Only pending requests can be deleted.
     *
     * @return bool
     */
    public function canBeDeletedByUser(): bool
    {
        return $this->isPending();
    }

    /**
     * Get count of duplicate requests with same title.
     *
     * @return int
     */
    public function getDuplicateCount(): int
    {
        return static::where('title', $this->title)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->count();
    }

    /**
     * Get the human-readable type name.
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'case' => 'Case',
            'statute' => 'Statute',
            'provision' => 'Provision',
            'division' => 'Division',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the human-readable status name.
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'fulfilled' => 'Fulfilled',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}
```

### Update CourtCase Model

**Location:** `app/Models/CourtCase.php`

Add this relationship to the existing CourtCase model:

```php
/**
 * Get the content requests that resulted in this case.
 */
public function contentRequests(): HasMany
{
    return $this->morphMany(ContentRequest::class, 'created_content');
}
```

### Update Statute Model (Future)

**Location:** `app/Models/Statute.php`

Add this relationship to the existing Statute model:

```php
/**
 * Get the content requests that resulted in this statute.
 */
public function contentRequests(): HasMany
{
    return $this->morphMany(ContentRequest::class, 'created_content');
}

/**
 * Get requests for provisions/divisions under this statute.
 */
public function pendingContentRequests(): HasMany
{
    return $this->hasMany(ContentRequest::class, 'statute_id');
}
```

---

## Validation Layer

### CreateContentRequestRequest

**Location:** `app/Http/Requests/CreateContentRequestRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateContentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:case,statute,provision,division',
            'title' => 'required|string|max:500',
            'additional_notes' => 'nullable|string|max:2000',

            // For provisions/divisions
            'statute_id' => 'required_if:type,provision,division|nullable|exists:statutes,id',
            'parent_division_id' => 'nullable|exists:statute_divisions,id',
            'parent_provision_id' => 'nullable|exists:statute_provisions,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please specify the type of content you are requesting.',
            'type.in' => 'Invalid content type. Must be: case, statute, provision, or division.',
            'title.required' => 'Please provide a title for the requested content.',
            'title.max' => 'Title must not exceed 500 characters.',
            'additional_notes.max' => 'Additional notes must not exceed 2000 characters.',
            'statute_id.required_if' => 'Statute ID is required for provision and division requests.',
            'statute_id.exists' => 'The selected statute does not exist.',
            'parent_division_id.exists' => 'The selected parent division does not exist.',
            'parent_provision_id.exists' => 'The selected parent provision does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default to 'case' type if not specified
        if (!$this->has('type')) {
            $this->merge(['type' => 'case']);
        }

        // Sanitize title and notes
        if ($this->has('title')) {
            $this->merge(['title' => strip_tags($this->title)]);
        }

        if ($this->has('additional_notes')) {
            $this->merge(['additional_notes' => strip_tags($this->additional_notes)]);
        }
    }
}
```

### AdminUpdateContentRequestRequest

**Location:** `app/Http/Requests/AdminUpdateContentRequestRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateContentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAdminAccess();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contentRequest = $this->route('content_request');

        return [
            'status' => 'sometimes|in:pending,in_progress,fulfilled,rejected',

            // Polymorphic content linkage
            'created_content_type' => [
                'sometimes',
                'string',
                Rule::in([
                    'App\Models\CourtCase',
                    'App\Models\Statute',
                    'App\Models\StatuteProvision',
                    'App\Models\StatuteDivision',
                ]),
            ],
            'created_content_id' => 'required_with:created_content_type|integer',

            // Rejection
            'rejection_reason' => 'nullable|string|max:2000',

            // Admin notes (via Commentable trait)
            'admin_notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Must be: pending, in_progress, fulfilled, or rejected.',
            'created_content_type.in' => 'Invalid content type.',
            'created_content_id.required_with' => 'Content ID is required when specifying content type.',
            'rejection_reason.max' => 'Rejection reason must not exceed 2000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $request = $this;
            $contentRequest = $this->route('content_request');

            // If marking as fulfilled, ensure created_content is provided
            if ($request->status === 'fulfilled' &&
                (!$request->created_content_type || !$request->created_content_id)) {
                $validator->errors()->add(
                    'created_content_id',
                    'You must link created content when marking request as fulfilled.'
                );
            }

            // If providing created_content, verify it exists
            if ($request->created_content_type && $request->created_content_id) {
                $model = $request->created_content_type;

                if (!class_exists($model)) {
                    $validator->errors()->add('created_content_type', 'Invalid model class.');
                    return;
                }

                if (!$model::find($request->created_content_id)) {
                    $validator->errors()->add('created_content_id', 'The specified content does not exist.');
                }
            }

            // If marking as rejected with no reason, warn (optional)
            if ($request->status === 'rejected' && !$request->rejection_reason) {
                // This is allowed, just a best practice warning
                // You could uncomment this to require rejection reason:
                // $validator->errors()->add('rejection_reason', 'Please provide a reason for rejection.');
            }
        });
    }
}
```

---

## API Resources

### ContentRequestResource

**Location:** `app/Http/Resources/ContentRequestResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_name' => $this->type_name,
            'title' => $this->title,
            'additional_notes' => $this->additional_notes,
            'status' => $this->status,
            'status_name' => $this->status_name,

            // User who made the request
            'user' => new UserResource($this->whenLoaded('user')),

            // Created content (when fulfilled)
            'created_content' => $this->when(
                $this->isFulfilled() && $this->relationLoaded('createdContent'),
                function () {
                    return match($this->created_content_type) {
                        'App\Models\CourtCase' => new CaseResource($this->createdContent),
                        'App\Models\Statute' => new StatuteResource($this->createdContent),
                        'App\Models\StatuteProvision' => new ProvisionResource($this->createdContent),
                        'App\Models\StatuteDivision' => new DivisionResource($this->createdContent),
                        default => null,
                    };
                }
            ),

            // Related statute (for provision/division requests)
            'statute' => new StatuteResource($this->whenLoaded('statute')),
            'parent_division' => new DivisionResource($this->whenLoaded('parentDivision')),
            'parent_provision' => new ProvisionResource($this->whenLoaded('parentProvision')),

            // Fulfillment info
            'fulfilled_by' => new UserResource($this->whenLoaded('fulfilledBy')),
            'fulfilled_at' => $this->fulfilled_at?->toISOString(),

            // Rejection info
            'rejected_by' => new UserResource($this->whenLoaded('rejectedBy')),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejection_reason' => $this->when(
                $this->isRejected() && ($this->user_id === $request->user()?->id || $request->user()?->hasAdminAccess()),
                $this->rejection_reason
            ),

            // Duplicate count (admin only)
            'duplicate_count' => $this->when(
                $request->user()?->hasAdminAccess(),
                fn() => $this->getDuplicateCount()
            ),

            // Permissions
            'can_edit' => $this->canBeEditedByUser(),
            'can_delete' => $this->canBeDeletedByUser(),

            // Comments (if loaded)
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(
                isset($this->comments_count),
                $this->comments_count
            ),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## Controllers

### ContentRequestController (User)

**Location:** `app/Http/Controllers/ContentRequestController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use App\Http\Requests\CreateContentRequestRequest;
use App\Http\Resources\ContentRequestResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentRequestController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the user's content requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = ContentRequest::where('user_id', $user->id)
                ->with(['user', 'createdContent', 'statute', 'fulfilledBy', 'rejectedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Search by title
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Sort
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 100);
            $contentRequests = $query->paginate($perPage);

            return ApiResponse::success([
                'content_requests' => ContentRequestResource::collection($contentRequests),
                'meta' => [
                    'current_page' => $contentRequests->currentPage(),
                    'last_page' => $contentRequests->lastPage(),
                    'per_page' => $contentRequests->perPage(),
                    'total' => $contentRequests->total(),
                    'from' => $contentRequests->firstItem(),
                    'to' => $contentRequests->lastItem(),
                ],
            ], 'Content requests retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving content requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content requests', null, 500);
        }
    }

    /**
     * Store a newly created content request.
     *
     * @param CreateContentRequestRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateContentRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $contentRequest = ContentRequest::create([
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'title' => $request->title,
                'additional_notes' => $request->additional_notes,
                'statute_id' => $request->statute_id,
                'parent_division_id' => $request->parent_division_id,
                'parent_provision_id' => $request->parent_provision_id,
                'status' => 'pending',
            ]);

            $contentRequest->load(['user', 'statute']);

            // Send email notifications
            $this->notificationService->sendContentRequestCreatedEmail(
                $request->user(),
                $contentRequest
            );

            DB::commit();

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request submitted successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while creating content request', null, 500);
        }
    }

    /**
     * Display the specified content request.
     *
     * @param Request $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ContentRequest $contentRequest)
    {
        try {
            // Ensure user can only view their own requests
            if ($contentRequest->user_id !== $request->user()->id) {
                return ApiResponse::forbidden('You can only view your own content requests');
            }

            $contentRequest->load([
                'user',
                'createdContent',
                'statute',
                'parentDivision',
                'parentProvision',
                'fulfilledBy',
                'rejectedBy',
                'comments.user',
            ]);

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content request', null, 500);
        }
    }

    /**
     * Remove the specified content request.
     * Only pending requests can be deleted by users.
     *
     * @param Request $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, ContentRequest $contentRequest)
    {
        try {
            // Ensure user can only delete their own requests
            if ($contentRequest->user_id !== $request->user()->id) {
                return ApiResponse::forbidden('You can only delete your own content requests');
            }

            // Only pending requests can be deleted
            if (!$contentRequest->canBeDeletedByUser()) {
                return ApiResponse::error(
                    'Only pending requests can be deleted',
                    null,
                    422
                );
            }

            $contentRequest->delete();

            return ApiResponse::success(
                null,
                'Content request deleted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error deleting content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while deleting content request', null, 500);
        }
    }
}
```

### AdminContentRequestController

**Location:** `app/Http/Controllers/AdminContentRequestController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use App\Http\Requests\AdminUpdateContentRequestRequest;
use App\Http\Resources\ContentRequestResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminContentRequestController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of all content requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ContentRequest::with([
                'user',
                'createdContent',
                'statute',
                'fulfilledBy',
                'rejectedBy'
            ]);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Search by title
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Sort
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = min($request->input('per_page', 15), 100);
            $contentRequests = $query->paginate($perPage);

            return ApiResponse::success([
                'content_requests' => ContentRequestResource::collection($contentRequests),
                'meta' => [
                    'current_page' => $contentRequests->currentPage(),
                    'last_page' => $contentRequests->lastPage(),
                    'per_page' => $contentRequests->perPage(),
                    'total' => $contentRequests->total(),
                    'from' => $contentRequests->firstItem(),
                    'to' => $contentRequests->lastItem(),
                ],
            ], 'Content requests retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Admin error retrieving content requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content requests', null, 500);
        }
    }

    /**
     * Display the specified content request.
     *
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ContentRequest $contentRequest)
    {
        try {
            $contentRequest->load([
                'user',
                'createdContent',
                'statute',
                'parentDivision',
                'parentProvision',
                'fulfilledBy',
                'rejectedBy',
                'comments.user',
            ]);

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Admin error retrieving content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving content request', null, 500);
        }
    }

    /**
     * Update the specified content request.
     *
     * @param AdminUpdateContentRequestRequest $request
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdminUpdateContentRequestRequest $request, ContentRequest $contentRequest)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $contentRequest->status;
            $changes = [];

            // Update status
            if ($request->has('status') && $request->status !== $oldStatus) {
                $contentRequest->status = $request->status;
                $changes['status'] = ['from' => $oldStatus, 'to' => $request->status];
            }

            // Link created content
            if ($request->has('created_content_type') && $request->has('created_content_id')) {
                $model = $request->created_content_type;
                $createdContent = $model::find($request->created_content_id);

                if ($createdContent) {
                    $contentRequest->markAsFulfilled($createdContent, $request->user()->id);
                    $changes['fulfilled'] = true;
                }
            }

            // Handle rejection
            if ($request->status === 'rejected') {
                $contentRequest->markAsRejected(
                    $request->user()->id,
                    $request->rejection_reason
                );
                $changes['rejected'] = true;
            }

            // Handle in_progress
            if ($request->status === 'in_progress' && $oldStatus === 'pending') {
                $contentRequest->markAsInProgress();
            }

            $contentRequest->save();
            $contentRequest->load([
                'user',
                'createdContent',
                'fulfilledBy',
                'rejectedBy'
            ]);

            // Send appropriate email notification
            if ($changes) {
                if (isset($changes['fulfilled'])) {
                    $this->notificationService->sendContentRequestFulfilledEmail(
                        $contentRequest->user,
                        $contentRequest
                    );
                } elseif (isset($changes['rejected'])) {
                    $this->notificationService->sendContentRequestRejectedEmail(
                        $contentRequest->user,
                        $contentRequest
                    );
                } elseif (isset($changes['status'])) {
                    $this->notificationService->sendContentRequestUpdatedEmail(
                        $contentRequest->user,
                        $contentRequest,
                        $changes
                    );
                }
            }

            DB::commit();

            return ApiResponse::success(
                ['content_request' => new ContentRequestResource($contentRequest)],
                'Content request updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin error updating content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while updating content request', null, 500);
        }
    }

    /**
     * Remove the specified content request.
     *
     * @param ContentRequest $contentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ContentRequest $contentRequest)
    {
        try {
            $contentRequest->delete();

            return ApiResponse::success(
                null,
                'Content request deleted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Admin error deleting content request: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while deleting content request', null, 500);
        }
    }

    /**
     * Get statistics about content requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => ContentRequest::count(),
                'by_status' => [
                    'pending' => ContentRequest::pending()->count(),
                    'in_progress' => ContentRequest::inProgress()->count(),
                    'fulfilled' => ContentRequest::fulfilled()->count(),
                    'rejected' => ContentRequest::rejected()->count(),
                ],
                'by_type' => [
                    'case' => ContentRequest::cases()->count(),
                    'statute' => ContentRequest::statutes()->count(),
                    'provision' => ContentRequest::provisions()->count(),
                    'division' => ContentRequest::divisions()->count(),
                ],
                'recent_activity' => [
                    'last_7_days' => ContentRequest::where('created_at', '>=', now()->subDays(7))->count(),
                    'last_30_days' => ContentRequest::where('created_at', '>=', now()->subDays(30))->count(),
                ],
                'fulfillment_rate' => $this->calculateFulfillmentRate(),
            ];

            return ApiResponse::success($stats, 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving content request stats: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving statistics', null, 500);
        }
    }

    /**
     * Get duplicate requests (same title, different users).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicates(Request $request)
    {
        try {
            $duplicates = DB::table('content_requests')
                ->select('title', 'type', DB::raw('COUNT(*) as request_count'))
                ->groupBy('title', 'type')
                ->having('request_count', '>', 1)
                ->orderBy('request_count', 'desc')
                ->limit(50)
                ->get();

            $detailedDuplicates = $duplicates->map(function ($duplicate) {
                $requests = ContentRequest::where('title', $duplicate->title)
                    ->where('type', $duplicate->type)
                    ->with(['user'])
                    ->get();

                return [
                    'title' => $duplicate->title,
                    'type' => $duplicate->type,
                    'request_count' => $duplicate->request_count,
                    'requests' => ContentRequestResource::collection($requests),
                ];
            });

            return ApiResponse::success(
                ['duplicates' => $detailedDuplicates],
                'Duplicate requests retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error retrieving duplicate requests: ' . $e->getMessage());
            return ApiResponse::error('An error occurred while retrieving duplicates', null, 500);
        }
    }

    /**
     * Calculate fulfillment rate percentage.
     *
     * @return float
     */
    private function calculateFulfillmentRate(): float
    {
        $total = ContentRequest::count();

        if ($total === 0) {
            return 0.0;
        }

        $fulfilled = ContentRequest::fulfilled()->count();

        return round(($fulfilled / $total) * 100, 2);
    }
}
```

---

## Email Notifications

### Mailable Classes

#### ContentRequestCreatedEmail

**Location:** `app/Mail/ContentRequestCreatedEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContentRequestCreatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ContentRequest $contentRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Content Request Submitted - ' . $this->contentRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-request-created',
            with: [
                'userName' => $this->user->name,
                'requestType' => $this->contentRequest->type_name,
                'requestTitle' => $this->contentRequest->title,
                'requestId' => $this->contentRequest->id,
                'additionalNotes' => $this->contentRequest->additional_notes,
            ],
        );
    }
}
```

#### ContentRequestCreatedAdminEmail

**Location:** `app/Mail/ContentRequestCreatedAdminEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContentRequestCreatedAdminEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ContentRequest $contentRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[New Request] ' . $this->contentRequest->type_name . ' - ' . $this->contentRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-request-created-admin',
            with: [
                'requesterName' => $this->user->name,
                'requesterEmail' => $this->user->email,
                'requestType' => $this->contentRequest->type_name,
                'requestTitle' => $this->contentRequest->title,
                'requestId' => $this->contentRequest->id,
                'additionalNotes' => $this->contentRequest->additional_notes,
                'duplicateCount' => $this->contentRequest->getDuplicateCount(),
            ],
        );
    }
}
```

#### ContentRequestFulfilledEmail

**Location:** `app/Mail/ContentRequestFulfilledEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContentRequestFulfilledEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ContentRequest $contentRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Content Request Has Been Fulfilled - ' . $this->contentRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $createdContent = $this->contentRequest->createdContent;
        $contentUrl = $this->getContentUrl($createdContent);

        return new Content(
            view: 'emails.content-request-fulfilled',
            with: [
                'userName' => $this->user->name,
                'requestType' => $this->contentRequest->type_name,
                'requestTitle' => $this->contentRequest->title,
                'createdContentTitle' => $createdContent?->title ?? $createdContent?->name ?? 'N/A',
                'contentUrl' => $contentUrl,
                'fulfilledBy' => $this->contentRequest->fulfilledBy?->name,
            ],
        );
    }

    /**
     * Get URL for the created content.
     */
    private function getContentUrl($content): ?string
    {
        if (!$content) {
            return null;
        }

        $baseUrl = config('app.frontend_url', 'https://lawexa.com');

        return match(get_class($content)) {
            'App\Models\CourtCase' => "{$baseUrl}/cases/{$content->slug}",
            'App\Models\Statute' => "{$baseUrl}/statutes/{$content->slug}",
            'App\Models\StatuteProvision' => "{$baseUrl}/statutes/{$content->statute->slug}/provisions/{$content->id}",
            'App\Models\StatuteDivision' => "{$baseUrl}/statutes/{$content->statute->slug}/divisions/{$content->id}",
            default => null,
        };
    }
}
```

#### ContentRequestRejectedEmail

**Location:** `app/Mail/ContentRequestRejectedEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContentRequestRejectedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ContentRequest $contentRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Content Request Update - ' . $this->contentRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-request-rejected',
            with: [
                'userName' => $this->user->name,
                'requestType' => $this->contentRequest->type_name,
                'requestTitle' => $this->contentRequest->title,
                'rejectionReason' => $this->contentRequest->rejection_reason,
                'rejectedBy' => $this->contentRequest->rejectedBy?->name,
            ],
        );
    }
}
```

#### ContentRequestUpdatedEmail

**Location:** `app/Mail/ContentRequestUpdatedEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContentRequestUpdatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ContentRequest $contentRequest,
        public array $changes = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Content Request Status Updated - ' . $this->contentRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-request-updated',
            with: [
                'userName' => $this->user->name,
                'requestType' => $this->contentRequest->type_name,
                'requestTitle' => $this->contentRequest->title,
                'currentStatus' => $this->contentRequest->status_name,
                'changes' => $this->changes,
            ],
        );
    }
}
```

### Email Templates (Blade Views)

#### content-request-created.blade.php

**Location:** `resources/views/emails/content-request-created.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Request Submitted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
        <h1 style="color: #2c3e50; margin-top: 0;">Content Request Submitted</h1>

        <p>Hello {{ $userName }},</p>

        <p>Thank you for submitting a content request to Lawexa. We've received your request and our team will review it shortly.</p>

        <div style="background-color: #fff; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Request Details</h3>
            <p><strong>Type:</strong> {{ $requestType }}</p>
            <p><strong>Title:</strong> {{ $requestTitle }}</p>
            <p><strong>Request ID:</strong> #{{ $requestId }}</p>

            @if($additionalNotes)
            <p><strong>Your Notes:</strong></p>
            <p style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;">{{ $additionalNotes }}</p>
            @endif
        </div>

        <p>You will receive an email notification when:</p>
        <ul>
            <li>Your request is assigned to a researcher</li>
            <li>The content is added to our database</li>
            <li>There are any updates regarding your request</li>
        </ul>

        <p>You can track the status of your request by logging into your Lawexa account and visiting the "My Requests" section.</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>The Lawexa Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 20px;">
        <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
```

#### content-request-created-admin.blade.php

**Location:** `resources/views/emails/content-request-created-admin.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Content Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #fff3cd; border-radius: 10px; padding: 30px; margin-bottom: 20px; border-left: 5px solid #ffc107;">
        <h1 style="color: #856404; margin-top: 0;">🔔 New Content Request</h1>

        <p>A user has submitted a new content request that requires review.</p>

        <div style="background-color: #fff; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Request Information</h3>
            <p><strong>Type:</strong> <span style="background-color: #3498db; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">{{ $requestType }}</span></p>
            <p><strong>Title:</strong> {{ $requestTitle }}</p>
            <p><strong>Request ID:</strong> #{{ $requestId }}</p>
        </div>

        <div style="background-color: #fff; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Requester Information</h3>
            <p><strong>Name:</strong> {{ $requesterName }}</p>
            <p><strong>Email:</strong> {{ $requesterEmail }}</p>
        </div>

        @if($additionalNotes)
        <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h4 style="margin-top: 0;">Additional Notes:</h4>
            <p>{{ $additionalNotes }}</p>
        </div>
        @endif

        @if($duplicateCount > 0)
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <p style="margin: 0;"><strong>⚠️ Note:</strong> There are {{ $duplicateCount }} similar request(s) with the same title.</p>
        </div>
        @endif

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ config('app.admin_url') }}/content-requests/{{ $requestId }}"
               style="background-color: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                View Request
            </a>
        </div>
    </div>

    <div style="text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 20px;">
        <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
    </div>
</body>
</html>
```

#### content-request-fulfilled.blade.php

**Location:** `resources/views/emails/content-request-fulfilled.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Request Fulfilled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #d4edda; border-radius: 10px; padding: 30px; margin-bottom: 20px; border-left: 5px solid #28a745;">
        <h1 style="color: #155724; margin-top: 0;">✅ Your Request Has Been Fulfilled!</h1>

        <p>Hello {{ $userName }},</p>

        <p>Great news! The content you requested has been added to Lawexa.</p>

        <div style="background-color: #fff; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Your Request</h3>
            <p><strong>Type:</strong> {{ $requestType }}</p>
            <p><strong>Requested:</strong> {{ $requestTitle }}</p>
        </div>

        <div style="background-color: #fff; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Added Content</h3>
            <p><strong>Title:</strong> {{ $createdContentTitle }}</p>
            @if($fulfilledBy)
            <p><strong>Added by:</strong> {{ $fulfilledBy }}</p>
            @endif
        </div>

        @if($contentUrl)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $contentUrl }}"
               style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; font-size: 16px;">
                View Content
            </a>
        </div>
        @endif

        <p>Thank you for helping us improve Lawexa's legal database. Your contribution helps students and legal professionals access important legal resources.</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>The Lawexa Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 20px;">
        <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
    </div>
</body>
</html>
```

#### content-request-rejected.blade.php

**Location:** `resources/views/emails/content-request-rejected.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Request Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
        <h1 style="color: #2c3e50; margin-top: 0;">Content Request Update</h1>

        <p>Hello {{ $userName }},</p>

        <p>Thank you for your content request. After review, we're unable to fulfill this request at this time.</p>

        <div style="background-color: #fff; border-left: 4px solid #6c757d; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Request Details</h3>
            <p><strong>Type:</strong> {{ $requestType }}</p>
            <p><strong>Title:</strong> {{ $requestTitle }}</p>
        </div>

        @if($rejectionReason)
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h4 style="margin-top: 0; color: #856404;">Reason:</h4>
            <p style="margin-bottom: 0;">{{ $rejectionReason }}</p>
        </div>
        @endif

        @if($rejectedBy)
        <p style="font-size: 14px; color: #6c757d;"><em>Reviewed by: {{ $rejectedBy }}</em></p>
        @endif

        <p>If you have any questions or would like to submit a different request, please feel free to do so through your account.</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>The Lawexa Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 20px;">
        <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
    </div>
</body>
</html>
```

#### content-request-updated.blade.php

**Location:** `resources/views/emails/content-request-updated.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Request Status Updated</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
        <h1 style="color: #2c3e50; margin-top: 0;">Content Request Status Update</h1>

        <p>Hello {{ $userName }},</p>

        <p>There's an update on your content request.</p>

        <div style="background-color: #fff; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #2c3e50;">Request Details</h3>
            <p><strong>Type:</strong> {{ $requestType }}</p>
            <p><strong>Title:</strong> {{ $requestTitle }}</p>
            <p><strong>Current Status:</strong> <span style="background-color: #3498db; color: white; padding: 3px 10px; border-radius: 3px; font-size: 14px;">{{ $currentStatus }}</span></p>
        </div>

        @if(!empty($changes))
        <div style="background-color: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h4 style="margin-top: 0;">What Changed:</h4>
            <ul>
                @foreach($changes as $field => $change)
                    @if(is_array($change))
                        <li><strong>{{ ucfirst($field) }}:</strong> {{ $change['from'] }} → {{ $change['to'] }}</li>
                    @else
                        <li><strong>{{ ucfirst($field) }}</strong></li>
                    @endif
                @endforeach
            </ul>
        </div>
        @endif

        <p>We'll keep you updated on any further progress. You can always check the status in your account's "My Requests" section.</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>The Lawexa Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 20px;">
        <p>© {{ date('Y') }} Lawexa. All rights reserved.</p>
    </div>
</body>
</html>
```

---

## Service Layer

### Update NotificationService

**Location:** `app/Services/NotificationService.php`

Add these methods to the existing NotificationService class:

```php
use App\Mail\ContentRequestCreatedEmail;
use App\Mail\ContentRequestCreatedAdminEmail;
use App\Mail\ContentRequestFulfilledEmail;
use App\Mail\ContentRequestRejectedEmail;
use App\Mail\ContentRequestUpdatedEmail;
use App\Models\ContentRequest;

/**
 * Send email when content request is created.
 *
 * @param User $user
 * @param ContentRequest $contentRequest
 * @return void
 */
public function sendContentRequestCreatedEmail(User $user, ContentRequest $contentRequest): void
{
    try {
        // Send confirmation to user
        Mail::to($user->email)->queue(new ContentRequestCreatedEmail($user, $contentRequest));

        // Send notification to all admins
        $adminEmails = $this->getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            Mail::to($adminEmail)->queue(new ContentRequestCreatedAdminEmail($user, $contentRequest));
        }

        Log::info('Content request created emails sent', [
            'request_id' => $contentRequest->id,
            'user_id' => $user->id,
            'type' => $contentRequest->type,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send content request created email: ' . $e->getMessage());
    }
}

/**
 * Send email when content request is fulfilled.
 *
 * @param User $user
 * @param ContentRequest $contentRequest
 * @return void
 */
public function sendContentRequestFulfilledEmail(User $user, ContentRequest $contentRequest): void
{
    try {
        Mail::to($user->email)->queue(new ContentRequestFulfilledEmail($user, $contentRequest));

        Log::info('Content request fulfilled email sent', [
            'request_id' => $contentRequest->id,
            'user_id' => $user->id,
            'created_content_type' => $contentRequest->created_content_type,
            'created_content_id' => $contentRequest->created_content_id,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send content request fulfilled email: ' . $e->getMessage());
    }
}

/**
 * Send email when content request is rejected.
 *
 * @param User $user
 * @param ContentRequest $contentRequest
 * @return void
 */
public function sendContentRequestRejectedEmail(User $user, ContentRequest $contentRequest): void
{
    try {
        Mail::to($user->email)->queue(new ContentRequestRejectedEmail($user, $contentRequest));

        Log::info('Content request rejected email sent', [
            'request_id' => $contentRequest->id,
            'user_id' => $user->id,
            'rejection_reason' => $contentRequest->rejection_reason,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send content request rejected email: ' . $e->getMessage());
    }
}

/**
 * Send email when content request is updated.
 *
 * @param User $user
 * @param ContentRequest $contentRequest
 * @param array $changes
 * @return void
 */
public function sendContentRequestUpdatedEmail(User $user, ContentRequest $contentRequest, array $changes = []): void
{
    try {
        Mail::to($user->email)->queue(new ContentRequestUpdatedEmail($user, $contentRequest, $changes));

        Log::info('Content request updated email sent', [
            'request_id' => $contentRequest->id,
            'user_id' => $user->id,
            'changes' => $changes,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send content request updated email: ' . $e->getMessage());
    }
}
```

---

## Routing

### API Routes

**Location:** `routes/api.php`

Add these routes to your existing routes file:

```php
use App\Http\Controllers\ContentRequestController;
use App\Http\Controllers\AdminContentRequestController;

// User Content Request Routes (authenticated + verified users)
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('content-requests')->group(function () {
        Route::get('/', [ContentRequestController::class, 'index']);
        Route::post('/', [ContentRequestController::class, 'store']);
        Route::get('/{contentRequest}', [ContentRequestController::class, 'show']);
        Route::delete('/{contentRequest}', [ContentRequestController::class, 'destroy']);
    });
});

// Admin Content Request Routes (admins, researchers, superadmins)
Route::middleware(['auth:sanctum', 'role:admin,researcher,superadmin'])->prefix('admin')->group(function () {
    Route::prefix('content-requests')->group(function () {
        Route::get('/stats', [AdminContentRequestController::class, 'stats']);
        Route::get('/duplicates', [AdminContentRequestController::class, 'duplicates']);
        Route::get('/', [AdminContentRequestController::class, 'index']);
        Route::get('/{contentRequest}', [AdminContentRequestController::class, 'show']);
        Route::put('/{contentRequest}', [AdminContentRequestController::class, 'update']);
        Route::delete('/{contentRequest}', [AdminContentRequestController::class, 'destroy']);
    });
});
```

---

## Integration Points

### Update AdminCaseController

**Location:** `app/Http/Controllers/AdminCaseController.php`

In the `store` method, add support for linking to content requests:

```php
use App\Models\ContentRequest;

public function store(CreateCaseRequest $request)
{
    DB::beginTransaction();

    try {
        // ... existing case creation logic ...

        $case = CourtCase::create([
            // ... existing fields ...
        ]);

        // Handle content request linkage
        if ($request->has('content_request_id')) {
            $contentRequest = ContentRequest::find($request->content_request_id);

            if ($contentRequest && $contentRequest->isPending()) {
                $contentRequest->markAsFulfilled($case, $request->user()->id);

                // Send fulfillment email
                app(NotificationService::class)->sendContentRequestFulfilledEmail(
                    $contentRequest->user,
                    $contentRequest
                );
            }
        }

        DB::commit();

        return ApiResponse::success(
            [
                'case' => new CaseResource($case),
                'from_content_request' => $request->has('content_request_id'),
            ],
            'Case created successfully',
            201
        );

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating case: ' . $e->getMessage());
        return ApiResponse::error('An error occurred while creating case', null, 500);
    }
}
```

Update `CreateCaseRequest` validation:

```php
public function rules(): array
{
    return [
        // ... existing rules ...
        'content_request_id' => 'nullable|exists:content_requests,id',
    ];
}
```

---

## Testing Guidelines

### Unit Tests

Create test file: `tests/Unit/ContentRequestTest.php`

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\ContentRequest;
use App\Models\CourtCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_content_request()
    {
        $user = User::factory()->create();

        $request = ContentRequest::create([
            'user_id' => $user->id,
            'type' => 'case',
            'title' => 'Test Case Title',
            'additional_notes' => 'Some notes',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('content_requests', [
            'title' => 'Test Case Title',
            'type' => 'case',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_mark_request_as_fulfilled()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $case = CourtCase::factory()->create();

        $request = ContentRequest::factory()->create([
            'type' => 'case',
            'status' => 'pending',
        ]);

        $result = $request->markAsFulfilled($case, $admin->id);

        $this->assertTrue($result);
        $this->assertEquals('fulfilled', $request->status);
        $this->assertEquals($case->id, $request->created_content_id);
        $this->assertEquals(CourtCase::class, $request->created_content_type);
        $this->assertEquals($admin->id, $request->fulfilled_by);
        $this->assertNotNull($request->fulfilled_at);
    }

    /** @test */
    public function it_can_mark_request_as_rejected()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $request = ContentRequest::factory()->create([
            'status' => 'pending',
        ]);

        $result = $request->markAsRejected($admin->id, 'Content already exists');

        $this->assertTrue($result);
        $this->assertEquals('rejected', $request->status);
        $this->assertEquals($admin->id, $request->rejected_by);
        $this->assertEquals('Content already exists', $request->rejection_reason);
        $this->assertNotNull($request->rejected_at);
    }

    /** @test */
    public function it_cannot_be_edited_after_submission()
    {
        $request = ContentRequest::factory()->create();

        $this->assertFalse($request->canBeEditedByUser());
    }

    /** @test */
    public function it_can_only_be_deleted_when_pending()
    {
        $pendingRequest = ContentRequest::factory()->create(['status' => 'pending']);
        $fulfilledRequest = ContentRequest::factory()->create(['status' => 'fulfilled']);

        $this->assertTrue($pendingRequest->canBeDeletedByUser());
        $this->assertFalse($fulfilledRequest->canBeDeletedByUser());
    }

    /** @test */
    public function it_can_count_duplicates()
    {
        ContentRequest::factory()->create(['title' => 'Same Title', 'type' => 'case']);
        ContentRequest::factory()->create(['title' => 'Same Title', 'type' => 'case']);
        $request = ContentRequest::factory()->create(['title' => 'Same Title', 'type' => 'case']);

        $this->assertEquals(2, $request->getDuplicateCount());
    }
}
```

### Feature Tests

Create test file: `tests/Feature/ContentRequestApiTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ContentRequest;
use App\Models\CourtCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ContentRequestApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_content_request()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/content-requests', [
            'type' => 'case',
            'title' => 'Smith v Jones',
            'additional_notes' => 'Important case on contracts',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'content_request' => [
                        'id',
                        'type',
                        'title',
                        'status',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('content_requests', [
            'title' => 'Smith v Jones',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_can_only_view_own_requests()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $request1 = ContentRequest::factory()->create(['user_id' => $user1->id]);
        $request2 = ContentRequest::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/content-requests');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.content_requests');
    }

    /** @test */
    public function user_cannot_delete_fulfilled_request()
    {
        $user = User::factory()->create();
        $request = ContentRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'fulfilled',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/content-requests/{$request->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('content_requests', ['id' => $request->id]);
    }

    /** @test */
    public function admin_can_view_all_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        ContentRequest::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/content-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.content_requests');
    }

    /** @test */
    public function admin_can_fulfill_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $case = CourtCase::factory()->create();
        $request = ContentRequest::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/admin/content-requests/{$request->id}", [
            'status' => 'fulfilled',
            'created_content_type' => CourtCase::class,
            'created_content_id' => $case->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('content_requests', [
            'id' => $request->id,
            'status' => 'fulfilled',
            'created_content_id' => $case->id,
        ]);
    }

    /** @test */
    public function admin_can_reject_request_with_reason()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = ContentRequest::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/admin/content-requests/{$request->id}", [
            'status' => 'rejected',
            'rejection_reason' => 'Content already exists in database',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('content_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Content already exists in database',
        ]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_endpoints()
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/content-requests');

        $response->assertStatus(403);
    }
}
```

### Manual Testing Checklist

- [ ] User can submit case request
- [ ] User receives confirmation email
- [ ] Admins receive notification email
- [ ] User can view only their own requests
- [ ] User can delete pending requests
- [ ] User cannot delete fulfilled/rejected requests
- [ ] User cannot edit requests after submission
- [ ] Admin can view all requests
- [ ] Admin can filter requests by status/type
- [ ] Admin can search requests by title
- [ ] Admin can view duplicate requests
- [ ] Admin can change request status
- [ ] Admin can link case to request
- [ ] Admin can reject with reason
- [ ] User receives email when request fulfilled
- [ ] User receives email when request rejected
- [ ] Fulfilled email includes link to created case
- [ ] Statistics endpoint returns correct counts
- [ ] Pagination works correctly
- [ ] Polymorphic relationships work for all content types

---

## API Documentation

### User Endpoints

#### 1. Create Content Request

**Endpoint:** `POST /api/content-requests`

**Authentication:** Required (verified users)

**Request Body:**
```json
{
  "type": "case",
  "title": "Smith v Jones, [2020] UKSC 15",
  "additional_notes": "Important case on contract law and consideration. Would be useful for students studying contract formation."
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Content request submitted successfully",
  "data": {
    "content_request": {
      "id": 1,
      "type": "case",
      "type_name": "Case",
      "title": "Smith v Jones, [2020] UKSC 15",
      "additional_notes": "Important case on contract law...",
      "status": "pending",
      "status_name": "Pending",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "can_edit": false,
      "can_delete": true,
      "created_at": "2025-10-20T10:30:00.000000Z",
      "updated_at": "2025-10-20T10:30:00.000000Z"
    }
  }
}
```

#### 2. Get My Content Requests

**Endpoint:** `GET /api/content-requests`

**Authentication:** Required

**Query Parameters:**
- `status` (optional): Filter by status (pending, in_progress, fulfilled, rejected)
- `type` (optional): Filter by type (case, statute, provision, division)
- `search` (optional): Search in titles
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number

**Example:** `GET /api/content-requests?status=pending&per_page=10`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Content requests retrieved successfully",
  "data": {
    "content_requests": [
      {
        "id": 1,
        "type": "case",
        "title": "Smith v Jones",
        "status": "pending",
        "created_at": "2025-10-20T10:30:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 1,
      "from": 1,
      "to": 1
    }
  }
}
```

#### 3. Get Single Content Request

**Endpoint:** `GET /api/content-requests/{id}`

**Authentication:** Required (can only view own requests)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Content request retrieved successfully",
  "data": {
    "content_request": {
      "id": 1,
      "type": "case",
      "title": "Smith v Jones",
      "additional_notes": "Important case...",
      "status": "fulfilled",
      "user": {...},
      "created_content": {
        "id": 5109,
        "title": "Smith v Jones, [2020] UKSC 15",
        "slug": "smith-v-jones-2020-uksc-15-5109",
        "court": "Supreme Court",
        "citation": "[2020] UKSC 15"
      },
      "fulfilled_by": {
        "id": 1,
        "name": "Admin User"
      },
      "fulfilled_at": "2025-10-21T14:20:00.000000Z",
      "created_at": "2025-10-20T10:30:00.000000Z"
    }
  }
}
```

#### 4. Delete Content Request

**Endpoint:** `DELETE /api/content-requests/{id}`

**Authentication:** Required (can only delete own pending requests)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Content request deleted successfully",
  "data": null
}
```

**Error Response (422) - Cannot delete:**
```json
{
  "status": "error",
  "message": "Only pending requests can be deleted",
  "data": null
}
```

### Admin Endpoints

#### 1. Get All Content Requests

**Endpoint:** `GET /api/admin/content-requests`

**Authentication:** Required (admin, researcher, superadmin)

**Query Parameters:**
- `status`, `type`, `search`, `per_page`, `page` (same as user endpoint)
- `user_id` (optional): Filter by specific user

**Example:** `GET /api/admin/content-requests?status=pending&type=case`

**Success Response:** Similar to user endpoint but includes all requests

#### 2. Get Content Request Statistics

**Endpoint:** `GET /api/admin/content-requests/stats`

**Authentication:** Required (admin)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Statistics retrieved successfully",
  "data": {
    "total": 150,
    "by_status": {
      "pending": 25,
      "in_progress": 10,
      "fulfilled": 100,
      "rejected": 15
    },
    "by_type": {
      "case": 120,
      "statute": 20,
      "provision": 8,
      "division": 2
    },
    "recent_activity": {
      "last_7_days": 12,
      "last_30_days": 45
    },
    "fulfillment_rate": 66.67
  }
}
```

#### 3. Get Duplicate Requests

**Endpoint:** `GET /api/admin/content-requests/duplicates`

**Authentication:** Required (admin)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Duplicate requests retrieved successfully",
  "data": {
    "duplicates": [
      {
        "title": "Smith v Jones",
        "type": "case",
        "request_count": 3,
        "requests": [
          {
            "id": 1,
            "user": {...},
            "status": "pending",
            "created_at": "2025-10-20T10:30:00.000000Z"
          },
          {
            "id": 5,
            "user": {...},
            "status": "pending",
            "created_at": "2025-10-22T14:15:00.000000Z"
          }
        ]
      }
    ]
  }
}
```

#### 4. Update Content Request (Fulfill/Reject)

**Endpoint:** `PUT /api/admin/content-requests/{id}`

**Authentication:** Required (admin)

**Request Body (Fulfill):**
```json
{
  "status": "fulfilled",
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 5109
}
```

**Request Body (Reject):**
```json
{
  "status": "rejected",
  "rejection_reason": "This case already exists in our database under a different citation."
}
```

**Request Body (Mark In Progress):**
```json
{
  "status": "in_progress"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Content request updated successfully",
  "data": {
    "content_request": {...}
  }
}
```

#### 5. Delete Content Request

**Endpoint:** `DELETE /api/admin/content-requests/{id}`

**Authentication:** Required (admin)

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Content request deleted successfully",
  "data": null
}
```

### cURL Examples

**Create Request:**
```bash
curl -X POST "https://rest.lawexa.com/api/content-requests" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "case",
    "title": "Smith v Jones, [2020] UKSC 15",
    "additional_notes": "Important contract law case"
  }'
```

**Get My Requests:**
```bash
curl -X GET "https://rest.lawexa.com/api/content-requests?status=pending&per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Admin: Fulfill Request:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/content-requests/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "fulfilled",
    "created_content_type": "App\\Models\\CourtCase",
    "created_content_id": 5109
  }'
```

**Admin: Get Statistics:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/content-requests/stats" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

---

## Future Extensions

### Phase 2: Statute Requests

To implement statute requests, the system is already prepared. Simply:

1. Update frontend to allow `type: 'statute'` in request form
2. When admin creates statute, pass `content_request_id` to `AdminStatuteController@store`
3. Link statute to request using `markAsFulfilled($statute, $adminId)`

**No database changes needed** - the polymorphic relationship handles all content types.

### Phase 3: Provision/Division Requests

For provision and division requests:

1. Request form needs additional fields:
   - `statute_id` (required) - which statute this belongs to
   - `parent_division_id` (optional) - if nested under a division
   - `parent_provision_id` (optional) - if nested under a provision

2. Validation already in place via `CreateContentRequestRequest`

3. Admin workflow:
   - View request with statute context
   - Create provision/division under correct statute
   - Link created provision/division to request

**Example Request:**
```json
{
  "type": "provision",
  "title": "Section 5: Definition of Murder",
  "statute_id": 123,
  "parent_division_id": 456,
  "additional_notes": "Missing from Part II of the Criminal Code"
}
```

### Phase 4: AI-Powered Duplicate Detection

Future enhancement: Use AI to detect similar requests even if titles don't match exactly.

**Implementation:**
1. Add `similarity_score` column to track AI confidence
2. Create `SimilarityService` using OpenAI embeddings or similar
3. On request creation, check for semantic duplicates
4. Show admin "Possibly related requests" with similarity scores

**Example:**
```php
class SimilarityService
{
    public function findSimilarRequests(ContentRequest $request, float $threshold = 0.8): Collection
    {
        // Use vector embeddings to find semantically similar titles
        // Return requests above similarity threshold
    }
}
```

### Phase 5: User Request Voting

Allow multiple users to "upvote" existing requests to show demand:

**Database:**
```php
Schema::create('content_request_votes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('content_request_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();

    $table->unique(['content_request_id', 'user_id']);
});
```

**Model Method:**
```php
public function vote(User $user): bool
{
    return $this->votes()->create(['user_id' => $user->id]);
}

public function votes(): HasMany
{
    return $this->hasMany(ContentRequestVote::class);
}
```

**UI:** Show vote count, allow users to vote, sort by popularity.

### Phase 6: Request Comments/Discussion

Enable users and admins to discuss requests:

- Already supported via `Commentable` trait on ContentRequest model
- Add comment endpoints following existing Issue comment pattern
- Users can provide additional context
- Admins can ask for clarification

### Phase 7: Batch Fulfillment

Admin feature to fulfill multiple duplicate requests at once:

**Endpoint:** `POST /api/admin/content-requests/batch-fulfill`

**Request:**
```json
{
  "request_ids": [1, 5, 12],
  "created_content_type": "App\\Models\\CourtCase",
  "created_content_id": 5109
}
```

**Implementation:**
```php
public function batchFulfill(Request $request)
{
    $validated = $request->validate([
        'request_ids' => 'required|array',
        'request_ids.*' => 'exists:content_requests,id',
        'created_content_type' => 'required|string',
        'created_content_id' => 'required|integer',
    ]);

    $model = $validated['created_content_type'];
    $content = $model::find($validated['created_content_id']);

    foreach ($validated['request_ids'] as $requestId) {
        $contentRequest = ContentRequest::find($requestId);
        $contentRequest->markAsFulfilled($content, auth()->id());

        // Send email notification
        app(NotificationService::class)->sendContentRequestFulfilledEmail(
            $contentRequest->user,
            $contentRequest
        );
    }

    return ApiResponse::success(null, 'Requests fulfilled successfully');
}
```

---

## Implementation Checklist

### Phase 1: Cases Only (Initial Implementation)

- [ ] Create migration `create_content_requests_table.php`
- [ ] Run migration: `php artisan migrate`
- [ ] Create `ContentRequest` model
- [ ] Update `CourtCase` model with relationship
- [ ] Create `CreateContentRequestRequest` validation
- [ ] Create `AdminUpdateContentRequestRequest` validation
- [ ] Create `ContentRequestResource`
- [ ] Create `ContentRequestController`
- [ ] Create `AdminContentRequestController`
- [ ] Create 5 Mailable classes (ContentRequestCreated, etc.)
- [ ] Create 5 Blade email templates
- [ ] Update `NotificationService` with 4 new methods
- [ ] Add routes to `routes/api.php`
- [ ] Update `AdminCaseController@store` to accept `content_request_id`
- [ ] Update `CreateCaseRequest` validation
- [ ] Create model factory: `ContentRequestFactory`
- [ ] Write unit tests
- [ ] Write feature tests
- [ ] Test email sending manually
- [ ] Create user API documentation
- [ ] Create admin API documentation
- [ ] Update `.env` with `ADMIN_EMAILS` if not set
- [ ] Update frontend to add "Request Content" button
- [ ] Deploy to staging
- [ ] Manual QA testing
- [ ] Deploy to production


---



## Troubleshooting

### Common Issues

**1. Emails not sending**
- Check mail configuration in `.env`
- Verify queue is running: `php artisan queue:work`
- Check logs: `storage/logs/laravel.log`

**2. Polymorphic relationship not working**
- Ensure `created_content_type` is full class name with namespace
- Example: `App\Models\CourtCase` (not just `CourtCase`)

**3. User can't delete request**
- Only pending requests can be deleted
- Check `canBeDeletedByUser()` method

**4. Admin can't update request**
- Verify user has admin role: `hasAdminAccess()`
- Check role middleware on route

**5. Duplicate detection not working**
- Case-sensitive match - ensure using LIKE or ILIKE
- Check `getDuplicateCount()` query

---

## Performance Optimization

### Database Indexes

The migration includes these indexes:
- `user_id` - Fast user request lookups
- `status` - Fast filtering by status
- `type` - Fast filtering by type
- `created_content_type + created_content_id` - Fast polymorphic lookups
- `statute_id` - Fast statute relation lookups
- `created_at` - Fast sorting by date

### Query Optimization

Use eager loading to prevent N+1 queries:

```php
$requests = ContentRequest::with([
    'user',
    'createdContent',
    'statute',
    'fulfilledBy',
    'rejectedBy'
])->paginate(15);
```

### Caching

Consider caching statistics:

```php
public function stats()
{
    return Cache::remember('content_request_stats', 300, function () {
        return [
            'total' => ContentRequest::count(),
            'by_status' => [...],
            // ...
        ];
    });
}
```

---

## Security Considerations

1. **Input Sanitization:** All user input is stripped of HTML tags in `prepareForValidation()`

2. **Authorization:** Users can only view/delete their own requests via ownership checks

3. **Role-Based Access:** Admin endpoints protected by `role:admin,researcher,superadmin` middleware

4. **SQL Injection:** Using Eloquent ORM and parameterized queries prevents SQL injection

5. **XSS Protection:** Blade templates auto-escape output, `strip_tags()` on input

6. **Mass Assignment:** Only fillable fields allowed via `$fillable` array

7. **Rate Limiting:** Consider adding rate limiting to prevent spam requests

---

## Conclusion

This implementation provides a complete, production-ready Content Request System for Lawexa. The architecture is:

- **Scalable:** Supports multiple content types via polymorphic relationships
- **Extensible:** Easy to add statutes, provisions, divisions in future phases
- **Maintainable:** Follows Laravel best practices and existing codebase patterns
- **User-Friendly:** Clear email notifications and intuitive API
- **Admin-Friendly:** Comprehensive admin tools for managing requests
- **Secure:** Proper authorization, validation, and input sanitization
- **Performant:** Database indexes and query optimization
- **Well-Tested:** Unit and feature tests included

The system is ready for implementation starting with **Cases only** in Phase 1, with a clear path to extend to other content types in future phases.
