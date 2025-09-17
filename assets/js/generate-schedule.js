
function showAlert(message, type){ try { showNotice(message, type); } catch(e) { alert(String(message)); } }
function modalConfirm(message){ try { return showConfirm(message, { confirmText: 'Confirm', cancelText: 'Cancel' }); } catch(e) { return Promise.resolve(confirm(String(message))); } }

// Global variable to track the currently scheduled semester
let scheduledSemester = null; // null = no courses scheduled, 'first'/'second'/'summer' = scheduled semester

// Global variable to track semester across all sections and year levels
let globalScheduledSemester = null; // null = no courses scheduled globally, 'first'/'second'/'summer' = global scheduled semester

// Initialize dashboard navigation cards
function initializeDashboardNavigation() {
    const dashboardCards = document.querySelectorAll('.dashboard-card-nav[data-dashboard]');
    
    // Add click handlers for dashboard navigation
    dashboardCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Remove active class from all dashboard cards
            dashboardCards.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Navigate to the selected dashboard
            const dashboard = this.dataset.dashboard;
            const href = this.getAttribute('href');
            
            if (href && href !== '#') {
                // Add a small delay to show the active state before navigation
                setTimeout(() => {
                    window.location.href = href;
                }, 150);
            }
        });
    });
}

// Generate college-style timetables (all 4 tables)
function generateTimetable(preserveScheduleData = false) {
    // If preserving schedule data, save current data before regenerating
    if (preserveScheduleData) {
        saveScheduleData();
    }
    const mainTimes = [
        '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
        '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM'
    ];
    
    const mainTimeValues = [
        '08:00', '09:00', '10:00', '11:00',
        '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'
    ];
    
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    // Generate all 3 tables
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        const tbody = document.querySelector(`.timetable[data-table="${tableNum}"] tbody`);
        if (!tbody) continue;

        tbody.innerHTML = '';

        mainTimes.forEach((time, index) => {
            // Main time row
            const mainRow = document.createElement('tr');
            mainRow.className = 'main-time-row';
            
            // Time cell with collapsible functionality
            const timeCell = document.createElement('td');
            timeCell.className = 'time-cell collapsible-time';
            timeCell.setAttribute('data-time', mainTimeValues[index]);
            
            // Split time into number and AM/PM
            const timeParts = time.split(' ');
            const timeNumber = timeParts[0];
            const timePeriod = timeParts[1];
            
            timeCell.innerHTML = `
                <div class="time-main" onclick="toggleTimeSlots('${mainTimeValues[index]}', ${tableNum})">
                    <div class="time-number">${timeNumber}</div>
                    <div class="time-period">${timePeriod}</div>
                    <div class="time-toggle">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            `;
            
            mainRow.appendChild(timeCell);

            // Day cells for main time
            days.forEach(day => {
                const cell = document.createElement('td');
                cell.className = 'timetable-cell main-time-cell';
                cell.setAttribute('data-day', day);
                cell.setAttribute('data-time', mainTimeValues[index]);
                cell.setAttribute('data-table', tableNum);
                cell.addEventListener('click', () => openSubjectModal(day, mainTimeValues[index], cell, tableNum));
                mainRow.appendChild(cell);
            });

            tbody.appendChild(mainRow);
            
            // Collapsible sub-time row
            const subRow = document.createElement('tr');
            subRow.className = 'sub-time-row';
            subRow.style.display = 'none';
            subRow.setAttribute('data-parent-time', mainTimeValues[index]);
            
            // Sub-time cell
            const subTimeCell = document.createElement('td');
            subTimeCell.className = 'sub-time-cell';
            // Calculate sub-times with proper AM/PM
            const baseHour = parseInt(mainTimeValues[index].split(':')[0]);
            const period = timeParts[1];
            
            subTimeCell.innerHTML = `
                <div class="sub-times">
                    <div class="sub-time" data-time="${mainTimeValues[index].replace(':00', ':15')}">${baseHour}:15 ${period}</div>
                    <div class="sub-time" data-time="${mainTimeValues[index].replace(':00', ':30')}">${baseHour}:30 ${period}</div>
                    <div class="sub-time" data-time="${mainTimeValues[index].replace(':00', ':45')}">${baseHour}:45 ${period}</div>
                </div>
            `;
            subRow.appendChild(subTimeCell);
            
            // Day cells for sub-times
            days.forEach(day => {
                const cell = document.createElement('td');
                cell.className = 'timetable-cell sub-time-cell';
                cell.setAttribute('data-day', day);
                cell.setAttribute('data-table', tableNum);
                
                // Create sub-time slots
                const subTimes = ['15', '30', '45'];
                subTimes.forEach(subTime => {
                    const subCell = document.createElement('div');
                    subCell.className = 'sub-time-slot';
                    subCell.setAttribute('data-time', mainTimeValues[index].replace(':00', `:${subTime}`));
                    subCell.addEventListener('click', () => openSubjectModal(day, mainTimeValues[index].replace(':00', `:${subTime}`), subCell, tableNum));
                    cell.appendChild(subCell);
                });
                
                subRow.appendChild(cell);
            });

            tbody.appendChild(subRow);
        });
    }

    // Initialize empty timetables
    initializeEmptyTimetables();
}

// Initialize empty timetables (all 3 tables)
function initializeEmptyTimetables() {
    const cells = document.querySelectorAll('.timetable-cell');
    cells.forEach(cell => {
        cell.innerHTML = '';
        cell.classList.add('empty');
    });
}

// Toggle time slots (show/hide 15-minute intervals)
function toggleTimeSlots(timeValue, tableNum) {
    // Find the specific table's sub-row and toggle icon
    const table = document.querySelector(`.timetable[data-table="${tableNum}"]`);
    if (!table) return;
    
    const subRow = table.querySelector(`tr[data-parent-time="${timeValue}"]`);
    const toggleIcon = table.querySelector(`[data-time="${timeValue}"] .time-toggle i`);
    
    if (subRow && toggleIcon) {
        if (subRow.style.display === 'none') {
            subRow.style.display = 'table-row';
            toggleIcon.classList.remove('fa-chevron-down');
            toggleIcon.classList.add('fa-chevron-up');
        } else {
            subRow.style.display = 'none';
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
        }
    }
}

// Add subject to a specific cell
function addSubjectToCell(day, time, subjectName, teacher, room, type, tableNum = null) {
    console.log(`Attempting to add subject: ${subjectName} to day: ${day}, time: ${time}, table: ${tableNum}`);
    
    let cell;
    if (tableNum) {
        // Try to find main time cell first
        cell = document.querySelector(`[data-day="${day}"][data-time="${time}"][data-table="${tableNum}"]`);
        console.log(`Main cell search result:`, cell);
        
        // If not found, try to find sub-time slot
        if (!cell) {
            cell = document.querySelector(`.sub-time-slot[data-day="${day}"][data-time="${time}"][data-table="${tableNum}"]`);
            console.log(`Sub-time cell search result:`, cell);
        }
    } else {
        cell = document.querySelector(`[data-day="${day}"][data-time="${time}"]`);
        console.log(`General cell search result:`, cell);
    }
    
    if (!cell) {
        console.error(`Cell not found for day: ${day}, time: ${time}, table: ${tableNum}`);
        console.log('Available cells with similar attributes:');
        const allCells = document.querySelectorAll('[data-day]');
        allCells.forEach(c => {
            console.log(`- Day: ${c.getAttribute('data-day')}, Time: ${c.getAttribute('data-time')}, Table: ${c.getAttribute('data-table')}`);
        });
        return;
    }
    
    console.log(`Found cell, adding subject: ${subjectName}`);

    cell.innerHTML = '';
    cell.classList.remove('empty');

    const subjectBlock = document.createElement('div');
    subjectBlock.className = `subject-block subject-${type}`;
    
    console.log(`Creating subject block with class: subject-block subject-${type}`);
    
    // Ensure visibility
    subjectBlock.style.opacity = '1';
    subjectBlock.style.visibility = 'visible';
    subjectBlock.style.display = 'flex';
    
    // Adjust content based on cell type
    if (cell.classList.contains('sub-time-slot')) {
        subjectBlock.innerHTML = `
            <div class="subject-name-small">${subjectName}</div>
            <div class="subject-details-small">${teacher}</div>
        `;
    } else {
    subjectBlock.innerHTML = `
        <div class="subject-name">${subjectName}</div>
        <div class="subject-details">${teacher} • ${room}</div>
    `;
    }
    
    subjectBlock.addEventListener('click', (e) => {
        e.stopPropagation();
        const tableNumber = cell.getAttribute('data-table');
        openEditSubjectModal(day, time, subjectName, teacher, room, type, cell, tableNumber);
    });

    // Add right-click context menu for deletion
    subjectBlock.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const tableNumber = cell.getAttribute('data-table');
        deleteSubjectFromCell(day, time, subjectName, cell, tableNumber);
    });

    cell.appendChild(subjectBlock);
    
    // Save schedule data to localStorage
    saveScheduleData();
    
    // Refresh course dropdown to remove the newly scheduled course
    refreshCourseDropdown();
}

