<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../config/database.php';

$user = new User();

// Get all users with their role-specific data
try {
    $db = new Database();
    
    // Get all users with role-specific information (excluding admin users)
    $db->query('SELECT u.*, 
                       CASE 
                           WHEN u.role = "teacher" THEN t.department
                           WHEN u.role = "student" THEN CONCAT(s.year_level, " - Section ", s.section)
                           ELSE NULL
                       END as additional_info,
                       CASE 
                           WHEN u.role = "teacher" THEN t.teacher_id
                           WHEN u.role = "student" THEN s.student_id
                           ELSE NULL
                       END as role_id
                FROM users u
                LEFT JOIN teachers t ON u.user_id = t.user_id
                LEFT JOIN students s ON u.user_id = s.user_id
                WHERE u.role != "admin"
                ORDER BY u.created_at DESC');
    
    $all_users = $db->resultset();
    
    // Separate users by status
    $pending_users = array_filter($all_users, function($u) { return $u['status'] === 'pending'; });
    $approved_users = array_filter($all_users, function($u) { return $u['status'] === 'approved'; });
    $rejected_users = array_filter($all_users, function($u) { return $u['status'] === 'rejected'; });
    
} catch (Exception $e) {
    $error_message = "Error loading users: " . $e->getMessage();
    $all_users = [];
    $pending_users = [];
    $approved_users = [];
    $rejected_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Subject Scheduling System</title>
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
                        <a href="manage-users.php" class="nav-link active">
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
        <main class="admin-main">
            <!-- Main Header at the Top -->
            <div class="main-header">
                <h1 class="page-title">
                    <i class="fas fa-users"></i> Manage User Accounts
                </h1>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const sidebarToggle = document.getElementById('headerSidebarToggle');
                    const sidebar = document.querySelector('.admin-sidebar');
                    const mainContent = document.querySelector('.admin-main');
                    const navLinks = document.querySelectorAll('.nav-link');
                    
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('collapsed');
                        mainContent.classList.toggle('expanded');
                    });

                    navLinks.forEach(link => {
                        link.addEventListener('click', function() {
                            if (!sidebar.classList.contains('collapsed')) {
                                sidebar.classList.add('collapsed');
                                mainContent.classList.add('expanded');
                            }
                        });
                    });
                });
            </script>

            <!-- User Management Header - Count Boxes Below Header -->
            <div class="user-management-header">
                <div class="header-stats">
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($pending_users); ?></h3>
                            <p>Pending Approval</p>
                        </div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($approved_users); ?></h3>
                            <p>Approved Users</p>
                        </div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($rejected_users); ?></h3>
                            <p>Rejected Users</p>
                        </div>
                    </div>
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($all_users); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin: 20px 0;">
            </div>

            <div class="main-content">

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- User Management Tabs -->
            <div class="tabs enhanced-tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="pending">
                        <i class="fas fa-clock"></i> 
                        <span>Pending Approval</span>
                    </button>
                    <button class="tab-button" data-tab="approved">
                        <i class="fas fa-check-circle"></i> 
                        <span>Approved Users</span>
                    </button>
                    <button class="tab-button" data-tab="rejected">
                        <i class="fas fa-times-circle"></i> 
                        <span>Rejected Users</span>
                    </button>
                </div>
                
                <!-- Search and Filter Bar -->
                <div class="management-controls">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users by name or ID..." class="search-input">
                    </div>
                    <div class="filter-controls">
                        <select id="roleFilter" class="filter-select">
                            <option value="">All Roles</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                </div>

                <!-- Pending Users Tab -->
                <div class="tab-content active" id="pending">
                    <?php if (count($pending_users) > 0): ?>
                        <div class="table-container" style="width: 100%; overflow: hidden; display: flex; justify-content: center;">
                            <table class="table users-table" id="pendingTable" style="width: 100%; table-layout: fixed; font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%; padding: 0.3rem;">User</th>
                                        <th style="width: 8%; padding: 0.3rem;">Role</th>
                                        
                                        <th style="width: 15%; padding: 0.3rem;">Additional Information</th>
                                        <th style="width: 12%; padding: 0.3rem;">Time and Date of Registration</th>
                                        <th style="width: 10%; padding: 0.3rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                            <?php foreach ($pending_users as $pending_user): ?>
                                    <tr data-user-id="<?php echo $pending_user['user_id']; ?>" data-role="<?php echo $pending_user['role']; ?>" data-status="pending">
                                        <td class="cell-name">
                                            <strong><?php echo htmlspecialchars($pending_user['full_name']); ?></strong><br>
                                            <small class="cell-id">ID: <?php echo htmlspecialchars($pending_user['role_id']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $pending_user['role']; ?>"><?php echo ucfirst($pending_user['role']); ?></span>
                                        </td>
                                        
                                        <td><?php echo htmlspecialchars($pending_user['additional_info'] ?? ''); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($pending_user['created_at'])); ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button class="btn btn-success" onclick="updateUserStatus('<?php echo $pending_user['user_id']; ?>', 'approved')"><i class="fas fa-check"></i> Approve</button>
                                                <button class="btn btn-danger" onclick="updateUserStatus('<?php echo $pending_user['user_id']; ?>', 'rejected')"><i class="fas fa-times"></i> Reject</button>
                                    </div>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>No Pending Approvals</h3>
                            <p>All user accounts have been reviewed. New registrations will appear here for your approval.</p>
                            <button class="btn btn-primary" onclick="refreshUserData()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Approved Users Tab -->
                <div class="tab-content" id="approved">
                    <?php if (count($approved_users) > 0): ?>
                        <div class="table-container" style="width: 100%; overflow: hidden; display: flex; justify-content: center;">
                            <table class="table users-table" id="approvedTable" style="width: 100%; table-layout: fixed; font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%; padding: 0.3rem;">User</th>
                                        <th style="width: 8%; padding: 0.3rem;">Role</th>
                                        
                                        <th style="width: 15%; padding: 0.3rem;">Additional Information</th>
                                        <th style="width: 12%; padding: 0.3rem;">Time and Date of Registration</th>
                                        <th style="width: 10%; padding: 0.3rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                            <?php foreach ($approved_users as $approved_user): ?>
                                    <tr data-user-id="<?php echo $approved_user['user_id']; ?>" data-role="<?php echo $approved_user['role']; ?>" data-status="approved">
                                        <td class="cell-name">
                                            <strong><?php echo htmlspecialchars($approved_user['full_name']); ?></strong><br>
                                            <small class="cell-id">ID: <?php echo htmlspecialchars($approved_user['role_id']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $approved_user['role']; ?>"><?php echo ucfirst($approved_user['role']); ?></span>
                                        </td>
                                        
                                        <td><?php echo htmlspecialchars($approved_user['additional_info'] ?? ''); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($approved_user['updated_at'] ?? $approved_user['created_at'])); ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button class="btn btn-danger" onclick="updateUserStatus('<?php echo $approved_user['user_id']; ?>', 'rejected')"><i class="fas fa-ban"></i> Suspend</button>
                                    </div>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>No Approved Users</h3>
                            <p>Approved users will appear here once you approve pending registrations.</p>
                            <button class="btn btn-primary" onclick="switchTab('pending')"><i class="fas fa-clock"></i> View Pending</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rejected Users Tab -->
                <div class="tab-content" id="rejected">
                    <?php if (count($rejected_users) > 0): ?>
                        <div class="table-container" style="width: 100%; overflow: hidden; display: flex; justify-content: center;">
                            <table class="table users-table" id="rejectedTable" style="width: 100%; table-layout: fixed; font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%; padding: 0.3rem;">User</th>
                                        <th style="width: 8%; padding: 0.3rem;">Role</th>
                                        
                                        <th style="width: 15%; padding: 0.3rem;">Additional Information</th>
                                        <th style="width: 12%; padding: 0.3rem;">Time and Date of Registration</th>
                                        <th style="width: 10%; padding: 0.3rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                            <?php foreach ($rejected_users as $rejected_user): ?>
                                    <tr data-user-id="<?php echo $rejected_user['user_id']; ?>" data-role="<?php echo $rejected_user['role']; ?>" data-status="rejected">
                                        <td class="cell-name">
                                            <strong><?php echo htmlspecialchars($rejected_user['full_name']); ?></strong><br>
                                            <small class="cell-id">ID: <?php echo htmlspecialchars($rejected_user['role_id']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $rejected_user['role']; ?>"><?php echo ucfirst($rejected_user['role']); ?></span>
                                        </td>
                                        
                                        <td><?php echo htmlspecialchars($rejected_user['additional_info'] ?? ''); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($rejected_user['updated_at'] ?? $rejected_user['created_at'])); ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button class="btn btn-success" onclick="updateUserStatus('<?php echo $rejected_user['user_id']; ?>', 'approved')"><i class="fas fa-check"></i> Approve</button>
                                                <button class="btn btn-danger" onclick="deleteUser('<?php echo $rejected_user['user_id']; ?>')"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>No Rejected Users</h3>
                            <p>Great! You haven't rejected any users yet. This helps maintain a positive user experience.</p>
                            <button class="btn btn-primary" onclick="switchTab('pending')"><i class="fas fa-clock"></i> View Pending</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit User</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div id="editFormFields">
                        <!-- Dynamic form fields will be loaded here -->
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Centered Message Modal -->
    <div id="messageModal" class="modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3 id="messageModalTitle" style="display:flex;align-items:center;gap:.5rem;">
                    <i id="messageModalIcon" class="fas fa-info-circle"></i>
                    <span>Notice</span>
                </h3>
                <button class="modal-close" onclick="closeMessageModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="messageModalContent"></div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="closeMessageModal()" style="min-width: 80px; padding: 10px 20px; cursor: pointer;">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Centered Confirm Modal -->
    <div id="confirmModal" class="modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3 style="display:flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-question-circle"></i>
                    <span>Confirm Action</span>
                </h3>
                <button class="modal-close" onclick="closeConfirmModal(false)" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="confirmModalMessage"></div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="confirmNoBtn">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmYesBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Custom Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="loading-spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
            </div>
            <div class="loading-text">
                <h3>Processing Request</h3>
                <p>Please wait while we process your request...</p>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Header Sidebar Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const headerToggle = document.getElementById('headerSidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.admin-main');
            
            if (headerToggle && sidebar && mainContent) {
                // Check saved state
                const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                
                if (isSidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    headerToggle.classList.add('sidebar-collapsed');
                }
                
                headerToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('sidebar-collapsed');
                    headerToggle.classList.toggle('sidebar-collapsed');
                    
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                });
            }
        });
    </script>
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        });

        // Update user status (uses centered confirm modal)
        async function updateUserStatus(userId, status) {
            const action = status === 'approved' ? 'approve' : status === 'rejected' ? 'reject' : 'suspend';

            const ok = await modalConfirm(`Are you sure you want to ${action} this user?`);
            if (!ok) return;

            try {
                showLoading(); // Show custom loading overlay
                
                console.log(`Updating user ${userId} status to ${status}`);
                
                const response = await fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${encodeURIComponent(userId)}&status=${encodeURIComponent(status)}`
                });

                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('Response result:', result);
                
                hideLoading(); // Hide loading overlay
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message || 'Failed to update user status', 'error');
                }
            } catch (error) {
                hideLoading(); // Hide loading overlay on error
                console.error('Error updating user status:', error);
                
                let errorMessage = 'An error occurred while updating user status';
                if (error.message.includes('HTTP error')) {
                    errorMessage = 'Server error occurred. Please try again.';
                } else if (error.message.includes('fetch')) {
                    errorMessage = 'Network error. Please check your connection.';
                }
                
                showAlert(errorMessage, 'error');
            }
        }

        // View user details
        async function viewUserDetails(userId) {
            try {
                showLoading(); // Show custom loading overlay
                
                const response = await fetch(`get-user.php?user_id=${userId}`);
                const result = await response.json();

                hideLoading(); // Hide loading overlay

                if (!result.success) {
                    showAlert(result.message || 'Error loading user data', 'error');
                    return;
                }

                const user = result.data;
                const roleId = user.role === 'teacher' ? (user.teacher_id || '—') : user.role === 'student' ? (user.student_id || '—') : '—';
                const createdAt = user.created_at ? user.created_at : '';
                const updatedAt = user.updated_at ? user.updated_at : '';

                const html = `
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div>${user.full_name || ''}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">System User ID (Login)</label>
                        <div><code>${user.user_id}</code></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div>${(user.role || '').charAt(0).toUpperCase() + (user.role || '').slice(1)}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role-specific ID</label>
                        <div>${roleId !== '—' ? `<code>${roleId}</code>` : '—'}</div>
                    </div>
                    <div class="form-group">
                        
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div>${user.status || ''}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registered At</label>
                        <div>${createdAt}</div>
                    </div>
                    ${updatedAt ? `
                    <div class="form-group">
                        <label class="form-label">Last Updated</label>
                        <div>${updatedAt}</div>
                    </div>` : ''}
                    <div class="form-group">
                        <label class="form-label">Login Credentials</label>
                        <div class="credentials-box">
                            <div class="credential-item">
                                <strong>User ID:</strong> <code>${user.user_id}</code>
                            </div>
                            <div class="credential-item">
                                <strong>Password:</strong> <span class="password-placeholder">••••••••</span>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="showPassword('${user.user_id}')">
                                    <i class="fas fa-eye"></i> Show Password
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="resetUserPassword('${user.user_id}')">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info" style="margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> For security reasons, actual passwords cannot be displayed as only password hashes are stored in the system. You can reset a user's password if needed.
                    </div>
                `;

                document.getElementById('viewUserContent').innerHTML = html;
                document.getElementById('viewUserModal').style.display = 'flex';
            } catch (err) {
                hideLoading(); // Hide loading overlay on error
                console.error('Error loading user details:', err);
                showAlert('An error occurred while loading user details', 'error');
            }
        }

        function closeViewModal() {
            document.getElementById('viewUserModal').style.display = 'none';
        }

        // Edit user functionality
        async function editUser(userId) {
            try {
                showLoading(); // Show custom loading overlay
                
                const response = await fetch(`admin/get-user.php?user_id=${userId}`);
                const user = await response.json();
                
                hideLoading(); // Hide loading overlay
                
                if (user.success) {
                    populateEditForm(user.data);
                    document.getElementById('editUserModal').style.display = 'flex';
                } else {
                    showAlert('Error loading user data', 'error');
                }
            } catch (error) {
                hideLoading(); // Hide loading overlay on error
                console.error('Error loading user:', error);
                showAlert('An error occurred while loading user data', 'error');
            }
        }

        function populateEditForm(user) {
            document.getElementById('editUserId').value = user.user_id;
            
            let formFields = `
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" value="${user.full_name}" required>
                </div>
            `;

            

            // Add role-specific fields
            if (user.role === 'teacher') {
                formFields += `
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-input" required>
                            <option value="College of Communication and Information Technology" ${user.department === 'College of Communication and Information Technology' ? 'selected' : ''}>College of Communication and Information Technology</option>
                            <option value="College of Teacher Education" ${user.department === 'College of Teacher Education' ? 'selected' : ''}>College of Teacher Education</option>
                        </select>
                    </div>
                `;
            } else if (user.role === 'student') {
                formFields += `
                    <div class="form-group">
                        <label class="form-label">Year Level</label>
                        <select name="year_level" class="form-input" required>
                            <option value="First Year" ${user.year_level === 'First Year' ? 'selected' : ''}>First Year</option>
                            <option value="Second Year" ${user.year_level === 'Second Year' ? 'selected' : ''}>Second Year</option>
                            <option value="Third Year" ${user.year_level === 'Third Year' ? 'selected' : ''}>Third Year</option>
                            <option value="Fourth Year" ${user.year_level === 'Fourth Year' ? 'selected' : ''}>Fourth Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section</label>
                        <select name="section" class="form-input" required>
                            <option value="A" ${user.section === 'A' ? 'selected' : ''}>Section A</option>
                            <option value="B" ${user.section === 'B' ? 'selected' : ''}>Section B</option>
                            <option value="C" ${user.section === 'C' ? 'selected' : ''}>Section C</option>
                        </select>
                    </div>
                `;
            }

            document.getElementById('editFormFields').innerHTML = formFields;
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Handle edit form submission
        document.getElementById('editUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                showLoading(); // Show custom loading overlay
                
                const response = await fetch('admin/update-user.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                hideLoading(); // Hide loading overlay
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeEditModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                hideLoading(); // Hide loading overlay on error
                console.error('Error updating user:', error);
                showAlert('An error occurred while updating user', 'error');
            }
        });

        // Centered message modal (replaces browser popups and page-top alerts)
        function showAlert(message, type) {
            const modal = document.getElementById('messageModal');
            const modalContent = modal.querySelector('.modal-content');
            const icon = document.getElementById('messageModalIcon');
            const title = document.getElementById('messageModalTitle').querySelector('span');
            const content = document.getElementById('messageModalContent');

            // Remove any existing type classes
            modal.classList.remove('success', 'error', 'warning', 'info');

            // Configure icon and title by type
            if (type === 'success') { 
                icon.className = 'fas fa-check-circle'; 
                title.textContent = 'Success';
                modal.classList.add('success');
            }
            else if (type === 'error') { 
                icon.className = 'fas fa-exclamation-circle'; 
                title.textContent = 'Error';
                modal.classList.add('error');
            }
            else if (type === 'warning') { 
                icon.className = 'fas fa-exclamation-triangle'; 
                title.textContent = 'Warning';
                modal.classList.add('warning');
            }
            else { 
                icon.className = 'fas fa-info-circle'; 
                title.textContent = 'Notice';
                modal.classList.add('info');
            }

            content.textContent = message;
            modal.style.display = 'flex';
            
            // Add scale animation
            setTimeout(() => {
                modalContent.classList.add('show');
            }, 10);

            // Add event listeners for better UX
            const okButton = modal.querySelector('.btn-primary');
            if (okButton) {
                okButton.focus(); // Focus the OK button for better accessibility
            }

            // Auto close after 5s
            clearTimeout(window.__messageModalTimer);
            window.__messageModalTimer = setTimeout(() => {
                closeMessageModal();
            }, 5000);
        }

        function closeMessageModal() {
            const modal = document.getElementById('messageModal');
            const modalContent = modal.querySelector('.modal-content');
            if (modal) {
                // Remove scale animation
                modalContent.classList.remove('show');
                
                // Hide modal after animation
                setTimeout(() => {
                modal.style.display = 'none';
                }, 300);
                
                // Clear any pending auto-close timer
                clearTimeout(window.__messageModalTimer);
            }
        }

        // Add keyboard event listener for Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const messageModal = document.getElementById('messageModal');
                if (messageModal && messageModal.style.display === 'flex') {
                    closeMessageModal();
                }
            }
        });

        // Add click outside modal to close functionality
        document.addEventListener('click', function(event) {
            const messageModal = document.getElementById('messageModal');
            if (messageModal && messageModal.style.display === 'flex') {
                if (event.target === messageModal) {
                    closeMessageModal();
                }
            }
        });

        // Reusable centered confirm modal
        function modalConfirm(message) {
            console.log('modalConfirm function called with message:', message);
            
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const msg = document.getElementById('confirmModalMessage');
                const yesBtn = document.getElementById('confirmYesBtn');
                const noBtn = document.getElementById('confirmNoBtn');
                const modalHeader = modal.querySelector('.modal-header h3 span');
                const modalIcon = modal.querySelector('.modal-header h3 i');
                
                console.log('Modal elements found:', {
                    modal: !!modal,
                    msg: !!msg,
                    yesBtn: !!yesBtn,
                    noBtn: !!noBtn,
                    modalHeader: !!modalHeader,
                    modalIcon: !!modalIcon
                });
                
                if (!modal || !msg || !yesBtn || !noBtn) {
                    console.error('Required modal elements not found!');
                    alert('Error: Modal elements not found. Please refresh the page.');
                    resolve(false);
                    return;
                }

                // Handle multi-line messages and special formatting
                if (message.includes('\n')) {
                    msg.innerHTML = message.replace(/\n/g, '<br>');
                } else {
                msg.textContent = message;
                }

                // Special styling for delete confirmations
                if (message.includes('delete') || message.includes('WARNING')) {
                    modalIcon.className = 'fas fa-exclamation-triangle';
                    modalIcon.style.color = 'white';
                    modalHeader.textContent = 'Confirm Deletion';
                    modalHeader.style.color = 'white';
                    yesBtn.className = 'btn btn-danger';
                    yesBtn.textContent = 'Yes, Delete';
                    noBtn.className = 'btn btn-secondary';
                    noBtn.textContent = 'Cancel';
                    console.log('Set delete modal styling - Header text:', modalHeader.textContent);
                } else {
                    modalIcon.className = 'fas fa-question-circle';
                    modalIcon.style.color = 'white';
                    modalHeader.textContent = 'Confirm Action';
                    modalHeader.style.color = 'white';
                    yesBtn.className = 'btn btn-primary';
                    yesBtn.textContent = 'Confirm';
                    noBtn.className = 'btn btn-secondary';
                    noBtn.textContent = 'Cancel';
                    console.log('Set regular modal styling - Header text:', modalHeader.textContent);
                }

                console.log('Setting modal display to flex...');
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.setAttribute('aria-hidden', 'false');
                
                // Add scale animation
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.classList.add('show');
                }
                
                console.log('Modal display set. Current style:', modal.style.display);
                console.log('Modal computed style:', window.getComputedStyle(modal).display);
                console.log('Modal visibility:', window.getComputedStyle(modal).visibility);
                console.log('Modal opacity:', window.getComputedStyle(modal).opacity);

                const cleanup = () => {
                    yesBtn.removeEventListener('click', onYes);
                    noBtn.removeEventListener('click', onNo);
                    document.removeEventListener('keydown', onKey);
                    
                    // Remove scale animation
                    const modalContent = modal.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.classList.remove('show');
                    }
                };

                const onYes = () => { cleanup(); modal.style.display = 'none'; resolve(true); };
                const onNo = () => { cleanup(); modal.style.display = 'none'; resolve(false); };
                const onKey = (e) => { if (e.key === 'Escape') onNo(); };

                yesBtn.addEventListener('click', onYes);
                noBtn.addEventListener('click', onNo);
                document.addEventListener('keydown', onKey);

                // Clicking outside modal-content closes as Cancel
                modal.addEventListener('click', function onBackdrop(ev) {
                    if (ev.target === modal) {
                        modal.removeEventListener('click', onBackdrop);
                        onNo();
                    }
                });
            });
        }

        function closeConfirmModal(result) {
            // Helper for X button; default to cancel if undefined
            const modal = document.getElementById('confirmModal');
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.remove('show');
            }
            modal.style.display = 'none';
        }
        
        // Function to show user password (this would need to be implemented with proper security)
        function showPassword(userId) {
            // For security reasons, we can't show the actual password since only the hash is stored
            // Display a clear message to the user
            showAlert('The system only stores password hashes for security, not actual passwords. Original passwords cannot be retrieved. You can reset the user\'s password if needed.', 'info');
            
            // Update the UI to make it clearer
            const passwordPlaceholder = document.querySelector('.password-placeholder');
            if (passwordPlaceholder) {
                passwordPlaceholder.innerHTML = '<em>Not retrievable - only password hash is stored</em>';
            }
        }
        
        // Function to reset user password (placeholder for future implementation)
        function resetUserPassword(userId) {
            showAlert('Password reset functionality will be implemented soon. For now, users can use the "Forgot Password" feature on the login page.', 'info');
        }
        
        // Custom loading functions
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
    </script>

    <style>
        /* Enhanced confirmation modal styles */
        #confirmModal .modal-content {
            max-width: 600px;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        #confirmModal .modal-content.show {
            transform: scale(1);
        }
        
        #confirmModal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            padding: 2rem 2rem 1.5rem 2rem;
            border: none;
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        #confirmModal .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
            z-index: -1;
        }
        
        #confirmModal .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: white !important;
            z-index: 1;
            position: relative;
            width: 100%;
            text-align: center;
        }
        
        #confirmModal .modal-header h3 i {
            font-size: 1.75rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            color: white !important;
        }
        
        #confirmModal .modal-header h3 span {
            color: white !important;
        }
        
        #confirmModal .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }
        
        #confirmModal .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
        }
        
        #confirmModal .modal-body {
            padding: 2rem;
            background: white;
        }
        
        #confirmModalMessage {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
            color: #2d3748;
            font-weight: 500;
            text-align: center;
        }
        
        #confirmModal .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        #confirmModal .btn {
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            min-width: 120px;
            position: relative;
            overflow: hidden;
        }
        
        #confirmModal .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        #confirmModal .btn:hover::before {
            left: 100%;
        }
        
        #confirmModal .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(238, 90, 82, 0.4);
        }
        
        #confirmModal .btn-danger:hover {
            background: linear-gradient(135deg, #ff5252 0%, #e53e3e 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(238, 90, 82, 0.6);
        }
        
        #confirmModal .btn-secondary {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        #confirmModal .btn-secondary:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.6);
        }
        
        #confirmModal .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        #confirmModal .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.6);
        }
        
        /* Count boxes at the very top */
        .admin-main { padding-top: 1rem; }
        .user-management-header { 
            margin-top: 0; 
            margin-bottom: 2rem;
            padding-top: 1rem;
        }
        .main-content { padding-top: 0; }
        .tabs { margin-top: 1rem; }

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
            background: white !important;
            border-radius: 12px !important;
            padding: 1.5rem !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #e5e7eb !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            color: inherit !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            min-height: 80px !important;
        }

        .admin-section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
            text-decoration: none;
            color: inherit;
        }

        .admin-section-card .card-icon {
            width: 45px !important;
            height: 45px !important;
            border-radius: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
            color: white !important;
            background: linear-gradient(135deg, #667eea, #5a6fd8) !important;
            flex-shrink: 0 !important;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
        }

        .admin-section-card .card-content {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        /* Force horizontal layout with higher specificity */
        .admin-section-card.clickable-card {
            display: flex !important;
            align-items: center !important;
            flex-direction: row !important;
            min-height: 80px !important;
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
                min-height: 70px;
            }
            
            .admin-section-card .card-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
        }

        /* Override any external CSS for navigation cards - Force horizontal layout */
        .admin-cards-grid .admin-section-card,
        .admin-section-card.clickable-card,
        a.admin-section-card {
            display: flex !important;
            align-items: center !important;
            flex-direction: row !important;
            min-height: 80px !important;
            gap: 1rem !important;
            width: 100% !important;
        }

        .admin-cards-grid .admin-section-card .card-icon,
        .admin-section-card .card-icon,
        a.admin-section-card .card-icon {
            width: 45px !important;
            height: 45px !important;
            flex-shrink: 0 !important;
            order: 1 !important;
            margin-right: 1rem !important;
        }

        .admin-cards-grid .admin-section-card .card-content,
        .admin-section-card .card-content,
        a.admin-section-card .card-content {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            order: 2 !important;
            text-align: left !important;
        }

        /* Quick Switch Shortcuts */
        .quick-switch {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .quick-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            border: none;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .quick-switch-btn .count-badge {
            background: white;
            color: #111827;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }
        .quick-switch-btn.pending { background: #fff7ed; color: #c2410c; }
        .quick-switch-btn.approved { background: #ecfdf5; color: #047857; }
        .quick-switch-btn.rejected { background: #fef2f2; color: #b91c1c; }
        .quick-switch-btn:hover { filter: brightness(0.98); transform: translateY(-1px); }

        /* Credentials box styling */
        .credentials-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .credential-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .credential-item:last-child {
            margin-bottom: 0;
        }
        
        .password-placeholder {
            font-family: monospace;
            font-size: 1.1rem;
            color: #64748b;
            margin-right: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Custom Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .loading-spinner {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
        }
        
        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }
        
        .spinner-ring:nth-child(2) {
            border-top-color: #10b981;
            animation-delay: 0.5s;
        }
        
        .spinner-ring:nth-child(3) {
            border-top-color: #f59e0b;
            animation-delay: 1s;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .loading-text p {
            margin: 0;
            opacity: 0.8;
            font-size: 1rem;
        }
        
        /* Animation for user card removal */
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }

        /* Tab section headers */
        .tab-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 0.25rem 0 0.75rem 0;
        }
        .tab-header h2 {
            margin: 0;
            font-size: 1.125rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tab-header-count {
            background: #e5e7eb;
            color: #111827;
            border-radius: 999px;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            font-weight: 700;
        }
        .tab-header-actions {
            display: flex;
            gap: 0.5rem;
        }
        .tab-header .btn.btn-secondary {
            padding: 0.4rem 0.75rem;
        }

        /* Improve Approved/Rejected card clarity */
        .approved-user .user-card-header { border-bottom: 1px dashed rgba(16,185,129,0.35); }
        .rejected-user .user-card-header { border-bottom: 1px dashed rgba(239,68,68,0.35); }
        .approved-user .approved-badge { box-shadow: 0 0 0 2px rgba(16,185,129,0.15) inset; }
        .rejected-user .rejected-badge { box-shadow: 0 0 0 2px rgba(239,68,68,0.15) inset; }

        /* Compact the gaps slightly so content appears higher */
        .header-stats { margin-bottom: 1rem; }
        .enhanced-tabs { margin-top: 0.5rem; }
        .main-content { padding-top: 1rem; }

        .tabs {
            margin-top: 2rem;
        }

        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .tab-button {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button:hover {
            color: var(--primary-color);
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .modal {
            display: none !important;
            position: fixed !important;
            z-index: 9999 !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            align-items: center !important;
            justify-content: center !important;
            visibility: hidden !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease, visibility 0.3s ease !important;
        }
        
        .modal[style*="flex"] {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Force modal to be visible when display is flex */
        .modal[style*="display: flex"] {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0.25rem;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .status-badge.status-teacher {
            background-color: var(--primary-color);
            color: white;
        }

        .status-badge.status-student {
            background-color: var(--info-color);
            color: white;
        }

        .status-badge.status-admin {
            background-color: var(--warning-color);
            color: white;
        }

        /* Admin Layout Styles */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: #f8fafc;
        }

        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .admin-sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-nav {
            padding: 2rem 0;
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
            margin-left: 280px;
            padding: 1rem;
            transition: margin-left 0.3s ease;
        }

        .admin-main.sidebar-collapsed {
            margin-left: 0;
        }



        .main-header {
            margin-bottom: 2rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            padding: 1rem;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        /* User Management Header Styles */
        .user-management-header {
            margin-bottom: 2rem;
        }

        .header-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            max-width: 100%;
        }

        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
            min-height: 120px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-card.pending {
            border-left-color: #f59e0b;
        }

        .stat-card.approved {
            border-left-color: #10b981;
        }

        .stat-card.rejected {
            border-left-color: #ef4444;
        }

        .stat-card.total {
            border-left-color: #6366f1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .stat-card.pending .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-card.approved .stat-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-card.rejected .stat-icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stat-card.total .stat-icon {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            color: #1f2937;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-info h3 {
            transform: scale(1.05);
        }

        .stat-info p {
            margin: 0.5rem 0 0 0;
            color: #6b7280;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-info p {
            color: #374151;
        }

        /* Management Controls - Enhanced for Full Width Usage */
        .management-controls {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 2rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-top: 1px solid #e2e8f0;
            margin-top: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .search-bar {
            flex: 1.5;
            position: relative;
            min-width: 0;
        }

        .search-bar i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.4rem;
            z-index: 1;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .search-input:focus + .search-bar i,
        .search-bar:focus-within i {
            color: #4f46e5;
            transform: translateY(-50%) scale(1.1);
        }

        .search-input {
            width: 100%;
            padding: 1.5rem 1.5rem 1.5rem 4rem;
            border: 3px solid #667eea;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            transition: all 0.3s ease;
            box-sizing: border-box;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            color: #1f2937;
        }

        .search-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.2), 0 12px 35px rgba(102, 126, 234, 0.3);
            background: white;
            transform: translateY(-3px);
        }

        .search-input::placeholder {
            color: #6b7280;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .filter-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-select {
            padding: 1.5rem 1.5rem;
            border: 3px solid #667eea;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            color: #1f2937;
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.2), 0 12px 35px rgba(102, 126, 234, 0.3);
            background: white;
            transform: translateY(-3px);
        }

        /* Enhanced Tabs */
        .enhanced-tabs {
            margin-bottom: 2rem;
        }
        
        .enhanced-tabs .tab-buttons {
            display: flex;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 0.75rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            gap: 0.75rem;
        }

        .enhanced-tabs .tab-button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border: none;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1rem;
            color: #6b7280;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .enhanced-tabs .tab-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .enhanced-tabs .tab-button:hover::before {
            left: 100%;
        }

        .enhanced-tabs .tab-button:hover {
            background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #374151;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .enhanced-tabs .tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .enhanced-tabs .tab-button i {
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .enhanced-tabs .tab-button:hover i {
            transform: scale(1.1);
        }

        .enhanced-tabs .tab-button.active i {
            transform: scale(1.1);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .tab-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .enhanced-tabs .tab-button.active .tab-count {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Users table layout */
        .users-table .cell-name { min-width: 220px; }
        
        .users-table .cell-id { color: var(--text-secondary); }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .user-card.pending-user {
            border-left: 4px solid #f59e0b;
        }

        .user-card.approved-user {
            border-left: 4px solid #10b981;
        }

        .user-card.rejected-user {
            border-left: 4px solid #ef4444;
        }

        .user-card-header {
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
        }

        .user-id {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0 0 0.25rem 0;
        }

        

        .user-status {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .pending-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .approved-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .rejected-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .user-card-body {
            padding: 0 1.5rem 1rem 1.5rem;
        }

        .user-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .user-detail i {
            color: var(--primary-color);
            width: 16px;
        }

        .user-card-actions {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .approve-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        /* Table Container and Table Styles - FORCE FULL WIDTH USAGE */
        .table-container {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 0 !important;
            padding: 0 !important;
        }

        .table, .users-table {
            width: 95% !important;
            max-width: 95% !important;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            table-layout: auto !important;
            margin: 0 auto !important;
            padding: 0 !important;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table th, .users-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.25rem 1rem !important;
            text-align: center;
            font-weight: 700;
            color: white;
            border: none;
            white-space: nowrap;
            min-width: 0 !important;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .table th:first-child, .users-table th:first-child {
            border-top-left-radius: 12px;
        }

        .table th:last-child, .users-table th:last-child {
            border-top-right-radius: 12px;
        }

        .table td, .users-table td {
            padding: 1.25rem 1rem !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            word-wrap: break-word;
            min-width: 0 !important;
            background: white;
            transition: all 0.3s ease;
            color: #1f2937 !important;
            font-weight: 500 !important;
            font-size: 1.1rem !important;
        }

        .table tbody tr, .users-table tbody tr {
            transition: all 0.3s ease;
            position: relative;
        }

        .table tbody tr:hover, .users-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .table tbody tr:last-child td, .users-table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        /* FORCE COLUMNS TO USE AVAILABLE SPACE */
        .table th:nth-child(1), .table td:nth-child(1),
        .users-table th:nth-child(1), .users-table td:nth-child(1) { 
            width: auto !important; 
            min-width: 200px !important;
        } /* User */
        
        .table th:nth-child(2), .table td:nth-child(2),
        .users-table th:nth-child(2), .users-table td:nth-child(2) { 
            width: auto !important; 
            min-width: 120px !important;
        } /* Role */
        
        
        
        .table th:nth-child(4), .table td:nth-child(4),
        .users-table th:nth-child(4), .users-table td:nth-child(4) { 
            width: auto !important; 
            min-width: 180px !important;
        } /* Additional Information */
        
        .table th:nth-child(5), .table td:nth-child(5),
        .users-table th:nth-child(5), .users-table td:nth-child(5) { 
            width: auto !important; 
            min-width: 150px !important;
        } /* Time and Date of Registration */
        
        .table th:nth-child(6), .table td:nth-child(6),
        .users-table th:nth-child(6), .users-table td:nth-child(6) { 
            width: auto !important; 
            min-width: 200px !important;
        } /* Actions */

        /* Enhanced Status Badge Styles */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .status-badge:hover::before {
            left: 100%;
        }

        .status-badge.status-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .status-badge.status-teacher {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .status-badge.status-student {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        /* Enhanced Cell Styling */
        .cell-name {
            font-weight: 700 !important;
            color: #111827 !important;
        }

        .cell-name strong {
            font-size: 1.3rem !important;
            color: #111827 !important;
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 700 !important;
        }

        .cell-id {
            color: #4b5563 !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
        }

        /* Additional Information and Date Column Styling */
        .users-table td:nth-child(3),
        .users-table td:nth-child(4) {
            color: #374151 !important;
            font-weight: 500 !important;
            font-size: 1.1rem !important;
        }

        /* Center the Additional Information column */
        .users-table th:nth-child(3),
        .users-table td:nth-child(3) {
            text-align: center !important;
        }

        /* Center the Time and Date of Registration column */
        .users-table th:nth-child(4),
        .users-table td:nth-child(4) {
            text-align: center !important;
        }

        /* Action Column Styling */
        .users-table td:nth-child(5) {
            color: #111827 !important;
        }

        /* Enhanced Management Controls - Use Full Width */
        .management-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 2rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-top: 1px solid #e2e8f0;
            margin-top: 0;
            width: 100%;
        }

        .search-bar {
            flex: 2;
            position: relative;
            min-width: 0;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .filter-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex: 0.8;
            justify-content: flex-end;
        }

        .filter-select {
            padding: 1.5rem 1.5rem;
            border: 3px solid #667eea;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            color: #1f2937;
            min-width: 150px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.2), 0 12px 35px rgba(102, 126, 234, 0.3);
            background: white;
            transform: translateY(-3px);
        }

        /* Tab Content - FORCE FULL WIDTH */
        .tab-content {
            width: 100% !important;
            max-width: 100% !important;
            margin-top: 1rem;
            padding: 0 !important;
        }

        .tab-content.active {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Enhanced Tabs - Use Full Width */
        .enhanced-tabs {
            width: 100% !important;
            max-width: 100% !important;
        }

        .tab-buttons {
            width: 100% !important;
            max-width: 100% !important;
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        /* Empty State - Use Full Width */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            width: 100%;
        }

        .empty-state .empty-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .approve-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .reject-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .reject-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .edit-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .edit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .suspend-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .suspend-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        .delete-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .view-btn {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .view-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .empty-icon i {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .empty-state h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            color: #6b7280;
            font-weight: 500;
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        /* Special styling for "No Results Found" state */
        .empty-state.no-results {
            background: linear-gradient(145deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
        }

        .empty-state.no-results .empty-icon {
            color: #f59e0b;
        }

        .empty-state.no-results h3 {
            color: #92400e;
        }

        .empty-state.no-results p {
            color: #b45309;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .management-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar {
                min-width: auto;
            }

            .filter-controls {
                justify-content: space-between;
            }

            .users-grid {
                grid-template-columns: 1fr;
            }

            .enhanced-tabs .tab-button {
                flex-direction: column;
                gap: 0.25rem;
                padding: 0.75rem 0.5rem;
            }

            .enhanced-tabs .tab-button span:first-of-type {
                display: none;
            }

            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }

            .admin-sidebar {
                position: fixed;
                left: -280px;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .admin-sidebar.open {
                left: 0;
            }
        }

        @media (max-width: 480px) {
            .header-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .user-card-actions {
                flex-direction: column;
            }

            .action-btn {
                justify-content: center;
            }
        }

        /* FINAL OVERRIDE - Force horizontal layout for all navigation cards */
        .admin-sections .admin-cards-grid a[class*="admin-section-card"],
        .admin-sections .admin-cards-grid .admin-section-card,
        .admin-sections .admin-cards-grid .clickable-card {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            min-height: 80px !important;
            max-height: 80px !important;
            padding: 1rem 1.5rem !important;
        }
        
        .admin-sections .admin-cards-grid a[class*="admin-section-card"] .card-icon,
        .admin-sections .admin-cards-grid .admin-section-card .card-icon,
        .admin-sections .admin-cards-grid .clickable-card .card-icon {
            order: 1 !important;
            flex-shrink: 0 !important;
            width: 45px !important;
            height: 45px !important;
            margin-right: 1rem !important;
            font-size: 1.25rem !important;
        }
        
        .admin-sections .admin-cards-grid a[class*="admin-section-card"] .card-content,
        .admin-sections .admin-cards-grid .admin-section-card .card-content,
        .admin-sections .admin-cards-grid .clickable-card .card-content {
            order: 2 !important;
            flex: 1 !important;
            text-align: left !important;
            justify-content: center !important;
            min-height: auto !important;
            padding: 0 !important;
        }
        
        .admin-sections .admin-cards-grid a[class*="admin-section-card"] .card-content h3,
        .admin-sections .admin-cards-grid .admin-section-card .card-content h3,
        .admin-sections .admin-cards-grid .clickable-card .card-content h3 {
            margin-bottom: 0.25rem !important;
            font-size: 1.1rem !important;
        }
        
        .admin-sections .admin-cards-grid a[class*="admin-section-card"] .card-content p,
        .admin-sections .admin-cards-grid .admin-section-card .card-content p,
        .admin-sections .admin-cards-grid .clickable-card .card-content p {
            margin-bottom: 0 !important;
            font-size: 0.85rem !important;
            line-height: 1.3 !important;
        }

        /* ULTIMATE OVERRIDE - Target external CSS directly */
        body .admin-section-card.clickable-card {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            min-height: 80px !important;
            max-height: 80px !important;
            padding: 1rem 1.5rem !important;
            gap: 1rem !important;
        }

        body .admin-section-card.clickable-card .card-icon {
            width: 45px !important;
            height: 45px !important;
            flex-shrink: 0 !important;
            font-size: 1.25rem !important;
        }

        body .admin-section-card.clickable-card .card-content {
            flex: 1 !important;
            justify-content: center !important;
            min-height: auto !important;
            padding: 0 !important;
        }

        body .admin-section-card.clickable-card .card-content h3 {
            margin-bottom: 0.25rem !important;
            font-size: 1.1rem !important;
        }

        body .admin-section-card.clickable-card .card-content p {
            margin-bottom: 0 !important;
            font-size: 0.85rem !important;
            line-height: 1.3 !important;
        }
    </style>

    <?php include __DIR__ . '/partials/modals.php'; ?>
    <script>
        

        // Enhanced User Management JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            initializeUserManagement();
        });

        function initializeUserManagement() {
            // Initialize tab functionality
            initializeTabs();
            
            // Initialize search and filter functionality
            initializeSearchAndFilter();
            
            // Initialize tooltips and animations
            initializeTooltips();
        }

        function initializeTabs() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetTab = button.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        content.style.display = 'none';
                    });
                    
                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    const targetContent = document.getElementById(targetTab);
                    if (targetContent) {
                        targetContent.classList.add('active');
                        targetContent.style.display = 'block';
                        
                        // Trigger animations for the cards
                        const cards = targetContent.querySelectorAll('.user-card');
                        cards.forEach((card, index) => {
                            card.style.animation = `fadeIn 0.3s ease ${index * 0.1}s both`;
                        });
                    }
                });
            });
            
            // Initialize first tab
            const firstTab = document.querySelector('.tab-button.active');
            if (firstTab) {
                const targetTab = firstTab.getAttribute('data-tab');
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
            }
        }

        function initializeSearchAndFilter() {
            const searchInput = document.getElementById('userSearch');
            const roleFilter = document.getElementById('roleFilter');

            if (searchInput) {
                searchInput.addEventListener('input', debounce(filterUsers, 300));
            }

            if (roleFilter) {
                roleFilter.addEventListener('change', filterUsers);
            }
        }

        function filterUsers() {
            const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
            const roleFilter = document.getElementById('roleFilter')?.value || '';
            
            // Get all table rows from all tabs
            const allRows = document.querySelectorAll('table.users-table tbody tr');
            
            allRows.forEach(row => {
                const userName = row.querySelector('.cell-name strong')?.textContent.toLowerCase() || '';
                const userId = row.querySelector('.cell-id')?.textContent.toLowerCase() || '';
                const userRole = row.getAttribute('data-role') || '';
                const userStatus = row.getAttribute('data-status') || '';
                const additionalInfo = row.cells[2]?.textContent.toLowerCase() || ''; // Additional Information column
                
                // Check if row matches search term
                const matchesSearch = !searchTerm || 
                    userName.includes(searchTerm) || 
                    userId.includes(searchTerm) ||
                    additionalInfo.includes(searchTerm);
                    
                // Check if row matches role filter
                const matchesRole = !roleFilter || userRole === roleFilter;
                
                // Show/hide row based on filters
                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                    row.style.animation = 'fadeIn 0.3s ease';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update empty state visibility for each tab
            updateEmptyStates();
        }
        
        function updateEmptyStates() {
            const tabs = ['pending', 'approved', 'rejected'];
            const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
            
            tabs.forEach(tabId => {
                const tabContent = document.getElementById(tabId);
                if (!tabContent) return;
                
                const table = tabContent.querySelector('table.users-table');
                const emptyState = tabContent.querySelector('.empty-state');
                
                if (table && emptyState) {
                    const visibleRows = table.querySelectorAll('tbody tr[style=""], tbody tr:not([style*="display: none"])');
                    const hasVisibleRows = visibleRows.length > 0;
                    
                    if (hasVisibleRows) {
                        emptyState.style.display = 'none';
                    } else {
                        emptyState.style.display = 'block';
                        
                        // Update empty state message based on search
                        if (searchTerm) {
                            const emptyIcon = emptyState.querySelector('.empty-icon i');
                            const emptyTitle = emptyState.querySelector('h3');
                            const emptyMessage = emptyState.querySelector('p');
                            
                            // Add no-results class for special styling
                            emptyState.classList.add('no-results');
                            
                            if (emptyIcon) emptyIcon.className = 'fas fa-search';
                            if (emptyTitle) emptyTitle.textContent = 'No Results Found';
                            if (emptyMessage) emptyMessage.textContent = `No users found matching "${searchTerm}". Try adjusting your search terms or filters.`;
                        } else {
                            // Remove no-results class
                            emptyState.classList.remove('no-results');
                            // Reset to original empty state messages
                            const emptyIcon = emptyState.querySelector('.empty-icon i');
                            const emptyTitle = emptyState.querySelector('h3');
                            const emptyMessage = emptyState.querySelector('p');
                            
                            if (tabId === 'pending') {
                                if (emptyIcon) emptyIcon.className = 'fas fa-check-circle';
                                if (emptyTitle) emptyTitle.textContent = 'No Pending Approvals';
                                if (emptyMessage) emptyMessage.textContent = 'All user accounts have been reviewed. New registrations will appear here for your approval.';
                            } else if (tabId === 'approved') {
                                if (emptyIcon) emptyIcon.className = 'fas fa-users';
                                if (emptyTitle) emptyTitle.textContent = 'No Approved Users';
                                if (emptyMessage) emptyMessage.textContent = 'Approved users will appear here once you approve pending registrations.';
                            } else if (tabId === 'rejected') {
                                if (emptyIcon) emptyIcon.className = 'fas fa-times-circle';
                                if (emptyTitle) emptyTitle.textContent = 'No Rejected Users';
                                if (emptyMessage) emptyMessage.textContent = 'Rejected users will appear here once you reject pending registrations.';
                            }
                        }
                    }
                }
            });
        }

        function initializeTooltips() {
            // Add hover effects and tooltips
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        }

        function switchTab(tabName) {
            const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (tabButton) {
                tabButton.click();
            }
        }

        function refreshUserData() {
            // Show loading state
            showNotification('Refreshing user data...', 'info');
            
            // Reload the page to get fresh data
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // This function is now handled by the main updateUserStatus function above
        // Keeping this for backward compatibility but it's not used

        function editUser(userId) {
            // Implement edit user functionality
            showNotification('Edit user functionality coming soon!', 'info');
        }


        function deleteUser(userId) {
            console.log('deleteUser function called with userId:', userId);
            
            // Check if modalConfirm function exists
            if (typeof modalConfirm !== 'function') {
                console.error('modalConfirm function is not defined!');
                alert('Error: Modal confirmation function not available. Please refresh the page.');
                return;
            }
            
            // Enhanced confirmation message with more details
            const confirmMessage = `⚠️ WARNING: You are about to permanently delete this user account.\n\nAre you sure you want to proceed?`;
            
            console.log('Calling modalConfirm with message:', confirmMessage);
            
            modalConfirm(confirmMessage).then(confirmed => {
                console.log('modalConfirm resolved with:', confirmed);
                if (!confirmed) {
                    console.log('User deletion cancelled by user');
                    return;
                }

                proceedWithDeletion(userId);
            }).catch(error => {
                console.error('Error in modalConfirm:', error);
                alert('Error with confirmation modal. Please try again.');
            });
        }
        
        function proceedWithDeletion(userId) {
                showLoading(); // Show custom loading overlay

            console.log(`Proceeding with deletion of user: ${userId}`);

                // Make actual API call
                fetch('delete-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                body: `user_id=${encodeURIComponent(userId)}`
            })
            .then(response => {
                console.log('Delete response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
                .then(result => {
                    hideLoading(); // Hide loading overlay
                console.log('Delete result:', result);
                    
                    if (result.success) {
                        showAlert(result.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                    showAlert(result.message || 'Failed to delete user', 'error');
                    }
                })
                .catch(error => {
                    hideLoading(); // Hide loading overlay on error
                    console.error('Error deleting user:', error);
                
                let errorMessage = 'An error occurred while deleting user';
                if (error.message.includes('HTTP error')) {
                    errorMessage = 'Server error occurred. Please try again.';
                } else if (error.message.includes('fetch')) {
                    errorMessage = 'Network error. Please check your connection.';
                }
                
                showAlert(errorMessage, 'error');
            });
        }

        function showNotification(message, type = 'info') {
            try { showNotice(message, type); return; } catch(e) {}
            // Fallback inline notification if shared modal JS is unavailable
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'}; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 10000; display: flex; align-items: center; gap: 0.5rem; animation: slideIn 0.3s ease;`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => { notification.remove(); }, 300);
            }, 3000);
        }

        // Backward-compatible aliases leveraging shared modal helpers
        // All confirmations now use the modal system - no browser popups

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }


        // Add CSS animations and improved button styling
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-10px); }
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            /* Modern Message Modal Styling */
            #messageModal .modal-content {
                max-width: 500px;
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                border-radius: 20px;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                overflow: hidden;
                position: relative;
                transform: scale(0.9);
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            
            #messageModal .modal-content.show {
                transform: scale(1);
            }
            
            #messageModal .modal-header {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 2rem 2rem 1.5rem 2rem;
                border: none;
                position: relative;
                z-index: 1;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
            
            #messageModal .modal-header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(40, 167, 69, 0.8) 0%, rgba(32, 201, 151, 0.8) 100%);
                z-index: -1;
            }
            
            #messageModal .modal-header h3 {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 700;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                color: white !important;
                z-index: 1;
                position: relative;
                width: 100%;
                text-align: center;
            }
            
            #messageModal .modal-header h3 i {
                font-size: 1.75rem;
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
                color: white !important;
            }
            
            #messageModal .modal-header h3 span {
                color: white !important;
            }
            
            #messageModal .modal-close {
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid rgba(255, 255, 255, 0.3);
                color: white;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
                position: absolute;
                top: 1rem;
                right: 1rem;
                z-index: 2;
            }
            
            #messageModal .modal-close:hover {
                background: rgba(255, 255, 255, 0.3);
                border-color: rgba(255, 255, 255, 0.5);
                transform: scale(1.1);
            }
            
            #messageModal .modal-body {
                padding: 2rem;
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            }
            
            #messageModalContent {
                font-size: 1.1rem;
                line-height: 1.6;
                color: #2c3e50;
                margin-bottom: 2rem;
                text-align: center;
            }
            
            #messageModal .form-actions {
                display: flex;
                justify-content: center;
                gap: 1rem;
                margin-top: 1.5rem;
            }
            
            #messageModal .btn {
                padding: 12px 30px;
                border: none;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
                min-width: 120px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            #messageModal .btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            #messageModal .btn:hover::before {
                left: 100%;
            }
            
            #messageModal .btn-primary {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            }
            
            #messageModal .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            }
            
            #messageModal .btn-primary:active {
                transform: translateY(0);
            }
            
            /* Error message styling */
            #messageModal.error .modal-header {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            }
            
            #messageModal.error .modal-header::before {
                background: linear-gradient(135deg, rgba(220, 53, 69, 0.8) 0%, rgba(200, 35, 51, 0.8) 100%);
            }
            
            #messageModal.error .btn-primary {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            }
            
            #messageModal.error .btn-primary:hover {
                box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            }
            
            /* Warning message styling */
            #messageModal.warning .modal-header {
                background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            }
            
            #messageModal.warning .modal-header::before {
                background: linear-gradient(135deg, rgba(255, 193, 7, 0.8) 0%, rgba(224, 168, 0, 0.8) 100%);
            }
            
            #messageModal.warning .btn-primary {
                background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
                color: #212529;
                box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            }
            
            #messageModal.warning .btn-primary:hover {
                box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
            }
            
            /* Info message styling */
            #messageModal.info .modal-header {
                background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            }
            
            #messageModal.info .modal-header::before {
                background: linear-gradient(135deg, rgba(23, 162, 184, 0.8) 0%, rgba(19, 132, 150, 0.8) 100%);
            }
            
            #messageModal.info .btn-primary {
                background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
                box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
            }
            
            #messageModal.info .btn-primary:hover {
                box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
            }
            
            /* Enhanced Table Action Buttons */
            .users-table .btn {
                padding: 0.75rem 1.25rem;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 1rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                position: relative;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .users-table .btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .users-table .btn:hover::before {
                left: 100%;
            }

            .users-table .btn-success {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
            }

            .users-table .btn-success:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            }

            .users-table .btn-danger {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
            }

            .users-table .btn-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
