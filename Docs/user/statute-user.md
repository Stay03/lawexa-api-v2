# User Statute API - Complete Navigation Guide

## Authentication

All endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Base URL
- **Local:** `http://localhost:8000/api`
- **Production:** `https://rest.lawexa.com/api`

## User Permissions

Regular users have **read-only access** to **published statutes only**. This includes:
- ✅ View published statutes and their content
- ✅ Navigate hierarchical structures (divisions, provisions)
- ✅ Access breadcrumb navigation
- ✅ Search and filter published content
- ❌ View draft/unpublished statutes
- ❌ Modify statute content
- ❌ Access administrative functions

---

## System Overview

The User Statute API provides **complete hierarchical navigation** through published legal document structures using **SEO-friendly slug-based routing**. This system supports unlimited nesting depth with consistent pagination and breadcrumb navigation.

### Core Navigation Features

1. **Hierarchical Drill-Down**: Navigate from statute → divisions → provisions → child provisions
2. **Breadcrumb Navigation**: Full context path at every level
3. **Consistent Pagination**: All endpoints support pagination with meta and links
4. **SEO-Friendly URLs**: All routes use slugs instead of IDs
5. **Filtering Support**: Search and status filtering throughout
6. **Adaptive Structure**: Accommodates various legal document patterns
7. **Range Support**: Optional range indicators showing coverage scope (e.g., "Chapter I - X", "Section 1-10")

---

## Available Statutes

Currently available published statutes include:
- **The Statute** (`the-statute`) - Test statute with hierarchical structure
- **Companies and Allied Matters Act** (`companies-and-allied-matters-act`) - CAMA 2020, Range: "Sections 1-870"
- **Constitution of the Federal Republic of Nigeria** (`constitution-of-the-federal-republic-of-nigeria-1999`) - Range: "Chapters I-VIII"
- **Administration of Criminal Justice Act** (`administration-of-criminal-justice-act-2015`) - Range: "Parts 1-49"
- **Test Statute for API Validation** (`test-statute-for-api-validation`) - Range: "Sections 1-10"
- **Test Statute Act 2024** (`test-statute-act-2024`) - Range: "Sections 1-50"

---

## Complete Endpoint Documentation

### 1. List All Statutes

**GET** `/statutes`

