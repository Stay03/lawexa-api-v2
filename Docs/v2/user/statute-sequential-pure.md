# Sequential Pure Content API - User Endpoint

## Overview
The Sequential Pure Content API provides an optimized, flat-structure endpoint for lazy loading statute content with bidirectional navigation. This endpoint is specifically designed for infinite scroll implementations and delivers statute content in a pure, unnested format ideal for modern frontend rendering.

### Key Benefits
- **Pure flat structure** - All fields at root level (no nested objects except optional breadcrumb)
- **Type-agnostic rendering** - Same field structure for both divisions and provisions
- **Smaller payloads** - 60% smaller response size when breadcrumbs are excluded
- **Optimal for lazy loading** - Designed for infinite scroll with minimal overhead
- **Bidirectional navigation** - Load content before or after any position
- **Flexible pagination** - Complete metadata for seamless infinite scroll

## Base URL
```
https://rest.lawexa.com/api
```
For local development:
```
http://localhost:8000/api
```

## Authentication
This endpoint requires authentication. Both authenticated users and guest users can access this endpoint.

### Authentication Headers (Required)
```http
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

---

## Endpoint

### GET `/statutes/{statute_slug}/content/sequential-pure`

Load statute content sequentially in a pure flat structure format, ideal for lazy loading and infinite scroll implementations.

**Access:** Authenticated users (including guests)

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statute_slug` | string | Yes | Statute identifier slug |

### Query Parameters

| Parameter | Type | Required | Default | Max | Description |
|-----------|------|----------|---------|-----|-------------|
| `from_order` | integer | **Yes*** | - | - | Starting order_index position |
| `from_slug` | string | **Yes*** | - | - | Starting content slug (alternative to from_order) |
| `direction` | string | **Yes** | - | - | Navigation direction: `"before"` or `"after"` |
| `limit` | integer | No | `15` | `50` | Number of items to return |
| `include_breadcrumb` | boolean | No | `true` | - | Include full breadcrumb trail |

**\* Required:** Either `from_order` OR `from_slug` must be provided (not both).

#### Parameter Details

**`from_order`** (conditionally required)
- The `order_index` position to load content from
- Use `0` to start from the beginning
- Use the `order_index` from hash lookup to start from a specific position
- Must be a non-negative integer
- **Cannot be used together with `from_slug`**

**`from_slug`** (conditionally required) - **NEW**
- The slug of the content item to load from
- Backend automatically resolves slug to order_index
- Example: `"section-1"`, `"chapter-i"`, `"3-KPe5tDot"`
- **Benefits:** Single API call instead of two (no need to lookup order_index first)
- **Use case:** Hash-based navigation, deep linking
- **Cannot be used together with `from_order`**
- Returns 404 error if slug not found

**`direction`** (required)
- `"after"`: Load items with order_index > from_order (scroll down, forward, excludes cursor)
- `"before"`: Load items with order_index < from_order (scroll up, backward, excludes cursor)
- `"at"`: Load items with order_index >= from_order (includes cursor item, then forward)

**`limit`** (optional)
- Default: `15` items
- Maximum: `50` items (enforced by backend)
- Values exceeding 50 are automatically capped at 50
- Recommended: Use 10-20 for optimal performance

**`include_breadcrumb`** (optional)
- Default: `true`
- Set to `false` for lazy loading to reduce response size by ~60%
- Recommended: `true` for initial/hash loads, `false` for scroll loads

---

## Response Structure

### Success Response (200 OK)

