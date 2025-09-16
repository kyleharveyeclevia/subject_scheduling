<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

// Get year level from URL parameter
$year_level = isset($_GET['year']) ? $_GET['year'] : '';

// Validate year level
$valid_years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
if (!in_array($year_level, $valid_years)) {
    header('Location: ../dashboard.php');
    exit();
}

// Ensure required classes are loaded
require_once __DIR__ . '/../../config/database.php';

// Get sections from database for the specific year level
$sections = [];
try {
    $db = new Database();
    $db->query("SELECT section_name, year_level FROM sections WHERE status = 'available' AND year_level = :year_level ORDER BY section_name ASC");
    $db->bind(':year_level', $year_level);
    $db->execute();
    $sections = $db->resultset();
} catch (Exception $e) {
    // Handle error silently for now
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($year_level); ?> Course Assignment - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar" aria-expanded="true">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar" onclick="toggleSidebar()">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Manage User Accounts</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-scheduling.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Manage Scheduling Information</span>
                        </a>
                    </li>

                    <li class="nav-item">
                    </li>
                    <li class="nav-item">
                        <a href="generate-schedule.php" class="nav-link">
                            <i class="fas fa-magic"></i>
                            <span>Generate Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="export-schedule.php" class="nav-link">
                            <i class="fas fa-download"></i>
                            <span>Export/Download Schedule</span>
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-footer">
                    <a href="../../auth/logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main" id="adminMain">
            <div class="main-header">
                <div class="header-content">
                    <a href="dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <h1 class="page-title">
                        <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($year_level); ?> Course Assignment
                    </h1>
                </div>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const sidebarToggle = document.getElementById('headerSidebarToggle');
                    const sidebar = document.querySelector('.admin-sidebar');
                    const mainContent = document.querySelector('.admin-main');
                    
                    // Header sidebar toggle functionality
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('collapsed');
                        mainContent.classList.toggle('sidebar-collapsed');
                    });
                });
            </script>

            <!-- Admin Section Navigation Cards -->
            <div class="admin-sections">
                <div class="admin-cards-grid">
                    <!-- Dashboard Overview Card -->
                    <a href="dashboard.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="card-content">
                            <h3>Dashboard Overview</h3>
                            <p>View system overview, statistics, and quick access to all administrative functions and reports.</p>
                        </div>
                    </a>

                    <!-- Manage User Accounts Card -->
                    <a href="manage-users.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3>Manage User Accounts</h3>
                            <p>Review and manage user registrations, approve or reject new accounts, and maintain user information.</p>
                        </div>
                    </a>

                    <!-- Manage Scheduling Information Card -->
                    <a href="manage-scheduling.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="card-content">
                            <h3>Manage Scheduling Information</h3>
                            <p>Configure and maintain scheduling parameters, set up time slots, rooms, and other scheduling requirements.</p>
                        </div>
                    </a>

                    <!-- Manage Academic Year Course Card -->

                    <!-- Generate Schedule Card -->
                    <a href="generate-schedule.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="card-content">
                            <h3>Generate Schedule</h3>
                            <p>Automatically create optimized schedules based on user preferences, room availability, and scheduling constraints.</p>
                        </div>
                    </a>

                    <!-- Export/Download Schedule Card -->
                    <a href="export-schedule.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="card-content">
                            <h3>Export/Download Schedule</h3>
                            <p>Export generated schedules in various formats, download reports, and share scheduling information with stakeholders.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="main-content">
                <!-- Content Area -->
                <div class="content-area">
                    <!-- Course Assignment Selection Card -->
                    <div class="dashboard-card" style="margin-bottom: 1.5rem; width: 100% !important; max-width: none !important;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tasks"></i> Course Assignment Selection
                            </h3>
                            <p class="card-subtitle">Select the Section and Semester for <?php echo htmlspecialchars($year_level); ?> course assignment</p>
                        </div>
                        
                        <div class="card-body">
                            <form id="assignmentSelectionForm" class="assignment-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="section">Section</label>
                                        <select id="section" name="section" class="form-select" required>
                                            <option value="" disabled selected>Select Section</option>
                                            <?php if (!empty($sections)): ?>
                                                <?php foreach ($sections as $section): ?>
                                                    <option value="<?php echo htmlspecialchars($section['section_name']); ?>">
                                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>No sections available for <?php echo htmlspecialchars($year_level); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="semester">Semester</label>
                                        <select id="semester" name="semester" class="form-select" required>
                                            <option value="" disabled selected>Select Semester</option>
                                            <option value="first">First Semester</option>
                                            <option value="second">Second Semester</option>
                                            <?php if ($year_level === '3rd Year'): ?>
                                                <option value="summer">Mid Year</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> View Available Courses
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset Selection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .dashboard-switcher{display:flex;gap:.5rem;flex-wrap:wrap;margin:.5rem 0 1rem}
        .dashboard-switcher .switch-link{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:999px;background:#fff;color:#334155;text-decoration:none;font-weight:500}
        .dashboard-switcher .switch-link.active{background:#eef2ff;border-color:#6366f1;color:#3730a3}
        .dashboard-switcher .switch-link:hover{background:#f8fafc;border-color:#cbd5e1}
        
        /* Course Assignment Card Styling */
        .dashboard-card {
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
            overflow: hidden !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            transform: scale(1) !important;
            box-sizing: border-box !important;
        }
        
        /* Force full width for content area */
        .content-area .dashboard-card {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        .card-header {
            background: #f8fafc !important;
            padding: 0.5rem 1rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
            min-height: auto !important;
        }
        
        .card-title {
            margin: 0 0 1rem 0 !important;
            color: #1e293b !important;
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            line-height: 1.3 !important;
        }
        
        .card-subtitle {
            margin: 0 !important;
            color: #1e293b !important;
            font-size: 1.1rem !important;
            line-height: 1.4 !important;
            text-align: left !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-weight: 500 !important;
        }
        
        .card-body {
            padding: 0.5rem 1rem !important;
            min-height: auto !important;
        }
        
        .assignment-form {
            width: 100%;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.25rem;
            color: #374151;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .form-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-select:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .form-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #5855eb;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        /* Force full width layout */
        .main-content {
            width: 100% !important;
            max-width: none !important;
        }
        
        .content-area {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
        }

        /* Admin Section Cards */
        .admin-sections {
            margin-bottom: 2rem;
        }

        .admin-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            min-height: 120px;
        }

        .admin-section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
            text-decoration: none;
            color: inherit;
        }

        .admin-section-card .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, #667eea, #5a6fd8);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .admin-section-card .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .admin-section-card .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .admin-section-card .card-content p {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.4;
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-cards-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .admin-section-card {
                padding: 1.25rem;
                min-height: 100px;
            }
            
            .admin-section-card .card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }
        }
        }
        
        /* Header styling */
        .header-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .back-link {
            color: #6366f1;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>

    <script>
        // Auto-collapse sidebar when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.admin-main');
            
            if (sidebar && mainContent) {
                // Auto-collapse sidebar after a short delay when page loads
                setTimeout(() => {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    console.log('Sidebar auto-collapsed on page load');
                }, 500);
            }
            
            // Initialize form functionality
            initializeAssignmentForm();
        });
        
        // Initialize the assignment form functionality
        function initializeAssignmentForm() {
            const form = document.getElementById('assignmentSelectionForm');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmission();
                });
            }
        }
        
        // Handle form submission
        function handleFormSubmission() {
            const section = document.getElementById('section').value;
            const semester = document.getElementById('semester').value;
            
            if (!section || !semester) {
                alert('Please select both Section and Semester');
                return;
            }
            
            // Show success message (you can replace this with actual functionality)
            alert(`Selection confirmed!\nSection: ${section}\nSemester: ${semester === 'summer' ? 'Mid Year' : semester === 'first' ? 'First Semester' : 'Second Semester'}`);
            
            console.log('Form submitted:', { section, semester });
            // Here you can add the actual logic to fetch available courses
            // or redirect to another page with the selected parameters
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const mainContent = document.getElementById('adminMain');
            if (sidebar && mainContent) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
            }
        }
    </script>
</body>
</html>
