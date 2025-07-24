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
        "report": null,
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
      "report": "Updated comprehensive case report...",
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
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
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
        "report": null,
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
      "report": "Updated comprehensive case report...",
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
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "created_at": "2025-07-24T17:18:48.000000Z",
      "updated_at": "2025-07-24T20:20:21.000000Z"
    }
  }
}
```

### 5. Create Case (Admin)

**POST** `/admin/cases`

Creates a new legal case record.

#### Required Permissions
- admin, researcher, or superadmin

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `title` | string | Yes | max:500 | Case title |
| `body` | string | Yes | - | Case description/summary |
| `report` | string | No | nullable | Detailed case report |
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

#### Example Request
```json
{
  "title": "New Constitutional Case",
  "body": "This case deals with fundamental rights under the constitution.",
  "report": "Detailed analysis of the constitutional implications...",
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
  "judicial_precedent": "Strong"
}
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
      "report": "Detailed analysis of the constitutional implications...",
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
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
      "created_at": "2025-07-24T20:45:00.000000Z",
      "updated_at": "2025-07-24T20:45:00.000000Z"
    }
  }
}
```

### 6. Update Case (Admin)

**PUT** `/admin/cases/{id}`

Updates an existing legal case record.

#### Required Permissions
- admin, researcher, or superadmin

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Case ID to update |

#### Request Body
Same as Create Case endpoint (all fields optional for updates)

#### Example Request
```json
{
  "title": "Updated Case Title",
  "body": "Updated case description",
  "topic": "Updated Legal Topic"
}
```

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
      "report": "Updated comprehensive case report...",
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
      "creator": {
        "id": 36,
        "name": "Calvin Hammes-Fay"
      },
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
    "body": ["Case body is required"]
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
| `report` | string | Yes | Detailed case report |
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
| `creator` | object | No | Creator user information |
| `created_at` | string | Yes | ISO timestamp of creation |
| `updated_at` | string | Yes | ISO timestamp of last update |

### Creator Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Creator user ID |
| `name` | string | No | Creator name |

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

### Security
- User endpoints use slug-based routing (public-friendly)
- Admin endpoints use ID-based routing (secure, fast)
- Admin endpoints restricted to numeric IDs only
- All endpoints require authentication
- Role-based access control enforced