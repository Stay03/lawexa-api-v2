# Statute Management API - Complete Hierarchical Navigation Guide

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
- **user** - Regular user (read-only access to published statutes)
- **admin** - Administrator (full statute management)
- **researcher** - Research access (full statute management)
- **superadmin** - Full system access (full statute management)

### Access Matrix

| Action | User | Admin | Researcher | Superadmin |
|--------|------|-------|------------|------------|
| View published statutes | ✅ | ✅ | ✅ | ✅ |
| View all statutes | ❌ | ✅ | ✅ | ✅ |
| Create statutes | ❌ | ✅ | ✅ | ✅ |
| Edit statutes | ❌ | ✅ | ✅ | ✅ |
| Delete statutes | ❌ | ✅ | ✅ | ✅ |
| Manage divisions | ❌ | ✅ | ✅ | ✅ |
| Manage provisions | ❌ | ✅ | ✅ | ✅ |

---

## System Overview

The Statute Management System provides **complete hierarchical navigation** through complex legal document structures. Unlike traditional systems, this API supports unlimited nesting depth with consistent pagination and breadcrumb navigation at every level.

### Core Navigation Features

1. **Hierarchical Drill-Down**: Navigate from statute → divisions → provisions → child provisions
2. **Breadcrumb Navigation**: Full context path at every level
3. **Consistent Pagination**: All endpoints support pagination with meta and links
4. **Filtering Support**: Status, type, and search filtering throughout
5. **Adaptive Structure**: Accommodates various legal document patterns

---

## Complete Navigation Flow

### Understanding the Flexible Hierarchy

The system supports **completely flexible hierarchical structures** for legal documents. Unlike rigid systems, this API adapts to any document pattern by using two key concepts:

#### **Core Concepts:**

1. **Divisions** = Structural containers (organize document structure)
2. **Provisions** = Content elements (contain actual legal text)

#### **Flexible Leveling System:**

- **Levels 1-10**: Both divisions and provisions support any level from 1 to 10
- **Continuous Numbering**: Levels increment as you go deeper in the hierarchy  
- **Context-Dependent**: Actual levels depend on your specific document structure
- **No Fixed Rules**: The system doesn't enforce "divisions must be levels 1-2"

#### **Overlapping Types:**

Notice that some types can be **either** divisions or provisions:

**Division Types**: `part`, `chapter`, `article`, `title`, `book`, `division`, `section`, `subsection`
**Provision Types**: `section`, `subsection`, `paragraph`, `subparagraph`, `clause`, `subclause`, `item`

**Key Insight**: A "section" can be either:
- **Division**: Structural container organizing content (no legal text)
- **Provision**: Actual legal content with text

#### **Range Field Support:**

Both divisions and provisions support an optional `range` field for indicating scope:

**Division Range Examples:**
- `"Chapter I - X"` - Indicates chapters 1 through 10
- `"Parts 1-49"` - Covers parts 1 through 49
- `"Book I - III"` - Spans books 1 through 3

**Provision Range Examples:**
- `"Section 1-10"` - Sections 1 through 10
- `"Article 1-50"` - Articles 1 through 50  
- `"Clause (a)-(z)"` - Clauses a through z

**Usage Notes:**
- Range field is optional (can be `null`)
- Useful for indicating coverage scope
- Displayed in all list and detail responses
- Can be searched and filtered

#### **Flexible Structure Examples:**

```
Example 1: Current Test Data Pattern
Statute (Implicit Level 0)
├── Division Level 1 (Chapter) 
│   ├── Division Level 2 (Part)
│   │   ├── Provision Level 3 (Section with legal text)
│   │   │   └── Provision Level 4 (Subsection with legal text)

Example 2: Complex Regulatory Pattern  
Statute (Implicit Level 0)
├── Division Level 1 (Book)
│   ├── Division Level 2 (Title) 
│   │   ├── Division Level 3 (Chapter)
│   │   │   ├── Division Level 4 (Section as structure)
│   │   │   │   └── Provision Level 5 (Subsection with text)

Example 3: Simple Act Pattern
Statute (Implicit Level 0)
├── Division Level 1 (Part)
│   ├── Provision Level 2 (Section with text)
│   │   ├── Provision Level 3 (Subsection with text)
│   │   │   └── Provision Level 4 (Clause with text)
```

**The key is flexibility**: You design the structure based on your document's needs, and the system adapts.

### Navigation Endpoints Overview

| Level | Endpoint Pattern | Purpose | Returns |
|-------|-----------------|---------|---------|
| **Division List** | `GET /statutes/{id}/divisions` | Top-level divisions | Chapters, Parts, Titles |
| **Division Children** | `GET /statutes/{id}/divisions/{divisionId}/children` | Child divisions | Nested divisions within parent |
| **Division Provisions** | `GET /statutes/{id}/divisions/{divisionId}/provisions` ✨ | Provisions in division | Sections, articles in division |
| **Provision Children** | `GET /statutes/{id}/provisions/{provisionId}/children` ✨ | Child provisions | Subsections, subclauses, etc. |

---

## Flexible Document Structure Patterns

Understanding how to model different legal document traditions using the flexible hierarchy system.

### Pattern 1: Nigerian Federal Acts (Division-Heavy Structure)

**Structure**: Uses divisions for major organization, provisions for content.

```
Federal Act
├── Division Level 1: Part I (Preliminary)
│   ├── Provision Level 2: Section 1 (Purpose)
│   │   ├── Provision Level 3: Subsection (1)
│   │   └── Provision Level 3: Subsection (2)
│   └── Provision Level 2: Section 2 (Application)
├── Division Level 1: Part II (Main Provisions)
│   ├── Provision Level 2: Section 3 (Requirements)
│   └── Provision Level 2: Section 4 (Procedures)
```

