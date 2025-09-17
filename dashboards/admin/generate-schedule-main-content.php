 <main class="admin-main">
            <div class="main-header">
                <h1 class="page-title">
                    <i class="fas fa-magic"></i> Generate Schedule
                </h1>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Main Dashboard Navigation Cards -->
            <div class="dashboard-navigation-cards">
                <a href="Instructor-dashboard.php" class="dashboard-card-nav" data-dashboard="instructors">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    <div class="dashboard-card-content">
                        <h3>Instructors</h3>
                        <p>Manage Instructors</p>
                        </div>
                    </a>
                <a href="Courses-dashboard.php" class="dashboard-card-nav" data-dashboard="courses">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-book"></i>
                        </div>
                    <div class="dashboard-card-content">
                        <h3>Courses</h3>
                        <p>Manage Courses</p>
                        </div>
                    </a>
                <a href="academic-year-dashboard.php" class="dashboard-card-nav" data-dashboard="academic-year">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-calendar"></i>
                        </div>
                    <div class="dashboard-card-content">
                        <h3>Academic Year</h3>
                        <p>Manage Academic Years</p>
                        </div>
                    </a>
                <a href="rooms-dashboard.php" class="dashboard-card-nav" data-dashboard="rooms">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-door-open"></i>
                        </div>
                    <div class="dashboard-card-content">
                        <h3>Rooms</h3>
                        <p>Manage Rooms</p>
                        </div>
                    </a>
                <a href="sections-dashboard.php" class="dashboard-card-nav" data-dashboard="sections">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-users"></i>
                </div>
                    <div class="dashboard-card-content">
                        <h3>Sections</h3>
                        <p>Manage Sections</p>
                </div>
                </a>
            </div>

            <div class="main-content">
                <!-- Backdrop for centered expanded table -->
                <div class="timetable-backdrop" id="timetableBackdrop"></div>
                <div class="timetables-container">
                    <div class="timetables-header">
                        <div class="header-left">
                    <h2 class="timetables-title">
                                <i class="fas fa-calendar-alt"></i> Generate Schedule
                    </h2>
                        </div>
                    </div>
                    
                    <!-- Filter Dropdowns -->
                    <div class="filter-dropdowns" style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <!-- First row: Year Level, Section, Semester -->
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                            <label for="yearLevelSelect" style="font-weight: 500; color: #374151; font-size: 0.875rem;">Year Level *</label>
                            <select id="yearLevelSelect" name="year_level" required style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; transition: border-color 0.2s, box-shadow 0.2s;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                                <option value="">Select Year Level</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                            <label for="sectionSelect" style="font-weight: 500; color: #374151; font-size: 0.875rem;">Section *</label>
                            <select id="sectionSelect" name="section_id" required style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; transition: border-color 0.2s, box-shadow 0.2s;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" disabled>
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                            <label for="semesterSelect" style="font-weight: 500; color: #374151; font-size: 0.875rem;">Semester *</label>
                            <select id="semesterSelect" name="semester" required style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; transition: border-color 0.2s, box-shadow 0.2s;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                                <option value="">Select Semester</option>
                            </select>
                            </div>
                        </div>
                        
                        <!-- Second row: Course dropdown, Day Selection, and Add Schedule Button -->
                        <div style="display: flex; gap: 1rem; align-items: flex-end;">
                            <!-- Course dropdown -->
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                                    <label for="courseSelect" style="font-weight: 500; color: #374151; font-size: 0.875rem;">
                                        Course Title * 
                                        <span id="sectionIndicator" style="font-size: 0.75rem; color: #6b7280; font-weight: 400;"></span>
                                    </label>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <select id="courseSelect" name="course_id" required style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; transition: border-color 0.2s, box-shadow 0.2s; flex: 1;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" disabled>
                                <option value="">Select Course</option>
                            </select>
                                    <div id="courseUnitsDisplay" style="min-width: 60px; padding: 0.75rem; background: #f8fafc; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; color: #374151; text-align: center; display: flex; align-items: center; justify-content: center;">
                                        Units
                                    </div>
                        </div>
                    </div>
                    
                    <!-- Day Selection Buttons -->
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                        <label style="font-weight: 500; color: #374151; font-size: 0.875rem;">Day Selection *</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <button type="button" class="day-btn" data-day="monday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                M
                            </button>
                            <button type="button" class="day-btn" data-day="tuesday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                T
                            </button>
                            <button type="button" class="day-btn" data-day="wednesday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                W
                            </button>
                            <button type="button" class="day-btn" data-day="thursday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                TH
                            </button>
                            <button type="button" class="day-btn" data-day="friday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                F
                            </button>
                            <button type="button" class="day-btn" data-day="saturday" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background: #f8fafc; color: #374151; cursor: pointer; transition: all 0.2s; min-width: 45px;" onmouseover="if(!this.classList.contains('selected')) this.style.background='#e5e7eb'" onmouseout="if(!this.classList.contains('selected')) this.style.background='#f8fafc'">
                                S
                            </button>
                        </div>
                        <div id="schedulingOptionsInfo"></div>
                    </div>
                    
                    <!-- Add Schedule Button -->
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                                <div style="height: 1.25rem;"></div> <!-- Spacer to align with other elements -->
                                <button type="button" id="addScheduleBtn" style="background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;" onmouseover="this.style.background='#5a6fd8'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#667eea'; this.style.transform='translateY(0)'">
                            <i class="fas fa-plus"></i>
                            Add Schedule
                        </button>
                                <button type="button" id="clearAllSchedulesBtn" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;" onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
                            <i class="fas fa-trash"></i>
                            Clear All
                        </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Spacing between dropdowns and table -->
                    <div style="margin-top: 2rem;"></div>
                    
                    <!-- 1x4 Grid Layout - All Timetables Arranged Horizontally -->
                    <div class="timetables-grid-single">
                        <!-- Table 1 - Student -->
                        <div class="timetable-section" data-table="1">
                            <button class="table-enlarge-btn" onclick="toggleTableExpansion(1)">
                                <i class="fas fa-expand-arrows-alt"></i> Enlarge
                            </button>
                            <h3 class="table-title">Student Schedule</h3>
                            <div class="timetable-wrapper">
                                <table class="timetable" data-table="1">
                                    <thead>
                                        <tr>
                                            <th class="time-header">Time</th>
                                            <th class="day-header day-monday">M</th>
                                            <th class="day-header day-tuesday">T</th>
                                            <th class="day-header day-wednesday">W</th>
                                            <th class="day-header day-thursday">TH</th>
                                            <th class="day-header day-friday">F</th>
                                            <th class="day-header day-saturday">S</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Timetable rows will be generated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Table 2 - Teacher -->
                        <div class="timetable-section" data-table="2">
                            <button class="table-enlarge-btn" onclick="toggleTableExpansion(2)">
                                <i class="fas fa-expand-arrows-alt"></i> Enlarge
                            </button>
                            <h3 class="table-title">Instructor Schedule</h3>
                            <div class="timetable-wrapper">
                                <table class="timetable" data-table="2">
                                    <thead>
                                        <tr>
                                            <th class="time-header">Time</th>
                                            <th class="day-header day-monday">M</th>
                                            <th class="day-header day-tuesday">T</th>
                                            <th class="day-header day-wednesday">W</th>
                                            <th class="day-header day-thursday">TH</th>
                                            <th class="day-header day-friday">F</th>
                                            <th class="day-header day-saturday">S</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Timetable rows will be generated here -->
                                    </tbody>
                                </table>
                        </div>
                    </div>

                        <!-- Table 3 - Room -->
                        <div class="timetable-section" data-table="3">
                            <button class="table-enlarge-btn" onclick="toggleTableExpansion(3)">
                                <i class="fas fa-expand-arrows-alt"></i> Enlarge
                            </button>
                            <h3 class="table-title">Room Schedule</h3>
                            <div class="timetable-wrapper">
                                <table class="timetable" data-table="3">
                                    <thead>
                                        <tr>
                                            <th class="time-header">Time</th>
                                            <th class="day-header day-monday">M</th>
                                            <th class="day-header day-tuesday">T</th>
                                            <th class="day-header day-wednesday">W</th>
                                            <th class="day-header day-thursday">TH</th>
                                            <th class="day-header day-friday">F</th>
                                            <th class="day-header day-saturday">S</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Timetable rows will be generated here -->
                                    </tbody>
                                </table>
                                </div>
                            </div>

                    </div>

                </div>
            </div>
        </main>