**Purpose**: Retrieve all published statutes available to users.

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number (default: 1)

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statutes retrieved successfully",
  "data": {
    "statutes": [
      {
        "id": 18,
        "slug": "the-statute",
        "title": "The statute",
        "short_title": null,
        "year_enacted": 2025,
        "commencement_date": null,
        "status": "active",
        "jurisdiction": "Federal",
        "country": "Nigeria",
        "citation_format": null,
        "sector": null,
        "tags": [],
        "description": "Lorem ipsum sit dolor amet",
        "range": "Sections 1-50",
        "creator": {
          "id": 2,
          "name": "Stay Njokede"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 6,
      "total": 6
    },
    "links": {
      "first": "https://rest.lawexa.com/api/statutes?page=1",
      "last": "https://rest.lawexa.com/api/statutes?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

### 2. Show Specific Statute

**GET** `/statutes/{statute}`

**Purpose**: Retrieve detailed information about a specific statute.

**Parameters:**
- `statute` (required): Statute slug (e.g., "the-statute")

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute retrieved successfully",
  "data": {
    "statute": {
      "id": 18,
      "slug": "the-statute",
      "title": "The statute",
      "short_title": null,
      "year_enacted": 2025,
      "commencement_date": null,
      "status": "active",
      "jurisdiction": "Federal",
      "country": "Nigeria",
      "description": "Lorem ipsum sit dolor amet",
      "range": "Sections 1-50",
      "creator": {
        "id": 2,
        "name": "Stay Njokede"
      },
      "files": [],
      "files_count": 0
    }
  }
}
```

---

### 3. Get Statute Divisions

**GET** `/statutes/{statute}/divisions`

**Purpose**: Get the top-level structural divisions (chapters, parts, titles) of a statute.

**Parameters:**
- `statute` (required): Statute slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status (active, repealed, amended)
- `division_type` (optional): Filter by type (chapter, part, article, etc.)

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/divisions"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute divisions retrieved successfully",
  "data": {
    "statute": {
      "id": 18,
      "title": "The statute",
      "slug": "the-statute",
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "slug": "the-statute",
          "type": "statute"
        }
      ]
    },
    "divisions": [
      {
        "id": 27,
        "slug": "first-chapter",
        "statute_id": 18,
        "division_type": "chapter",
        "division_number": "1",
        "division_title": "First Chapter",
        "division_subtitle": null,
        "content": null,
        "range": null,
        "sort_order": 1,
        "level": 1,
        "status": "active",
        "parent_division": null
      }
    ],
    "meta": {
      "statute_slug": "the-statute",
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 4,
      "total": 4
    },
    "links": {
      "first": "https://rest.lawexa.com/api/statutes/the-statute/divisions?page=1",
      "last": "https://rest.lawexa.com/api/statutes/the-statute/divisions?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

---

### 4. Show Specific Division

**GET** `/statutes/{statute}/divisions/{divisionSlug}`

**Purpose**: Retrieve detailed information about a specific division.

**Parameters:**
- `statute` (required): Statute slug
- `divisionSlug` (required): Division slug (e.g., "first-chapter")

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/divisions/first-chapter"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute division retrieved successfully",
  "data": {
    "division": {
      "id": 27,
      "slug": "first-chapter",
      "statute_id": 18,
      "division_type": "chapter",
      "division_number": "1",
      "division_title": "First Chapter",
      "division_subtitle": null,
      "content": null,
      "range": null,
      "sort_order": 1,
      "level": 1,
      "status": "active",
      "parent_division": null,
      "child_divisions": [],
      "provisions": []
    }
  }
}
```

---

### 5. Get Division Children

**GET** `/statutes/{statute}/divisions/{divisionSlug}/children`

**Purpose**: Retrieve child divisions within a parent division.

**Parameters:**
- `statute` (required): Statute slug
- `divisionSlug` (required): Parent division slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `division_type` (optional): Filter by type

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/divisions/first-chapter/children"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Division children retrieved successfully",
  "data": {
    "parent": {
      "id": 27,
      "title": "First Chapter",
      "number": "1",
      "slug": "first-chapter",
      "type": "chapter",
      "level": 1,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "slug": "the-statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "slug": "first-chapter",
          "type": "chapter"
        }
      ]
    },
    "children": [
      {
        "id": 29,
        "slug": "first-part",
        "statute_id": 18,
        "parent_division_id": 27,
        "division_type": "part",
        "division_number": "I",
        "division_title": "First Part",
        "division_subtitle": null,
        "content": null,
        "range": "Chapter I - X",
        "sort_order": 1,
        "level": 2,
        "status": "active",
        "parent_division": {
          "id": 27,
          "division_title": "First Chapter",
          "division_number": "1",
          "slug": "first-chapter"
        }
      }
    ],
    "meta": {
      "has_children": true,
      "child_level": 2,
      "parent_division_slug": "first-chapter",
      "statute_slug": "the-statute",
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 3,
      "total": 3
    }
  }
}
```

---

### 6. Get Division Provisions

**GET** `/statutes/{statute}/divisions/{divisionSlug}/provisions`

**Purpose**: Retrieve provisions within a specific division.

**Parameters:**
- `statute` (required): Statute slug
- `divisionSlug` (required): Division slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `provision_type` (optional): Filter by type (section, subsection, paragraph, etc.)
- `search` (optional): Search in provision text/title

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/divisions/first-part/provisions"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Division provisions retrieved successfully",
  "data": {
    "division": {
      "id": 29,
      "title": "First Part",
      "number": "I",
      "slug": "first-part",
      "type": "part",
      "level": 2,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "slug": "the-statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "slug": "first-chapter",
          "type": "chapter"
        },
        {
          "id": 29,
          "title": "First Part",
          "number": "I",
          "slug": "first-part",
          "type": "part"
        }
      ]
    },
    "provisions": [
      {
        "id": 18,
        "slug": "first-section",
        "statute_id": 18,
        "division_id": 29,
        "parent_provision_id": null,
        "provision_type": "section",
        "provision_number": "(1)",
        "provision_title": "First section",
        "provision_text": "lorem",
        "marginal_note": null,
        "interpretation_note": null,
        "range": "Section 1-10",
        "sort_order": 1,
        "level": 3,
        "status": "active",
        "parent_provision": null
      }
    ],
    "meta": {
      "division_slug": "first-part",
      "statute_slug": "the-statute",
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 3,
      "total": 3
    }
  }
}
```

---

### 7. Get Statute Provisions

**GET** `/statutes/{statute}/provisions`

**Purpose**: Retrieve all provisions within a statute (across all divisions).

