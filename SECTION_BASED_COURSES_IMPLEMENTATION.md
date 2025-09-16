# Section-Based Courses Dashboard Implementation

## Overview
This document describes the implementation of section-based courses in the Courses Dashboard, where courses are now organized by sections per year level instead of just two tables for active/inactive courses.

## Changes Made

### 1. Database Query Structure
- **Before**: Single queries for active and inactive subjects across all sections
- **After**: Queries grouped by sections, creating separate data arrays for each section

```php
// New structure
$activeSubjectsBySection = [];
$inactiveSubjectsBySection = [];

foreach ($availableSections as $section) {
    // Fetch active subjects for this specific section
    $activeSubjectsBySection[$section['section_name']] = $database->resultset();
    
    // Fetch inactive subjects for this specific section
    $inactiveSubjectsBySection[$section['section_name']] = $database->resultset();
}
```

### 2. HTML Table Structure
- **Before**: Two tables (activeSubjects, inactiveSubjects)
- **After**: Multiple table pairs per section, each with unique IDs

```html
<!-- For each section -->
<div class="section-group" data-section="SectionName">
    <div class="section-header">
        <h2>Section A</h2>
    </div>
    
    <!-- Active Courses Table -->
    <table id="activeSubjects_SectionA">...</table>
    
    <!-- Inactive Courses Table -->
    <table id="inactiveSubjects_SectionA">...</table>
</div>
```

### 3. CSS Styling
Added new CSS classes for section organization:

```css
.section-group {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(248, 250, 252, 0.1) 100%);
    border: 1px solid rgba(229, 231, 235, 0.3);
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(99, 102, 241, 0.2);
}
```

### 4. JavaScript Updates
Updated all JavaScript functions to work with the new section-based structure:

#### Table Sorting
- **Before**: `sortTableAlphabetically('activeSubjects')`
- **After**: `sortAllSectionTables()` - sorts all section tables

#### Status Toggle
- **Before**: Moved rows between two fixed tables
- **After**: Moves rows between tables within the same section

#### Duplicate Checking
- **Before**: Checked against two fixed tables
- **After**: Checks against all section tables

### 5. Table IDs
New naming convention for table IDs:
- Active courses: `activeSubjects_{SectionName}`
- Inactive courses: `inactiveSubjects_{SectionName}`

Examples:
- `activeSubjects_A`
- `inactiveSubjects_A`
- `activeSubjects_B`
- `inactiveSubjects_B`

## Benefits

### 1. Better Organization
- Courses are now clearly separated by sections
- Easier to manage courses for specific student groups
- Better visual hierarchy

### 2. Improved User Experience
- Users can quickly identify which section a course belongs to
- Clearer separation between active and inactive courses per section
- Better navigation for administrators

### 3. Scalability
- Easy to add new sections
- Maintains performance with large numbers of courses
- Flexible structure for future enhancements

## Database Requirements

### Required Tables
1. **sections** table with columns:
   - `section_id` (Primary Key)
   - `section_name`
   - `year_level`
   - `status`

2. **subjects** table with columns:
   - `subject_id` (Primary Key)
   - `subject_code`
   - `subject_name`
   - `units`
   - `year_level`
   - `semester`
   - `section_id` (Foreign Key to sections.section_id)
   - `status`
   - `academic_year`

### Required Migration
Run the `add_section_to_subjects.sql` migration to add the `section_id` column to the subjects table.

## Testing

A test file `test_section_based_courses.php` has been created to verify:
1. Sections table structure and data
2. Subjects table structure (section_id column)
3. Course grouping by sections
4. Active/inactive course separation per section

## Usage

### 1. Access the Dashboard
Navigate to `dashboards/admin/Courses-dashboard.php`

### 2. Select Year Level and Semester
Use the dropdown selectors to choose:
- Year Level (1st Year, 2nd Year, 3rd Year, 4th Year)
- Semester (First, Second, Mid Year for 3rd Year only)

### 3. View Section-Based Courses
Courses will be displayed organized by sections, with each section showing:
- Section header with section name
- Active courses table
- Inactive courses table

### 4. Manage Courses
- Toggle course status (active/inactive)
- Edit course details
- Delete courses
- Add new courses

## Future Enhancements

### 1. Section Management
- Add/remove sections
- Edit section details
- Section-specific settings

### 2. Bulk Operations
- Bulk status changes per section
- Section-wide course operations
- Import/export by section

### 3. Advanced Filtering
- Filter by multiple sections
- Search within specific sections
- Section-based reporting

## Troubleshooting

### Common Issues

1. **No sections displayed**
   - Check if sections table exists and has data
   - Verify sections have 'available' status
   - Check database connection

2. **Tables not loading**
   - Verify section_id column exists in subjects table
   - Check foreign key relationships
   - Review database permissions

3. **JavaScript errors**
   - Check browser console for errors
   - Verify table IDs match expected format
   - Ensure all required functions are loaded

### Debug Steps

1. Run `test_section_based_courses.php` to verify database structure
2. Check browser console for JavaScript errors
3. Verify database queries are working
4. Check table IDs in HTML source

## Conclusion

The section-based courses dashboard provides a much more organized and user-friendly way to manage courses. It separates courses by sections per year level, making it easier for administrators to manage course offerings for different student groups while maintaining all existing functionality.