// Refresh course dropdown to update available courses
function refreshCourseDropdown() {
    const semesterSelect = document.getElementById('semesterSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    
    if (semesterSelect && sectionSelect && semesterSelect.value && sectionSelect.value) {
        console.log('Refreshing course dropdown after schedule change');
        loadCourses(semesterSelect.value, sectionSelect.value, false); // Don't show loading message
    }
}

// Delete subject from a specific cell
function deleteSubjectFromCell(day, time, subjectName, cell, tableNum) {
    // Clear the cell content
    cell.innerHTML = '';
    cell.classList.add('empty');
    
    // Save updated schedule data to localStorage
    saveScheduleData();
    
    // Refresh course dropdown to add back the deleted course
    refreshCourseDropdown();
    
    // Show success message
    showAlert(`"${subjectName}" has been deleted from the schedule`, 'success');
}

// Save schedule data to localStorage with section information
function saveScheduleData() {
    // Get current section information
    const sectionSelect = document.getElementById('sectionSelect');
    const yearLevelSelect = document.getElementById('yearLevelSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    
    if (!sectionSelect || !sectionSelect.value || !yearLevelSelect || !yearLevelSelect.value) {
        console.log('Cannot save schedule data: missing section or year level selection');
        return;
    }
    
    const sectionId = sectionSelect.value;
    const sectionName = sectionSelect.options[sectionSelect.selectedIndex].textContent;
    const yearLevel = yearLevelSelect.value;
    const semester = semesterSelect ? semesterSelect.value : '';
    
    const scheduleData = [];
    
    // Get all scheduled subjects from all three timetables
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        const cells = document.querySelectorAll(`[data-table="${tableNum}"] .timetable-cell`);
        cells.forEach(cell => {
            const subjectBlock = cell.querySelector('.subject-block');
            if (subjectBlock) {
                const day = cell.getAttribute('data-day');
                const time = cell.getAttribute('data-time');
                const subjectName = subjectBlock.querySelector('.subject-name, .subject-name-small')?.textContent?.trim();
                const subjectDetails = subjectBlock.querySelector('.subject-details, .subject-details-small')?.textContent?.trim();
                
                if (subjectName && day && time) {
                    scheduleData.push({
                        tableNum: tableNum,
                        day: day,
                        time: time,
                        subjectName: subjectName,
                        subjectDetails: subjectDetails,
                        subjectType: Array.from(subjectBlock.classList).find(cls => cls.startsWith('subject-'))?.replace('subject-', '') || 'computer',
                        sectionId: sectionId,
                        sectionName: sectionName,
                        yearLevel: yearLevel,
                        semester: semester
                    });
                }
            }
        });
    }
    
    // Use a global schedule key for all sections
    const scheduleKey = 'globalScheduleData';
    localStorage.setItem(scheduleKey, JSON.stringify(scheduleData));
    
    console.log(`Saved global schedule data with key: ${scheduleKey}`);
}

// Load schedule data from localStorage (global schedule for all sections)
function loadScheduleData() {
    // Use global schedule key
    const scheduleKey = 'globalScheduleData';
    const savedData = localStorage.getItem(scheduleKey);
    
    if (savedData) {
        try {
            const scheduleData = JSON.parse(savedData);
            console.log(`Loading schedule data for section ${sectionId} (${yearLevel} ${semester}):`, scheduleData);
            
            // Check if there are any scheduled courses to determine the semester
            if (scheduleData.length > 0) {
                // Get the current semester from the dropdown to set scheduledSemester
                const semesterSelect = document.getElementById('semesterSelect');
                if (semesterSelect && semesterSelect.value) {
                    scheduledSemester = semesterSelect.value;
                    console.log('Set scheduled semester to:', scheduledSemester);
                    
                    // Set global semester if not already set
                    if (!globalScheduledSemester) {
                        globalScheduledSemester = semesterSelect.value;
                        console.log('Set global semester to:', globalScheduledSemester);
                    }
                    
                    // Lock the semester options based on the loaded data
                    lockSemesterOptions(scheduledSemester);
                } else {
                    // If no semester is selected but there's schedule data, 
                    // we need to determine the semester from the data
                    // For now, we'll assume it's the currently selected semester if any
                    const currentSemester = semesterSelect.value;
                    if (currentSemester) {
                        scheduledSemester = currentSemester;
                        if (!globalScheduledSemester) {
                            globalScheduledSemester = currentSemester;
                        }
                        lockSemesterOptions(scheduledSemester);
                    }
                }
            }
            
            scheduleData.forEach(item => {
                // Parse subject details to get instructor and room
                const details = item.subjectDetails || '';
                const parts = details.split(' • ');
                const instructor = parts[0] || 'TBA';
                const room = parts[1] || 'TBA';
                
                // Ensure we have a valid subject type
                const validSubjectTypes = ['math', 'science', 'english', 'history', 'physics', 'chemistry', 'biology', 'computer', 'art', 'pe', 'break', 'lunch'];
                const subjectType = validSubjectTypes.includes(item.subjectType) ? item.subjectType : 'computer';
                
                console.log(`Loading: ${item.subjectName} for ${item.day} at ${item.time} in table ${item.tableNum} with type: ${subjectType}`);
                
                // Add the subject to the cell
                addSubjectToCell(item.day, item.time, item.subjectName, instructor, room, subjectType, item.tableNum);
            });
            
            console.log('Schedule data loaded successfully');
            
            // Force refresh visibility of all subject blocks
            setTimeout(() => {
                refreshSubjectBlockVisibility();
                forceApplySubjectColors();
            }, 100);
        } catch (error) {
            console.error('Error loading schedule data:', error);
        }
    } else {
        console.log('No saved schedule data found');
    }
}

// Clear all schedule data
function clearScheduleData() {
    // Clear the global schedule data
    localStorage.removeItem('globalScheduleData');
    
    // Also clear old format and section-specific data for cleanup
    localStorage.removeItem('scheduleData');
    const keys = Object.keys(localStorage);
    keys.forEach(key => {
        if (key.startsWith('scheduleData_')) {
            localStorage.removeItem(key);
        }
    });
}

// Clear global schedule data
function clearCurrentSectionSchedule() {
    const scheduleKey = 'globalScheduleData';
    localStorage.removeItem(scheduleKey);
    
    console.log('Cleared global schedule data');
}

// Clear the current timetable display
function clearTimetable() {
    // Clear all subject blocks from all timetables
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        const cells = document.querySelectorAll(`[data-table="${tableNum}"] .timetable-cell`);
        cells.forEach(cell => {
            cell.innerHTML = '';
            cell.classList.add('empty');
        });
    }
    console.log('Timetable cleared');
}

// Force refresh visibility of all subject blocks
function refreshSubjectBlockVisibility() {
    const subjectBlocks = document.querySelectorAll('.subject-block');
    subjectBlocks.forEach(block => {
        block.style.opacity = '1';
        block.style.visibility = 'visible';
        block.style.display = 'flex';
        
        // Ensure the CSS class is properly applied
        const classList = Array.from(block.classList);
        const subjectTypeClass = classList.find(cls => cls.startsWith('subject-'));
        if (subjectTypeClass) {
            console.log(`Subject block has class: ${subjectTypeClass}`);
        } else {
            console.log('Subject block missing subject type class, adding default');
            block.classList.add('subject-computer');
        }
    });
    console.log(`Refreshed visibility for ${subjectBlocks.length} subject blocks`);
}

// Force apply subject colors by re-adding CSS classes
function forceApplySubjectColors() {
    const subjectBlocks = document.querySelectorAll('.subject-block');
    subjectBlocks.forEach(block => {
        const classList = Array.from(block.classList);
        const subjectTypeClass = classList.find(cls => cls.startsWith('subject-'));
        
        if (subjectTypeClass) {
            const subjectType = subjectTypeClass.replace('subject-', '');
            
            // Force re-apply the class by removing and adding it back
            block.classList.remove(subjectTypeClass);
            block.classList.add(`subject-${subjectType}`);
            
            console.log(`Re-applied class: subject-${subjectType}`);
        }
    });
    console.log(`Force applied colors to ${subjectBlocks.length} subject blocks`);
}

// Regenerate timetable while preserving schedule data
function regenerateTimetableWithScheduleData() {
    console.log('Regenerating timetable while preserving schedule data');
    
    // Save current schedule data
    saveScheduleData();
    
    // Regenerate the timetable
    generateTimetable();
    
    // Reload schedule data after a short delay
    setTimeout(() => {
        loadScheduleData();
    }, 100);
}

// Clear all schedules from timetables and localStorage
function clearAllSchedules() {
    // Clear all subject blocks from all timetables
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        const cells = document.querySelectorAll(`[data-table="${tableNum}"] .timetable-cell`);
        cells.forEach(cell => {
            cell.innerHTML = '';
            cell.classList.add('empty');
        });
    }
    
    // Clear localStorage
    clearScheduleData();
    
    // Reset scheduled semester (both local and global)
    scheduledSemester = null;
    globalScheduledSemester = null;
    console.log('Global semester lock cleared');
    
    // Restore all semester options
    restoreSemesterOptions();
    
    // Show success message
    showAlert('All schedules have been cleared', 'success');
}

// Timetable actions
function initializeTimetableActions() {
    // Timetable actions can be added here if needed
}


// Extract year level from section name (e.g., "1A" -> "1st Year")
function extractYearLevelFromSection(sectionName) {
    const match = sectionName.match(/^(\d+)/);
    if (match) {
        const yearNumber = parseInt(match[1]);
        switch(yearNumber) {
            case 1: return '1st Year';
            case 2: return '2nd Year';
            case 3: return '3rd Year';
            case 4: return '4th Year';
            default: return null;
        }
    }
    return null;
}

