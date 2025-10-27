
# Statute Lazy Loading API Specification

**Version:** 1.0
**Date:** 2025-10-27
**Status:** Proposed

---

## Executive Summary

### Problem Statement

The current statute viewing system loads all divisions, provisions, and their nested children before rendering the page. When a user visits a statute with a hash fragment (e.g., `#the-legislature`), the frontend must:

1. Load all top-level divisions
2. Recursively load children until the hash target is found
3. Render all loaded content
4. Scroll to the hash target

**Issues with this approach:**

- **Poor UX**: Users see a loading spinner while potentially hundreds of divisions load
- **Inaccurate Analytics**: All loaded divisions/provisions get view counts, even though users only view the hash target section
- **Wasted Resources**: Loading content that users never see
- **Slow Performance**: Large statutes (e.g., Constitution with 300+ sections) take seconds to load

### Proposed Solution

Implement a **hash-first lazy loading system** that:

1. **Loads only the hash target** when URL contains a hash fragment
2. **Renders immediately** - users see content in <500ms instead of 3-5 seconds
3. **Lazy loads bidirectionally** - loads content above/below as user scrolls


### Benefits

- **10x faster perceived load time** for hash-based navigation
- **90% reduction in initial API calls** for deep-linked content
- **Better SEO** - faster Time to First Contentful Paint
- **Improved scalability** - handles massive statutes efficiently

---

## Required API Endpoints

### Overview

Three new endpoints are required to support hash-first lazy loading:

| Endpoint | Purpose | Priority |
|----------|---------|----------|
| `GET /statutes/{slug}/content/{contentSlug}` | Look up any division/provision by slug | **Critical** |
| `GET /statutes/{slug}/content/sequential` | Load content before/after a position | **Critical** |
| `GET /statutes/{slug}/content/range` | Batch load content by position range | Optional |

---

## 1. Universal Content Lookup Endpoint

### Purpose

Look up any division or provision by its slug without knowing its type or hierarchy level. This is the **foundation** of hash-first loading.

### Endpoint

```
GET /statutes/{statuteSlug}/content/{contentSlug}
```

### Authentication

- **Required:** Bearer token (same as existing statute endpoints)
- **Roles:** All authenticated users, including guests

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statuteSlug` | string | Yes | Statute identifier slug |
| `contentSlug` | string | Yes | Division or provision slug to look up |

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `include_children` | boolean | No | `true` | Include immediate child divisions/provisions |
| `include_breadcrumb` | boolean | No | `true` | Include full breadcrumb trail from statute root |
| `include_siblings` | boolean | No | `false` | Include sibling content at same level |

### Response Format

**Status Code:** `200 OK`

```json
{
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "division",
    "content": {
      "id": 45,
      "slug": "the-legislature",
      "statute_id": 1,
      "parent_id": 12,
      "division_type": "part",
      "division_number": "V",
      "division_title": "The Legislature",
      "description": "Provisions relating to the National Assembly",
      "level": 3,
      "order_index": 156,
      "status": "active",
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    },
    "breadcrumb": [
      {
        "id": 1,
        "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
        "title": "Constitution of the Federal Republic of Nigeria, 1999",
        "type": "statute",
        "order_index": null
      },
      {
        "id": 5,
        "slug": "chapter-v",
        "title": "Chapter V",
        "number": "V",
        "type": "chapter",
        "order_index": 120
      },
      {
        "id": 45,
        "slug": "the-legislature",
        "title": "The Legislature",
        "number": "V",
        "type": "part",
        "order_index": 156
      }
    ],
    "children": [
      {
        "id": 201,
        "slug": "establishment-of-the-national-assembly",
        "division_type": "section",
        "division_number": "47",
        "division_title": "Establishment of the National Assembly",
        "order_index": 157,
        "has_children": true,
        "child_count": 3
      },
      {
        "id": 202,
        "slug": "composition-of-the-national-assembly",
        "division_type": "section",
        "division_number": "48",
        "division_title": "Composition of the National Assembly",
        "order_index": 158,
        "has_children": false
      }
    ],
    "position": {
      "order_index": 156,
      "total_items": 523,
      "has_content_before": true,
      "has_content_after": true
    }
  }
}
```

### Response for Provision

When `contentSlug` references a provision instead of a division:

```json
{
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "provision",
    "content": {
      "id": 1205,
      "slug": "section-47-subsection-1",
      "statute_id": 1,
      "parent_id": null,
      "division_id": 201,
      "provision_type": "subsection",
      "provision_number": "1",
      "provision_title": null,
      "provision_text": "There shall be a National Assembly for the Federation which shall consist of a Senate and a House of Representatives.",
      "level": 1,
      "order_index": 285,
      "status": "active",
      "has_children": false,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    },
    "breadcrumb": [
      {
        "id": 1,
        "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
        "title": "Constitution of the Federal Republic of Nigeria, 1999",
        "type": "statute"
      },
      {
        "id": 5,
        "slug": "chapter-v",
        "title": "Chapter V",
        "type": "chapter",
        "order_index": 120
      },
      {
        "id": 45,
        "slug": "the-legislature",
        "title": "The Legislature",
        "type": "part",
        "order_index": 156
      },
      {
        "id": 201,
        "slug": "establishment-of-the-national-assembly",
        "title": "Establishment of the National Assembly",
        "number": "47",
        "type": "section",
        "order_index": 157
      },
      {
        "id": 1205,
        "slug": "section-47-subsection-1",
        "number": "1",
        "type": "subsection",
        "order_index": 285
      }
    ],
    "children": [],
    "position": {
      "order_index": 285,
      "total_items": 523,
      "has_content_before": true,
      "has_content_after": true
    }
  }
}
```

### Error Responses

**404 Not Found** - Content doesn't exist:
```json
{
  "status": "error",
  "message": "Content not found",
  "data": {
    "statute_slug": "constitution-of-the-federal-republic-of-nigeria-1999",
    "content_slug": "invalid-slug"
  }
}
```

**404 Not Found** - Statute doesn't exist:
```json
{
  "status": "error",
  "message": "Statute not found",
  "data": {
    "statute_slug": "invalid-statute"
  }
}
```

**401 Unauthorized** - No authentication:
```json
{
  "status": "error",
  "message": "Authentication required",
  "data": null
}
```

### Request Examples

**Basic lookup:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/the-legislature"
```

