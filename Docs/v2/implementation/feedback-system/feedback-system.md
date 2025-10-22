# Feedback System Implementation Documentation

## Overview

The Feedback System allows authenticated users to submit feedback about content (cases, statutes, provisions, divisions, notes) or general pages, with support for image attachments (up to 4 images). Administrators can review, manage, and track feedback through a comprehensive management interface.

**Implementation Date:** October 22, 2025
**Version:** 1.0
**Status:** Completed

## System Architecture

### Components

1. **Database Layer**
   - `feedback` table - Stores feedback submissions
   - `feedback_images` table - Stores image references for feedback

2. **Model Layer**
   - `Feedback` - Main feedback model with relationships
   - `FeedbackImage` - Image model for S3-stored images

3. **Service Layer**
   - `FeedbackService` - Handles image uploads and feedback creation

4. **Controller Layer**
   - `FeedbackController` - User endpoints for submitting and viewing feedback
   - `AdminFeedbackController` - Admin endpoints for managing feedback

5. **Request Validation Layer**
   - `CreateFeedbackRequest` - Validates user feedback submissions
   - `UpdateFeedbackStatusRequest` - Validates admin status updates
   - `MoveFeedbackToIssuesRequest` - Validates moving feedback to issues

6. **Resource Layer**
   - `FeedbackResource` - API response formatting
   - `FeedbackImageResource` - Image data formatting

## Database Schema

### feedback Table

```sql
CREATE TABLE feedback (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    feedback_text TEXT NOT NULL,
    content_type VARCHAR(255) NULL,           -- Polymorphic type
    content_id BIGINT UNSIGNED NULL,           -- Polymorphic ID
    page VARCHAR(100) NULL,                    -- Page reference
    status ENUM('pending', 'under_review', 'resolved') DEFAULT 'pending',
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    moved_to_issues BOOLEAN DEFAULT FALSE,
    moved_by BIGINT UNSIGNED NULL,
    moved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_content (content_type, content_id),
    INDEX idx_moved_to_issues (moved_to_issues),
    INDEX idx_created_at (created_at)
);
```

### feedback_images Table

```sql
CREATE TABLE feedback_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feedback_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,         -- S3 path
    `order` TINYINT UNSIGNED DEFAULT 0,       -- Display order
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,

    INDEX idx_feedback_id (feedback_id),
    INDEX idx_order (`order`)
);
```

## Model Relationships

### Feedback Model

```php
class Feedback extends Model
{
    // Relationships
    public function user(): BelongsTo           // User who submitted
    public function content(): MorphTo          // Polymorphic content
    public function images(): HasMany           // Attached images
    public function resolvedBy(): BelongsTo     // Admin who resolved
    public function movedBy(): BelongsTo        // Admin who moved to issues

    // Scopes
    scopePending($query)
    scopeUnderReview($query)
    scopeResolved($query)
    scopeMovedToIssues($query)
    scopeForUser($query, int $userId)
    scopeOfContentType($query, string $contentType)
    scopeSearch($query, string $search)

    // Helper Methods
    isPending(): bool
    isUnderReview(): bool
    isResolved(): bool
    hasBeenMovedToIssues(): bool
    markAsUnderReview(): void
    markAsResolved(int $resolvedById): void
    moveToIssues(int $movedById): void
    canBeResolved(): bool
    canBeMovedToIssues(): bool
}
```

### FeedbackImage Model

```php
class FeedbackImage extends Model
{
    // Relationships
    public function feedback(): BelongsTo

    // Accessors
    getUrlAttribute(): string  // Returns full S3 URL
}
```

## Service Layer

### FeedbackService

**Purpose:** Handles feedback creation with image uploads to S3

**Key Methods:**

```php
class FeedbackService
{
    public function createFeedback(array $data, ?array $images = null): Feedback
    public function uploadFeedbackImages(Feedback $feedback, array $images): array
    private function uploadImageToS3(UploadedFile $image, int $feedbackId): string
    private function validateImage(UploadedFile $image): void
    private function cleanupUploadedImages(array $uploadedImages): void
    public function deleteFeedbackImages(Feedback $feedback): bool
    public function getMaxImages(): int
}
```

**Image Upload Flow:**

1. Validate feedback data
2. Create feedback record in database
3. Iterate through uploaded images
4. Validate each image (format, size, MIME type)
5. Upload to S3 with unique filename
6. Create FeedbackImage record
7. If any error occurs, cleanup and rollback

**S3 Storage Structure:**

```
feedback/
  └── {year}/
      └── {month}/
          └── {feedback_id}/
              ├── {uuid-1}.jpg
              ├── {uuid-2}.png
              └── {uuid-3}.jpg
```

## API Endpoints

### User Endpoints (Authenticated + Verified)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/feedback` | List user's feedback |
| POST | `/api/feedback` | Submit new feedback |
| GET | `/api/feedback/{id}` | View single feedback |

