# Statute Management API - Admin Documentation

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

The Statute Management System is designed with **maximum flexibility** to accommodate various legal document structures. Unlike rigid systems that force specific hierarchies, this system adapts to different legal traditions and document patterns.

### Core Flexibility Features

1. **Configurable Division Types**: Parts, Chapters, Articles, Titles, Books, Divisions, Sections, Subsections
2. **Configurable Provision Types**: Sections, Subsections, Paragraphs, Subparagraphs, Clauses, Subclauses, Items
3. **Dynamic Level Assignment**: 10-level deep hierarchy support (levels 1-10)
4. **Flexible Parent-Child Relationships**: Any division/provision can have children
5. **Adaptive Sort Ordering**: Maintains proper sequence regardless of structure

---

## Flexible Hierarchy Architecture

### Database Schema Flexibility

The system uses two main tables for content organization:

#### Statute Divisions Table
```sql
division_type: ['part', 'chapter', 'article', 'title', 'book', 'division', 'section', 'subsection']
level: INTEGER (1-10) -- Hierarchy depth
parent_division_id: FOREIGN KEY -- For nested divisions
sort_order: INTEGER -- Sequencing within same level
```

#### Statute Provisions Table  
```sql
provision_type: ['section', 'subsection', 'paragraph', 'subparagraph', 'clause', 'subclause', 'item']
level: INTEGER (1-10) -- Hierarchy depth  
parent_provision_id: FOREIGN KEY -- For nested provisions
division_id: FOREIGN KEY -- Links to parent division
sort_order: INTEGER -- Sequencing within same level
```

### Hierarchy Patterns

The system supports various legal document patterns:

#### Pattern 1: Nigerian Federal Acts
```
Statute (Level 0)
├── Part I (Division, Level 1)
│   ├── Section 1 (Provision, Level 2)
│   │   ├── Subsection (1) (Child Provision, Level 3)
│   │   └── Subsection (2) (Child Provision, Level 3)
│   └── Section 2 (Provision, Level 2)
└── Part II (Division, Level 1)
```

#### Pattern 2: Constitutional Documents
```
Constitution (Level 0)
├── Chapter I (Division, Level 1)
│   ├── Section 1 (Provision, Level 2)
│   └── Section 2 (Provision, Level 2)
│       ├── Subsection (1) (Child Provision, Level 3)
│       └── Subsection (2) (Child Provision, Level 3)
└── Chapter II (Division, Level 1)
```

#### Pattern 3: Regulations/By-laws
```
Regulation (Level 0)
├── Article 1 (Division, Level 1)
│   ├── Paragraph 1.1 (Provision, Level 2)
│   │   ├── Subparagraph (a) (Child Provision, Level 3)
│   │   └── Subparagraph (b) (Child Provision, Level 3)
│   └── Paragraph 1.2 (Provision, Level 2)
└── Article 2 (Division, Level 1)
```

#### Pattern 4: Complex Nested Structure
```
Code (Level 0)
├── Book I (Division, Level 1)
│   ├── Title 1 (Child Division, Level 2)
│   │   ├── Chapter A (Child Division, Level 3)
│   │   │   ├── Section 1 (Provision, Level 4)
│   │   │   │   ├── Subsection (1) (Child Provision, Level 5)
│   │   │   │   └── Subsection (2) (Child Provision, Level 5)
│   │   │   └── Section 2 (Provision, Level 4)
│   │   └── Chapter B (Child Division, Level 3)
│   └── Title 2 (Child Division, Level 2)
└── Book II (Division, Level 1)
```

---

## Complete API Reference

### 1. Statute Management

#### Create Statute

**POST** `/admin/statutes`

Creates a new statute with basic metadata. This is always the first step.

