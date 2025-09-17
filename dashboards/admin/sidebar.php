<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get current file name
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="admin-profile">
            <div class="admin-avatar">
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
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-users.php" class="nav-link <?php echo ($current_page == 'manage-users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manage User Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-scheduling.php" class="nav-link <?php echo ($current_page == 'manage-scheduling.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Manage Scheduling Information</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="generate-schedule.php" class="nav-link <?php echo ($current_page == 'generate-schedule.php') ? 'active' : ''; ?>">
                    <i class="fas fa-magic"></i>
                    <span>Generate Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="export-schedule.php" class="nav-link <?php echo ($current_page == 'export-schedule.php') ? 'active' : ''; ?>">
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