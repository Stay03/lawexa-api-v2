# Statute Frontend Implementation Guide

## Document Overview
This document provides clear implementation guidance for displaying statutes with dynamic hierarchical structures (divisions and provisions) in the LawExA frontend application.

**Target Audience:** Frontend Developers
**Last Updated:** October 25, 2025
**API Base URL:** `https://rest.lawexa.com/api`

---

## Table of Contents
1. [Understanding the Statute Hierarchy](#understanding-the-statute-hierarchy)
2. [Recommended UI/UX Pattern](#recommended-uiux-pattern)
3. [URL Structure Strategy](#url-structure-strategy)
4. [API Endpoints Reference](#api-endpoints-reference)
5. [Implementation Steps](#implementation-steps)
6. [Component Architecture](#component-architecture)
7. [Data Flow & State Management](#data-flow--state-management)
8. [Key Features to Implement](#key-features-to-implement)
9. [Backend Limitation & Workarounds](#backend-limitation--workarounds)
10. [Mobile Considerations](#mobile-considerations)

---

## 1. Understanding the Statute Hierarchy

### Structure Overview

Statutes in LawExA have a flexible, nested hierarchy:

```
Statute (Root Document)
├── Division (Chapter, Part, Article, etc.)
│   ├── Division (Nested - e.g., Part > Chapter)
│   │   ├── Division (Further nested)
│   │   └── Provision (Section, Subsection, etc.)
│   │       └── Provision (Nested - e.g., Subsection > Paragraph)
│   └── Provision
│       └── Provision (Nested subsections)
└── Provision (Top-level sections)
    └── Provision (Nested)
```

### Key Concepts

**Divisions** are structural containers:
- Types: `chapter`, `part`, `article`, `title`, `book`, `division`, `section`, `subsection`, `schedule`, `order`
- Can contain other divisions (nested)
- Can contain provisions
- Have titles, numbers, and optional content
- Example: "Chapter I", "Part II", "Schedule A"

**Provisions** are content elements:
- Types: `section`, `subsection`, `paragraph`, `subparagraph`, `clause`, `subclause`, `item`
- Contain the actual legal text
- Can be nested (section → subsection → paragraph)
- Have numbers, optional titles, and text content
- Example: "Section 1", "(1)", "(a)"

**Key Fields:**
- `slug`: Unique identifier for URLs
- `sort_order`: Determines display sequence
- `level`: Hierarchy depth (1 = top-level)
- `division_number` / `provision_number`: Display label (e.g., "I", "1", "(a)")
- `breadcrumb`: Full navigation path from statute to current item

---

## 2. Recommended UI/UX Pattern

### Design Approach: **Hybrid Continuous Reading View**

The best approach combines continuous scrolling (like Bible websites) with smart navigation features.

### Layout Structure

```
┌────────────────────────────────────────────────────────────┐
│ Header: Statute Title + Metadata                          │
│ Year: 1999 | Status: Active | Views: 71 | [Bookmark]     │
├──────────────┬─────────────────────────────────────────────┤
│              │                                             │
│ Table of     │  MAIN CONTENT AREA (Scrollable)            │
│ Contents     │                                             │
│ (Sidebar)    │  ═══════════════════════════════════════   │
│              │  CHAPTER I                                  │
│ • Chapter I  │  General provisions; Federal Republic...   │
│   - Sec 1    │  ───────────────────────────────────────   │
│   - Sec 2    │                                             │
│ • Chapter II │  Section 1 - Supremacy of Constitution     │
│   - Sec 13   │  This Constitution is supreme and its      │
│   - Sec 14   │  provisions shall have binding force...    │
│ • Chapter III│  [📌 Bookmark] [🔗 Share] [💬 Feedback]   │
│              │                                             │
│ [Sticky,     │  Section 2 - Federal Republic             │
│  Auto-scroll │  (1) Nigeria is one indivisible...        │
│  highlight]  │  (2) The territory of Nigeria...          │
│              │  [📌] [🔗] [💬]                            │
│              │                                             │
│              │  ═══════════════════════════════════════   │
│              │  CHAPTER II                                 │
│              │  Fundamental Objectives...                  │
│              │                                             │
└──────────────┴─────────────────────────────────────────────┘
```

### Why This Approach?

✅ **Maintains reading flow** - Statutes are meant to be read sequentially
✅ **Provides context** - Users see surrounding sections while reading
✅ **Easy navigation** - TOC sidebar allows quick jumps
✅ **Shareable links** - Deep linking to specific sections
✅ **Professional appearance** - Matches legal document conventions

---

## 3. URL Structure Strategy

### Primary Pattern: Hash-Based Navigation (Recommended)

Use hash fragments for smooth navigation within a single page:

```
/statutes/{statute-slug}                          # Main view
/statutes/{statute-slug}#chapter-i                # Jump to division
/statutes/{statute-slug}#section-35               # Jump to provision
/statutes/{statute-slug}#section-35-subsection-1  # Jump to nested provision
```

**Benefits:**
- Single page load = fast initial render
- Browser handles scroll position
- Back/forward buttons work naturally
- Easy to share specific sections
- No additional API calls when navigating

### Secondary Pattern: Route-Based (Optional)

For focused views or deep linking from external sources:

```
/statutes/{statute-slug}/{division-slug}          # Division focused view
/statutes/{statute-slug}/{provision-slug}         # Provision focused view
```

**Use cases:**
- Search results linking to specific sections
- Email notifications about updates
- Bookmarks opening to specific content

### Implementation Strategy

**Recommended: Support both patterns simultaneously**

1. Default to continuous view with hash navigation
2. Detect route-based URLs and load focused view
3. Include "View full statute" button in focused views
4. Update hash as user scrolls through continuous view

---

## 4. API Endpoints Reference

### Available Endpoints

#### Statute Endpoints

```http
GET /api/statutes
GET /api/statutes/{statute-slug}
GET /api/statutes/{statute-slug}/divisions
GET /api/statutes/{statute-slug}/provisions
```

#### Division Endpoints

```http
GET /api/statutes/{statute-slug}/divisions/{division-slug}
GET /api/statutes/{statute-slug}/divisions/{division-slug}/children
GET /api/statutes/{statute-slug}/divisions/{division-slug}/provisions
```

#### Provision Endpoints

```http
GET /api/statutes/{statute-slug}/provisions/{provision-slug}
GET /api/statutes/{statute-slug}/provisions/{provision-slug}/children
```

### Response Structure Examples

#### Statute Detail Response

```json
{
  "status": "success",
  "message": "Statute retrieved successfully",
  "data": {
    "statute": {
      "id": 19,
      "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
      "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
      "year_enacted": 1999,
      "status": "active",
      "jurisdiction": "Federal",
      "country": "Nigeria",
      "description": "...",
      "views_count": 71,
      "is_bookmarked": true,
      "bookmark_id": 104,
      "bookmarks_count": 1
    }
  }
}
```

#### Division List Response

```json
{
  "status": "success",
  "message": "Statute divisions retrieved successfully",
  "data": {
    "statute": {
      "id": 19,
      "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
      "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
      "breadcrumb": [
        {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
          "type": "statute"
        }
      ]
    },
    "divisions": [
      {
        "id": 47,
        "slug": "general-provisions-federal-republic-of-nigeria-powers-of-the-federal-republic-of-nigeria",
        "division_type": "chapter",
        "division_number": "I",
        "division_title": "General provisions; Federal Republic of Nigeria; Powers...",
        "content": null,
        "sort_order": 0,
        "level": 1,
        "status": "active",
        "range": "Sections 1-12",
        "views_count": 2,
        "bookmarks_count": 0,
        "parent_division": null
      }
    ]
  }
}
```

#### Provision with Children Response

```json
{
  "status": "success",
  "message": "Provision children retrieved successfully",
  "data": {
    "parent": {
      "id": 294,
      "title": "Right to dignity of person",
      "number": "35",
      "slug": "right-to-dignity-of-person-mUpW7IVe",
      "type": "section",
      "level": 1,
      "breadcrumb": [
        {
          "id": 19,
          "title": "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
          "slug": "constitution-of-the-federal-republic-of-nigeria-1999",
          "type": "statute"
        },
        {
          "id": 170,
          "title": "Fundamental Rights",
          "number": "IV",
          "slug": "fundamental-rights",
          "type": "chapter"
        },
        {
          "id": 294,
          "title": "Right to dignity of person",
          "number": "35",
          "slug": "right-to-dignity-of-person-mUpW7IVe",
          "type": "section"
        }
      ]
    },
    "children": [
      {
        "id": 295,
        "slug": "1-17LLRNQM",
        "provision_type": "subsection",
        "provision_number": "(1)",
        "provision_text": "Every person shall have the right to dignity of person and accordingly...",
        "sort_order": 0,
        "level": 1,
        "status": "active"
      },
      {
        "id": 296,
        "provision_type": "subsection",
        "provision_number": "(2)",
        "provision_text": "Any person who is arrested or detained..."
      }
    ]
  }
}
```

### Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

**Meta Information in Response:**
```json
"meta": {
  "current_page": 1,
  "last_page": 3,
  "per_page": 15,
  "total": 44,
  "from": 1,
  "to": 15
}
```

---

## 5. Implementation Steps

### Step 1: Initial Page Load

**For Continuous Reading View:**

1. **Fetch statute metadata**
   ```
   GET /api/statutes/{slug}
   ```

2. **Fetch top-level divisions** (for TOC)
   ```
   GET /api/statutes/{slug}/divisions?per_page=100
   ```

3. **Build Table of Contents structure**
   - Display division hierarchy
   - Keep divisions in state for navigation

4. **Load first visible content**
   - Fetch provisions for first 2-3 divisions
   - Or fetch provisions at statute level with pagination

### Step 2: Progressive Content Loading

**Lazy Loading Strategy:**

1. **Detect scroll position**
   - Monitor when user approaches end of loaded content
   - Trigger next content fetch

2. **Load next batch**
   ```
   GET /api/statutes/{slug}/divisions/{division-slug}/provisions
   GET /api/statutes/{slug}/provisions?page=2
   ```

3. **Append to DOM**
   - Add new content below existing
   - Update scroll tracking

### Step 3: Table of Contents Navigation

1. **Build hierarchical TOC**
   - Use divisions data to create nested list
   - Include provision numbers if available

2. **Implement click handlers**
   - Smooth scroll to target section
   - Update URL hash
   - Highlight active item

3. **Auto-highlight current section**
   - Use Intersection Observer API
   - Track which section is in viewport
   - Update TOC active state

### Step 4: Deep Linking

1. **Parse URL hash on page load**
   ```javascript
   const hash = window.location.hash // #section-35
   ```

2. **Identify target element**
   - Match hash to division/provision slug
   - Load that content if not present

3. **Scroll to position**
   ```javascript
   element.scrollIntoView({ behavior: 'smooth', block: 'start' })
   ```

### Step 5: Nested Content Expansion

**For provisions with children:**

1. **Check if provision has children**
   ```javascript
   if (provision.provision_text === "[Section has subsections]") {
     // This provision has nested content
   }
   ```

2. **Fetch children on demand**
   ```
   GET /api/statutes/{slug}/provisions/{provision-slug}/children
   ```

3. **Display with proper indentation**
   - Apply CSS classes based on level
   - Show hierarchy visually

**For divisions with nested divisions:**

1. **Check for child divisions**
   ```
   GET /api/statutes/{slug}/divisions/{division-slug}/children
   ```

2. **Render nested structure**
   - Indent child divisions
   - Apply visual hierarchy

---

## 6. Component Architecture

### Recommended Component Structure

```
<StatutePage>
  ├── <StatuteHeader>
  │   ├── Title, metadata
  │   ├── Action buttons (bookmark, share)
  │   └── Breadcrumb
  │
  ├── <Layout>
  │   ├── <StatuteTOC> (Sidebar)
  │   │   ├── <TOCDivisionItem> (recursive)
  │   │   │   └── <TOCProvisionItem>
  │   │   └── Auto-highlight current section
  │   │
  │   └── <StatuteContent> (Main area)
  │       ├── <DivisionSection>
  │       │   ├── Division header
  │       │   ├── Division content
  │       │   ├── <DivisionSection> (nested)
  │       │   └── <ProvisionList>
  │       │       └── <ProvisionItem>
  │       │           ├── Provision content
  │       │           ├── Action buttons
  │       │           └── <ProvisionItem> (nested)
  │       │
  │       └── <ScheduleSection>
  │
  └── <ProgressBar> (optional)
```

### Component Responsibilities

#### `<StatutePage>`
- Manage overall state
- Handle API calls
- Coordinate child components
- Manage URL hash updates

#### `<StatuteHeader>`
- Display statute metadata
- Render action buttons
- Show breadcrumb navigation
- Handle bookmark/share actions

#### `<StatuteTOC>`
- Build hierarchical navigation menu
- Implement smooth scroll on click
- Auto-highlight current section
- Collapse/expand sections (mobile)

#### `<StatuteContent>`
- Render statute text content
- Handle lazy loading
- Manage scroll position
- Apply proper hierarchy styling

#### `<DivisionSection>`
- Display division header
- Render division content
- Recursively render nested divisions
- Fetch and display provisions

#### `<ProvisionItem>`
- Display provision text
- Show provision metadata
- Render action buttons
- Recursively render nested provisions

---

## 7. Data Flow & State Management

### State Structure

```javascript
{
  // Statute metadata
  statute: {
    id: 19,
    slug: "constitution-of-the-federal-republic-of-nigeria-1999",
    title: "CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA, 1999",
    // ... other metadata
  },

  // TOC structure (for navigation)
  divisions: [
    {
      id: 47,
      slug: "chapter-i",
      title: "General provisions",
      number: "I",
      level: 1,
      children: [], // nested divisions if any
      provisions: [] // provision summaries for TOC
    }
  ],

  // Loaded content (for display)
  content: {
    "chapter-i": {
      division: { /* division data */ },
      provisions: [ /* provision data */ ],
      loaded: true
    },
    "section-35": {
      provision: { /* provision data */ },
      children: [ /* nested provisions */ ],
      loaded: true
    }
  },

  // UI state
  ui: {
    activeSection: "chapter-i", // current visible section
    loadingMore: false,
    scrollPosition: 0
  }
}
```

### Data Loading Strategy

**Option A: Load All at Once (for smaller statutes)**
```javascript
// Fetch complete structure
const statute = await fetchStatute(slug)
const divisions = await fetchDivisions(slug, { per_page: 100 })
const provisions = await fetchProvisions(slug, { per_page: 100 })

// Build complete document
buildCompleteDocument(statute, divisions, provisions)
```

**Option B: Progressive Loading (for large statutes - RECOMMENDED)**
```javascript
// 1. Load metadata + TOC structure
const statute = await fetchStatute(slug)
const divisions = await fetchDivisions(slug, { per_page: 100 })

// 2. Load first visible content
const firstDivision = divisions[0]
const firstProvisions = await fetchDivisionProvisions(slug, firstDivision.slug)

// 3. Lazy load remaining content on scroll
onScroll(() => {
  if (nearBottom() && !loading) {
    loadNextSection()
  }
})
```

### Caching Strategy

```javascript
// Cache loaded content to avoid refetching
const contentCache = new Map()

function getCachedContent(slug, itemSlug) {
  const cacheKey = `${slug}-${itemSlug}`
  if (contentCache.has(cacheKey)) {
    return contentCache.get(cacheKey)
  }
  // Fetch and cache
  const content = await fetchContent(slug, itemSlug)
  contentCache.set(cacheKey, content)
  return content
}
```

---

## 8. Key Features to Implement

### 8.1 Sticky Table of Contents with Auto-Highlight

**Implementation:**

```javascript
// Use Intersection Observer to detect visible sections
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        // Update TOC highlight
        updateActiveTOCItem(entry.target.id)
      }
    })
  },
  { threshold: 0.5, rootMargin: '-80px 0px -80%' }
)

// Observe all section headers
document.querySelectorAll('[data-section-id]').forEach(el => {
  observer.observe(el)
})
```

### 8.2 Deep Linking with Auto-Scroll

**Implementation:**

```javascript
// On page load or hash change
function handleHashNavigation() {
  const hash = window.location.hash.slice(1) // Remove #
  if (!hash) return

  const targetElement = document.getElementById(hash)
  if (targetElement) {
    // Element already in DOM
    targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } else {
    // Need to load content first
    loadContentForSlug(hash).then(() => {
      document.getElementById(hash)?.scrollIntoView({ behavior: 'smooth' })
    })
  }
}

window.addEventListener('hashchange', handleHashNavigation)
```

### 8.3 Hierarchical Visual Styling

**CSS Implementation:**

```css
/* Divisions */
.division-level-1 {
  font-size: 1.75rem;
  margin-left: 0;
  border-bottom: 2px solid #333;
  padding: 1.5rem 0;
}

.division-level-2 {
  font-size: 1.5rem;
  margin-left: 1rem;
  border-bottom: 1px solid #666;
  padding: 1.25rem 0;
}

.division-level-3 {
  font-size: 1.25rem;
  margin-left: 2rem;
  padding: 1rem 0;
}

/* Provisions */
.provision-section {
  margin-left: 0;
  font-weight: 600;
  font-size: 1.1rem;
}

.provision-subsection {
  margin-left: 2rem;
  font-weight: 400;
}

.provision-paragraph {
  margin-left: 4rem;
  font-weight: 300;
}

.provision-subparagraph {
  margin-left: 6rem;
  font-size: 0.95rem;
}

/* Provision numbers */
.provision-number {
  display: inline-block;
  min-width: 3rem;
  font-weight: 600;
  color: #0066cc;
}
```

### 8.4 Section Actions (Bookmark, Share, Feedback)

**Implementation:**

```javascript
// Render action buttons for each section
<div className="section-actions">
  <button onClick={() => toggleBookmark(provision.id, 'provision')}>
    {provision.is_bookmarked ? '📌 Bookmarked' : '📌 Bookmark'}
  </button>

  <button onClick={() => copyShareLink(provision.slug)}>
    🔗 Copy Link
  </button>

  <button onClick={() => openFeedbackModal(provision)}>
    💬 Report Issue
  </button>
</div>

// Generate shareable link
function copyShareLink(itemSlug) {
  const url = `${window.location.origin}/statutes/${statuteSlug}#${itemSlug}`
  navigator.clipboard.writeText(url)
  showToast('Link copied!')
}
```

### 8.5 Search within Statute

**Implementation:**

```javascript
// Client-side search through loaded content
function searchInStatute(query) {
  const results = []

  // Search through provisions
  Object.values(content).forEach(item => {
    if (item.provision?.provision_text?.includes(query)) {
      results.push({
        type: 'provision',
        text: item.provision.provision_text,
        slug: item.provision.slug,
        context: getContext(item.provision.provision_text, query)
      })
    }
  })

  return results
}

// Highlight search term in results
function highlightSearchTerm(text, query) {
  return text.replace(
    new RegExp(query, 'gi'),
    match => `<mark>${match}</mark>`
  )
}
```

### 8.6 Reading Progress Indicator

**Implementation:**

```javascript
// Calculate and display reading progress
function updateReadingProgress() {
  const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
  const scrolled = window.scrollY
  const progress = (scrolled / scrollHeight) * 100

  document.getElementById('progress-bar').style.width = `${progress}%`
}

window.addEventListener('scroll', updateReadingProgress)
```

### 8.7 Print-Friendly View

**Implementation:**

```css
/* Print styles */
@media print {
  .toc-sidebar,
  .section-actions,
  .header-actions {
    display: none;
  }

  .statute-content {
    max-width: 100%;
  }

  .division-section,
  .provision-item {
    page-break-inside: avoid;
  }
}
```

---

## 9. Backend Limitation & Workarounds

### Current Limitation

The API returns **empty arrays** for nested relationships in single-item endpoints:

```json
{
  "division": {
    "id": 47,
    "title": "General provisions",
    "child_divisions": [],  // ❌ Empty even if children exist
    "provisions": []         // ❌ Empty even if provisions exist
  }
}
```

However, dedicated child endpoints work correctly:

```
GET /divisions/{slug}/children     ✅ Returns children
GET /divisions/{slug}/provisions   ✅ Returns provisions
GET /provisions/{slug}/children    ✅ Returns children
```

### Workaround Strategy

**Option 1: Multiple API Calls (Current Approach)**

```javascript
// Fetch parent
const division = await fetchDivision(statuteSlug, divisionSlug)

// Fetch children separately
const [children, provisions] = await Promise.all([
  fetchDivisionChildren(statuteSlug, divisionSlug),
  fetchDivisionProvisions(statuteSlug, divisionSlug)
])

// Combine data
const complete = {
  ...division,
  child_divisions: children,
  provisions: provisions
}
```

**Option 2: Request Backend Enhancement (Recommended)**

Ask backend team to add a query parameter to include nested data:

```
GET /statutes/{slug}?include=divisions.provisions.children

# Response would include full nested structure in one call
```

**Option 3: Build Complete Tree Client-Side**

```javascript
// Fetch all divisions and provisions
const [divisions, provisions] = await Promise.all([
  fetchAllDivisions(slug, { per_page: 100 }),
  fetchAllProvisions(slug, { per_page: 100 })
])

// Build tree structure
const tree = buildHierarchicalTree(divisions, provisions)

function buildHierarchicalTree(divisions, provisions) {
  // Group provisions by division
  const provisionsByDivision = groupBy(provisions, 'division_id')

  // Group nested divisions
  const divisionsByParent = groupBy(divisions, 'parent_division_id')

  // Build recursive structure
  return buildNode(null, divisionsByParent, provisionsByDivision)
}
```

### Performance Consideration

For large statutes (like the Constitution with 218 provisions):
- Use progressive loading instead of loading all at once
- Cache loaded sections to avoid refetching
- Implement virtual scrolling for very long documents

---

## 10. Mobile Considerations

### Responsive Layout

**Desktop (> 1024px):**
- Sticky sidebar TOC (20% width)
- Main content area (80% width)
- All features visible

**Tablet (768px - 1024px):**
- Collapsible TOC (hamburger menu)
- Full-width content
- Floating TOC button

**Mobile (< 768px):**
- Hidden TOC by default
- Bottom navigation bar
- Simplified hierarchy display
- Accordion-style nested sections

### Mobile-Specific Features

**1. Floating TOC Button**
```html
<button class="toc-toggle" onclick="toggleTOC()">
  📑 Table of Contents
</button>
```

**2. Bottom Navigation**
```html
<nav class="bottom-nav">
  <button onclick="previousSection()">← Previous</button>
  <button onclick="scrollToTop()">↑ Top</button>
  <button onclick="nextSection()">Next →</button>
</nav>
```

**3. Simplified Hierarchy**
- Use accordion/collapsible sections for nested content
- Reduce indentation levels
- Increase touch target sizes

**4. Gesture Support**
- Swipe left/right for previous/next section
- Pull-to-refresh for content updates
- Long-press for action menu

---

## Implementation Checklist

### Phase 1: Core Functionality
- [ ] Set up routing structure
- [ ] Create component architecture
- [ ] Implement API service layer
- [ ] Build statute header component
- [ ] Display basic statute content
- [ ] Implement pagination for provisions

### Phase 2: Navigation
- [ ] Build table of contents sidebar
- [ ] Implement smooth scroll navigation
- [ ] Add hash-based URL updates
- [ ] Implement deep linking
- [ ] Add auto-highlight current section
- [ ] Handle browser back/forward

### Phase 3: Hierarchy Display
- [ ] Implement division rendering
- [ ] Implement provision rendering
- [ ] Handle nested divisions
- [ ] Handle nested provisions
- [ ] Apply hierarchical styling
- [ ] Add visual separators

### Phase 4: Advanced Features
- [ ] Add bookmark functionality
- [ ] Implement share/copy link
- [ ] Add feedback modal integration
- [ ] Implement search within statute
- [ ] Add reading progress bar
- [ ] Implement print styles

### Phase 5: Optimization
- [ ] Add lazy loading for long statutes
- [ ] Implement content caching
- [ ] Optimize scroll performance
- [ ] Add loading skeletons
- [ ] Handle error states
- [ ] Add retry logic for failed requests

### Phase 6: Mobile
- [ ] Responsive layout
- [ ] Collapsible TOC
- [ ] Bottom navigation
- [ ] Touch gestures
- [ ] Mobile-optimized hierarchy
- [ ] Test on various devices

---

## Code Examples

### Example: Building TOC from Divisions

```javascript
function buildTOC(divisions) {
  return divisions.map(division => ({
    id: division.id,
    slug: division.slug,
    label: `${division.division_number}. ${division.division_title}`,
    level: division.level,
    type: division.division_type,
    href: `#${division.slug}`,
    children: division.child_divisions ? buildTOC(division.child_divisions) : []
  }))
}
```

### Example: Rendering Provision with Nesting

```javascript
function ProvisionItem({ provision, level = 0 }) {
  const [children, setChildren] = useState([])
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    // Check if this provision has children
    if (provision.provision_text === "[Section has subsections]") {
      loadChildren()
    }
  }, [provision])

  async function loadChildren() {
    setLoading(true)
    const data = await fetchProvisionChildren(
      provision.statute_id,
      provision.slug
    )
    setChildren(data)
    setLoading(false)
  }

  return (
    <div
      id={provision.slug}
      className={`provision-item provision-level-${level}`}
      style={{ marginLeft: `${level * 2}rem` }}
    >
      <div className="provision-header">
        <span className="provision-number">
          {provision.provision_number}
        </span>
        {provision.provision_title && (
          <span className="provision-title">
            {provision.provision_title}
          </span>
        )}
      </div>

      <div className="provision-text">
        {provision.provision_text !== "[Section has subsections]" && (
          <p>{provision.provision_text}</p>
        )}
      </div>

      {loading && <div>Loading subsections...</div>}

      {children.length > 0 && (
        <div className="provision-children">
          {children.map(child => (
            <ProvisionItem
              key={child.id}
              provision={child}
              level={level + 1}
            />
          ))}
        </div>
      )}

      <div className="section-actions">
        <button onClick={() => bookmarkProvision(provision)}>
          📌 Bookmark
        </button>
        <button onClick={() => shareProvision(provision)}>
          🔗 Share
        </button>
        <button onClick={() => reportIssue(provision)}>
          💬 Feedback
        </button>
      </div>
    </div>
  )
}
```

### Example: Smooth Scroll with Hash Update

```javascript
function scrollToSection(slug) {
  const element = document.getElementById(slug)
  if (!element) return

  // Update URL without triggering page reload
  history.pushState(null, null, `#${slug}`)

  // Smooth scroll
  element.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
    inline: 'nearest'
  })

  // Highlight briefly
  element.classList.add('highlight')
  setTimeout(() => element.classList.remove('highlight'), 2000)
}
```

---

## Summary

### Key Implementation Points

1. **Use continuous reading view** with hash-based navigation for best UX
2. **Build hierarchical TOC** from divisions for easy navigation
3. **Implement lazy loading** for large statutes to improve performance
4. **Support deep linking** to allow sharing specific sections
5. **Apply visual hierarchy** with indentation and styling
6. **Add section actions** (bookmark, share, feedback) for engagement
7. **Make it responsive** with mobile-optimized navigation
8. **Handle nested content** by fetching children on demand

### API Call Strategy

For optimal performance:
- Fetch metadata + divisions list on initial load
- Lazy load provisions as user scrolls
- Cache loaded sections to avoid refetching
- Use separate calls for nested children (due to backend limitation)

### Next Steps

1. Review this document with the development team
2. Set up basic component structure
3. Implement core navigation features
4. Add hierarchical content rendering
5. Enhance with advanced features
6. Optimize for mobile
7. Test with real statute data

---

## Support & Questions

For questions about this implementation:
- Check the API documentation: `/Docs/v2/`
- Test endpoints using the provided token
- Refer to existing case/note implementations for patterns

**Last Updated:** October 25, 2025
