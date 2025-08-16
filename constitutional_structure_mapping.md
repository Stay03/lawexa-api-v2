# Nigerian Constitution 1999 - Hierarchical Structure Mapping

## Document Overview
- **Title**: Constitution of the Federal Republic of Nigeria 1999 CAP. C23 L.F.N. 2004
- **Total Pages**: 245 pages
- **Structure Type**: Constitutional Document Pattern
- **Hierarchical Levels**: 4-5 levels deep

## Identified Hierarchical Structure

### Level 1: Chapters (Divisions)
**Type**: Structural Divisions
**Level**: 1
**Purpose**: Major thematic organization of constitutional content

#### Identified Chapters:
1. **Chapter I** - General Provisions
   - Part I: Federal Republic of Nigeria
   - Part II: Powers of the Federal Republic of Nigeria

2. **Chapter II** - Fundamental Objectives and Directive Principles of State Policy

3. **Chapter III** - Citizenship

4. **Chapter IV** - Fundamental Rights

5. **Chapter V** - The Legislature
   - Part I: National Assembly
   - Part II: House of Assembly of a State

6. **Chapter VI** - The Executive
   - Part I: Federal Executive
   - Part II: State Executive
   - Part III: Supplemental

7. **Chapter VII** - The Judicature
   - Part I: Federal Courts
   - Part II: State Courts
   - Part III: Election Tribunals
   - Part IV: Supplemental

8. **Chapter VIII** - Federal Capital Territory, Abuja and General Supplementary Provisions
   - Part I: Federal Capital Territory, Abuja
   - Part II: Miscellaneous Provisions
   - Part III: Transitional Provisions and Savings
   - Part IV: Interpretation, Citation and Commencement

### Level 2: Parts (Divisions)
**Type**: Structural Divisions  
**Level**: 2
**Purpose**: Sub-organization within chapters
**Pattern**: Some chapters have parts, others don't

#### Examples:
- Chapter I → Part I (Federal Republic of Nigeria), Part II (Powers of Federal Republic)
- Chapter V → Part I (National Assembly), Part II (House of Assembly of a State)
- Chapter VI → Part I (Federal Executive), Part II (State Executive), Part III (Supplemental)

### Level 3: Sections (Provisions)
**Type**: Content Provisions
**Level**: 3
**Purpose**: Primary legal provisions with substantive text
**Numbering**: Sequential numbers (1, 2, 3, etc.)

#### Examples:
- **Section 1**: Supremacy of constitution
- **Section 2**: The Federal Republic of Nigeria
- **Section 3**: States of the Federation and the Federal Capital Territory, Abuja
- **Section 33**: Right to life
- **Section 34**: Right to dignity of human persons

### Level 4: Subsections (Provisions)
**Type**: Content Provisions
**Level**: 4
**Purpose**: Detailed breakdown of sections
**Numbering**: (1), (2), (3), etc.

#### Examples:
- Section 1(1): "This Constitution is supreme and its provisions shall have binding force..."
- Section 1(2): "The Federal Republic of Nigeria shall not be governed..."
- Section 1(3): "If any other law is inconsistent with the provisions of this Constitution..."

### Level 5: Paragraphs/Clauses (Provisions)
**Type**: Content Provisions
**Level**: 5
**Purpose**: Further breakdown of subsections
**Numbering**: (a), (b), (c), etc.

#### Examples:
- Section 4(4)(a): "any matter in the Concurrent Legislative List..."
- Section 4(4)(b): "any other matter with respect to which it is empowered..."

## Mapping to API Structure

### Divisions (Structural Elements)
```json
{
  "level": 1,
  "division_type": "chapter",
  "division_number": "I",
  "division_title": "General Provisions",
  "range": null,
  "content": null
}
```

```json
{
  "level": 2,
  "division_type": "part",
  "division_number": "I",
  "division_title": "Federal Republic of Nigeria",
  "range": null,
  "parent_division_id": [chapter_id]
}
```

### Provisions (Content Elements)
```json
{
  "level": 3,
  "provision_type": "section",
  "provision_number": "1",
  "provision_title": "Supremacy of constitution",
  "provision_text": "This Constitution is supreme and its provisions shall have binding force on the authorities and persons throughout the Federal Republic of Nigeria.",
  "range": null,
  "division_id": [part_id]
}
```

```json
{
  "level": 4,
  "provision_type": "subsection",
  "provision_number": "(1)",
  "provision_title": null,
  "provision_text": "This Constitution is supreme and its provisions shall have binding force on the authorities and persons throughout the Federal Republic of Nigeria.",
  "range": null,
  "parent_provision_id": [section_id]
}
```

## Implementation Strategy

### Phase 1: Create Chapters (Level 1 Divisions)
- Chapter I through Chapter VIII
- Each as a division with level 1
- No legal text, purely structural

### Phase 2: Create Parts (Level 2 Divisions)
- Create parts within relevant chapters
- Not all chapters have parts
- Level 2 divisions, child of chapters

### Phase 3: Create Sections (Level 3 Provisions)
- Primary legal content
- Attach to either chapters (if no parts) or parts
- Include full legal text and titles

### Phase 4: Create Subsections (Level 4 Provisions)
- Detailed breakdowns of sections
- Child provisions of sections
- Include specific legal text

### Phase 5: Create Clauses (Level 5 Provisions)
- Further breakdowns where applicable
- Child provisions of subsections
- Include specific legal text

## Special Considerations

### Schedules
The Constitution contains multiple schedules that should be treated as appendices:
- First Schedule (States of the Federation)
- Second Schedule (Legislative Lists)
- Third Schedule (Federal Executive Bodies)
- Fourth Schedule (Functions of Local Councils)
- Fifth Schedule (Code of Conduct)
- Sixth Schedule (Election Tribunals)
- Seventh Schedule (Oaths)

**Recommendation**: Create these as separate chapters or as special provisions under Chapter VIII.

### Cross-References
Many sections reference other sections. Ensure proper linking during data entry.

### Amendments
This is the 1999 Constitution with LFN 2004 amendments. Track any amendment history if relevant.

## API Endpoints to Use

1. **POST** `/admin/statutes` - Create main Constitution
2. **POST** `/admin/statutes/{id}/divisions` - Create chapters
3. **POST** `/admin/statutes/{id}/divisions/{divisionId}/children` - Create parts
4. **POST** `/admin/statutes/{id}/divisions/{divisionId}/provisions` - Create sections
5. **POST** `/admin/statutes/{id}/provisions/{provisionId}/children` - Create subsections and clauses

## Data Processing Order

1. Constitution statute (main document)
2. All chapters (8 chapters)
3. All parts within chapters
4. All sections within parts/chapters
5. All subsections within sections
6. All clauses within subsections
7. All schedules

This structure follows the Constitutional Document Pattern described in the API documentation and provides a clear hierarchical navigation system for the Nigerian Constitution.