**Request Body:**
```json
{
  "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
  "short_title": "ACJA 2015",
  "year_enacted": 2015,
  "commencement_date": "2015-05-13",
  "status": "active",
  "jurisdiction": "Federal",
  "country": "Nigeria",
  "state": null,
  "local_government": null,
  "citation_format": "ACJA 2015",
  "sector": "Criminal Justice",
  "tags": ["criminal justice", "administration", "courts", "federal"],
  "description": "An Act to provide for the administration of criminal justice in the courts of the Federal Capital Territory and other federal courts in Nigeria; and for related matters.",
  "range": "Parts 1-49"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Statute created successfully",
  "data": {
    "statute": {
      "id": 5,
      "slug": "administration-of-criminal-justice-act-2015",
      "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
      "short_title": "ACJA 2015",
      "year_enacted": 2015,
      "commencement_date": "2015-05-13",
      "status": "active",
      "jurisdiction": "Federal",
      "country": "Nigeria",
      "citation_format": "ACJA 2015",
      "sector": "Criminal Justice",
      "tags": ["criminal justice", "administration", "courts", "federal"],
      "description": "An Act to provide for the administration of criminal justice...",
      "range": "Parts 1-49",
      "created_at": "2025-08-05 01:44:13",
      "updated_at": "2025-08-05 01:44:13",
      "creator": {
        "id": 47,
        "name": "Shannon Pfannerstill"
      },
      "files": []
    }
  }
}
```

#### List Statutes with Hierarchy

**GET** `/admin/statutes`

**Response shows nested structure:**
```json
{
  "status": "success",
  "message": "Statutes retrieved successfully",
  "data": {
    "statutes": [
      {
        "id": 5,
        "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
        "divisions": [
          {
            "id": 7,
            "division_type": "part",
            "division_number": "1",
            "division_title": "PRELIMINARY",
            "level": 1,
            "sort_order": 1,
            "divisions_count": 0,
            "provisions_count": 2
          }
        ],
        "divisions_count": 1
      }
    ]
  }
}
```

#### Get Specific Statute with Full Hierarchy

**GET** `/admin/statutes/{id}`

**Response with complete nested structure:**
```json
{
  "status": "success",
  "message": "Statute retrieved successfully",
  "data": {
    "statute": {
      "id": 5,
      "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
      "divisions": [
        {
          "id": 7,
          "division_type": "part",
          "division_number": "1", 
          "division_title": "PRELIMINARY",
          "level": 1,
          "sort_order": 1,
          "child_divisions": [],
          "provisions": [
            {
              "id": 5,
              "provision_type": "section",
              "provision_number": "1",
              "provision_title": "Purpose",
              "level": 2,
              "sort_order": 1,
              "child_provisions": [
                {
                  "id": 6,
                  "provision_type": "subsection",
                  "provision_number": "(1)",
                  "provision_text": "The purpose of this Act is to ensure...",
                  "level": 3,
                  "sort_order": 1,
                  "child_provisions": []
                },
                {
                  "id": 7,
                  "provision_type": "subsection", 
                  "provision_number": "(2)",
                  "provision_text": "The courts, law enforcement agencies...",
                  "level": 3,
                  "sort_order": 2,
                  "child_provisions": []
                }
              ],
              "child_provisions_count": 2
            }
          ],
          "provisions_count": 2
        }
      ],
      "divisions_count": 1
    }
  }
}
```

### 2. Division Management

#### Create Division

**POST** `/admin/statutes/{statute_id}/divisions`

Divisions are organizational units. Choose the appropriate `division_type` and `level` based on your document structure.

**Level Assignment Guidelines:**
- **Level 1**: Primary divisions (Parts, Books, Titles)
- **Level 2**: Secondary divisions (Chapters, Articles) 
- **Level 3**: Tertiary divisions (Sections as divisions, Sub-articles)
- **Level 4+**: Further nested divisions as needed

**Request for Primary Division (Part):**
```json
{
  "division_type": "part",
  "division_number": "1",
  "division_title": "PRELIMINARY", 
  "division_subtitle": null,
  "content": "This part contains preliminary provisions relating to the purpose and application of the Administration of Criminal Justice Act.",
  "parent_division_id": null,
  "sort_order": 1,
  "level": 1,
  "status": "active"
}
```

**Request for Nested Division (Chapter under Part):**
```json
{
  "division_type": "chapter",
  "division_number": "A",
  "division_title": "General Provisions",
  "division_subtitle": "Scope and Application", 
  "content": "This chapter outlines the general provisions...",
  "parent_division_id": 7,
  "sort_order": 1,
  "level": 2,
  "status": "active"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Division created successfully",
  "data": {
    "division": {
      "id": 7,
      "division_type": "part",
      "division_number": "1",
      "division_title": "PRELIMINARY",
      "level": 1,
      "sort_order": 1,
      "statute_id": 5,
      "slug": "preliminary",
      "created_at": "2025-08-05T01:44:25.000000Z"
    }
  }
}
```

#### Division Types Reference

