# Statute Lazy Loading API - User Endpoints

## Overview
The Statute Lazy Loading API provides high-performance access to statute content with hash-first navigation and bidirectional sequential loading. These endpoints enable loading statute content on-demand, dramatically improving performance for large statutes (e.g., constitutions with 300+ sections).

### Key Benefits
- **10x faster perceived load time** for hash-based navigation
- **90% reduction in initial API calls** for deep-linked content
- **Better SEO** - faster Time to First Contentful Paint
- **Improved scalability** - handles massive statutes efficiently

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
All lazy loading endpoints require authentication. Both authenticated users and guest users can access these endpoints.

### Authentication Headers (Required)
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

## How It Works

### Traditional Approach (Old)
1. Load all top-level divisions
2. Recursively load children until hash target is found
3. Render all loaded content
4. Scroll to hash target
5. **Result**: 3-5 seconds load time, wasted resources

### Hash-First Lazy Loading (New)
1. Load only the hash target content
2. Render immediately (<500ms)
3. Lazy load content above/below as user scrolls
4. **Result**: 10x faster, accurate analytics

## Order Index System

All statute content (divisions and provisions) have an `order_index` field that represents their sequential position in the statute's reading order. This enables efficient navigation without knowing the hierarchy.

**Example Order Index Structure:**
```
Constitution (statute)
  ├─ Chapter I (order_index: 100)
  │   ├─ Section 1 (order_index: 200)
  │   │   ├─ Subsection 1 (order_index: 300)
  │   │   └─ Subsection 2 (order_index: 400)
  │   └─ Section 2 (order_index: 500)
  └─ Chapter II (order_index: 600)
```

## Endpoints

---

## 1. Universal Content Lookup

Look up any division or provision by its slug without knowing its type or hierarchy level. This is the foundation of hash-first loading.

**Endpoint:** `GET /statutes/{statute_slug}/content/{content_slug}`