**Characteristics**:
- **Parts**: Structural divisions (level 1)  
- **Sections**: Content provisions (level 2)
- **Subsections**: Child provisions (level 3)

### Pattern 2: Constitutional Documents (Mixed Structure)

**Structure**: Chapters as divisions, articles as provisions.

```
Constitution  
├── Division Level 1: Chapter I (Fundamental Rights)
│   ├── Provision Level 2: Article 1 (Right to Life)
│   │   ├── Provision Level 3: Clause (a)
│   │   └── Provision Level 3: Clause (b) 
│   └── Provision Level 2: Article 2 (Right to Liberty)
├── Division Level 1: Chapter II (Directive Principles)
│   ├── Division Level 2: Section A (Social Principles)
│   │   ├── Provision Level 3: Article 10 (Education)
│   │   └── Provision Level 3: Article 11 (Healthcare)
```

**Characteristics**:
- **Chapters**: Major divisions (level 1)
- **Sections**: Sub-divisions where needed (level 2)  
- **Articles**: Content provisions (level 2-3)
- **Clauses**: Detailed provisions (level 3+)

### Pattern 3: Complex Regulations (Deep Hierarchy)

**Structure**: Books → Titles → Chapters → Sections → Provisions.

```
Regulatory Code
├── Division Level 1: Book I (General Provisions)
│   ├── Division Level 2: Title 1 (Scope and Application) 
│   │   ├── Division Level 3: Chapter A (Definitions)
│   │   │   ├── Division Level 4: Section I (Basic Terms)
│   │   │   │   ├── Provision Level 5: Paragraph 1.1 (Person)
│   │   │   │   └── Provision Level 5: Paragraph 1.2 (Entity)
│   │   │   └── Division Level 4: Section II (Technical Terms)
│   │   └── Division Level 3: Chapter B (Scope)
│   └── Division Level 2: Title 2 (Implementation)
```

**Characteristics**:
- **Books**: Highest-level divisions (level 1)
- **Titles**: Major subdivisions (level 2)
- **Chapters**: Topic groupings (level 3)  
- **Sections**: Can be structural divisions (level 4)
- **Paragraphs**: Content provisions (level 5+)

### Pattern 4: Simple Acts (Provision-Heavy)

**Structure**: Minimal divisions, mostly nested provisions.

```
Simple Act
├── Division Level 1: Part 1 (Main Provisions)
│   ├── Provision Level 2: Section 1 (Purpose)  
│   │   ├── Provision Level 3: Subsection (1)
│   │   │   ├── Provision Level 4: Paragraph (a)
│   │   │   │   └── Provision Level 5: Item (i)
│   │   │   └── Provision Level 4: Paragraph (b)
│   │   └── Provision Level 3: Subsection (2)
│   └── Provision Level 2: Section 2 (Definitions)
```

**Characteristics**:
- **Parts**: Minimal structural divisions (level 1)
- **Sections**: Primary content provisions (level 2)
- **Deep Nesting**: Multiple provision levels (3, 4, 5+)

### Pattern 5: Current Test Data Structure

**Structure**: Chapters → Parts → Sections → Subsections.

```
Test Statute (ID: 18)
├── Division Level 1: Chapter 1 "First Chapter" (ID: 27)
│   ├── Division Level 2: Part I "First Part" (ID: 29) 
│   │   ├── Provision Level 3: Section (1) "First section" (ID: 18)
│   │   │   ├── Provision Level 4: Subsection (a) "subone" (ID: 21)
│   │   │   └── Provision Level 4: Subsection (b) "subtwo" (ID: 23)
│   ├── Division Level 2: Part II "Second Part" (ID: 30)
│   └── Division Level 2: Part III "Third part" (ID: 32)
├── Division Level 1: Chapter 2 "Second Chapter" (ID: 28)
└── Division Level 1: Chapter 3 "Test" (ID: 31)
```

**Navigation Example**:
1. `GET /admin/statutes/18/divisions` → Shows 3 chapters
2. `GET /admin/statutes/18/divisions/27/children` → Shows 3 parts in Chapter 1  
3. `GET /admin/statutes/18/divisions/29/provisions` → Shows sections in Part I
4. `GET /admin/statutes/18/provisions/18/children` → Shows subsections in Section (1)

### Key Design Principles

#### **When to Use Divisions vs Provisions:**

**Use Divisions When**:
- Creating structural organization without legal text
- Grouping related content thematically  
- Need hierarchical navigation menus
- Organizing large documents by topic/subject

**Use Provisions When**:
- Content contains actual legal text
- Creating numbered legal rules/requirements
- Building searchable legal content
- Establishing enforceable obligations

#### **Level Assignment Strategies:**

**Conservative Approach** (Recommended):
- Start with level 1 for top-level elements
- Increment by 1 for each hierarchy level
- Leave gaps (1, 3, 5) for future intermediate levels

**Dense Approach**:  
- Use consecutive levels (1, 2, 3, 4, 5...)
- Good for well-defined, stable structures
- Easier to understand hierarchy depth

#### **Type Selection Guidelines:**

| Element | Usually Division | Usually Provision | Context Matters |
|---------|-----------------|-------------------|-----------------|
| Book, Title | ✅ | | Major structural containers |
| Chapter, Part | ✅ | | Thematic groupings |  
| Article | ✅ | ✅ | Depends on document tradition |
| Section | ✅ | ✅ | **Key decision point** |
| Subsection | | ✅ | Usually detailed content |
| Paragraph, Clause | | ✅ | Always content elements |

---

## Step-by-Step Navigation Guide

### Level 1: List Top-Level Divisions

**GET** `/admin/statutes/{statuteId}/divisions`

