# File Management API

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://127.0.0.1:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## User Roles & Permissions

### Role Hierarchy
- **user** - Regular user (limited file access)
- **admin** - Administrator (full file management)
- **researcher** - Research access (full file management)
- **superadmin** - Full system access (full file management)

### Access Matrix

| Action | User | Admin | Researcher | Superadmin |
|--------|------|-------|------------|------------|
| Upload files | ✅ | ✅ | ✅ | ✅ |
| View files | ❌ | ✅ | ✅ | ✅ |
| Download files | ❌ | ✅ | ✅ | ✅ |
| Delete files | ❌ | ✅ | ✅ | ✅ |
| File statistics | ❌ | ✅ | ✅ | ✅ |
| Cleanup operations | ❌ | ✅ | ❌ | ✅ |

### Routing Structure

**Admin Endpoints**: All file management operations use ID-based URLs for security
- `/api/admin/files/{id}` - Numeric IDs for admin operations

---

## File Upload

### Primary Upload Endpoint

**POST** `/upload` - The main file upload endpoint for all authenticated users. Files are uploaded directly to S3 storage and return immediately with completed file records and S3 URLs.

---

## Admin Endpoints

**Note:** Admin endpoints are for file management operations like viewing, downloading, and deleting files. For uploading files, use the primary `/upload` endpoint above.

### 1. Get Files List (Admin)

**GET** `/admin/files`