**Access:** Authenticated users (including guests)

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statute_slug` | string | Yes | Statute identifier slug |
| `content_slug` | string | Yes | Division or provision slug to look up |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `include_children` | boolean | No | `true` | Include immediate child divisions/provisions |
| `include_breadcrumb` | boolean | No | `true` | Include full breadcrumb trail from statute root |
| `include_siblings` | boolean | No | `false` | Include sibling content at same level |

### Example Requests

**Basic lookup (division):**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/general-provisions-7dsr1V6J" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Lookup provision:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/supremacy-of-constitution-I5ENJmXK" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Lookup without children and breadcrumb (faster):**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/general-provisions-7dsr1V6J?include_children=false&include_breadcrumb=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### Success Response (200) - Division

```json
{
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "division",
    "content": {
      "id": 375,
      "slug": "general-provisions-7dsr1V6J",
      "statute_id": 18,
      "parent_division_id": null,
      "division_type": "chapter",
      "division_number": "I",
      "division_title": "General Provisions",
      "division_subtitle": null,
      "content": null,
      "sort_order": 0,
      "level": 1,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-08-17T07:17:35.000000Z",
      "updated_at": "2025-10-27T04:31:19.000000Z",
      "range": null,
      "order_index": 100
    },
    "position": {
      "order_index": 100,
      "total_items": 76,
      "has_content_before": false,
      "has_content_after": true
    },
    "breadcrumb": [
      {
        "id": 18,
        "slug": "the-statute",
        "title": "The statute",
        "type": "statute",
        "order_index": null
      },
      {
        "id": 375,
        "slug": "general-provisions-7dsr1V6J",
        "title": "General Provisions",
        "number": "I",
        "type": "chapter",
        "order_index": 100
      }
    ],
    "children": [
      {
        "id": 376,
        "slug": "federal-republic-of-nigeria-5LZnaids",
        "division_type": "part",
        "division_number": "I",
        "division_title": "Federal Republic of Nigeria",
        "order_index": 200,
        "level": 2
      },
      {
        "id": 377,
        "slug": "powers-of-the-federal-republic-of-nigeria-qJGFSanW",
        "division_type": "part",
        "division_number": "II",
        "division_title": "Powers of the Federal Republic of Nigeria",
        "order_index": 1700,
        "level": 2
      }
    ],
    "siblings": null
  }
}
```

### Success Response (200) - Provision

```json
{
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "provision",
    "content": {
      "id": 79,
      "slug": "supremacy-of-constitution-I5ENJmXK",
      "statute_id": 18,
      "division_id": 376,
      "parent_provision_id": null,
      "provision_type": "section",
      "provision_number": "1",
      "provision_title": "Supremacy of constitution",
      "provision_text": "See subsections",
      "marginal_note": null,
      "interpretation_note": null,
      "sort_order": 0,
      "level": 3,
      "status": "active",
      "effective_date": null,
      "created_at": "2025-08-17T07:18:28.000000Z",
      "updated_at": "2025-10-27T04:31:19.000000Z",
      "range": null,
      "order_index": 300
    },
    "position": {
      "order_index": 300,
      "total_items": 76,
      "has_content_before": true,
      "has_content_after": true
    },
    "breadcrumb": [
      {
        "id": 18,
        "slug": "the-statute",
        "title": "The statute",
        "type": "statute",
        "order_index": null
      },
      {
        "id": 375,
        "slug": "general-provisions-7dsr1V6J",
        "title": "General Provisions",
        "number": "I",
        "type": "chapter",
        "order_index": 100
      },
      {
        "id": 376,
        "slug": "federal-republic-of-nigeria-5LZnaids",
        "title": "Federal Republic of Nigeria",
        "number": "I",
        "type": "part",
        "order_index": 200
      },
      {
        "id": 79,
        "slug": "supremacy-of-constitution-I5ENJmXK",
        "title": "Supremacy of constitution",
        "number": "1",
        "type": "section",
        "order_index": 300
      }
    ],
    "children": [
      {
        "id": 80,
        "slug": "1-TayN0J8b",
        "provision_type": "subsection",
        "provision_number": "(1)",
        "provision_title": null,
        "order_index": 400,
        "level": 4
      },
      {
        "id": 81,
        "slug": "2-RzTOfsfF",
        "provision_type": "subsection",
        "provision_number": "(2)",
        "provision_title": null,
        "order_index": 500,
        "level": 4
      },
      {
        "id": 82,
        "slug": "3-4ypQyzkf",
        "provision_type": "subsection",
        "provision_number": "(3)",
        "provision_title": null,
        "order_index": 600,
        "level": 4
      }
    ],
    "siblings": null
  }
}
```

### Error Responses

**404 Not Found - Content doesn't exist:**
```json
{
  "status": "error",
  "message": "Content with slug 'non-existent-slug' not found in statute 'the-statute'",
  "data": null
}
```

**404 Not Found - Statute doesn't exist:**
```json
{
  "status": "error",
  "message": "Statute not found",
  "data": null
}
```

**401 Unauthorized - No authentication:**
```json
{
  "status": "error",
  "message": "Unauthenticated.",
  "data": null
}
```

---

## 2. Sequential Content Navigation

Load content sequentially before or after a given position. This enables bidirectional lazy loading as users scroll.

**Endpoint:** `GET /statutes/{statute_slug}/content/sequential`

**Access:** Authenticated users (including guests)

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statute_slug` | string | Yes | Statute identifier slug |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `from_order` | integer | Yes | - | Starting position (order_index) |
| `direction` | string | Yes | - | `"before"` or `"after"` |
| `limit` | integer | No | `5` | Number of items to return (max: 50) |
| `include_children` | boolean | No | `true` | Include immediate children for divisions |

### Example Requests

**Load 5 items BEFORE position 300:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential?from_order=300&direction=before&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Load 10 items AFTER position 300:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential?from_order=300&direction=after&limit=10" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Load items without children (faster):**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential?from_order=300&direction=after&limit=3&include_children=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### Success Response (200) - Load Before