// Update semester options based on year level and scheduled semester
function updateSemesterOptions(yearLevel, lockedSemester = null) {
    const semesterSelect = document.getElementById('semesterSelect');
    if (!semesterSelect) return;

    // Clear existing options
    semesterSelect.innerHTML = '<option value="">Select Semester</option>';

    // If semester is locked, only show the locked semester option
    if (lockedSemester) {
        const semesterNames = {
            'first': 'First Semester',
            'second': 'Second Semester',
            'summer': 'Mid Year'
        };
        
        const lockedOption = document.createElement('option');
        lockedOption.value = lockedSemester;
        lockedOption.textContent = semesterNames[lockedSemester] + ' (Locked)';
        lockedOption.selected = true;
        semesterSelect.appendChild(lockedOption);
        
        // Make the dropdown read-only when locked
        semesterSelect.disabled = true;
        semesterSelect.style.backgroundColor = '#f3f4f6';
        semesterSelect.style.color = '#6b7280';
        semesterSelect.style.cursor = 'not-allowed';
        
        // Add a visual indicator that semester is locked
        const semesterLabel = document.querySelector('label[for="semesterSelect"]');
        if (semesterLabel) {
            semesterLabel.innerHTML = 'Semester <span style="color: #dc2626; font-weight: bold;">(Locked)</span>';
        }
        
        return;
    }

    // If no semester is locked, show all available options
    semesterSelect.disabled = false;
    semesterSelect.style.backgroundColor = 'white';
    semesterSelect.style.color = '#374151';
    semesterSelect.style.cursor = 'pointer';
    
    // Remove visual indicator that semester is locked
    const semesterLabel = document.querySelector('label[for="semesterSelect"]');
    if (semesterLabel) {
        semesterLabel.innerHTML = 'Semester';
    }

    // Show First and Second Semester for all year levels
    const firstSemester = document.createElement('option');
    firstSemester.value = 'first';
    firstSemester.textContent = 'First Semester';
    semesterSelect.appendChild(firstSemester);

    const secondSemester = document.createElement('option');
    secondSemester.value = 'second';
    secondSemester.textContent = 'Second Semester';
    semesterSelect.appendChild(secondSemester);

    // Only add Mid Year for 3rd Year
    if (yearLevel === '3rd Year') {
        const midYear = document.createElement('option');
        midYear.value = 'summer';
        midYear.textContent = 'Mid Year';
        semesterSelect.appendChild(midYear);
    }
}

// Update semester dropdown to lock specific semester
function lockSemesterOptions(lockedSemester) {
    const semesterSelect = document.getElementById('semesterSelect');
    if (!semesterSelect) return;
    
    const sectionSelect = document.getElementById('sectionSelect');
    const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];
    const sectionName = selectedSection ? selectedSection.textContent : '';
    const yearLevel = extractYearLevelFromSection(sectionName);
    
    if (yearLevel) {
        // Use global semester if available, otherwise use the provided semester
        const semesterToLock = globalScheduledSemester || lockedSemester;
        updateSemesterOptions(yearLevel, semesterToLock);
        // Ensure the locked semester is selected
        semesterSelect.value = semesterToLock;
    }
}

// Restore semester dropdown to normal state
function restoreSemesterOptions() {
    const semesterSelect = document.getElementById('semesterSelect');
    if (!semesterSelect) return;
    
    const sectionSelect = document.getElementById('sectionSelect');
    const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];
    const sectionName = selectedSection ? selectedSection.textContent : '';
    const yearLevel = extractYearLevelFromSection(sectionName);
    
    if (yearLevel) {
        updateSemesterOptions(yearLevel, null);
    }
}

// Load year levels from subject scheduling database
async function loadYearLevels() {
    try {
        const response = await fetch('../../api/get-year-levels-scheduling.php');
        const data = await response.json();
        
        const yearLevelSelect = document.getElementById('yearLevelSelect');
        if (data.success && yearLevelSelect) {
            yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
            data.year_levels.forEach(yearLevel => {
                const option = document.createElement('option');
                option.value = yearLevel.year_level;
                option.textContent = yearLevel.year_level;
                yearLevelSelect.appendChild(option);
            });
            yearLevelSelect.disabled = false;
        } else {
            showAlert('Failed to load year levels', 'error');
        }
    } catch (error) {
        console.error('Error loading year levels:', error);
        showAlert('Failed to load year levels', 'error');
    }
}

