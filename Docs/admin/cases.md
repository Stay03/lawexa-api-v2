# Legal Cases Management API

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
- **user** - Regular user (can view cases)
- **admin** - Administrator (full case management)
- **researcher** - Research access (full case management)
- **superadmin** - Full system access (full case management)

### Access Matrix

| Action | User | Admin | Researcher | Superadmin |
|--------|------|-------|------------|------------|
| View all cases | ✅ | ✅ | ✅ | ✅ |
| View single case | ✅ | ✅ | ✅ | ✅ |
| Create cases | ❌ | ✅ | ✅ | ✅ |
| Edit cases | ❌ | ✅ | ✅ | ✅ |
| Delete cases | ❌ | ✅ | ✅ | ✅ |

### Routing Structure

**User Endpoints**: Use slug-based URLs for SEO-friendly access
- `/api/cases/{slug}` - Human-readable URLs

**Admin Endpoints**: Use ID-based URLs for security and performance  
- `/api/admin/cases/{id}` - Numeric IDs for admin operations

---

## User Endpoints

### 1. Get Cases List (Public)

**GET** `/cases`

Retrieves a paginated list of legal cases with filtering and search capabilities.

#### Required Permissions
- Authenticated user (any role)

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in title, body, principles, judges |
| `country` | string | No | - | Filter by country |
| `court` | string | No | - | Filter by court name |
| `topic` | string | No | - | Filter by legal topic |
| `level` | string | No | - | Filter by court level |
| `date_from` | date | No | - | Filter cases from date (YYYY-MM-DD) |
| `date_to` | date | No | - | Filter cases up to date (YYYY-MM-DD) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /cases?search=constitutional&country=Nigeria&court=Supreme Court&page=1&per_page=10
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 2,
        "title": "Utique volo turba crapula labore temperantia. Sufficio titulus adhaero acidus dens deduco quaerat cerno canonicus. Aliquid cernuus vespillo alias tabella cito. Inflammatio aqua adaugeo ipsa curis ultio sumo curto.",
        "body": "Celebrer tardus delego denuncio nam quibusdam agnosco iusto.",
        "course": "Constitutional Law 201",
        "topic": "Fundamental Rights",
        "tag": "landmark",
        "principles": null,
        "level": "Supreme Court",
        "slug": "utique-volo-turba-crapula-labore-temperantia-sufficio-titulus-adhaero-acidus-dens-deduco-quaerat-cerno-canonicus-aliquid-cernuus-vespillo-alias-tabella-cito-inflammatio-aqua-adaugeo-ipsa-curis-ultio-sumo-curto",
        "court": "Supreme Court of Nigeria",
        "date": "2023-06-15",
        "country": "Nigeria",
        "citation": "2023 SCNJ 456",
        "judges": "Justice Adebayo, Justice Okafor, Justice Musa",
        "judicial_precedent": "Strong",
        "creator": {
          "id": 36,
          "name": "Calvin Hammes-Fay"
        },
        "files": [
          {
            "id": 23,
            "name": "test-case-document.txt",
            "size": 57,
            "human_size": "57 B",
            "mime_type": "text/plain",
            "extension": "txt",
            "category": "case_reports",
            "is_image": false,
            "is_document": true,
            "created_at": "2025-07-25 17:09:44"
          }
        ],
        "files_count": 1,
        "similar_cases": {},
        "similar_cases_count": {},
        "created_at": "2025-07-24T17:24:35.000000Z",
        "updated_at": "2025-07-24T17:24:35.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 2,
      "to": 2,
      "total": 2
    },
    "links": {
      "first": "http://localhost:8000/api/cases?page=1",
      "last": "http://localhost:8000/api/cases?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 2. Get Single Case (Public)

**GET** `/cases/{slug}`

Retrieves detailed information about a specific legal case using its slug.

#### Required Permissions
- Authenticated user (any role)

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Case slug (URL-friendly identifier) |

#### Example Request
```
GET /cases/test-id-based-update
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 1,
      "title": "Test ID-based Update",
      "body": "Testing admin ID-based routing",
      "course": "Constitutional Law 201",
      "topic": "Updated Legal Topic",
      "tag": "landmark",
      "principles": null,
      "level": "Court of Appeal",
      "slug": "test-id-based-update",
      "court": "Court of Appeal, Lagos",
      "date": "2023-06-15",
      "country": "Nigeria",
      "citation": "2023 SCNJ 456",
      "judges": "Justice Adebayo, Justice Okafor, Justice Musa",
      "judicial_precedent": "Strong",
      "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>TEST ID-BASED UPDATE [2023] SCNJ 456</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">Test Plaintiff Ltd<span style=\"white-space: pre;\"> </span>Claimant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Test Defendant<span style=\"white-space: pre;\"> </span>Respondent</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>This comprehensive case report contains detailed legal analysis, precedents, and judicial reasoning. The HTML format preserves legal document structure and formatting...</p>",
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "files": [],
      "files_count": 0,
      "similar_cases": [
        {
          "id": 15,
          "title": "Related Case v Example Corp [2023] Test 123",
          "slug": "related-case-v-example-corp-2023-test-123",
          "court": "Supreme Court",
          "date": "2023-04-05",
          "country": "Nigeria",
          "citation": "[2023] Test 123"
        },
        {
          "id": 25,
          "title": "Another Similar Case [2022] Test 789",
          "slug": "another-similar-case-2022-test-789",
          "court": "Court of Appeal",
          "date": "2022-12-15",
          "country": "Nigeria",
          "citation": "[2022] Test 789"
        }
      ],
      "similar_cases_count": 2,
      "created_at": "2025-07-24T17:18:48.000000Z",
      "updated_at": "2025-07-24T20:20:21.000000Z"
    }
  }
}
```

#### Error Responses

**401 Unauthenticated**
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

**404 Not Found**
```json
{
  "status": "error",
  "message": "Endpoint not found",
  "data": null
}
```

---

## Admin Endpoints

### 3. Get Cases List (Admin)

**GET** `/admin/cases`

Retrieves a paginated list of legal cases with admin-specific filtering options.

#### Required Permissions
- admin, researcher, or superadmin

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search in title, body, principles, judges |
| `country` | string | No | - | Filter by country |
| `court` | string | No | - | Filter by court name |
| `topic` | string | No | - | Filter by legal topic |
| `level` | string | No | - | Filter by court level |
| `date_from` | date | No | - | Filter cases from date (YYYY-MM-DD) |
| `date_to` | date | No | - | Filter cases up to date (YYYY-MM-DD) |
| `created_by` | integer | No | - | Filter by creator user ID (admin only) |
| `include_similar_cases` | boolean | No | false | Include similar cases data in list view (performance impact) |
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 15 | Items per page (max: 100) |

#### Example Request
```
GET /admin/cases?search=constitutional&created_by=36&page=1&per_page=15
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Cases retrieved successfully",
  "data": {
    "cases": [
      {
        "id": 2,
        "title": "Utique volo turba crapula labore temperantia. Sufficio titulus adhaero acidus dens deduco quaerat cerno canonicus. Aliquid cernuus vespillo alias tabella cito. Inflammatio aqua adaugeo ipsa curis ultio sumo curto.",
        "body": "Celebrer tardus delego denuncio nam quibusdam agnosco iusto.",
        "course": "Constitutional Law 201",
        "topic": "Fundamental Rights",
        "tag": "landmark",
        "principles": null,
        "level": "Supreme Court",
        "slug": "utique-volo-turba-crapula-labore-temperantia-sufficio-titulus-adhaero-acidus-dens-deduco-quaerat-cerno-canonicus-aliquid-cernuus-vespillo-alias-tabella-cito-inflammatio-aqua-adaugeo-ipsa-curis-ultio-sumo-curto",
        "court": "Supreme Court of Nigeria",
        "date": "2023-06-15",
        "country": "Nigeria",
        "citation": "2023 SCNJ 456",
        "judges": "Justice Adebayo, Justice Okafor, Justice Musa",
        "judicial_precedent": "Strong",
        "creator": {
          "id": 36,
          "name": "Calvin Hammes-Fay"
        },
        "files": [
          {
            "id": 28,
            "name": "test-case-report.txt",
            "filename": "211c7970-b4da-4a3b-ba6f-0f2fd9699290.txt",
            "size": 52,
            "human_size": "52 B",
            "mime_type": "text/plain",
            "extension": "txt",
            "category": "case_reports",
            "url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/case_reports/2025/07/211c7970-b4da-4a3b-ba6f-0f2fd9699290.txt",
            "download_url": "https://lawexa-api-files-dev.s3.amazonaws.com/uploads/case_reports/2025/07/211c7970-b4da-4a3b-ba6f-0f2fd9699290.txt?[signed-parameters]",
            "is_image": false,
            "is_document": true,
            "disk": "s3",
            "metadata": {
              "upload_ip": "127.0.0.1",
              "upload_user_agent": "curl/8.2.1",
              "expected_size": 52,
              "initiated_at": "2025-07-26T10:46:34.955492Z",
              "completed_at": "2025-07-26T10:46:36.960856Z",
              "s3_etag": "bef2f710ba2c4d7315ac23c197c6b39f"
            },
            "attached_to": {
              "type": "App\\Models\\CourtCase",
              "id": 15
            },
            "uploaded_by": {
              "id": 2,
              "name": "Dr. Arturo Rogahn",
              "email": "Johnathon.Prohaska@hotmail.com"
            },
            "created_at": "2025-07-26T10:46:34.000000Z",
            "updated_at": "2025-07-26T10:46:37.000000Z"
          }
        ],
        "files_count": 1,
        "similar_cases": {},
        "similar_cases_count": {},
        "created_at": "2025-07-24T17:24:35.000000Z",
        "updated_at": "2025-07-24T17:24:35.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 2,
      "to": 2,
      "total": 2
    },
    "links": {
      "first": "http://localhost:8000/api/admin/cases?page=1",
      "last": "http://localhost:8000/api/admin/cases?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

### 4. Get Single Case (Admin)

**GET** `/admin/cases/{id}`

Retrieves detailed information about a specific legal case using its ID.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Case ID (numeric identifier) |

#### Example Request
```
GET /admin/cases/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Case retrieved successfully",
  "data": {
    "case": {
      "id": 1,
      "title": "Test ID-based Update",
      "body": "Testing admin ID-based routing",
      "course": "Constitutional Law 201",
      "topic": "Updated Legal Topic",
      "tag": "landmark",
      "principles": null,
      "level": "Court of Appeal",
      "slug": "test-id-based-update",
      "court": "Court of Appeal, Lagos",
      "date": "2023-06-15",
      "country": "Nigeria",
      "citation": "2023 SCNJ 456",
      "judges": "Justice Adebayo, Justice Okafor, Justice Musa",
      "judicial_precedent": "Strong",
      "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>TEST ID-BASED UPDATE [2023] SCNJ 456</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">Test Plaintiff Ltd<span style=\"white-space: pre;\"> </span>Claimant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Test Defendant<span style=\"white-space: pre;\"> </span>Respondent</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>This comprehensive case report contains detailed legal analysis, precedents, and judicial reasoning. The HTML format preserves legal document structure and formatting...</p>",
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "files": [],
      "files_count": 0,
      "similar_cases": [
        {
          "id": 15,
          "title": "Related Case v Example Corp [2023] Test 123",
          "slug": "related-case-v-example-corp-2023-test-123",
          "court": "Supreme Court",
          "date": "2023-04-05",
          "country": "Nigeria",
          "citation": "[2023] Test 123"
        },
        {
          "id": 25,
          "title": "Another Similar Case [2022] Test 789",
          "slug": "another-similar-case-2022-test-789",
          "court": "Court of Appeal",
          "date": "2022-12-15",
          "country": "Nigeria",
          "citation": "[2022] Test 789"
        }
      ],
      "similar_cases_count": 2,
      "created_at": "2025-07-24T17:18:48.000000Z",
      "updated_at": "2025-07-24T20:20:21.000000Z"
    }
  }
}
```

### 5. Create Case (Admin)

**POST** `/admin/cases`

Creates a new legal case record with optional file attachments.

**Content Types Supported:**
- **JSON (`application/json`)**: For creating cases without file uploads
- **Multipart (`multipart/form-data`)**: Required for creating cases with file uploads

**Note**: Files can only be uploaded using multipart/form-data. Files are automatically categorized as "case_reports" and uploaded to S3.

#### Required Permissions
- admin, researcher, or superadmin

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:500 | Case title |
| `body` | string | Yes | - | Case description/summary |
| `course` | string | No | nullable, max:255 | Associated course |
| `topic` | string | No | nullable, max:255 | Legal topic/area |
| `tag` | string | No | nullable, max:100 | Case tag/category |
| `principles` | string | No | nullable | Legal principles established |
| `level` | string | No | nullable, max:255 | Court level |
| `court` | string | No | nullable, max:255 | Court name |
| `date` | date | No | nullable | Case date (YYYY-MM-DD) |
| `country` | string | No | nullable, max:255 | Country |
| `citation` | string | No | nullable, max:255 | Legal citation |
| `judges` | string | No | nullable | Judges involved |
| `judicial_precedent` | string | No | nullable, max:255 | Precedent strength |
| `case_report_text` | string | No | nullable | Full detailed case report text in HTML format (stored separately for performance) |
| `similar_case_ids` | integer array | No | max:50 items, each must exist | Array of case IDs to link as similar cases |
| `files` | file array | No | max:10, each max:100MB | Case report files (PDF, DOC, TXT, etc.) |

#### Example Request (JSON)
```json
{
  "title": "New Constitutional Case",
  "body": "This case deals with fundamental rights under the constitution.",
  "course": "Constitutional Law 301",
  "topic": "Fundamental Rights",
  "tag": "landmark",
  "principles": "Freedom of expression is a fundamental right",
  "level": "Supreme Court",
  "court": "Supreme Court of Nigeria",
  "date": "2024-01-15",
  "country": "Nigeria",
  "citation": "2024 SCNJ 123",
  "judges": "Justice Olukoya, Justice Adeola",
  "judicial_precedent": "Strong",
  "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>NEW CONSTITUTIONAL CASE [2024] SCNJ 123</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">The Attorney General<span style=\"white-space: pre;\"> </span>Applicant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Citizens Rights Group</span></strong></p><p><strong><span style=\"font-size: 14pt;\">2.<span style=\"white-space: pre;\"> </span>Freedom Foundation<span style=\"white-space: pre;\"> </span>Respondents</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>This case establishes fundamental principles regarding constitutional rights and freedoms under the Nigerian Constitution...</p>",
  "similar_case_ids": [15, 25, 30]
}
```

#### Example Request (With File Upload and Similar Cases)
```bash
curl -X POST "/admin/cases" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "title=New Constitutional Case with Documents" \
  -F "body=This case deals with fundamental rights under the constitution." \
  -F "court=Supreme Court of Nigeria" \
  -F "country=Nigeria" \
  -F "case_report_text=<p><span style=\"font-size: 24pt;\"><strong>NEW CONSTITUTIONAL CASE WITH DOCUMENTS [2024] SCNJ 123</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">The Attorney General<span style=\"white-space: pre;\"> </span>Applicant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Citizens Rights Group<span style=\"white-space: pre;\"> </span>Respondents</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>This comprehensive case report establishes key constitutional principles...</p>" \
  -F "similar_case_ids[]=15" \
  -F "similar_case_ids[]=25" \
  -F "files[]=@case-report.pdf" \
  -F "files[]=@supporting-document.docx"