```json
{
  "status": "success",
  "message": "Sequential content retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": 200,
        "type": "division",
        "content": {
          "id": 376,
          "slug": "federal-republic-of-nigeria-5LZnaids",
          "order_index": 200,
          "type_name": "part",
          "number": "I",
          "title": "Federal Republic of Nigeria",
          "level": 2,
          "parent_id": 375,
          "status": "active",
          "created_at": "2025-08-17 07:17:39",
          "updated_at": "2025-10-27 04:31:19",
          "subtitle": null,
          "content": null,
          "has_children": true,
          "child_count": 3
        },
        "children": []
      },
      {
        "order_index": 100,
        "type": "division",
        "content": {
          "id": 375,
          "slug": "general-provisions-7dsr1V6J",
          "order_index": 100,
          "type_name": "chapter",
          "number": "I",
          "title": "General Provisions",
          "level": 1,
          "parent_id": null,
          "status": "active",
          "created_at": "2025-08-17 07:17:35",
          "updated_at": "2025-10-27 04:31:19",
          "subtitle": null,
          "content": null,
          "has_children": true,
          "child_count": 2
        },
        "children": [
          {
            "id": 376,
            "slug": "federal-republic-of-nigeria-5LZnaids",
            "division_type": "part",
            "division_number": "I",
            "division_title": "Federal Republic of Nigeria",
            "order_index": 200
          },
          {
            "id": 377,
            "slug": "powers-of-the-federal-republic-of-nigeria-qJGFSanW",
            "division_type": "part",
            "division_number": "II",
            "division_title": "Powers of the Federal Republic of Nigeria",
            "order_index": 1700
          }
        ]
      }
    ],
    "meta": {
      "direction": "before",
      "from_order": 300,
      "limit": 5,
      "returned": 2,
      "has_more": false,
      "next_from_order": null
    }
  }
}
```

### Success Response (200) - Load After

```json
{
  "status": "success",
  "message": "Sequential content retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": 400,
        "type": "provision",
        "content": {
          "id": 80,
          "slug": "1-TayN0J8b",
          "order_index": 400,
          "type_name": "subsection",
          "number": "(1)",
          "title": null,
          "level": 4,
          "parent_id": 79,
          "status": "active",
          "created_at": "2025-08-17 07:18:34",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "This Constitution is supreme and its provisions shall have binding force on the authorities and persons throughout the Federal Republic of Nigeria.",
          "division_id": null,
          "has_children": false
        },
        "children": []
      },
      {
        "order_index": 500,
        "type": "provision",
        "content": {
          "id": 81,
          "slug": "2-RzTOfsfF",
          "order_index": 500,
          "type_name": "subsection",
          "number": "(2)",
          "title": null,
          "level": 4,
          "parent_id": 79,
          "status": "active",
          "created_at": "2025-08-17 07:18:42",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "The Federal Republic of Nigeria shall not be governed, nor shall any persons or group of persons take control of the Government of Nigeria or any part thereof, except in accordance with the provisions of this Constitution.",
          "division_id": null,
          "has_children": false
        },
        "children": []
      },
      {
        "order_index": 600,
        "type": "provision",
        "content": {
          "id": 82,
          "slug": "3-4ypQyzkf",
          "order_index": 600,
          "type_name": "subsection",
          "number": "(3)",
          "title": null,
          "level": 4,
          "parent_id": 79,
          "status": "active",
          "created_at": "2025-08-17 07:18:47",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "If any other law is inconsistent with the provisions of this Constitution, this Constitution shall prevail, and that other law shall, to the extent of the inconsistency, be void.",
          "division_id": null,
          "has_children": false
        },
        "children": []
      }
    ],
    "meta": {
      "direction": "after",
      "from_order": 300,
      "limit": 3,
      "returned": 3,
      "has_more": true,
      "next_from_order": 600
    }
  }
}
```

### Error Responses