**Purpose**: Get the main structural divisions (chapters, parts, titles) of a statute.

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status (active, repealed, amended)
- `division_type` (optional): Filter by type (chapter, part, article, etc.)

**Example Request:**
```bash
GET /admin/statutes/18/divisions?per_page=5
```

**Response Structure:**
```json
{
  "status": "success",
  "message": "Statute divisions retrieved successfully",
  "data": {
    "divisions": [
      {
        "id": 27,
        "slug": "first-chapter",
        "statute_id": 18,
        "division_type": "chapter",
        "division_number": "1",
        "division_title": "First Chapter",
        "range": null,
        "level": 1,
        "status": "active",
        "sort_order": 1
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 4,
      "per_page": 5,
      "last_page": 1
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/statutes/18/divisions?page=1",
      "last": "https://rest.lawexa.com/api/admin/statutes/18/divisions?page=1",
      "prev": null,
      "next": null
    }
  }
}
```

**Next Step**: To see what's inside a chapter, use the division's `id` (27) in the next endpoint.

---

### Level 2: Explore Division Children

**GET** `/admin/statutes/{statuteId}/divisions/{divisionId}/children`

**Purpose**: Get child divisions within a parent division (e.g., parts within a chapter).

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `division_type` (optional): Filter by division type

**Example Request:**
```bash
GET /admin/statutes/18/divisions/27/children
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
      "type": "chapter",
      "level": 1,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "type": "chapter"
        }
      ]
    },
    "children": [
      {
        "id": 29,
        "division_type": "part",
        "division_number": "I",
        "division_title": "First Part",
        "range": "Chapter I - X",
        "level": 2,
        "parent_division_id": 27
      },
      {
        "id": 30,
        "division_type": "part",
        "division_number": "II",
        "division_title": "Second Part",
        "range": null,
        "level": 2,
        "parent_division_id": 27
      }
    ],
    "meta": {
      "has_children": true,
      "child_level": 2,
      "statute_id": "18",
      "current_page": 1,
      "total": 3,
      "per_page": 15
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/statutes/18/divisions/27/children?page=1",
      "next": null
    }
  }
}
```

**Key Features:**
- **Breadcrumb Navigation**: Shows full path from statute to current division
- **Parent Context**: Information about the parent division
- **Child Level**: Indicates the hierarchy level of children
- **Pagination**: Handles large numbers of child divisions

**Next Step**: To see the actual content (sections, articles) within "First Part" (ID: 29), use the provisions endpoint.

---

### Level 3: Get Division Provisions ✨

**GET** `/admin/statutes/{statuteId}/divisions/{divisionId}/provisions`

**Purpose**: Get the actual legal content (sections, articles, paragraphs) within a specific division.

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `provision_type` (optional): Filter by type (section, subsection, paragraph, etc.)
- `search` (optional): Search within provision text

**Example Request:**
```bash
GET /admin/statutes/18/divisions/29/provisions
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
      "type": "part",
      "level": 2,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "type": "chapter"
        },
        {
          "id": 29,
          "title": "First Part",
          "number": "I",
          "type": "part"
        }
      ]
    },
    "provisions": [
      {
        "id": 18,
        "provision_type": "section",
        "provision_number": "(1)",
        "provision_title": "First section",
        "provision_text": "lorem",
        "range": "Section 1-10",
        "level": 3,
        "parent_provision_id": null,
        "sort_order": 1
      }
    ],
    "meta": {
      "division_id": "29",
      "statute_id": "18",
      "current_page": 1,
      "total": 3,
      "per_page": 15
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/statutes/18/divisions/29/provisions?page=1",
      "next": null
    }
  }
}
```

**Key Features:**
- **Division Context**: Full information about the parent division
- **Breadcrumb Trail**: Complete navigation path
- **Legal Content**: Actual provisions with text, titles, and numbering
- **Search Capability**: Can search within provision text

**Next Step**: To see subsections within "First section" (ID: 18), use the provision children endpoint.

---

### Level 4: Get Provision Children ✨

**GET** `/admin/statutes/{statuteId}/provisions/{provisionId}/children`

**Purpose**: Get child provisions (subsections, subclauses, items) within a parent provision.

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status
- `provision_type` (optional): Filter by type
- `search` (optional): Search within provision text

**Example Request:**
```bash
GET /admin/statutes/18/provisions/18/children
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
      "type": "section",
      "level": 3,
      "breadcrumb": [
        {
          "id": 18,
          "title": "The statute",
          "type": "statute"
        },
        {
          "id": 27,
          "title": "First Chapter",
          "number": "1",
          "type": "chapter"
        },
        {
          "id": 29,
          "title": "First Part",
          "number": "I",
          "type": "part"
        },
        {
          "id": 18,
          "title": "First section",
          "number": "(1)",
          "type": "section"
        }
      ]
    },
    "children": [
      {
        "id": 21,
        "provision_type": "subsection",
        "provision_number": "(a)",
        "provision_title": "subone",
        "provision_text": "subsection one",
        "range": null,
        "level": 4,
        "parent_provision_id": 18,
        "sort_order": 10
      },
      {
        "id": 23,
        "provision_type": "subsection",
        "provision_number": "(b)",
        "provision_title": "subtwo",
        "provision_text": "subsection two",
        "range": null,
        "level": 4,
        "parent_provision_id": 18,
        "sort_order": 10
      }
    ],
    "meta": {
      "has_children": true,
      "child_level": 4,
      "parent_provision_id": "18",
      "statute_id": "18",
      "current_page": 1,
      "total": 2,
      "per_page": 15
    },
    "links": {
      "first": "https://rest.lawexa.com/api/admin/statutes/18/provisions/18/children?page=1",
      "next": null
    }
  }
}
```

