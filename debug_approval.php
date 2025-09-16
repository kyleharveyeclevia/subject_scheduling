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
    <title>Debug Approval System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .result { margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .success { border-left-color: #28a745; background: #d4edda; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Debug Approval System</h1>
    
    <div class="debug-section">
        <h2>Session Information</h2>
        <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Not set'); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? 'Not set'); ?></p>
        <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
    </div>
    
    <div class="debug-section">
        <h2>User Statistics</h2>
        <p><strong>Total Users:</strong> <?php echo count($all_users); ?></p>
        <p><strong>Pending Users:</strong> <?php echo count($pending_users); ?></p>
        <p><strong>Approved Users:</strong> <?php echo count($approved_users); ?></p>
        <p><strong>Rejected Users:</strong> <?php echo count($rejected_users); ?></p>
    </div>
    
    <div class="debug-section">
        <h2>Pending Users</h2>
        <?php if (!empty($pending_users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Additional Info</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $pending_user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pending_user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($pending_user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($pending_user['role']); ?></td>
                            <td><?php echo htmlspecialchars($pending_user['additional_info'] ?? ''); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($pending_user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-success" onclick="testApproval('<?php echo $pending_user['user_id']; ?>')">Test Approve</button>
                                <button class="btn btn-danger" onclick="testRejection('<?php echo $pending_user['user_id']; ?>')">Test Reject</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending users found.</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>Test Results</h2>
        <div id="testResults"></div>
    </div>
    
    <div class="debug-section">
        <h2>Quick Actions</h2>
        <button class="btn btn-info" onclick="refreshData()">Refresh Data</button>
        <button class="btn btn-info" onclick="testConnection()">Test Database Connection</button>
        <button class="btn btn-info" onclick="testUserClass()">Test User Class</button>
    </div>

    <script>
        async function testApproval(userId) {
            const resultDiv = document.getElementById('testResults');
            resultDiv.innerHTML = `<div class="result">Testing approval for user: ${userId}...</div>`;
            
            try {
                const response = await fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${encodeURIComponent(userId)}&status=approved`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `<div class="result success">✅ Approval successful! Message: ${result.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="result error">❌ Approval failed! Message: ${result.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error">❌ Error: ${error.message}</div>`;
            }
        }
        
        async function testRejection(userId) {
            const resultDiv = document.getElementById('testResults');
            resultDiv.innerHTML = `<div class="result">Testing rejection for user: ${userId}...</div>`;
            
            try {
                const response = await fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${encodeURIComponent(userId)}&status=rejected`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `<div class="result success">✅ Rejection successful! Message: ${result.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="result error">❌ Rejection failed! Message: ${result.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error">❌ Error: ${error.message}</div>`;
            }
        }
        
        function refreshData() {
            window.location.reload();
        }
        
        async function testConnection() {
            const resultDiv = document.getElementById('testResults');
            resultDiv.innerHTML = `<div class="result">Testing database connection...</div>`;
            
            try {
                const response = await fetch('../test_approval_simple.php');
                const text = await response.text();
                resultDiv.innerHTML = `<div class="result success">✅ Database connection test completed. Check the response for details.</div>`;
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error">❌ Connection test failed: ${error.message}</div>`;
            }
        }
        
        async function testUserClass() {
            const resultDiv = document.getElementById('testResults');
            resultDiv.innerHTML = `<div class="result">Testing User class functionality...</div>`;
            
            try {
                const response = await fetch('../test_approval_comprehensive.php');
                const text = await response.text();
                resultDiv.innerHTML = `<div class="result success">✅ User class test completed. Check the response for details.</div>`;
            } catch (error) {
                resultDiv.innerHTML = `<div class="result error">❌ User class test failed: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
