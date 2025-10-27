# Frontend Requirements for Statute Lazy Loading API

**Date:** 2025-10-27
**Status:** Required Changes
**Priority:** HIGH

---

## Executive Summary

The current API response structure in `statute-lazyloading.md` requires modifications to work seamlessly with the existing frontend UI. The main issue is that **the UI renders divisions and provisions as separate hierarchies**, not as a mixed `children` array.

---

## Critical Issues

### 1. Mixed `children` Array (BLOCKING ISSUE)

**Current API Response:**
```json
{
  "type": "division",
  "content": { ...division... },
  "children": [
    { "id": 376, "division_type": "part", ... },
    { "id": 377, "division_type": "part", ... }
  ]
}
```

**Frontend Expects:**
```json
{
  "type": "division",
  "content": { ...division... },
  "childDivisions": [
    { "id": 376, "division_type": "part", ... }
  ],
  "provisions": [
    { "id": 79, "provision_type": "section", ... }
  ]
}
```

**Why:** The UI uses separate rendering logic:
- `renderDivision()` recursively renders `childDivisions` array
- Then renders `provisions` array at that division level
- Mixing them in one array breaks the rendering hierarchy

---

### 2. Missing `childProvisions` for Provisions

**Current API Response:**
```json
{
  "type": "provision",
  "content": { ...provision... },
  "children": []  // Empty or missing
}
```

**Frontend Expects:**
```json
{
  "type": "provision",
  "content": { ...provision... },
  "childProvisions": [
    { "id": 80, "provision_type": "subsection", ... },
    { "id": 81, "provision_type": "subsection", ... }
  ]
}
```

**Why:** The UI lazy-loads child provisions (subsections, paragraphs, clauses) and expects them in a `childProvisions` array for recursive rendering.

---

### 3. Sequential Endpoint Returns Flat List

**Current API Response:**
```json
{
  "items": [
    { "type": "division", "order_index": 100, ... },
    { "type": "provision", "order_index": 200, ... },
    { "type": "provision", "order_index": 300, ... }
  ]
}
```

**Frontend Needs:** Parent-child relationships to reconstruct hierarchy.

**Add to each item:**
```json
{
  "type": "provision",
  "content": { ...provision... },
  "parent_division_id": 376,      // NEW: Which division owns this provision
  "parent_provision_id": null,     // NEW: Which provision is the parent (for subsections)
  "childProvisions": [ ... ]       // NEW: Nested provisions if include_children=true
}
```

---

## Required API Changes

### Change 1: Universal Content Lookup Endpoint

**Endpoint:** `GET /statutes/{slug}/content/{contentSlug}`

**For Division Responses:**

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
      "level": 1,
      "status": "active",
      "order_index": 100
    },
    "position": { ... },
    "breadcrumb": [ ... ],

    // CHANGE 1: Rename 'children' to 'childDivisions'
    "childDivisions": [
      {
        "id": 376,
        "slug": "federal-republic-of-nigeria-5LZnaids",
        "division_type": "part",
        "division_number": "I",
        "division_title": "Federal Republic of Nigeria",
        "order_index": 200,
        "level": 2,
        "status": "active",
        "has_children": true
      }
    ],

    // CHANGE 2: Add separate 'provisions' array
    "provisions": [
      {
        "id": 79,
        "slug": "supremacy-of-constitution-I5ENJmXK",
        "provision_type": "section",
        "provision_number": "1",
        "provision_title": "Supremacy of constitution",
        "provision_text": "See subsections",
        "order_index": 300,
        "level": 3,
        "status": "active",
        "has_children": true,

        // CHANGE 3: Include childProvisions when include_children=true
        "childProvisions": [
          {
            "id": 80,
            "slug": "1-TayN0J8b",
            "provision_type": "subsection",
            "provision_number": "(1)",
            "provision_title": null,
            "provision_text": "This Constitution is supreme...",
            "order_index": 400,
            "level": 4,
            "status": "active",
            "has_children": false
          }
        ]
      }
    ],

    // REMOVE: Combined 'children' array
    "siblings": null
  }
}
```

**For Provision Responses:**

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
      "level": 3,
      "status": "active",
      "order_index": 300
    },
    "position": { ... },
    "breadcrumb": [ ... ],

    // CHANGE: Rename 'children' to 'childProvisions'
    "childProvisions": [
      {
        "id": 80,
        "slug": "1-TayN0J8b",
        "provision_type": "subsection",
        "provision_number": "(1)",
        "provision_title": null,
        "provision_text": "This Constitution is supreme...",
        "order_index": 400,
        "level": 4,
        "status": "active",
        "has_children": false
      }
    ],

    // REMOVE: 'children' array
    "siblings": null
  }
}
```