Retrieves a paginated list of files with filtering and search capabilities.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `category` | string | No | - | Filter by file category (general, case_reports) |
| `type` | string | No | - | Filter by file type (image, document) |
| `search` | string | No | - | Search in filename or original name |
| `fileable_type` | string | No | - | Filter by parent model type |
| `fileable_id` | integer | No | - | Filter by parent model ID |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /admin/files?category=case_reports&type=document&page=1&per_page=10
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Files retrieved successfully",
  "data": {
    "files": [
      {
        "id": 12,
        "name": "test-case-document.txt",
        "filename": "7c002749-23bd-4731-9696-85e085bcf9d6.txt",
        "size": 70,
        "human_size": "70 B",
        "mime_type": "text/plain",
        "extension": "txt",
        "category": "general",
        "url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/general/2025/07/7c002749-23bd-4731-9696-85e085bcf9d6.txt",
        "download_url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/general/2025/07/7c002749-23bd-4731-9696-85e085bcf9d6.txt",
        "is_image": false,
        "is_document": true,
        "disk": "s3",
        "metadata": {
          "upload_ip": "89.38.97.151",
          "upload_user_agent": "curl/8.2.1",
          "expected_size": 70,
          "initiated_at": "2025-07-25T17:29:22.280568Z",
          "completed_at": "2025-07-25T17:29:22.415800Z",
          "s3_etag": "0729e75c39d3426fe4cecaf983a29bc0"
        },
        "uploaded_by": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com"
        },
        "created_at": "2025-07-25T02:28:41.000000Z",
        "updated_at": "2025-07-25T02:28:41.000000Z"
      },
      {
        "id": 11,
        "name": "Receipt-2378-9662.pdf",
        "filename": "65de88f5-a47e-41d3-bdbb-5f17a0c21865.pdf",
        "size": 29692,
        "human_size": "29 KB",
        "mime_type": "application/pdf",
        "extension": "pdf",
        "category": "case_reports",
        "url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/case_reports/2025/07/65de88f5-a47e-41d3-bdbb-5f17a0c21865.pdf",
        "download_url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/case_reports/2025/07/65de88f5-a47e-41d3-bdbb-5f17a0c21865.pdf",
        "is_image": false,
        "is_document": true,
        "disk": "s3",
        "metadata": {
          "upload_ip": "89.38.97.151",
          "upload_user_agent": "curl/8.2.1",
          "expected_size": 29692,
          "initiated_at": "2025-07-25T17:17:48.024130Z",
          "completed_at": "2025-07-25T17:17:48.148049Z",
          "s3_etag": "c59f02240abd2b4299ee4301e551f119"
        },
        "attached_to": {
          "type": "App\\Models\\CourtCase",
          "id": 8
        },
        "uploaded_by": {
          "id": 2,
          "name": "Dr. Arturo Rogahn",
          "email": "Johnathon.Prohaska@hotmail.com"
        },
        "created_at": "2025-07-25T02:20:44.000000Z",
        "updated_at": "2025-07-25T02:20:44.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 12,
      "total": 12
    },
    "links": {
      "first": "http://127.0.0.1:8000/api/admin/files?page=1",
      "last": "http://127.0.0.1:8000/api/admin/files?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 2. Get Single File (Admin)

**GET** `/admin/files/{id}`

Retrieves detailed information about a specific file using its ID.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | File ID (numeric identifier) |

#### Example Request
```
GET /admin/files/12
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "File retrieved successfully",
  "data": {
    "file": {
      "id": 12,
      "name": "test-case-document.txt",
      "filename": "7c002749-23bd-4731-9696-85e085bcf9d6.txt",
      "size": 70,
      "human_size": "70 B",
      "mime_type": "text/plain",
      "extension": "txt",
      "category": "general",
      "url": "/storage/uploads/general/2025/07/7c002749-23bd-4731-9696-85e085bcf9d6.txt",
      "download_url": "/storage/uploads/general/2025/07/7c002749-23bd-4731-9696-85e085bcf9d6.txt",
      "is_image": false,
      "is_document": true,
      "disk": "local",
      "metadata": {
        "upload_ip": "127.0.0.1",
        "upload_user_agent": "curl/8.2.1"
      },
      "uploaded_by": {
        "id": 2,
        "name": "Dr. Arturo Rogahn",
        "email": "Johnathon.Prohaska@hotmail.com"
      },
      "created_at": "2025-07-25T02:28:41.000000Z",
      "updated_at": "2025-07-25T02:28:41.000000Z"
    }
  }
}
```

### 3. Upload Files

**POST** `/upload`

Primary endpoint for uploading files directly to S3 storage. Files are uploaded immediately and return completed records with S3 URLs.

#### Required Permissions
- Any authenticated user (all roles)

#### Request Body (Multipart Form Data)

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `files[]` | file array | Yes | See validation rules | Multiple file upload (max 10) |
| `category` | string | No | general, legal, case, document, image, case_reports | File category (default: general) |

#### File Validation Rules
- **Max size:** 100MB per file
- **Max files:** 10 files per request
- **Allowed image types:** jpg, jpeg, png, gif, webp
- **Allowed document types:** pdf, doc, docx, txt, rtf
- **Storage:** Direct upload to Amazon S3

#### Example Request (Single File)
```bash
curl -X POST "/upload" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "files[]=@document.pdf" \
  -F "category=case_reports"
```

#### Example Request (Multiple Files)
```bash
curl -X POST "/upload" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "files[]=@document1.pdf" \
  -F "files[]=@document2.docx" \
  -F "category=document"
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Files uploaded successfully",
  "data": {
    "files": [
      {
        "id": 24,
        "original_name": "test-document.pdf",
        "filename": "e8ecf23d-6da2-45e1-a78e-66caf629aa74.pdf",
        "size": 2048576,
        "human_size": "2 MB",
        "mime_type": "application/pdf",
        "category": "case_reports",
        "upload_status": "completed",
        "url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/case_reports/2025/07/e8ecf23d-6da2-45e1-a78e-66caf629aa74.pdf",
        "created_at": "2025-07-25T17:26:51.000000Z"
      }
    ],
    "uploaded_count": 1,
    "failed_count": 0
  }
}
```

#### Error Response with Partial Success
```json
{
  "status": "success",
  "message": "Files uploaded successfully",
  "data": {
    "files": [
      {
        "id": 25,
        "original_name": "valid-document.pdf",
        "filename": "a1b2c3d4-5e6f-7890-abcd-ef1234567890.pdf",
        "size": 1024768,
        "human_size": "1000 KB",
        "mime_type": "application/pdf",
        "category": "general",
        "upload_status": "completed",
        "url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/general/2025/07/a1b2c3d4-5e6f-7890-abcd-ef1234567890.pdf",
        "created_at": "2025-07-25T17:28:15.000000Z"
      }
    ],
    "uploaded_count": 1,
    "failed_count": 1,
    "errors": [
      {
        "filename": "invalid-file.exe",
        "error": "File type not allowed. Allowed types: jpg, jpeg, png, gif, webp, pdf, doc, docx, txt, rtf"
      }
    ]
  }
}
```

### 4. Download File (Admin)

**GET** `/admin/files/{id}/download`

Generates a temporary signed download URL for accessing a specific file stored in S3.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | File ID to download |

#### Example Request
```bash
curl -X GET "/admin/files/7/download" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Success",
  "data": {
    "download_url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/general/2025/07/a5e5b9fb-1e64-4d70-8f1f-ffc6e5096fd6.txt?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVIJPT2UGT6Y6E2FQ%2F20250725%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20250725T182323Z&X-Amz-SignedHeaders=host&X-Amz-Expires=3600&X-Amz-Signature=621fd12ce02ed1df0174ed2ffc489e507d847cbf5a1a1276b4398cf63193a9fe",
    "filename": "test-primary-upload.txt",
    "size": 55,
    "mime_type": "text/plain"
  }
}
```

**Note:** The `download_url` is a temporary signed URL that expires in 1 hour (3600 seconds). Use this URL directly in your browser or application to download the file content.

#### Using the Download URL
Once you have the `download_url`, you can access the file content directly:

```bash
# Download the file content using the signed URL
curl -o downloaded-file.txt "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/general/2025/07/filename.txt?[signed-parameters]"
```

The signed URL provides direct access to the file stored in S3 without requiring additional authentication.

### 5. Delete File (Admin)

**DELETE** `/admin/files/{id}`

Permanently deletes a file from the system and storage.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | File ID to delete |

#### Example Request
```
DELETE /admin/files/13
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "File deleted successfully",
  "data": null
}
```

### 6. Delete Multiple Files (Admin)

**POST** `/admin/files/delete-multiple`

Deletes multiple files in a single request.

#### Required Permissions
- admin, researcher, or superadmin

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file_ids` | integer array | Yes | Array of file IDs to delete |

#### Example Request
```json
{
  "file_ids": [1, 2, 3, 4]
}
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Files deleted successfully",
  "data": {
    "deleted_count": 4,
    "failed_count": 0
  }
}
```

### 7. Get File Statistics (Admin)

**GET** `/admin/files/stats`

Retrieves comprehensive statistics about files in the system.

#### Required Permissions
- admin, researcher, or superadmin

#### Example Request
```
GET /admin/files/stats
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Success",
  "data": {
    "total_files": 13,
    "total_size": 10210985,
    "by_category": {
      "case_reports": {
        "category": "case_reports",
        "count": 9,
        "total_size": 5153758
      },
      "general": {
        "category": "general",
        "count": 4,
        "total_size": 5057227
      }
    },
    "by_type": {
      "images": 6,
      "documents": 7
    },
    "by_disk": {
      "local": {
        "disk": "local",
        "count": 13,
        "total_size": 10210985
      }
    }
  }
}
```

### 8. Cleanup Orphaned Files (Admin)

**POST** `/admin/files/cleanup`

Removes orphaned files that are not attached to any model.

#### Required Permissions
- admin or superadmin (restricted)

#### Example Request
```
POST /admin/files/cleanup
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Cleanup completed successfully",
  "data": {
    "cleaned_files": 5,
    "total_size_freed": "2.5 MB"
  }
}
```

#### Error Responses (All Endpoints)

**401 Unauthenticated**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

**403 Unauthorized**
```json
{
  "status": "error",
  "message": "Access denied",
  "data": null
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "File not found",
  "data": null
}
```

**422 Validation Error**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "file": ["The file field is required"],
    "files.0": ["The file size cannot exceed 10MB"]
  }
}
```

