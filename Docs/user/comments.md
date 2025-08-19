# User Comments API - Complete Guide

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://localhost:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## User Permissions

Regular users have **comment and interaction access** on published content. This includes:
- ✅ Create comments on Issues and Notes
- ✅ View all approved comments and replies
- ✅ Edit and delete their own comments
- ✅ Create nested replies to comments
- ✅ View comment counts in parent resources
- ❌ Access admin comment moderation features
- ❌ Approve/reject comments
- ❌ View unapproved comments (except their own)

---

## System Overview

The User Comments API provides **comprehensive commenting functionality** with support for polymorphic relationships and nested threading. Users can comment on different content types (Issues, Notes) and create threaded discussions with unlimited reply depth.

### Core Comment Features

1. **Polymorphic Comments**: Comment on Issues, Notes, and future content types
2. **Nested Threading**: Unlimited reply depth with parent-child relationships
3. **Real-time Counts**: Automatic comment count updates in parent resources
4. **Edit Tracking**: Transparent edit history with timestamps
5. **Content Validation**: Security and length validation
6. **Soft Deletion**: Comments are soft-deleted preserving thread integrity
7. **User Ownership**: Users can only edit/delete their own comments

---

## Available Comment Targets

Currently, users can comment on:
- **Issues** (`Issue`) - Bug reports, feature requests, support tickets
- **Notes** (`Note`) - User-created notes and documentation

Future support planned for:
- **Blog Posts** - When blog functionality is added
- **Legal Documents** - Annotations on statutes and provisions

---

## Complete Endpoint Documentation

### 1. List Comments

**GET** `/comments`

**Purpose**: Retrieve comments for a specific commentable resource (Issue or Note).

**Required Query Parameters:**
- `commentable_type` (required): The model type - supports both short format (`Issue` or `Note`) and full class format (`App\Models\Issue` or `App\Models\Note`)
- `commentable_id` (required): The ID of the resource to get comments for

**Optional Query Parameters:**
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number (default: 1)

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/comments?commentable_type=App%5CModels%5CIssue&commentable_id=45"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Comments retrieved successfully",
  "data": {
    "comments": {
      "data": [
        {
          "id": 1,
          "content": "Test comment",
          "is_approved": true,
          "is_edited": false,
          "edited_at": null,
          "created_at": "2025-08-19T02:56:37.000000Z",
          "updated_at": "2025-08-19T02:56:37.000000Z",
          "user": {
            "id": 82,
            "name": "Chidere",
            "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
          },
          "parent_id": null,
          "replies_count": 1,
          "replies": [
            {
              "id": 5,
              "content": "First level reply to original comment",
              "is_approved": true,
              "is_edited": false,
              "edited_at": null,
              "created_at": "2025-08-19T03:00:41.000000Z",
              "updated_at": "2025-08-19T03:00:41.000000Z",
              "user": {
                "id": 82,
                "name": "Chidere",
                "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
              },
              "parent_id": 1
            }
          ]
        },
        {
          "id": 3,
          "content": "Updated comment: This is now an edited version of my original comment!",
          "is_approved": true,
          "is_edited": true,
          "edited_at": "2025-08-19T02:58:13.000000Z",
          "created_at": "2025-08-19T02:57:48.000000Z",
          "updated_at": "2025-08-19T02:58:13.000000Z",
          "user": {
            "id": 82,
            "name": "Chidere",
            "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
          },
          "parent_id": null,
          "replies_count": 0,
          "replies": []
        }
      ],
      "meta": {
        "total": 2,
        "per_page": 15,
        "current_page": 1,
        "last_page": 1,
        "from": 1,
        "to": 2
      },
      "links": {
        "first": "https://rest.lawexa.com/api/comments?page=1",
        "last": "https://rest.lawexa.com/api/comments?page=1",
        "prev": null,
        "next": null
      }
    }
  }
}
```

---

### 2. Create Comment

**POST** `/comments`

**Purpose**: Create a new comment on an Issue or Note, or reply to an existing comment.

**Content-Type**: Both `application/json` and `application/x-www-form-urlencoded` are fully supported

**Required Parameters:**
- `content` (required): Comment text (1-2000 characters)
- `commentable_type` (required): Must be `Issue` or `Note`
- `commentable_id` (required): Valid ID of the target resource

**Optional Parameters:**
- `parent_id` (optional): ID of parent comment to create a reply

**Example Request (New Comment on Issue):**
```bash
curl -X POST \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d 'content=This is a test comment on the issue&commentable_type=Issue&commentable_id=45' \
     "https://rest.lawexa.com/api/comments"
