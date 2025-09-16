<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = new Database;
        // Ensure required tables exist so registration/login and admin views work on fresh setups
        try {
            $this->ensureCoreTablesExist();
        } catch (Exception $e) {
            error_log('Failed ensuring core tables exist: ' . $e->getMessage());
        }
    }

    // Register new user
    public function register($data) {
        // Duplicate checks for critical identifiers before creating anything
        try {
            // Email removed from registration; skip email duplicate checks

            // Check duplicate role-specific IDs across all roles
            if ($data['role'] === 'teacher' && !empty($data['teacher_id'])) {
                // Teacher duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM teachers WHERE teacher_id = :id');
                $this->db->bind(':id', $data['teacher_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Teacher. Please use a different ID number.'];
                }
                // Student duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM students WHERE student_id = :id');
                $this->db->bind(':id', $data['teacher_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Student. Please use a different ID number.'];
                }
                // Admin duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM admins WHERE admin_id = :id');
                $this->db->bind(':id', $data['teacher_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as an Admin. Please use a different ID number.'];
                }
            } elseif ($data['role'] === 'student' && !empty($data['student_id'])) {
                // Student duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM students WHERE student_id = :id');
                $this->db->bind(':id', $data['student_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Student. Please use a different ID number.'];
                }
                // Teacher duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM teachers WHERE teacher_id = :id');
                $this->db->bind(':id', $data['student_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Teacher. Please use a different ID number.'];
                }
                // Admin duplicate
                $this->db->query('SELECT COUNT(*) as cnt FROM admins WHERE admin_id = :id');
                $this->db->bind(':id', $data['student_id']);
                $row = $this->db->single();
                if ($row && intval($row['cnt']) > 0) {
                    return ['success' => false, 'message' => 'This ID number is already registered as an Admin. Please use a different ID number.'];
                }
            }
        } catch (Exception $e) {
            error_log('Duplicate check failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed due to a server error while validating your information. Please try again.'];
        }

        // Generate unique system user ID based on role
        $user_id = $this->generateUserId($data['role']);
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Debug logging
        error_log("Registration attempt for role: " . $data['role']);
        error_log("Generated user_id: " . $user_id);
        error_log("Data: " . print_r($data, true));

        try {
            // Insert into users table
            $this->db->query('INSERT INTO users (user_id, full_name, email, password_hash, role, status, email_verified) 
                             VALUES (:user_id, :full_name, :email, :password_hash, :role, :status, :email_verified)');
            
            $this->db->bind(':user_id', $user_id);
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':email', null);
            $this->db->bind(':password_hash', $password_hash);
            $this->db->bind(':role', $data['role']);
            
            // Set status to 'pending' for regular users, 'approved' for admin
            $status = ($data['role'] === 'admin') ? 'approved' : 'pending';
            $this->db->bind(':status', $status);
            $this->db->bind(':email_verified', 1);
            
            error_log("Attempting to insert user with status: " . $status);
            
            if ($this->db->execute()) {
                error_log("User inserted successfully, now inserting role-specific data");
                
                // Insert role-specific data
                switch ($data['role']) {
                    case 'teacher':
                        $this->db->query('INSERT INTO teachers (user_id, teacher_id, department) 
                                         VALUES (:user_id, :teacher_id, :department)');
                        $this->db->bind(':user_id', $user_id);
                        $this->db->bind(':teacher_id', $data['teacher_id']);
                        $this->db->bind(':department', $data['department']);
                        $this->db->execute();
                        error_log("Teacher data inserted successfully");
                        break;
                        
                    case 'student':
                        $this->db->query('INSERT INTO students (user_id, student_id, year_level, section) 
                                         VALUES (:user_id, :student_id, :year_level, :section)');
                        $this->db->bind(':user_id', $user_id);
                        $this->db->bind(':student_id', $data['student_id']);
                        $this->db->bind(':year_level', $data['year_level']);
                        $this->db->bind(':section', $data['section']);
                        $this->db->execute();
                        error_log("Student data inserted successfully");
                        break;
                        

                }
                
                $message = ($data['role'] === 'admin') 
                    ? 'Registration successful. You can now login with your credentials.' 
                    : 'Registration successful. Your account requires admin approval before you can login.';
                
                error_log("Registration completed successfully for user: " . $user_id);
                return ['success' => true, 'message' => $message, 'user_id' => $user_id];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            // Friendly duplicate key handling
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate') !== false || stripos($msg, '1062') !== false) {
                if (stripos($msg, 'teachers.teacher_id') !== false) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Teacher. Please use a different ID number.'];
                }
                if (stripos($msg, 'students.student_id') !== false) {
                    return ['success' => false, 'message' => 'This ID number is already registered as a Student. Please use a different ID number.'];
                }
                if (stripos($msg, 'admins.admin_id') !== false) {
                    return ['success' => false, 'message' => 'This ID number is already registered as an Admin. Please use a different ID number.'];
                }
            }
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }

    // Login user
    public function login($login_id, $password, $ip_address) {
        // Check if user exists by user_id first
        $this->db->query('SELECT * FROM users WHERE user_id = :login_id');
        $this->db->bind(':login_id', $login_id);
        $user = $this->db->single();
        
        // If not found by user_id, try to find by teacher_id or student_id
        if (!$user) {
            $this->db->query('SELECT u.* FROM users u 
                             LEFT JOIN teachers t ON u.user_id = t.user_id 
                             LEFT JOIN students s ON u.user_id = s.user_id 
                             WHERE t.teacher_id = :login_id OR s.student_id = :login_id');
            $this->db->bind(':login_id', $login_id);
            $user = $this->db->single();
        }

        if ($user) {
            // Check if account is approved (skip approval check for admin accounts)
            if ($user['role'] !== 'admin' && $user['status'] !== 'approved') {
                $this->logLoginAttempt($login_id, $ip_address, false);
                $message = $user['status'] === 'pending' ? 
                    'Your account is pending approval by an administrator.' : 
                    'Your account has been ' . $user['status'] . '. Please contact an administrator.';
                return ['success' => false, 'message' => $message];
            }

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                $this->logLoginAttempt($login_id, $ip_address, true);
                
                // Get role-specific data
                $role_data = $this->getRoleData($user['user_id'], $user['role']);
                
                // Make sure status is included in the returned data
                $userData = array_merge($user, $role_data);
                unset($userData['email']);
                
                return [
                    'success' => true, 
                    'message' => 'Login successful',
                    'user' => $userData
                ];
            }
        }

        $this->logLoginAttempt($login_id, $ip_address, false);
        return ['success' => false, 'message' => 'Incorrect Username or password'];
    }

    // Get role-specific data
    private function getRoleData($user_id, $role) {
        switch ($role) {
            case 'teacher':
                $this->db->query('SELECT * FROM teachers WHERE user_id = :user_id');
                break;
            case 'student':
                $this->db->query('SELECT * FROM students WHERE user_id = :user_id');
                break;

            default:
                return [];
        }
        
        $this->db->bind(':user_id', $user_id);
        return $this->db->single() ?: [];
    }

    // Generate unique user ID
    private function generateUserId($role) {
        $prefix = strtoupper(substr($role, 0, 3));
        $timestamp = date('ymd');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }

    // Log login attempts
    private function logLoginAttempt($user_id, $ip_address, $success) {
        $this->db->query('INSERT INTO login_attempts (user_id, ip_address, success) 
                         VALUES (:user_id, :ip_address, :success)');
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':ip_address', $ip_address);
        $this->db->bind(':success', $success);
        $this->db->execute();
    }

    // Ensure core tables exist to avoid runtime errors on fresh installs
    private function ensureCoreTablesExist() {
        // users
        $this->db->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) UNIQUE NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','teacher','student') NOT NULL,
            status ENUM('pending','approved','rejected','suspended') DEFAULT 'pending',
            email_verified TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(100),
            reset_token VARCHAR(100),
            reset_token_expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->execute();

        // teachers
        $this->db->query("CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) UNIQUE NOT NULL,
            teacher_id VARCHAR(20) UNIQUE NOT NULL,
            department VARCHAR(255) NOT NULL,
            CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->execute();

        // students
        $this->db->query("CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) UNIQUE NOT NULL,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            year_level VARCHAR(50) NOT NULL,
            section VARCHAR(50) NOT NULL,
            CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->execute();

        // admins
        $this->db->query("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) UNIQUE NOT NULL,
            admin_id VARCHAR(20) UNIQUE NOT NULL,
            CONSTRAINT fk_admins_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->execute();

        // login_attempts
        $this->db->query("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20),
            ip_address VARCHAR(45),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success TINYINT(1) DEFAULT 0,
            INDEX idx_user_id (user_id),
            INDEX idx_ip_time (ip_address, attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->execute();
    }

    // Send verification email
    private function sendVerificationEmail($email, $token) {
        // Email functionality removed
        return;
    }

    // Verify email
    public function verifyEmail($token) {
        $this->db->query('UPDATE users SET email_verified = 1, verification_token = NULL 
                         WHERE verification_token = :token');
        $this->db->bind(':token', $token);
        
        if ($this->db->execute() && $this->db->rowCount() > 0) {
            return ['success' => true, 'message' => 'Email verified successfully'];
        }
        
        return ['success' => false, 'message' => 'Invalid verification token'];
    }

    // Get pending users for admin approval
    public function getPendingUsers() {
        $this->db->query('SELECT u.*, 
                                CASE 
                                    WHEN u.role = "teacher" THEN t.department
                                    WHEN u.role = "student" THEN CONCAT(s.year_level, " - Section ", s.section)
                                    ELSE NULL
                                END as additional_info
                         FROM users u
                         LEFT JOIN teachers t ON u.user_id = t.user_id
                         LEFT JOIN students s ON u.user_id = s.user_id
                         WHERE u.status = "pending" AND u.role != "admin"
                         ORDER BY u.created_at DESC');
        
        return $this->db->resultset();
    }

    // Approve/Reject user
    public function updateUserStatus($user_id, $status) {
        try {
            // First check if user exists
            $this->db->query('SELECT user_id, status FROM users WHERE user_id = :user_id');
            $this->db->bind(':user_id', $user_id);
            $existing_user = $this->db->single();
            
            if (!$existing_user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Check if status is already the same
            if ($existing_user['status'] === $status) {
                return ['success' => true, 'message' => 'User status is already ' . $status];
            }
            
            // Update the status
            $this->db->query('UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id');
            $this->db->bind(':status', $status);
            $this->db->bind(':user_id', $user_id);
            
            if ($this->db->execute()) {
                // Verify the update was successful
                $this->db->query('SELECT status FROM users WHERE user_id = :user_id');
                $this->db->bind(':user_id', $user_id);
                $updated_user = $this->db->single();
                
                if ($updated_user && $updated_user['status'] === $status) {
                    return ['success' => true, 'message' => 'User status updated successfully'];
                } else {
                    return ['success' => false, 'message' => 'Status update verification failed'];
                }
            }
            
            return ['success' => false, 'message' => 'Failed to update user status'];
            
        } catch (Exception $e) {
            error_log("User::updateUserStatus error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // Get user by ID
    public function getUserById($user_id) {
        $this->db->query('SELECT * FROM users WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $user = $this->db->single();
        
        if ($user) {
            $role_data = $this->getRoleData($user_id, $user['role']);
            return array_merge($user, $role_data);
        }
        
        return false;
    }

    // Update user information
    public function updateUser($user_id, $data) {
        try {
            // Update main user data
            $this->db->query('UPDATE users SET full_name = :full_name 
                             WHERE user_id = :user_id');
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':user_id', $user_id);
            $this->db->execute();

            // Update role-specific data
            $user = $this->getUserById($user_id);
            switch ($user['role']) {
                case 'teacher':
                    $this->db->query('UPDATE teachers SET department = :department 
                                     WHERE user_id = :user_id');
                    $this->db->bind(':department', $data['department']);
                    $this->db->bind(':user_id', $user_id);
                    $this->db->execute();
                    break;
                    
                case 'student':
                    $this->db->query('UPDATE students SET year_level = :year_level, section = :section 
                                     WHERE user_id = :user_id');
                    $this->db->bind(':year_level', $data['year_level']);
                    $this->db->bind(':section', $data['section']);
                    $this->db->bind(':user_id', $user_id);
                    $this->db->execute();
                    break;
            }

            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    // Delete user
    public function deleteUser($user_id) {
        try {
            // First, get user data to determine role
            $this->db->query('SELECT role FROM users WHERE user_id = :user_id');
            $this->db->bind(':user_id', $user_id);
            $user = $this->db->single();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Delete role-specific data first (due to foreign key constraints)
            switch ($user['role']) {
                case 'teacher':
                    $this->db->query('DELETE FROM teachers WHERE user_id = :user_id');
                    break;
                case 'student':
                    $this->db->query('DELETE FROM students WHERE user_id = :user_id');
                    break;
                case 'admin':
                    $this->db->query('DELETE FROM admins WHERE user_id = :user_id');
                    break;
            }
            
            $this->db->bind(':user_id', $user_id);
            $this->db->execute();
            
            // Delete from users table (this will cascade delete related records)
            $this->db->query('DELETE FROM users WHERE user_id = :user_id');
            $this->db->bind(':user_id', $user_id);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete user'];
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()];
        }
    }


}
 
