# Statute Management API (Admin)

This document covers the admin endpoints for creating, viewing, updating, and deleting **Statutes**, **Divisions**, and **Provisions**.

**Base URL:** `https://rest.lawexa.com/api/admin`

**Authentication:** All endpoints require a Bearer token with admin, researcher, or superadmin role.

**Headers Required:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Implementation Status

### Statute Endpoints
- **Status:** ✅ Fully functional and tested on live production server
- All CRUD operations (Create, View, Update, Delete) work as documented

### Division & Provision Endpoints
- **Status:** ⚠️ Partially functional - bugs fixed, routing issues remain
- **What Works:**
  - ✅ CREATE endpoints are fully functional (tested on local server)
  - Bugs in controllers were identified and fixed (undefined `$statuteId`, `$provisionId` variables)
- **What Needs Attention:**
  - ❌ VIEW/UPDATE/DELETE endpoints return "Endpoint not found" errors
  - This appears to be a route matching issue with nested parameters `{statute}/{division}` and `{statute}/{provision}`
  - Routes are properly defined in `routes/api.php` but not matching at runtime
  - **Not yet deployed to production** - endpoints return 404 on live server

**Recommendation:** The division and provision controller bugs have been fixed in this session. The routing issue needs investigation - possibly related to route model binding configuration or middleware interference.

---