**Key Features:**
- **Complete Breadcrumb**: Shows full path from statute through all divisions to current provision
- **Parent Context**: Information about the parent provision
- **Nested Content**: Child provisions with full text and metadata
- **Unlimited Depth**: Can continue nesting with further child provisions

**Continuing Further**: If subsection (a) has its own children, you can call:
```bash
GET /admin/statutes/18/provisions/21/children
```

---

## Complete Navigation Examples

### Example 1: Live Test Data (Statute ID 18) - ACTUAL API RESPONSES

**Real structure**: Chapters (Level 1) → Parts (Level 2) → Sections (Level 3) → Subsections (Level 4)

#### Step 1: Get Top-Level Divisions
```bash
GET /admin/statutes/18/divisions
```
**Returns**: 3 chapters with actual IDs and levels
```json
{
  "status": "success",
  "data": {
    "divisions": [
      {"id": 27, "division_type": "chapter", "division_number": "1", "division_title": "First Chapter", "range": null, "level": 1},
      {"id": 28, "division_type": "chapter", "division_number": "2", "division_title": "Second Chapter", "range": "Chapter I - X", "level": 1},
      {"id": 31, "division_type": "chapter", "division_number": "3", "division_title": "Test", "range": null, "level": 1}
    ],
    "meta": {"total": 3, "per_page": 15},
    "links": {"next": null}
  }
}
```

#### Step 2: Get Children of Chapter 1
```bash
GET /admin/statutes/18/divisions/27/children
```  
**Returns**: 3 parts within Chapter 1 with full breadcrumb navigation
```json
{
  "status": "success", 
  "data": {
    "parent": {
      "id": 27, "title": "First Chapter", "level": 1,
      "breadcrumb": [
        {"id": 18, "title": "The statute", "type": "statute"},
        {"id": 27, "title": "First Chapter", "number": "1", "type": "chapter"}
      ]
    },
    "children": [
      {"id": 29, "division_type": "part", "division_number": "I", "division_title": "First Part", "range": "Chapter I - V", "level": 2},
      {"id": 30, "division_type": "part", "division_number": "II", "division_title": "Second Part", "range": null, "level": 2},
      {"id": 32, "division_type": "part", "division_number": "III", "division_title": "Third part", "range": null, "level": 2}
    ],
    "meta": {"total": 3, "child_level": 2}
  }
}
```

#### Step 3: Get Provisions in Part I
```bash
GET /admin/statutes/18/divisions/29/provisions
```
**Returns**: Legal content (sections) with full context
```json
{
  "status": "success",
  "data": {
    "division": {
      "id": 29, "title": "First Part", "level": 2,
      "breadcrumb": [
        {"id": 18, "title": "The statute", "type": "statute"},
        {"id": 27, "title": "First Chapter", "number": "1", "type": "chapter"}, 
        {"id": 29, "title": "First Part", "number": "I", "type": "part"}
      ]
    },
    "provisions": [
      {"id": 18, "provision_type": "section", "provision_number": "(1)", "provision_title": "First section", "range": "Section 1-10", "level": 3}
    ],
    "meta": {"total": 3, "division_id": "29"}
  }
}
```

#### Step 4: Get Subsections within Section (1)
```bash
GET /admin/statutes/18/provisions/18/children
```
**Returns**: Child provisions with complete hierarchical context
```json
{
  "status": "success",
  "data": {
    "parent": {
      "id": 18, "title": "First section", "level": 3,
      "breadcrumb": [
        {"id": 18, "title": "The statute", "type": "statute"},
        {"id": 27, "title": "First Chapter", "number": "1", "type": "chapter"},
        {"id": 29, "title": "First Part", "number": "I", "type": "part"},
        {"id": 18, "title": "First section", "number": "(1)", "type": "section"}
      ]
    },
    "children": [
      {"id": 21, "provision_type": "subsection", "provision_number": "(a)", "provision_title": "subone", "range": null, "level": 4},
      {"id": 23, "provision_type": "subsection", "provision_number": "(b)", "provision_title": "subtwo", "range": null, "level": 4}
    ],
    "meta": {"total": 2, "child_level": 4}
  }
}
```

### Example 2: Nigerian Federal Act Pattern

**Structure**: Parts (Level 1) → Sections (Level 2) → Subsections (Level 3)

```bash
1. GET /admin/statutes/5/divisions
   → Returns: Part 1 (Preliminary), Part 2 (Procedures), etc.

2. GET /admin/statutes/5/divisions/7/provisions  
   → Returns: Section 1 (Purpose), Section 2 (Application), etc.

3. GET /admin/statutes/5/provisions/5/children
   → Returns: Subsection (1), Subsection (2), etc.
```

### Example 3: Constitutional Document Pattern  

**Structure**: Chapters (Level 1) → Articles (Level 2) → Clauses (Level 3)

```bash
1. GET /admin/statutes/12/divisions
   → Returns: Chapter I (Fundamental Rights), Chapter II (Directive Principles), etc.

2. GET /admin/statutes/12/divisions/15/provisions
   → Returns: Article 1 (Right to Life), Article 2 (Right to Liberty), etc.

3. GET /admin/statutes/12/provisions/45/children  
   → Returns: Clause (a), Clause (b), etc.
```

### Example 4: Complex Regulation Pattern

**Structure**: Books (Level 1) → Titles (Level 2) → Chapters (Level 3) → Sections (Level 4) → Paragraphs (Level 5)

```bash
1. GET /admin/statutes/20/divisions
   → Returns: Book I (General), Book II (Specific), etc.

2. GET /admin/statutes/20/divisions/22/children
   → Returns: Title 1, Title 2, etc.

3. GET /admin/statutes/20/divisions/25/children  
   → Returns: Chapter A, Chapter B, etc.

4. GET /admin/statutes/20/divisions/28/provisions
   → Returns: Paragraph 1.1, Paragraph 1.2, etc.

5. GET /admin/statutes/20/provisions/67/children
   → Returns: Subparagraph (i), (ii), (iii), etc.
```

