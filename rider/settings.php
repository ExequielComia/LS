<<<<<<< HEAD
<?php
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1;

// Fetch current rider data
$query = "SELECT fullname, email, contact, vehicle_type, profile_pic FROM riders WHERE id = $rider_id";
$result = mysqli_query($conn, $query);
$rider = mysqli_fetch_assoc($result);

// Set up initials for the default avatar
$name_parts = explode(' ', trim($rider['fullname']));
$initials = count($name_parts) >= 2 ? strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1)) : strtoupper(substr($rider['fullname'], 0, 2));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<style>
    /* Ensure the cropper stays perfectly inside the popup */
    .cropper-container {
        width: 100% !important;
        max-height: 400px;
    }

    #imageToCrop {
        display: block;
        max-width: 100%;
    }
</style>

<body>
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

                    <div class="sb-profile-pic" id="avatar-preview" style="width: 100px; height: 100px; font-size: 36px; <?php echo !empty($rider['profile_pic']) ? "background-image: url('" . $rider['profile_pic'] . "'); background-size: cover; background-position: center; color: transparent;" : ""; ?>">
                        <?= htmlspecialchars($initials) ?>
                    </div>

                    <input type="file" id="newCropperInput" accept="image/*" style="display: none;" onchange="openCropper(this)">
                    <button class="edit-btn" onclick="document.getElementById('newCropperInput').click()">Change Picture</button>
                </div>

                <div class="settings-right">
                    <div class="field-group">
                        <label class="field-label">Full Name</label>
                        <input type="text" class="settings-input" id="fullname" value="<?= htmlspecialchars($rider['fullname']) ?>" disabled>
                    </div>
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Email</label>
                            <input type="email" class="settings-input" id="email" value="<?= htmlspecialchars($rider['email']) ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Phone Number</label>
                            <input type="tel" class="settings-input" id="phone" value="<?= htmlspecialchars($rider['contact']) ?>" maxlength="13" disabled oninput="formatPhone(this)">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Vehicle Type</label>
                        <select class="settings-input" id="vehicle" disabled style="background: #fdf8ef;">
                            <option <?= $rider['vehicle_type'] == 'Bicycle' ? 'selected' : '' ?>>Bicycle</option>
                            <option <?= $rider['vehicle_type'] == 'Motorcycle' ? 'selected' : '' ?>>Motorcycle</option>
                            <option <?= $rider['vehicle_type'] == 'Car' ? 'selected' : '' ?>>Car</option>
                            <option <?= $rider['vehicle_type'] == 'E-bike' ? 'selected' : '' ?>>E-bike</option>
                        </select>
                    </div>

                    <button class="edit-btn" id="edit-toggle-btn" onclick="toggleEdit()" style="margin-top: 10px;">Edit Information</button>

                    <div id="save-info-wrap" style="display:none; margin-top: 8px;">
                        <button class="save-changes-btn" onclick="savePersonalInfo()">Save Info</button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Account Management</h3>
                    <p class="settings-card-desc">Update your password to help prevent unauthorized access.</p>
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
                            <button class="save-changes-btn" onclick="savePassword()">Update Password</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="cropModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
            <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive; text-align: center;">Adjust Profile Picture</h3>

            <div style="height: 300px; width: 100%; margin-bottom: 15px; background: #eee; display: flex; align-items: center; justify-content: center;">
                <img id="imageToCrop" src="" style="max-width: 100%; max-height: 100%; display: block;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="save-changes-btn" id="cropBtn" style="flex: 1; padding: 10px; border-radius: 8px;">Crop & Save</button>
                <button class="edit-btn" onclick="closeCropModal()" style="flex: 1; padding: 10px; border-radius: 8px;">Cancel</button>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <script>
        const riderId = <?= $rider_id ?>;
        let editMode = false;
        let cropper;
        
        // ─── 1. IMAGE CROPPING LOGIC ───
        function openCropper(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const imgToCrop = document.getElementById('imageToCrop');

                    // 1. Show the modal immediately
                    document.getElementById('cropModal').style.display = 'flex';

                    // 2. Destroy old cropper
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }

                    // 3. Define what happens when the image loads
                    imgToCrop.onload = function() {

                        // THE ULTIMATE FIX: Wait 100ms to let the browser physically draw the popup first!
                        setTimeout(function() {
                            cropper = new Cropper(imgToCrop, {
                                aspectRatio: 1, // Forces a perfect square
                                viewMode: 1, // Restricts crop box to inside canvas
                                dragMode: 'move', // Allows user to drag image around
                                autoCropArea: 0.8,
                                background: false
                            });
                        }, 100); // 100 millisecond delay

                    };

                    // 4. NOW set the image source
                    imgToCrop.src = e.target.result;
                };

                reader.readAsDataURL(input.files[0]);
                input.value = ''; // Reset input
            }
        }

        function closeCropModal() {
            document.getElementById('cropModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // When user clicks "Crop & Save"
        document.getElementById('cropBtn').addEventListener('click', function() {
            if (!cropper) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            // Get the cropped area as a high-quality 300x300 image
            cropper.getCroppedCanvas({
                width: 300,
                height: 300
            }).toBlob(function(blob) {

                const formData = new FormData();
                formData.append('profile_pic', blob, 'avatar.jpg');
                formData.append('action', 'update_pic');

                // Send to the PHP backend
                fetch('update_rider_settings.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Update the preview immediately on screen
                            const preview = document.getElementById('avatar-preview');
                            const url = URL.createObjectURL(blob);
                            preview.style.backgroundImage = `url(${url})`;
                            preview.style.color = 'transparent'; // Hide initials

                            // Close modal silently (Removed the alert!)
                            closeCropModal();
                        } else {
                            // Only alert if there is a database/server error
                            alert(data.message);
                        }
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.textContent = 'Crop & Save';
                    });

            }, 'image/jpeg', 0.85); // Output as JPEG at 85% quality
        });


        // ─── 2. STANDARD SETTINGS LOGIC ───
        function toggleEdit() {
            editMode = !editMode;
            const fields = ['fullname', 'email', 'phone', 'vehicle'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !editMode;
            });
            document.getElementById('edit-toggle-btn').textContent = editMode ? 'Cancel Edit' : 'Edit Information';
            document.getElementById('save-info-wrap').style.display = editMode ? 'block' : 'none';
        }

        function formatPhone(input) {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            if (val.length > 7) val = val.slice(0, 4) + '-' + val.slice(4, 7) + '-' + val.slice(7);
            else if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4);
            input.value = val;
        }

        function savePersonalInfo() {
            const fullname = document.getElementById('fullname').value.trim();
            const email = document.getElementById('email').value.trim();
            const contact = document.getElementById('phone').value.replace(/\D/g, '');
            const vehicle = document.getElementById('vehicle').value;

            if (!fullname || !email || !contact) {
                alert('Please fill in all fields.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_info');
            formData.append('fullname', fullname);
            formData.append('email', email);
            formData.append('contact', contact);
            formData.append('vehicle_type', vehicle);

            fetch('update_rider_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Personal info saved!');
                        toggleEdit();
                    } else {
                        alert(data.message);
                    }
                });
        }

        function savePassword() {
            const current = document.getElementById('pass-current').value;
            const newPass = document.getElementById('pass-new').value;
            const confirm = document.getElementById('pass-confirm').value;

            if (!current || !newPass || !confirm) {
                alert('Please fill in all password fields.');
                return;
            }
            if (newPass !== confirm) {
                alert('New passwords do not match.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_password');
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            fetch('update_rider_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        document.getElementById('pass-current').value = '';
                        document.getElementById('pass-new').value = '';
                        document.getElementById('pass-confirm').value = '';
                    }
                });
        }
    </script>