// Load sections based on selected year level from subject scheduling database
async function loadSections(yearLevel = '') {
    try {
        let url = '../../api/get-sections-scheduling.php';
        if (yearLevel) {
            url += `?year_level=${encodeURIComponent(yearLevel)}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        const sectionSelect = document.getElementById('sectionSelect');
        if (data.success && sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            // Filter out sections named A, B, or C
            const filteredSections = data.sections.filter(section => {
                return !['A', 'B', 'C'].includes(section.section_name);
            });
            
            filteredSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_id;
                option.textContent = section.section_name;
                sectionSelect.appendChild(option);
            });
            sectionSelect.disabled = !yearLevel; // Enable only if year level is selected
        } else {
            showAlert('Failed to load sections', 'error');
        }
    } catch (error) {
        console.error('Error loading sections:', error);
        showAlert('Failed to load sections', 'error');
    }
}

// Load courses based on semester and section using selected year level
async function loadCourses(semester, sectionId, showLoading = true) {
    try {
        // Get year level from the year level dropdown
        const yearLevelSelect = document.getElementById('yearLevelSelect');
        const yearLevel = yearLevelSelect ? yearLevelSelect.value : '';
        
        if (!yearLevel) {
            showAlert('Please select a year level first', 'error');
            return;
        }
        
        console.log('Loading courses for:', { yearLevel, semester, sectionId });
        console.log('Current semester lock state - global:', globalScheduledSemester, 'local:', scheduledSemester);
        console.log('Section ID type:', typeof sectionId, 'Value:', sectionId);
        
        const courseSelect = document.getElementById('courseSelect');
        if (showLoading && courseSelect) {
            // Show which year level and section's courses are being loaded
            const yearLevelSelect = document.getElementById('yearLevelSelect');
            const sectionSelect = document.getElementById('sectionSelect');
            const selectedYearLevel = yearLevelSelect ? yearLevelSelect.options[yearLevelSelect.selectedIndex].textContent : '';
            const selectedSection = sectionSelect ? sectionSelect.options[sectionSelect.selectedIndex].textContent : '';
            const loadingText = (selectedYearLevel && selectedSection) ? `Loading ${selectedYearLevel} ${selectedSection} courses...` : 'Loading courses...';
            courseSelect.innerHTML = `<option value="">${loadingText}</option>`;
            courseSelect.disabled = true;
        }
        
        // Add cache busting to ensure fresh data
        const timestamp = new Date().getTime();
        const url = `../../api/get-courses-scheduling.php?year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}&section_id=${sectionId}&_t=${timestamp}`;
        console.log('API URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        console.log('API Response courses count:', data.courses ? data.courses.length : 0);
        if (data.courses && data.courses.length > 0) {
            console.log('First few courses:', data.courses.slice(0, 3).map(c => ({ code: c.code, section: c.section_name })));
        }
        
        if (data.success && courseSelect) {
            console.log('Course loading successful. Found', data.courses ? data.courses.length : 0, 'courses');
            
            // Update section indicator
            const sectionIndicator = document.getElementById('sectionIndicator');
            if (sectionIndicator) {
                const yearLevelSelect = document.getElementById('yearLevelSelect');
                const sectionSelect = document.getElementById('sectionSelect');
                const selectedYearLevel = yearLevelSelect ? yearLevelSelect.options[yearLevelSelect.selectedIndex].textContent : '';
                const selectedSection = sectionSelect ? sectionSelect.options[sectionSelect.selectedIndex].textContent : '';
                if (selectedYearLevel && selectedSection) {
                    sectionIndicator.textContent = `- ${selectedYearLevel} ${selectedSection} courses only`;
                } else {
                    sectionIndicator.textContent = '';
                }
            }
            
            // Get already scheduled course codes
            const scheduledCourseCodes = getScheduledCourseCodes();
            console.log('Already scheduled courses:', scheduledCourseCodes);
            
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            if (data.courses && data.courses.length > 0) {
                // Separate courses into available and scheduled
                const availableCourses = [];
                const scheduledCourses = [];
                
                data.courses.forEach(course => {
                    const isScheduled = scheduledCourseCodes.includes(course.code);
                    if (isScheduled) {
                        scheduledCourses.push(course);
                        console.log(`Course already scheduled: ${course.code}`);
                    } else {
                        availableCourses.push(course);
                    }
                });
                
                console.log('Available courses:', availableCourses.map(c => c.code));
                console.log('Scheduled courses (will be disabled):', scheduledCourses.map(c => c.code));
                
                // Add available courses first
                if (availableCourses.length > 0) {
                    availableCourses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        // Show course title without section information
                        option.textContent = `${course.code} - ${course.title}`;
                        option.setAttribute('data-units', course.units || 'N/A');
                        option.setAttribute('data-section', course.section_name || '');
                        courseSelect.appendChild(option);
                    });
                }
                
                // Add scheduled courses as disabled options
                if (scheduledCourses.length > 0) {
                    // Add separator if there are available courses
                    if (availableCourses.length > 0) {
                        const separator = document.createElement('option');
                        separator.disabled = true;
                        separator.textContent = '────────── Already Scheduled ──────────';
                        separator.style.color = '#6b7280';
                        separator.style.fontStyle = 'italic';
                        separator.style.fontWeight = '600';
                        separator.style.textAlign = 'center';
                        separator.style.backgroundColor = '#f3f4f6';
                        courseSelect.appendChild(separator);
                    }
                    
                    scheduledCourses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        // Show course title without section information
                        option.textContent = `${course.code} - ${course.title} (Scheduled)`;
                        option.setAttribute('data-units', course.units || 'N/A');
                        option.setAttribute('data-section', course.section_name || '');
                        option.disabled = true;
                        option.style.color = '#9ca3af';
                        option.style.fontStyle = 'italic';
                        courseSelect.appendChild(option);
                    });
                }
                
                courseSelect.disabled = false;
                console.log('Course dropdown enabled with', availableCourses.length, 'available courses and', scheduledCourses.length, 'scheduled courses (disabled)');
            } else {
                console.log('No courses available for this section/semester combination');
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No courses available';
                courseSelect.appendChild(option);
                courseSelect.disabled = false;
            }
        } else {
            console.error('API returned error:', data.message || 'Unknown error');
            showAlert(`Failed to load courses: ${data.message || 'Unknown error'}`, 'error');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showAlert(`Failed to load courses: ${error.message}`, 'error');
    }
}

// Open subject modal for adding new subject
function openSubjectModal(day = null, time = null, cell = null, tableNum = null) {
    // Store current values for adding new subject
    window.currentEditData = {
        day: day,
        time: time,
        subjectName: '',
        teacher: '',
        room: '',
        type: 'math',
        cell: cell,
        tableNum: tableNum,
        isNewSubject: true
    };

    // Populate the modal form with default values
    document.getElementById('modalTitle').textContent = 'Add New Subject';
    document.getElementById('editSubjectName').value = '';
    document.getElementById('editTeacherName').value = '';
    document.getElementById('editRoomNumber').value = '';
    document.getElementById('editSubjectType').value = 'math';
    document.getElementById('editTableNumber').value = tableNum || '1';
    document.getElementById('editDay').value = day || 'monday';
    document.getElementById('editTime').value = time || '08:00';

    // Show the modal
    document.getElementById('subjectEditModal').style.display = 'flex';
}

// Open edit subject modal
function openEditSubjectModal(day, time, subjectName, teacher, room, type, cell, tableNum) {
    // Store current values for editing
    window.currentEditData = {
        day: day,
        time: time,
        subjectName: subjectName,
        teacher: teacher,
        room: room,
        type: type,
        cell: cell,
        tableNum: tableNum
    };

    // Populate the modal form with current values
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('editSubjectName').value = subjectName;
    document.getElementById('editTeacherName').value = teacher;
    document.getElementById('editRoomNumber').value = room;
    document.getElementById('editSubjectType').value = type;
    document.getElementById('editTableNumber').value = tableNum;
    document.getElementById('editDay').value = day;
    document.getElementById('editTime').value = time;

    // Show the modal
    document.getElementById('subjectEditModal').style.display = 'flex';
}




// Toggle table expansion
        function toggleTableExpansion(tableNumber) {
    const tableSection = document.querySelector(`[data-table="${tableNumber}"]`);
    const button = tableSection.querySelector('.table-enlarge-btn');
    const icon = button.querySelector('i');
    const allTables = document.querySelectorAll('.timetable-section');
    const backdrop = document.getElementById('timetableBackdrop');
    
    if (tableSection.classList.contains('expanded')) {
        // Collapse the table
        tableSection.classList.remove('expanded');
        button.classList.remove('expanded');
        button.innerHTML = '<i class="fas fa-expand-arrows-alt"></i> Enlarge';
        backdrop.classList.remove('active');
        
        // Remove backdrop blur from all other tables
        allTables.forEach(table => {
            table.classList.remove('backdrop-blur');
        });
        
        // Regenerate the table with collapsible functionality
        generateTimetable(true);
        
        // Reload schedule data to restore scheduled courses
        setTimeout(() => {
            loadScheduleData();
        }, 100);
    } else {
        // Collapse any other expanded tables first
        const expandedTables = document.querySelectorAll('.timetable-section.expanded');
        expandedTables.forEach(table => {
            table.classList.remove('expanded');
            const btn = table.querySelector('.table-enlarge-btn');
            btn.classList.remove('expanded');
            btn.innerHTML = '<i class="fas fa-expand-arrows-alt"></i> Enlarge';
        });
        
        // Add backdrop blur to all other tables
        allTables.forEach(table => {
            if (table !== tableSection) {
                table.classList.add('backdrop-blur');
            }
        });
        
        // Expand the selected table
        tableSection.classList.add('expanded');
        button.classList.add('expanded');
        button.innerHTML = '<i class="fas fa-compress-arrows-alt"></i> Collapse';
        backdrop.classList.add('active');
        
        // Regenerate the table with collapsible functionality
        generateTimetable(true);
        
        // Reload schedule data to restore scheduled courses
        setTimeout(() => {
            loadScheduleData();
        }, 100);
    }
}

// Close expanded table when clicking on backdrop
document.getElementById('timetableBackdrop').addEventListener('click', function() {
    const expandedTable = document.querySelector('.timetable-section.expanded');
    if (expandedTable) {
        const tableNumber = expandedTable.getAttribute('data-table');
        toggleTableExpansion(tableNumber);
    }
});

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboardNavigation();
    generateTimetable();
    initializeTimetableActions();
    initializeModalControls();
    initializeDaySelection();
    initializeAddScheduleButton();
    loadYearLevels(); // Load year levels on page load
    
    // Load saved schedule data after timetables are generated
    setTimeout(() => {
        loadScheduleData();
    // Force enable course dropdown on page load
    forceEnableCourseDropdown();
    
    // Enforce semester lock on page load
    enforceSemesterLock();
    
    // Set up periodic check to ensure dropdown stays enabled and semester stays locked
    setInterval(() => {
        const courseSelect = document.getElementById('courseSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        
        // Only force enable if we have both section and semester selected
        if (courseSelect && courseSelect.disabled && sectionSelect.value && semesterSelect.value) {
            console.log('Course dropdown was disabled, force enabling...');
            forceEnableCourseDropdown();
        }
        
        // Check if semester lock needs to be enforced
        enforceSemesterLock();
    }, 2000); // Check every 2 seconds
    
    // Set up mutation observer to watch for changes to course dropdown
    const courseSelect = document.getElementById('courseSelect');
    if (courseSelect) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                    console.log('Course dropdown disabled attribute changed, force enabling...');
                    forceEnableCourseDropdown();
                }
            });
        });
        
        observer.observe(courseSelect, {
            attributes: true,
            attributeFilter: ['disabled']
        });
    }
    }, 500);
});

// Initialize day selection functionality
function initializeDaySelection() {
    // Initialize day selection buttons
    const dayButtons = document.querySelectorAll('.day-btn');
    let selectedDays = [];
    
    dayButtons.forEach(button => {
        button.addEventListener('click', function() {
            const day = this.getAttribute('data-day');
            
            // Toggle selection
            if (this.classList.contains('selected')) {
                // Deselect
                this.classList.remove('selected');
                selectedDays = selectedDays.filter(d => d !== day);
            } else {
                // Check if we can select this day based on valid combinations
                if (canSelectDay(day, selectedDays)) {
                    this.classList.add('selected');
                    selectedDays.push(day);
                } else {
                    // Show visual feedback for invalid selection
                    this.classList.add('invalid');
                    
                    // Get course information to show specific warning
                    const courseSelect = document.getElementById('courseSelect');
                    let warningMessage = 'Invalid day selection';
                    
                    if (courseSelect && courseSelect.value) {
                        const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
                        const units = selectedCourse.getAttribute('data-units');
                        
                        // Show specific warning for 2-unit courses
                        if (units === '2' || units === 2) {
                            warningMessage = '2-unit courses can only be scheduled for exactly 2 days (1 hour per day)';
                        }
                    }
                    
                    showAlert(warningMessage, 'error');
                    
                    // Remove invalid class after a short delay
                    setTimeout(() => {
                        this.classList.remove('invalid');
                    }, 1000);
                    return;
                }
            }
            
            // Update global selectedDays variable
            window.selectedDays = selectedDays;
            
            // Highlight selected day columns in timetables
            highlightSelectedDayColumns(new Set(selectedDays));
            
            console.log('Selected days:', selectedDays);
        });
    });
    
    console.log('Day selection buttons initialized');
}

// Check if a day can be selected based on course units
function canSelectDay(newDay, currentSelectedDays) {
    // Get the currently selected course to check its units
    const courseSelect = document.getElementById('courseSelect');
    if (!courseSelect || !courseSelect.value) {
        // If no course selected, allow free selection
        return true;
    }
    
    const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
    const units = selectedCourse.getAttribute('data-units');
    
    // Special handling for 2-unit courses - limit to exactly 2 days
    if (units === '2' || units === 2) {
        return currentSelectedDays.length < 2;
    }
    
    // For other courses, allow free selection
    return true;
}

// Filter day selection based on course units
function filterDaySelectionByUnits(units, courseCode) {
    console.log('Filtering day selection for units:', units, 'course:', courseCode);
    
    // Special handling for NSTP courses
    if (courseCode && courseCode.toLowerCase().includes('nstp')) {
        // NSTP courses can only be scheduled on Saturday for 3 straight hours
        enableOnlySaturday();
        return;
    }
    
    // Handle 2-unit courses
    if (units === '2' || units === 2) {
        // 2-unit courses can only be scheduled for 2 days (1 hour per day)
        showTwoUnitSchedulingOptions();
        return;
    }
    
    // Handle 3-unit courses
    if (units === '3' || units === 3) {
        // Show scheduling options for 3-unit courses
        showThreeUnitSchedulingOptions();
        return;
    }
    
    // For other courses, allow free selection
    enableAllDays();
}

// Enable only Saturday for NSTP courses
function enableOnlySaturday() {
    const dayButtons = document.querySelectorAll('.day-btn');
    dayButtons.forEach(btn => {
        const day = btn.getAttribute('data-day');
        if (day === 'saturday') {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.title = 'NSTP courses can only be scheduled on Saturday';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.title = 'NSTP courses cannot be scheduled on ' + day;
        }
    });
}

// Show scheduling options for 2-unit courses
function showTwoUnitSchedulingOptions() {
    const dayButtons = document.querySelectorAll('.day-btn');
    dayButtons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.title = '2-unit courses can be scheduled for 2 days (1 hour per day)';
    });
    
    // Show info message for 2-unit courses
    const infoMessage = document.getElementById('schedulingOptionsInfo');
    if (infoMessage) {
        infoMessage.innerHTML = `
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <strong>2-Unit Course Scheduling:</strong> Select exactly 2 days for this course. Each day will be scheduled for 1 hour.
            </div>
        `;
        infoMessage.style.display = 'block';
    }
}

// Show scheduling options for 3-unit courses
function showThreeUnitSchedulingOptions() {
    const dayButtons = document.querySelectorAll('.day-btn');
    dayButtons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.title = '3-unit course: Can be scheduled for 3 days (1 hour each) or 2 days (1.5 hours each)';
    });
    
    // Show scheduling options info
    showSchedulingOptionsInfo();
}

// Enable all days for regular courses
function enableAllDays() {
    const dayButtons = document.querySelectorAll('.day-btn');
    dayButtons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.title = 'Select days for scheduling';
    });
}

// Show scheduling options information
function showSchedulingOptionsInfo() {
    const infoDiv = document.getElementById('schedulingOptionsInfo');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <div style="background: #e0f2fe; border: 1px solid #0ea5e9; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem;">
                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle" style="color: #0ea5e9; margin-right: 0.5rem;"></i>
                    <strong style="color: #0c4a6e;">3-Unit Course Scheduling Options:</strong>
                </div>
                <div style="color: #0c4a6e; font-size: 0.875rem;">
                    <div>• <strong>Option 1:</strong> 3 days × 1 hour each</div>
                    <div>• <strong>Option 2:</strong> 2 days × 1.5 hours each</div>
                </div>
            </div>
        `;
    }
}