---

## Navigation Decision Tree

Use this decision tree to determine which endpoint to use based on what type of content you're looking for:

```
What do you want to see?

├── 📋 START: Top-level structure of a statute
│   → GET /admin/statutes/{id}/divisions
│   
├── 🔍 INSIDE A DIVISION: What's contained within a specific division?
│   │
│   ├── 📁 STRUCTURAL: More organizational divisions?
│   │   │   Examples: Parts within Chapters, Articles within Parts
│   │   │   Purpose: Continue navigating the document structure  
│   │   └── → GET /admin/statutes/{id}/divisions/{divisionId}/children
│   │
│   └── 📝 CONTENT: Actual legal text and provisions?  
│       │   Examples: Sections with legal text, Articles with rules
│       │   Purpose: Get the substantive legal content
│       └── → GET /admin/statutes/{id}/divisions/{divisionId}/provisions
│
└── 🔍 INSIDE A PROVISION: What's nested within a specific provision?
    │
    └── 📝 SUB-CONTENT: Child provisions with more detailed legal text?
        │   Examples: Subsections within Sections, Clauses within Articles
        │   Purpose: Drill down into detailed legal requirements
        └── → GET /admin/statutes/{id}/provisions/{provisionId}/children
```

### **Key Decision Points:**

#### **Division Children vs Division Provisions**
- **Use `/children`** when the division contains **more divisions** (structural organization)
- **Use `/provisions`** when the division contains **legal content** (text-bearing provisions)
- **Remember**: A "Section" might be either a structural division OR a content provision

#### **Real-World Examples:**

```
Example: Chapter contains Parts (structural)
GET /admin/statutes/18/divisions/27/children
→ Returns: Part I, Part II, Part III (more divisions)

Example: Part contains Sections (content)  
GET /admin/statutes/18/divisions/29/provisions
→ Returns: Section 1, Section 2 (provisions with legal text)

Example: Section contains Subsections (detailed content)
GET /admin/statutes/18/provisions/18/children  
→ Returns: Subsection (a), Subsection (b) (child provisions)
```

#### **How to Decide:**
1. **Look at your document design**: Did you model something as a division (structural) or provision (content)?
2. **Check the content**: Does it have legal text (provision) or just organize other elements (division)?
3. **Try both endpoints**: The system will return appropriate results based on your data structure

---

## Response Pattern Consistency

All drill-down endpoints follow consistent response patterns:

### Common Response Elements

```json
{
  "status": "success",
  "message": "...",
  "data": {
    // Context (parent/division info)
    "parent": { /* or */ "division": { 
      "id": 123,
      "title": "...",
      "breadcrumb": [
        // Full navigation path
      ]
    },
    
    // Content (children/provisions)
    "children": [ /* or */ "provisions": [
      // Array of items
    ],
    
    // Pagination metadata
    "meta": {
      "current_page": 1,
      "total": 50,
      "per_page": 15,
      "last_page": 4,
      // Additional context-specific metadata
    },
    
    // Pagination navigation
    "links": {
      "first": "...",
      "last": "...",
      "prev": null,
      "next": "..."
    }
  }
}
```

### Breadcrumb Structure

All breadcrumbs follow this consistent format:

```json
"breadcrumb": [
  {
    "id": 18,
    "title": "The statute",
    "type": "statute"
  },
  {
    "id": 27,
    "title": "First Chapter",
    "number": "1",
    "type": "chapter"
  },
  {
    "id": 29,
    "title": "First Part",
    "number": "I", 
    "type": "part"
  }
  // ... continues to current level
]
```

---

## Query Parameters Reference

All drill-down endpoints support these common parameters:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `per_page` | integer | Items per page (1-100, default: 15) | `?per_page=25` |
| `page` | integer | Page number (default: 1) | `?page=2` |
| `status` | string | Filter by status | `?status=active` |
| `provision_type` | string | Filter provisions by type | `?provision_type=section` |
| `division_type` | string | Filter divisions by type | `?division_type=chapter` |
| `search` | string | Search within text content | `?search=criminal` |

### Combined Parameters

```bash
# Get active sections in a division with search
GET /admin/statutes/18/divisions/29/provisions?status=active&provision_type=section&search=purpose&per_page=10

# Get child subsections with pagination
GET /admin/statutes/18/provisions/18/children?provision_type=subsection&page=2&per_page=5
```

---

## Status Codes and Error Handling

### Success Responses

| Code | Description |
|------|-------------|
| `200` | Success - content retrieved |
| `201` | Success - content created |

### Error Responses

| Code | Description | Example |
|------|-------------|---------|
| `404` | Resource not found | Statute, division, or provision doesn't exist |
| `400` | Bad request | Invalid query parameters |
| `401` | Unauthorized | Missing or invalid token |
| `403` | Forbidden | Insufficient permissions |
| `422` | Validation error | Invalid input data |

### Error Response Format

```json
{
  "status": "error",
  "message": "Provision not found",
  "data": null,
  "errors": {
    "provision_id": ["The specified provision does not exist"]
  }
}
```

---

## Performance Considerations

### Pagination Best Practices

1. **Use appropriate page sizes**: Default is 15, max is 100
2. **Implement lazy loading**: Load additional pages as needed
3. **Cache frequently accessed content**: Especially for navigation breadcrumbs
4. **Monitor total counts**: Use meta.total for UI indicators

### Efficient Navigation

1. **Preload breadcrumbs**: Cache navigation paths for quick access
2. **Batch related requests**: Consider loading sibling content
3. **Use search effectively**: Filter large result sets
4. **Implement client-side caching**: Cache navigation structures