```

#### Success Response (201)
```json
{
  "status": "success",
  "message": "Case created successfully",
  "data": {
    "case": {
      "id": 3,
      "title": "New Constitutional Case",
      "body": "This case deals with fundamental rights under the constitution.",
          "course": "Constitutional Law 301",
      "topic": "Fundamental Rights",
      "tag": "landmark",
      "principles": "Freedom of expression is a fundamental right",
      "level": "Supreme Court",
      "slug": "new-constitutional-case",
      "court": "Supreme Court of Nigeria",
      "date": "2024-01-15",
      "country": "Nigeria",
      "citation": "2024 SCNJ 123",
      "judges": "Justice Olukoya, Justice Adeola",
      "judicial_precedent": "Strong",
      "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>NEW CONSTITUTIONAL CASE [2024] SCNJ 123</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">The Attorney General<span style=\"white-space: pre;\"> </span>Applicant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Citizens Rights Group</span></strong></p><p><strong><span style=\"font-size: 14pt;\">2.<span style=\"white-space: pre;\"> </span>Freedom Foundation<span style=\"white-space: pre;\"> </span>Respondents</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>This case establishes fundamental principles regarding constitutional rights and freedoms under the Nigerian Constitution...</p>",
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "files": [],
      "files_count": 0,
      "similar_cases": [
        {
          "id": 15,
          "title": "Related Constitutional Case [2023] Test 456",
          "slug": "related-constitutional-case-2023-test-456",
          "court": "Supreme Court",
          "date": "2023-04-05",
          "country": "Nigeria",
          "citation": "[2023] Test 456"
        },
        {
          "id": 25,
          "title": "Freedom of Expression Case [2022] Test 789",
          "slug": "freedom-of-expression-case-2022-test-789",
          "court": "Court of Appeal",
          "date": "2022-12-15",
          "country": "Nigeria",
          "citation": "[2022] Test 789"
        }
      ],
      "similar_cases_count": 2,
      "created_at": "2025-07-24T20:45:00.000000Z",
      "updated_at": "2025-07-24T20:45:00.000000Z"
    }
  }
}
```

### 6. Update Case (Admin)

**PUT** `/admin/cases/{id}`

Updates an existing legal case record with optional file attachments.

**Content Types Supported:**
- **JSON (`application/json`)**: For updating case fields without file uploads
- **Multipart (`multipart/form-data`)**: Required for updating cases with file uploads

**Important Note for File Uploads**: 
- Files uploaded during updates are **added to existing files** (files accumulate)
- For file uploads with updates, use `POST` with `_method=PUT` parameter instead of actual `PUT` method
- New files are automatically categorized as "case_reports" and uploaded to S3

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Case ID to update |

#### Request Body
Same as Create Case endpoint (all fields optional for updates), including:
- All case fields are optional
- `case_report_text` field to create/update/delete full case report text (optional)
  - Provide text to create or update existing case report
  - Provide empty string `""` to delete existing case report
  - Omit field to leave case report unchanged
- `similar_case_ids[]` array to update linked similar cases (optional - replaces existing links)
- `files[]` array for new file uploads (optional)

#### Example Request (JSON)
```json
{
  "title": "Updated Case Title",
  "body": "Updated case description",
  "topic": "Updated Legal Topic",
  "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>UPDATED CASE TITLE [2023] SCNJ 456</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">The State<span style=\"white-space: pre;\"> </span>Prosecutor</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Defendant Name<span style=\"white-space: pre;\"> </span>Defendant</span></strong></p><p>&nbsp;</p><p><strong>UPDATED JUDGMENT</strong></p><p>This updated case report includes additional legal analysis and revised judicial reasoning...</p>",
  "similar_case_ids": [10, 20, 35]
}
```

#### Example Request (With File Upload and Similar Cases)
```bash
curl -X POST "/admin/cases/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "_method=PUT" \
  -F "title=Updated Case Title with New Files" \
  -F "case_report_text=<p><span style=\"font-size: 24pt;\"><strong>UPDATED CASE TITLE WITH NEW FILES [2023] SCNJ 456</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">Updated Plaintiff<span style=\"white-space: pre;\"> </span>Claimant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Updated Defendant<span style=\"white-space: pre;\"> </span>Respondent</span></strong></p><p>&nbsp;</p><p><strong>AMENDED JUDGMENT</strong></p><p>This updated case report with additional files includes comprehensive legal analysis...</p>" \
  -F "similar_case_ids[]=10" \
  -F "similar_case_ids[]=20" \
  -F "files[]=@additional-report.pdf" \
  -F "files[]=@supporting-document.docx"
