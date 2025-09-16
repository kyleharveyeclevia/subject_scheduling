# Subject Scheduling Login System

A comprehensive web-based login and user management system for subject scheduling, built with HTML, CSS, JavaScript, and PHP.

## Features

### 🔐 Authentication System
- **Role-based login** (Admin, Teacher, Student)
- **Secure password hashing** using PHP's password_hash()
- **Email verification** for account activation
- **Password recovery** with secure reset tokens
- **Login attempt tracking** for security
- **Session management** with automatic role detection

### 👥 User Management
- **Three user roles** with specific registration requirements:
  - **Admin**: Full name, ID, email (@gmail.com), password
  - **Teacher**: Full name, Instructor ID, department, email (@gmail.com), password
  - **Student**: Full name, Student ID, year level, section, password
- **Admin approval system** for Teacher and Student accounts
- **User profile editing** by administrators
- **Account status management** (pending, approved, rejected, suspended)

### 🎨 Modern UI/UX
- **Responsive design** that works on all devices
- **Modern gradient backgrounds** and card-based layouts
- **Interactive form validation** with real-time feedback
- **Password visibility toggles** for better user experience
- **Loading states** and smooth animations
- **FontAwesome icons** throughout the interface

### 🛡️ Security Features
- **Password requirements**: Alphanumeric with special characters, uppercase/lowercase
- **Email validation**: Only @gmail.com addresses allowed (for Admin/Teacher)
- **Input sanitization** and validation on both frontend and backend
- **SQL injection protection** using prepared statements
- **CSRF protection** through session management
- **Secure password reset** with time-limited tokens

### 📊 Dashboard System
- **Admin Dashboard**: User approval, management, and system overview
- **Teacher Dashboard**: Subject management, schedule viewing, student tracking
- **Student Dashboard**: Schedule viewing, grade tracking, academic progress

## Installation

### Prerequisites
- **XAMPP/WAMP/LAMP** server with PHP 7.4+ and MySQL 5.7+
- Web browser with JavaScript enabled

### Setup Instructions

1. **Clone/Download** the project to your web server directory:
   ```
   c:\wamp64\www\nls\  (for WAMP)
   ```

2. **Start your web server** (Apache and MySQL)

3. **Run the setup script** by visiting:
   ```
   http://localhost/nls/setup.php
   ```

4. **Default Admin Account** will be created:
   - **ID**: ADMIN001
   - **Password**: Admin123!
   - **Email**: admin@gmail.com

## File Structure

```
nls/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet with modern UI
│   └── js/
│       └── main.js            # Form validation and UI interactions
├── auth/
│   ├── login.php              # Login handler
│   ├── register.php           # Registration handler
│   ├── logout.php             # Logout handler
│   ├── forgot-password.php    # Password reset request
│   └── reset-password.php     # Password reset handler
├── admin/
│   ├── update-user-status.php # User approval/rejection
│   ├── get-user.php           # Fetch user data
│   └── update-user.php        # Edit user information
├── classes/
│   └── User.php               # Main user management class
├── config/
│   └── database.php           # Database configuration
├── database/
│   └── schema.sql             # Database schema
├── forms/
│   ├── admin-form.php         # Admin registration form
│   ├── teacher-form.php       # Teacher registration form
│   └── student-form.php       # Student registration form
├── index.php                  # Login page
├── register.php               # Registration page
├── forgot-password.php        # Password reset request page
├── reset-password.php         # Password reset page
├── verify.php                 # Email verification page
├── admin-dashboard.php        # Admin dashboard
├── Instructor-dashboard.php   # Instructor dashboard
├── student-dashboard.php      # Student dashboard
├── setup.php                  # Database setup script
└── README.md                  # This file
```

## Database Schema

### Users Table
- `id`: Auto-increment primary key
- `user_id`: Unique identifier for login
- `full_name`: User's full name
- `email`: Email address (nullable for students)
- `password_hash`: Hashed password
- `role`: User role (admin, teacher, student)
- `status`: Account status (pending, approved, rejected, suspended)
- `email_verified`: Email verification status
- `verification_token`: Email verification token
- `reset_token`: Password reset token
- `reset_token_expires`: Reset token expiration
- `created_at`, `updated_at`: Timestamps

### Role-Specific Tables
- **teachers**: teacher_id, department
- **students**: student_id, year_level, section
- **admins**: admin_id

### Security Tables
- **login_attempts**: Track login attempts for security
- **email_verifications**: Manage email verification tokens

## Usage

### For Administrators
1. **Login** with admin credentials
2. **Review pending users** on the dashboard
3. **Approve or reject** teacher and student registrations
4. **Edit user information** as needed
5. **Manage system users** through the admin panel

### For Teachers
1. **Register** with teacher credentials
2. **Wait for admin approval**
3. **Login** after approval
4. **Access teacher dashboard** with subject management tools

### For Students
1. **Register** with student credentials
2. **Wait for admin approval**
3. **Login** after approval
4. **Access student dashboard** with schedule and grade viewing

## Validation Rules

### Name Fields
- **Letters and spaces only**
- Required for all user types

### ID Fields
- **Numbers only**
- Unique across the system

### Email Fields
- **@gmail.com addresses only** (Admin and Teacher)
- Unique across the system

### Password Fields
- **Minimum 8 characters**
- **At least one uppercase letter**
- **At least one lowercase letter**
- **At least one number**
- **At least one special character** (@$!%*?&)

### Department (Teachers)
- College of Communication and Information Technology
- College of Teacher Education

### Year Level (Students)
- First Year, Second Year, Third Year, Fourth Year

### Section (Students)
- A, B, C

## Security Considerations

1. **Password Security**: All passwords are hashed using PHP's `password_hash()` function
2. **SQL Injection Prevention**: All database queries use prepared statements
3. **Session Security**: Proper session management with secure logout
4. **Input Validation**: Both client-side and server-side validation
5. **Email Verification**: Prevents unauthorized account creation
6. **Admin Approval**: Additional security layer for user registration

## Browser Compatibility

- **Chrome** 80+
- **Firefox** 75+
- **Safari** 13+
- **Edge** 80+

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Custom CSS with modern design principles
- **Icons**: FontAwesome 6.0
- **Fonts**: Google Fonts (Inter)

## Contributing

1. Follow the existing code style and structure
2. Test all changes thoroughly
3. Ensure security best practices are maintained
4. Update documentation as needed

## License

This project is created for educational purposes. Feel free to use and modify as needed.

## Support

For issues or questions, please check the code comments and ensure all prerequisites are met. The system includes comprehensive error handling and user feedback.

---

**Note**: This system is designed for educational and demonstration purposes. For production use, additional security measures and features should be implemented.