**422 Validation Error - Invalid parameters:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "direction": [
      "The selected direction is invalid."
    ]
  }
}
```

**422 Validation Error - Missing required parameters:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "from_order": [
      "The from order field is required."
    ],
    "direction": [
      "The direction field is required."
    ]
  }
}
```

**422 Validation Error - Limit exceeded:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "limit": [
      "The limit may not be greater than 50."
    ]
  }
}
```

---

## 3. Batch Content Range Loading

Load a range of content by position indices. Useful for loading a "buffer" of content around the hash target.

**Endpoint:** `GET /statutes/{statute_slug}/content/range`

**Access:** Authenticated users (including guests)

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statute_slug` | string | Yes | Statute identifier slug |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start_order` | integer | Yes | - | Start position (inclusive) |
| `end_order` | integer | Yes | - | End position (inclusive) |
| `include_children` | boolean | No | `true` | Include immediate children for divisions |

### Validation Rules
- `end_order` must be greater than or equal to `start_order`
- Maximum range size: 100 items (configurable)
- Both `start_order` and `end_order` must be positive integers

### Example Requests

**Load content from position 300 to 800:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/range?start_order=300&end_order=800" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Load single item range:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/range?start_order=300&end_order=300" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Load range without children:**
```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/range?start_order=100&end_order=300&include_children=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### Success Response (200)

```json
{
  "status": "success",
  "message": "Content range retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": 300,
        "type": "provision",
        "content": {
          "id": 79,
          "slug": "supremacy-of-constitution-I5ENJmXK",
          "order_index": 300,
          "type_name": "section",
          "number": "1",
          "title": "Supremacy of constitution",
          "level": 3,
          "parent_id": null,
          "status": "active",
          "created_at": "2025-08-17 07:18:28",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "See subsections",
          "division_id": 376,
          "has_children": true
        },
        "children": []
      },
      {
        "order_index": 400,
        "type": "provision",
        "content": {
          "id": 80,
          "slug": "1-TayN0J8b",
          "order_index": 400,
          "type_name": "subsection",
          "number": "(1)",
          "title": null,
          "level": 4,
          "parent_id": 79,
          "status": "active",
          "created_at": "2025-08-17 07:18:34",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "This Constitution is supreme and its provisions shall have binding force on the authorities and persons throughout the Federal Republic of Nigeria.",
          "division_id": null,
          "has_children": false
        },
        "children": []
      },
      {
        "order_index": 500,
        "type": "provision",
        "content": {
          "id": 81,
          "slug": "2-RzTOfsfF",
          "order_index": 500,
          "type_name": "subsection",
          "number": "(2)",
          "title": null,
          "level": 4,
          "parent_id": 79,
          "status": "active",
          "created_at": "2025-08-17 07:18:42",
          "updated_at": "2025-10-27 04:31:19",
          "provision_text": "The Federal Republic of Nigeria shall not be governed, nor shall any persons or group of persons take control of the Government of Nigeria or any part thereof, except in accordance with the provisions of this Constitution.",
          "division_id": null,
          "has_children": false
        },
        "children": []
      }
    ],
    "meta": {
      "start_order": 300,
      "end_order": 500,
      "returned": 3,
      "total_items_in_statute": 76
    }
  }
}
```

### Error Responses

**422 Validation Error - Invalid range:**
```json
{
  "status": "error",
  "message": "end_order must be greater than or equal to start_order",
  "data": null
}
```

**422 Validation Error - Missing parameters:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "start_order": [
      "The start order field is required."
    ],
    "end_order": [
      "The end order field is required."
    ]
  }
}
```

**422 Validation Error - Range too large:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "end_order": [
      "The range size may not be greater than 100."
    ]
  }
}
```

---

## Response Field Descriptions

### Position Metadata
| Field | Type | Description |
|-------|------|-------------|
| `order_index` | integer | Current position in statute's reading order |
| `total_items` | integer | Total number of items (divisions + provisions) in statute |
| `has_content_before` | boolean | Whether there is content before this position |
| `has_content_after` | boolean | Whether there is content after this position |

