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

### Understanding the Hierarchy

Legal documents follow a hierarchical structure that can be navigated systematically:

```
Statute (Root Level)
├── Division Level 1 (Chapters, Parts, Titles)
│   ├── Division Level 2 (Articles, Sections as divisions)
│   │   ├── Provision Level 1 (Sections, Paragraphs)
│   │   │   ├── Provision Level 2 (Subsections, Subparagraphs)
│   │   │   │   └── Provision Level 3 (Clauses, Items)
│   │   │   └── Provision Level 2 (More subsections)
│   │   └── Provision Level 1 (More sections)
│   └── Division Level 2 (More articles)
└── Division Level 1 (More chapters)
```

### Navigation Endpoints Overview

| Level | Endpoint Pattern | Purpose | Returns |
|-------|-----------------|---------|---------|
| **Division List** | `GET /statutes/{id}/divisions` | Top-level divisions | Chapters, Parts, Titles |
| **Division Children** | `GET /statutes/{id}/divisions/{divisionId}/children` | Child divisions | Nested divisions within parent |
| **Division Provisions** | `GET /statutes/{id}/divisions/{divisionId}/provisions` ✨ | Provisions in division | Sections, articles in division |
| **Provision Children** | `GET /statutes/{id}/provisions/{provisionId}/children` ✨ | Child provisions | Subsections, subclauses, etc. |

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
        "level": 2,
        "parent_division_id": 27
      },
      {
        "id": 30,
        "division_type": "part",
        "division_number": "II",
        "division_title": "Second Part",
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

### Example 1: Nigerian Federal Act Structure

```
1. GET /admin/statutes/5/divisions
   → Parts: Part 1 (Preliminary), Part 2 (Procedures), etc.

2. GET /admin/statutes/5/divisions/7/provisions
   → Sections: Section 1 (Purpose), Section 2 (Application), etc.

3. GET /admin/statutes/5/provisions/5/children
   → Subsections: (1), (2), (3), etc.

4. GET /admin/statutes/5/provisions/6/children
   → Sub-subsections: (a), (b), (c), etc.
```

### Example 2: Constitutional Document Structure

```
1. GET /admin/statutes/12/divisions
   → Chapters: Chapter 1 (Fundamental Rights), Chapter 2 (Directive Principles), etc.

2. GET /admin/statutes/12/divisions/15/children
   → Articles: Article 1, Article 2, etc.

3. GET /admin/statutes/12/divisions/16/provisions
   → Sections: Section 1, Section 2, etc.

4. GET /admin/statutes/12/provisions/45/children
   → Clauses: (a), (b), (c), etc.
```

### Example 3: Complex Regulation Structure

```
1. GET /admin/statutes/20/divisions
   → Books: Book I (General), Book II (Specific), etc.

2. GET /admin/statutes/20/divisions/22/children
   → Titles: Title 1, Title 2, etc.

3. GET /admin/statutes/20/divisions/25/children
   → Chapters: Chapter A, Chapter B, etc.

4. GET /admin/statutes/20/divisions/28/provisions
   → Paragraphs: Paragraph 1.1, Paragraph 1.2, etc.

5. GET /admin/statutes/20/provisions/67/children
   → Subparagraphs: (i), (ii), (iii), etc.
```

---

## Navigation Decision Tree

Use this decision tree to determine which endpoint to use:

```
Do you want to see...

├── Top-level structure of a statute?
│   → GET /admin/statutes/{id}/divisions
│
├── What's inside a specific division?
│   ├── More divisions (parts, articles, etc.)?
│   │   → GET /admin/statutes/{id}/divisions/{divisionId}/children
│   │
│   └── Actual legal content (sections, paragraphs)?
│       → GET /admin/statutes/{id}/divisions/{divisionId}/provisions
│
└── What's inside a specific provision?
    └── Child provisions (subsections, clauses, etc.)?
        → GET /admin/statutes/{id}/provisions/{provisionId}/children
```

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