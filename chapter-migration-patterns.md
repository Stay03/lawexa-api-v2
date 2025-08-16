# Chapter Migration Patterns Documentation

This document outlines the analysis of 433 chapters from the old database and their migration strategy to the new statute divisions system.

## Overview

The old database contains 433 chapters in the `statute_chapters` table with various numbering patterns. We've categorized these into patterns that can be migrated immediately (simple single-level divisions) and those that require future hierarchical migration (complex multi-level patterns).

## Migration Phase 1: Simple Single-Level Divisions (344 chapters)

These patterns will be migrated in the first phase as they represent simple, single-level divisions:

### 1. Simple Parts (219 chapters)
**Pattern**: `Part X` where X is a number or Roman numeral
**Examples**: Part 1, Part I, Part II, PART III, Part A, etc.
**Maps to**: `division_type='part'`

**Key Statutes using this pattern**:
- ADMINISTRATION OF CRIMINAL JUSTICE ACT, 2015 (PART 1, PART 2, PART 3)
- ADVANCE FEE FRAUD AND OTHER FRAUD RELATED OFFENCES ACT (PART I, PART II, PART III, PART IV)
- MONEY LAUNDERING (PROHIBITION) ACT (Part 1, Part 2, etc.)
- ADVERTISING REGULATORY COUNCIL OF NIGERIA ACT (Part I through Part XII)

### 2. Simple Chapters (79 chapters)
**Pattern**: `Chapter X` where X is a number or Roman numeral
**Examples**: Chapter 1, Chapter I, Chapter II, Chapter 0, etc.
**Maps to**: `division_type='chapter'`

**Key Statutes using this pattern**:
- CONSTITUTION OF THE FEDERAL REPUBLIC OF NIGERIA (Chapter I, Chapter II, etc.)
- Various statutes with standalone chapter divisions

### 3. Simple Orders (17 chapters) ⭐ NEW DIVISION TYPE
**Pattern**: `Order X` where X is a number
**Examples**: Order 1, Order 2, Order 3, ..., Order 17
**Maps to**: `division_type='order'` (requires new enum value)

**Key Statutes using this pattern**:
- VALUE ADDED TAX TRIBUNALS RULES, 2003 (Order 1 through Order 17)

### 4. Simple Schedules (29 chapters)
**Pattern**: `Schedule` with optional number/Roman numeral
**Examples**: Schedule, Schedule 1, Schedule I, First Schedule, Second Schedule
**Maps to**: `division_type='schedule'`

**Key Statutes using this pattern**:
- Various statutes with schedule divisions

## Migration Phase 2+: Complex Multi-Level Patterns (89 chapters)

These patterns will NOT be migrated in the first phase as they represent hierarchical, multi-level structures that require special handling:

### 1. Complex Part+Chapter (58 chapters)
**Pattern**: `Part X Chapter Y` - Hierarchical structure
**Examples**: Part 1 Chapter 1, Part 2 Chapter 7, Part 1 Chapter 2, etc.
**Future Strategy**: Create parent Part division, then child Chapter divisions

**Key Statutes using this pattern**:
- CRIMINAL CODE ACT (Part 1 Chapter 1 through Part 8 Chapter 55)
  - Contains the majority of these complex patterns
  - Represents a clear hierarchical structure: Parts contain Chapters

### 2. Complex Chapter+Section (4 chapters)
**Pattern**: `Chapter X Section Y` or `Chapter Sections X-Y`
**Examples**: Chapter Sections 1-25, Chapter Sections 1-6, etc.
**Future Strategy**: Create Chapter division with embedded section ranges

### 3. Just Numbers (12 chapters)
**Pattern**: Bare numbers without prefix
**Examples**: 0, 1, 2, 3, etc.
**Future Strategy**: Needs context analysis to determine appropriate division type

### 4. Schedule Ranges (8 chapters)
**Pattern**: Schedule with ranges
**Examples**: Schedule 1-3, Schedule 1-4
**Future Strategy**: Either create multiple schedule divisions or handle as single division with range

### 5. Other Complex (7 chapters)
**Pattern**: Various special patterns
**Examples**: "-", "X", "O", "Forms 1-9", etc.
**Future Strategy**: Case-by-case analysis needed

## Technical Implementation

### Database Changes Required
1. **New Migration**: Add 'order' to division_type enum in statute_divisions table
2. **Cross-Database Compatibility**: Support both SQLite (development) and MySQL (production)

### Transfer Script Logic
```php
// WILL migrate (344 chapters):
if (preg_match('/^Part\s+[0-9IVX]+$/i', $number)) {
    return ['type' => 'part', 'number' => $matches[1]];
} elseif (preg_match('/^Chapter\s+[0-9IVX]+$/i', $number)) {
    return ['type' => 'chapter', 'number' => $matches[1]];
} elseif (preg_match('/^Order\s+[0-9]+$/i', $number)) {
    return ['type' => 'order', 'number' => $matches[1]];  // NEW!
} elseif (preg_match('/^Schedule\s*[0-9IVX]*$/i', $number)) {
    return ['type' => 'schedule', 'number' => $matches[1]];
}

// WILL NOT migrate (89 chapters):
// - Part X Chapter Y patterns
// - Chapter X Section Y patterns  
// - Bare numbers (0, 1, 2, etc.)
// - Complex ranges and special patterns
```

### Mapping Strategy
- **Old statute_chapters.statute_id** → Find matching **statutes.title** → **statute_divisions.statute_id**
- **Old statute_chapters.number** → Parse pattern → **statute_divisions.division_type + division_number**
- **Old statute_chapters.title** → **statute_divisions.division_title**
- **Old statute_chapters.range** → **statute_divisions.range**

## Future Migration Phases

### Phase 2: Hierarchical Part+Chapter (58 chapters)
Focus on CRIMINAL CODE ACT's hierarchical structure:
1. Create Part divisions as top-level (Part 1, Part 2, etc.)
2. Create Chapter divisions as children of Parts (Chapter 1 under Part 1, etc.)
3. Use `parent_division_id` to establish hierarchy

### Phase 3: Complex Patterns (31 chapters)
Handle remaining edge cases:
- Section ranges within chapters
- Schedule ranges
- Bare numbers with context analysis
- Special patterns requiring custom logic

## Statistics Summary

| Pattern Type | Count | Status | Division Type |
|--------------|-------|--------|---------------|
| Simple Parts | 219 | ✅ Phase 1 | `part` |
| Simple Chapters | 79 | ✅ Phase 1 | `chapter` |
| Simple Orders | 17 | ✅ Phase 1 | `order` (new) |
| Simple Schedules | 29 | ✅ Phase 1 | `schedule` |
| **Phase 1 Total** | **344** | **✅ Ready** | |
| Complex Part+Chapter | 58 | 🔄 Phase 2 | `part` + `chapter` hierarchy |
| Complex Chapter+Section | 4 | 🔄 Phase 3 | `chapter` with ranges |
| Just Numbers | 12 | 🔄 Phase 3 | TBD by context |
| Schedule Ranges | 8 | 🔄 Phase 3 | `schedule` with ranges |
| Other Complex | 7 | 🔄 Phase 3 | Case-by-case |
| **Future Phases Total** | **89** | **🔄 Pending** | |
| **Grand Total** | **433** | | |

## Conclusion

The first migration phase will successfully transfer 344 well-defined, single-level divisions (79.4% of all chapters) while establishing a solid foundation for future hierarchical migrations. The remaining 89 chapters (20.6%) represent complex patterns that require careful hierarchical planning and will be addressed in subsequent phases.