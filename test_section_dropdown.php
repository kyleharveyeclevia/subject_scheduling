<!DOCTYPE html>
<html>
<head>
    <title>Test Section Dropdown</title>
    <style>
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Test Section Dropdown Functionality</h1>
    
    <div class="form-group">
        <label for="year_level">Year Level:</label>
        <select id="year_level">
            <option value="">Select Year Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="section">Section:</label>
        <select id="section">
            <option value="">Select section</option>
        </select>
    </div>
    
    <button onclick="testAPI()">Test API Call</button>
    
    <div id="result" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px;"></div>

    <script>
        // Test the section dropdown functionality
        document.getElementById('year_level').addEventListener('change', function() {
            const yearLevel = this.value;
            if (yearLevel) {
                loadSectionsForYearLevel(yearLevel, 'section');
            } else {
                document.getElementById('section').innerHTML = '<option value="">Select section</option>';
            }
        });

        async function loadSectionsForYearLevel(yearLevel, dropdownId) {
            try {
                const response = await fetch(`api/get-active-sections.php?year_level=${encodeURIComponent(yearLevel)}`);
                const data = await response.json();
                
                if (data.success) {
                    const dropdown = document.getElementById(dropdownId);
                    if (dropdown) {
                        // Clear existing options
                        dropdown.innerHTML = '<option value="">Select section</option>';
                        
                        // Add new options
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section_id;
                            option.textContent = section.section_name;
                            dropdown.appendChild(option);
                        });
                        
                        document.getElementById('result').innerHTML = `<strong>✅ Success:</strong> Loaded ${data.sections.length} sections for ${yearLevel}`;
                    }
                } else {
                    document.getElementById('result').innerHTML = `<strong>❌ Error:</strong> ${data.message}`;
                }
            } catch (error) {
                document.getElementById('result').innerHTML = `<strong>❌ Error:</strong> ${error.message}`;
            }
        }

        async function testAPI() {
            const yearLevel = document.getElementById('year_level').value;
            if (!yearLevel) {
                document.getElementById('result').innerHTML = '<strong>⚠️ Warning:</strong> Please select a year level first';
                return;
            }
            
            document.getElementById('result').innerHTML = '<strong>🔄 Testing...</strong> API call in progress...';
            await loadSectionsForYearLevel(yearLevel, 'section');
        }
    </script>
</body>
</html>
