# Case Management API - Admin Endpoints

## Overview
The Case Management Admin API provides comprehensive CRUD operations for legal case records. These endpoints are restricted to users with administrative privileges (admin, superadmin, or researcher roles).

## Base URL
```
https://rest.lawexa.com/api/admin
```
For local development:
```
http://localhost:8000/api/admin
```

## Authentication
All admin endpoints require authentication with appropriate role permissions.

### Required Roles
- **admin**: Full access to case management
- **superadmin**: Full access to case management  
- **researcher**: Full access to case management

### Authentication Headers
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

## Endpoints

### Get Admin Cases List
Retrieve a paginated list of all cases with administrative privileges and additional filtering options.

**Endpoint:** `GET /admin/cases`

**Access:** Admin, SuperAdmin, Researcher roles required

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in case title, body, court, or citation |
| `country` | string | No | - | Filter by country |
| `court` | string | No | - | Filter by court |
| `topic` | string | No | - | Filter by topic |
| `level` | string | No | - | Filter by academic level |
| `course` | string | No | - | Filter by course |
| `date_from` | date | No | - | Filter cases from this date (YYYY-MM-DD) |
| `date_to` | date | No | - | Filter cases to this date (YYYY-MM-DD) |
| `created_by` | integer | No | - | Filter by creator user ID |
| `per_page` | integer | No | 15 | Number of items per page (max 100) |
| `page` | integer | No | 1 | Page number |
| `include_similar_cases` | boolean | No | false | Include similar cases relationships |
| `include_cited_cases` | boolean | No | false | Include cited cases relationships |

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/cases?created_by=1&per_page=20" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 7186,
        "title": "Test Case - Admin Created",
        "body": "Case content...",
        "report": null,
        "course": null,
        "topic": "Administrative Law",
        "tag": null,
        "principles": "Legal principles...",
        "level": "500",
        "slug": "test-case-admin-created",
        "court": "Test Supreme Court",
        "date": "2024-01-15",
        "country": "Nigeria",
        "citation": "Test Citation (2024) TSC 001",
        "judges": "Hon. Justice Test Judge",
        "judicial_precedent": "Precedent information...",
        "case_report_text": null,
        "creator": {
          "id": 85,
          "name": "Test User"
        },
        "files": [],
        "files_count": 0,
        "views_count": 0,
        "similar_cases": [],
        "similar_cases_count": 0,
        "cited_cases": [],
        "cited_cases_count": 0,
        "created_at": "2025-08-26T16:02:57.000000Z",
        "updated_at": "2025-08-26T16:02:57.000000Z"
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### Create New Case
Create a new legal case record with comprehensive details.

**Endpoint:** `POST /admin/cases`

**Access:** Admin, SuperAdmin, Researcher roles required

**Request Body:**
```json
{
  "title": "Case Title (required)",
  "body": "Complete case description and details (required)",
  "report": "Additional report information",
  "course": "Course name",
  "topic": "Legal topic",
  "tag": "Comma-separated tags",
  "principles": "Legal principles established",
  "level": "Academic level (e.g., 300, 400, 500)",
  "court": "Court name",
  "date": "2024-01-15",
  "country": "Country name",
  "citation": "Legal citation",
  "judges": "Judge names",
  "judicial_precedent": "Precedent information",
  "case_report_text": "Additional case report text",
  "similar_case_ids": [123, 456],
  "cited_case_ids": [789, 012],
  "files": ["file attachments array"]
}
```

**Validation Rules:**
- `title`: Required, max 255 characters
- `body`: Required
- `date`: Must be valid date format (YYYY-MM-DD)
- `similar_case_ids`: Array, max 50 items, must exist in database
- `cited_case_ids`: Array, max 50 items, must exist in database
- `files`: Array, max 10 files, file size and type restrictions apply

**Example Request:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/cases" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Test Case Title",
    "body": "Comprehensive case description with legal details...",
    "court": "Supreme Court",
    "country": "Nigeria",
    "date": "2024-01-15",
    "citation": "Test Citation (2024) SC 001",
    "topic": "Constitutional Law",
    "level": "500",
    "judges": "Hon. Justice Test Judge",
    "principles": "Legal principles established by this case"
  }'
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Case created successfully",
  "data": {
    "case": {
      "id": 7186,
      "title": "Test Case Title",
      "body": "Comprehensive case description...",
      "report": null,
      "course": null,
      "topic": "Constitutional Law",
      "tag": null,
      "principles": "Legal principles established by this case",
      "level": "500",
      "slug": "test-case-title",
      "court": "Supreme Court",
      "date": "2024-01-15",
      "country": "Nigeria",
      "citation": "Test Citation (2024) SC 001",
      "judges": "Hon. Justice Test Judge",
      "judicial_precedent": null,
      "case_report_text": null,
      "creator": {
        "id": 85,
        "name": "Test User"
      },
      "files": [],
      "files_count": 0,
      "views_count": 0,
      "similar_cases": [],
      "similar_cases_count": 0,
      "cited_cases": [],
      "cited_cases_count": 0,
      "created_at": "2025-08-26T16:02:57.000000Z",
      "updated_at": "2025-08-26T16:02:57.000000Z"
    }
  }
}
```

### Get Single Case (Admin)
Retrieve detailed case information using case ID.

**Endpoint:** `GET /admin/cases/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Example Request:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/cases/7186" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 7186,
      "title": "Test Case Title",
      "body": "Case content...",
      "creator": {
        "id": 85,
        "name": "Test User"
      },
      "files": [],
      "caseReport": null,
      "similarCases": [],
      "casesWhereThisIsSimilar": [],
      "citedCases": [],
      "casesThatCiteThis": [],
      "created_at": "2025-08-26T16:02:57.000000Z",
      "updated_at": "2025-08-26T16:02:57.000000Z"
    }
  }
}
```

### Update Case
Update an existing case record.

**Endpoint:** `PUT /admin/cases/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Request Body:** Same as create request, but all fields are optional