### Admin Endpoints (Admin Role)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/feedback` | List all feedback with stats |
| GET | `/api/admin/feedback/{id}` | View any feedback |
| PATCH | `/api/admin/feedback/{id}/status` | Update status |
| POST | `/api/admin/feedback/{id}/move-to-issues` | Move to issues |

## Request Validation

### CreateFeedbackRequest

```php
[
    'feedback_text' => ['required', 'string', 'min:10', 'max:5000'],
    'content_type' => ['nullable', 'string', Rule::in([...])],
    'content_id' => ['nullable', 'integer', 'required_with:content_type'],
    'page' => ['nullable', 'string', 'max:100'],
    'images' => ['nullable', 'array', 'max:4'],
    'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
]
```

### UpdateFeedbackStatusRequest

```php
[
    'status' => ['required', 'string', Rule::in(['pending', 'under_review', 'resolved'])],
]
```

## Status Workflow

```
┌─────────┐      ┌──────────────┐      ┌──────────┐
│ pending │─────→│ under_review │─────→│ resolved │
└────┬────┘      └──────┬───────┘      └──────────┘
     │                  │
     └──────────────────┘
```

**Status Transitions:**
- `pending` → `under_review` → `resolved`
- Statuses can move backward if needed
- Only `resolved` status records `resolved_by` and `resolved_at`

## Features

### 1. Polymorphic Content Association

Feedback can be attached to multiple content types:

```php
// Example: Feedback for a case
content_type: "App\Models\CourtCase"
content_id: 123

// Example: Feedback for a statute provision
content_type: "App\Models\StatuteProvision"
content_id: 456

// Example: General page feedback (no content)
content_type: NULL
content_id: NULL
```

### 2. Image Attachments

- **Max Images:** 4 per feedback
- **Formats:** JPEG, PNG, GIF, WebP
- **Max Size:** 5MB per image
- **Storage:** Amazon S3
- **Naming:** UUID-based for uniqueness
- **Organization:** By year/month/feedback_id

### 3. Move to Issues

Admins can flag critical feedback to be moved to issues tracking:

```php
$feedback->moveToIssues($adminId);

// Sets:
moved_to_issues = true
moved_by = $adminId
moved_at = now()
```

This feature allows:
- Prioritizing critical bugs/features
- Tracking which feedback needs development work
- Separating actionable items from general feedback

### 4. Resolution Tracking

When feedback is resolved:

```php
$feedback->markAsResolved($adminId);

// Sets:
status = 'resolved'
resolved_by = $adminId
resolved_at = now()
```

### 5. Statistics and Filtering

Admin index endpoint includes statistics:

```json
{
  "stats": {
    "total": 95,
    "pending": 42,
    "under_review": 18,
    "resolved": 35,
    "moved_to_issues": 12
  }
}
```

Filters available:
- Status
- Content type
- User ID
- Date range
- Moved to issues
- Search text

## Security Considerations

### Authentication & Authorization

1. **User Endpoints:**
   - Require `auth:sanctum` middleware
   - Require `verified` middleware for submission
   - Users can only view their own feedback

2. **Admin Endpoints:**
   - Require `role:admin,researcher,superadmin` middleware
   - Can view and manage all feedback

### Data Validation

1. **Input Sanitization:**
   - All inputs validated through Form Requests
   - File uploads validated for type and size
   - MIME type verification for images

2. **SQL Injection Prevention:**
   - Using Eloquent ORM
   - Parameterized queries
   - No raw SQL in feedback system

3. **XSS Prevention:**
   - API returns JSON only
   - Frontend responsible for sanitization

### File Upload Security

1. **Validation:**
   - MIME type checking
   - Extension validation
   - Size limits enforced
   - Maximum file count enforced

2. **Storage:**
   - Unique UUID filenames prevent conflicts
   - S3 ACL configured properly
   - Temporary signed URLs for access

3. **Cleanup:**
   - Failed uploads are cleaned up
   - Orphaned images handled in transaction rollback

## Performance Considerations

### Database Indexes

Indexes created for optimal query performance:

```sql
INDEX idx_user_id (user_id)              -- User's feedback lookup
INDEX idx_status (status)                -- Status filtering
INDEX idx_content (content_type, content_id)  -- Content-specific feedback
INDEX idx_moved_to_issues (moved_to_issues)   -- Issue tracking
INDEX idx_created_at (created_at)        -- Date sorting
```

### Eager Loading

Controllers use eager loading to prevent N+1 queries:

```php
Feedback::with(['user', 'images', 'content', 'resolvedBy', 'movedBy'])
```

### Pagination

All list endpoints use pagination:
- Default: 15 items per page
- Maximum: 100 items per page

### S3 Upload Optimization

- Direct uploads to S3 (not temporary storage)
- UUID-based naming for distribution
- Organized folder structure for performance

## Error Handling