```json
{
  "status": "success",
  "message": "Sequential content retrieved successfully",
  "data": {
    "items": [
      {
        "id": 375,
        "slug": "general-provisions-7dsr1V6J",
        "type": "division",

        "division_type": "chapter",
        "division_number": "I",
        "division_title": "General Provisions",
        "division_subtitle": null,
        "content": null,

        "provision_type": null,
        "provision_number": null,
        "provision_title": null,
        "provision_text": null,
        "marginal_note": null,
        "interpretation_note": null,

        "level": 1,
        "parent_division_id": null,
        "parent_provision_id": null,

        "order_index": 100,
        "has_children": true,
        "child_count": 2,

        "status": "active",
        "effective_date": null,
        "created_at": "2025-08-17 07:17:35",
        "updated_at": "2025-10-27 11:41:09",

        "breadcrumb": [
          {
            "id": 18,
            "type": "statute",
            "slug": "the-statute",
            "title": "The statute",
            "order_index": null
          },
          {
            "id": 375,
            "type": "division",
            "slug": "general-provisions-7dsr1V6J",
            "division_type": "chapter",
            "division_number": "I",
            "division_title": "General Provisions",
            "level": null,
            "order_index": 100
          }
        ]
      }
    ],
    "meta": {
      "format": "sequential_pure",
      "direction": "after",
      "from_order": 0,
      "limit": 15,
      "returned": 1,
      "has_more": true,
      "next_from_order": 100
    }
  }
}
```

### Response Field Descriptions

#### Item Fields (Pure Flat Structure)

**Identity:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier |
| `slug` | string | URL-friendly identifier |
| `type` | string | Content type: `"division"` or `"provision"` |

**Division Fields** (null if type=provision):
| Field | Type | Description |
|-------|------|-------------|
| `division_type` | string\|null | Type: `"chapter"`, `"part"`, etc. |
| `division_number` | string\|null | Number: `"I"`, `"II"`, `"1"`, etc. |
| `division_title` | string\|null | Title of the division |
| `division_subtitle` | string\|null | Subtitle (if any) |
| `content` | string\|null | Additional content text |

**Provision Fields** (null if type=division):
| Field | Type | Description |
|-------|------|-------------|
| `provision_type` | string\|null | Type: `"section"`, `"subsection"`, etc. |
| `provision_number` | string\|null | Number: `"1"`, `"(1)"`, `"(a)"`, etc. |
| `provision_title` | string\|null | Title of the provision |
| `provision_text` | string\|null | Full text content |
| `marginal_note` | string\|null | Marginal notes |
| `interpretation_note` | string\|null | Interpretation notes |

**Hierarchy:**
| Field | Type | Description |
|-------|------|-------------|
| `level` | integer | Depth level in hierarchy (1=top level) |
| `parent_division_id` | integer\|null | Parent division ID (if nested in a division) |
| `parent_provision_id` | integer\|null | Parent provision ID (if nested in a provision) |

**Position:**
| Field | Type | Description |
|-------|------|-------------|
| `order_index` | integer | Sequential position in reading order |
| `has_children` | boolean | Whether this item has child items |
| `child_count` | integer | Number of direct children |

**Metadata:**
| Field | Type | Description |
|-------|------|-------------|
| `status` | string | Status: `"active"`, `"inactive"`, etc. |
| `effective_date` | string\|null | Effective date (ISO 8601) |
| `created_at` | string | Creation timestamp |
| `updated_at` | string | Last update timestamp |

**Optional:**
| Field | Type | Description |
|-------|------|-------------|
| `breadcrumb` | array\|null | Full ancestor trail (only if `include_breadcrumb=true`) |

#### Breadcrumb Structure

Each breadcrumb item contains:
```json
{
  "id": 18,
  "type": "statute",
  "slug": "the-statute",
  "title": "The statute",
  "order_index": 100
}
```

For divisions, additional fields:
- `division_type`: e.g., `"chapter"`, `"part"`
- `division_number`: e.g., `"I"`, `"1"`
- `division_title`: Full title

For provisions, additional fields:
- `provision_type`: e.g., `"section"`, `"subsection"`
- `provision_number`: e.g., `"1"`, `"(1)"`
- `provision_title`: Full title (may be null)

#### Meta Fields

| Field | Type | Description |
|-------|------|-------------|
| `format` | string | Always `"sequential_pure"` |
| `direction` | string | Direction used: `"before"` or `"after"` |
| `from_order` | integer | Starting position requested |
| `from_slug` | string | (Optional) The slug used for navigation (only present when using `from_slug` parameter) |
| `resolved_order_index` | integer | (Optional) The order_index resolved from the slug (only present when using `from_slug` parameter) |
| `limit` | integer | Limit requested (capped at 50) |
| `returned` | integer | Actual number of items returned |
| `has_more` | boolean | Whether more content exists in this direction |
| `next_from_order` | integer\|null | Next position for pagination (null if no more content) |