### Breadcrumb Item
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Content ID |
| `slug` | string | Content slug |
| `title` | string | Content title |
| `number` | string\|null | Content number (e.g., "I", "1", "(a)") |
| `type` | string | Content type: `"statute"`, `"chapter"`, `"part"`, `"section"`, `"subsection"`, etc. |
| `order_index` | integer\|null | Position in reading order (null for statute root) |

### Sequential Navigation Meta
| Field | Type | Description |
|-------|------|-------------|
| `direction` | string | Direction of navigation: `"before"` or `"after"` |
| `from_order` | integer | Starting position requested |
| `limit` | integer | Limit requested |
| `returned` | integer | Actual number of items returned |
| `has_more` | boolean | Whether more content exists in this direction |
| `next_from_order` | integer\|null | Next position for pagination (null if no more content) |

### Range Loading Meta
| Field | Type | Description |
|-------|------|-------------|
| `start_order` | integer | Start position requested |
| `end_order` | integer | End position requested |
| `returned` | integer | Actual number of items returned |
| `total_items_in_statute` | integer | Total items in the statute |

---

## Usage Examples

### Frontend Integration - Hash-First Loading

#### Scenario 1: User visits with hash (e.g., `#supremacy-of-constitution`)

```javascript
// 1. Detect hash in URL
const hash = window.location.hash.slice(1); // "supremacy-of-constitution-I5ENJmXK"

if (hash) {
  // 2. Load only the hash target
  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/${hash}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();

  // 3. Render immediately - users see content in <500ms
  renderContent(data.data.content);
  renderBreadcrumb(data.data.breadcrumb);

  // 4. Setup scroll observers for lazy loading
  setupBidirectionalLoading(data.data.position.order_index);
}
```

#### Scenario 2: User scrolls up from hash target

```javascript
// Intersection Observer triggers when approaching top
const topObserver = new IntersectionObserver((entries) => {
  if (entries[0].isIntersecting && !loadingBefore) {
    loadContentBefore(currentOrderIndex);
  }
}, { rootMargin: '200px 0px 0px 0px' });

async function loadContentBefore(fromOrder) {
  loadingBefore = true;

  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/sequential?from_order=${fromOrder}&direction=before&limit=5`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();

  // Prepend content while maintaining scroll position
  const scrollBefore = document.documentElement.scrollTop;
  const heightBefore = document.documentElement.scrollHeight;

  prependContent(data.data.items);

  const heightAfter = document.documentElement.scrollHeight;
  document.documentElement.scrollTop = scrollBefore + (heightAfter - heightBefore);

  // Update currentOrderIndex for next load
  if (data.data.meta.has_more) {
    currentOrderIndex = data.data.meta.next_from_order;
  }

  loadingBefore = false;
}
```

#### Scenario 3: User scrolls down from hash target

```javascript
// Intersection Observer triggers when approaching bottom
const bottomObserver = new IntersectionObserver((entries) => {
  if (entries[0].isIntersecting && !loadingAfter) {
    loadContentAfter(currentOrderIndex);
  }
}, { rootMargin: '0px 0px 200px 0px' });