```

**Note**: Use `POST` with `_method=PUT` for file uploads due to HTTP limitations with multipart data in PUT requests.

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Case updated successfully",
  "data": {
    "case": {
      "id": 1,
      "title": "Updated Case Title",
      "body": "Updated case description",
      "course": "Constitutional Law 201",
      "topic": "Updated Legal Topic",
      "tag": "landmark",
      "principles": null,
      "level": "Court of Appeal",
      "slug": "updated-case-title",
      "court": "Court of Appeal, Lagos",
      "date": "2023-06-15",
      "country": "Nigeria",
      "citation": "2023 SCNJ 456",
      "judges": "Justice Adebayo, Justice Okafor, Justice Musa",
      "judicial_precedent": "Strong",
      "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>UPDATED CASE TITLE [2023] SCNJ 456</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">The State<span style=\"white-space: pre;\"> </span>Prosecutor</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Defendant Name<span style=\"white-space: pre;\"> </span>Defendant</span></strong></p><p>&nbsp;</p><p><strong>UPDATED JUDGMENT</strong></p><p>This updated case report includes additional legal analysis and revised judicial reasoning...</p>",
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "files": [],
      "files_count": 0,
      "similar_cases": [
        {
          "id": 10,
          "title": "Updated Similar Case [2023] Test 101",
          "slug": "updated-similar-case-2023-test-101",
          "court": "High Court",
          "date": "2023-03-10",
          "country": "Nigeria",
          "citation": "[2023] Test 101"
        },
        {
          "id": 20,
          "title": "Related Legal Topic Case [2022] Test 202",
          "slug": "related-legal-topic-case-2022-test-202",
          "court": "Court of Appeal",
          "date": "2022-11-20",
          "country": "Nigeria",
          "citation": "[2022] Test 202"
        }
      ],
      "similar_cases_count": 2,
      "created_at": "2025-07-24T17:18:48.000000Z",
      "updated_at": "2025-07-24T20:50:00.000000Z"
    }
  }
}
```