```

**Example Request (Reply to Comment):**
```bash
curl -X POST \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d 'content=This is a reply to the original comment&commentable_type=Issue&commentable_id=45&parent_id=1' \
     "https://rest.lawexa.com/api/comments"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Comment created successfully",
  "data": {
    "comment": {
      "id": 1,
      "content": "Test comment",
      "is_approved": true,
      "is_edited": null,
      "edited_at": null,
      "created_at": "2025-08-19T02:56:37.000000Z",
      "updated_at": "2025-08-19T02:56:37.000000Z",
      "user": {
        "id": 82,
        "name": "Chidere",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
      },
      "parent_id": null,
      "replies_count": 0,
      "replies": []
    }
  }
}
```

---

### 3. Reply to Comment (Alternative Endpoint)

**POST** `/comments/{id}/reply`

**Purpose**: Create a reply to an existing comment using a dedicated endpoint that automatically inherits commentable information.

**Content-Type**: Both `application/json` and `application/x-www-form-urlencoded` are fully supported

**Required Parameters:**
- `content` (required): Reply text (1-2000 characters)

**Path Parameters:**
- `{id}`: ID of the parent comment to reply to

**Example Request:**
```bash
curl -X POST \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"content":"This is a reply using the dedicated endpoint"}' \
     "https://rest.lawexa.com/api/comments/8/reply"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Reply created successfully",
  "data": {
    "comment": {
      "id": 10,
      "content": "This is a reply using the dedicated endpoint",
      "is_approved": true,
      "is_edited": null,
      "edited_at": null,
      "created_at": "2025-08-19T03:30:00.000000Z",
      "updated_at": "2025-08-19T03:30:00.000000Z",
      "user": {
        "id": 82,
        "name": "Chidere",
        "avatar": "https://..."
      },
      "parent_id": 8,
      "replies_count": 0,
      "replies": []
    }
  }
}
```

**Advantages of this endpoint:**
- ✅ **Simpler**: No need to specify `commentable_type` or `commentable_id`
- ✅ **Automatic inheritance**: Inherits parent comment's target resource
- ✅ **Type safety**: Validates parent comment exists and is approved

---

### 4. Show Specific Comment

**GET** `/comments/{id}`

**Purpose**: Retrieve detailed information about a specific comment including its replies.

**Parameters:**
- `id` (required): Comment ID

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/comments/1"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Comment retrieved successfully",
  "data": {
    "comment": {
      "id": 1,
      "content": "Test comment",
      "is_approved": true,
      "is_edited": false,
      "edited_at": null,
      "created_at": "2025-08-19T02:56:37.000000Z",
      "updated_at": "2025-08-19T02:56:37.000000Z",
      "user": {
        "id": 82,
        "name": "Chidere",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
      },
      "parent_id": null,
      "replies_count": 1,
      "replies": [
        {
          "id": 5,
          "content": "First level reply to original comment",
          "is_approved": true,
          "is_edited": false,
          "edited_at": null,
          "created_at": "2025-08-19T03:00:41.000000Z",
          "updated_at": "2025-08-19T03:00:41.000000Z",
          "user": {
            "id": 82,
            "name": "Chidere",
            "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
          },
          "parent_id": 1
        }
      ]
    }
  }
}
```

---

### 5. Update Comment

**PUT** `/comments/{id}`

**Purpose**: Update the content of your own comment. Automatically tracks edit history.

**Content-Type**: Both `application/json` and `application/x-www-form-urlencoded` are fully supported

**Parameters:**
- `id` (required): Comment ID (must be owned by authenticated user)
- `content` (required): Updated comment text (1-2000 characters)