### Large Document Handling

For statutes with thousands of provisions:

1. **Use search parameters**: Filter before paginating
2. **Implement progressive loading**: Load structure first, content on demand
3. **Consider result limits**: Implement reasonable per_page limits
4. **Use status filtering**: Show only active provisions by default

---

## Frontend Implementation Examples

### React Navigation Component

```jsx
function StatuteNavigator({ statuteId }) {
  const [breadcrumb, setBreadcrumb] = useState([]);
  const [currentLevel, setCurrentLevel] = useState('divisions');
  const [currentItems, setCurrentItems] = useState([]);
  const [currentParentId, setCurrentParentId] = useState(null);

  const navigateToLevel = async (level, parentId = null) => {
    let endpoint;
    
    switch(level) {
      case 'divisions':
        endpoint = `/admin/statutes/${statuteId}/divisions`;
        break;
      case 'division-children':
        endpoint = `/admin/statutes/${statuteId}/divisions/${parentId}/children`;
        break;
      case 'division-provisions':
        endpoint = `/admin/statutes/${statuteId}/divisions/${parentId}/provisions`;
        break;
      case 'provision-children':
        endpoint = `/admin/statutes/${statuteId}/provisions/${parentId}/children`;
        break;
    }
    
    const response = await fetch(endpoint, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      setCurrentItems(data.data.children || data.data.provisions || data.data.divisions);
      if (data.data.parent?.breadcrumb || data.data.division?.breadcrumb) {
        setBreadcrumb(data.data.parent?.breadcrumb || data.data.division?.breadcrumb);
      }
      setCurrentLevel(level);
      setCurrentParentId(parentId);
    }
  };

  return (
    <div className="statute-navigator">
      {/* Breadcrumb */}
      <nav className="breadcrumb">
        {breadcrumb.map((crumb, index) => (
          <span key={crumb.id}>
            {crumb.title}
            {index < breadcrumb.length - 1 && ' → '}
          </span>
        ))}
      </nav>
      
      {/* Content List */}
      <div className="content-list">
        {currentItems.map(item => (
          <div key={item.id} className="content-item">
            <h3>{item.division_title || item.provision_title || 'Untitled'}</h3>
            <p>Number: {item.division_number || item.provision_number}</p>
            <p>Type: {item.division_type || item.provision_type}</p>
            
            <div className="actions">
              {item.division_type && (
                <>
                  <button onClick={() => navigateToLevel('division-children', item.id)}>
                    View Children
                  </button>
                  <button onClick={() => navigateToLevel('division-provisions', item.id)}>
                    View Provisions
                  </button>
                </>
              )}
              {item.provision_type && (
                <button onClick={() => navigateToLevel('provision-children', item.id)}>
                  View Subsections
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### Vue.js Navigation Store

```javascript
// store/statute-navigation.js
export const useStatuteNavigation = () => {
  const state = reactive({
    currentPath: [],
    currentItems: [],
    loading: false,
    error: null
  });

  const navigateTo = async (endpoint, level) => {
    state.loading = true;
    state.error = null;
    
    try {
      const response = await $fetch(endpoint, {
        headers: { 'Authorization': `Bearer ${getToken()}` }
      });
      
      if (response.status === 'success') {
        // Update current items
        state.currentItems = response.data.children || 
                            response.data.provisions || 
                            response.data.divisions;
        
        // Update breadcrumb path
        if (response.data.parent?.breadcrumb || response.data.division?.breadcrumb) {
          state.currentPath = response.data.parent?.breadcrumb || 
                             response.data.division?.breadcrumb;
        }
      }
    } catch (error) {
      state.error = error.message;
    } finally {
      state.loading = false;
    }
  };

  const goToStatuteDivisions = (statuteId) => 
    navigateTo(`/admin/statutes/${statuteId}/divisions`, 'divisions');
  
  const goToDivisionChildren = (statuteId, divisionId) =>
    navigateTo(`/admin/statutes/${statuteId}/divisions/${divisionId}/children`, 'division-children');
  
  const goToDivisionProvisions = (statuteId, divisionId) =>
    navigateTo(`/admin/statutes/${statuteId}/divisions/${divisionId}/provisions`, 'division-provisions');
  
  const goToProvisionChildren = (statuteId, provisionId) =>
    navigateTo(`/admin/statutes/${statuteId}/provisions/${provisionId}/children`, 'provision-children');

  return {
    state: readonly(state),
    goToStatuteDivisions,
    goToDivisionChildren,
    goToDivisionProvisions,
    goToProvisionChildren
  };
};
```

---

## Advanced Features

### Search Across Hierarchy

You can search within provisions at any level:

```bash
# Search for "criminal" in all provisions of a division
GET /admin/statutes/18/divisions/29/provisions?search=criminal

# Search for "procedure" in child provisions
GET /admin/statutes/18/provisions/18/children?search=procedure&provision_type=subsection
```

### Filtering by Status and Type

```bash
# Get only active chapters
GET /admin/statutes/18/divisions?status=active&division_type=chapter

# Get only section-type provisions in a division
GET /admin/statutes/18/divisions/29/provisions?provision_type=section&status=active
```

### Bulk Navigation

For efficient frontend loading, you can make multiple requests:

```javascript
// Load division structure and provisions in parallel
const [divisionsResponse, provisionsResponse] = await Promise.all([
  fetch(`/admin/statutes/${statuteId}/divisions/${divisionId}/children`),
  fetch(`/admin/statutes/${statuteId}/divisions/${divisionId}/provisions`)
]);
```

---

## Document Structure Design Guide

This section provides comprehensive guidance on designing your legal document hierarchies using the flexible system.

### Phase 1: Document Analysis

Before creating any statute, thoroughly analyze your source document:

#### **1.1 Identify Document Elements**

Create an inventory of all structural and content elements:

```
Example: Nigerian Constitution Analysis
✅ Structural Elements (Divisions):
  - Chapters (Chapter I, Chapter II, etc.)
  - Parts (Part A, Part B within chapters)  
  - Sections (when used organizationally)

