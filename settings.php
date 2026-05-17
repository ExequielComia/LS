<<<<<<< HEAD
<?php
session_start(); // MUST be first!
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// --- POST/AJAX ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // 1. SAVE PERSONAL INFO & AVATAR
    if ($_POST['action'] === 'save_personal_info') {
        $fname   = mysqli_real_escape_string($conn, $_POST['fname']);
        $lname   = mysqli_real_escape_string($conn, $_POST['lname']);
        $dob     = mysqli_real_escape_string($conn, $_POST['dob']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $email   = mysqli_real_escape_string($conn, $_POST['email']);
        $phone   = mysqli_real_escape_string($conn, $_POST['phone']);

        // Handle Image Upload
        $avatar_sql = "";
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'cust_' . $customer_id . '_' . time() . '.' . $file_ext;
            $dest = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $avatar_sql = ", avatar = '$dest'"; 
            }
        }

        // Updated SQL using exact column names: customer_id, fname, lname
        $update_query = "UPDATE customer SET 
                            fname = '$fname', 
                            lname = '$lname', 
                            dob = '$dob', 
                            address = '$address', 
                            email = '$email', 
                            phone = '$phone'
                            $avatar_sql 
                         WHERE customer_id = $customer_id";

        if ($conn->query($update_query)) {
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully!';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    // 2. CHANGE PASSWORD
    if ($_POST['action'] === 'change_password') {
        $current = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new     = mysqli_real_escape_string($conn, $_POST['new_password']);

        $check_result = $conn->query("SELECT password FROM customer WHERE customer_id = $customer_id");
        if ($check_result && $check_result->num_rows > 0) {
            if ($check_result->fetch_assoc()['password'] === $current) {
                if ($conn->query("UPDATE customer SET password = '$new' WHERE customer_id = $customer_id")) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully!';
                }
            } else {
                $response['message'] = 'Incorrect current password.';
            }
        }
        echo json_encode($response);
        exit;
    }

    // 3. DELETE ACCOUNT
    if ($_POST['action'] === 'delete_account') {
        if ($conn->query("DELETE FROM customer WHERE customer_id = $customer_id")) {
            $response['success'] = true;
        }
        echo json_encode($response);
        exit;
    }
    exit;
}

include 'header.php';
include 'sidebar.php';

// Fetch current data for the settings form using customer_id
$query = "SELECT * FROM customer WHERE customer_id = $customer_id";
$result = $conn->query($query);
$customer = $result ? $result->fetch_assoc() : [];

$initials = strtoupper(substr($customer['fname'] ?? 'U', 0, 1) . substr($customer['lname'] ?? 'N', 0, 1));
$avatar_src = $customer['avatar'] ?? '';
?>