**Example Request:**
```bash
curl -X PUT \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d 'content=Updated comment: This is now an edited version of my original comment!' \
     "https://rest.lawexa.com/api/comments/3"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Comment updated successfully",
  "data": {
    "comment": {
      "id": 3,
      "content": "Updated comment: This is now an edited version of my original comment!",
      "is_approved": true,
      "is_edited": true,
      "edited_at": "2025-08-19T02:58:13.000000Z",
      "created_at": "2025-08-19T02:57:48.000000Z",
      "updated_at": "2025-08-19T02:58:13.000000Z",
      "user": {
        "id": 82,
        "name": "Chidere",
        "avatar": "https://lh3.googleusercontent.com/a/ACg8ocLDCrho_CWhPuWncLE1WLgXVxRRiRUoXY0Jh3Qj88YB_CAdQg=s96-c"
      },
      "parent_id": null,
      "replies_count": 0,
      "replies": []
    }
  }
}
```

**Notes:**
- Only the comment owner can update their comments
- Original content is replaced with new content
- `is_edited` flag is automatically set to `true`
- `edited_at` timestamp is automatically set

---

### 6. Delete Comment

**DELETE** `/comments/{id}`

**Purpose**: Soft delete your own comment. Preserves thread integrity for replies.

**Parameters:**
- `id` (required): Comment ID (must be owned by authenticated user)

**Example Request:**
```bash
curl -X DELETE \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/comments/4"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Comment deleted successfully",
  "data": null
}
```

**Notes:**
- Only the comment owner can delete their comments
- Comments are soft-deleted (not permanently removed)
- Thread structure is preserved for existing replies
- Comment counts in parent resources are automatically updated

---

## Comment Counts in Parent Resources

Comments automatically update counts in their parent resources (Issues and Notes).

### Issue with Comments
```json
{
  "id": 45,
  "title": "Comments System Test Issue",
  "description": "This is a test issue for testing the comments functionality",
  "comments_count": 4,
  "created_at": "2025-08-19T02:56:03.000000Z",
  "updated_at": "2025-08-19T02:56:03.000000Z"
}
```

### Note with Comments
```json
{
  "id": 10,
  "title": "Comments System Test Note",
  "content": "This is a test note for testing the comments functionality",
  "comments_count": 1,
  "created_at": "2025-08-19T02:56:12.000000Z",
  "updated_at": "2025-08-19T02:56:12.000000Z"
}
```

---

## Threading and Nested Replies

### Creating Nested Replies

To create a reply to an existing comment, include the `parent_id` parameter:

```bash
# Reply to comment #1
curl -X POST \
     -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d 'content=This is a reply&commentable_type=Issue&commentable_id=45&parent_id=1' \
     "https://rest.lawexa.com/api/comments"
```

### Multi-Level Threading

Comments support unlimited nesting depth:

```
Comment #1 (root)
├── Reply #5 (level 1)
    └── Reply #6 (level 2)
        └── Reply #7 (level 3)
            └── ... (unlimited depth)
```

### Thread Display Structure

When listing comments, only root comments are returned at the top level, with replies nested in the `replies` array:

- **Root comments**: `parent_id` is `null`
- **Replies**: `parent_id` contains the parent comment ID
- **Reply counts**: `replies_count` shows direct children only
- **Nested loading**: Replies are loaded with user information

---

## Validation Rules

### Content Validation
- **Required**: Comment content cannot be empty
- **Length**: Minimum 1 character, maximum 2000 characters
- **Type**: Must be a string

### Commentable Validation
- **Type**: Must be exactly `Issue` or `Note`
- **ID**: Must be a valid integer referencing an existing resource
- **Existence**: The target Issue or Note must exist and be accessible

### Reply Validation
- **Parent ID**: If provided, must reference an existing, approved comment
- **Same target**: Reply must target the same commentable resource as parent

### Security Validation
- **Authentication**: All endpoints require valid Bearer token
- **Ownership**: Users can only edit/delete their own comments
- **Content types**: Only whitelisted models can be commented on

---

## Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

#### 403 Forbidden
```json
{
  "status": "error",
  "message": "Unauthorized",
  "data": null
}
```

#### 404 Not Found
```json
{
  "status": "error",
  "message": "Comment not found",
  "data": null
}
```

#### 422 Validation Error
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "content": ["Comment content is required."],
    "commentable_type": ["Invalid commentable type. Must be Issue or Note."],
    "commentable_id": ["Commentable ID is required."],
    "parent_id": ["Parent comment does not exist."]
  }
}
```

### Specific Error Scenarios

#### Invalid Commentable Type (Security)
```bash
# Attempt to comment on User model (blocked)
curl -X POST \
     -d 'content=Malicious comment&commentable_type=User&commentable_id=1'