---

## Data Models

### File Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique file identifier |
| `original_name` | string | No | Original filename |
| `filename` | string | No | Stored filename (UUID-based) |
| `size` | integer | No | File size in bytes |
| `human_size` | string | No | Human-readable file size |
| `mime_type` | string | No | File MIME type |
| `extension` | string | Yes | File extension |
| `category` | string | No | File category (general, legal, case, document, image, case_reports) |
| `upload_status` | string | No | Upload status (completed, pending, failed) |
| `url` | string | No | S3 file access URL |
| `download_url` | string | Yes | Direct download URL (deprecated - admin only) |
| `is_image` | boolean | Yes | Whether file is an image |
| `is_document` | boolean | Yes | Whether file is a document |
| `disk` | string | No | Storage disk (s3 for new uploads) |
| `metadata` | object | Yes | File metadata (IP, user agent, S3 info) |
| `width` | integer | Yes | Image width (images only) |
| `height` | integer | Yes | Image height (images only) |
| `attached_to` | object | Yes | Parent model information |
| `uploaded_by` | object | Yes | User who uploaded the file |
| `created_at` | string | No | ISO timestamp of creation |
| `updated_at` | string | No | ISO timestamp of last update |

### Uploaded By Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Uploader user ID |
| `name` | string | No | Uploader name |
| `email` | string | No | Uploader email |

