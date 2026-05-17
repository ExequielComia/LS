<<<<<<< HEAD
<?php
include 'db.php';

// --- 📥 EXPORT ORDERS TO CSV (Runs on GET request) ---
if (isset($_GET['action']) && $_GET['action'] === 'export_orders') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Order ID', 'Product', 'Quantity', 'Total Price', 'Payment Method', 'Customer Name', 'Address', 'Status', 'Type', 'Date'));
    
    $query = "SELECT id, product, quantity, total_price, payment_method, customer_name, delivery_address, status, order_type, order_date FROM orders ORDER BY order_date DESC";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // --- 🔐 CHANGE PASSWORD ---
    if ($_POST['action'] === 'change_password') {
        $current = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new = mysqli_real_escape_string($conn, $_POST['new_password']);
        
        // Safely check the very first admin account (ID = 1) without worrying about case-sensitivity of the username
        $check_query = "SELECT id, password FROM admin LIMIT 1";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $admin = $check_result->fetch_assoc();
            
            // Check if the typed current password matches the database
            if ($admin['password'] === $current) {
                $admin_id = $admin['id'];
                $update_query = "UPDATE admin SET password = '$new' WHERE id = $admin_id";
                
                if ($conn->query($update_query)) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully!';
                } else {
                    $response['message'] = 'Database error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Incorrect current password.';
            }
        } else {
            $response['message'] = 'No admin account found in the database.';
        }
        
        echo json_encode($response);
        exit;
    }

    // --- SAVE GENERAL SETTINGS ---
    if ($_POST['action'] === 'save_settings' && isset($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            
            $query = "INSERT INTO settings (setting_key, setting_value) 
                      VALUES ('$key', '$value') 
                      ON DUPLICATE KEY UPDATE setting_value = '$value'";
            $conn->query($query);
        }
        $response['success'] = true;
        $response['message'] = 'Settings saved successfully!';
        echo json_encode($response);
        exit;
    }

    // --- 🗑️ CLEAR ORDER HISTORY ---
    if ($_POST['action'] === 'clear_orders') {
        if ($conn->query("DELETE FROM orders") && $conn->query("DELETE FROM returns")) {
            $conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE returns AUTO_INCREMENT = 1");
            $response['success'] = true;
            $response['message'] = 'All Order and Return history has been wiped clean.';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    // --- 🔄 RESET INVENTORY ---
    if ($_POST['action'] === 'reset_inventory') {
        if ($conn->query("DELETE FROM inventory")) {
            $conn->query("ALTER TABLE inventory AUTO_INCREMENT = 1");
            $response['success'] = true;
            $response['message'] = 'Inventory database has been reset.';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    echo json_encode($response);
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';

// Fetch all current settings from the database
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$sys_settings = [];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $sys_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Helper function to easily grab a setting or return a default value
function getSetting($key, $default = '') {
    global $sys_settings;
    return isset($sys_settings[$key]) ? htmlspecialchars($sys_settings[$key]) : $default;
}
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Settings</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;" id="loading-text">Processing...</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem; padding-bottom: 2rem;">

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Store Info</span>
                    <button class="btn-sm" onclick="saveStoreInfo()">Save</button>
                </div>
                <hr class="section-divider">
                <div class="new-product-grid">
                    <div class="new-product-field">
                        <label class="field-label">Store Name</label>
                        <input type="text" id="store-name" class="inv-input" style="width:100%;"
                            placeholder="e.g. La Seanale" value="<?php echo getSetting('store_name'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Address</label>
                        <input type="text" id="store-address" class="inv-input" style="width:100%;"
                            placeholder="e.g. 123 Main St, Cebu" value="<?php echo getSetting('store_address'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Contact Number</label>
                        <input type="text" id="store-contact" class="inv-input" style="width:100%;"
                            placeholder="e.g. 09XX-XXX-XXXX" value="<?php echo getSetting('store_contact'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Email</label>
                        <input type="email" id="store-email" class="inv-input" style="width:100%;"
                            placeholder="e.g. store@laseanale.com" value="<?php echo getSetting('store_email'); ?>">
                    </div>
                </div>
            </div>

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> User & Access (Admin)</span>
                    <button class="btn-sm" onclick="changePassword()">Update Password</button>
                </div>
                <hr class="section-divider">
                <div class="new-product-grid">
                    <div class="new-product-field">
                        <label class="field-label">Current Password</label>
                        <input type="password" id="pass-current" class="inv-input" style="width:100%;"
                            placeholder="Enter current password">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">New Password</label>
                        <input type="password" id="pass-new" class="inv-input" style="width:100%;"
                            placeholder="Enter new password">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Confirm New Password</label>
                        <input type="password" id="pass-confirm" class="inv-input" style="width:100%;"
                            placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Data Management</span>
                </div>
                <hr class="section-divider">
                
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <a href="settings.php?action=export_orders" class="btn-sm" style="background: white; color: #8b6340; border: 1px solid #8b6340; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px;">
                        📥 Export Orders as CSV
                    </a>
                    
                    <button class="btn-sm" style="background: white; color: #c0392b; border: 1px solid #c0392b; padding: 8px 16px;" onclick="clearOrders()">
                        🗑️ Clear Order History
                    </button>
                    
                    <button class="btn-sm" style="background: white; color: #c0392b; border: 1px solid #c0392b; padding: 8px 16px;" onclick="resetInventory()">
                        🔄 Reset Inventory
                    </button>
                </div>
                <p style="font-family: sans-serif; font-size: 12px; color: #c0392b; margin-top: 15px;">
                    ⚠ Danger zone — these actions cannot be undone. Users (Admin/Riders/Customers) will NOT be deleted.
                </p>
            </div>

        </div>
    </main>

    <script>
        function showLoading(text) {
            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        // --- Core Settings DB Function ---
        function pushSettingsToDatabase(settingsObj) {
            showLoading('Saving Settings...');

            const formData = new URLSearchParams();
            formData.append('action', 'save_settings');
            
            for (const key in settingsObj) {
                formData.append(`settings[${key}]`, settingsObj[key]);
            }

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        // --- Save Form Functions ---
        function saveStoreInfo() {
            const info = {
                'store_name': document.getElementById('store-name').value.trim(),
                'store_address': document.getElementById('store-address').value.trim(),
                'store_contact': document.getElementById('store-contact').value.trim(),
                'store_email': document.getElementById('store-email').value.trim()
            };
            pushSettingsToDatabase(info);
        }

        function saveReceiptSettings() {
            const receipt = {
                'receipt_prefix': document.getElementById('receipt-prefix').value.trim(),
                'receipt_header': document.getElementById('receipt-header').value.trim(),
                'receipt_footer': document.getElementById('receipt-footer').value.trim()
            };
            pushSettingsToDatabase(receipt);
        }

        function saveAlerts() {
            const alerts = {
                'alert_threshold': document.getElementById('alert-threshold').value
            };
            pushSettingsToDatabase(alerts);
        }

        // --- 🔐 CHANGE PASSWORD FUNCTION ---
        function changePassword() {
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

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    document.getElementById('pass-current').value = '';
                    document.getElementById('pass-new').value = '';
                    document.getElementById('pass-confirm').value = '';
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        // --- Database Data Management Functions ---
        function clearOrders() {
            if (!confirm('Are you ABSOLUTELY sure you want to clear all order and return history? This will permanently delete transactions. User accounts will NOT be affected.')) return;
            
            showLoading('Wiping Order Data...');

            const formData = new URLSearchParams();
            formData.append('action', 'clear_orders');

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        function resetInventory() {
            if (!confirm('Are you ABSOLUTELY sure you want to reset the inventory? All added products will be permanently deleted.')) return;
            
            showLoading('Resetting Inventory...');

            const formData = new URLSearchParams();
            formData.append('action', 'reset_inventory');

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }
    </script>
</body>
=======
<?php
include 'db.php';

// --- 📥 EXPORT ORDERS TO CSV (Runs on GET request) ---
if (isset($_GET['action']) && $_GET['action'] === 'export_orders') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Order ID', 'Product', 'Quantity', 'Total Price', 'Payment Method', 'Customer Name', 'Address', 'Status', 'Type', 'Date'));
    
    $query = "SELECT id, product, quantity, total_price, payment_method, customer_name, delivery_address, status, order_type, order_date FROM orders ORDER BY order_date DESC";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // --- 🔐 CHANGE PASSWORD ---
    if ($_POST['action'] === 'change_password') {
        $current = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new = mysqli_real_escape_string($conn, $_POST['new_password']);
        
        // Safely check the very first admin account (ID = 1) without worrying about case-sensitivity of the username
        $check_query = "SELECT id, password FROM admin LIMIT 1";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $admin = $check_result->fetch_assoc();
            
            // Check if the typed current password matches the database
            if ($admin['password'] === $current) {
                $admin_id = $admin['id'];
                $update_query = "UPDATE admin SET password = '$new' WHERE id = $admin_id";
                
                if ($conn->query($update_query)) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully!';
                } else {
                    $response['message'] = 'Database error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Incorrect current password.';
            }
        } else {
            $response['message'] = 'No admin account found in the database.';
        }
        
        echo json_encode($response);
        exit;
    }

    // --- SAVE GENERAL SETTINGS ---
    if ($_POST['action'] === 'save_settings' && isset($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            
            $query = "INSERT INTO settings (setting_key, setting_value) 
                      VALUES ('$key', '$value') 
                      ON DUPLICATE KEY UPDATE setting_value = '$value'";
            $conn->query($query);
        }
        $response['success'] = true;
        $response['message'] = 'Settings saved successfully!';
        echo json_encode($response);
        exit;
    }

    // --- 🗑️ CLEAR ORDER HISTORY ---
    if ($_POST['action'] === 'clear_orders') {
        if ($conn->query("DELETE FROM orders") && $conn->query("DELETE FROM returns")) {
            $conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE returns AUTO_INCREMENT = 1");
            $response['success'] = true;
            $response['message'] = 'All Order and Return history has been wiped clean.';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    // --- 🔄 RESET INVENTORY ---
    if ($_POST['action'] === 'reset_inventory') {
        if ($conn->query("DELETE FROM inventory")) {
            $conn->query("ALTER TABLE inventory AUTO_INCREMENT = 1");
            $response['success'] = true;
            $response['message'] = 'Inventory database has been reset.';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        echo json_encode($response);
        exit;
    }

    echo json_encode($response);
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';

// Fetch all current settings from the database
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$sys_settings = [];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $sys_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Helper function to easily grab a setting or return a default value
function getSetting($key, $default = '') {
    global $sys_settings;
    return isset($sys_settings[$key]) ? htmlspecialchars($sys_settings[$key]) : $default;
}
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Settings</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;" id="loading-text">Processing...</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem; padding-bottom: 2rem;">

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Store Info</span>
                    <button class="btn-sm" onclick="saveStoreInfo()">Save</button>
                </div>
                <hr class="section-divider">
                <div class="new-product-grid">
                    <div class="new-product-field">
                        <label class="field-label">Store Name</label>
                        <input type="text" id="store-name" class="inv-input" style="width:100%;"
                            placeholder="e.g. La Seanale" value="<?php echo getSetting('store_name'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Address</label>
                        <input type="text" id="store-address" class="inv-input" style="width:100%;"
                            placeholder="e.g. 123 Main St, Cebu" value="<?php echo getSetting('store_address'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Contact Number</label>
                        <input type="text" id="store-contact" class="inv-input" style="width:100%;"
                            placeholder="e.g. 09XX-XXX-XXXX" value="<?php echo getSetting('store_contact'); ?>">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Email</label>
                        <input type="email" id="store-email" class="inv-input" style="width:100%;"
                            placeholder="e.g. store@laseanale.com" value="<?php echo getSetting('store_email'); ?>">
                    </div>
                </div>
            </div>

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> User & Access (Admin)</span>
                    <button class="btn-sm" onclick="changePassword()">Update Password</button>
                </div>
                <hr class="section-divider">
                <div class="new-product-grid">
                    <div class="new-product-field">
                        <label class="field-label">Current Password</label>
                        <input type="password" id="pass-current" class="inv-input" style="width:100%;"
                            placeholder="Enter current password">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">New Password</label>
                        <input type="password" id="pass-new" class="inv-input" style="width:100%;"
                            placeholder="Enter new password">
                    </div>
                    <div class="new-product-field">
                        <label class="field-label">Confirm New Password</label>
                        <input type="password" id="pass-confirm" class="inv-input" style="width:100%;"
                            placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Data Management</span>
                </div>
                <hr class="section-divider">
                
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <a href="settings.php?action=export_orders" class="btn-sm" style="background: white; color: #8b6340; border: 1px solid #8b6340; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px;">
                        📥 Export Orders as CSV
                    </a>
                    
                    <button class="btn-sm" style="background: white; color: #c0392b; border: 1px solid #c0392b; padding: 8px 16px;" onclick="clearOrders()">
                        🗑️ Clear Order History
                    </button>
                    
                    <button class="btn-sm" style="background: white; color: #c0392b; border: 1px solid #c0392b; padding: 8px 16px;" onclick="resetInventory()">
                        🔄 Reset Inventory
                    </button>
                </div>
                <p style="font-family: sans-serif; font-size: 12px; color: #c0392b; margin-top: 15px;">
                    ⚠ Danger zone — these actions cannot be undone. Users (Admin/Riders/Customers) will NOT be deleted.
                </p>
            </div>

        </div>
    </main>

    <script>
        function showLoading(text) {
            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        // --- Core Settings DB Function ---
        function pushSettingsToDatabase(settingsObj) {
            showLoading('Saving Settings...');

            const formData = new URLSearchParams();
            formData.append('action', 'save_settings');
            
            for (const key in settingsObj) {
                formData.append(`settings[${key}]`, settingsObj[key]);
            }

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        // --- Save Form Functions ---
        function saveStoreInfo() {
            const info = {
                'store_name': document.getElementById('store-name').value.trim(),
                'store_address': document.getElementById('store-address').value.trim(),
                'store_contact': document.getElementById('store-contact').value.trim(),
                'store_email': document.getElementById('store-email').value.trim()
            };
            pushSettingsToDatabase(info);
        }

        function saveReceiptSettings() {
            const receipt = {
                'receipt_prefix': document.getElementById('receipt-prefix').value.trim(),
                'receipt_header': document.getElementById('receipt-header').value.trim(),
                'receipt_footer': document.getElementById('receipt-footer').value.trim()
            };
            pushSettingsToDatabase(receipt);
        }

        function saveAlerts() {
            const alerts = {
                'alert_threshold': document.getElementById('alert-threshold').value
            };
            pushSettingsToDatabase(alerts);
        }

        // --- 🔐 CHANGE PASSWORD FUNCTION ---
        function changePassword() {
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

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                    document.getElementById('pass-current').value = '';
                    document.getElementById('pass-new').value = '';
                    document.getElementById('pass-confirm').value = '';
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        // --- Database Data Management Functions ---
        function clearOrders() {
            if (!confirm('Are you ABSOLUTELY sure you want to clear all order and return history? This will permanently delete transactions. User accounts will NOT be affected.')) return;
            
            showLoading('Wiping Order Data...');

            const formData = new URLSearchParams();
            formData.append('action', 'clear_orders');

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }

        function resetInventory() {
            if (!confirm('Are you ABSOLUTELY sure you want to reset the inventory? All added products will be permanently deleted.')) return;
            
            showLoading('Resetting Inventory...');

            const formData = new URLSearchParams();
            formData.append('action', 'reset_inventory');

            fetch('settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert('Connection error. Please try again.');
            });
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>