**Lookup without children (faster):**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/the-legislature?include_children=false"
```

**Lookup with siblings:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/the-legislature?include_siblings=true"
```

### Implementation Notes

1. **Search both tables**: Query both `divisions` and `provisions` tables by slug
2. **Include statute validation**: Ensure content belongs to the requested statute
3. **Optimize breadcrumb generation**: Use recursive CTE or cached hierarchy
4. **Return position metadata**: Essential for sequential navigation
5. **Support nested children**: When `include_children=true`, include immediate children with their metadata

---

## 2. Sequential Content Navigation Endpoint

### Purpose

Load content sequentially before or after a given position. This enables bidirectional lazy loading as users scroll.

### Endpoint

```
GET /statutes/{statuteSlug}/content/sequential
```

### Authentication

- **Required:** Bearer token
- **Roles:** All authenticated users, including guests

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statuteSlug` | string | Yes | Statute identifier slug |

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `from_order` | integer | Yes | - | Starting position (order_index) |
| `direction` | string | Yes | - | `"before"` or `"after"` |
| `limit` | integer | No | `5` | Number of items to return (max: 50) |
| `include_children` | boolean | No | `true` | Include immediate children for divisions |

### Response Format

**Status Code:** `200 OK`

```json
{
  "status": "success",
  "message": "Sequential content retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": 155,
        "type": "division",
        "content": {
          "id": 44,
          "slug": "fundamental-rights",
          "division_type": "part",
          "division_number": "IV",
          "division_title": "Fundamental Rights",
          "level": 3,
          "has_children": true,
          "child_count": 15
        },
        "children": [
          {
            "id": 180,
            "slug": "right-to-life",
            "division_type": "section",
            "division_number": "33",
            "division_title": "Right to Life",
            "order_index": 156
          }
        ]
      },
      {
        "order_index": 154,
        "type": "division",
        "content": {
          "id": 43,
          "slug": "citizenship",
          "division_type": "part",
          "division_number": "III",
          "division_title": "Citizenship",
          "level": 3,
          "has_children": true,
          "child_count": 8
        },
        "children": []
      }
    ],
    "meta": {
      "direction": "before",
      "from_order": 156,
      "limit": 5,
      "returned": 2,
      "has_more": true,
      "next_from_order": 153
    }
  }
}
```

### Error Responses

**422 Validation Error** - Invalid parameters:
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "errors": {
    "direction": ["The direction field must be either 'before' or 'after'."],
    "from_order": ["The from order field is required."],
    "limit": ["The limit may not be greater than 50."]
  }
}
```

**404 Not Found** - Statute doesn't exist:
```json
{
  "status": "error",
  "message": "Statute not found",
  "data": {
    "statute_slug": "invalid-statute"
  }
}
```

### Request Examples

**Load 5 items before position 156:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/sequential?from_order=156&direction=before&limit=5"
```

**Load 10 items after position 200:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/sequential?from_order=200&direction=after&limit=10"
```