### 7. Delete Case (Admin)

**DELETE** `/admin/cases/{id}`

Permanently deletes a legal case record.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Case ID to delete |

#### Example Request
```
DELETE /admin/cases/1
```

#### Success Response (200)
```json
{
  "status": "success",
  "message": "Case deleted successfully",
  "data": null
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
  "message": "Endpoint not found",
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
    "title": ["Case title is required"],
    "body": ["Case body is required"],
    "similar_case_ids.0": ["One or more similar case IDs do not exist"],
    "similar_case_ids": ["A case cannot be marked as similar to itself"]
  }
}
```

---

## Data Models

### Case Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Unique case identifier |
| `title` | string | No | Case title |
| `body` | string | No | Case description/summary |
| `course` | string | Yes | Associated course |
| `topic` | string | Yes | Legal topic/area |
| `tag` | string | Yes | Case tag/category |
| `principles` | string | Yes | Legal principles established |
| `level` | string | Yes | Court level |
| `slug` | string | No | URL-friendly identifier (auto-generated) |
| `court` | string | Yes | Court name |
| `date` | string | Yes | Case date (YYYY-MM-DD) |
| `country` | string | Yes | Country |
| `citation` | string | Yes | Legal citation |
| `judges` | string | Yes | Judges involved |
| `judicial_precedent` | string | Yes | Precedent strength |
| `case_report_text` | string | Yes | Full detailed case report text in HTML format (null if not set, stored separately for performance) |
| `creator` | object | No | Creator user information |
| `files` | array | No | Array of attached case report files |
| `files_count` | integer | No | Total number of attached case report files |
| `similar_cases` | array | No | Array of similar/related cases (empty object `{}` in list views, array in single case views) |
| `similar_cases_count` | integer/object | No | Number of similar cases (empty object `{}` in list views, integer in single case views) |
| `created_at` | string | Yes | ISO timestamp of creation |
| `updated_at` | string | Yes | ISO timestamp of last update |