<body>
    <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
            <p style="margin-top: 10px; font-family: sans-serif; color: #3b2208;" id="loading-text">Processing...</p>
        </div>
    </div>

    <main class="main" id="main">
        <h1 class="dashboard-title">Settings</h1>
        <hr class="divider">

        <div class="settings-wrap">
            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Personal Information</h3>
                    <p class="settings-card-desc">Your personal details are private and used only to improve your experience.</p>
                </div>
                <div class="settings-divider"></div>
                <div class="settings-middle" style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    
                    <label for="avatar-upload" class="avatar-box" id="avatar-preview" style="cursor: default; overflow: hidden; display: flex; justify-content: center; align-items: center; background: saddlebrown; color: white; font-size: 24px; font-weight: bold; width: 80px; height: 80px; border-radius: 50%; position: relative;">
                        <?php if (!empty($avatar_src)): ?>
                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" id="avatar-img" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span id="avatar-initials"><?php echo $initials; ?></span>
                            <img src="" id="avatar-img" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <?php endif; ?>
                        
                        <div id="avatar-overlay" style="display: none; position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; font-size: 12px; align-items: center; justify-content: center; text-align: center;">
                            Upload Photo
                        </div>
                    </label>
                    <input type="file" id="avatar-upload" accept="image/*" style="display: none;" disabled onchange="previewImage(event)">

                    <button class="edit-btn" onclick="toggleEdit()">Edit</button>
                </div>
                <div class="settings-right">
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">First Name</label>
                            <input type="text" class="settings-input" id="fname" value="<?php echo htmlspecialchars($customer['fname'] ?? ''); ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Last Name</label>
                            <input type="text" class="settings-input" id="lname" value="<?php echo htmlspecialchars($customer['lname'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date of Birth</label>
                        <input type="date" class="settings-input" id="dob" value="<?php echo htmlspecialchars($customer['dob'] ?? ''); ?>" disabled>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Address</label>
                        <input type="text" class="settings-input" id="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="House No., Street, Barangay, City" maxlength="150" disabled oninput="charCount(this, 'address-count', 150)">
                        <span class="field-hint" id="address-count"><?php echo strlen($customer['address'] ?? ''); ?>/150</span>
                    </div>
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Email</label>
                            <input type="email" class="settings-input" id="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Phone Number</label>
                            <input type="tel" class="settings-input" id="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" maxlength="13" disabled oninput="formatPhone(this)">
                            <span class="field-hint" id="phone-hint"></span>
                        </div>
                    </div>
                    <div id="save-info-wrap" style="display:none; margin-top: 8px;">
                        <button class="save-changes-btn" onclick="savePersonalInfo()">Save Info</button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Account Management</h3>
                    <p class="settings-card-desc">We recommend that you periodically update your password.</p>
                </div>
                <div class="settings-divider"></div>
                <div class="settings-right" style="flex: 2;">
                    <div class="acct-grid">
                        <div class="acct-pass-col">
                            <div class="field-group">
                                <label class="field-label">Current Password</label>
                                <input type="password" class="settings-input" id="pass-current">
                            </div>
                            <div class="field-group">
                                <label class="field-label">New Password</label>
                                <input type="password" class="settings-input" id="pass-new">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Confirm Password</label>
                                <input type="password" class="settings-input" id="pass-confirm">
                            </div>
                            <button class="save-changes-btn" onclick="savePassword()">Save Changes</button>
                        </div>
                        <div class="acct-danger-col">
                            <button class="support-btn" onclick="alert('Support: contact@laseanale.com')">Support</button>
                            <button class="delete-acct-btn" onclick="deleteAccount()">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let editMode = false;

        function showLoading(text) {
            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function toggleEdit() {
            editMode = !editMode;
            // Removed mname and uname from this array
            const fields = ['fname', 'lname', 'dob', 'address', 'email', 'phone', 'avatar-upload'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !editMode;
            });
            
            document.querySelector('.edit-btn').textContent = editMode ? 'Cancel' : 'Edit';
            document.getElementById('save-info-wrap').style.display = editMode ? 'block' : 'none';
            
            const avatarBox = document.getElementById('avatar-preview');
            const overlay = document.getElementById('avatar-overlay');
            avatarBox.style.cursor = editMode ? 'pointer' : 'default';
            overlay.style.display = editMode ? 'flex' : 'none';
        }

        function previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('avatar-img');
                    const initials = document.getElementById('avatar-initials');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if(initials) initials.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function charCount(input, counterId, max) { document.getElementById(counterId).textContent = input.value.length + '/' + max; }
        
        function formatPhone(input) {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            if (val.length > 7)      val = val.slice(0,4) + '-' + val.slice(4,7) + '-' + val.slice(7);
            else if (val.length > 4) val = val.slice(0,4) + '-' + val.slice(4);
            input.value = val;
        }

        function savePersonalInfo() {
            showLoading('Saving Profile...');

            const formData = new FormData();
            formData.append('action', 'save_personal_info');
            formData.append('fname', document.getElementById('fname').value.trim());
            formData.append('lname', document.getElementById('lname').value.trim());
            formData.append('dob', document.getElementById('dob').value);
            formData.append('address', document.getElementById('address').value.trim());
            formData.append('email', document.getElementById('email').value.trim());
            formData.append('phone', document.getElementById('phone').value.replace(/\D/g,''));

            const avatarInput = document.getElementById('avatar-upload');
            if (avatarInput.files.length > 0) {
                formData.append('avatar', avatarInput.files[0]);
            }

            fetch('settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload(); 
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        function savePassword() {
            const current = document.getElementById('pass-current').value;
            const newPass = document.getElementById('pass-new').value;
            const confirm = document.getElementById('pass-confirm').value;
            
            if (!current || !newPass || !confirm) { alert('Please fill in all password fields.'); return; }
            if (newPass !== confirm) { alert('New passwords do not match.'); return; }
            if (newPass.length < 6) { alert('Password must be at least 6 characters.'); return; }

            showLoading('Updating Password...');

            const formData = new URLSearchParams();
            formData.append('action', 'change_password');
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            fetch('settings.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    document.getElementById('pass-current').value = ''; document.getElementById('pass-new').value = ''; document.getElementById('pass-confirm').value = '';
                } else { alert('❌ ' + data.message); }
            }).catch(() => { hideLoading(); alert('Connection error.'); });
        }

        function deleteAccount() {
            if (!confirm('Are you absolutely sure? This will permanently delete your account and cannot be undone.')) return;
            showLoading('Deleting Account...');
            const formData = new URLSearchParams(); formData.append('action', 'delete_account');
            fetch('settings.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => {
                if (data.success) { alert('Account deleted.'); window.location.href = 'index.php'; }
            });
        }
    </script>
