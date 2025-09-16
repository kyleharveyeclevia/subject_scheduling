<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

// Validate input
if (empty($login_id) || empty($password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please enter both ID and password']);
    exit();
}

header('Content-Type: application/json');

try {
    // Hidden admin login check (bypass database) so admin can always access
    if ($login_id === '22120091' && $password === '@Admin1899') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = '22120091';
        $_SESSION['full_name'] = 'System Administrator';
        $_SESSION['email'] = 'admin@gmail.com';
        $_SESSION['role'] = 'admin';
        $_SESSION['status'] = 'approved';
        $_SESSION['admin_id'] = '22120091';

        // Use absolute paths for redirects
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/nls2/";
        $redirect_url = $base_url . 'dashboards/admin/dashboard.php';
        
        // Log the redirect URL for debugging
        error_log("Admin bypass login redirect URL: " . $redirect_url);
        
        // Force the browser to clear cache for this redirect
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'user_id' => '22120091',
                'full_name' => 'System Administrator',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
                'status' => 'approved',
            ],
            'redirect' => $redirect_url
        ]);
        exit();
    }

    // Check if database exists first
    $testDb = new PDO('mysql:host=localhost', 'root', '');
    $testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $testDb->query("SHOW DATABASES LIKE 'subject_scheduling'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database not found. Please run the setup script first: <a href="setup.php">setup.php</a>'
        ]);
        exit();
    }
    
    $user = new User();
    $result = $user->login($login_id, $password, $ip_address);
    
    if ($result['success']) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $result['user']['user_id'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['email'] = $result['user']['email'] ?? '';
        $_SESSION['role'] = $result['user']['role'];
        $_SESSION['status'] = $result['user']['status'];
        
        // Store role-specific data
        switch ($result['user']['role']) {
            case 'teacher':
                $_SESSION['teacher_id'] = $result['user']['teacher_id'] ?? '';
                $_SESSION['department'] = $result['user']['department'] ?? '';
                break;
            case 'student':
                $_SESSION['student_id'] = $result['user']['student_id'] ?? '';
                $_SESSION['year_level'] = $result['user']['year_level'] ?? '';
                $_SESSION['section'] = $result['user']['section'] ?? '';
                break;
            case 'admin':
                $_SESSION['admin_id'] = $result['user']['admin_id'] ?? '';
                break;
        }
        
        // Force the browser to clear cache for this redirect
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Set absolute redirect URL based on role
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/nls2/";
        
        $dashboards = [
            'admin' => 'dashboards/admin/dashboard.php',
            'teacher' => 'dashboards/teacher/dashboard.php',
            'student' => 'dashboards/student/dashboard.php'
        ];
        
        $relative_path = $dashboards[$result['user']['role']] ?? 'index.php';
        $redirect_url = $base_url . $relative_path;
        
        $result['redirect'] = $redirect_url;
        
        // Log the redirect URL for debugging
        error_log("Regular login redirect URL for {$result['user']['role']}: " . $result['redirect']);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log the actual error for debugging
    error_log("Login error: " . $e->getMessage());
    
    // Check if it's a database connection error
    if (strpos($e->getMessage(), 'database') !== false || strpos($e->getMessage(), 'connection') !== false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection failed. Please ensure your web server and MySQL are running, then run the setup script: <a href="setup.php">setup.php</a>'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Login failed. Error: ' . $e->getMessage()
        ]);
    }
}
?>
