<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../config/database.php';

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
    <title>Debug Modal Issue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .result { margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .success { border-left-color: #28a745; background: #d4edda; }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            border-bottom: 2px solid #e5e7eb;
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        #confirmModalMessage {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            color: #374151;
        }
        
        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        
        .form-actions .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>
    <h1>Debug Modal Issue</h1>
    
    <div class="debug-section">
        <h2>Test Modal Functions</h2>
        <button class="btn btn-primary" onclick="testModalConfirm()">Test modalConfirm Function</button>
        <button class="btn btn-danger" onclick="testDeleteUser()">Test deleteUser Function</button>
        <button class="btn btn-primary" onclick="testDirectModal()">Test Direct Modal Show</button>
    </div>
    
    <div class="debug-section">
        <h2>Rejected Users (for testing delete)</h2>
        <?php if (!empty($rejected_users)): ?>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rejected_users as $rejected_user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rejected_user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($rejected_user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($rejected_user['role']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="deleteUser('<?php echo $rejected_user['user_id']; ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No rejected users found.</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>Debug Results</h2>
        <div id="debugResults"></div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3>
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

    <script>
        // Copy the modalConfirm function from manage-users.php
        function modalConfirm(message) {
            console.log('modalConfirm called with message:', message);
            
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
                    console.error('Modal elements not found!');
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
                    modalIcon.style.color = '#dc3545';
                    modalHeader.textContent = '⚠️ Confirm Deletion';
                    yesBtn.className = 'btn btn-danger';
                    yesBtn.textContent = 'Yes, Delete';
                    noBtn.className = 'btn btn-secondary';
                    noBtn.textContent = 'Cancel';
                } else {
                    modalIcon.className = 'fas fa-question-circle';
                    modalIcon.style.color = '#007bff';
                    modalHeader.textContent = 'Confirm Action';
                    yesBtn.className = 'btn btn-primary';
                    yesBtn.textContent = 'Confirm';
                    noBtn.className = 'btn btn-secondary';
                    noBtn.textContent = 'Cancel';
                }

                console.log('Showing modal...');
                modal.style.display = 'flex';

                const cleanup = () => {
                    yesBtn.removeEventListener('click', onYes);
                    noBtn.removeEventListener('click', onNo);
                    document.removeEventListener('keydown', onKey);
                };

                const onYes = () => { 
                    console.log('User clicked Yes');
                    cleanup(); 
                    modal.style.display = 'none'; 
                    resolve(true); 
                };
                const onNo = () => { 
                    console.log('User clicked No');
                    cleanup(); 
                    modal.style.display = 'none'; 
                    resolve(false); 
                };
                const onKey = (e) => { 
                    if (e.key === 'Escape') {
                        console.log('User pressed Escape');
                        onNo();
                    }
                };

                yesBtn.addEventListener('click', onYes);
                noBtn.addEventListener('click', onNo);
                document.addEventListener('keydown', onKey);

                // Clicking outside modal-content closes as Cancel
                modal.addEventListener('click', function onBackdrop(ev) {
                    if (ev.target === modal) {
                        console.log('User clicked outside modal');
                        modal.removeEventListener('click', onBackdrop);
                        onNo();
                    }
                });
            });
        }

        function closeConfirmModal(result) {
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'none';
        }

        // Copy the deleteUser function from manage-users.php
        function deleteUser(userId) {
            console.log('deleteUser called with userId:', userId);
            
            // Enhanced confirmation message with more details
            const confirmMessage = `⚠️ WARNING: You are about to permanently delete this user account.\n\nThis action will:\n• Remove all user data from the system\n• Delete associated records (teacher/student data)\n• Cannot be undone\n\nAre you absolutely sure you want to proceed?`;
            
            console.log('Calling modalConfirm...');
            modalConfirm(confirmMessage).then(confirmed => {
                console.log('modalConfirm resolved with:', confirmed);
                
                if (!confirmed) {
                    console.log('User deletion cancelled by user');
                    document.getElementById('debugResults').innerHTML = '<div class="result">User cancelled deletion</div>';
                    return;
                }

                console.log('User confirmed deletion, would proceed with API call...');
                document.getElementById('debugResults').innerHTML = '<div class="result success">User confirmed deletion! (API call would happen here)</div>';
            }).catch(error => {
                console.error('Error in modalConfirm:', error);
                document.getElementById('debugResults').innerHTML = '<div class="result error">Error in modalConfirm: ' + error.message + '</div>';
            });
        }

        async function testModalConfirm() {
            console.log('Testing modalConfirm...');
            const result = await modalConfirm('This is a test confirmation message. Do you want to proceed?');
            document.getElementById('debugResults').innerHTML = '<div class="result">Modal confirm result: ' + result + '</div>';
        }

        async function testDeleteUser() {
            console.log('Testing deleteUser...');
            deleteUser('TEST123');
        }

        function testDirectModal() {
            console.log('Testing direct modal show...');
            const modal = document.getElementById('confirmModal');
            if (modal) {
                modal.style.display = 'flex';
                document.getElementById('debugResults').innerHTML = '<div class="result">Modal shown directly</div>';
            } else {
                document.getElementById('debugResults').innerHTML = '<div class="result error">Modal element not found!</div>';
            }
        }

        // Test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, testing modal elements...');
            const modal = document.getElementById('confirmModal');
            const msg = document.getElementById('confirmModalMessage');
            const yesBtn = document.getElementById('confirmYesBtn');
            const noBtn = document.getElementById('confirmNoBtn');
            
            console.log('Modal elements on load:', {
                modal: !!modal,
                msg: !!msg,
                yesBtn: !!yesBtn,
                noBtn: !!noBtn
            });
            
            if (modal && msg && yesBtn && noBtn) {
                document.getElementById('debugResults').innerHTML = '<div class="result success">All modal elements found successfully!</div>';
            } else {
                document.getElementById('debugResults').innerHTML = '<div class="result error">Some modal elements are missing!</div>';
            }
        });
    </script>
</body>
</html>