| Type | Common Use | Level Suggestion | Example |
|------|------------|------------------|---------|
| `part` | Major divisions in Acts | 1 | Part I, Part II |
| `chapter` | Sections within parts | 1-2 | Chapter 1, Chapter A |
| `article` | Individual articles | 1-2 | Article 1, Article 2 |
| `title` | Major groupings | 1 | Title I, Title II |
| `book` | Largest divisions | 1 | Book I, Book II |
| `division` | Generic divisions | 1-3 | Division A, Division 1 |
| `section` | When sections are structural | 2-3 | Section I (as division) |
| `subsection` | Sub-organizational units | 3-4 | Subsection A |

### 3. Provision Management

#### Create Provision (Section)

**POST** `/admin/statutes/{statute_id}/provisions`

Provisions contain the actual legal text. Choose appropriate `provision_type` and `level`.

**Level Assignment Guidelines:**
- **Level 2**: Primary provisions (Sections, Paragraphs)
- **Level 3**: Secondary provisions (Subsections, Subparagraphs) 
- **Level 4**: Tertiary provisions (Clauses, Items)
- **Level 5+**: Further nested provisions

**Request for Section:**
```json
{
  "provision_type": "section",
  "provision_number": "1", 
  "provision_title": "Purpose",
  "provision_text": "This section establishes the purpose and compliance requirements for the administration of criminal justice.",
  "marginal_note": "Purpose of Act",
  "interpretation_note": "This section defines the overall objectives of the ACJA 2015",
  "division_id": 7,
  "parent_provision_id": null,
  "sort_order": 1,
  "level": 2,
  "status": "active"
}
```

**Request for Subsection (Child Provision):**
```json
{
  "provision_type": "subsection",
  "provision_number": "(1)",
  "provision_title": null,
  "provision_text": "The purpose of this Act is to ensure that the system of administration of criminal justice in Nigeria promotes efficient management of criminal justice institutions, speedy dispensation of justice, protection of the society from crime and protection of the rights and interests of the suspect, the defendant, and the victim.",
  "division_id": 7,
  "parent_provision_id": 5,
  "sort_order": 1,
  "level": 3,
  "status": "active"
}
```

**Response:**
```json
{
  "status": "success", 
  "message": "Provision created successfully",
  "data": {
    "provision": {
      "id": 6,
      "provision_type": "subsection",
      "provision_number": "(1)",
      "provision_text": "The purpose of this Act is to ensure...",
      "level": 3,
      "sort_order": 1,
      "parent_provision_id": 5,
      "division_id": 7,
      "statute_id": 5,
      "created_at": "2025-08-05T01:44:46.000000Z"
    }
  }
}
```

#### Provision Types Reference

| Type | Common Use | Level Suggestion | Parent | Example |
|------|------------|------------------|--------|---------|
| `section` | Primary legal provisions | 2 | Division | Section 1, Section 2 |
| `subsection` | Sub-provisions of sections | 3 | Section | (1), (2), (a), (b) |
| `paragraph` | Primary provisions in articles | 2-3 | Division/Section | Paragraph 1, Para (a) |
| `subparagraph` | Sub-provisions of paragraphs | 4 | Paragraph | (i), (ii), (A), (B) |
| `clause` | Detailed provisions | 3-4 | Section/Paragraph | Clause (a), Clause (i) |
| `subclause` | Sub-provisions of clauses | 5 | Clause | (A), (B), (I), (II) |
| `item` | List items | 3-5 | Any | Item 1, Item (a) |

---

## Frontend Implementation Guide

### 1. Hierarchy Building Interface

#### Dynamic Form Generation

Create forms that adapt based on document type:

```javascript
// Example: Form configuration based on document pattern
const documentPatterns = {
  'federal-act': {
    divisions: [
      { type: 'part', level: 1, label: 'Part' },
      { type: 'chapter', level: 2, label: 'Chapter' }
    ],
    provisions: [
      { type: 'section', level: 2, label: 'Section' },
      { type: 'subsection', level: 3, label: 'Subsection' }
    ]
  },
  'constitution': {
    divisions: [
      { type: 'chapter', level: 1, label: 'Chapter' }
    ],
    provisions: [
      { type: 'section', level: 2, label: 'Section' },
      { type: 'subsection', level: 3, label: 'Subsection' }
    ]
  }
}
```

#### Level Validation

Implement client-side validation for proper hierarchy:

