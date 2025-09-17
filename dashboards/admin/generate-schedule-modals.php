<!-- Subject Edit Modal -->
    <div id="subjectEditModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Edit Subject</h3>
                <button type="button" class="modal-close" onclick="closeSubjectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="subjectEditForm">
                    <div class="form-group">
                        <label for="editSubjectName">Subject Name *</label>
                        <input type="text" id="editSubjectName" name="subjectName" required>
                    </div>
                    <div class="form-group">
                        <label for="editTeacherName">Teacher Name *</label>
                        <input type="text" id="editTeacherName" name="teacherName" required>
                    </div>
                    <div class="form-group">
                        <label for="editRoomNumber">Room Number *</label>
                        <input type="text" id="editRoomNumber" name="roomNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectType">Subject Type *</label>
                        <select id="editSubjectType" name="subjectType" required>
                            <option value="math">Math</option>
                            <option value="science">Science</option>
                            <option value="english">English</option>
                            <option value="history">History</option>
                            <option value="physics">Physics</option>
                            <option value="chemistry">Chemistry</option>
                            <option value="biology">Biology</option>
                            <option value="computer">Computer</option>
                            <option value="art">Art</option>
                            <option value="pe">PE</option>
                            <option value="break">Break</option>
                            <option value="lunch">Lunch</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editTableNumber">Table Number *</label>
                        <select id="editTableNumber" name="tableNumber" required>
                            <option value="1">Table 1</option>
                            <option value="2">Table 2</option>
                            <option value="3">Table 3</option>
                            <option value="4">Table 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDay">Day *</label>
                        <select id="editDay" name="day" required>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editTime">Time *</label>
                        <select id="editTime" name="time" required>
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSubjectModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSubjectChanges()">Save Changes</button>
            </div>
        </div>
    </div>