**Parameters:**
- `statute` (required): Statute slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `provision_type` (optional): Filter by type
- `search` (optional): Search in provision text/title

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/provisions"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute provisions retrieved successfully",
  "data": {
    "statute": {
      "id": 18,
      "title": "The statute",
      "slug": "the-statute",
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "slug": "the-statute",
          "type": "statute"
        }
      ]
    },
    "provisions": [
      {
        "id": 18,
        "slug": "first-section",
        "statute_id": 18,
        "division_id": 29,
        "parent_provision_id": null,
        "provision_type": "section",
        "provision_number": "(1)",
        "provision_title": "First section",
        "provision_text": "lorem",
        "marginal_note": null,
        "interpretation_note": null,
        "range": "Section 1-10",
        "sort_order": 1,
        "level": 3,
        "status": "active",
        "division": {
          "id": 29,
          "division_title": "First Part",
          "slug": "first-part"
        },
        "parent_provision": null
      }
    ],
    "meta": {
      "statute_slug": "the-statute",
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 5,
      "total": 5
    }
  }
}
```

---

### 8. Show Specific Provision

**GET** `/statutes/{statute}/provisions/{provisionSlug}`

**Purpose**: Retrieve detailed information about a specific provision.

**Parameters:**
- `statute` (required): Statute slug
- `provisionSlug` (required): Provision slug (e.g., "first-section")

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/provisions/first-section"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute provision retrieved successfully",
  "data": {
    "provision": {
      "id": 18,
      "slug": "first-section",
      "statute_id": 18,
      "division_id": 29,
      "parent_provision_id": null,
      "provision_type": "section",
      "provision_number": "(1)",
      "provision_title": "First section",
      "provision_text": "lorem",
      "marginal_note": null,
      "interpretation_note": null,
      "range": "Section 1-10",
      "sort_order": 1,
      "level": 3,
      "status": "active",
      "division": {
        "id": 29,
        "division_title": "First Part"
      },
      "parent_provision": null,
      "child_provisions": []
    }
  }
}
```

---

### 9. Get Provision Children

**GET** `/statutes/{statute}/provisions/{provisionSlug}/children`

**Purpose**: Retrieve child provisions within a parent provision (subsections, clauses, etc.).

**Parameters:**
- `statute` (required): Statute slug
- `provisionSlug` (required): Parent provision slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `provision_type` (optional): Filter by type
- `search` (optional): Search in provision text/title

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/provisions/first-section/children"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Provision children retrieved successfully",
  "data": {
    "parent": {
      "id": 18,
      "title": "First section",
      "number": "(1)",
      "slug": "first-section",
      "type": "section",
      "level": 3,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "slug": "the-statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "slug": "first-chapter",
          "type": "chapter"
        },
        {
          "id": 29,
          "title": "First Part",
          "number": "I",
          "slug": "first-part",
          "type": "part"
        },
        {
          "id": 18,
          "title": "First section",
          "number": "(1)",
          "slug": "first-section",
          "type": "section"
        }
      ]
    },
    "children": [
      {
        "id": 21,
        "slug": "subone",
        "statute_id": 18,
        "division_id": 29,
        "parent_provision_id": 18,
        "provision_type": "subsection",
        "provision_number": "(a)",
        "provision_title": "subone",
        "provision_text": "subsection one",
        "marginal_note": null,
        "interpretation_note": null,
        "range": null,
        "sort_order": 10,
        "level": 4,
        "status": "active",
        "parent_provision": {
          "id": 18,
          "provision_title": "First section",
          "provision_number": "(1)",
          "slug": "first-section"
        }
      }
    ],
    "meta": {
      "has_children": true,
      "child_level": 4,
      "parent_provision_slug": "first-section",
      "statute_slug": "the-statute",
      "current_page": 1,
      "from": 1,
      "last_page": 1,
      "per_page": 15,
      "to": 2,
      "total": 2
    }
  }
}
```

---

### 10. Get Statute Schedules

**GET** `/statutes/{statute}/schedules`

**Purpose**: Retrieve schedules/appendices within a statute.

**Parameters:**
- `statute` (required): Statute slug

**Query Parameters:**
- `per_page` (optional): Items per page (default: 20)
- `page` (optional): Page number (default: 1)

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/schedules"
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute schedules retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [],
    "first_page_url": "https://rest.lawexa.com/api/statutes/the-statute/schedules?page=1",
    "from": null,
    "last_page": 1,
    "last_page_url": "https://rest.lawexa.com/api/statutes/the-statute/schedules?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "https://rest.lawexa.com/api/statutes/the-statute/schedules?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "https://rest.lawexa.com/api/statutes/the-statute/schedules",
    "per_page": 20,
    "prev_page_url": null,
    "to": null,
    "total": 0
  }
}
```

---

### 11. Show Specific Schedule

**GET** `/statutes/{statute}/schedules/{scheduleSlug}`

**Purpose**: Retrieve detailed information about a specific schedule.

**Parameters:**
- `statute` (required): Statute slug
- `scheduleSlug` (required): Schedule slug