---

### Change 2: Sequential Content Navigation Endpoint

**Endpoint:** `GET /statutes/{slug}/content/sequential`

**Current Response (Lines 369-446 in backend doc):**

```json
{
  "items": [
    {
      "order_index": 200,
      "type": "division",
      "content": { ...division... },
      "children": []  // Mixed child divisions only
    }
  ]
}
```

**Required Response:**

```json
{
  "items": [
    {
      "order_index": 200,
      "type": "division",
      "content": {
        "id": 376,
        "slug": "federal-republic-of-nigeria-5LZnaids",
        "division_type": "part",
        "division_number": "I",
        "division_title": "Federal Republic of Nigeria",
        "level": 2,
        "parent_division_id": 375,  // NEW: Parent reference
        "status": "active",
        "has_children": true
      },

      // CHANGE: Split children into separate arrays
      "childDivisions": [ ... ],
      "provisions": [ ... ]
    },
    {
      "order_index": 300,
      "type": "provision",
      "content": {
        "id": 79,
        "slug": "supremacy-of-constitution-I5ENJmXK",
        "provision_type": "section",
        "provision_number": "1",
        "provision_title": "Supremacy of constitution",
        "provision_text": "See subsections",
        "level": 3,
        "division_id": 376,           // NEW: Parent division reference
        "parent_provision_id": null,   // NEW: Parent provision reference
        "status": "active",
        "has_children": true
      },

      // CHANGE: Use childProvisions instead of children
      "childProvisions": [
        {
          "id": 80,
          "slug": "1-TayN0J8b",
          "provision_type": "subsection",
          "provision_number": "(1)",
          "provision_text": "This Constitution is supreme...",
          "order_index": 400,
          "level": 4,
          "parent_provision_id": 79,  // Points to parent
          "status": "active",
          "has_children": false
        }
      ]
    }
  ],
  "meta": { ... }
}
```

---

### Change 3: Batch Content Range Endpoint

**Endpoint:** `GET /statutes/{slug}/content/range`

**Apply same changes as Sequential endpoint:**
- Split `children` into `childDivisions` and `provisions` for divisions
- Use `childProvisions` for provisions
- Add parent references: `parent_division_id`, `parent_provision_id`

---

## Field Name Mapping

### Division Object Fields

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| `id` | integer | Yes | Division ID |
| `slug` | string | Yes | Division slug |
| `statute_id` | integer | Yes | Parent statute ID |
| `parent_division_id` | integer\|null | Yes | Parent division ID (null for top-level) |
| `division_type` | string | Yes | Type: "chapter", "part", "title", "article", etc. |
| `division_number` | string | Yes | Numbering: "I", "1", "A", etc. |
| `division_title` | string | Yes | Division title |
| `division_subtitle` | string\|null | No | Subtitle (optional) |
| `content` | string\|null | No | Preamble/description |
| `level` | integer | Yes | Hierarchy level (1 = top) |
| `status` | string | Yes | "active", "repealed", "amended", "suspended" |
| `order_index` | integer | Yes | Sequential position |
| `has_children` | boolean | Yes | Whether division has child divisions |
| **`childDivisions`** | **array** | **Yes** | **Array of child division objects** |
| **`provisions`** | **array** | **Yes** | **Array of provisions at this level** |