### Attached To Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `type` | string | No | Parent model class name |
| `id` | integer | No | Parent model ID |

### File Metadata Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `upload_ip` | string | Yes | IP address of uploader |
| `upload_user_agent` | string | Yes | User agent of uploader |
| `expected_size` | integer | Yes | Expected file size during upload |
| `initiated_at` | string | Yes | ISO timestamp when upload was initiated |
| `completed_at` | string | Yes | ISO timestamp when upload was completed |
| `s3_etag` | string | Yes | S3 ETag for file verification |
| `width` | integer | Yes | Image width (images only) |
| `height` | integer | Yes | Image height (images only) |

### File Statistics Object

| Field | Type | Description |
|-------|------|-------------|
| `total_files` | integer | Total number of files |
| `total_size` | integer | Total size of all files in bytes |
| `by_category` | object | Statistics grouped by category |
| `by_type` | object | Statistics grouped by file type |
| `by_disk` | object | Statistics grouped by storage disk |

### Pagination Meta Object

| Field | Type | Description |
|-------|------|-------------|
| `current_page` | integer | Current page number |
| `from` | integer | Starting record number |
| `last_page` | integer | Last page number |
| `per_page` | integer | Records per page |
| `to` | integer | Ending record number |
| `total` | integer | Total number of records |

### Pagination Links Object

| Field | Type | Description |
|-------|------|-------------|
| `first` | string | URL to first page |
| `last` | string | URL to last page |
| `prev` | string\|null | URL to previous page |
| `next` | string\|null | URL to next page |

---

## File Categories

### Available Categories

| Category | Description | Use Case |
|----------|-------------|----------|
| `general` | General file uploads | Standalone files, user uploads |
| `legal` | Legal documents | Legal forms, contracts, agreements |
| `case` | Case-related files | General case files |
| `document` | Document files | Reports, presentations, documents |
| `image` | Image files | Photos, screenshots, diagrams |
| `case_reports` | Legal case documents | Case attachments, legal case reports |

### Category-based S3 Storage

Files are organized in Amazon S3 by category:
```
s3://bucket-name/uploads/{category}/{year}/{month}/{uuid}.{extension}
```

Examples:
- `uploads/general/2025/07/a1b2c3d4-5e6f-7890-abcd-ef1234567890.pdf`
- `uploads/case_reports/2025/07/e8ecf23d-6da2-45e1-a78e-66caf629aa74.docx`
- `uploads/image/2025/07/f9g8h7i6-j5k4-l3m2-n1o0-p9q8r7s6t5u4.jpg`

