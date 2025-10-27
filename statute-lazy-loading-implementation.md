# Statute Lazy Loading System - Implementation Guide

**Version:** 1.0
**Date:** 2025-10-27
**Status:** Implementation Ready
**Target:** Backend API (Laravel)

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Architecture Design](#architecture-design)
3. [Data Flow](#data-flow)
4. [Core Components](#core-components)
5. [Database Architecture](#database-architecture)
6. [API Endpoint Architecture](#api-endpoint-architecture)
7. [Caching Strategy](#caching-strategy)
8. [Order Index Management](#order-index-management)
9. [Integration Points](#integration-points)
10. [Performance Considerations](#performance-considerations)
11. [Migration Strategy](#migration-strategy)

---

## System Overview

### Purpose

Transform the statute viewing system from **full-load architecture** to **hash-first lazy loading architecture** to achieve:

- 10x faster perceived load times (500ms vs 5 seconds)
- 90% reduction in initial data transfer
- Accurate view analytics (only count viewed content)
- Infinite scalability for large statutes

### Current Architecture Problems

```
User Request → Load Statute → Load ALL Divisions (Recursive)
             → Load ALL Provisions → Render Everything
             → Scroll to Hash Target

Timeline: 3-5 seconds for large statutes
Data Transfer: 500KB+ for Constitution
Analytics: All content counted as "viewed"
```

### New Architecture Vision

```
User Request → Detect Hash → Lookup Hash Target Directly
             → Return ONLY Target + Metadata → Render Immediately
             → User Scrolls → Load More Content on Demand

Timeline: <500ms for initial render
Data Transfer: 5-10KB for initial load
Analytics: Only viewed content tracked
```

---

## Architecture Design

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend Layer                          │
│  - Hash Detection                                            │
│  - Scroll Observers (Bidirectional)                          │
│  - Content Buffering                                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                      API Gateway                             │
│  - Authentication Middleware                                 │
│  - Rate Limiting                                             │
│  - Request Validation                                        │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Lazy Loading Controller Layer                   │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Universal   │  │  Sequential  │  │    Range     │     │
│  │   Content    │  │  Navigation  │  │   Loading    │     │
│  │   Lookup     │  │   Endpoint   │  │   Endpoint   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                              │
│                                                              │
│  ┌────────────────────┐      ┌────────────────────┐        │
│  │  Content Resolver  │      │  Breadcrumb        │        │
│  │  Service           │      │  Builder Service   │        │
│  │                    │      │                    │        │
│  │  - Slug Lookup     │      │  - Cache-Aware     │        │
│  │  - Type Detection  │      │  - Recursive Build │        │
│  │  - Position Calc   │      │  - Tag Management  │        │
│  └────────────────────┘      └────────────────────┘        │
│                                                              │
│  ┌────────────────────┐      ┌────────────────────┐        │
│  │  Sequential        │      │  Order Index       │        │
│  │  Navigator Service │      │  Manager Service   │        │
│  │                    │      │                    │        │
│  │  - Before/After    │      │  - Gap Management  │        │
│  │  - Union Queries   │      │  - Recalculation   │        │
│  │  - Pagination      │      │  - Validation      │        │
│  └────────────────────┘      └────────────────────┘        │
│                                                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                    Cache Layer                               │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Cache Strategy                                     │    │
│  │  - Breadcrumbs: 1 hour TTL, tag-based              │    │
│  │  - Position Metadata: 30 min TTL                    │    │
│  │  - Content Ranges: 15 min TTL                       │    │
│  │  - Invalidation: Observer-based                     │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Repository Layer                            │
│                                                              │
│  ┌──────────────────┐  ┌──────────────────┐               │
│  │  Division        │  │  Provision       │               │
│  │  Repository      │  │  Repository      │               │
│  │                  │  │                  │               │
│  │  - By Slug       │  │  - By Slug       │               │
│  │  - By Order      │  │  - By Order      │               │
│  │  - Range Queries │  │  - Range Queries │               │
│  └──────────────────┘  └──────────────────┘               │
│                                                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐        │
│  │  statute_divisions   │  │  statute_provisions  │        │
│  │                      │  │                      │        │
│  │  + order_index       │  │  + order_index       │        │
│  │  + indexes           │  │  + indexes           │        │
│  └──────────────────────┘  └──────────────────────┘        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Data Flow

### Flow 1: Hash-First Content Loading

```
User visits: /statutes/constitution#the-legislature

1. Frontend Detection
   ├─ Parse URL hash: "the-legislature"
   ├─ Check if content already loaded
   └─ If not → API Request

2. API Request
   GET /api/statutes/constitution/content/the-legislature
   ├─ Headers: Authorization Bearer {token}
   └─ Query: ?include_children=true&include_breadcrumb=true

3. Backend Processing
   ├─ Authentication & Authorization
   ├─ Statute Validation (exists, published)
   ├─ Content Resolver Service
   │   ├─ Search divisions table by slug
   │   ├─ If not found → Search provisions table
   │   ├─ If found → Load position metadata
   │   └─ Calculate has_before/has_after flags
   ├─ Breadcrumb Builder Service
   │   ├─ Check cache: "breadcrumb:1:division:45"
   │   ├─ If cached → Return cached
   │   ├─ If not → Build recursively → Cache → Return
   └─ Children Loader (if requested)
       ├─ Load immediate children only
       └─ Include has_children flag for each

4. Response Formation
   {
     "type": "division",
     "content": {...},
     "breadcrumb": [...],
     "children": [...],
     "position": {
       "order_index": 156,
       "total_items": 523,
       "has_content_before": true,
       "has_content_after": true
     }
   }

5. Frontend Rendering
   ├─ Render target content immediately
   ├─ Render breadcrumb for context
   ├─ Show "Content above ↑" indicator
   ├─ Show "Content below ↓" indicator
   ├─ Initialize scroll observers
   └─ Track view (order_index: 156)
```

### Flow 2: Bidirectional Lazy Loading (Scroll Up)

```
User scrolls near top of page

1. Intersection Observer Triggers
   ├─ Detects top sentinel element entering viewport
   ├─ Checks: Not already loading + has_content_before = true
   └─ Initiates load request

2. API Request
   GET /api/statutes/constitution/content/sequential
   ├─ Query: ?from_order=156&direction=before&limit=5
   └─ Headers: Authorization

3. Backend Processing
   ├─ Validate parameters (direction, from_order, limit)
   ├─ Sequential Navigator Service
   │   ├─ Build UNION query:
   │   │   SELECT from divisions WHERE order_index < 156
   │   │   UNION ALL
   │   │   SELECT from provisions WHERE order_index < 156
   │   ├─ Order by order_index DESC
   │   ├─ Limit 5
   │   └─ Calculate has_more flag
   └─ Format unified response (type discriminator)

4. Response Formation
   {
     "items": [
       {"order_index": 155, "type": "division", ...},
       {"order_index": 154, "type": "provision", ...},
       {"order_index": 153, "type": "division", ...}
     ],
     "meta": {
       "direction": "before",
       "from_order": 156,
       "has_more": true,
       "next_from_order": 152
     }
   }

5. Frontend Rendering
   ├─ Store current scroll position
   ├─ Store current document height
   ├─ Prepend items to DOM (in reverse order)
   ├─ Calculate new document height
   ├─ Adjust scroll position:
   │   scrollTop = oldScrollTop + (newHeight - oldHeight)
   ├─ Attach observer to new top element
   └─ Track views for newly visible items
```

### Flow 3: Range Loading (Buffering)

```
User requests specific range (e.g., for prefetching)

1. API Request
   GET /api/statutes/constitution/content/range
   └─ Query: ?start_order=150&end_order=160

2. Backend Processing
   ├─ Validate: end_order >= start_order
   ├─ Validate: range size <= max_range (100)
   ├─ Build query:
   │   SELECT * FROM (
   │     SELECT from divisions WHERE order_index BETWEEN 150 AND 160
   │     UNION ALL
   │     SELECT from provisions WHERE order_index BETWEEN 150 AND 160
   │   ) ORDER BY order_index ASC
   └─ Return ordered items

3. Frontend Processing
   ├─ Receive ordered items
   ├─ Store in content buffer
   ├─ Update UI if items in viewport
   └─ Mark range as loaded
```

---

## Core Components

### 1. Content Resolver Service

**Responsibility:** Resolve any content (division or provision) by slug and return unified metadata

**Key Methods:**
- `resolveBySlug(Statute $statute, string $slug)` → Returns ContentResolution object
- `getPositionMetadata(Statute $statute, int $orderIndex)` → Returns position flags
- `getTotalItems(Statute $statute)` → Returns count of all content

**Data Flow:**
```
Input: Statute + Content Slug
  ↓
Query divisions table (statute_id, slug)
  ↓
If found → Return with type="division"
  ↓
If not found → Query provisions table
  ↓
If found → Return with type="provision"
  ↓
If not found → Throw NotFoundHttpException
  ↓
Enrich with position metadata
  ↓
Return ContentResolution object
```

**ContentResolution Object:**
```
{
  type: "division" | "provision",
  content: Model instance,
  orderIndex: integer,
  totalItems: integer,
  hasContentBefore: boolean,
  hasContentAfter: boolean
}
```

---

### 2. Breadcrumb Builder Service

**Responsibility:** Build hierarchical breadcrumb trail from statute root to target content

**Caching Strategy:**
- Cache key pattern: `breadcrumb:{statute_id}:{type}:{content_id}`
- TTL: 1 hour (3600 seconds)
- Tags: `["statute:{statute_id}"]`
- Invalidation: On content update/delete via Observers

**Key Methods:**
- `build(Model $content)` → Returns breadcrumb array
- `invalidate(Model $content)` → Clears cached breadcrumb
- `invalidateStatute(Statute $statute)` → Clears all breadcrumbs for statute

**Algorithm:**
```
Input: Division or Provision
  ↓
Check cache: Cache::tags(['statute:1'])->get('breadcrumb:1:division:45')
  ↓
If cached → Return immediately
  ↓
If not cached:
  ├─ Start with content itself
  ├─ Walk up parent chain (parent_id)
  ├─ Collect each ancestor (id, slug, title, type, order_index)
  ├─ Add statute root at beginning
  ├─ Reverse array (root → content)
  ├─ Cache result with tags
  └─ Return breadcrumb array
```

**Breadcrumb Format:**
```
[
  {
    id: 1,
    slug: "constitution-of-...",
    title: "Constitution of...",
    type: "statute",
    order_index: null
  },
  {
    id: 5,
    slug: "chapter-v",
    title: "Chapter V",
    number: "V",
    type: "chapter",
    order_index: 120
  },
  ...
]
```

---

### 3. Sequential Navigator Service

**Responsibility:** Load content before or after a given position using efficient queries

**Key Methods:**
- `loadBefore(Statute $statute, int $fromOrder, int $limit)` → Returns items + meta
- `loadAfter(Statute $statute, int $fromOrder, int $limit)` → Returns items + meta
- `loadRange(Statute $statute, int $startOrder, int $endOrder)` → Returns items

**Query Strategy:**

**For "before" direction:**
```sql
-- Unified query across both tables
SELECT
  'division' as content_type,
  id, slug, order_index,
  division_type, division_number, division_title,
  level, has_children, child_count
FROM statute_divisions
WHERE statute_id = ? AND order_index < ? AND status = 'active'

UNION ALL

SELECT
  'provision' as content_type,
  id, slug, order_index,
  provision_type, provision_number, provision_title,
  provision_text, level, has_children
FROM statute_provisions
WHERE statute_id = ? AND order_index < ? AND status = 'active'

ORDER BY order_index DESC
LIMIT ?
```

**For "after" direction:**
```sql
-- Same structure but:
WHERE order_index > ?
ORDER BY order_index ASC
```

**Performance Notes:**
- Composite index on (statute_id, order_index, status) is critical
- UNION ALL (not UNION) to avoid deduplication overhead
- Query returns unified structure with type discriminator

---

### 4. Order Index Manager Service

**Responsibility:** Manage order_index values throughout content lifecycle

**Key Methods:**
- `calculateOrderIndex(Model $content)` → Returns next available index
- `reindexStatute(Statute $statute)` → Recalculates all indices for statute
- `shiftIndices(Statute $statute, int $fromIndex, int $offset)` → Bulk shifts
- `validateIndices(Statute $statute)` → Returns validation report

**Gap-Based Management System:**

**Initial Assignment:**
```
Gap size: 100 (configurable)

Chapter I → order_index = 100
  Section 1 → order_index = 200
    Subsection 1a → order_index = 300
    Subsection 1b → order_index = 400
  Section 2 → order_index = 500
Chapter II → order_index = 600
```

**Insertion Logic:**
```
Insert new content between order_index 300 and 400:
  ↓
Check gap size: 400 - 300 = 100
  ↓
If gap >= 2:
  ├─ Use midpoint: (300 + 400) / 2 = 350
  └─ Assign new content → order_index = 350
  ↓
If gap < 2 (no room):
  ├─ Trigger reindexing for this section
  └─ Recalculate with fresh gaps
```

**Reindexing Algorithm:**
```
1. Load all content for statute ordered by current indices
2. Walk through in order, assign new indices with gaps:
   - First item: 100
   - Each subsequent: previous + gap_size
3. Batch update all records
4. Clear cache tags for statute
5. Return reindexing report
```

**When to Reindex:**
- Manual trigger via admin command
- Automatic when gap exhaustion detected
- After bulk imports
- During statute restructuring

---

## Database Architecture

### Schema Changes

**statute_divisions table:**
```sql
ALTER TABLE statute_divisions
ADD COLUMN order_index INTEGER NULL;

-- Composite index for efficient range queries
CREATE INDEX idx_divisions_order
ON statute_divisions(statute_id, order_index, status);

-- Index for slug lookups
CREATE INDEX idx_divisions_slug
ON statute_divisions(statute_id, slug);
```

**statute_provisions table:**
```sql
ALTER TABLE statute_provisions
ADD COLUMN order_index INTEGER NULL;

-- Composite index for efficient range queries
CREATE INDEX idx_provisions_order
ON statute_provisions(statute_id, order_index, status);

-- Index for slug lookups
CREATE INDEX idx_provisions_slug
ON statute_provisions(statute_id, slug);
```

### Index Strategy

**Why These Indexes:**

1. **Composite (statute_id, order_index, status)**
   - Supports sequential queries: `WHERE statute_id = ? AND order_index < ?`
   - Includes status for filtered queries
   - Enables index-only scans (covering index)

2. **Slug Index (statute_id, slug)**
   - Supports hash-first lookup: `WHERE statute_id = ? AND slug = ?`
   - Unique constraint ensures no duplicate slugs per statute

3. **Existing Indexes (Maintain)**
   - parent_id indexes (for hierarchy traversal)
   - sort_order indexes (for legacy queries)

### Query Patterns

**Pattern 1: Slug Lookup**
```sql
-- Uses idx_divisions_slug or idx_provisions_slug
SELECT * FROM statute_divisions
WHERE statute_id = 1 AND slug = 'the-legislature';
```

**Pattern 2: Sequential Before**
```sql
-- Uses idx_divisions_order (index-only scan)
SELECT * FROM statute_divisions
WHERE statute_id = 1
  AND order_index < 156
  AND status = 'active'
ORDER BY order_index DESC
LIMIT 5;
```

**Pattern 3: Range Query**
```sql
-- Uses idx_divisions_order
SELECT * FROM statute_divisions
WHERE statute_id = 1
  AND order_index BETWEEN 150 AND 160
  AND status = 'active'
ORDER BY order_index ASC;
```

### Migration Considerations

**Phase 1: Add Columns (Nullable)**
- Allows system to continue functioning
- Existing queries unaffected

**Phase 2: Populate order_index**
- Run background job per statute
- Use recursive traversal to assign indices
- Can take minutes for large statutes

**Phase 3: Enable New Endpoints**
- Activate lazy loading routes
- Frontend can gradually adopt
- Old endpoints remain functional

**Phase 4: Make Non-Nullable (Future)**
- After all statutes indexed
- Add NOT NULL constraint
- Add database-level validation

---

## API Endpoint Architecture

### Endpoint 1: Universal Content Lookup

**Route:** `GET /api/statutes/{statuteSlug}/content/{contentSlug}`

**Controller:** `StatuteContentController@lookup`

**Request Flow:**
```
1. Middleware Stack
   ├─ auth:sanctum (authentication)
   ├─ throttle:60,1 (rate limiting)
   └─ ValidateStatuteAccess (authorization)

2. Controller Action
   ├─ Resolve statute by slug
   ├─ Validate statute is published
   ├─ Call ContentResolverService::resolveBySlug()
   ├─ Call BreadcrumbBuilderService::build() if requested
   ├─ Load children if requested
   ├─ Build position metadata
   └─ Return ContentLookupResource

3. Resource Transformation
   ├─ Wrap content in unified format
   ├─ Add type discriminator
   ├─ Include metadata
   └─ Return JSON response
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| include_children | boolean | true | Include immediate children |
| include_breadcrumb | boolean | true | Include breadcrumb trail |
| include_siblings | boolean | false | Include same-level content |

**Response Contract:**
```
Status: 200 OK
Body: {
  "status": "success",
  "message": "Content retrieved successfully",
  "data": {
    "type": "division" | "provision",
    "content": {...},
    "breadcrumb": [...] | null,
    "children": [...] | null,
    "siblings": [...] | null,
    "position": {
      "order_index": integer,
      "total_items": integer,
      "has_content_before": boolean,
      "has_content_after": boolean
    }
  }
}
```

**Error Responses:**
- 404: Statute not found
- 404: Content not found
- 401: Unauthorized
- 422: Invalid parameters

---

### Endpoint 2: Sequential Navigation

**Route:** `GET /api/statutes/{statuteSlug}/content/sequential`

**Controller:** `StatuteContentController@sequential`

**Request Flow:**
```
1. Validation
   ├─ Validate from_order (required, integer, > 0)
   ├─ Validate direction (required, in: before, after)
   ├─ Validate limit (integer, min: 1, max: 50)
   └─ Validate include_children (boolean)

2. Service Delegation
   ├─ If direction = "before" → SequentialNavigator::loadBefore()
   ├─ If direction = "after" → SequentialNavigator::loadAfter()
   └─ Service returns items + pagination metadata

3. Resource Transformation
   ├─ Wrap each item in UnifiedContentResource
   ├─ Add pagination metadata
   └─ Return SequentialContentCollection
```

**Query Parameters:**
| Parameter | Type | Required | Default | Max |
|-----------|------|----------|---------|-----|
| from_order | integer | Yes | - | - |
| direction | string | Yes | - | "before" or "after" |
| limit | integer | No | 5 | 50 |
| include_children | boolean | No | true | - |

**Response Contract:**
```
Status: 200 OK
Body: {
  "status": "success",
  "message": "Sequential content retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": integer,
        "type": "division" | "provision",
        "content": {...},
        "children": [...] | null
      }
    ],
    "meta": {
      "direction": "before" | "after",
      "from_order": integer,
      "limit": integer,
      "returned": integer,
      "has_more": boolean,
      "next_from_order": integer | null
    }
  }
}
```

---

### Endpoint 3: Range Loading

**Route:** `GET /api/statutes/{statuteSlug}/content/range`

**Controller:** `StatuteContentController@range`

**Request Flow:**
```
1. Validation
   ├─ Validate start_order (required, integer, > 0)
   ├─ Validate end_order (required, integer, >= start_order)
   ├─ Validate range size: (end - start) <= 100
   └─ Validate include_children (boolean)

2. Service Delegation
   ├─ Call SequentialNavigator::loadRange()
   └─ Service returns ordered items

3. Resource Transformation
   ├─ Wrap items in UnifiedContentResource
   ├─ Add range metadata
   └─ Return RangeContentCollection
```

**Query Parameters:**
| Parameter | Type | Required | Default | Constraint |
|-----------|------|----------|---------|------------|
| start_order | integer | Yes | - | > 0 |
| end_order | integer | Yes | - | >= start_order |
| include_children | boolean | No | true | - |

**Response Contract:**
```
Status: 200 OK
Body: {
  "status": "success",
  "message": "Content range retrieved successfully",
  "data": {
    "items": [
      {
        "order_index": integer,
        "type": "division" | "provision",
        "content": {...}
      }
    ],
    "meta": {
      "start_order": integer,
      "end_order": integer,
      "returned": integer,
      "total_items_in_statute": integer
    }
  }
}
```

---

## Caching Strategy

### Cache Layers

**Layer 1: Breadcrumb Cache**
- **Key Pattern:** `breadcrumb:{statute_id}:{type}:{content_id}`
- **TTL:** 3600 seconds (1 hour)
- **Tags:** `["statute:{statute_id}"]`
- **Size:** ~1-5KB per entry
- **Hit Rate Target:** 90%+ (breadcrumbs rarely change)

**Layer 2: Position Metadata Cache**
- **Key Pattern:** `position:{statute_id}:{order_index}`
- **TTL:** 1800 seconds (30 minutes)
- **Tags:** `["statute:{statute_id}"]`
- **Size:** ~200 bytes per entry
- **Hit Rate Target:** 70%+ (recalculated on content changes)

**Layer 3: Total Items Cache**
- **Key Pattern:** `total_items:{statute_id}`
- **TTL:** 3600 seconds (1 hour)
- **Tags:** `["statute:{statute_id}"]`
- **Size:** ~50 bytes
- **Hit Rate Target:** 95%+ (rarely changes)

### Cache Invalidation Strategy

**Observer-Based Invalidation:**

```
When Division/Provision is Created/Updated/Deleted:
  ↓
Model Observer Fires
  ↓
Clear specific caches:
  ├─ Breadcrumb for this content
  ├─ Breadcrumb for all descendants (recursive)
  ├─ Position metadata for this statute
  └─ Total items for this statute
  ↓
Use Cache Tags for bulk clear:
  Cache::tags(["statute:{statute_id}"])->flush()
```

**Event-Driven Invalidation:**

```
StatuteReindexed Event Fired
  ↓
Clear all caches for statute:
  Cache::tags(["statute:{statute_id}"])->flush()
  ↓
Optional: Pre-warm critical paths
  ├─ First 10 items (commonly accessed)
  └─ Top-level divisions
```

### Cache Warming Strategy

**On Statute Publish:**
```
1. Calculate order_index for all content
2. Pre-cache breadcrumbs for top-level divisions
3. Pre-cache total_items count
4. Pre-cache first page of sequential content
```

**Periodic Warming (Optional):**
```
Daily job:
  ├─ Identify most-viewed statutes (last 7 days)
  ├─ Pre-warm breadcrumbs for frequently accessed sections
  └─ Pre-warm sequential content for common entry points
```

### Cache Storage

**Recommended Driver:** Redis

**Why Redis:**
- Supports tagging (critical for invalidation)
- High performance for small object retrieval
- Built-in TTL management
- Atomic operations for concurrent access
- Persistent storage (optional)

**Fallback:** Database cache (if Redis unavailable)

**Configuration:**
```php
'stores' => [
    'statute_cache' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

---

## Order Index Management

### Gap-Based System

**Configuration:**
```php
// config/statute.php
return [
    'order_index' => [
        'gap_size' => 100,           // Space between items
        'min_gap_threshold' => 2,     // Trigger reindex when gap < 2
        'reindex_strategy' => 'auto', // auto | manual | scheduled
    ],
];
```

**Assignment Algorithm:**

```
When creating new content:
  ↓
Determine insertion point:
  ├─ If first child → parent.order_index + gap_size
  ├─ If middle child → Calculate midpoint between siblings
  └─ If last child → previous_sibling.order_index + gap_size
  ↓
Check available gap:
  ├─ If gap >= min_gap_threshold → Assign midpoint
  ├─ If gap < min_gap_threshold → Trigger reindexing
  └─ Assign new order_index
```

**Example Insertion:**

```
Existing structure:
  Section 1 → order_index = 100
  Section 3 → order_index = 300

Insert "Section 2" between them:
  ↓
Calculate midpoint: (100 + 300) / 2 = 200
  ↓
Assign: Section 2 → order_index = 200

Result:
  Section 1 → order_index = 100
  Section 2 → order_index = 200  ← New
  Section 3 → order_index = 300
```

### Reindexing System

**Trigger Conditions:**
1. Manual trigger (admin command)
2. Gap exhaustion detection
3. Bulk content import
4. Major content restructuring

**Reindexing Process:**

```
1. Lock statute (prevent concurrent modifications)
   ↓
2. Load all content ordered by current indices
   ├─ Fetch divisions (ordered by order_index, sort_order)
   ├─ Fetch provisions (ordered by order_index, sort_order)
   └─ Merge into single ordered array
   ↓
3. Calculate new indices with gaps
   ├─ Start: 100
   ├─ Each item: previous + gap_size
   └─ Store mapping: old_index → new_index
   ↓
4. Batch update database
   ├─ Update divisions table (chunk by 500)
   ├─ Update provisions table (chunk by 500)
   └─ Commit transaction
   ↓
5. Clear all caches for statute
   └─ Cache::tags(["statute:{id}"])->flush()
   ↓
6. Unlock statute
   ↓
7. Fire StatuteReindexed event
```

**Performance Considerations:**
- Process in chunks (500 records at a time)
- Use database transactions for atomicity
- Queue for large statutes (>1000 items)
- Provide progress feedback for admin interface

### Validation & Monitoring

**Validation Checks:**
1. **Uniqueness:** No duplicate order_index values
2. **Completeness:** All content has order_index
3. **Order Integrity:** Parent always before children
4. **Gap Health:** Average gap size >= threshold

**Admin Command:**
```bash
php artisan statutes:validate-indices {statute_id?}
  ↓
Runs validation checks
  ↓
Returns report:
  - Total items
  - Missing indices count
  - Duplicate indices count
  - Average gap size
  - Recommendation (OK | Reindex Needed)
```

**Monitoring Metrics:**
- Average gap size per statute
- Reindex frequency
- Gap exhaustion events
- Index assignment time

---

## Integration Points

### Frontend Integration

**Initial Load (Hash Present):**
```javascript
// 1. Detect hash
const hash = location.hash.slice(1);

// 2. Call lookup endpoint
const response = await fetch(
  `/api/statutes/${slug}/content/${hash}`,
  {
    headers: { Authorization: `Bearer ${token}` },
    params: {
      include_children: true,
      include_breadcrumb: true
    }
  }
);

// 3. Render immediately
renderContent(response.data);
setupScrollObservers(response.data.position.order_index);
```

**Scroll Observers:**
```javascript
// Observer for upward scrolling
const topObserver = new IntersectionObserver(
  (entries) => {
    if (entries[0].isIntersecting && hasContentBefore) {
      loadContentBefore(currentOrderIndex);
    }
  },
  { rootMargin: '200px 0px 0px 0px' }
);

// Observer for downward scrolling
const bottomObserver = new IntersectionObserver(
  (entries) => {
    if (entries[0].isIntersecting && hasContentAfter) {
      loadContentAfter(currentOrderIndex);
    }
  },
  { rootMargin: '0px 0px 200px 0px' }
);
```

**Scroll Position Maintenance:**
```javascript
async function loadContentBefore(fromOrder) {
  // Store current state
  const oldScrollTop = document.documentElement.scrollTop;
  const oldScrollHeight = document.documentElement.scrollHeight;

  // Load and prepend content
  const items = await fetchSequential(fromOrder, 'before');
  prependToDOM(items);

  // Adjust scroll to prevent jump
  const newScrollHeight = document.documentElement.scrollHeight;
  const heightDiff = newScrollHeight - oldScrollHeight;
  document.documentElement.scrollTop = oldScrollTop + heightDiff;
}
```



### Backward Compatibility

**Maintain Existing Endpoints:**
- All current endpoints remain functional
- New endpoints are additive (no breaking changes)
- Frontend can gradually migrate

**Feature Detection:**
```javascript
// Frontend checks if lazy loading available
const supportsLazyLoading = await checkEndpoint(
  `/api/statutes/${slug}/content/test`
);

if (supportsLazyLoading) {
  // Use new lazy loading approach
  loadWithLazyLoading();
} else {
  // Fallback to old approach
  loadTraditionalWay();
}
```

**Migration Path:**
1. Backend deploys new endpoints (feature flagged)
2. Frontend A/B tests new approach (10% users)
3. Monitor performance and analytics
4. Gradual rollout to 100%
5. Eventually deprecate old loading logic

---

## Performance Considerations

### Database Query Optimization

**Index Coverage:**
- Ensure queries use composite indexes
- Avoid index scans where possible
- Use EXPLAIN to verify query plans

**Query Limits:**
- Max sequential items: 50 per request
- Max range size: 100 items
- Enforce at validation layer

**Connection Pooling:**
- Use persistent connections for frequently accessed data
- Consider read replicas for heavy read load

### API Response Times

**Target SLAs:**
- Content lookup: <200ms (p95)
- Sequential navigation: <150ms (p95)
- Range loading: <250ms (p95)

**Optimization Techniques:**
- Database query optimization
- Redis caching for hot paths
- Response compression (gzip)
- CDN for static metadata

### Caching Hit Rates

**Target Metrics:**
- Breadcrumb cache: >90% hit rate
- Position metadata: >70% hit rate
- Total items: >95% hit rate

**Monitoring:**
- Track cache hit/miss rates
- Alert on degradation
- Auto-warm on cache clear

### Scalability Planning

**Horizontal Scaling:**
- API servers: Stateless, can scale infinitely
- Redis cache: Master-replica setup
- Database: Read replicas for queries

**Vertical Scaling:**
- Database optimization for large statutes
- Consider partitioning by statute_id (if needed)

**Load Testing Targets:**
- 1000 concurrent users per statute
- 10,000 requests/minute across all statutes
- <500ms response time at peak

---

## Migration Strategy

### Phase 1: Preparation (Week 1)

**Tasks:**
1. Create migration files for order_index columns
2. Add database indexes
3. Create OrderIndexManagerService
4. Create population script with dry-run mode
5. Test on staging with copy of production data

**Deliverables:**
- Migration files
- Population script
- Validation report for existing statutes

### Phase 2: Data Population (Week 2)

**Tasks:**
1. Run population script in maintenance window
2. Validate all statutes have correct order_index
3. Run performance tests on indexed queries
4. Monitor database performance

**Process:**
```bash
# Dry run first
php artisan statutes:populate-order-index --dry-run

# Run for real (with progress bar)
php artisan statutes:populate-order-index --verbose

# Validate
php artisan statutes:validate-indices
```

### Phase 3: API Implementation (Week 3-4)

**Tasks:**
1. Implement services (ContentResolver, BreadcrumbBuilder, etc.)
2. Implement controllers and routes
3. Create API resources
4. Add comprehensive tests
5. Deploy behind feature flag

**Feature Flag:**
```php
if (config('features.statute_lazy_loading')) {
    // Register new routes
    Route::get('/content/{contentSlug}', [StatuteContentController::class, 'lookup']);
}
```

### Phase 4: Frontend Integration (Week 5-6)

**Tasks:**
1. Implement hash detection logic
2. Implement scroll observers
3. Implement content buffering
4. A/B test with 10% traffic
5. Monitor performance and analytics

### Phase 5: Rollout (Week 7)

**Tasks:**
1. Gradual rollout: 25% → 50% → 75% → 100%
2. Monitor error rates and performance
3. Gather user feedback
4. Fix any issues
5. Full deployment

### Phase 6: Cleanup (Week 8)

**Tasks:**
1. Remove feature flags
2. Deprecate old loading approach
3. Update documentation
4. Optimize based on production metrics
5. Add monitoring dashboards

---

## Success Metrics

### Performance Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| Initial Load Time | 3-5s | <500ms | Time to First Contentful Paint |
| API Response Time | 800ms | <200ms | p95 latency |
| Data Transfer (Initial) | 500KB | <10KB | Network payload size |
| Scroll Smoothness | Janky | 60fps | Frame rate during scroll |

### User Experience Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Bounce Rate | <20% | Analytics |
| Time on Page | +50% | Analytics |
| Scroll Depth | >75% | Analytics |
| User Satisfaction | >4.5/5 | Surveys |

### System Health Metrics

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| Cache Hit Rate | >85% | <70% |
| Database Query Time | <50ms | >100ms |
| Error Rate | <0.1% | >1% |
| API Availability | 99.9% | <99.5% |

### Accuracy Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| View Count Accuracy | ~30% | >95% | Manual verification |
| Analytics Quality | Poor | Good | Data analysis |

---

## Summary

This implementation guide provides a comprehensive architecture for transforming the statute viewing system into a high-performance, hash-first lazy loading system.

**Key Architectural Decisions:**

1. **Gap-Based Order Index:** Enables efficient insertions without cascading updates
2. **Service Layer Architecture:** Clean separation of concerns, testable components
3. **Cache-First Strategy:** Aggressive caching with tag-based invalidation
4. **Union Query Approach:** Efficient sequential navigation across content types
5. **Backward Compatibility:** Gradual migration path, no breaking changes

**Implementation Priorities:**

1. **Critical:** Database schema + population script
2. **Critical:** Universal lookup + sequential navigation endpoints
3. **High:** Breadcrumb caching + invalidation
4. **Medium:** Range loading endpoint
5. **Low:** Advanced optimizations (prefetching, warming)

**Expected Outcomes:**

- 10x faster initial page loads
- 90% reduction in unnecessary data transfer
- Accurate view analytics
- Infinite scalability for large statutes
- Improved user engagement and satisfaction

**Next Steps:**

1. Review and approve this architecture
2. Create implementation tickets
3. Begin Phase 1 (database preparation)
4. Set up monitoring and alerting
5. Plan rollout schedule

---

**Document Version:** 1.0
**Last Updated:** 2025-10-27
**Status:** Ready for Implementation Review