### Creator Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Creator user ID |
| `name` | string | No | Creator name |

### Similar Case Object

**Note**: Similar cases are automatically detected based on case content, topics, and legal principles. They appear as an array in single case views and as empty objects `{}` in list views for performance optimization.

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Similar case ID |
| `title` | string | No | Similar case title |
| `slug` | string | No | Similar case slug for URL routing |
| `court` | string | Yes | Court name where the similar case was heard |
| `date` | string | Yes | Similar case date (YYYY-MM-DD format) |
| `country` | string | Yes | Country where the similar case was heard |
| `citation` | string | Yes | Legal citation of the similar case |

### File Object

**Note for Frontend**: Files attached to cases are specifically case report documents (PDFs, Word docs, text files, etc.) that supplement the case information.

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | File ID |
| `name` | string | No | Original filename |
| `filename` | string | No | UUID-based stored filename |
| `size` | integer | No | File size in bytes |
| `human_size` | string | No | Human-readable file size (e.g., "52 B") |
| `mime_type` | string | No | MIME type of the file |
| `extension` | string | No | File extension |
| `category` | string | No | Always "case_reports" for case files |
| `url` | string | No | Direct S3 access URL |
| `download_url` | string | No | Signed S3 download URL (expires in 1 hour) |
| `is_image` | boolean | No | Whether file is an image |
| `is_document` | boolean | No | Whether file is a document |
| `disk` | string | No | Storage disk (always "s3" for new uploads) |
| `metadata` | object | No | Upload metadata (IP, user agent, S3 info) |
| `attached_to` | object | No | Parent case information |
| `uploaded_by` | object | No | User who uploaded the file |
| `created_at` | string | No | File upload timestamp |
| `updated_at` | string | No | File last update timestamp |