✅ Content Elements (Provisions):  
  - Articles (with legal text)
  - Sections (with legal text)
  - Subsections (detailed requirements)
  - Clauses (specific conditions)
```

#### **1.2 Map Hierarchical Relationships**

Document the nesting pattern:

```
Constitution
├── Chapter I (Fundamental Rights)        [Division, Level 1]
│   ├── Part A (Right to Life)            [Division, Level 2] 
│   │   ├── Article 33 (Protection)       [Provision, Level 3]
│   │   │   ├── Subsection (1)            [Provision, Level 4]
│   │   │   └── Subsection (2)            [Provision, Level 4]
│   │   └── Article 34 (Enforcement)      [Provision, Level 3]
│   └── Part B (Right to Liberty)         [Division, Level 2]
└── Chapter II (Directive Principles)     [Division, Level 1]
```

#### **1.3 Decision Matrix**

For each element, decide: Division or Provision?

| Element | Has Legal Text? | Organizes Others? | Searchable Content? | **Recommendation** |
|---------|----------------|-------------------|--------------------|--------------------|
| Chapter I | No | Yes (contains parts) | No | **Division** |
| Part A | No | Yes (contains articles) | No | **Division** |  
| Article 33 | Yes | Maybe (contains subsections) | Yes | **Provision** |
| Subsection (1) | Yes | No | Yes | **Provision** |

### Phase 2: Level Assignment Strategy

#### **2.1 Conservative Approach (Recommended)**

Start with lower levels and leave room for expansion:

```
Level 1-2: Major structural divisions
Level 3-4: Content provisions  
Level 5-6: Detailed sub-provisions
Level 7-10: Reserved for future expansion
```

**Benefits**: Future-proof, easy to insert intermediate levels

#### **2.2 Dense Approach**

Use consecutive levels with no gaps:

```
Level 1: Books/Titles
Level 2: Chapters  
Level 3: Parts
Level 4: Sections
Level 5: Subsections
Level 6: Clauses
```

**Benefits**: Clear hierarchy depth, intuitive numbering

#### **2.3 Semantic Approach**

Assign levels based on legal significance:

```
Level 1: Constitutional/Fundamental divisions
Level 2: Primary subject matter divisions  
Level 3: Core legal provisions
Level 4: Implementation details
Level 5: Technical specifications
```

### Phase 3: Implementation Planning

#### **3.1 Creation Order Strategy**

**Top-Down Approach** (Recommended):
```
1. Create statute shell
2. Create all divisions (structure first)  
3. Create provisions within divisions
4. Create child provisions within provisions
```

**Bottom-Up Approach**:
```  
1. Create statute shell
2. Create deepest content first
3. Build structure around content
4. Connect relationships last
```

#### **3.2 Numbering Schemes**

Plan your numbering system before implementation:

**Division Numbering Examples**:
- Chapters: `1`, `2`, `3` or `I`, `II`, `III` 
- Parts: `A`, `B`, `C` or `1`, `2`, `3`
- Sections: `1.1`, `1.2` or `A`, `B`, `C`

**Provision Numbering Examples**:
- Sections: `1`, `2`, `3` or `101`, `102`, `103`
- Subsections: `(1)`, `(2)` or `(a)`, `(b)` or `.1`, `.2`
- Clauses: `(i)`, `(ii)` or `(A)`, `(B)`

#### **3.3 Content Strategy** 

**Structural Divisions (No Legal Text)**:
```json
{
  "division_type": "chapter",
  "division_title": "Fundamental Rights",
  "content": null,  // No legal text
  "range": "Chapter I - VIII",
  "level": 1
}
```

**Content Provisions (With Legal Text)**:
```json
{
  "provision_type": "article", 
  "provision_title": "Right to Life",
  "provision_text": "Every person has a right to life...",  // Legal text
  "range": "Article 1-50",
  "level": 3
}
```

### Phase 4: Common Patterns and Solutions

#### **4.1 Mixed Section Usage**

When "sections" appear as both divisions and provisions:

**Pattern**: Constitutional chapters with structural sections and content sections

```
Solution:
├── Chapter I (Division, Level 1)
│   ├── Section A - Rights Overview (Division, Level 2) 
│   │   └── Section 1 - Right to Life (Provision, Level 3)
│   └── Section B - Enforcement (Division, Level 2)
│       └── Section 10 - Court Procedures (Provision, Level 3)
```

**Implementation**:
- Use `division_type: "section"` for structural sections  
- Use `provision_type: "section"` for content sections
- Different levels distinguish their roles

#### **4.2 Deep Nesting Challenges**

When documents have 6+ hierarchy levels:

**Problem**: `Part → Chapter → Section → Subsection → Paragraph → Subparagraph → Item`

**Solutions**:

**Option A: Collapse Some Levels**
```
Part (Division, Level 1)  
→ Chapter (Division, Level 2)
→ Section (Provision, Level 3) 
→ Paragraph (Provision, Level 4)  [Skip subsection level]
→ Item (Provision, Level 5)       [Skip subparagraph level]
```

**Option B: Use All Levels**
```
Part (Division, Level 1)
→ Chapter (Division, Level 2) 
→ Section (Provision, Level 3)
→ Subsection (Provision, Level 4)
→ Paragraph (Provision, Level 5)
→ Subparagraph (Provision, Level 6)
→ Item (Provision, Level 7)
```

#### **4.3 Irregular Structures**

When documents don't follow consistent patterns:

**Problem**: Some chapters have parts, others don't

**Solution**: Use nullable parent relationships
```
Regular: Chapter → Part → Section
Irregular: Chapter → Section (no part)

