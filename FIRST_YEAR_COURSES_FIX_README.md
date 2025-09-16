# First Year Courses Not Showing - Fix Guide

## Problem Description

The first year data in the course dashboard is not displaying. This is a common issue caused by database structure and data problems in the subject scheduling system.

## Root Cause Analysis

The issue occurs because the course dashboard follows this logic:

1. **Fetches sections first**: Looks for available sections for the selected year level
2. **Then fetches subjects**: For each section, fetches subjects that match the year level, semester, and section
3. **Displays grouped by sections**: Shows subjects organized by sections

**The problem**: If any of these are missing, the first year data won't show:
- ❌ Missing `sections` table
- ❌ Missing sections for "1st Year"
- ❌ Missing `section_id` column in `subjects` table
- ❌ Missing `semester` column in `subjects` table
- ❌ Missing `academic_year` column in `subjects` table
- ❌ Subjects not properly linked to sections

## Quick Fix

### Option 1: Run the Fix Script (Recommended)

1. **Navigate to your project root directory**
2. **Open in browser**: `fix_first_year_courses.php`
3. **The script will automatically**:
   - Check and create the `sections` table
   - Add missing columns to `subjects` table
   - Create default sections for all year levels
   - Add sample course data
   - Link subjects to sections
   - Test the fix

### Option 2: Manual Database Fix

If the script fails, run these SQL commands manually:

```sql
-- 1. Create sections table
CREATE TABLE IF NOT EXISTS sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_year (section_name, year_level)
);

-- 2. Add missing columns to subjects table
ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS section_id INT NULL AFTER year_level,
ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'first' AFTER year_level,
ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025' AFTER semester;

-- 3. Insert default sections
INSERT INTO sections (section_name, year_level, status) VALUES
('A', '1st Year', 'available'),
('B', '1st Year', 'available'),
('C', '1st Year', 'available'),
('A', '2nd Year', 'available'),
('B', '2nd Year', 'available'),
('A', '3rd Year', 'available'),
('B', '3rd Year', 'available'),
('A', '4th Year', 'available'),
('B', '4th Year', 'available')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- 4. Link existing subjects to sections
UPDATE subjects s 
JOIN sections sec ON s.year_level = sec.year_level AND sec.status = 'available'
SET s.section_id = sec.section_id 
WHERE s.section_id IS NULL;
```

## Testing the Fix

### Run the Test Script

1. **Open in browser**: `test_first_year_fix.php`
2. **Check all tests pass**:
   - ✅ Sections table exists and has 1st Year sections
   - ✅ Subjects table has all required columns
   - ✅ First year subjects are found
   - ✅ Dashboard query logic works

### Manual Verification

1. **Check sections table**:
   ```sql
   SELECT * FROM sections WHERE year_level = '1st Year' AND status = 'available';
   ```

2. **Check subjects table structure**:
   ```sql
   DESCRIBE subjects;
   ```

3. **Check first year subjects**:
   ```sql
   SELECT s.*, sec.section_name 
   FROM subjects s 
   LEFT JOIN sections sec ON s.section_id = sec.section_id 
   WHERE s.year_level = '1st Year' AND s.semester = 'first' AND s.status = 'available';
   ```

## Expected Results After Fix

### Database Structure
- ✅ `sections` table with sections for all year levels
- ✅ `subjects` table with `section_id`, `semester`, and `academic_year` columns
- ✅ Foreign key relationship between subjects and sections

### Sample Data
- ✅ Sections: A, B, C for 1st Year
- ✅ Sample courses: MATH101, ENG101, PHYS101, CHEM101 for 1st Year
- ✅ All subjects properly linked to sections

### Dashboard Behavior
- ✅ First year courses display when "1st Year" is selected
- ✅ Courses organized by sections (A, B, C)
- ✅ Active and inactive courses shown separately per section
- ✅ Year level and semester filtering works correctly

## Troubleshooting

### If the fix script fails:

1. **Check database connection**:
   - Verify `config/database.php` settings
   - Ensure database server is running
   - Check user permissions

2. **Check table permissions**:
   - User must have CREATE, ALTER, INSERT, UPDATE permissions
   - Tables must exist and be accessible

3. **Check for existing data conflicts**:
   - Remove conflicting data manually
   - Check for duplicate entries

### If courses still don't show after fix:

1. **Check browser console** for JavaScript errors
2. **Verify URL parameters** are correct:
   - `?year_level=1st%20Year&semester=first`
3. **Check if sections have 'available' status**
4. **Verify subjects have correct status ('available')**

### Common Issues:

1. **"No sections found"**:
   - Run the fix script again
   - Check sections table manually

2. **"Missing columns"**:
   - Verify ALTER TABLE commands ran successfully
   - Check table structure with DESCRIBE

3. **"No subjects found"**:
   - Check if subjects exist for 1st Year
   - Verify semester and status values

## Files Created/Modified

### New Files:
- `fix_first_year_courses.php` - Main fix script
- `test_first_year_fix.php` - Test script to verify fix
- `FIRST_YEAR_COURSES_FIX_README.md` - This documentation

### Modified Files:
- Database structure (sections and subjects tables)
- Sample data added

## Next Steps

After running the fix:

1. **Test the course dashboard** - Navigate to `dashboards/admin/Courses-dashboard.php`
2. **Verify first year data** - Select "1st Year" and "First Semester"
3. **Add more courses** - Use the "Add New Course" button
4. **Manage sections** - Use `dashboards/admin/sections-dashboard.php` to customize sections

## Support

If you continue to experience issues:

1. **Run the test script** to identify specific problems
2. **Check the error logs** for detailed error messages
3. **Verify database structure** matches expected schema
4. **Test with minimal data** to isolate the issue

## Technical Details

### Database Schema Requirements:
- `sections` table with `section_id`, `section_name`, `year_level`, `status`
- `subjects` table with `subject_id`, `subject_code`, `subject_name`, `units`, `year_level`, `section_id`, `semester`, `academic_year`, `status`
- Foreign key relationship: `subjects.section_id` → `sections.section_id`

### Dashboard Query Flow:
1. Get available sections for selected year level
2. For each section, fetch active/inactive subjects
3. Group subjects by section
4. Display in section-based tables

This fix ensures all components work together to display first year courses correctly.