// Validate day selection based on course units
function validateDaySelectionForUnits(selectedDays, courseCode, units) {
    // Special validation for NSTP courses
    if (courseCode && courseCode.toLowerCase().includes('nstp')) {
        if (selectedDays.length !== 1 || !selectedDays.includes('saturday')) {
            return {
                isValid: false,
                message: 'NSTP courses can only be scheduled on Saturday for 3 straight hours.'
            };
        }
        return { isValid: true };
    }
    
    // Validation for 2-unit courses
    if (units === '2' || units === 2) {
        if (selectedDays.length === 2) {
            return { isValid: true };
        } else {
            return {
                isValid: false,
                message: '2-unit courses must be scheduled for exactly 2 days (1 hour per day).'
            };
        }
    }
    // Validation for 3-unit courses
    if (units === '3' || units === 3) {
        if (selectedDays.length === 2 || selectedDays.length === 3) {
            return { isValid: true };
        } else {
            return {
                isValid: false,
                message: '3-unit courses must be scheduled for either 2 days (1.5 hours each) or 3 days (1 hour each).'
            };
        }
    }
    
    // For other courses, allow any valid selection
    return { isValid: true };
}

// Find available time slots based on course requirements
function findAvailableTimeSlotsForCourse(selectedDays, courseCode, units) {
    // Special handling for NSTP courses - need 3 consecutive hours
    if (courseCode && courseCode.toLowerCase().includes('nstp')) {
        return findConsecutiveTimeSlots(selectedDays, 3);
    }
    
    // For 2-unit courses - exactly 2 days, 1 hour each
    if (units === '2' || units === 2) {
        // 2 days × 1 hour each - regular 1 hour slots
        return findAvailableTimeSlots(selectedDays);
    }
    
    // For 3-unit courses, check if it's 2 days (1.5 hours each) or 3 days (1 hour each)
    if (units === '3' || units === 3) {
        if (selectedDays.length === 2) {
            // 2 days × 1.5 hours each - need consecutive 1.5 hour slots
            return findConsecutiveTimeSlots(selectedDays, 1.5);
        } else if (selectedDays.length === 3) {
            // 3 days × 1 hour each - regular 1 hour slots
            return findAvailableTimeSlots(selectedDays);
        }
    }
    
    // For other courses, use regular time slot finding
    return findAvailableTimeSlots(selectedDays);
}

// Find consecutive time slots for courses requiring multiple hours
function findConsecutiveTimeSlots(selectedDays, requiredHours) {
    const availableSlots = [];
    const timeSlots = [
        '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
        '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM'
    ];
    
    selectedDays.forEach(day => {
        for (let i = 0; i < timeSlots.length; i++) {
            const startTime = timeSlots[i];
            const endIndex = i + Math.ceil(requiredHours);
            
            // Check if we have enough consecutive slots
            if (endIndex <= timeSlots.length) {
                let canSchedule = true;
                
                // Check if all required consecutive slots are available
                for (let j = i; j < endIndex; j++) {
                    if (!isTimeSlotAvailable(day, timeSlots[j])) {
                        canSchedule = false;
                        break;
                    }
                }
                
                if (canSchedule) {
                    const endTime = timeSlots[endIndex] || '5:00 PM';
                    availableSlots.push({
                        day: day,
                        startTime: startTime,
                        endTime: endTime,
                        duration: requiredHours,
                        displayText: `${startTime} - ${endTime} (${requiredHours}h)`
                    });
                }
            }
        }
    });
    
    return availableSlots;
}

// Check if a specific time slot is available for a day
function isTimeSlotAvailable(day, timeSlot) {
    const timetables = document.querySelectorAll('.timetable');
    
    for (let table of timetables) {
        const dayColumn = table.querySelector(`.day-${day}`);
        if (dayColumn) {
            const cells = dayColumn.querySelectorAll('.time-cell');
            for (let cell of cells) {
                if (cell.textContent.trim() === timeSlot) {
                    // Check if cell is empty or has placeholder content
                    const cellContent = cell.querySelector('.subject-item');
                    if (cellContent && cellContent.textContent.trim() !== '') {
                        return false; // Slot is occupied
                    }
                }
            }
        }
    }
    
    return true; // Slot is available
}

// Convert time format from "8:00 AM" to "08:00"
function convertTimeFormat(timeString) {
    const timeMap = {
        '8:00 AM': '08:00',
        '9:00 AM': '09:00',
        '10:00 AM': '10:00',
        '11:00 AM': '11:00',
        '12:00 PM': '12:00',
        '1:00 PM': '13:00',
        '2:00 PM': '14:00',
        '3:00 PM': '15:00',
        '4:00 PM': '16:00',
        '5:00 PM': '17:00'
    };
    
    return timeMap[timeString] || timeString;
}