Implementation:
- Section.division_id can point to either Chapter or Part
- Use different levels to maintain hierarchy consistency
```

### Phase 5: Validation and Testing

#### **5.1 Structure Validation Checklist**

Before finalizing your design:

```
✅ Hierarchy Consistency
  - All child levels > parent levels
  - No gaps that break navigation
  - Consistent numbering within levels

✅ Content Distribution  
  - Legal text only in provisions
  - Structural elements only in divisions
  - Searchable content properly tagged

✅ Navigation Completeness
  - All endpoints return expected content
  - Breadcrumbs show complete paths
  - Pagination works at all levels

✅ Real-World Usability
  - Structure matches legal professionals' expectations
  - Navigation follows logical document flow  
  - Search finds relevant content effectively
```

#### **5.2 Testing Strategy**

**Phase A: Structure Testing**
```
1. Test basic navigation flow
2. Verify breadcrumb accuracy  
3. Check pagination at each level
4. Test filtering and search
```

**Phase B: Content Testing**
```
1. Verify legal text appears correctly
2. Test cross-references between provisions
3. Check hierarchical relationships
4. Validate numbering consistency
```

**Phase C: Performance Testing**  
```
1. Test large document navigation
2. Verify pagination performance
3. Check search response times
4. Test concurrent access patterns
```

### Phase 6: Best Practices Summary

#### **DO:**
- ✅ Plan your entire structure before implementation
- ✅ Use divisions for structure, provisions for content  
- ✅ Leave room for future expansion in level assignments
- ✅ Test navigation flows early and often
- ✅ Document your design decisions for future maintainers
- ✅ Use consistent numbering schemes throughout
- ✅ Validate with legal professionals familiar with the document

#### **DON'T:**
- ❌ Mix structural and content elements arbitrarily
- ❌ Use all 10 levels unless absolutely necessary
- ❌ Change level assignments after implementation
- ❌ Ignore the logical flow of the source document  
- ❌ Create levels that won't be used
- ❌ Forget to plan for document amendments/updates

#### **REMEMBER:**
- The system adapts to your design choices
- Flexibility is the key strength - use it wisely
- Consistent patterns make navigation predictable
- Good structure design pays dividends in usability
- Your design should match how legal professionals think about the document

---

## Migration from Old System

If you're upgrading from the previous API, here's the mapping:

### Old Endpoints → New Drill-Down Endpoints

| Old Pattern | New Pattern | Benefits |
|-------------|-------------|----------|
| `GET /provisions?division_id=X` | `GET /divisions/{id}/provisions` ✨ | Breadcrumbs + context |
| `GET /provisions?parent_provision_id=X` | `GET /provisions/{id}/children` ✨ | Breadcrumbs + pagination |
| Manual breadcrumb building | Automatic in all responses | Consistent navigation |
| No pagination on filtered results | All endpoints paginated | Better performance |

### Breaking Changes

1. **Response Structure**: Breadcrumbs now included in all drill-down endpoints
2. **Pagination**: All list endpoints now use pagination by default
3. **Context Information**: Parent/division context included in responses
4. **Filter Parameters**: Some parameter names may have changed

### Migration Steps

1. **Update Frontend Routes**: Map old navigation to new drill-down endpoints
2. **Handle Breadcrumbs**: Use provided breadcrumbs instead of building manually
3. **Implement Pagination**: Handle paginated responses throughout
4. **Update Error Handling**: New error response format
5. **Test Navigation Flows**: Ensure all drill-down paths work correctly

---

## Best Practices Summary

### Navigation Design

1. **Follow the Flow**: Use divisions → children → provisions → children pattern
2. **Show Context**: Always display breadcrumbs for user orientation
3. **Handle Empty States**: Gracefully handle divisions/provisions with no children
4. **Implement Search**: Provide search functionality at each level
5. **Use Pagination**: Implement proper pagination controls

### Performance

1. **Cache Strategically**: Cache navigation structures but not detailed content
2. **Lazy Load**: Load children on demand, not eagerly
3. **Batch Requests**: Use parallel requests for related data
4. **Monitor Usage**: Track which endpoints are called most frequently

### User Experience

1. **Clear Navigation**: Make it obvious how to go up/down the hierarchy
2. **Loading States**: Show loading indicators during navigation
3. **Breadcrumb Clicks**: Make breadcrumb elements clickable for quick navigation
4. **Search Integration**: Integrate search results with navigation context
5. **Mobile Friendly**: Ensure navigation works well on mobile devices

---

## Support and Troubleshooting

### Common Issues

**Issue**: "Division has no children but I know it should have provisions"
**Solution**: Use `/divisions/{id}/provisions` instead of `/divisions/{id}/children` - provisions are separate from child divisions.

**Issue**: "Breadcrumbs are missing or incomplete"
**Solution**: Ensure you're using the drill-down endpoints, not the basic list endpoints. Breadcrumbs are only available on navigation endpoints.

**Issue**: "Pagination not working as expected"
**Solution**: Check that you're passing `per_page` and `page` parameters correctly. Default page size is 15.

**Issue**: "Search returns too many results"
**Solution**: Combine search with status/type filters to narrow results: `?search=term&status=active&provision_type=section`

### Getting Help

For additional support:
1. Check the error response messages for specific guidance
2. Verify authentication tokens are valid and have proper permissions
3. Ensure all required path parameters are provided
4. Test with smaller page sizes if experiencing timeout issues

---

This comprehensive API provides complete hierarchical navigation through any legal document structure while maintaining performance, consistency, and ease of use. The drill-down approach ensures users can efficiently navigate from high-level structure to specific legal provisions with full context at every step.