### File Metadata Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `upload_ip` | string | No | IP address of uploader |
| `upload_user_agent` | string | No | User agent of uploader |
| `expected_size` | integer | No | Expected file size during upload |
| `initiated_at` | string | No | ISO timestamp when upload was initiated |
| `completed_at` | string | No | ISO timestamp when upload was completed |
| `s3_etag` | string | No | S3 ETag for file verification |

### File Attached To Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `type` | string | No | Parent model class (always "App\\Models\\CourtCase") |
| `id` | integer | No | Parent case ID |

### File Uploaded By Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Uploader user ID |
| `name` | string | No | Uploader name |
| `email` | string | No | Uploader email |

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

## Common Use Cases

### Search Cases
```
GET /cases?search=constitutional
GET /admin/cases?search=constitutional
```

### Filter by Country
```
GET /cases?country=Nigeria
GET /admin/cases?country=Nigeria
```

### Filter by Court Level
```
GET /cases?level=Supreme Court
GET /admin/cases?level=Supreme Court
```

### Date Range Filter
```
GET /cases?date_from=2023-01-01&date_to=2023-12-31
GET /admin/cases?date_from=2023-01-01&date_to=2023-12-31
```

### Filter by Creator (Admin Only)
```
GET /admin/cases?created_by=36
```

### Combined Filters
```
GET /cases?search=rights&country=Nigeria&level=Supreme Court&topic=Constitutional
GET /admin/cases?search=rights&country=Nigeria&created_by=36&page=2&per_page=25
```

