# Statute API Testing - Insomnia/cURL Commands

## Authentication Setup
First, you'll need to authenticate and get a Bearer token. Use your existing auth endpoint:

```bash
# Login to get token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your-admin@example.com",
    "password": "your-password"
  }'
```

**Note**: Replace `localhost:8000` with your actual API URL. Use the token from the response in the `Authorization: Bearer {token}` header for all subsequent requests.

---

## 1. USER ROUTES (Slug-based, Read-only)

### 1.1 List All Statutes
```bash
curl -X GET "http://localhost:8000/api/statutes" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.2 List Statutes with Filters
```bash
curl -X GET "http://localhost:8000/api/statutes?search=company&country=Nigeria&jurisdiction=Federal&sector=Corporate&year=2020&per_page=10&sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.3 Get Single Statute (by slug)
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.4 Get Statute with Relationships
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020?include_related=true&include_amendments=true&include_citations=true" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.5 Get Statute Divisions
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/divisions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.6 Get Specific Division (by slug)
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/divisions/general-provisions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.7 Get Statute Provisions
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/provisions?per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.8 Get Specific Provision (by slug)
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/provisions/incorporation-requirements" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.9 Get Statute Schedules
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/schedules" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 1.10 Get Specific Schedule (by slug)
```bash
curl -X GET "http://localhost:8000/api/statutes/companies-and-allied-matters-act-2020/schedules/forms-and-documents" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 2. ADMIN ROUTES (ID-based, Full CRUD)

### 2.1 List All Statutes (Admin)
```bash
curl -X GET "http://localhost:8000/api/admin/statutes" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 2.2 List Statutes with Admin Filters
```bash
curl -X GET "http://localhost:8000/api/admin/statutes?status=active&search=company&created_by=1&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 2.3 Create New Statute
```bash
curl -X POST "http://localhost:8000/api/admin/statutes" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Statute Act 2024",
    "short_title": "TSA 2024",
    "year_enacted": 2024,
    "commencement_date": "2024-01-01",
    "status": "active",
    "jurisdiction": "Federal",
    "country": "Nigeria",
    "state": null,
    "local_government": null,
    "citation_format": "TSA 2024",
    "sector": "Testing",
    "tags": ["test", "example", "api"],
    "description": "A test statute created via API for validation purposes. This statute demonstrates the creation capabilities of the statute management system.",
    "range": "Sections 1-50"
  }'
```

### 2.4 Get Single Statute (Admin by ID)
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 2.5 Update Statute
```bash
curl -X PUT "http://localhost:8000/api/admin/statutes/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Test Statute Act 2024",
    "description": "Updated description for the test statute.",
    "status": "amended",
    "tags": ["test", "updated", "api", "example"]
  }'
```

### 2.6 Delete Statute
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 3. DIVISIONS MANAGEMENT

### 3.1 List Statute Divisions (Admin)
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/divisions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 3.2 Create Division
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/1/divisions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "division_type": "part",
    "division_number": "I",
    "division_title": "Preliminary Provisions",
    "division_subtitle": "General Matters",
    "content": "This part contains the preliminary provisions of the statute including definitions and scope.",
    "sort_order": 1,
    "level": 1,
    "status": "active"
  }'
```

### 3.3 Get Single Division
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/divisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 3.4 Update Division
```bash
curl -X PUT "http://localhost:8000/api/admin/statutes/1/divisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "division_title": "Updated Preliminary Provisions",
    "content": "Updated content for the preliminary provisions section."
  }'
```

### 3.5 Delete Division
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1/divisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 4. PROVISIONS MANAGEMENT

### 4.1 List Statute Provisions (Admin)
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/provisions?division_id=1&per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 4.2 Create Provision
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/1/provisions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "provision_type": "section",
    "provision_number": "1",
    "provision_title": "Short Title and Commencement",
    "provision_text": "(1) This Act may be cited as the Test Statute Act 2024.\n(2) This Act shall come into operation on such date as the Minister may, by notice published in the Gazette, appoint.",
    "marginal_note": "Citation and commencement",
    "interpretation_note": "This section provides the official title and commencement provisions.",
    "division_id": 1,
    "sort_order": 1,
    "level": 1,
    "status": "active"
  }'
