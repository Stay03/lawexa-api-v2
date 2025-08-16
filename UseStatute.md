# UseStatute.md - Admin Statute Management Guide

This document provides comprehensive guidance for managing statutes in the admin system, including understanding the flexible division/provision structure, hierarchy levels, and administrative operations.

## Table of Contents

1. [Overview](#overview)
2. [Statute Structure](#statute-structure)
3. [Division & Provision System](#division--provision-system)
4. [Creating Statutes](#creating-statutes)
5. [Managing Divisions](#managing-divisions)
6. [Managing Provisions](#managing-provisions)
7. [Viewing Statutes](#viewing-statutes)
8. [Editing Statutes](#editing-statutes)
9. [API Endpoints](#api-endpoints)

## Overview

The statute management system provides a hierarchical structure for organizing legal documents with flexible divisions and provisions. The system can handle complex statute structures where items can be both divisions and provisions - for example, a "Section" can be a division that contains provisions, or it can be a provision itself containing actual legal text.

### Key Concepts

- **Statute**: The main legal document (e.g., "Companies Act 2023")
- **Division**: Organizational units within a statute (Parts, Chapters, Sections, etc.)
- **Provision**: The actual legal content/text within divisions or standalone legal content
- **Level**: Hierarchical depth (1 = top level, 2 = first nested level, etc.)
- **Flexible Structure**: Items like "Section" can serve as both organizational divisions and content provisions

## Statute Structure

### Basic Statute Properties

A statute contains the following core information:

- **Title & Short Title**: Full and abbreviated names
- **Year Enacted**: The year the statute was passed
- **Jurisdiction**: Legal jurisdiction (federal, state, local)
- **Country/State**: Geographic scope
- **Status**: Current state (active, repealed, amended, suspended)
- **Citation Format**: How to reference the statute
- **Sector**: Legal domain (corporate, criminal, civil, etc.)
- **Description**: Summary of the statute's purpose
- **Tags**: Keywords for categorization

### Statute Relationships

Statutes can have hierarchical relationships:

- **Parent/Child**: Subsidiary legislation relationship

## Division & Provision System

The system is designed to handle complex statute structures where the same structural element (like "Section") can function as either a division (container) or a provision (content), depending on the context and legal document structure.

### Division Types

The system supports multiple division types with hierarchical organization:

1. **Book** (Highest level)
2. **Title** 
3. **Part**
4. **Chapter**
5. **Article**
6. **Division**
7. **Section**
8. **Subsection** (Lowest level)
9. **Schedule** (Special type for appendices)

### Hierarchical Levels

- **Level 1**: Top-level divisions (typically Parts or Chapters)
- **Level 2**: First nested level (Sections within Parts)
- **Level 3+**: Further nesting as needed (Subsections, etc.)
- **Maximum**: 10 levels supported

### Division Properties

Each division contains:

- **Division Type**: The structural type (part, chapter, section, etc.)
- **Division Number**: Identifier (e.g., "1", "A", "I")
- **Division Title**: Descriptive name
- **Division Subtitle**: Optional additional description
- **Content**: Main text content
- **Range**: Provision range (e.g., "1-15")
- **Sort Order**: Display sequence
- **Level**: Hierarchical depth
- **Status**: Active, repealed, or amended

### Provision Structure

Provisions are the actual legal content and include:

- **Provision Number**: Legal identifier (e.g., "1", "2(a)", "15.3")
- **Provision Title**: Optional heading
- **Provision Text**: The actual legal content
- **Marginal Note**: Side notes or references
- **Interpretation Note**: Explanatory text
- **Parent Provision**: For nested provisions
- **Division ID**: Parent division (if any)
- **Level**: Hierarchical depth within the division

### Flexible Structure Examples

The system supports various organizational patterns:

#### Pattern 1: Section as Division
```
Part I: Preliminary
├── Section 1: Interpretation (Division)
│   ├── 1(1): In this Act... (Provision)
│   ├── 1(2): Unless the context... (Provision)
│   └── 1(3): For the purposes... (Provision)
```

#### Pattern 2: Section as Provision
```
Part I: Preliminary
├── 1. Short title (Provision)
├── 2. Commencement (Provision) 
└── 3. Interpretation (Provision)
```

#### Pattern 3: Mixed Structure
```
Part II: Main Provisions
├── Chapter 1: General (Division)
│   ├── Section 10: Purpose (Division)
│   │   ├── 10(1): The purpose of this Act... (Provision)
│   │   └── 10(2): This Act applies to... (Provision)
│   └── 11. Application (Provision)
```

## Creating Statutes

### Step 1: Basic Information

1. Access Admin Statute Controller via `/admin/statutes`
2. Provide required fields:
   - **Title**: Full statute name
   - **Short Title**: Abbreviated name (optional)
   - **Year Enacted**: Year of enactment
   - **Jurisdiction**: Legal authority
   - **Status**: Usually "active" for new statutes

### Step 2: Optional Metadata

- **Citation Format**: How to reference this statute
- **Sector**: Legal domain classification
- **Description**: Purpose and scope summary
- **Tags**: Keywords for search and categorization
- **Country/State**: Geographic jurisdiction

### Step 3: File Attachments

- Upload PDF, DOC, or other document formats
- Files are stored in S3 with proper organization
- Multiple files per statute supported

### Example API Call

```json
POST /admin/statutes
{
  "title": "Data Protection Act",
  "short_title": "DPA",
  "year_enacted": 2023,
  "jurisdiction": "federal",
  "country": "Nigeria",
  "sector": "privacy",
  "description": "Comprehensive data protection legislation",
  "tags": ["privacy", "data", "protection", "gdpr"],
  "status": "active"
}
```

## Managing Divisions

### Creating Divisions

Divisions organize content within statutes and can be nested for complex structures.

#### Required Fields

- **Division Type**: Choose from available types
- **Division Number**: Unique identifier within the statute
- **Division Title**: Descriptive name
- **Statute ID**: Parent statute

#### Optional Fields

- **Parent Division ID**: For nested divisions
- **Division Subtitle**: Additional description
- **Content**: Direct text content
- **Range**: Provision range this division covers
- **Level**: Hierarchical depth (auto-calculated if not specified)
- **Sort Order**: Display sequence

### Division Hierarchy Example

```
Data Protection Act 2023
├── Part I: Preliminary (Level 1)
│   ├── Section 1: Short title (Level 2)
│   └── Section 2: Interpretation (Level 2)
├── Part II: Principles (Level 1)
│   ├── Chapter 1: General Principles (Level 2)
│   │   ├── Section 3: Lawfulness (Level 3)
│   │   └── Section 4: Purpose limitation (Level 3)
│   └── Chapter 2: Special Categories (Level 2)
```

### API Examples

```json
POST /admin/statutes/{statuteId}/divisions
{
  "division_type": "part",
  "division_number": "I",
  "division_title": "Preliminary",
  "level": 1,
  "sort_order": 1
}

POST /admin/statutes/{statuteId}/divisions
{
  "division_type": "section",
  "division_number": "1",
  "division_title": "Short title",
  "parent_division_id": 1,
  "level": 2,
  "sort_order": 1
}
```

## Managing Provisions

Provisions contain the actual legal text and can be nested within divisions or other provisions. They can also exist at the same structural level as divisions (e.g., both sections and provisions under a part).

### Creating Provisions

#### Required Fields

- **Provision Number**: Legal identifier
- **Provision Text**: The actual legal content
- **Statute ID**: Parent statute

#### Optional Fields

- **Provision Title**: Heading or caption
- **Parent Provision ID**: For sub-provisions
- **Division ID**: Parent division (if any)
- **Marginal Note**: Side references
- **Interpretation Note**: Explanatory content
- **Level**: Hierarchical depth
- **Sort Order**: Display sequence

### Provision Hierarchy Example

```
Section 3: Data processing principles
├── 3(1): Personal data shall be processed lawfully... (Level 1)
├── 3(2): Personal data shall be collected for specified... (Level 1)
│   ├── 3(2)(a): the purposes are explicit and legitimate (Level 2)
│   ├── 3(2)(b): subsequent processing is compatible (Level 2)
│   └── 3(2)(c): further processing for research purposes (Level 2)
└── 3(3): Personal data shall be adequate... (Level 1)
```

### API Example

```json
POST /admin/statutes/{statuteId}/provisions
{
  "provision_number": "3(2)(a)",
  "provision_text": "the purposes are explicit and legitimate",
  "parent_provision_id": 15,
  "division_id": 8,
  "level": 2,
  "sort_order": 1
}
```

## Viewing Statutes

### Listing Statutes

The admin interface provides comprehensive filtering and search:

```
GET /admin/statutes?search=data&jurisdiction=federal&status=active&per_page=20
```

#### Available Filters

- **Status**: active, repealed, amended, suspended
- **Jurisdiction**: federal, state, local
- **Country/State**: Geographic filters
- **Sector**: Legal domain
- **Year**: Year enacted
- **Search**: Text search across title, description, citation
- **Created By**: Filter by creator

#### Sorting Options

- **created_at**: Creation date (default: desc)
- **title**: Alphabetical
- **year_enacted**: By year
- **updated_at**: Last modified

### Viewing Individual Statutes

Retrieve complete statute with nested structure:

```
GET /admin/statutes/{id}
```

This returns:
- Basic statute information
- All divisions with nested children
- All provisions with hierarchical structure
- Attached files
- Complete breadcrumb navigation

### Navigating Division Hierarchies

#### View Division Children

```
GET /admin/statutes/{statuteId}/divisions/{divisionId}/children
```

Returns:
- Child divisions with pagination
- Breadcrumb trail to current location
- Metadata about hierarchy level
- Navigation links

#### View Division Provisions

```
GET /admin/statutes/{statuteId}/divisions/{divisionId}/provisions
```

Returns:
- All provisions within the division
- Hierarchical provision structure
- Search and filter capabilities

## Editing Statutes

### Updating Basic Information

```json
PUT /admin/statutes/{id}
{
  "title": "Updated Data Protection Act",
  "description": "Updated comprehensive data protection legislation",
  "status": "amended",
  "tags": ["privacy", "data", "protection", "gdpr", "updated"]
}
```

### Updating Divisions

```json
PUT /admin/statutes/{statuteId}/divisions/{divisionId}
{
  "division_title": "Updated Section Title",
  "content": "Updated content text",
  "status": "amended"
}
```

### Updating Provisions

```json
PUT /admin/statutes/{statuteId}/provisions/{provisionId}
{
  "provision_text": "Updated legal text",
  "interpretation_note": "Additional clarification",
  "status": "amended"
}
```

## API Endpoints

### Core Statute Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/statutes` | List all statutes with filters |
| POST | `/admin/statutes` | Create new statute |
| GET | `/admin/statutes/{id}` | Get statute details |
| PUT | `/admin/statutes/{id}` | Update statute |
| DELETE | `/admin/statutes/{id}` | Delete statute |

### Division Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/statutes/{statuteId}/divisions` | List top-level divisions |
| POST | `/admin/statutes/{statuteId}/divisions` | Create division |
| GET | `/admin/statutes/{statuteId}/divisions/{id}` | Get division details |
| PUT | `/admin/statutes/{statuteId}/divisions/{id}` | Update division |
| DELETE | `/admin/statutes/{statuteId}/divisions/{id}` | Delete division |
| GET | `/admin/statutes/{statuteId}/divisions/{id}/children` | Get child divisions |
| GET | `/admin/statutes/{statuteId}/divisions/{id}/provisions` | Get division provisions |

### Provision Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/statutes/{statuteId}/provisions` | List provisions |
| POST | `/admin/statutes/{statuteId}/provisions` | Create provision |
| GET | `/admin/statutes/{statuteId}/provisions/{id}` | Get provision details |
| PUT | `/admin/statutes/{statuteId}/provisions/{id}` | Update provision |
| DELETE | `/admin/statutes/{statuteId}/provisions/{id}` | Delete provision |

## Best Practices

1. **Use Consistent Numbering**: Follow standard legal numbering conventions
2. **Maintain Hierarchy**: Keep division levels logical and consistent
3. **Use Sort Orders**: Ensure proper display sequence with sort_order field
4. **Set Appropriate Levels**: Use level field for proper nesting visualization
5. **Include Metadata**: Add comprehensive descriptions and tags for searchability
6. **Handle Status Changes**: Update status when statutes are amended or repealed
7. **File Management**: Organize uploaded files with clear naming conventions
8. **Flexible Structure**: Utilize the system's ability to handle items as both divisions and provisions as needed
9. **Complex Structures**: Take advantage of the system's capability to handle complex statute organization patterns

This system provides complete flexibility for organizing legal documents while maintaining proper hierarchical structure and comprehensive administrative capabilities. The flexible division/provision system accommodates various legal document structures and organizational patterns.