### Case Report Text Management
```bash
# Create case with full report text
curl -X POST "http://127.0.0.1:8000/api/admin/cases" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Case with Report",
    "body": "Case summary",
    "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>TEST CASE WITH REPORT [2025] TEST 001</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">Test Plaintiff<span style=\"white-space: pre;\"> </span>Claimant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Test Defendant<span style=\"white-space: pre;\"> </span>Respondent</span></strong></p><p>&nbsp;</p><p><strong>JUDGMENT</strong></p><p>Comprehensive full report text with detailed legal analysis...</p>"
  }'

# Update only the case report text
curl -X PUT "http://127.0.0.1:8000/api/admin/cases/123" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "case_report_text": "<p><span style=\"font-size: 24pt;\"><strong>UPDATED TEST CASE [2025] TEST 001</strong></span></p><p>&nbsp;</p><p><strong><span style=\"font-size: 14pt;\">Between:</span></strong></p><p><strong><span style=\"font-size: 14pt;\">Updated Plaintiff<span style=\"white-space: pre;\"> </span>Claimant</span></strong></p><p>&nbsp;</p><p><strong>AND</strong></p><p><strong><span style=\"font-size: 14pt;\">1.<span style=\"white-space: pre;\"> </span>Updated Defendant<span style=\"white-space: pre;\"> </span>Respondent</span></strong></p><p>&nbsp;</p><p><strong>REVISED JUDGMENT</strong></p><p>Updated comprehensive report text with additional analysis...</p>"
  }'

# Delete case report text
curl -X PUT "http://127.0.0.1:8000/api/admin/cases/123" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "case_report_text": ""
  }'
```