## Table of Contents
1. [Statute Endpoints](#statute-endpoints)
   - [Create Statute](#create-statute)
   - [View Statute](#view-statute)
   - [Update Statute](#update-statute)
   - [Delete Statute](#delete-statute)
2. [Division Endpoints](#division-endpoints)
   - [Create Division](#create-division)
   - [View Division](#view-division)
   - [Update Division](#update-division)
   - [Delete Division](#delete-division)
3. [Provision Endpoints](#provision-endpoints)
   - [Create Provision](#create-provision)
   - [View Provision](#view-provision)
   - [Update Provision](#update-provision)
   - [Delete Provision](#delete-provision)
4. [Error Responses](#error-responses)
5. [Notes & Best Practices](#notes--best-practices)

---

## Statute Endpoints

### Create Statute

**Endpoint:** `POST /admin/statutes`

Creates a new statute in the system.

**Request Example:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/statutes" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Statute for Documentation",
    "status": "active",
    "year_enacted": 2024,
    "jurisdiction": "Federal",
    "description": "This is a test statute created for API documentation purposes"
  }'
```

**Required Fields:**
- `title` (string, max 255): The full title of the statute
- `status` (enum): One of: `active`, `repealed`, `amended`, `suspended`

**Optional Fields:**
- `short_title` (string, max 255): Abbreviated title
- `year_enacted` (integer, 1800 to current_year + 10): Year the statute was enacted
- `commencement_date` (date, format: YYYY-MM-DD): When the statute came into effect
- `repealed_date` (date): Required if status is `repealed`
- `repealing_statute_id` (integer): ID of statute that repealed this one (required if status is `repealed`)
- `parent_statute_id` (integer): ID of parent statute for hierarchical organization
- `jurisdiction` (string, max 100): Legal jurisdiction (e.g., "Federal", "State")
- `country` (string, max 100): Country name
- `state` (string, max 100): State/province name
- `local_government` (string, max 100): Local government area
- `citation_format` (string, max 255): Standard citation format for this statute
- `sector` (string, max 100): Legal sector (e.g., "Education", "Criminal Law")
- `tags` (array, max 20 items): Array of strings (each max 50 chars)
- `description` (text): Detailed description of the statute
- `range` (string, max 255): Section/provision range covered
- `files` (array, max 10): File uploads (handled via file upload service)

**Validation Rules:**
- Statute cannot be its own parent (`parent_statute_id` ≠ statute ID)
- Statute cannot repeal itself (`repealing_statute_id` ≠ statute ID)
- `commencement_date` cannot be before `year_enacted`
- If `status` is `repealed`, both `repealed_date` and `repealing_statute_id` are required

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Statute created successfully",
  "data": {
    "statute": {
      "id": 87,
      "slug": "test-statute-for-documentation",
      "title": "Test Statute for Documentation",
      "short_title": null,
      "year_enacted": 2024,
      "commencement_date": null,
      "status": "active",
      "repealed_date": null,
      "jurisdiction": "Federal",
      "country": null,
      "state": null,
      "local_government": null,
      "citation_format": null,
      "sector": null,
      "tags": null,
      "description": "This is a test statute created for API documentation purposes",
      "range": null,
      "created_at": "2025-11-12 17:26:19",
      "updated_at": "2025-11-12 17:26:19",
      "creator": {
        "id": 2,
        "name": "Stay Njokede"
      },
      "files": [],
      "files_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0
    }
  }
}
```

**Notes:**
- A unique `slug` is automatically generated from the `title` upon creation
- The `created_by` field is automatically set to the authenticated user's ID
- Files are uploaded to S3 storage and managed via the DirectS3Upload system

---

### View Statute

**Endpoint:** `GET /admin/statutes/{id}`

Retrieves a single statute with all its relationships.

**Request Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/statutes/87" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `id` (integer, required): The numeric ID of the statute

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Statute retrieved successfully",
  "data": {
    "statute": {
      "id": 87,
      "slug": "test-statute-for-documentation",
      "title": "Test Statute for Documentation",
      "short_title": null,
      "year_enacted": 2024,
      "commencement_date": null,
      "status": "active",
      "repealed_date": null,
      "jurisdiction": "Federal",
      "country": null,
      "state": null,
      "local_government": null,
      "citation_format": null,
      "sector": null,
      "tags": null,
      "description": "This is a test statute created for API documentation purposes",
      "range": null,
      "created_at": "2025-11-12 17:26:19",
      "updated_at": "2025-11-12 17:26:19",
      "creator": {
        "id": 2,
        "name": "Stay Njokede"
      },
      "parent_statute": null,
      "child_statutes": [],
      "amendments": [],
      "amended_by": [],
      "cited_statutes": [],
      "citing_statutes": [],
      "divisions": [],
      "divisions_count": 0,
      "schedules": [],
      "schedules_count": 0,
      "files": [],
      "files_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0
    }
  }
}
```

**Included Relationships:**
- `creator`: User who created the statute
- `parent_statute`: Parent statute (if hierarchical)
- `child_statutes`: Child statutes (if parent)
- `amendments`: Amendments to this statute
- `amended_by`: Statutes that amended this one
- `cited_statutes`: Statutes cited by this one
- `citing_statutes`: Statutes that cite this one
- `divisions`: Nested divisions structure
- `schedules`: Associated schedules
- `files`: Uploaded document files

---

### Update Statute

**Endpoint:** `PUT /admin/statutes/{id}`

Updates an existing statute. All fields are optional - only send the fields you want to update.

**Request Example:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/statutes/87" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "short_title": "Test Statute (Updated)",
    "sector": "Education",
    "tags": ["test", "documentation", "updated"]
  }'
```

**URL Parameters:**
- `id` (integer, required): The numeric ID of the statute to update

**Request Body:**
All fields from the Create endpoint are available, but all are optional. Only include fields you want to update.

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Statute updated successfully",
  "data": {
    "statute": {
      "id": 87,
      "slug": "test-statute-for-documentation",
      "title": "Test Statute for Documentation",
      "short_title": "Test Statute (Updated)",
      "year_enacted": 2024,
      "commencement_date": null,
      "status": "active",
      "repealed_date": null,
      "jurisdiction": "Federal",
      "country": null,
      "state": null,
      "local_government": null,
      "citation_format": null,
      "sector": "Education",
      "tags": ["test", "documentation", "updated"],
      "description": "This is a test statute created for API documentation purposes",
      "range": null,
      "created_at": "2025-11-12 17:26:19",
      "updated_at": "2025-11-12 17:28:00",
      "creator": {
        "id": 2,
        "name": "Stay Njokede"
      },
      "files": [],
      "files_count": 0,
      "views_count": 0,
      "is_bookmarked": false,
      "bookmark_id": null,
      "bookmarks_count": 0
    }
  }
}
```

**Notes:**
- If you update the `title`, the `slug` will be automatically regenerated
- The `updated_at` timestamp is automatically updated
- Same validation rules apply as in Create

---

### Delete Statute

**Endpoint:** `DELETE /admin/statutes/{id}`

Permanently deletes a statute and all associated files from storage.

**Request Example:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/statutes/87" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `id` (integer, required): The numeric ID of the statute to delete

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Statute deleted successfully",
  "data": []
}
```

**Notes:**
- This action is **irreversible**
- All associated S3 files are deleted before the statute record is removed
- Cascading deletes may affect related divisions, provisions, and schedules (depending on database constraints)

---

## Division Endpoints

Divisions represent hierarchical structural components of statutes (Parts, Chapters, Articles, Titles, Books, Sections, Subsections, etc.).

### Create Division

**Endpoint:** `POST /admin/statutes/{statute}/divisions`

Creates a new division within a statute.

**Request Example:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/statutes/87/divisions" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "division_type": "part",
    "division_number": "I",
    "division_title": "Preliminary Provisions",
    "content": "This part contains preliminary provisions",
    "sort_order": 1,
    "level": 1,
    "status": "active"
  }'
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute

**Required Fields:**
- `division_type` (enum): One of: `part`, `chapter`, `article`, `title`, `book`, `division`, `section`, `subsection`
- `division_number` (string, max 255): The division number/identifier (e.g., "I", "1", "A")
- `division_title` (string, max 255): The title of the division

**Optional Fields:**
- `division_subtitle` (string, max 255): Subtitle or additional title text
- `content` (text): Full text content of the division
- `range` (string, max 255): Section/provision range covered by this division
- `parent_division_id` (integer): ID of parent division for nested structure
- `sort_order` (integer, min 0): Sort order for display (default: auto-incremented)
- `level` (integer, min 1, max 10): Depth level in hierarchy (1 = top level)
- `status` (enum): One of: `active`, `repealed`, `amended` (default: `active`)
- `effective_date` (date, format: YYYY-MM-DD): When this division became effective

**Success Response (201) - Actual tested response:**
```json
{
  "status": "success",
  "message": "Division created successfully",
  "data": {
    "division": {
      "division_type": "part",
      "division_number": "I",
      "division_title": "Preliminary Provisions",
      "content": "This part contains preliminary provisions",
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "statute_id": 87,
      "slug": "preliminary-provisions-Mz84UExh",
      "updated_at": "2025-11-12T17:46:47.000000Z",
      "created_at": "2025-11-12T17:46:47.000000Z",
      "id": 380
    }
  }
}
```

**Notes:**
- A unique `slug` is automatically generated from `division_title` + 8 random characters
- Divisions can be nested by setting `parent_division_id`
- The `level` field helps track hierarchy depth (parent divisions should have lower level numbers)

---

### View Division

**Endpoint:** `GET /admin/statutes/{statute}/divisions/{division}`

Retrieves a single division with its relationships.

**Request Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/statutes/87/divisions/123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `division` (integer, required): The numeric ID of the division

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Division retrieved successfully",
  "data": {
    "division": {
      "id": 123,
      "slug": "preliminary-provisions-a1b2c3d4",
      "statute_id": 87,
      "division_type": "part",
      "division_number": "I",
      "division_title": "Preliminary Provisions",
      "division_subtitle": null,
      "content": "This part contains preliminary provisions",
      "range": null,
      "parent_division_id": null,
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-11-12 18:00:00",
      "updated_at": "2025-11-12 18:00:00",
      "parent_division": null,
      "child_divisions": [],
      "provisions": []
    }
  }
}
```

**Included Relationships:**
- `parent_division`: The parent division (if nested)
- `child_divisions`: Child divisions nested under this one
- `provisions`: Provisions belonging to this division

---

### Update Division

**Endpoint:** `PUT /admin/statutes/{statute}/divisions/{division}`

Updates an existing division. All fields are optional - only send the fields you want to update.

**Request Example:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/statutes/87/divisions/123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "division_subtitle": "General Provisions",
    "content": "Updated content for preliminary provisions section"
  }'
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `division` (integer, required): The numeric ID of the division to update

**Request Body:**
All fields from the Create endpoint are available, but all are optional. Only include fields you want to update.

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Division updated successfully",
  "data": {
    "division": {
      "id": 123,
      "slug": "preliminary-provisions-a1b2c3d4",
      "statute_id": 87,
      "division_type": "part",
      "division_number": "I",
      "division_title": "Preliminary Provisions",
      "division_subtitle": "General Provisions",
      "content": "Updated content for preliminary provisions section",
      "range": null,
      "parent_division_id": null,
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-11-12 18:00:00",
      "updated_at": "2025-11-12 18:05:00"
    }
  }
}
```

**Notes:**
- If you update `division_title`, the `slug` will be automatically regenerated
- The system validates that the division belongs to the specified statute

---

### Delete Division

**Endpoint:** `DELETE /admin/statutes/{statute}/divisions/{division}`

Permanently deletes a division.

**Request Example:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/statutes/87/divisions/123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `division` (integer, required): The numeric ID of the division to delete

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Division deleted successfully",
  "data": []
}
```

**Notes:**
- This action is **irreversible**
- The system validates that the division belongs to the specified statute
- Cascading deletes may affect child divisions and provisions (depending on database constraints)

---

## Provision Endpoints

Provisions represent the actual legal text sections within statutes (Sections, Subsections, Paragraphs, Subparagraphs, Clauses, Subclauses, Items).

### Create Provision

**Endpoint:** `POST /admin/statutes/{statute}/provisions`

Creates a new provision within a statute.

**Request Example:**
```bash
curl -X POST "https://rest.lawexa.com/api/admin/statutes/87/provisions" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "provision_type": "section",
    "provision_number": "1",
    "provision_title": "Short Title",
    "provision_text": "This Act may be cited as the Test Statute 2024.",
    "sort_order": 1,
    "level": 1,
    "status": "active"
  }'
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute

**Required Fields:**
- `provision_type` (enum): One of: `section`, `subsection`, `paragraph`, `subparagraph`, `clause`, `subclause`, `item`
- `provision_number` (string, max 255): The provision number/identifier (e.g., "1", "1(a)", "2.3")
- `provision_text` (text): The actual legal text of the provision

**Optional Fields:**
- `provision_title` (string, max 255): The title/heading of the provision
- `marginal_note` (text): Marginal notes or side notes
- `interpretation_note` (text): Interpretation guidance or explanatory notes
- `range` (string, max 255): Range covered by this provision
- `division_id` (integer): ID of the division this provision belongs to
- `parent_provision_id` (integer): ID of parent provision for nested structure
- `sort_order` (integer, min 0): Sort order for display (default: auto-incremented)
- `level` (integer, min 1, max 10): Depth level in hierarchy (1 = top level)
- `status` (enum): One of: `active`, `repealed`, `amended` (default: `active`)
- `effective_date` (date, format: YYYY-MM-DD): When this provision became effective

**Success Response (201) - Actual tested response:**
```json
{
  "status": "success",
  "message": "Provision created successfully",
  "data": {
    "provision": {
      "provision_type": "section",
      "provision_number": "1",
      "provision_title": "Short Title",
      "provision_text": "This Act may be cited as the Test Statute 2024.",
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "statute_id": 87,
      "slug": "short-title-pazK7cad",
      "updated_at": "2025-11-12T17:51:27.000000Z",
      "created_at": "2025-11-12T17:51:27.000000Z",
      "id": 365
    }
  }
}
```

**Notes:**
- A unique `slug` is automatically generated from `provision_title` or `provision_number` + 8 random characters
- Provisions can be nested by setting `parent_provision_id`
- Provisions can be associated with divisions via `division_id`
- The `level` field helps track hierarchy depth

---

### View Provision

**Endpoint:** `GET /admin/statutes/{statute}/provisions/{provision}`

Retrieves a single provision with its relationships.

**Request Example:**
```bash
curl -X GET "https://rest.lawexa.com/api/admin/statutes/87/provisions/456" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `provision` (integer, required): The numeric ID of the provision

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Provision retrieved successfully",
  "data": {
    "provision": {
      "id": 456,
      "slug": "short-title-x1y2z3a4",
      "statute_id": 87,
      "provision_type": "section",
      "provision_number": "1",
      "provision_title": "Short Title",
      "provision_text": "This Act may be cited as the Test Statute 2024.",
      "marginal_note": null,
      "interpretation_note": null,
      "range": null,
      "division_id": null,
      "parent_provision_id": null,
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-11-12 18:10:00",
      "updated_at": "2025-11-12 18:10:00",
      "division": null,
      "parent_provision": null,
      "child_provisions": []
    }
  }
}
```

**Included Relationships:**
- `division`: The division this provision belongs to (if any)
- `parent_provision`: The parent provision (if nested)
- `child_provisions`: Child provisions nested under this one

---

### Update Provision

**Endpoint:** `PUT /admin/statutes/{statute}/provisions/{provision}`

Updates an existing provision. All fields are optional - only send the fields you want to update.

**Request Example:**
```bash
curl -X PUT "https://rest.lawexa.com/api/admin/statutes/87/provisions/456" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "marginal_note": "Citation of the Act",
    "interpretation_note": "This provision provides the short title for citation purposes."
  }'
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `provision` (integer, required): The numeric ID of the provision to update

**Request Body:**
All fields from the Create endpoint are available, but all are optional. Only include fields you want to update.

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Provision updated successfully",
  "data": {
    "provision": {
      "id": 456,
      "slug": "short-title-x1y2z3a4",
      "statute_id": 87,
      "provision_type": "section",
      "provision_number": "1",
      "provision_title": "Short Title",
      "provision_text": "This Act may be cited as the Test Statute 2024.",
      "marginal_note": "Citation of the Act",
      "interpretation_note": "This provision provides the short title for citation purposes.",
      "range": null,
      "division_id": null,
      "parent_provision_id": null,
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-11-12 18:10:00",
      "updated_at": "2025-11-12 18:15:00"
    }
  }
}
```

**Notes:**
- If you update `provision_title` or `provision_number`, the `slug` will be automatically regenerated
- The system validates that the provision belongs to the specified statute

---

### Delete Provision

**Endpoint:** `DELETE /admin/statutes/{statute}/provisions/{provision}`

Permanently deletes a provision.

**Request Example:**
```bash
curl -X DELETE "https://rest.lawexa.com/api/admin/statutes/87/provisions/456" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**URL Parameters:**
- `statute` (integer, required): The numeric ID of the parent statute
- `provision` (integer, required): The numeric ID of the provision to delete

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Provision deleted successfully",
  "data": []
}
```

**Notes:**
- This action is **irreversible**
- The system validates that the provision belongs to the specified statute
- Cascading deletes may affect child provisions (depending on database constraints)

---

## Error Responses

All endpoints follow a consistent error response format:

### Validation Error (422)
```json
{
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": ["The title field is required."],
    "status": ["The status field is required."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "status": "error",
  "message": "Unauthorized access"
}
```

### Not Found (404)
```json
{
  "status": "error",
  "message": "Statute not found"
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to create statute: [error details]",
  "errors": null
}
```

---

## Notes & Best Practices

### Authentication & Authorization
- All endpoints require Sanctum authentication
- Users must have one of these roles: `admin`, `researcher`, or `superadmin`
- Activity is tracked via the `track.guest.activity` middleware

### Slugs
- **Statutes:** Slugs are generated from the `title` only
- **Divisions:** Slugs are generated from `division_title` + 8 random characters
- **Provisions:** Slugs are generated from `provision_title` or `provision_number` + 8 random characters
- Slugs are automatically updated when titles change
- Slugs are used for public-facing URLs, while IDs are used for admin operations

### Hierarchical Structures
- **Statutes** can have parent-child relationships via `parent_statute_id`
- **Divisions** can be nested up to 10 levels deep via `parent_division_id`
- **Provisions** can be nested up to 10 levels deep via `parent_provision_id`
- **Provisions** can belong to divisions via `division_id`
- Use the `level` field to track depth (1 = top level, incrementing for each nested level)

### Sort Order
- The `sort_order` field controls display order
- If not provided, it auto-increments based on existing items
- Lower numbers appear first

### Status Values
- `active`: Currently in force
- `repealed`: No longer in force (requires `repealed_date` and `repealing_statute_id` for statutes)
- `amended`: Modified but still in force
- `suspended`: Temporarily not in force

### File Management (Statutes Only)
- Statutes support up to 10 file uploads
- Files are stored in S3 via the DirectS3Upload system
- File validation is handled by the FileUploadService
- Files are automatically deleted when the statute is deleted

### Route Binding Behavior
- **Admin routes:** Always use numeric IDs (e.g., `/admin/statutes/123`)
- **Public routes:** Use slugs (e.g., `/statutes/constitution-of-nigeria`)
- This distinction is enforced at the route level

### Division & Provision Endpoint Availability
**Important Notes:**
- **CREATE Endpoints:** ✅ Fully functional and tested on local server
  - `POST /admin/statutes/{statute}/divisions` - Working
  - `POST /admin/statutes/{statute}/provisions` - Working
- **VIEW/UPDATE/DELETE Endpoints:** ❌ Currently experiencing routing issues
  - `GET/PUT/DELETE /admin/statutes/{statute}/divisions/{division}` - Returns "Endpoint not found"
  - `GET/PUT/DELETE /admin/statutes/{statute}/provisions/{provision}` - Returns "Endpoint not found"
  - Routes are defined in `routes/api.php` but not matching at runtime
  - Controller bugs have been fixed (undefined variables), but routing needs investigation
- **Production Status:** Not yet deployed to live server (all division/provision endpoints return 404)

### Response Formats
- All successful responses include `status`, `message`, and `data` keys
- Status codes: `200` (success), `201` (created), `422` (validation error), `500` (server error)
- The `data` key contains the resource wrapped in a named object (e.g., `statute`, `division`, `provision`)

### Timestamps
- All resources include `created_at` and `updated_at` timestamps
- Timestamps are in `Y-m-d H:i:s` format (e.g., "2025-11-12 17:26:19")
- Times are in UTC

### Engagement Metrics (Statutes)
Statutes include engagement metrics in responses:
- `views_count`: Total number of views
- `is_bookmarked`: Whether the current user has bookmarked it
- `bookmark_id`: ID of the user's bookmark (if bookmarked)
- `bookmarks_count`: Total number of bookmarks

### Additional Statute Endpoints
Beyond CRUD operations, statutes also support:
- **List/Search:** `GET /admin/statutes` with filtering and pagination
- **Bulk Update:** `POST /admin/statutes/bulk-update`
- **Bulk Delete:** `POST /admin/statutes/bulk-delete`
- **Amendments:** `POST /admin/statutes/{id}/amendments` and `DELETE /admin/statutes/{id}/amendments/{amendmentId}`
- **Citations:** `POST /admin/statutes/{id}/citations` and `DELETE /admin/statutes/{id}/citations/{citationId}`

Refer to the full admin documentation for details on these endpoints.

---

**Document Version:** 1.0
**Last Updated:** 2025-11-12
**API Base URL:** https://rest.lawexa.com/api