**Load items without children (faster):**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/sequential?from_order=156&direction=before&limit=5&include_children=false"
```



## 3. Batch Content Range Endpoint (Optional)

### Purpose

Load a range of content by position indices. Useful for loading a "buffer" of content around the hash target.

### Endpoint

```
GET /statutes/{statuteSlug}/content/range
```

### Authentication

- **Required:** Bearer token
- **Roles:** All authenticated users, including guests

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `statuteSlug` | string | Yes | Statute identifier slug |

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start_order` | integer | Yes | - | Start position (inclusive) |
| `end_order` | integer | Yes | - | End position (inclusive) |
| `include_children` | boolean | No | `true` | Include immediate children for divisions |

### Response Format

**Status Code:** `200 OK`

```json
{
  "status": "success",
  "message": "Content range retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": 154,
        "type": "division",
        "content": {
          "id": 43,
          "slug": "citizenship",
          "division_type": "part",
          "division_number": "III",
          "division_title": "Citizenship",
          "level": 3
        }
      },
      {
        "order_index": 155,
        "type": "division",
        "content": {
          "id": 44,
          "slug": "fundamental-rights",
          "division_type": "part",
          "division_number": "IV",
          "division_title": "Fundamental Rights",
          "level": 3
        }
      },
      {
        "order_index": 156,
        "type": "division",
        "content": {
          "id": 45,
          "slug": "the-legislature",
          "division_type": "part",
          "division_number": "V",
          "division_title": "The Legislature",
          "level": 3
        }
      }
    ],
    "meta": {
      "start_order": 154,
      "end_order": 156,
      "returned": 3,
      "total_items_in_statute": 523
    }
  }
}
```

### Error Responses

**422 Validation Error**:
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "errors": {
    "end_order": ["The end order must be greater than or equal to start order."],
    "start_order": ["The start order field is required."]
  }
}
```

### Request Examples

**Load content from position 150 to 160:**
```bash
curl -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "https://rest.lawexa.com/api/statutes/constitution-of-the-federal-republic-of-nigeria-1999/content/range?start_order=150&end_order=160"
```

### Implementation Notes

1. **Validate range**: Ensure `end_order >= start_order`
2. **Limit range size**: Consider max range of 50-100 items
3. **Same union query**: Similar to sequential endpoint but with range filter
4. **Order by index**: Always return in ascending order

---

## Database Schema Requirements

### Overview

To support sequential content navigation, both `divisions` and `provisions` tables need an `order_index` column that represents their position in the statute's reading order.



### Order Index Calculation

The `order_index` should represent the sequential reading order of content within a statute. It should:

1. **Start at 1** for the first top-level division
2. **Increment sequentially** through all content
3. **Follow document hierarchy** (parent before children)
4. **Include all levels** (divisions and provisions)

### Example Order Index Values

For a statute structure like:

```
Constitution (statute)
  ├─ Chapter I (order_index: 1)
  │   ├─ Section 1 (order_index: 2)
  │   │   ├─ Subsection 1a (order_index: 3)
  │   │   └─ Subsection 1b (order_index: 4)
  │   └─ Section 2 (order_index: 5)
  ├─ Chapter II (order_index: 6)
  │   └─ Section 3 (order_index: 7)
  └─ Chapter III (order_index: 8)
```


## Frontend Integration Guide

### Typical User Flow

#### Scenario 1: User visits with hash (e.g., `#the-legislature`)

1. **Frontend detects hash** in URL
2. **Call lookup endpoint**:
   ```javascript
   GET /statutes/{slug}/content/the-legislature
   ```
3. **Render immediately** - Show the target section with breadcrumb
4. **Show scroll indicators** - "Content above" and "Content below"
5. **Setup scroll observers**:
   - When user scrolls near top → load previous content
   - When user scrolls near bottom → load next content

#### Scenario 2: User scrolls up from hash target

1. **Intersection Observer triggers** when approaching top
2. **Call sequential endpoint**:
   ```javascript
   GET /content/sequential?from_order=156&direction=before&limit=5
   ```
3. **Prepend content** to DOM
4. **Maintain scroll position** - Prevent jump
5. **Update scroll observer** - Attach to new top element

#### Scenario 3: User scrolls down from hash target

1. **Intersection Observer triggers** when approaching bottom
2. **Call sequential endpoint**:
   ```javascript
   GET /content/sequential?from_order=156&direction=after&limit=5
   ```
3. **Append content** to DOM
4. **Continue observing** - For infinite scroll

### Frontend Code Example