### Similar Cases Management
```
# Create case with similar cases
POST /admin/cases
{"title": "New Case", "body": "Case content", "similar_case_ids": [123, 456, 789]}

# Update similar cases only
PUT /admin/cases/1
{"similar_case_ids": [101, 202, 303]}

# Clear all similar cases
PUT /admin/cases/1
{"similar_case_ids": []}

# Get admin cases with similar cases included
GET /admin/cases?include_similar_cases=true&per_page=10
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 401 | Unauthenticated (invalid/missing token) |
| 403 | Unauthorized (insufficient permissions) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |

---

## Notes

### Automatic Slug Generation
- Slugs are automatically generated from case titles
- Slugs are updated when titles are modified
- Slugs are used for SEO-friendly user URLs
- Admin endpoints use numeric IDs for security and performance

### Search Functionality
- Searches across title, body, principles, and judges fields
- Case-insensitive search
- Partial word matching supported

### Similar Cases Feature
- **Automatic Detection**: Similar cases are automatically identified based on case content, legal topics, and principles
- **Manual Management**: Administrators can manually link similar cases using the `similar_case_ids` field during case creation or updates
- **Performance Optimization**: 
  - In list views (`/cases` and `/admin/cases`): `similar_cases` and `similar_cases_count` appear as empty objects `{}` for faster loading
  - In single case views (`/cases/{slug}` and `/admin/cases/{id}`): Full similar cases data is included as an array
  - Use `include_similar_cases=true` query parameter in admin list views to include similar cases data (impacts performance)
- **Data Structure**: Each similar case includes `id`, `title`, `slug`, `court`, `date`, `country`, and `citation`
- **Validation Rules**:
  - Maximum 50 similar cases per case
  - Case IDs must exist in the database
  - A case cannot be marked as similar to itself
  - Empty array `[]` can be used to clear all similar case links
- **Use Cases**: Helps legal researchers find related precedents and comparable cases for comprehensive legal analysis

### Case Report Text Feature
- **Purpose**: Stores comprehensive full case report text separately from main case data for performance optimization
- **Format**: HTML formatted text preserving legal document structure with proper headings, paragraphs, and styling
- **Typical Structure**: Case title, parties (Between/AND sections), judgment text with proper legal formatting
- **Storage**: Uses dedicated `case_reports` table with foreign key relationship to cases
- **Performance**: Prevents loading large text content during case listing operations
- **Optional Field**: Completely optional - cases work perfectly without case reports
- **Management**: Full CRUD operations supported:
  - **Create**: Provide `case_report_text` field during case creation
  - **Read**: Always included in single case responses when available (shows `null` if not set)
  - **Update**: Provide `case_report_text` field during case updates to create/modify
  - **Delete**: Provide empty string `""` in `case_report_text` field to remove existing report
- **Data Integrity**: Automatically deleted when parent case is deleted (cascade delete)
- **API Responses**: Included in both user and admin endpoints when case report relationship is loaded

### Security
- User endpoints use slug-based routing (public-friendly)
- Admin endpoints use ID-based routing (secure, fast)
- Admin endpoints restricted to numeric IDs only
- All endpoints require authentication
- Role-based access control enforced