```javascript
function validateHierarchy(parentLevel, childLevel, maxDepth = 10) {
  if (childLevel <= parentLevel) {
    throw new Error('Child level must be greater than parent level');
  }
  if (childLevel > maxDepth) {
    throw new Error(`Maximum hierarchy depth is ${maxDepth}`);
  }
  if (childLevel - parentLevel > 1) {
    console.warn('Skipping hierarchy levels - consider intermediate levels');
  }
  return true;
}
```

### 2. Hierarchical Display Components

#### Recursive Rendering

```jsx
function StatuteHierarchy({ statute }) {
  return (
    <div className="statute">
      <h1>{statute.title}</h1>
      {statute.divisions.map(division => 
        <DivisionComponent key={division.id} division={division} />
      )}
    </div>
  );
}

function DivisionComponent({ division }) {
  const indent = `ml-${division.level * 4}`;
  
  return (
    <div className={`division ${indent}`}>
      <h2>{division.division_number}. {division.division_title}</h2>
      
      {/* Nested divisions */}
      {division.child_divisions?.map(childDiv =>
        <DivisionComponent key={childDiv.id} division={childDiv} />
      )}
      
      {/* Provisions in this division */}
      {division.provisions?.map(provision =>
        <ProvisionComponent key={provision.id} provision={provision} />
      )}
    </div>
  );
}

function ProvisionComponent({ provision }) {
  const indent = `ml-${provision.level * 4}`;
  
  return (
    <div className={`provision ${indent}`}>
      <div className="provision-header">
        <span className="number">{provision.provision_number}</span>
        {provision.provision_title && (
          <span className="title">{provision.provision_title}</span>
        )}
      </div>
      <div className="provision-text">{provision.provision_text}</div>
      
      {/* Child provisions */}
      {provision.child_provisions?.map(childProv =>
        <ProvisionComponent key={childProv.id} provision={childProv} />
      )}
    </div>
  );
}
```

### 3. Creation Workflow

#### Step-by-Step Statute Creation

```javascript
class StatuteBuilder {
  constructor() {
    this.statute = null;
    this.divisions = [];
    this.provisions = [];
  }
  
  async createStatute(statuteData) {
    const response = await api.post('/admin/statutes', statuteData);
    this.statute = response.data.statute;
    return this.statute;
  }
  
  async addDivision(divisionData) {
    // Validate level assignment
    this.validateDivisionLevel(divisionData);
    
    const response = await api.post(
      `/admin/statutes/${this.statute.id}/divisions`, 
      divisionData
    );
    this.divisions.push(response.data.division);
    return response.data.division;
  }
  
  async addProvision(provisionData) {
    // Validate level assignment and parent relationships
    this.validateProvisionLevel(provisionData);
    
    const response = await api.post(
      `/admin/statutes/${this.statute.id}/provisions`,
      provisionData  
    );
    this.provisions.push(response.data.provision);
    return response.data.provision;
  }
  
  validateDivisionLevel(divisionData) {
    if (divisionData.parent_division_id) {
      const parent = this.divisions.find(d => d.id === divisionData.parent_division_id);
      if (parent && divisionData.level <= parent.level) {
        throw new Error('Child division level must be greater than parent');
      }
    }
  }
  
  validateProvisionLevel(provisionData) {
    // Check division level constraint
    if (provisionData.division_id) {
      const division = this.divisions.find(d => d.id === provisionData.division_id);
      if (division && provisionData.level <= division.level) {
        throw new Error('Provision level must be greater than division level');
      }
    }
    
    // Check parent provision level constraint  
    if (provisionData.parent_provision_id) {
      const parent = this.provisions.find(p => p.id === provisionData.parent_provision_id);
      if (parent && provisionData.level <= parent.level) {
        throw new Error('Child provision level must be greater than parent');
      }
    }
  }
}
```

### 4. Error Handling

#### Common Validation Errors

```javascript
const STATUTE_ERRORS = {
  SLUG_CONFLICT: 'UNIQUE constraint failed: statute_provisions.statute_id, statute_provisions.slug',
  INVALID_LEVEL: 'Level assignment violates hierarchy rules',
  MISSING_PARENT: 'Parent division/provision not found',
  INVALID_TYPE: 'Invalid division/provision type for this level'
};

function handleStatuteError(error) {
  if (error.message.includes('UNIQUE constraint failed')) {
    return {
      type: 'SLUG_CONFLICT',
      message: 'A provision with this title already exists. Please use a unique title.',
      suggestion: 'Add a distinguishing subtitle or number'
    };
  }
  
  if (error.message.includes('level')) {
    return {
      type: 'INVALID_LEVEL', 
      message: 'Invalid hierarchy level assignment',
      suggestion: 'Ensure child levels are greater than parent levels'
    };
  }
  
  return {
    type: 'UNKNOWN',
    message: error.message
  };
}
```

