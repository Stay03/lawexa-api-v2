# Direct S3 Upload API Documentation

## Overview

The Direct S3 Upload system allows clients to upload files directly to AWS S3 without passing through the server, eliminating file size limits and improving performance.

## Benefits

- **No file size limits** (S3 supports up to 5TB per file)
- **Faster uploads** (direct client → S3)
- **Reduced server load** (no memory/bandwidth usage)
- **Better scalability** for concurrent uploads
- **Lower server costs**

## Upload Flow

1. **Request Pre-signed URL**: Client requests upload URL with file metadata
2. **Generate URL**: Server validates request and generates S3 pre-signed URL  
3. **Direct Upload**: Client uploads file directly to S3 using the URL
4. **Mark Complete**: Client notifies server that upload completed
5. **Verification**: Server verifies upload and updates database

## API Endpoints

### 1. Get Upload Configuration

```http
GET /direct-upload/config
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "allowed_types": ["jpg", "jpeg", "png", "gif", "webp", "pdf", "doc", "docx", "txt", "rtf"],
    "max_file_size": 5368709120,
    "max_file_size_human": "5GB",
    "categories": ["general", "legal", "case", "document", "image"],
    "upload_url_expiration_minutes": 60
  }
}
```

### 2. Generate Pre-signed Upload URL

```http
POST /direct-upload/generate-url
Authorization: Bearer {token}
Content-Type: application/json

{
  "original_name": "document.pdf",
  "mime_type": "application/pdf",
  "size": 1048576,
  "category": "legal"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "upload_url": "https://bucket.s3.region.amazonaws.com/path/file.pdf?AWSAccessKeyId=...",
    "file_id": 123,
    "filename": "uuid.pdf",
    "expires_at": "2025-07-25T14:30:00Z",
    "instructions": {
      "method": "PUT",
      "headers": {
        "Content-Type": "application/pdf",
        "Content-Length": 1048576
      },
      "note": "Upload the file using a PUT request to the upload_url, then call the completion endpoint"
    }
  }
}
```

### 3. Upload File to S3

```http
PUT {upload_url}
Content-Type: {mime_type}
Content-Length: {size}

{file_binary_data}
```

**Response from S3:**
```xml
<Response>
  <Location>...</Location>
  <ETag>"etag-value"</ETag>
</Response>
```

### 4. Mark Upload as Completed

```http
POST /direct-upload/{fileId}/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "etag": "etag-from-s3-response",
  "metadata": {
    "custom_field": "value"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "file": {
      "id": 123,
      "original_name": "document.pdf",
      "filename": "uuid.pdf",
      "size": 1048576,
      "human_size": "1.00 MB",
      "mime_type": "application/pdf",
      "category": "legal",
      "upload_status": "completed",
      "url": "https://bucket.s3.region.amazonaws.com/path/file.pdf",
      "created_at": "2025-07-25T13:30:00Z"
    }
  }
}
```

### 5. Get Upload Status

```http
GET /direct-upload/{fileId}/status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "file": {
      "id": 123,
      "original_name": "document.pdf",
      "upload_status": "pending",
      "size": 0,
      "expected_size": 1048576,
      "created_at": "2025-07-25T13:30:00Z",
      "metadata": {
        "expected_size": 1048576,
        "initiated_at": "2025-07-25T13:30:00Z"
      }
    }
  }
}
```

### 6. Cancel Upload

```http
DELETE /direct-upload/{fileId}/cancel
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Upload cancelled successfully"
}
```

### 7. Get Pending Uploads

```http
GET /direct-upload/pending?per_page=10
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "files": [
      {
        "id": 123,
        "original_name": "document.pdf",
        "upload_status": "pending",
        "created_at": "2025-07-25T13:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 1
    }
  }
}
```

### 8. Mark Upload as Failed

```http
POST /direct-upload/{fileId}/failed
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Network error during upload"
}
```

## Admin Endpoints

### Cleanup Expired Uploads

```http
POST /admin/direct-upload/cleanup-expired
Authorization: Bearer {admin_token}
```

## Upload States

- **pending**: Upload URL generated, file not yet uploaded
- **completed**: File successfully uploaded and verified
- **failed**: Upload failed or was manually marked as failed

## Error Handling

All endpoints return standard API responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## File Size Limits

- **Direct uploads**: Up to 5GB (configurable)
- **Traditional uploads**: 10MB (server memory limited)

## Security

- Pre-signed URLs expire after 60 minutes
- User authentication required for all operations
- File type validation based on extension and MIME type
- Automatic cleanup of expired uploads

## Implementation Example (JavaScript)

```javascript
async function uploadFile(file) {
  // 1. Get upload URL
  const urlResponse = await fetch('/api/direct-upload/generate-url', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      original_name: file.name,
      mime_type: file.type,
      size: file.size,
      category: 'document'
    })
  });
  
  const { data } = await urlResponse.json();
  
  // 2. Upload to S3
  const uploadResponse = await fetch(data.upload_url, {
    method: 'PUT',
    headers: {
      'Content-Type': file.type,
      'Content-Length': file.size
    },
    body: file
  });
  
  const etag = uploadResponse.headers.get('ETag');
  
  // 3. Mark as completed
  await fetch(`/api/direct-upload/${data.file_id}/complete`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ etag })
  });
}
```

## S3 Webhook (Optional)

For automatic upload completion, configure S3 to send notifications:

```http
POST /webhooks/s3
Content-Type: application/json

{
  "Records": [{
    "eventSource": "aws:s3",
    "eventName": "ObjectCreated:Put",
    "s3": {
      "object": {
        "key": "uploads/legal/2025/07/uuid.pdf",
        "size": 1048576,
        "eTag": "etag-value"
      }
    }
  }]
}
```