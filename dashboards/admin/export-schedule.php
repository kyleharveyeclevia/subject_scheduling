<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../classes/User.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export/Download Schedule - Subject Scheduling System</title>
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
                        <a href="export-schedule.php" class="nav-link active">
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
                <h1 class="page-title">
                    <i class="fas fa-download"></i> Export/Download Schedule
                </h1>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

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
                </div>
            </div>

            <div class="main-content">
                <div class="export-options">
                    <div class="export-section">
                        <h3><i class="fas fa-filter"></i> Select Schedule to Export</h3>
                        <div class="filter-grid">
                            <div class="form-group">
                                <label class="form-label">Academic Year</label>
                                <select class="form-input" id="academicYear">
                                    <option value="">All Academic Years</option>
                                    <option value="2024-2025">2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Semester</label>
                                <select class="form-input" id="semester">
                                    <option value="">All Semesters</option>
                                    <option value="1st">1st Semester</option>
                                    <option value="2nd">2nd Semester</option>
                                    <option value="summer">Summer</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <select class="form-input" id="department">
                                    <option value="">All Departments</option>
                                    <option value="College of Communication and Information Technology">CCIT</option>
                                    <option value="College of Teacher Education">CTE</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Year Level</label>
                                <select class="form-input" id="yearLevel">
                                    <option value="">All Year Levels</option>
                                    <option value="First Year">First Year</option>
                                    <option value="Second Year">Second Year</option>
                                    <option value="Third Year">Third Year</option>
                                    <option value="Fourth Year">Fourth Year</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="export-formats">
                        <h3><i class="fas fa-file-export"></i> Export Formats</h3>
                        <div class="format-grid">
                            <div class="format-card">
                                <div class="format-icon pdf">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <h4>PDF Document</h4>
                                <p>Professional printable format with complete schedule details</p>
                                <button class="btn btn-primary" onclick="exportSchedule('pdf')">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                            </div>

                            <div class="format-card">
                                <div class="format-icon excel">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <h4>Excel Spreadsheet</h4>
                                <p>Editable format for further analysis and modifications</p>
                                <button class="btn btn-success" onclick="exportSchedule('excel')">
                                    <i class="fas fa-download"></i> Download Excel
                                </button>
                            </div>

                            <div class="format-card">
                                <div class="format-icon csv">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <h4>CSV File</h4>
                                <p>Simple format compatible with most applications</p>
                                <button class="btn btn-info" onclick="exportSchedule('csv')">
                                    <i class="fas fa-download"></i> Download CSV
                                </button>
                            </div>

                            <div class="format-card">
                                <div class="format-icon ical">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <h4>iCal Calendar</h4>
                                <p>Import directly into calendar applications</p>
                                <button class="btn btn-warning" onclick="exportSchedule('ical')">
                                    <i class="fas fa-download"></i> Download iCal
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="export-options-section">
                        <h3><i class="fas fa-cogs"></i> Export Options</h3>
                        <div class="options-grid">
                            <div class="option-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="includeTeachers" checked>
                                    <span class="checkmark"></span>
                                    Include Teacher Information
                                </label>
                            </div>
                            <div class="option-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="includeRooms" checked>
                                    <span class="checkmark"></span>
                                    Include Room/Venue Details
                                </label>
                            </div>
                            <div class="option-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="includeStudentCount">
                                    <span class="checkmark"></span>
                                    Include Student Count
                                </label>
                            </div>
                            <div class="option-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="colorCoded">
                                    <span class="checkmark"></span>
                                    Color-coded by Department
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="recent-exports">
                        <h3><i class="fas fa-history"></i> Recent Exports</h3>
                        <div class="exports-list">
                            <div class="export-item">
                                <div class="export-info">
                                    <div class="export-name">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <span>Full Schedule - 2024-2025 1st Sem.pdf</span>
                                    </div>
                                    <div class="export-meta">
                                        <span>Exported on: January 15, 2025 2:30 PM</span>
                                        <span>Size: 2.4 MB</span>
                                    </div>
                                </div>
                                <div class="export-actions">
                                    <button class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i> Re-download
                                    </button>
                                </div>
                            </div>

                            <div class="export-item">
                                <div class="export-info">
                                    <div class="export-name">
                                        <i class="fas fa-file-excel text-success"></i>
                                        <span>CCIT Schedule - 2024-2025.xlsx</span>
                                    </div>
                                    <div class="export-meta">
                                        <span>Exported on: January 10, 2025 10:15 AM</span>
                                        <span>Size: 1.8 MB</span>
                                    </div>
                                </div>
                                <div class="export-actions">
                                    <button class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i> Re-download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Coming Soon:</strong> The schedule export functionality is under development. You'll be able to export schedules in multiple formats including PDF, Excel, CSV, and iCal calendar formats.
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/partials/modals.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script>
        function exportSchedule(format) {
            // Get selected filters
            const filters = {
                academicYear: document.getElementById('academicYear').value,
                semester: document.getElementById('semester').value,
                department: document.getElementById('department').value,
                yearLevel: document.getElementById('yearLevel').value,
                includeTeachers: document.getElementById('includeTeachers').checked,
                includeRooms: document.getElementById('includeRooms').checked,
                includeStudentCount: document.getElementById('includeStudentCount').checked,
                colorCoded: document.getElementById('colorCoded').checked
            };

            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            button.disabled = true;

            // Simulate export process (replace with actual implementation)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                // Show success message via shared modal notice
                showAlert(`Schedule exported successfully as ${format.toUpperCase()}!`, 'success');
            }, 2000);
        }
        // Aliases to use shared modals consistently
        function showAlert(message, type){ try { showNotice(message, type); } catch(e) { alert(String(message)); } }
        function modalConfirm(message){ try { return showConfirm(message, { confirmText: 'Confirm', cancelText: 'Cancel' }); } catch(e) { return Promise.resolve(confirm(String(message))); } }

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

    <style>
        /* Admin Layout Styles */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: #f8fafc;
        }

        .admin-sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .admin-info h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .admin-info span {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-link {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .logout-link:hover {
            background: rgba(255, 0, 0, 0.1) !important;
            color: #ff6b6b !important;
            border-left-color: #ff6b6b !important;
        }

        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .main-header {
            margin-bottom: 2rem;
        }


        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title i {
            color: var(--primary-color);
        }

        .main-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 2rem;
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

        .export-section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .export-section h3 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .format-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .format-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .format-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .format-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .format-icon.pdf { background: #dc3545; }
        .format-icon.excel { background: #28a745; }
        .format-icon.csv { background: #17a2b8; }
        .format-icon.ical { background: #ffc107; }

        .format-card h4 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .format-card p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .checkbox-label:hover {
            background: #f8fafc;
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .exports-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .export-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .export-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .export-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }

        .info-section {
            margin-top: 2rem;
        }
    </style>
</body>
</html>
