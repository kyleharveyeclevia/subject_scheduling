<?php
// Standardized Admin Sidebar Template
// Include this file in all admin pages for consistent sidebar styling

// Get the current page name to set the active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar Navigation -->
<aside class="admin-sidebar" id="adminSidebar" aria-expanded="true">
    <div class="sidebar-header">
        <div class="admin-profile">
            <div class="admin-avatar" onclick="toggleSidebar()">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-info">
                <h4><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></h4>
                <span>Administrator</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-users.php" class="nav-link <?php echo ($current_page === 'manage-users') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manage User Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-scheduling.php" class="nav-link <?php echo ($current_page === 'manage-scheduling') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Manage Scheduling Information</span>
                </a>
            </li>
            <li class="nav-item">
            </li>
            <li class="nav-item">
                <a href="generate-schedule.php" class="nav-link <?php echo ($current_page === 'generate-schedule') ? 'active' : ''; ?>">
                    <i class="fas fa-magic"></i>
                    <span>Generate Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="export-schedule.php" class="nav-link <?php echo ($current_page === 'export-schedule') ? 'active' : ''; ?>">
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