</body>

=======
<?php
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1;

// Fetch current rider data
$query = "SELECT fullname, email, contact, vehicle_type, profile_pic FROM riders WHERE id = $rider_id";
$result = mysqli_query($conn, $query);
$rider = mysqli_fetch_assoc($result);

// Set up initials for the default avatar
$name_parts = explode(' ', trim($rider['fullname']));
$initials = count($name_parts) >= 2 ? strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1)) : strtoupper(substr($rider['fullname'], 0, 2));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<style>
    /* Ensure the cropper stays perfectly inside the popup */
    .cropper-container {
        width: 100% !important;
        max-height: 400px;
    }

    #imageToCrop {
        display: block;
        max-width: 100%;
    }
</style>

<body>
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

                    <div class="sb-profile-pic" id="avatar-preview" style="width: 100px; height: 100px; font-size: 36px; <?php echo !empty($rider['profile_pic']) ? "background-image: url('" . $rider['profile_pic'] . "'); background-size: cover; background-position: center; color: transparent;" : ""; ?>">
                        <?= htmlspecialchars($initials) ?>
                    </div>

                    <input type="file" id="newCropperInput" accept="image/*" style="display: none;" onchange="openCropper(this)">
                    <button class="edit-btn" onclick="document.getElementById('newCropperInput').click()">Change Picture</button>
                </div>

                <div class="settings-right">
                    <div class="field-group">
                        <label class="field-label">Full Name</label>
                        <input type="text" class="settings-input" id="fullname" value="<?= htmlspecialchars($rider['fullname']) ?>" disabled>
                    </div>
                    <div class="fields-row">
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Email</label>
                            <input type="email" class="settings-input" id="email" value="<?= htmlspecialchars($rider['email']) ?>" disabled>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label class="field-label">Phone Number</label>
                            <input type="tel" class="settings-input" id="phone" value="<?= htmlspecialchars($rider['contact']) ?>" maxlength="13" disabled oninput="formatPhone(this)">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Vehicle Type</label>
                        <select class="settings-input" id="vehicle" disabled style="background: #fdf8ef;">
                            <option <?= $rider['vehicle_type'] == 'Bicycle' ? 'selected' : '' ?>>Bicycle</option>
                            <option <?= $rider['vehicle_type'] == 'Motorcycle' ? 'selected' : '' ?>>Motorcycle</option>
                            <option <?= $rider['vehicle_type'] == 'Car' ? 'selected' : '' ?>>Car</option>
                            <option <?= $rider['vehicle_type'] == 'E-bike' ? 'selected' : '' ?>>E-bike</option>
                        </select>
                    </div>

                    <button class="edit-btn" id="edit-toggle-btn" onclick="toggleEdit()" style="margin-top: 10px;">Edit Information</button>

                    <div id="save-info-wrap" style="display:none; margin-top: 8px;">
                        <button class="save-changes-btn" onclick="savePersonalInfo()">Save Info</button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-left">
                    <h3 class="settings-card-title">Account Management</h3>
                    <p class="settings-card-desc">Update your password to help prevent unauthorized access.</p>
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
                            <button class="save-changes-btn" onclick="savePassword()">Update Password</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="cropModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
            <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive; text-align: center;">Adjust Profile Picture</h3>

            <div style="height: 300px; width: 100%; margin-bottom: 15px; background: #eee; display: flex; align-items: center; justify-content: center;">
                <img id="imageToCrop" src="" style="max-width: 100%; max-height: 100%; display: block;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="save-changes-btn" id="cropBtn" style="flex: 1; padding: 10px; border-radius: 8px;">Crop & Save</button>
                <button class="edit-btn" onclick="closeCropModal()" style="flex: 1; padding: 10px; border-radius: 8px;">Cancel</button>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <script>
        const riderId = <?= $rider_id ?>;
        let editMode = false;
        let cropper;
        
        // ─── 1. IMAGE CROPPING LOGIC ───
        function openCropper(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const imgToCrop = document.getElementById('imageToCrop');

                    // 1. Show the modal immediately
                    document.getElementById('cropModal').style.display = 'flex';

                    // 2. Destroy old cropper
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }

                    // 3. Define what happens when the image loads
                    imgToCrop.onload = function() {

                        // THE ULTIMATE FIX: Wait 100ms to let the browser physically draw the popup first!
                        setTimeout(function() {
                            cropper = new Cropper(imgToCrop, {
                                aspectRatio: 1, // Forces a perfect square
                                viewMode: 1, // Restricts crop box to inside canvas
                                dragMode: 'move', // Allows user to drag image around
                                autoCropArea: 0.8,
                                background: false
                            });
                        }, 100); // 100 millisecond delay

                    };

                    // 4. NOW set the image source
                    imgToCrop.src = e.target.result;
                };

                reader.readAsDataURL(input.files[0]);
                input.value = ''; // Reset input
            }
        }

        function closeCropModal() {
            document.getElementById('cropModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // When user clicks "Crop & Save"
        document.getElementById('cropBtn').addEventListener('click', function() {
            if (!cropper) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            // Get the cropped area as a high-quality 300x300 image
            cropper.getCroppedCanvas({
                width: 300,
                height: 300
            }).toBlob(function(blob) {

                const formData = new FormData();
                formData.append('profile_pic', blob, 'avatar.jpg');
                formData.append('action', 'update_pic');

                // Send to the PHP backend
                fetch('update_rider_settings.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Update the preview immediately on screen
                            const preview = document.getElementById('avatar-preview');
                            const url = URL.createObjectURL(blob);
                            preview.style.backgroundImage = `url(${url})`;
                            preview.style.color = 'transparent'; // Hide initials

                            // Close modal silently (Removed the alert!)
                            closeCropModal();
                        } else {
                            // Only alert if there is a database/server error
                            alert(data.message);
                        }
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.textContent = 'Crop & Save';
                    });

            }, 'image/jpeg', 0.85); // Output as JPEG at 85% quality
        });


        // ─── 2. STANDARD SETTINGS LOGIC ───
        function toggleEdit() {
            editMode = !editMode;
            const fields = ['fullname', 'email', 'phone', 'vehicle'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !editMode;
            });
            document.getElementById('edit-toggle-btn').textContent = editMode ? 'Cancel Edit' : 'Edit Information';
            document.getElementById('save-info-wrap').style.display = editMode ? 'block' : 'none';
        }

        function formatPhone(input) {
            let val = input.value.replace(/\D/g, '');
            if (val.length > 11) val = val.slice(0, 11);
            if (val.length > 7) val = val.slice(0, 4) + '-' + val.slice(4, 7) + '-' + val.slice(7);
            else if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4);
            input.value = val;
        }

        function savePersonalInfo() {
            const fullname = document.getElementById('fullname').value.trim();
            const email = document.getElementById('email').value.trim();
            const contact = document.getElementById('phone').value.replace(/\D/g, '');
            const vehicle = document.getElementById('vehicle').value;

            if (!fullname || !email || !contact) {
                alert('Please fill in all fields.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_info');
            formData.append('fullname', fullname);
            formData.append('email', email);
            formData.append('contact', contact);
            formData.append('vehicle_type', vehicle);

            fetch('update_rider_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Personal info saved!');
                        toggleEdit();
                    } else {
                        alert(data.message);
                    }
                });
        }

        function savePassword() {
            const current = document.getElementById('pass-current').value;
            const newPass = document.getElementById('pass-new').value;
            const confirm = document.getElementById('pass-confirm').value;

            if (!current || !newPass || !confirm) {
                alert('Please fill in all password fields.');
                return;
            }
            if (newPass !== confirm) {
                alert('New passwords do not match.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_password');
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            fetch('update_rider_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        document.getElementById('pass-current').value = '';
                        document.getElementById('pass-new').value = '';
                        document.getElementById('pass-confirm').value = '';
                    }
                });
        }
    </script>
</body>

>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>