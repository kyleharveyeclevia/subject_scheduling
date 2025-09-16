<?php
// Run subjects table migration
require_once 'config/database.php';

try {
    $database = new Database();
    
    echo "Starting subjects table migration...\n";
    
    // Add missing columns to subjects table
    $database->query("ALTER TABLE subjects 
                     ADD COLUMN IF NOT EXISTS section VARCHAR(10) DEFAULT 'A',
                     ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'first',
                     ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025'");
    $database->execute();
    echo "✓ Added missing columns to subjects table\n";
    
    // Update existing subjects with sample data
    // 1st Year subjects
    $database->query("UPDATE subjects SET 
                     section = 'A',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '1st Year' AND subject_code IN ('MATH101', 'ENG101')");
    $database->execute();
    
    $database->query("UPDATE subjects SET 
                     section = 'B',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '1st Year' AND subject_code IN ('PHYS101', 'CHEM101')");
    $database->execute();
    
    $database->query("UPDATE subjects SET 
                     section = 'C',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '1st Year' AND subject_code NOT IN ('MATH101', 'ENG101', 'PHYS101', 'CHEM101')");
    $database->execute();
    
    // 2nd Year subjects
    $database->query("UPDATE subjects SET 
                     section = 'A',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '2nd Year' AND subject_code IN ('MATH201', 'ENG201')");
    $database->execute();
    
    $database->query("UPDATE subjects SET 
                     section = 'B',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '2nd Year' AND subject_code IN ('PHYS201', 'CHEM201')");
    $database->execute();
    
    // 3rd Year subjects
    $database->query("UPDATE subjects SET 
                     section = 'A',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '3rd Year' AND subject_code = 'MATH301'");
    $database->execute();
    
    $database->query("UPDATE subjects SET 
                     section = 'B',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '3rd Year' AND subject_code = 'RES301'");
    $database->execute();
    
    // 4th Year subjects
    $database->query("UPDATE subjects SET 
                     section = 'A',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '4th Year' AND subject_code = 'THESIS401'");
    $database->execute();
    
    $database->query("UPDATE subjects SET 
                     section = 'B',
                     semester = 'first',
                     academic_year = '2024-2025'
                     WHERE year_level = '4th Year' AND subject_code = 'CAP401'");
    $database->execute();
    
    echo "✓ Updated existing subjects with section data\n";
    
    // Add some second semester courses
    $database->query("INSERT INTO subjects (subject_name, subject_code, units, year_level, section, semester, academic_year, status) VALUES
                     ('Mathematics I - Second Sem', 'MATH101S', 3, '1st Year', 'A', 'second', '2024-2025', 'available'),
                     ('English I - Second Sem', 'ENG101S', 3, '1st Year', 'B', 'second', '2024-2025', 'available'),
                     ('Mathematics II - Second Sem', 'MATH201S', 3, '2nd Year', 'A', 'second', '2024-2025', 'available'),
                     ('English II - Second Sem', 'ENG201S', 3, '2nd Year', 'B', 'second', '2024-2025', 'available'),
                     ('Advanced Mathematics - Second Sem', 'MATH301S', 3, '3rd Year', 'A', 'second', '2024-2025', 'available'),
                     ('Research Methods - Second Sem', 'RES301S', 3, '3rd Year', 'B', 'second', '2024-2025', 'available')");
    $database->execute();
    
    // Add some summer courses for 3rd Year
    $database->query("INSERT INTO subjects (subject_name, subject_code, units, year_level, section, semester, academic_year, status) VALUES
                     ('Summer Mathematics', 'MATH301SU', 3, '3rd Year', 'A', 'summer', '2024-2025', 'available'),
                     ('Summer Research', 'RES301SU', 3, '3rd Year', 'B', 'summer', '2024-2025', 'available')");
    $database->execute();
    
    echo "✓ Added additional sample courses\n";
    
    // Verify the changes
    $database->query("SELECT COUNT(*) as total_subjects FROM subjects");
    $database->execute();
    $result = $database->single();
    echo "✓ Total subjects in database: " . $result['total_subjects'] . "\n";
    
    $database->query("SELECT year_level, section, semester, COUNT(*) as count FROM subjects GROUP BY year_level, section, semester ORDER BY year_level, section, semester");
    $database->execute();
    $results = $database->resultset();
    
    echo "\nSubject distribution by year level, section, and semester:\n";
    foreach ($results as $row) {
        echo "- {$row['year_level']} - Section {$row['section']} - {$row['semester']} semester: {$row['count']} subjects\n";
    }
    
    echo "\n✅ Subjects table migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