// Force enable course dropdown - more aggressive approach
function forceEnableCourseDropdown() {
    const courseSelect = document.getElementById('courseSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    
    if (!courseSelect) {
        console.error('Course select element not found!');
        return;
    }
    
    console.log('Force enabling course dropdown...');
    console.log('Section value:', sectionSelect?.value);
    console.log('Semester value:', semesterSelect?.value);
    console.log('Current disabled state:', courseSelect.disabled);
    
    // Always enable the dropdown regardless of section/semester state
    courseSelect.disabled = false;
    courseSelect.style.backgroundColor = 'white';
    courseSelect.style.color = '#374151';
    courseSelect.style.opacity = '1';
    courseSelect.style.cursor = 'pointer';
    
    // Remove any disabled classes or attributes
    courseSelect.removeAttribute('disabled');
    courseSelect.classList.remove('disabled');
    
    // If we have section and semester, reload courses
    if (sectionSelect && semesterSelect && sectionSelect.value && semesterSelect.value) {
        console.log('Reloading courses for force enable...');
        loadCourses(semesterSelect.value, sectionSelect.value, false);
    } else {
        // If no section/semester, at least ensure dropdown has default option
        if (courseSelect.options.length <= 1) {
            courseSelect.innerHTML = '<option value="">Select Course</option>';
        }
    }
    
    console.log('Course dropdown force enabled - disabled:', courseSelect.disabled);
}

// Ensure course dropdown is enabled and ready for selection
function ensureCourseDropdownEnabled() {
    const courseSelect = document.getElementById('courseSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    
    if (courseSelect && sectionSelect && semesterSelect) {
        const hasSection = sectionSelect.value && sectionSelect.value !== '';
        const hasSemester = semesterSelect.value && semesterSelect.value !== '';
        
        console.log('Ensuring course dropdown enabled - section:', hasSection, 'semester:', hasSemester);
        
        if (hasSection && hasSemester) {
            courseSelect.disabled = false;
            courseSelect.style.backgroundColor = 'white';
            courseSelect.style.color = '#374151';
            courseSelect.style.opacity = '1';
            
            // If dropdown is empty, reload courses
            if (courseSelect.options.length <= 1) {
                console.log('Course dropdown is empty, reloading courses...');
                loadCourses(semesterSelect.value, sectionSelect.value, false);
            }
        }
    }
}

// Check and enforce semester lock
function enforceSemesterLock() {
    const currentLockedSemester = globalScheduledSemester || scheduledSemester;
    if (currentLockedSemester) {
        console.log('Enforcing semester lock for:', currentLockedSemester);
        lockSemesterOptions(currentLockedSemester);
    }
}

// Reset day selection - no longer using dropdown
function resetDaySelection() {
    // Clear day selections by resetting visual indicators
    clearDaySelections();
    
    // Clear scheduling options info
    const infoDiv = document.getElementById('schedulingOptionsInfo');
    if (infoDiv) {
        infoDiv.innerHTML = '';
    }
}

// Highlight selected day columns in all timetables
function highlightSelectedDayColumns(selectedDays) {
    const timetables = document.querySelectorAll('.timetable');
    
    timetables.forEach(table => {
        const dayHeaders = table.querySelectorAll('.day-header');
        
        dayHeaders.forEach(header => {
            const dayClass = Array.from(header.classList).find(cls => cls.startsWith('day-'));
            if (dayClass) {
                const day = dayClass.replace('day-', '');
                if (selectedDays.has(day)) {
                    header.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    header.style.color = 'white';
                } else {
                    header.style.background = 'linear-gradient(135deg, #667eea, #5a6fd8)';
                    header.style.color = 'white';
                }
            }
        });
    });
}

// Get currently selected days
function getSelectedDays() {
    // Get selected days from the day buttons directly
    const selectedDays = [];
    const dayButtons = document.querySelectorAll('.day-btn.selected');
    dayButtons.forEach(button => {
        const day = button.getAttribute('data-day');
        if (day) {
            selectedDays.push(day);
        }
    });
    
    // Also check window.selectedDays as backup
    if (selectedDays.length === 0 && window.selectedDays && window.selectedDays.length > 0) {
        return window.selectedDays;
    }
    
    return selectedDays;
}

// Clear all day selections
function clearDaySelections() {
    // Clear the selectedDays array
    window.selectedDays = [];
    
    // Clear day button selections
    const dayButtons = document.querySelectorAll('.day-btn');
    dayButtons.forEach(button => {
        button.classList.remove('selected');
    });
    
    // Reset day headers to default colors
    const timetables = document.querySelectorAll('.timetable');
    timetables.forEach(table => {
        const dayHeaders = table.querySelectorAll('.day-header');
        dayHeaders.forEach(header => {
            header.style.background = 'linear-gradient(135deg, #667eea, #5a6fd8)';
            header.style.color = 'white';
        });
    });
}

// Highlight available time slots in the timetable
function highlightAvailableTimeSlots(availableTimeSlots, selectedDays) {
    selectedDays.forEach(day => {
        availableTimeSlots.forEach(timeSlot => {
            // Highlight in all three timetables
            for (let tableNum = 1; tableNum <= 3; tableNum++) {
                const cell = document.querySelector(`[data-day="${day}"][data-time="${timeSlot}"][data-table="${tableNum}"]`);
                if (cell) {
                    cell.classList.add('available-slot');
                }
            }
        });
    });
}

// Highlight the selected time slot for confirmation
function highlightSelectedTimeSlot(timeSlot, selectedDays) {
    selectedDays.forEach(day => {
        // Highlight in all three timetables
        for (let tableNum = 1; tableNum <= 3; tableNum++) {
            const cell = document.querySelector(`[data-day="${day}"][data-time="${timeSlot}"][data-table="${tableNum}"]`);
            if (cell) {
                cell.classList.add('selected-slot');
            }
        }
    });
}

// Clear time slot highlights
function clearTimeSlotHighlights() {
    const highlightedCells = document.querySelectorAll('.available-slot, .selected-slot');
    highlightedCells.forEach(cell => {
        cell.classList.remove('available-slot', 'selected-slot');
    });
}

// Show time slot selection interface
function showTimeSlotSelection(availableTimeSlots, selectedDays) {
    const dayNames = {
        'monday': 'Monday',
        'tuesday': 'Tuesday', 
        'wednesday': 'Wednesday',
        'thursday': 'Thursday',
        'friday': 'Friday',
        'saturday': 'Saturday'
    };
    
    const selectedDayNames = selectedDays.map(day => dayNames[day] || day).join(', ');
    
    // Create a modal for time slot selection
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'timeSlotModal';
    modal.tabIndex = '-1';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Select Time Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Available time slots for ${selectedDayNames}:</p>
                    <div class="time-slots-container">
                        ${availableTimeSlots.map((slot, index) => `
                            <div class="time-slot-card card mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <input type="radio" name="timeSlot" id="slot${index}" value="${typeof slot === 'string' ? slot : slot.startTime}" class="form-check-input me-2">
                                        <label for="slot${index}" class="form-check-label">
                                            ${typeof slot === 'string' ? slot : `${slot.startTime} - ${slot.endTime}`}
                                        </label>
                                    </h6>
                                    ${typeof slot === 'object' ? `<p class="card-text text-muted mb-0">Consecutive time slot (${slot.duration} hours)</p>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmTimeSlot">Select Time Slot</button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to the document
    document.body.appendChild(modal);
    
    // Show the modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Return a promise that resolves with the selected time slot
    return new Promise((resolve) => {
        const confirmBtn = modal.querySelector('#confirmTimeSlot');
        const closeBtn = modal.querySelector('.btn-close');
        const cancelBtn = modal.querySelector('.btn-secondary');
        
        const cleanup = () => {
            modal.remove();
            modalInstance.dispose();
        };
        
        confirmBtn.addEventListener('click', () => {
            const selectedRadio = modal.querySelector('input[name="timeSlot"]:checked');
            if (selectedRadio) {
                const selectedValue = selectedRadio.value;
                // Find the original slot object to return
                const selectedSlot = availableTimeSlots.find(slot => 
                    typeof slot === 'string' ? slot === selectedValue : slot.startTime === selectedValue
                );
                cleanup();
                resolve(selectedSlot);
            } else {
                showAlert('Please select a time slot', 'warning');
            }
        });
        
        [closeBtn, cancelBtn].forEach(btn => {
            btn.addEventListener('click', () => {
                cleanup();
                resolve(null);
            });
        });
        
        // Handle modal hidden event
        modal.addEventListener('hidden.bs.modal', () => {
            cleanup();
            resolve(null);
        });
    });
}

// Get all scheduled course codes from the current timetable
function getScheduledCourseCodes() {
    const scheduledCourses = new Set();
    
    // Check all three timetables for scheduled courses
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        const allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        
        allDays.forEach(day => {
            // Get all time slots for this day in this table
            const dayCells = document.querySelectorAll(`[data-day="${day}"][data-table="${tableNum}"]`);
            
            dayCells.forEach(cell => {
                const subjectBlock = cell.querySelector('.subject-block');
                if (subjectBlock) {
                    const subjectName = subjectBlock.querySelector('.subject-name, .subject-name-small');
                    if (subjectName) {
                        const courseCode = subjectName.textContent.trim();
                        if (courseCode) {
                            scheduledCourses.add(courseCode);
                            console.log(`Found scheduled course: ${courseCode} in table ${tableNum}, day ${day}`);
                        }
                    }
                }
            });
        });
    }
    
    const result = Array.from(scheduledCourses);
    console.log(`Total scheduled courses found: ${result.length}`, result);
    return result;
}

// Check if a course is already scheduled for the same section (across all timetables and days)
function isCourseAlreadyScheduled(courseCode, selectedDays) {
    // Check all three timetables for the same course code across ALL days
    for (let tableNum = 1; tableNum <= 3; tableNum++) {
        // Check all days, not just the selected days
        const allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        
        allDays.forEach(day => {
            // Get all time slots for this day in this table
            const dayCells = document.querySelectorAll(`[data-day="${day}"][data-table="${tableNum}"]`);
            
            dayCells.forEach(cell => {
                const subjectBlock = cell.querySelector('.subject-block');
                if (subjectBlock) {
                    const subjectName = subjectBlock.querySelector('.subject-name, .subject-name-small');
                    if (subjectName && subjectName.textContent.trim() === courseCode) {
                        return true; // Course is already scheduled somewhere in this section
                    }
                }
            });
        });
    }
    return false;
}

// Find available time slots for selected days
function findAvailableTimeSlots(selectedDays) {
    const allTimeSlots = [
        '08:00', '09:00', '10:00', '11:00',
        '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'
    ];
    
    const availableSlots = [];
    
    // Check each time slot
    allTimeSlots.forEach(timeSlot => {
        let isAvailable = true;
        
        // Check if this time slot is available for ALL selected days
        selectedDays.forEach(day => {
            // Check all three timetables for conflicts
            for (let tableNum = 1; tableNum <= 3; tableNum++) {
                const cell = document.querySelector(`[data-day="${day}"][data-time="${timeSlot}"][data-table="${tableNum}"]`);
                if (cell && !cell.classList.contains('empty') && cell.querySelector('.subject-block')) {
                    // This cell already has a subject scheduled
                    isAvailable = false;
                    break;
                }
            }
        });
        
        if (isAvailable) {
            availableSlots.push(timeSlot);
        }
    });
    
    return availableSlots;
}

// z functionality
async function addScheduleToTimetable() {
    // Get selected values
    const sectionSelect = document.getElementById('sectionSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    const courseSelect = document.getElementById('courseSelect');
    const selectedDays = getSelectedDays();
    
    // Validation
    if (!sectionSelect.value) {
        showAlert('Please select a section first', 'error');
        return;
    }
    
    if (!semesterSelect.value) {
        showAlert('Please select a semester first', 'error');
        return;
    }
    
    // Check semester consistency (both local and global)
    const selectedSemester = semesterSelect.value;
    
    // Check global semester consistency first
    if (globalScheduledSemester && globalScheduledSemester !== selectedSemester) {
        const semesterNames = {
            'first': 'First Semester',
            'second': 'Second Semester', 
            'summer': 'Mid Year'
        };
        showAlert(`Cannot schedule ${semesterNames[selectedSemester]} course. The system-wide schedule is locked to ${semesterNames[globalScheduledSemester]} only. Please clear all schedules first to change semesters.`, 'error');
        return;
    }
    
    // Check local semester consistency
    if (scheduledSemester && scheduledSemester !== selectedSemester) {
        const semesterNames = {
            'first': 'First Semester',
            'second': 'Second Semester', 
            'summer': 'Mid Year'
        };
        showAlert(`Cannot schedule ${semesterNames[selectedSemester]} course. The current schedule is for ${semesterNames[scheduledSemester]} only. Please clear the existing schedule first to change semesters.`, 'error');
        return;
    }
    
    if (!courseSelect.value) {
        showAlert('Please select a course first', 'error');
        return;
    }
    
    if (selectedDays.length === 0) {
        showAlert('Please select at least one day for scheduling', 'error');
        return;
    }
    
    // Debug logging
    console.log('Selected days for scheduling:', selectedDays);
    console.log('Number of selected days:', selectedDays.length);
    
    // Get course information first to show specific warnings
    const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
    const courseText = selectedCourse.textContent;
    const courseCode = courseText.split(' - ')[0];
    const units = selectedCourse.getAttribute('data-units') || 'N/A';
    
    console.log('Course units:', units, 'Course code:', courseCode);
    
    // Check for 2-unit course constraint first
    if (units === '2' || units === 2) {
        if (selectedDays.length > 2) {
            showAlert('2-unit courses can only be scheduled for exactly 2 days (1 hour per day)', 'error');
            return;
        }
    }
    
    // Validate day selection based on course units
    const validationResult = validateDaySelectionForUnits(selectedDays, courseCode, units);
    console.log('Validation result:', validationResult);
    if (!validationResult.isValid) {
        showAlert(validationResult.message, 'error');
        return;
    }
    
    const courseTitle = courseText.split(' - ')[1] || courseText;
    
    // Check if this course is already scheduled for this section
    if (isCourseAlreadyScheduled(courseCode, selectedDays)) {
        showAlert(`Course "${courseCode}" is already scheduled for this section. Please delete the existing schedule first before adding a new one.`, 'error');
        return;
    }
    
    // Get section information
    const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];
    const sectionName = selectedSection.textContent;
    
    // Find available time slots for the selected days based on course requirements
    const availableTimeSlots = findAvailableTimeSlotsForCourse(selectedDays, courseCode, units);
    
    if (availableTimeSlots.length === 0) {
        showAlert('No available time slots found for the selected days. Please try different days.', 'error');
        return;
    }
    
    console.log('show time selection here..');
    // Show time slot selection interface to let user choose
    const selectedTimeSlot = await showTimeSlotSelection(availableTimeSlots, selectedDays);
    
    if (!selectedTimeSlot) {
        // User cancelled the time selection
        showAlert('Time selection cancelled. Please try again.', 'info');
        return;
    }
    
    // Extract the actual time value based on the slot type
    let actualTimeSlot;
    if (typeof selectedTimeSlot === 'string') {
        // Regular time slot (from findAvailableTimeSlots)
        actualTimeSlot = selectedTimeSlot;
    } else if (selectedTimeSlot && selectedTimeSlot.startTime) {
        // Consecutive time slot (from findConsecutiveTimeSlots)
        // Convert time format from "8:00 AM" to "08:00"
        actualTimeSlot = convertTimeFormat(selectedTimeSlot.startTime);
    } else {
        showAlert('Invalid time slot format', 'error');
        return;
    }
    
    // Highlight the selected time slot
    highlightSelectedTimeSlot(selectedTimeSlot, selectedDays);
    
    // Clear highlights after selection
    clearTimeSlotHighlights();
    
    // Use default values instead of prompts
    const instructor = 'TBA'; // To Be Announced
    const room = 'TBA'; // To Be Announced
    const subjectType = 'computer'; // Default subject type
    
    // Schedule the course for each selected day
    console.log(`Scheduling course: ${courseCode} for days: ${selectedDays.join(', ')} at time: ${actualTimeSlot}`);
    selectedDays.forEach(day => {
        console.log(`Processing day: ${day}`);
        // Add to all three timetables (Student, Instructor, Room)
        for (let tableNum = 1; tableNum <= 3; tableNum++) {
            console.log(`Adding to table: ${tableNum}`);
            addSubjectToCell(day, actualTimeSlot, courseCode, instructor, room, subjectType, tableNum);
        }
    });
    
    // Set the scheduled semester if this is the first course
    if (!scheduledSemester) {
        scheduledSemester = selectedSemester;
        console.log('Setting scheduled semester to:', scheduledSemester);
        // Lock the semester options to only allow the selected semester
        lockSemesterOptions(scheduledSemester);
    }
    
    // Set the global scheduled semester if this is the first course system-wide
    if (!globalScheduledSemester) {
        globalScheduledSemester = selectedSemester;
        console.log('Global semester locked to:', globalScheduledSemester);
    }
    
    // Ensure semester is locked after scheduling (additional safety check)
    setTimeout(() => {
        const currentSemester = document.getElementById('semesterSelect').value;
        if (currentSemester !== selectedSemester) {
            console.log('Semester was changed after scheduling, re-locking...');
            lockSemesterOptions(selectedSemester);
        }
    }, 100);
    
    // Show success message
    showAlert(`Course "${courseCode}" scheduled successfully for ${selectedDays.join(', ')} at ${actualTimeSlot}. You can now schedule another course.`, 'success');
    
    // Clear selections after successful scheduling
    clearDaySelections();
    
    // Reset course dropdown to allow selecting another course
    courseSelect.value = '';
    courseSelect.disabled = false;
    
    // Force enable the dropdown and ensure it's visible
    courseSelect.style.backgroundColor = 'white';
    courseSelect.style.color = '#374151';
    courseSelect.style.opacity = '1';
    
    console.log('Course dropdown reset - disabled:', courseSelect.disabled);
    
    // Reload courses to ensure dropdown is populated and ready for next selection
    const currentSection = document.getElementById('sectionSelect').value;
    const currentSemester = document.getElementById('semesterSelect').value;
    console.log('Reloading courses for section:', currentSection, 'semester:', currentSemester);
    
    if (currentSection && currentSemester) {
        loadCourses(currentSemester, currentSection, false);
    } else {
        console.warn('Cannot reload courses - missing section or semester');
        // If we can't reload, at least ensure the dropdown is enabled
        courseSelect.disabled = false;
        courseSelect.innerHTML = '<option value="">Select Course</option>';
    }
    
    // Reset units display
    const unitsDisplay = document.getElementById('courseUnitsDisplay');
    unitsDisplay.textContent = 'Units';
    unitsDisplay.style.background = '#f8fafc';
    unitsDisplay.style.borderColor = '#d1d5db';
    unitsDisplay.style.color = '#374151';
    
    // Clear scheduling options info
    const infoDiv = document.getElementById('schedulingOptionsInfo');
    if (infoDiv) {
        infoDiv.innerHTML = '';
    }
    
    // Reset day selection to show all options
    enableAllDays();
    
    // Force enable course dropdown after a short delay to allow for any async operations
    setTimeout(() => {
        forceEnableCourseDropdown();
    }, 100);
}

// Initialize Add Schedule button
function initializeAddScheduleButton() {
    document.getElementById('addScheduleBtn')?.addEventListener('click', function() {
        addScheduleToTimetable();
    });
    
    // Initialize Clear All Schedules button
    document.getElementById('clearAllSchedulesBtn')?.addEventListener('click', function() {
        clearAllSchedules();
    });
}

// Initialize modal controls
function initializeModalControls() {
    // Year level change handler
    document.getElementById('yearLevelSelect')?.addEventListener('change', function() {
        const yearLevel = this.value;
        const sectionSelect = document.getElementById('sectionSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        const courseSelect = document.getElementById('courseSelect');
        
        if (yearLevel) {
            // Load sections for the selected year level
            loadSections(yearLevel);
            
            // Update semester options based on year level
            updateSemesterOptions(yearLevel, null);
            
            // Reset course dropdown
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            courseSelect.disabled = true;
            
            // Reset units display
            const unitsDisplay = document.getElementById('courseUnitsDisplay');
            unitsDisplay.textContent = 'Units';
            unitsDisplay.style.background = '#f8fafc';
            unitsDisplay.style.borderColor = '#d1d5db';
            unitsDisplay.style.color = '#374151';
            
            // Reset day selection
            resetDaySelection();
        } else {
            // Reset dependent dropdowns
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            sectionSelect.disabled = true;
            semesterSelect.disabled = true;
            courseSelect.disabled = true;
            
            // Reset units display
            const unitsDisplay = document.getElementById('courseUnitsDisplay');
            unitsDisplay.textContent = 'Units';
            unitsDisplay.style.background = '#f8fafc';
            unitsDisplay.style.borderColor = '#d1d5db';
            unitsDisplay.style.color = '#374151';
            
            // Reset day selection
            resetDaySelection();
        }
    });

    // Section change handler
    document.getElementById('sectionSelect')?.addEventListener('change', function() {
        const sectionId = this.value;
        const semesterSelect = document.getElementById('semesterSelect');
        const courseSelect = document.getElementById('courseSelect');
        
        if (sectionId) {
            // Get year level from the year level dropdown
            const yearLevelSelect = document.getElementById('yearLevelSelect');
            const yearLevel = yearLevelSelect ? yearLevelSelect.value : '';
            
            if (yearLevel) {
                // Check if there's a global semester lock
                if (globalScheduledSemester) {
                    // If there's a global lock, enforce it for this section
                    scheduledSemester = globalScheduledSemester;
                    lockSemesterOptions(globalScheduledSemester);
                    console.log('Section changed - enforcing global semester lock:', globalScheduledSemester);
                } else if (scheduledSemester) {
                    // If there's a local semester lock, enforce it
                    lockSemesterOptions(scheduledSemester);
                    console.log('Section changed - enforcing local semester lock:', scheduledSemester);
                } else {
                    // No semester lock, reset local semester and restore options
                    scheduledSemester = null;
                    restoreSemesterOptions();
                    console.log('Section changed - no semester lock, restoring options');
                }
                
                // Reset course dropdown
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                courseSelect.disabled = true;
                
                // Reset units display
                const unitsDisplay = document.getElementById('courseUnitsDisplay');
                unitsDisplay.textContent = 'Units';
                unitsDisplay.style.background = '#f8fafc';
                unitsDisplay.style.borderColor = '#d1d5db';
                unitsDisplay.style.color = '#374151';
                
                // Always show all day selection options
                resetDaySelection();
                
                // Load courses for the new section
                const currentSemester = globalScheduledSemester || scheduledSemester;
                if (currentSemester) {
                    console.log('Loading courses for new section with locked semester:', currentSemester);
                    loadCourses(currentSemester, sectionId);
                    
                    // Ensure course dropdown is enabled after loading courses
                    setTimeout(() => {
                        forceEnableCourseDropdown();
                    }, 100);
                } else {
                    // If no semester lock, reset course dropdown and wait for semester selection
                    console.log('No semester lock - course dropdown will be loaded when semester is selected');
                    // Reset course dropdown to show it's waiting for semester selection
                    courseSelect.innerHTML = '<option value="">Select Semester First</option>';
                    courseSelect.disabled = true;
                }
            }
        } else {
            // Reset dependent dropdowns
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            semesterSelect.disabled = true;
            courseSelect.disabled = true;
            
            // Reset local scheduled semester when no section is selected
            // Keep global semester lock intact
            scheduledSemester = null;
            
            // Reset units display
            const unitsDisplay = document.getElementById('courseUnitsDisplay');
            unitsDisplay.textContent = 'Units';
            unitsDisplay.style.background = '#f8fafc';
            unitsDisplay.style.borderColor = '#d1d5db';
            unitsDisplay.style.color = '#374151';
            
            // Always show all day selection options
            resetDaySelection();
        }
    });

    // Semester change handler
    document.getElementById('semesterSelect')?.addEventListener('change', function() {
        // Prevent changes when semester is locked (check both local and global)
        const currentLockedSemester = globalScheduledSemester || scheduledSemester;
        console.log('Semester change attempted. Current locked semester:', currentLockedSemester, 'Selected:', this.value);
        
        if (currentLockedSemester && this.value !== currentLockedSemester) {
            const semesterNames = {
                'first': 'First Semester',
                'second': 'Second Semester',
                'summer': 'Mid Year'
            };
            showAlert(`Cannot change semester. The system is locked to ${semesterNames[currentLockedSemester]}. Clear all schedules first to change semesters.`, 'error');
            
            // Force the dropdown back to the locked semester
            this.value = currentLockedSemester;
            
            // Re-lock the semester options to ensure they stay locked
            lockSemesterOptions(currentLockedSemester);
            return;
        }
        
        const semester = this.value;
        const sectionId = document.getElementById('sectionSelect').value;
        const courseSelect = document.getElementById('courseSelect');
        
        if (semester && sectionId) {
            loadCourses(semester, sectionId);
            // Force enable dropdown after loading courses
            setTimeout(() => {
                forceEnableCourseDropdown();
            }, 100);
        } else {
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            courseSelect.disabled = true;
            
            // Clear section indicator
            const sectionIndicator = document.getElementById('sectionIndicator');
            if (sectionIndicator) {
                sectionIndicator.textContent = '';
            }
            
            // Restore all semester options if no semester is selected and no courses are scheduled
            if (!scheduledSemester) {
                restoreSemesterOptions();
            }
            
            // Reset units display
            const unitsDisplay = document.getElementById('courseUnitsDisplay');
            unitsDisplay.textContent = 'Units';
            unitsDisplay.style.background = '#f8fafc';
            unitsDisplay.style.borderColor = '#d1d5db';
            unitsDisplay.style.color = '#374151';
            
            // Always show all day selection options
            resetDaySelection();
        }
    });

    // Prevent course dropdown from being disabled
    const courseSelect = document.getElementById('courseSelect');
    if (courseSelect) {
        // Override the disabled property
        Object.defineProperty(courseSelect, 'disabled', {
            get: function() {
                return this.getAttribute('disabled') !== null;
            },
            set: function(value) {
                if (value) {
                    console.log('Attempted to disable course dropdown, preventing...');
                    this.removeAttribute('disabled');
                    this.style.backgroundColor = 'white';
                    this.style.color = '#374151';
                    this.style.opacity = '1';
                    this.style.cursor = 'pointer';
                } else {
                    this.removeAttribute('disabled');
                }
            }
        });
    }

    // Course change handler
    courseSelect?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const unitsDisplay = document.getElementById('courseUnitsDisplay');
        
        // Check if the selected option is disabled (scheduled course)
        if (selectedOption && selectedOption.disabled) {
            // Reset to default option
            this.selectedIndex = 0;
            showAlert('This course is already scheduled and cannot be selected again.', 'warning');
            return;
        }
        
        if (selectedOption && selectedOption.value) {
            const units = selectedOption.getAttribute('data-units') || 'N/A';
            const courseText = selectedOption.textContent;
            const courseCode = courseText.split(' - ')[0];
            
            unitsDisplay.textContent = `${units} units`;
            unitsDisplay.style.background = '#e0f2fe';
            unitsDisplay.style.borderColor = '#0ea5e9';
            unitsDisplay.style.color = '#0c4a6e';
            
            // Clear any existing day selections
            resetDaySelection();
            
            // Filter day selection based on course units and type
            filterDaySelectionByUnits(units, courseCode);
        } else {
            unitsDisplay.textContent = 'Units';
            unitsDisplay.style.background = '#f8fafc';
            unitsDisplay.style.borderColor = '#d1d5db';
            unitsDisplay.style.color = '#374151';
            
            // Reset day selection to show all options
            resetDaySelection();
            enableAllDays();
        }
    });



    // Form submission handler
    document.getElementById('addCourseForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        handleAddCourse();
    });

    // Close modal when clicking outside
    document.getElementById('addCourseModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddCourseModal();
        }
    });

    // Listen for course updates from other dashboards
    document.addEventListener('courseStatusChanged', function(e) {
        console.log('Course status changed detected:', e.detail);
        
        // Refresh the courses list if both section and semester are selected
        const sectionId = document.getElementById('sectionSelect').value;
        const semester = document.getElementById('semesterSelect').value;
        
        if (sectionId && semester) {
            console.log('Refreshing courses due to external update');
            loadCourses(semester, sectionId);
        }
    });
}

