<?php
session_start();
require_once '../../classes/User.php';

// Check if user is logged in, is a student, and has approved status
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || $_SESSION['status'] !== 'approved') {
    // If not approved, show a message and redirect
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student' && $_SESSION['status'] !== 'approved') {
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
    <title>Student Dashboard - Subject Scheduling System</title>
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
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                        <span>Student</span>
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
                        <a href="my-schedule.php" class="nav-link">
                            <i class="fas fa-calendar"></i>
                            <span>My Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="subjects.php" class="nav-link">
                            <i class="fas fa-book"></i>
                            <span>My Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="grades.php" class="nav-link">
                            <i class="fas fa-star"></i>
                            <span>My Grades</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="attendance.php" class="nav-link">
                            <i class="fas fa-check-square"></i>
                            <span>Attendance Record</span>
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
                <h1><i class="fas fa-user-graduate"></i> Student Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                <div style="margin-top: 0.5rem;">
                    <span class="status-badge status-approved">
                        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($_SESSION['year_level']); ?> - Section <?php echo htmlspecialchars($_SESSION['section']); ?>
                    </span>
                    <span class="status-badge" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color); margin-left: 0.5rem;">
                        ID: <?php echo htmlspecialchars($_SESSION['student_id']); ?>
                    </span>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h3>Enrolled Subjects</h3>
                            <div class="dashboard-stat">0</div>
                            <p>Subjects you're enrolled in</p>
                        </div>
                    </div>
                    <a href="subjects.php" class="btn btn-primary" style="margin-top: 1rem;">
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
                    <a href="my-schedule.php" class="btn btn-success" style="margin-top: 1rem;">
                        <i class="fas fa-calendar"></i> View Schedule
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon warning">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h3>Overall GPA</h3>
                            <div class="dashboard-stat">-</div>
                            <p>Your current GPA</p>
                        </div>
                    </div>
                    <a href="grades.php" class="btn btn-warning" style="margin-top: 1rem;">
                        <i class="fas fa-chart-line"></i> View Grades
                    </a>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="dashboard-card">
                <h3><i class="fas fa-clock"></i> Today's Schedule</h3>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-calendar-day" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <h4>No Classes Today</h4>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        You don't have any classes scheduled for today. Check your full schedule to see upcoming classes.
                    </p>
                    <a href="my-schedule.php" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> View Full Schedule
                    </a>
                </div>
            </div>

            <!-- Upcoming Classes -->
            <div class="dashboard-card">
                <h3><i class="fas fa-calendar-week"></i> Upcoming Classes</h3>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>This Week's Schedule</h4>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        View your upcoming classes for this week and plan your study schedule accordingly.
                    </p>
                    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); margin-top: 1rem;">
                        <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">Mon</div>
                            <small style="color: var(--text-secondary);">0 classes</small>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">Tue</div>
                            <small style="color: var(--text-secondary);">0 classes</small>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">Wed</div>
                            <small style="color: var(--text-secondary);">0 classes</small>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">Thu</div>
                            <small style="color: var(--text-secondary);">0 classes</small>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">Fri</div>
                            <small style="color: var(--text-secondary);">0 classes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Announcements -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-bell" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <h4>Welcome to the System!</h4>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Your student account has been approved. You can now access your schedule, view subjects, and track your academic progress.
                    </p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Getting Started:</strong> Visit the Subjects page to see your enrolled courses and the Schedule page to view your class timetable.
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="dashboard-grid">
                    <a href="my-schedule.php" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> View Schedule
                    </a>
                    <a href="subjects.php" class="btn btn-secondary">
                        <i class="fas fa-book"></i> My Subjects
                    </a>
                    <a href="grades.php" class="btn btn-secondary">
                        <i class="fas fa-star"></i> Check Grades
                    </a>
                    <a href="attendance.php" class="btn btn-secondary">
                        <i class="fas fa-check-square"></i> Attendance Record
                    </a>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="dashboard-card">
                <h3><i class="fas fa-user"></i> Profile Information</h3>
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                    <div>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($_SESSION['year_level']); ?></p>
                    </div>
                    <div>
                        <p><strong>Section:</strong> <?php echo htmlspecialchars($_SESSION['section']); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-approved">Active</span></p>
                        <a href="profile.php" class="btn btn-secondary" style="margin-top: 1rem;">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Academic Progress -->
            <div class="dashboard-card">
                <h3><i class="fas fa-chart-line"></i> Academic Progress</h3>
                <div class="text-center" style="padding: 2rem;">
                    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 600; color: var(--success-color);">0</div>
                            <small style="color: var(--text-secondary);">Completed Subjects</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 600; color: var(--primary-color);">0</div>
                            <small style="color: var(--text-secondary);">Current Subjects</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 600; color: var(--warning-color);">0%</div>
                            <small style="color: var(--text-secondary);">Attendance Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
