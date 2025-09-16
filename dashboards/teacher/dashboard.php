<?php
session_start();
require_once '../../classes/User.php';

// Check if user is logged in, is a teacher, and has approved status
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || $_SESSION['status'] !== 'approved') {
    // If not approved, show a message and redirect
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'teacher' && $_SESSION['status'] !== 'approved') {
        // Clear the session
        session_unset();
        session_destroy();
        // Redirect with error message
        header('Location: ../../index.php?error=Your+account+is+not+approved+yet');
    } else {
        header('Location: ../../index.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" aria-expanded="true">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                        <span>Teacher</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="my-subjects.php" class="nav-link">
                            <i class="fas fa-book"></i>
                            <span>My Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="schedule.php" class="nav-link">
                            <i class="fas fa-calendar"></i>
                            <span>My Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>My Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profile Settings</span>
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
        <main class="admin-main">
            <div class="content-header">
                <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                <div style="margin-top: 0.5rem;">
                    <span class="status-badge status-approved">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($_SESSION['department']); ?>
                    </span>
                    <span class="status-badge" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color); margin-left: 0.5rem;">
                        ID: <?php echo htmlspecialchars($_SESSION['teacher_id']); ?>
                    </span>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                            <h3>My Subjects</h3>
                            <div class="dashboard-stat">0</div>
                            <p>Subjects assigned to you</p>
                        </div>
                    </div>
                    <a href="my-subjects.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-eye"></i> View Subjects
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon success">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h3>Today's Classes</h3>
                            <div class="dashboard-stat">0</div>
                            <p>Classes scheduled for today</p>
                        </div>
                    </div>
                    <a href="schedule.php" class="btn btn-success" style="margin-top: 1rem;">
                        <i class="fas fa-calendar"></i> View Schedule
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h3>Total Students</h3>
                            <div class="dashboard-stat">0</div>
                            <p>Students in your classes</p>
                        </div>
                    </div>
                    <a href="students.php" class="btn btn-warning" style="margin-top: 1rem;">
                        <i class="fas fa-users"></i> View Students
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-card">
                <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-calendar-plus" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <h4>Welcome to the Subject Scheduling System!</h4>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Your account has been approved. You can now access all teacher features including subject management and scheduling.
                    </p>
                    <div class="flex gap-2 justify-center">
                        <a href="my-subjects.php" class="btn btn-primary">
                            <i class="fas fa-book"></i> Manage Subjects
                        </a>
                        <a href="schedule.php" class="btn btn-secondary">
                            <i class="fas fa-calendar"></i> View Schedule
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="dashboard-grid">
                    <a href="create-schedule.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Schedule
                    </a>
                    <a href="attendance.php" class="btn btn-secondary">
                        <i class="fas fa-check-square"></i> Take Attendance
                    </a>
                    <a href="grades.php" class="btn btn-secondary">
                        <i class="fas fa-star"></i> Manage Grades
                    </a>
                    <a href="announcements.php" class="btn btn-secondary">
                        <i class="fas fa-bullhorn"></i> Announcements
                    </a>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="dashboard-card">
                <h3><i class="fas fa-user"></i> Profile Information</h3>
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                    <div>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p><strong>Instructor ID:</strong> <?php echo htmlspecialchars($_SESSION['teacher_id']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($_SESSION['department']); ?></p>
                    </div>
                    <div>
                        
                        <p><strong>Status:</strong> <span class="status-badge status-approved">Active</span></p>
                        <a href="profile.php" class="btn btn-secondary" style="margin-top: 1rem;">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