```javascript
// 1. Initial load with hash
const hash = window.location.hash.slice(1); // "the-legislature"

if (hash) {
  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/${hash}`,
    { headers: { Authorization: `Bearer ${token}` } }
  );

  const data = await response.json();

  // Render immediately
  renderContent(data.data.content);
  renderBreadcrumb(data.data.breadcrumb);

  // Setup bidirectional loading
  setupScrollObservers(data.data.position.order_index);
}

// 2. Bidirectional scroll loading
function setupScrollObservers(currentOrderIndex) {
  // Observe top element for upward loading
  const topObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      loadContentBefore(currentOrderIndex);
    }
  }, { rootMargin: '200px 0px 0px 0px' });

  topObserver.observe(document.querySelector('[data-first-item]'));

  // Observe bottom element for downward loading
  const bottomObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      loadContentAfter(currentOrderIndex);
    }
  }, { rootMargin: '0px 0px 200px 0px' });

  bottomObserver.observe(document.querySelector('[data-last-item]'));
}

// 3. Load content before
async function loadContentBefore(fromOrder) {
  const response = await fetch(
    `/api/statutes/${statuteSlug}/content/sequential?from_order=${fromOrder}&direction=before&limit=5`,
    { headers: { Authorization: `Bearer ${token}` } }
  );

  const data = await response.json();

  // Prepend to DOM while maintaining scroll
  const scrollBefore = document.documentElement.scrollTop;
  const heightBefore = document.documentElement.scrollHeight;

  prependContent(data.data.items);

  const heightAfter = document.documentElement.scrollHeight;
  document.documentElement.scrollTop = scrollBefore + (heightAfter - heightBefore);
}
```

---

## Backward Compatibility

### Existing Endpoints

All current endpoints remain **unchanged and fully functional**:

- `GET /statutes/{slug}` - Still works
- `GET /statutes/{slug}/divisions` - Still works
- `GET /statutes/{slug}/divisions/{divisionSlug}` - Still works
- `GET /statutes/{slug}/divisions/{divisionSlug}/children` - Still works
- `GET /statutes/{slug}/divisions/{divisionSlug}/provisions` - Still works
- `GET /statutes/{slug}/provisions/{provisionSlug}` - Still works
- `GET /statutes/{slug}/provisions/{provisionSlug}/children` - Still works

### Gradual Migration

1. **Backend implements new endpoints** - Frontend continues using old approach
2. **Frontend A/B tests new approach** - Roll out to 10% of users
3. **Monitor performance and analytics** - Verify improvements
4. **Full rollout** - Switch all users to new approach
5. **Deprecate old approach** - Eventually remove old loading logic

### No Breaking Changes

- New `order_index` column is **nullable** initially
- Existing queries don't break if `order_index` is NULL
- New endpoints are **additive** - don't replace existing ones

---

## Testing Checklist

### Backend Testing

- [ ] Universal lookup finds divisions by slug
- [ ] Universal lookup finds provisions by slug
- [ ] Lookup returns correct breadcrumb trail
- [ ] Lookup returns correct position metadata
- [ ] Lookup validates statute ownership
- [ ] Sequential endpoint loads content before position
- [ ] Sequential endpoint loads content after position
- [ ] Sequential endpoint respects limit parameter
- [ ] Sequential endpoint handles edge cases (start/end of statute)
- [ ] Range endpoint loads correct range
- [ ] Range endpoint validates start <= end
- [ ] All endpoints return proper error responses
- [ ] Order index maintained correctly on content updates
- [ ] Queries perform efficiently with proper indexes

### Frontend Testing

- [ ] Hash navigation loads target content immediately
- [ ] Breadcrumb renders correctly
- [ ] Scroll up loads previous content
- [ ] Scroll down loads next content
- [ ] Scroll position maintained when prepending content
- [ ] No duplicate content rendered
- [ ] Works with nested children
- [ ] Analytics track only rendered content
- [ ] Works without hash (normal mode)
- [ ] Legacy URLs redirect properly

---

## Summary

This specification provides everything the backend team needs to implement hash-first lazy loading for statutes:

1. **Three new API endpoints** for content lookup and sequential navigation
2. **Database schema changes** to support sequential ordering
3. **Detailed request/response formats** for each endpoint
4. **Migration strategy** that doesn't break existing functionality
5. **Frontend integration examples** showing how endpoints will be consumed

The implementation will result in:
- **10x faster** perceived load times for deep-linked content
- **100% accurate** view analytics
- **Better UX** with immediate content rendering
- **Improved scalability** for large statutes

---

**Questions or Clarifications:**

Contact frontend team for:
- Frontend implementation details
- Analytics integration requirements
- UI/UX considerations

Contact backend team for:
- Database optimization strategies
- Caching layer implementation
- Load testing and performance tuning