**Example Request:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/the-statute/schedules/{scheduleSlug}"
```

**Response Structure:**
*Similar to individual provision response format, adapted for schedules.*

---

## Hierarchical Navigation Flow

### Example Navigation Pattern

Using "The Statute" as an example, here's how to navigate through the hierarchy:

1. **Start with statutes list**: `GET /statutes`
2. **Select specific statute**: `GET /statutes/the-statute`
3. **Get top-level divisions**: `GET /statutes/the-statute/divisions`
4. **Navigate to chapter**: `GET /statutes/the-statute/divisions/first-chapter`
5. **Get chapter children**: `GET /statutes/the-statute/divisions/first-chapter/children`
6. **Get part provisions**: `GET /statutes/the-statute/divisions/first-part/provisions`
7. **View specific section**: `GET /statutes/the-statute/provisions/first-section`
8. **Get section children**: `GET /statutes/the-statute/provisions/first-section/children`

### Breadcrumb Navigation

Every response includes breadcrumb navigation showing the full path:

```json
"breadcrumb": [
  {
    "id": 18,
    "title": "The statute",
    "slug": "the-statute",
    "type": "statute"
  },
  {
    "id": 27,
    "title": "First Chapter",
    "number": "1",
    "slug": "first-chapter",
    "type": "chapter"
  },
  {
    "id": 29,
    "title": "First Part",
    "number": "I",
    "slug": "first-part",
    "type": "part"
  }
]
```

---

## SEO-Friendly URL Structure

All user endpoints use slug-based routing for SEO benefits:

### URL Patterns:
- **Statutes**: `/statutes/{statute-slug}`
- **Divisions**: `/statutes/{statute-slug}/divisions/{division-slug}`
- **Division Children**: `/statutes/{statute-slug}/divisions/{division-slug}/children`
- **Division Provisions**: `/statutes/{statute-slug}/divisions/{division-slug}/provisions`
- **Provisions**: `/statutes/{statute-slug}/provisions/{provision-slug}`
- **Provision Children**: `/statutes/{statute-slug}/provisions/{provision-slug}/children`
- **Schedules**: `/statutes/{statute-slug}/schedules/{schedule-slug}`

### Benefits:
- **Human-readable URLs**: Easy to understand and share
- **SEO optimized**: Search engines can better index content
- **Consistent structure**: Predictable URL patterns
- **Cacheable**: Better caching strategies possible

---

## Query Parameters & Filtering

### Common Parameters:
- `per_page`: Results per page (default: 15, max: 100)
- `page`: Page number (default: 1)
- `status`: Filter by status (active, repealed, amended)

### Type-Specific Parameters:
- `division_type`: Filter divisions by type (chapter, part, article, etc.)
- `provision_type`: Filter provisions by type (section, subsection, paragraph, etc.)
- `search`: Search in titles and text content

### Example Filtered Requests:
```bash
# Get only active chapters
GET /statutes/the-statute/divisions?status=active&division_type=chapter

# Search provisions containing "criminal"
GET /statutes/the-statute/provisions?search=criminal

# Get subsections with pagination
GET /statutes/the-statute/provisions/first-section/children?provision_type=subsection&per_page=5
```

---

## Error Handling

### Common Error Responses:

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 404 Not Found
```json
{
  "status": "error",
  "message": "Statute not found"
}
```

#### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["The per page field must be an integer."]
  }
}
```

---

## Implementation Notes

### Key Differences from Admin API:
1. **Slug-based routing**: User APIs use slugs instead of IDs
2. **Published content only**: Only active/published statutes are accessible
3. **No modification endpoints**: Read-only access
4. **Simplified responses**: Reduced metadata for better performance
5. **Public caching**: Responses can be cached more aggressively

### Performance Considerations:
- All endpoints support pagination
- Use appropriate `per_page` values to balance performance
- Breadcrumb data is included in responses to reduce additional requests
- Responses are optimized for frontend consumption

### Integration Tips:
- Use breadcrumb data for navigation UI
- Implement client-side caching for frequently accessed endpoints
- Use search parameters for content filtering
- Leverage slug-based URLs for SEO-friendly web applications
- Display range information to show content scope to users

---

## Range Field Usage

### Understanding Range Fields

The `range` field is an optional indicator that shows the scope or coverage of a statute, division, or provision:

#### **Examples:**
- **Statute level**: `"Sections 1-870"` - Indicates the statute contains sections 1 through 870
- **Division level**: `"Chapter I - X"` - Shows the division covers chapters 1 through 10 
- **Provision level**: `"Section 1-10"` - Indicates the provision spans sections 1 through 10

#### **Common Range Formats:**
- Numeric: `"Section 1-10"`, `"Parts 1-49"`
- Roman numerals: `"Chapter I - VIII"`, `"Book I - III"`
- Alphabetic: `"Clause (a)-(z)"`, `"Item A-Z"`
- Mixed: `"Article 1(a)-5(z)"`

#### **Usage Notes:**
- Range field can be `null` if not applicable
- Displayed in all list and detail responses where present
- Useful for understanding document scope and navigation
- Not used for filtering or searching (use other fields for that)