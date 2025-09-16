<?php
session_start();
require_once 'classes/User.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    try {
        $user = new User();
        $result = $user->verifyEmail($token);
        
        if ($result['success']) {
            $success = true;
            $message = 'Email verified successfully! You can now log in to your account.';
        } else {
            $message = $result['message'];
        }
    } catch (Exception $e) {
        $message = 'Verification failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Scheduling System - Email Verification</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-calendar-alt"></i> Subject Scheduling
                </a>
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1>
                        <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        Email Verification
                    </h1>
                    <p><?php echo $success ? 'Verification Complete' : 'Verification Failed'; ?></p>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?php echo $success ? 'success' : 'error'; ?>">
                        <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                    <div class="text-center" style="margin-top: 2rem;">
                        <?php if ($success): ?>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login Now
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary" style="margin-right: 0.5rem;">
                                <i class="fas fa-user-plus"></i> Register Again
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Go Home
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