// Handle add course form submission
function handleAddCourse() {
    const formData = new FormData(document.getElementById('addCourseForm'));
    const sectionId = formData.get('section_id');
    const semester = formData.get('semester');
    const courseId = formData.get('course_id');

    if (!sectionId || !semester || !courseId) {
        showAlert('Please fill in all required fields', 'error');
        return;
    }

    // For now, just show a success message
    // In a real implementation, you would send this data to a server endpoint
    showAlert('Course added to schedule successfully!', 'success');
    closeAddCourseModal();
}


// Modal functions
function closeSubjectModal() {
    document.getElementById('subjectEditModal').style.display = 'none';
    window.currentEditData = null;
}

function saveSubjectChanges() {
    const form = document.getElementById('subjectEditForm');
    const formData = new FormData(form);
    
    const subjectName = formData.get('subjectName');
    const teacherName = formData.get('teacherName');
    const roomNumber = formData.get('roomNumber');
    const subjectType = formData.get('subjectType');
    const tableNumber = formData.get('tableNumber');
    const day = formData.get('day');
    const time = formData.get('time');

    // Validate required fields
    if (!subjectName || !teacherName || !roomNumber || !subjectType || !tableNumber || !day || !time) {
        alert('Please fill in all required fields.');
        return;
    }

    // Get current edit data
    const editData = window.currentEditData;
    if (!editData) {
        alert('No edit data found.');
        return;
    }

    // Add the subject to the cell
    addSubjectToCell(day, time, subjectName, teacherName, roomNumber, subjectType, tableNumber);
    
    // Close the modal
    closeSubjectModal();
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('subjectEditModal');
    if (event.target === modal) {
        closeSubjectModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeSubjectModal();
    }
});