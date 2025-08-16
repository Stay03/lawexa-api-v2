# Task: Complete Statute Structure Analysis and Data Entry

## 1. **Analyze the Text File Structure:**
   - Review `Docs/admin/statute-admin-new.md` for reference structure guidelines
   - Examine the statute text files in statutefile > chapters (`Chapter_I.txt`, `Chapter_II.txt`, etc.)
   - Get the drill-down structure and hierarchical levels of each item within each chapter from structure.md

## 2. **Data Entry Requirements:**
   - Process chapters sequentially (Chapter_I.txt, Chapter_II.txt, etc.)
   - Add each statute element systematically, working through each text file completely
   - Process elements in hierarchical order down to the subsection level (add provision as subsection)
   - Use the live server and configuration details from `.env.text`
   - Maintain proper chapter sequencing and cross-references

## 3. **Provision Text Rules:**
   - **DO NOT** add `provision_text` to sections that contain subsections
   - **ONLY** add `provision_text` to:
     - Sections that have no subsections but contain legal text
     - Individual subsections (add the text directly to each subsection)
   - Extract text accurately from the source files, preserving legal formatting

## 4. **Title Rules:**
   - **Subsections should NOT have titles** - leave title field empty for subsections
   - Use chapter titles and section titles as they appear in the text files
   - Maintain consistency with the original statute naming conventions

## 5. **Range Field Updates:**
   - After adding all divisions/provisions within an item, **edit the parent item**
   - Add the appropriate range to the `range` field (e.g., "Sections 1-5" or "Parts I-III")
   - Update chapter-level ranges to reflect all contained sections
   - Ensure ranges accurately represent the content structure from the text files

## 6. **Chapter Processing Workflow:**
   - Start with Chapter_I.txt and work sequentially through all chapter files
   - For each chapter:
     1. Get the hierarchical structure
     2. Create the chapter entry with proper metadata
     3. Add all sections, subsections, and provisions
     4. Apply provision text rules
     5. Update parent ranges
   - Verify cross-references between chapters remain intact

**Goal:** Complete the entire statute entry from all chapter text files with proper hierarchical structure, accurate text placement, sequential chapter processing, and updated range fields according to the specified rules.