---

## Best Practices & Guidelines

### 1. Planning Document Structure

Before creating any statute, plan the complete hierarchy:

```
1. Identify document type (Act, Constitution, Regulation, etc.)
2. Map out division structure (Parts → Chapters → Articles)
3. Plan provision patterns (Sections → Subsections → Paragraphs)
4. Assign appropriate levels (1 for top-level, incrementing down)
5. Plan numbering schemes (1, 2, 3 vs I, II, III vs A, B, C)
```

### 2. Level Assignment Strategy

**Conservative Approach** (Recommended):
- Start with level 1 for primary divisions
- Increment by 1 for each hierarchy level
- Leave room for future expansion

**Example:**
```
Statute (Level 0 - implicit)
├── Part I (Level 1)
│   ├── Section 1 (Level 2)  
│   │   ├── Subsection (1) (Level 3)
│   │   └── Subsection (2) (Level 3)
│   └── Section 2 (Level 2)
└── Part II (Level 1)
```

### 3. Sort Order Management

Use incremental integers for sort_order:
- Primary items: 1, 2, 3, 4...
- Allow gaps for future insertions: 10, 20, 30, 40...
- Use decimal notation for insertions: 15, 25, 35...

### 4. Content Management

**Legal Text Guidelines:**
- Preserve exact legal language
- Include proper citations and cross-references
- Use marginal notes for quick reference
- Add interpretation notes for complex provisions

### 5. Performance Considerations

**For Large Documents:**
- Consider pagination for divisions with many provisions
- Implement lazy loading for deep hierarchies
- Use caching for frequently accessed statutes
- Index on commonly searched fields

### 6. Bulk Operations

For complex statutes with many provisions, consider implementing:
- Bulk division creation
- Bulk provision import from structured data
- Template-based statute creation
- Copy from existing statutes

---

## Real-World Examples

### Complete ACJA 2015 PART 1 Workflow

This demonstrates the complete process used to create the ACJA 2015 example:

#### Step 1: Create Statute
```bash
POST /admin/statutes
{
  "title": "ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015",
  "short_title": "ACJA 2015",
  # ... full statute data
}
# Returns: statute_id = 5
```

#### Step 2: Create Part Division
```bash  
POST /admin/statutes/5/divisions
{
  "division_type": "part",
  "division_number": "1",
  "division_title": "PRELIMINARY",
  "level": 1,
  "sort_order": 1
}
# Returns: division_id = 7
```

#### Step 3: Create Section Provisions
```bash
POST /admin/statutes/5/provisions
{
  "provision_type": "section", 
  "provision_number": "1",
  "provision_title": "Purpose",
  "division_id": 7,
  "level": 2,
  "sort_order": 1
}
# Returns: section_id = 5

POST /admin/statutes/5/provisions  
{
  "provision_type": "section",
  "provision_number": "2", 
  "provision_title": "Application",
  "division_id": 7,
  "level": 2,
  "sort_order": 2
}
# Returns: section_id = 8
```

#### Step 4: Create Subsection Provisions
```bash
POST /admin/statutes/5/provisions
{
  "provision_type": "subsection",
  "provision_number": "(1)",
  "provision_text": "The purpose of this Act is to ensure...",
  "parent_provision_id": 5,
  "division_id": 7,
  "level": 3,
  "sort_order": 1
}

POST /admin/statutes/5/provisions
{
  "provision_type": "subsection", 
  "provision_number": "(2)",
  "provision_text": "The courts, law enforcement agencies...",
  "parent_provision_id": 5,
  "division_id": 7, 
  "level": 3,
  "sort_order": 2
}
```

**Final Result:** A properly nested 4-level hierarchy:
- Level 0: Statute (ACJA 2015)
- Level 1: Division (Part 1 - Preliminary)  
- Level 2: Provisions (Section 1, Section 2)
- Level 3: Child Provisions (Subsections under each section)

This flexible approach allows the same API endpoints to create various legal document structures while maintaining consistency and proper relationships.