---

## Common Use Cases

### Upload Case Documents
```bash
curl -X POST "/upload" \
  -H "Authorization: Bearer {token}" \
  -F "files[]=@case-report.pdf" \
  -F "files[]=@evidence.jpg" \
  -F "category=case_reports"
```

### Search Files by Name
```
GET /admin/files?search=invoice
```

### Filter by Category
```
GET /admin/files?category=case_reports
```

### Filter by File Type
```
GET /admin/files?type=document
GET /admin/files?type=image
```

### Filter Files by Parent Model
```
GET /admin/files?fileable_type=App\Models\CourtCase&fileable_id=123
```

### Combined Filters
```
GET /admin/files?category=case_reports&type=document&search=report&page=2&per_page=25
```

### Get Storage Statistics
```
GET /admin/files/stats
```

### Bulk Delete Files
```json
POST /admin/files/delete-multiple
{
  "file_ids": [1, 2, 3, 4]
}
```

---

## File Upload Integration

### Upload Files with Case Creation
When creating a case, files can be attached directly using the admin case endpoint:

```bash
curl -X POST "/admin/cases" \
  -H "Authorization: Bearer {token}" \
  -F "title=New Legal Case" \
  -F "body=Case description" \
  -F "files[]=@case-document.pdf" \
  -F "files[]=@evidence.jpg"
```

The uploaded files will automatically:
- Be uploaded to S3 directly
- Be categorized as `case_reports`
- Be linked to the created case via polymorphic relationship
- Include uploader information (`uploaded_by`)
- Have `upload_status` set to `completed`

### Standalone File Upload
For general file uploads not attached to specific models:

```bash
curl -X POST "/upload" \
  -H "Authorization: Bearer {token}" \
  -F "files[]=@document.pdf" \
  -F "files[]=@image.jpg" \
  -F "category=general"
```

Response includes immediate S3 URLs for the uploaded files.

---

## Security Features

### File Validation
- **Size limits:** Maximum 100MB per file
- **Type restrictions:** Only allowed file types can be uploaded
- **MIME type validation:** Files are validated by both extension and MIME type
- **Category validation:** Files must specify valid category
- **Virus scanning:** Files are scanned for malicious content (if configured)

### Storage Security
- **S3 Private Storage:** Files stored in private S3 buckets
- **UUID filenames:** Original filenames are replaced with UUIDs
- **Signed URLs:** Access via temporary signed URLs for security
- **Encryption:** Files encrypted at rest in S3
- **Access control:** All file access requires authentication

### Audit Trail
- **Upload tracking:** Every file tracks who uploaded it
- **Metadata logging:** IP address and user agent are logged
- **Soft deletes:** Files are soft-deleted by default for recovery
- **Storage verification:** System can verify if files exist in storage

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | File uploaded successfully |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | File not found |
| 422 | Validation error (file too large, invalid type, etc.) |
| 500 | Server error |

---

## Notes

### Automatic Features
- **UUID generation:** Files are automatically renamed with UUIDs for security
- **Directory organization:** Files are organized by category, year, and month
- **Metadata extraction:** Image dimensions and upload context are automatically captured
- **Human-readable sizes:** File sizes are automatically converted to human-readable format

### File Lifecycle
1. **Upload:** File is validated, stored, and metadata is extracted
2. **Processing:** File is linked to parent models if specified
3. **Access:** Files are accessed via secure download URLs
4. **Deletion:** Files are soft-deleted and can be cleaned up periodically

### Storage Options
- **S3 Storage:** Primary storage on Amazon S3 with direct upload
- **Local Storage:** Legacy files may exist on server filesystem (admin managed)
- **Direct Upload:** New files upload directly to S3 without server processing

### Performance
- **Pagination:** All file lists are paginated for performance
- **Eager loading:** Related models (uploaded_by) are efficiently loaded
- **Indexing:** Database indexes on frequently queried fields
- **Caching:** File metadata is cached for quick access