```

### 4.3 Get Single Provision
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/provisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 4.4 Update Provision
```bash
curl -X PUT "http://localhost:8000/api/admin/statutes/1/provisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "provision_text": "Updated provision text with additional subsections.",
    "interpretation_note": "Updated interpretation note explaining the changes."
  }'
```

### 4.5 Delete Provision
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1/provisions/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 5. SCHEDULES MANAGEMENT

### 5.1 List Statute Schedules (Admin)
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/schedules" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 5.2 Create Schedule
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/1/schedules" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "schedule_number": "1",
    "schedule_title": "Forms and Documents",
    "content": "FORM A - Application for Registration\nFORM B - Certificate of Incorporation\nFORM C - Annual Return",
    "schedule_type": "forms",
    "sort_order": 1,
    "status": "active"
  }'
```

### 5.3 Get Single Schedule
```bash
curl -X GET "http://localhost:8000/api/admin/statutes/1/schedules/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 5.4 Update Schedule
```bash
curl -X PUT "http://localhost:8000/api/admin/statutes/1/schedules/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "schedule_title": "Updated Forms and Documents",
    "content": "Updated content with additional forms."
  }'
```

### 5.5 Delete Schedule
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1/schedules/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 6. RELATIONSHIP MANAGEMENT

### 6.1 Add Amendment Relationship
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/1/amendments" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amending_statute_id": 2,
    "effective_date": "2024-06-01",
    "amendment_description": "Amendment to section 5 regarding registration procedures"
  }'
```

### 6.2 Remove Amendment Relationship
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1/amendments/2" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 6.3 Add Citation Relationship
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/1/citations" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "cited_statute_id": 3,
    "citation_context": "Referenced in section 10 for procedural guidance"
  }'
```

### 6.4 Remove Citation Relationship
```bash
curl -X DELETE "http://localhost:8000/api/admin/statutes/1/citations/3" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 7. BULK OPERATIONS

### 7.1 Bulk Update Statutes
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/bulk-update" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "statute_ids": [1, 2, 3],
    "updates": {
      "status": "active",
      "sector": "Updated Sector"
    }
  }'
```

### 7.2 Bulk Delete Statutes
```bash
curl -X POST "http://localhost:8000/api/admin/statutes/bulk-delete" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "statute_ids": [1, 2, 3]
  }'
```

---

## 8. FILE UPLOAD EXAMPLE

### 8.1 Create Statute with File Upload
```bash
curl -X POST "http://localhost:8000/api/admin/statutes" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "title=Test Statute with Files" \
  -F "jurisdiction=Federal" \
  -F "country=Nigeria" \
  -F "description=A test statute with file attachments" \
  -F "status=active" \
  -F "files[]=@/path/to/statute-document.pdf" \
  -F "files[]=@/path/to/explanatory-notes.docx"
```

---

## Common Query Parameters

- `per_page`: Number of items per page (max 100)
- `page`: Page number for pagination
- `search`: Search in title, description, citation
- `status`: Filter by status (active, repealed, amended, suspended)
- `jurisdiction`: Filter by jurisdiction
- `country`: Filter by country
- `state`: Filter by state
- `sector`: Filter by sector
- `year`: Filter by year enacted
- `sort_by`: Sort field (title, year_enacted, created_at, etc.)
- `sort_order`: Sort direction (asc, desc)
- `include_related`: Include parent/child relationships
- `include_amendments`: Include amendment relationships
- `include_citations`: Include citation relationships

## Testing Notes

1. **Authentication**: Ensure you have admin role for admin endpoints
2. **IDs vs Slugs**: User routes use slugs, admin routes use IDs
3. **File Uploads**: Use `multipart/form-data` for file uploads
4. **Validation**: All required fields must be provided
5. **Relationships**: Test cascade deletions carefully
6. **Pagination**: Large datasets are paginated automatically

## Expected Response Format

All responses follow the standard API format:
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

Use these cURL commands in Insomnia by importing them or creating new requests with the provided configurations.