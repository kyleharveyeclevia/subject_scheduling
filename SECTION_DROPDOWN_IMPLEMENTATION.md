# Section Dropdown Implementation for Course Dashboard

## Overview
This implementation adds a section dropdown to the course dashboard that allows administrators to assign courses to specific sections. The section dropdown is populated based on active sections from the sections dashboard.

## Changes Made

### 1. Database Migration
- **File**: `add_section_to_subjects.sql`
- **Purpose**: Adds `section_id` column to the `subjects` table
- **Changes**:
  - Adds `section_id INT` column after `year_level`
  - Creates foreign key constraint to `sections` table
  - Adds index for performance
  - Updates existing subjects with default sections

### 2. API Endpoint
- **File**: `api/get-active-sections.php`
- **Purpose**: Fetches active sections for a specific year level
- **Usage**: `GET /api/get-active-sections.php?year_level=1st Year`
- **Response**: JSON with sections data

### 3. Course Dashboard Updates
- **File**: `dashboards/admin/Courses-dashboard.php`
- **Changes**:
  - Added section dropdown to Add Course modal
  - Added section dropdown to Edit Course modal
  - Updated table to display section information
  - Added Section column to both active and inactive course tables
  - Updated PHP backend to handle section_id in add/edit operations
  - Added JavaScript to populate section dropdowns dynamically

### 4. Form Updates
- **Add Course Form**: Added section dropdown after semester field
- **Edit Course Form**: Added section dropdown after semester field
- **Validation**: Section is now required for both add and edit operations

### 5. Table Display Updates
- **New Column**: Added "Section" column between "Semester" and "Status"
- **Data Source**: Section information is fetched via JOIN with sections table
- **Fallback**: Shows "N/A" if no section is assigned

## Implementation Details

### JavaScript Functions
- `initializeSectionDropdowns()`: Sets up event listeners for year level changes
- `loadSectionsForYearLevel(yearLevel, dropdownId)`: Fetches and populates sections for a specific year level
- Dynamic loading: Sections are loaded when year level changes or modals open

### Database Queries
- **Active Subjects**: `SELECT s.*, sec.section_name FROM subjects s LEFT JOIN sections sec ON s.section_id = sec.section_id`
- **Inactive Subjects**: Same structure as active subjects
- **Section Loading**: `SELECT section_id, section_name, year_level FROM sections WHERE status = 'available' AND year_level = ?`

### Form Processing
- **Add Course**: Includes section_id in INSERT query
- **Edit Course**: Includes section_id in UPDATE query
- **Validation**: All fields including section are required

## Usage Instructions

### 1. Run Database Migration
```sql
-- Execute the migration script
source add_section_to_subjects.sql;
```

### 2. Test the Implementation
- Navigate to the course dashboard
- Click "Add New Course" button
- Select a year level - section dropdown will populate automatically
- Select a section from the dropdown
- Fill other required fields and save

### 3. Edit Existing Courses
- Click edit button on any course
- Section dropdown will be populated and show current section
- Modify section if needed and save changes

## Benefits
1. **Better Organization**: Courses are now linked to specific sections
2. **Improved Management**: Administrators can assign courses to appropriate sections
3. **Data Integrity**: Foreign key constraints ensure valid section references
4. **Dynamic Loading**: Sections are loaded based on year level selection
5. **User Experience**: Intuitive dropdown interface for section selection

## Technical Notes
- Uses AJAX to fetch sections dynamically
- Maintains existing validation and error handling
- Backward compatible with existing course data
- Responsive design maintained with updated table widths
- Error handling for missing sections (shows "N/A")

## Testing
- Test script: `test_section_migration.php`
- Verifies database structure and API functionality
- Run before and after migration to ensure success

## Future Enhancements
- Section-based course filtering
- Bulk section assignment
- Section capacity management
- Integration with scheduling system