### Transaction Management

Feedback creation uses database transactions:

```php
DB::beginTransaction();
try {
    $feedback = Feedback::create([...]);
    $this->uploadFeedbackImages($feedback, $images);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Cleanup any uploaded images
    throw $e;
}
```

### Image Upload Failures

If image upload fails during creation:
1. Transaction is rolled back
2. Database records are removed
3. Uploaded S3 images are deleted
4. Error is returned to user

### Common Error Scenarios

1. **Validation Errors (422):**
   - Invalid image format
   - Too many images
   - Missing required fields
   - Content type mismatch

2. **Authorization Errors (403):**
   - User trying to view another user's feedback
   - Non-admin accessing admin endpoints

3. **Not Found Errors (404):**
   - Feedback ID doesn't exist
   - Referenced content doesn't exist

## Testing Checklist

### User Flow Testing

- [ ] Create feedback without content reference
- [ ] Create feedback with content reference
- [ ] Create feedback with 1 image
- [ ] Create feedback with 4 images
- [ ] Try creating feedback with 5 images (should fail)
- [ ] Try uploading oversized image (should fail)
- [ ] View own feedback list
- [ ] View single feedback
- [ ] Try viewing another user's feedback (should fail)

### Admin Flow Testing

- [ ] View all feedback
- [ ] Filter by status
- [ ] Filter by content type
- [ ] Filter by date range
- [ ] Filter by user
- [ ] Search in feedback text
- [ ] View statistics
- [ ] Update feedback to under_review
- [ ] Update feedback to resolved
- [ ] Move feedback to issues
- [ ] Try moving same feedback twice (should fail)

### Edge Cases

- [ ] Create feedback with special characters
- [ ] Create feedback with maximum length text
- [ ] Create feedback with minimum length text
- [ ] Upload images in different formats
- [ ] Test concurrent feedback submissions
- [ ] Test S3 connection failure handling

## Files Created

### Migrations
- `2025_10_22_040358_create_feedback_table.php`
- `2025_10_22_040405_create_feedback_images_table.php`

### Models
- `app/Models/Feedback.php`
- `app/Models/FeedbackImage.php`

### Controllers
- `app/Http/Controllers/FeedbackController.php`
- `app/Http/Controllers/AdminFeedbackController.php`

### Requests
- `app/Http/Requests/CreateFeedbackRequest.php`
- `app/Http/Requests/UpdateFeedbackStatusRequest.php`
- `app/Http/Requests/MoveFeedbackToIssuesRequest.php`

### Resources
- `app/Http/Resources/FeedbackResource.php`
- `app/Http/Resources/FeedbackImageResource.php`

### Services
- `app/Services/FeedbackService.php`

### Routes
- User routes in `routes/api.php` (lines 355-360)
- Admin routes in `routes/api.php` (lines 506-512)

### Documentation
- `Docs/v2/user/feedback.md`
- `Docs/v2/admin/feedback.md`
- `Docs/v2/implementation/feedback-system/feedback-system.md`

## Future Enhancements

### Phase 2 Considerations

1. **Email Notifications:**
   - Notify users when feedback status changes
   - Notify admins of new feedback submissions
   - Daily digest for pending feedback

2. **Feedback Categories:**
   - Bug reports
   - Feature requests
   - Content errors
   - General feedback

3. **Priority Levels:**
   - Critical
   - High
   - Medium
   - Low

4. **Admin Responses:**
   - Add comments from admins to users
   - Internal notes for admin team
   - Resolution explanations

5. **Analytics Dashboard:**
   - Feedback trends over time
   - Most reported content
   - Average resolution time
   - User engagement metrics

6. **Batch Operations:**
   - Bulk status updates
   - Bulk move to issues
   - Export feedback reports

7. **Integration with Issues System:**
   - Automatically create issues from feedback
   - Link feedback to existing issues
   - Sync status between systems

## Maintenance Notes

### Regular Tasks

1. **Monitor Statistics:**
   - Check pending feedback count daily
   - Review resolution rates weekly
   - Analyze trends monthly

2. **S3 Storage:**
   - Monitor storage usage
   - Clean up orphaned images (if any)
   - Review access patterns

3. **Database:**
   - Monitor table sizes
   - Optimize indexes if needed
   - Archive old resolved feedback (future)

### Troubleshooting

**Issue: Images not uploading**
- Check S3 credentials in `.env`
- Verify bucket permissions
- Check image size limits
- Review validation errors

**Issue: Slow queries**
- Check if indexes are being used
- Review eager loading
- Consider pagination limits

**Issue: Users can't submit feedback**
- Verify email verification status
- Check authentication token
- Review validation rules

## Conclusion

The Feedback System provides a robust, secure, and scalable solution for collecting and managing user feedback. With support for image attachments, polymorphic content associations, and comprehensive admin management features, it enables effective communication between users and administrators while maintaining data integrity and security.