**Example Request:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/cases/7186" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Updated Case Title",
    "topic": "Updated Legal Topic",
    "principles": "Updated legal principles"
  }'
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Case updated successfully",
  "data": {
    "case": {
      "id": 7186,
      "title": "Updated Case Title",
      "body": "Original case description...",
      "topic": "Updated Legal Topic",
      "principles": "Updated legal principles",
      "updated_at": "2025-08-26T16:05:30.000000Z"
    }
  }
}
```

### Delete Case
Delete a case record and its associated files.

**Endpoint:** `DELETE /admin/cases/{id}`

**Access:** Admin, SuperAdmin, Researcher roles required

**Example Request:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/cases/7186" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Case deleted successfully",
  "data": []
}
```

## Advanced Features

### Similar Cases Management
When creating or updating cases, you can establish relationships with similar cases:

```json
{
  "similar_case_ids": [5101, 5102, 5103]
}
```

**Notes:**
- Maximum 50 similar cases per case
- Relationships are bidirectional
- Case cannot be marked as similar to itself
- Invalid case IDs will be rejected

### Cited Cases Management
Establish citation relationships between cases:

```json
{
  "cited_case_ids": [5104, 5105]
}
```

**Notes:**
- Maximum 50 cited cases per case
- Relationships are bidirectional
- Case cannot cite itself
- Invalid case IDs will be rejected

### File Attachments
Cases support file attachments for documents, reports, and other materials:

**Supported File Types:**
- PDF documents
- Word documents (DOC, DOCX)
- Images (PNG, JPG, JPEG, GIF)
- Text files (TXT)

**File Size Limits:**
- Maximum file size varies based on system configuration
- Maximum 10 files per case
- Files are stored securely in cloud storage

### Case Reports
Extended case reports can be added via the `case_report_text` field:

```json
{
  "case_report_text": "Detailed analysis and commentary on the case..."
}
```

## Error Responses

### Authentication Errors
**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

### Authorization Errors
**403 Forbidden:**
```json
{
  "status": "error",
  "message": "Insufficient permissions. Admin access required.",
  "data": null
}
```

### Validation Errors
**422 Unprocessable Entity:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": {
    "errors": {
      "title": ["The title field is required."],
      "body": ["The body field is required."],
      "similar_case_ids.0": ["The selected similar case id is invalid."]
    }
  }
}
```

### Not Found Errors
**404 Not Found:**
```json
{
  "status": "error",
  "message": "Case not found",
  "data": null
}
```

### Server Errors
**500 Internal Server Error:**
```json
{
  "status": "error",
  "message": "Failed to create case: Database connection error",
  "data": null
}
```

## Data Management Best Practices

### Case Creation
- Always provide comprehensive title and body content
- Include proper legal citations when available
- Set appropriate academic level for educational content
- Tag cases with relevant keywords for searchability

### Relationship Management
- Establish similar case relationships for better content discovery
- Create citation networks for legal precedent tracking
- Regular review and update of case relationships

### File Management
- Upload relevant supporting documents
- Use descriptive file names
- Ensure file formats are accessible to users
- Regular cleanup of unused or outdated files

### Content Quality
- Maintain consistent formatting and style
- Include comprehensive legal principles
- Provide context for case significance
- Regular content review and updates

## Security Considerations

### Access Control
- All operations require appropriate role permissions
- User actions are logged for audit trails
- File uploads are scanned for security threats

### Data Validation
- Input sanitization prevents injection attacks
- File type and size validation
- Relationship integrity checks

### Audit Logging
- All CRUD operations are logged
- User attribution for all changes
- Change history for sensitive modifications

## Performance Optimization

### Efficient Queries
- Use pagination for large datasets
- Selective relationship loading
- Database query optimization

### Caching Strategies
- Frequently accessed cases are cached
- Search results caching
- File metadata caching

### Bulk Operations
- Consider implementing bulk import/export features
- Batch processing for large datasets
- Background job processing for intensive operations