</body>
=======
<?php
session_start(); // MUST be first!
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// --- POST/AJAX ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // 1. SAVE PERSONAL INFO & AVATAR
    if ($_POST['action'] === 'save_personal_info') {
        $fname   = mysqli_real_escape_string($conn, $_POST['fname']);
        $lname   = mysqli_real_escape_string($conn, $_POST['lname']);
        $dob     = mysqli_real_escape_string($conn, $_POST['dob']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $email   = mysqli_real_escape_string($conn, $_POST['email']);
        $phone   = mysqli_real_escape_string($conn, $_POST['phone']);

        // Handle Image Upload
        $avatar_sql = "";
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'cust_' . $customer_id . '_' . time() . '.' . $file_ext;
            $dest = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $avatar_sql = ", avatar = '$dest'"; 
            }
        }

        // Updated SQL using exact column names: customer_id, fname, lname
        $update_query = "UPDATE customer SET 
                            fname = '$fname', 
                            lname = '$lname', 
                            dob = '$dob', 
                            address = '$address', 
                            email = '$email', 
                            phone = '$phone'
                            $avatar_sql 
                         WHERE customer_id = $customer_id";

        if ($conn->query($update_query)) {
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully!';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    // 2. CHANGE PASSWORD
    if ($_POST['action'] === 'change_password') {
        $current = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new     = mysqli_real_escape_string($conn, $_POST['new_password']);

        $check_result = $conn->query("SELECT password FROM customer WHERE customer_id = $customer_id");
        if ($check_result && $check_result->num_rows > 0) {
            if ($check_result->fetch_assoc()['password'] === $current) {
                if ($conn->query("UPDATE customer SET password = '$new' WHERE customer_id = $customer_id")) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully!';
                }
            } else {
                $response['message'] = 'Incorrect current password.';
            }
        }
        echo json_encode($response);
        exit;
    }

    // 3. DELETE ACCOUNT
    if ($_POST['action'] === 'delete_account') {
        if ($conn->query("DELETE FROM customer WHERE customer_id = $customer_id")) {
            $response['success'] = true;
        }
        echo json_encode($response);
        exit;
    }
    exit;
}

include 'header.php';
include 'sidebar.php';

// Fetch current data for the settings form using customer_id
$query = "SELECT * FROM customer WHERE customer_id = $customer_id";
$result = $conn->query($query);
$customer = $result ? $result->fetch_assoc() : [];

$initials = strtoupper(substr($customer['fname'] ?? 'U', 0, 1) . substr($customer['lname'] ?? 'N', 0, 1));
$avatar_src = $customer['avatar'] ?? '';
?>

