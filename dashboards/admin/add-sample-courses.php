<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Adding Sample Courses</h2>";
    
    // Check if subjects table exists
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $result = $db->single();
    
    if ($result['count'] == 0) {
        echo "<p>No subjects found. Adding sample courses...</p>";
        
        // Sample courses data
        $sampleCourses = [
            [
                'subject_code' => 'MATH101',
                'subject_name' => 'College Algebra',
                'units' => 3,
                'year_level' => '1st Year',
                'semester' => 'first',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'ENG101',
                'subject_name' => 'English Composition',
                'units' => 3,
                'year_level' => '1st Year',
                'semester' => 'first',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'SCI101',
                'subject_name' => 'General Science',
                'units' => 3,
                'year_level' => '1st Year',
                'semester' => 'first',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'MATH102',
                'subject_name' => 'Trigonometry',
                'units' => 3,
                'year_level' => '1st Year',
                'semester' => 'second',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'ENG102',
                'subject_name' => 'Literature',
                'units' => 3,
                'year_level' => '1st Year',
                'semester' => 'second',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Programming',
                'units' => 3,
                'year_level' => '2nd Year',
                'semester' => 'first',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ],
            [
                'subject_code' => 'CS102',
                'subject_name' => 'Data Structures',
                'units' => 3,
                'year_level' => '2nd Year',
                'semester' => 'second',
                'academic_year' => '2024-2025',
                'status' => 'available'
            ]
        ];
        
        // Insert sample courses
        $db->query("INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, academic_year, status) 
                    VALUES (:subject_code, :subject_name, :units, :year_level, :semester, :academic_year, :status)");
        
        foreach ($sampleCourses as $course) {
            $db->bind(':subject_code', $course['subject_code']);
            $db->bind(':subject_name', $course['subject_name']);
            $db->bind(':units', $course['units']);
            $db->bind(':year_level', $course['year_level']);
            $db->bind(':semester', $course['semester']);
            $db->bind(':academic_year', $course['academic_year']);
            $db->bind(':status', $course['status']);
            $db->execute();
            
            echo "<p>✅ Added: " . $course['subject_code'] . " - " . $course['subject_name'] . "</p>";
        }
        
        echo "<p><strong>Sample courses added successfully!</strong></p>";
        
    } else {
        echo "<p>Subjects table already has " . $result['count'] . " records.</p>";
    }
    
    // Show current subjects
    echo "<h3>Current Subjects:</h3>";
    $db->query("SELECT subject_id, subject_code, subject_name, year_level, semester, academic_year, status FROM subjects ORDER BY subject_code");
    $db->execute();
    $subjects = $db->resultset();
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
    
    foreach ($subjects as $subject) {
        echo "<tr>";
        echo "<td>" . $subject['subject_id'] . "</td>";
        echo "<td>" . $subject['subject_code'] . "</td>";
        echo "<td>" . $subject['subject_name'] . "</td>";
        echo "<td>" . $subject['year_level'] . "</td>";
        echo "<td>" . $subject['semester'] . "</td>";
        echo "<td>" . $subject['academic_year'] . "</td>";
        echo "<td>" . $subject['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