---

## Example Requests

### 1. Initial Load from Beginning

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential-pure?from_order=0&direction=after&limit=15" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User views statute from start, no hash in URL

### 2. Hash Navigation (Load from Specific Position - Using from_order)

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/constitution-1999/content/sequential-pure?from_order=400&direction=after&limit=15" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User visits URL with hash `#section-1`, load content from that position with full context
**Note:** Requires 2 API calls - first lookup order_index, then load content

### 3. Hash Navigation (Using from_slug) - **NEW & RECOMMENDED**

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/constitution-1999/content/sequential-pure?from_slug=section-1&direction=after&limit=15" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User visits URL with hash `#section-1`, load content directly using slug
**Benefits:**
- Only 1 API call (50% faster)
- Backend resolves slug automatically
- Simpler frontend code

**Response includes:**
```json
{
  "data": {
    "items": [...],
    "meta": {
      "format": "sequential_pure",
      "direction": "after",
      "from_order": 400,
      "from_slug": "section-1",
      "resolved_order_index": 400,
      "limit": 15,
      "returned": 15,
      "has_more": true,
      "next_from_order": 1500
    }
  }
}
```

### 4. Scroll Down (Infinite Scroll)

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential-pure?from_order=500&direction=after&limit=15&include_breadcrumb=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User scrolls to bottom, load next batch without breadcrumb overhead

### 5. Scroll Up (Reverse Infinite Scroll)

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential-pure?from_order=400&direction=before&limit=10" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User scrolls to top, load previous batch

### 6. Small Batch Load (Mobile)

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/the-statute/content/sequential-pure?from_order=300&direction=after&limit=5&include_breadcrumb=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** Mobile device with limited bandwidth, load small batches

### 7. Hash Navigation with Deep Linking (Using direction: 'at') - **RECOMMENDED**

```bash
curl -X GET "http://127.0.0.1:8000/api/statutes/constitution-1999/content/sequential-pure?from_slug=section-1&direction=at&limit=15" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}"
```

**Use Case:** User visits URL with hash `#section-1`, load content starting FROM that section (including it)
**Benefits:**
- Only 1 API call (no separate lookup needed)
- Target section is included in results (not skipped)
- Perfect for deep linking and bookmarks

**Comparison with `direction: 'after'`:**
- `direction: 'after'` - Excludes section-1, shows content AFTER it
- `direction: 'at'` - Includes section-1, shows content FROM it onwards

---

## Error Responses

### 422 Validation Error - Missing Required Parameters

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

### 422 Validation Error - Invalid Direction

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

### 422 Validation Error - Invalid Limit

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "limit": [
      "The limit field must be at least 1."
    ]
  }
}
```

### 404 Not Found - Statute Doesn't Exist

```json
{
  "status": "error",
  "message": "Endpoint not found",
  "data": null
}
```

### 401 Unauthorized - No Authentication

```json
{
  "status": "error",
  "message": "Unauthenticated.",
  "data": null
}
```

### 404 Not Found - Slug Not Found (from_slug parameter)

```json
{
  "status": "error",
  "message": "Content with slug 'nonexistent-slug' not found in this statute",
  "data": null,
  "errors": {
    "from_slug": [
      "The specified slug does not exist in this statute."
    ]
  }
}
```

### 422 Validation Error - Both from_order and from_slug Provided

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "from_slug": [
      "Cannot use both from_order and from_slug. Please provide only one."
    ]
  }
}
```