### Provision Object Fields

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| `id` | integer | Yes | Provision ID |
| `slug` | string | Yes | Provision slug |
| `statute_id` | integer | Yes | Parent statute ID |
| `division_id` | integer\|null | Yes | Parent division ID |
| `parent_provision_id` | integer\|null | Yes | Parent provision ID (null for section-level) |
| `provision_type` | string | Yes | Type: "section", "subsection", "paragraph", "clause" |
| `provision_number` | string | Yes | Numbering: "1", "(1)", "(a)", "(i)", etc. |
| `provision_title` | string\|null | Yes | Provision title (often null for subsections) |
| `provision_text` | string | Yes | Legal text content |
| `marginal_note` | string\|null | No | Side note |
| `interpretation_note` | string\|null | No | Interpretive guidance |
| `level` | integer | Yes | Hierarchy level |
| `status` | string | Yes | "active", "repealed", "amended", "suspended" |
| `order_index` | integer | Yes | Sequential position |
| `has_children` | boolean | Yes | Whether provision has child provisions |
| **`childProvisions`** | **array** | **Yes** | **Array of child provision objects (subsections, etc.)** |

---

## Query Parameter Behavior

### `include_children` Parameter

**When `include_children=true` (default):**
- For divisions: Include `childDivisions` and `provisions` arrays
- For provisions: Include `childProvisions` array
- Recursively include children up to 1 level deep (not all descendants)

**When `include_children=false`:**
- Return empty arrays: `childDivisions: []`, `provisions: []`, `childProvisions: []`
- Set `has_children: true/false` to indicate if children exist
- Frontend will lazy-load children on-demand

---

## Frontend Rendering Logic

### How UI Uses These Structures

**DivisionSection Component:**
```javascript
// Receives division object
function renderDivision(division) {
  return (
    <DivisionSection division={division}>
      {/* Recursively render child divisions */}
      {division.childDivisions.map(childDiv => renderDivision(childDiv))}

      {/* Render provisions at this level */}
      {division.provisions.map(provision => (
        <ProvisionItem provision={provision} />
      ))}
    </DivisionSection>
  );
}
```

**ProvisionItem Component:**
```javascript
// Receives provision object
function ProvisionItem({ provision }) {
  return (
    <div>
      <div>{provision.provision_text}</div>

      {/* Recursively render child provisions */}
      {provision.childProvisions.map(childProv => (
        <ProvisionItem provision={childProv} />
      ))}
    </div>
  );
}
```

**Key Point:** The UI expects **pre-separated arrays**, not a mixed list. This allows:
- Different styling for divisions vs provisions
- Correct indentation based on type
- Separate handling of lazy-loading logic

---

## Summary of Required Changes

| Change | Endpoint | Action |
|--------|----------|--------|
| 1 | Universal Content Lookup | Split `children` into `childDivisions` + `provisions` (divisions) or `childProvisions` (provisions) |
| 2 | Sequential Navigation | Same as above + add parent references |
| 3 | Batch Range | Same as above + add parent references |
| 4 | All endpoints | Use `childProvisions` instead of `children` for provision responses |
| 5 | All endpoints | Add `parent_division_id` and `parent_provision_id` fields |

---

## Backward Compatibility

**No breaking changes to existing endpoints.** These are new lazy-loading endpoints, so we can define the structure correctly from the start.

**Recommendation:** Implement these changes before frontend integration begins to avoid transformation layer complexity.

---


**Backend Implementation:**
- Update response builders in lazy-loading controllers
- Ensure `childDivisions`, `provisions`, and `childProvisions` arrays are populated
- Add parent reference fields: `parent_division_id`, `parent_provision_id`

---

**End of Document**