```
**Response:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "commentable_type": ["Invalid commentable type. Must be Issue or Note."]
  }
}
```

#### Non-existent Resource
```bash
# Comment on non-existent Issue
curl -X POST \
     -d 'content=Comment on missing&commentable_type=Issue&commentable_id=999999'
```
**Response:**
```json
{
  "status": "error",
  "message": "Commentable resource not found",
  "data": null,
  "errors": 404
}
```

#### Content Too Long
```bash
# Content over 2000 characters
curl -X POST \
     -d 'content=[2001 characters]&commentable_type=Issue&commentable_id=45'
```
**Response:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "content": ["Comment cannot exceed 2000 characters."]
  }
}
```

#### Invalid Parent Comment
```bash
# Reply to non-existent comment
curl -X POST \
     -d 'content=Reply to nothing&commentable_type=Issue&commentable_id=45&parent_id=999999'
```
**Response:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "parent_id": ["Parent comment does not exist."]
  }
}
```

---

## Implementation Notes

### Data Format Considerations
- **Form encoding recommended**: Use `application/x-www-form-urlencoded` for better compatibility
- **JSON support**: `application/json` is supported but may have parsing edge cases
- **Character encoding**: UTF-8 is fully supported for international characters

### Commentable Type Format
- **Query parameters**: Use URL-encoded format: `App%5CModels%5CIssue` (for `App\Models\Issue`)
- **Form data**: Use exact format: `Issue` or `Note`
- **Case sensitivity**: Type names are case-sensitive

### Performance Considerations
- **Pagination**: All listing endpoints support pagination (default 15 items)
- **Eager loading**: User data and replies are automatically loaded
- **Caching**: Comment counts are cached and updated automatically
- **Indexing**: Database indexes optimize query performance

### Real-time Updates
- **Comment counts**: Automatically updated in parent resources
- **Thread integrity**: Maintained during deletions and updates
- **Edit tracking**: Transparent edit history without version storage

### Frontend Integration Tips
- **Threading display**: Use `parent_id` to build nested UI structures
- **Edit indicators**: Show edit status using `is_edited` and `edited_at`
- **User permissions**: Check comment ownership before showing edit/delete buttons
- **Real-time counts**: Refresh parent resource to get updated comment counts
- **Form validation**: Implement client-side validation matching server rules

---

## Query Parameter Reference

### List Comments Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `commentable_type` | string | Yes | Short format: `Issue`, `Note` OR Full format: `App\Models\Issue`, `App\Models\Note` |
| `commentable_id` | integer | Yes | Valid resource ID |
| `per_page` | integer | No | Items per page (1-100, default: 15) |
| `page` | integer | No | Page number (default: 1) |

### Create Comment Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `content` | string | Yes | Comment text (1-2000 chars) |
| `commentable_type` | string | Yes | `Issue` or `Note` |
| `commentable_id` | integer | Yes | Valid resource ID |
| `parent_id` | integer | No | Parent comment ID for replies |

### Update Comment Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `content` | string | Yes | Updated comment text (1-2000 chars) |

---

## Example Usage Workflows

### Basic Comment Flow
1. **List issues**: `GET /issues` to find an issue to comment on
2. **Create comment**: `POST /comments` with issue details
3. **View comments**: `GET /comments?commentable_type=App%5CModels%5CIssue&commentable_id=45`
4. **Update comment**: `PUT /comments/1` to edit your comment
5. **Check counts**: `GET /issues/45` to see updated comment count

### Discussion Thread Flow
1. **Create root comment**: Comment on an issue
2. **Reply to comment**: Use `parent_id` to create reply
3. **Nested replies**: Continue threading with deeper replies
4. **View thread**: `GET /comments/1` to see full thread structure
5. **Manage thread**: Edit or delete your contributions

### Cross-Model Commenting
1. **Comment on Issue**: `POST /comments` with `commentable_type=Issue`
2. **Comment on Note**: `POST /comments` with `commentable_type=Note`
3. **List all activity**: Check both resource types for complete user activity
4. **Unified counts**: Each resource type maintains its own comment counts

---

This documentation provides complete coverage of the Comments API based on comprehensive testing and real-world usage scenarios. All examples use actual request/response data from the production API.