<body>
    <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
            <p style="margin-top: 10px; font-family: sans-serif; color: #3b2208;" id="loading-text">Processing...</p>
        </div>
    </div>

    <main class="main" id="main">
        <h1 class="dashboard-title">Settings</h1>
        <hr class="divider">

        <div class="settings-wrap">
            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Personal Information</h3>
                    <p class="settings-card-desc">Your personal details are private and used only to improve your experience.</p>
                </div>
                <div class="settings-divider"></div>
                <div class="settings-middle" style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    
                    <label for="avatar-upload" class="avatar-box" id="avatar-preview" style="cursor: default; overflow: hidden; display: flex; justify-content: center; align-items: center; background: saddlebrown; color: white; font-size: 24px; font-weight: bold; width: 80px; height: 80px; border-radius: 50%; position: relative;">
                        <?php if (!empty($avatar_src)): ?>
                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" id="avatar-img" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span id="avatar-initials"><?php echo $initials; ?></span>
                            <img src="" id="avatar-img" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <?php endif; ?>
                        
                        <div id="avatar-overlay" style="display: none; position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; font-size: 12px; align-items: center; justify-content: center; text-align: center;">
                            Upload Photo
                        </div>
                    </label>
                    <input type="file" id="avatar-upload" accept="image/*" style="display: none;" disabled onchange="previewImage(event)">

                    <button class="edit-btn" onclick="toggleEdit()">Edit</button>
                </div>
                <div class="settings-right">
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">First Name</label>
                            <input type="text" class="settings-input" id="fname" value="<?php echo htmlspecialchars($customer['fname'] ?? ''); ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Last Name</label>
                            <input type="text" class="settings-input" id="lname" value="<?php echo htmlspecialchars($customer['lname'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Date of Birth</label>
                        <input type="date" class="settings-input" id="dob" value="<?php echo htmlspecialchars($customer['dob'] ?? ''); ?>" disabled>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Address</label>
                        <input type="text" class="settings-input" id="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="House No., Street, Barangay, City" maxlength="150" disabled oninput="charCount(this, 'address-count', 150)">
                        <span class="field-hint" id="address-count"><?php echo strlen($customer['address'] ?? ''); ?>/150</span>
                    </div>
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Email</label>
                            <input type="email" class="settings-input" id="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Phone Number</label>
                            <input type="tel" class="settings-input" id="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" maxlength="13" disabled oninput="formatPhone(this)">
                            <span class="field-hint" id="phone-hint"></span>
                        </div>
                    </div>
                    <div id="save-info-wrap" style="display:none; margin-top: 8px;">
                        <button class="save-changes-btn" onclick="savePersonalInfo()">Save Info</button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Account Management</h3>
                    <p class="settings-card-desc">We recommend that you periodically update your password.</p>
                </div>
                <div class="settings-divider"></div>
                <div class="settings-right" style="flex: 2;">
                    <div class="acct-grid">
                        <div class="acct-pass-col">
                            <div class="field-group">
                                <label class="field-label">Current Password</label>
                                <input type="password" class="settings-input" id="pass-current">
                            </div>
                            <div class="field-group">
                                <label class="field-label">New Password</label>
                                <input type="password" class="settings-input" id="pass-new">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Confirm Password</label>
                                <input type="password" class="settings-input" id="pass-confirm">
                            </div>
                            <button class="save-changes-btn" onclick="savePassword()">Save Changes</button>
                        </div>
                        <div class="acct-danger-col">
                            <button class="support-btn" onclick="alert('Support: contact@laseanale.com')">Support</button>
                            <button class="delete-acct-btn" onclick="deleteAccount()">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let editMode = false;

        function showLoading(text) {
            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function toggleEdit() {
            editMode = !editMode;
            // Removed mname and uname from this array
            const fields = ['fname', 'lname', 'dob', 'address', 'email', 'phone', 'avatar-upload'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !editMode;
            });
            
            document.querySelector('.edit-btn').textContent = editMode ? 'Cancel' : 'Edit';
            document.getElementById('save-info-wrap').style.display = editMode ? 'block' : 'none';
            
            const avatarBox = document.getElementById('avatar-preview');
            const overlay = document.getElementById('avatar-overlay');
            avatarBox.style.cursor = editMode ? 'pointer' : 'default';
            overlay.style.display = editMode ? 'flex' : 'none';
        }

        function previewImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('avatar-img');
                    const initials = document.getElementById('avatar-initials');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if(initials) initials.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function charCount(input, counterId, max) { document.getElementById(counterId).textContent = input.value.length + '/' + max; }
        
        function formatPhone(input) {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            if (val.length > 7)      val = val.slice(0,4) + '-' + val.slice(4,7) + '-' + val.slice(7);
            else if (val.length > 4) val = val.slice(0,4) + '-' + val.slice(4);
            input.value = val;
        }

        function savePersonalInfo() {
            showLoading('Saving Profile...');

            const formData = new FormData();
            formData.append('action', 'save_personal_info');
            formData.append('fname', document.getElementById('fname').value.trim());
            formData.append('lname', document.getElementById('lname').value.trim());
            formData.append('dob', document.getElementById('dob').value);
            formData.append('address', document.getElementById('address').value.trim());
            formData.append('email', document.getElementById('email').value.trim());
            formData.append('phone', document.getElementById('phone').value.replace(/\D/g,''));

            const avatarInput = document.getElementById('avatar-upload');
            if (avatarInput.files.length > 0) {
                formData.append('avatar', avatarInput.files[0]);
            }

            fetch('settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload(); 
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        function savePassword() {
            const current = document.getElementById('pass-current').value;
            const newPass = document.getElementById('pass-new').value;
            const confirm = document.getElementById('pass-confirm').value;
            
            if (!current || !newPass || !confirm) { alert('Please fill in all password fields.'); return; }
            if (newPass !== confirm) { alert('New passwords do not match.'); return; }
            if (newPass.length < 6) { alert('Password must be at least 6 characters.'); return; }

            showLoading('Updating Password...');

            const formData = new URLSearchParams();
            formData.append('action', 'change_password');
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            fetch('settings.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    document.getElementById('pass-current').value = ''; document.getElementById('pass-new').value = ''; document.getElementById('pass-confirm').value = '';
                } else { alert('❌ ' + data.message); }
            }).catch(() => { hideLoading(); alert('Connection error.'); });
        }

        function deleteAccount() {
            if (!confirm('Are you absolutely sure? This will permanently delete your account and cannot be undone.')) return;
            showLoading('Deleting Account...');
            const formData = new URLSearchParams(); formData.append('action', 'delete_account');
            fetch('settings.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => {
                if (data.success) { alert('Account deleted.'); window.location.href = 'index.php'; }
            });
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>