async function loadContentAfter(fromOrder) {
  loadingAfter = true;

  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/sequential?from_order=${fromOrder}&direction=after&limit=5`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();

  // Append content to DOM
  appendContent(data.data.items);

  // Update currentOrderIndex for next load
  if (data.data.meta.has_more) {
    currentOrderIndex = data.data.meta.next_from_order;
  }

  loadingAfter = false;
}
```

#### Scenario 4: Pre-load buffer around hash target

```javascript
// Load 10 items before and after the hash target for smoother scrolling
async function loadBuffer(orderIndex) {
  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/range?start_order=${orderIndex - 1000}&end_order=${orderIndex + 1000}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();

  // Render all items in the buffer
  renderContentBuffer(data.data.items);
}
```

---

## Performance Considerations

### Caching
The API uses intelligent caching for:
- **Breadcrumb trails**: Cached for 1 hour
- **Position metadata**: Cached for 30 minutes
- **Total items count**: Cached for 1 hour

Cache is automatically invalidated when content is updated via observers.

### Optimal Query Parameters

**For initial hash-first load:**
- Include breadcrumb: `true` (for navigation)
- Include children: `true` (for context)
- Use content lookup endpoint

**For subsequent scroll loading:**
- Include children: `false` (load faster)
- Use sequential endpoint with `limit=5`
- Load more on faster connections

**For buffer loading:**
- Use range endpoint
- Load 10-20 items at once
- Include children: `false` initially, fetch children on-demand

---

## Backward Compatibility

All existing statute endpoints remain **unchanged and fully functional**:
- `GET /statutes/{slug}` - Still works
- `GET /statutes/{slug}/divisions` - Still works
- `GET /statutes/{slug}/divisions/{divisionSlug}` - Still works
- `GET /statutes/{slug}/provisions/{provisionSlug}` - Still works

The lazy loading endpoints are **additive** and don't replace existing functionality.

---

## Configuration

### Environment Variables

```env
# Statute Lazy Loading Configuration
STATUTE_LAZY_LOADING_ENABLED=true
STATUTE_DEFAULT_LIMIT=5
STATUTE_MAX_LIMIT=50
STATUTE_MAX_RANGE_SIZE=100

# Cache Configuration
STATUTE_CACHE_TAGS_ENABLED=true  # Requires Redis or Memcached
STATUTE_BREADCRUMB_TTL=3600      # 1 hour
STATUTE_POSITION_TTL=1800        # 30 minutes
STATUTE_TOTAL_ITEMS_TTL=3600     # 1 hour
```

### Production Requirements

For production deployment with caching enabled:
1. **Install Redis or Memcached**
2. **Update .env:**
   ```env
   CACHE_STORE=redis
   STATUTE_CACHE_TAGS_ENABLED=true
   ```
3. **Restart the application**

Without cache tags (development/testing):
```env
CACHE_STORE=database
STATUTE_CACHE_TAGS_ENABLED=false
```

---

## Error Handling

### Common Error Scenarios

**1. Content without order_index:**
```json
{
  "status": "error",
  "message": "Error retrieving content: Content 123 has no order_index. Run statutes:populate-order-index command.",
  "data": null
}
```

**Solution:** Run `php artisan statutes:populate-order-index`

**2. Invalid statute slug:**
```json
{
  "status": "error",
  "message": "Statute not found",
  "data": null
}
```

**3. Missing authentication:**
```json
{
  "status": "error",
  "message": "Unauthenticated.",
  "data": null
}
```

---

## Testing

### Test Endpoints

```bash
# Set your token
TOKEN="your_bearer_token_here"
BASE_URL="http://localhost:8000/api"

# Test 1: Universal Content Lookup
curl -X GET "${BASE_URL}/statutes/the-statute/content/general-provisions-7dsr1V6J" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 2: Sequential Navigation (Before)
curl -X GET "${BASE_URL}/statutes/the-statute/content/sequential?from_order=300&direction=before&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 3: Sequential Navigation (After)
curl -X GET "${BASE_URL}/statutes/the-statute/content/sequential?from_order=300&direction=after&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 4: Range Loading
curl -X GET "${BASE_URL}/statutes/the-statute/content/range?start_order=100&end_order=500" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

---

## Additional Resources

- **Implementation Guide:** See `statute-lazy-loading-implementation.md`
- **API Specification:** See `statute-lazy-loading-api-spec.md`
- **Test Suite Documentation:** See `STATUTE_LAZY_LOADING_TESTS.md`
- **Frontend Implementation:** See `Docs/v2/frontend/STATUTE_FRONTEND_IMPLEMENTATION.md`

---

## Support

For issues or questions about the Statute Lazy Loading API:
- Check the test suite documentation for comprehensive examples
- Review the implementation guide for technical details
- Contact the backend team for performance optimization
- Contact the frontend team for integration assistance