### 422 Validation Error - Neither from_order nor from_slug Provided

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "from_order": [
      "The from order field is required when from slug is not present."
    ],
    "from_slug": [
      "The from slug field is required when from order is not present."
    ]
  }
}
```

---

## Performance Considerations

### Response Sizes

**With breadcrumb (`include_breadcrumb=true`):**
- Approximately **15KB for 15 items**
- Use for: Initial page load, hash navigation

**Without breadcrumb (`include_breadcrumb=false`):**
- Approximately **6KB for 15 items** (60% smaller)
- Use for: Scroll-based lazy loading, mobile devices

### Recommended Usage Patterns

**Initial Load from Beginning:**
```
from_order: 0
direction: "after"
limit: 15-20
include_breadcrumb: true
```

**Hash Navigation / Deep Linking (RECOMMENDED):**
```
from_slug: <target slug>
direction: "at"
limit: 15-20
include_breadcrumb: true
```
Use `direction: "at"` to include the target section in results. This is the recommended approach for hash-based navigation and deep linking.

**Scroll Down (Lazy Load):**
```
from_order: <last visible order_index>
direction: "after"
limit: 10-15
include_breadcrumb: false
```

**Scroll Up (Reverse Lazy Load):**
```
from_order: <first visible order_index>
direction: "before"
limit: 10-15
include_breadcrumb: false
```

**Mobile / Low Bandwidth:**
```
limit: 5-10
include_breadcrumb: false
```

### Pagination Strategy

Use `meta.next_from_order` for seamless pagination:
- Check `meta.has_more` to determine if more content is available
- Use `meta.next_from_order` as the `from_order` parameter for the next request
- When `has_more` is `false`, you've reached the end of content in that direction

---

## Comparison with Sequential Endpoint

| Feature | `/content/sequential` | `/content/sequential-pure` |
|---------|----------------------|---------------------------|
| Structure | Nested (has child arrays) | Pure flat (no child arrays) |
| Response Size | Larger | 40-60% smaller |
| Division Fields | Inside nested object | At root level |
| Provision Fields | Inside nested object | At root level |
| Breadcrumb | Optional | Optional |
| Type Consistency | Different for division/provision | Same structure for both |
| Best For | Tree rendering | List/scroll rendering |
| Mobile Friendly | Good | Excellent |

---

## Key Features

✅ **Pure Flat Structure** - All fields at root level, no unwrapping needed
✅ **Type-Agnostic Fields** - Same field structure for divisions and provisions
✅ **Optimized Payloads** - 60% smaller when breadcrumbs excluded
✅ **Bidirectional Navigation** - Load before or after any position
✅ **Flexible Pagination** - Complete metadata for infinite scroll
✅ **Optional Breadcrumbs** - Include only when needed
✅ **Proper Validation** - Clear error messages for all scenarios
✅ **Limit Enforcement** - Maximum 50 items per request
✅ **Hash-First Compatible** - Works seamlessly with hash navigation

---

## Testing Commands

```bash
# Set your environment
export API_URL="http://127.0.0.1:8000/api"
export TOKEN="your_bearer_token_here"

# Test 1: Load from beginning
curl -X GET "${API_URL}/statutes/the-statute/content/sequential-pure?from_order=0&direction=after&limit=5" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 2: Load before a position
curl -X GET "${API_URL}/statutes/the-statute/content/sequential-pure?from_order=500&direction=before&limit=3" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 3: Load after with no breadcrumb
curl -X GET "${API_URL}/statutes/the-statute/content/sequential-pure?from_order=300&direction=after&limit=10&include_breadcrumb=false" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 4: Validation error (missing parameters)
curl -X GET "${API_URL}/statutes/the-statute/content/sequential-pure" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"

# Test 5: Limit enforcement (request 100, capped at 50)
curl -X GET "${API_URL}/statutes/the-statute/content/sequential-pure?from_order=0&direction=after&limit=100" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

---

## Additional Resources

- **Related Endpoints:**
  - Universal Content Lookup: See `statute-lazyload.md`
  - Sequential Navigation (nested format): See `statute-lazyload.md#2-sequential-content-navigation`
  - Range Loading: See `statute-lazyload.md#3-batch-content-range-loading`

- **Implementation Details:**
  - Implementation Summary: `SEQUENTIAL_PURE_IMPLEMENTATION_SUMMARY.md`
  - Test Results: `TEST_RESULTS_SUMMARY.md`

---

## Support

For issues or questions about the Sequential Pure Content API:
- Check the implementation summary for technical details
- Review the statute-lazyload.md documentation for related endpoints
- Contact the backend team for performance optimization
- Contact the frontend team for integration assistance
