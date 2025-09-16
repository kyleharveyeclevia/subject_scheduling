<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../classes/User.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scheduling Information - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            color: #1e293b;
            overflow-x: hidden;
            min-height: 100vh;
        }



        /* Hero Section */
        .hero-section {
            min-height: 40vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            text-align: center;
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            padding: 1rem 1rem 1rem 1rem;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            margin-top: 0.5rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            margin-top: 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            line-height: 1.1;
            letter-spacing: -2px;
            animation: fadeInUp 1s ease-out;
            color: #667eea;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #000000;
            text-shadow: 0 1px 5px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out 0.2s both;
            font-weight: 400;
        }

        /* Modern Episodes Section */
        .episodes-section {
            padding: 0 1rem 3rem 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            margin-top: -6rem;
            position: relative;
        }

        .episodes-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .episodes-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            margin-top: 0.5rem;
            color: #1e293b;
            text-align: center;
            position: relative;
        }

        .episodes-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            border-radius: 2px;
        }

        .episodes-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            margin-top: 0.25rem;
            width: 100%;
        }

        .episode-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .episode-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(229, 9, 20, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
        }

        .episode-thumbnail {
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .episode-thumbnail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .episode-card:hover .episode-thumbnail::before {
            transform: translateX(100%);
        }

        .episode-info {
            padding: 2rem;
        }

        .episode-number {
            font-size: 0.9rem;
            color: rgba(30, 41, 59, 0.6);
            margin-bottom: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .episode-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #1e293b;
            line-height: 1.3;
        }

        .episode-description {
            font-size: 1rem;
            color: rgba(30, 41, 59, 0.7);
            line-height: 1.5;
        }

        /* Specific card styling */
        .episode-card.teacher-dashboard .episode-thumbnail {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .episode-card.courses-dashboard .episode-thumbnail {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .episode-card.room-dashboard .episode-thumbnail {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .episode-card.section-dashboard .episode-thumbnail {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
        }

        .episode-card.academic-year-dashboard .episode-thumbnail {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }

        /* Responsive Design */
         @media (max-width: 1400px) {
            .episodes-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1.25rem;
            }
            
            .hero-title {
                font-size: 3.5rem;
             }
         }
         
         @media (max-width: 1024px) {
            .episodes-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.25rem;
             }
         }
         
         @media (max-width: 768px) {
            .episodes-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .episodes-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 1rem 0.5rem;
            }
            
            .episodes-section {
                padding: 0 0.5rem 2rem 0.5rem;
            }
            
            .episode-info {
                padding: 1.5rem;
            }
            
            .episodes-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
         }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Manage Scheduling Information</h1>
            <p class="hero-subtitle">Configure and manage all scheduling components for your institution</p>
        </div>
    </section>

    <!-- Episodes Section -->
    <section class="episodes-section">
        <div class="episodes-container">
            <h2 class="episodes-title">Scheduling Management</h2>
            <div class="episodes-grid">
                <!-- Teacher Dashboard Card -->
                <a href="Instructor-dashboard.php" class="episode-card teacher-dashboard">
                    <div class="episode-thumbnail">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="episode-info">
                        <h3 class="episode-title">Teacher Dashboard</h3>
                        <p class="episode-description">Manage teacher information, assignments, schedules, and academic loads across the system.</p>
                          </div>
                      </a>

                      <!-- Courses Dashboard Card -->
                <a href="Courses-dashboard.php" class="episode-card courses-dashboard">
                    <div class="episode-thumbnail">
                              <i class="fas fa-book"></i>
                          </div>
                    <div class="episode-info">
                        <h3 class="episode-title">Courses Dashboard</h3>
                        <p class="episode-description">Manage course subjects, curriculum requirements, and academic programs for the institution.</p>
                          </div>
                      </a>

                      <!-- Room Dashboard Card -->
                <a href="rooms-dashboard.php" class="episode-card room-dashboard">
                    <div class="episode-thumbnail">
                              <i class="fas fa-door-open"></i>
                          </div>
                    <div class="episode-info">
                        <h3 class="episode-title">Room Dashboard</h3>
                        <p class="episode-description">Set up and maintain classroom assignments, capacity limits, and room availability for scheduling.</p>
                          </div>
                      </a>

                      <!-- Section Dashboard Card -->
                <a href="sections-dashboard.php" class="episode-card section-dashboard">
                    <div class="episode-thumbnail">
                              <i class="fas fa-users"></i>
                          </div>
                    <div class="episode-info">
                        <h3 class="episode-title">Section Dashboard</h3>
                        <p class="episode-description">Configure and manage student sections, class assignments, and enrollment information for the system.</p>
                          </div>
                      </a>

                <!-- Academic Year Dashboard Card -->
                <a href="academic-year-dashboard.php" class="episode-card academic-year-dashboard">
                    <div class="episode-thumbnail">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="episode-info">
                        <h3 class="episode-title">Academic Year Dashboard</h3>
                        <p class="episode-description">Manage academic year configurations, semester settings, and year-level assignments for the scheduling system.</p>
                    </div>
                </a>
            </div>
            </div>
    </section>

    <script>
        // Add smooth scrolling and modern interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to episode cards
            const episodeCards = document.querySelectorAll('.episode-card');
            episodeCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add ripple effect styles
        const style = document.createElement('style');
        style.textContent = `
            .episode-card {
                position: relative;
                overflow: hidden;
            }
            
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
