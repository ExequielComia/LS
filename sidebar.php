<<<<<<< HEAD
<?php

$sidebar_cust_id = $_SESSION['customer_id'];

// ✅ Catch the background request when the customer hovers to mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notifs_read') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $sidebar_cust_id");
    exit; 
}

// Fetch the customer's name and avatar using the correct column names (fname, lname, customer_id)
$sb_query = "SELECT fname, lname, avatar FROM customer WHERE customer_id = $sidebar_cust_id";
$sb_result = $conn->query($sb_query);
$sb_cust = $sb_result ? $sb_result->fetch_assoc() : null;

$sb_fname = $sb_cust['fname'] ?? 'Juan';
$sb_lname = $sb_cust['lname'] ?? 'Dela Cruz';
$sb_fullname = trim($sb_fname . ' ' . $sb_lname);
$sb_initials = strtoupper(substr($sb_fname, 0, 1) . substr($sb_lname, 0, 1));
$sb_avatar = $sb_cust['avatar'] ?? '';

// ✅ Fetch Unread Notification Count
$count_query = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE customer_id = $sidebar_cust_id AND is_read = 0");
$unread_count = $count_query ? $count_query->fetch_assoc()['unread'] : 0;

// ✅ Fetch Recent Notifications (Top 10)
$notifs_query = $conn->query("SELECT message, is_read, created_at FROM notifications WHERE customer_id = $sidebar_cust_id ORDER BY created_at DESC LIMIT 10");
?>

<style>
    /* ✅ NEW: Top Right Notification Styling */
    .top-right-notif {
        position: fixed;
        top: 25px;
        right: 35px;
        z-index: 9999;
    }
    
    .notif-bell-icon {
        color: saddlebrown;
        font-size: 20px;
        position: relative;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: #fdfaf6;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: 0.2s;
    }
    
    .notif-bell-icon:hover { background: #f5eedf; }
    
    .notif-badge {
        background-color: #c0392b; color: white; padding: 2px 6px;
        border-radius: 50%; font-size: 10px; font-weight: bold;
        position: absolute; top: -2px; right: -4px; border: 2px solid white;
    }

    .notif-dropdown {
        display: none; /* Hidden by default */
        position: absolute; right: 0; top: 55px; width: 320px;
        background: white; border: 1px solid #e8dcc8; border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15); z-index: 1000;
        max-height: 400px; overflow-y: auto; font-family: sans-serif;
    }
    
    .notif-dropdown h4 { padding: 12px 15px; margin: 0; border-bottom: 1px solid #f0f0f0; color: saddlebrown; font-weight: bold; font-size: 14px; background: #fdfaf6; }
    .notif-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: block; text-decoration: none; color: #3b2208; font-size: 12px; line-height: 1.4; transition: 0.2s; }
    .notif-item.unread { background-color: #fdf8f0; font-weight: bold; border-left: 3px solid saddlebrown; }
    .notif-item:hover { background-color: #f5f5f5; }
    .notif-date { font-size: 10px; color: #888; margin-top: 4px; display: block; font-weight: normal; }
    .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 12px; }

    /* ✅ THE MAGIC HOVER EFFECT: Shows dropdown when hovering over the container */
    .top-right-notif:hover .notif-dropdown {
        display: block;
    }
</style>

<!-- ✅ THE TOP RIGHT NOTIFICATION BELL -->
<div class="top-right-notif" id="notif-container">
    <a href="#" class="notif-bell-icon">
        <i class="fa-solid fa-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notif-badge" id="notif-badge-count"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>

    <!-- The Hover Dropdown Menu -->
    <div class="notif-dropdown" id="notif-dropdown-menu">
        <h4>Notifications</h4>
        <div>
            <?php
            if ($notifs_query && $notifs_query->num_rows > 0) {
                while ($notif = $notifs_query->fetch_assoc()) {
                    $is_unread = $notif['is_read'] == 0 ? 'unread' : '';
                    $formatted_date = date('M d, Y - h:i A', strtotime($notif['created_at']));
                    echo "<div class='notif-item $is_unread'>
                            {$notif['message']}
                            <span class='notif-date'>$formatted_date</span>
                          </div>";
                }
            } else {
                echo "<div class='notif-empty'>No new notifications.</div>";
            }
            ?>
        </div>
    </div>
</div>

<!-- ✅ THE ORIGINAL LEFT SIDEBAR (Unchanged) -->
<nav class="heading" style="display: flex; flex-direction: column; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; width: 220px;">
    <div>
        <img src="./assets/hero.png" alt="Logo" class="logo" style="max-width: 20vh; display: block; margin: 0 auto;">
        
        <div class="nav-section">
            <ul class="nav-button">
                <li><a href="dashboard.php"><i class="fa-solid fa-store"></i> Shop list</a></li>
            </ul>
            <ul class="nav-button">
                <li><a href="order_list.php"><i class="fa-solid fa-clipboard-list"></i> Order list</a></li>
            </ul>
        </div>
    </div>

    <div class="nav-section" style="margin-bottom: 1rem;">
        <hr class="sb-divider-line">
        
        <div class="sb-profile-row">
            <div class="sb-profile-pic" style="overflow: hidden; display: flex; justify-content: center; align-items: center; background: saddlebrown; color: white;">
                <?php if (!empty($sb_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($sb_avatar); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo $sb_initials; ?>
                <?php endif; ?>
            </div>
            <div class="sb-profile-info">
                <p class="sb-profile-name"><?php echo htmlspecialchars($sb_fullname); ?></p>
            </div>
        </div>

        <ul class="nav-button">
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <ul class="nav-button">
            <li><a href="logout.php" style="color: #e07070;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<script>
    // ✅ Automatically mark notifications as read when the user hovers over the bell
    document.getElementById('notif-container').addEventListener('mouseenter', function() {
        const badge = document.getElementById('notif-badge-count');
        
        // If there's an active red badge, clear it in the database
        if (badge) {
            badge.style.display = 'none'; // Hide the red dot visually instantly
            
            const formData = new URLSearchParams();
            formData.append('action', 'mark_notifs_read');

            // Send silent background request to update the database
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            }).catch(err => console.error('Notification update failed:', err));

            // Remove the bold styling from the list items visually
            document.querySelectorAll('.notif-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
        }
    }, { once: true }); // Use {once: true} so it only triggers the database request the very first time they hover
=======
<?php

$sidebar_cust_id = $_SESSION['customer_id'];

// ✅ Catch the background request when the customer hovers to mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notifs_read') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $sidebar_cust_id");
    exit; 
}

// Fetch the customer's name and avatar using the correct column names (fname, lname, customer_id)
$sb_query = "SELECT fname, lname, avatar FROM customer WHERE customer_id = $sidebar_cust_id";
$sb_result = $conn->query($sb_query);
$sb_cust = $sb_result ? $sb_result->fetch_assoc() : null;

$sb_fname = $sb_cust['fname'] ?? 'Juan';
$sb_lname = $sb_cust['lname'] ?? 'Dela Cruz';
$sb_fullname = trim($sb_fname . ' ' . $sb_lname);
$sb_initials = strtoupper(substr($sb_fname, 0, 1) . substr($sb_lname, 0, 1));
$sb_avatar = $sb_cust['avatar'] ?? '';

// ✅ Fetch Unread Notification Count
$count_query = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE customer_id = $sidebar_cust_id AND is_read = 0");
$unread_count = $count_query ? $count_query->fetch_assoc()['unread'] : 0;

// ✅ Fetch Recent Notifications (Top 10)
$notifs_query = $conn->query("SELECT message, is_read, created_at FROM notifications WHERE customer_id = $sidebar_cust_id ORDER BY created_at DESC LIMIT 10");
?>

<style>
    /* ✅ NEW: Top Right Notification Styling */
    .top-right-notif {
        position: fixed;
        top: 25px;
        right: 35px;
        z-index: 9999;
    }
    
    .notif-bell-icon {
        color: saddlebrown;
        font-size: 20px;
        position: relative;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: #fdfaf6;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: 0.2s;
    }
    
    .notif-bell-icon:hover { background: #f5eedf; }
    
    .notif-badge {
        background-color: #c0392b; color: white; padding: 2px 6px;
        border-radius: 50%; font-size: 10px; font-weight: bold;
        position: absolute; top: -2px; right: -4px; border: 2px solid white;
    }

    .notif-dropdown {
        display: none; /* Hidden by default */
        position: absolute; right: 0; top: 55px; width: 320px;
        background: white; border: 1px solid #e8dcc8; border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15); z-index: 1000;
        max-height: 400px; overflow-y: auto; font-family: sans-serif;
    }
    
    .notif-dropdown h4 { padding: 12px 15px; margin: 0; border-bottom: 1px solid #f0f0f0; color: saddlebrown; font-weight: bold; font-size: 14px; background: #fdfaf6; }
    .notif-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: block; text-decoration: none; color: #3b2208; font-size: 12px; line-height: 1.4; transition: 0.2s; }
    .notif-item.unread { background-color: #fdf8f0; font-weight: bold; border-left: 3px solid saddlebrown; }
    .notif-item:hover { background-color: #f5f5f5; }
    .notif-date { font-size: 10px; color: #888; margin-top: 4px; display: block; font-weight: normal; }
    .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 12px; }

    /* ✅ THE MAGIC HOVER EFFECT: Shows dropdown when hovering over the container */
    .top-right-notif:hover .notif-dropdown {
        display: block;
    }
</style>

<!-- ✅ THE TOP RIGHT NOTIFICATION BELL -->
<div class="top-right-notif" id="notif-container">
    <a href="#" class="notif-bell-icon">
        <i class="fa-solid fa-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notif-badge" id="notif-badge-count"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>

    <!-- The Hover Dropdown Menu -->
    <div class="notif-dropdown" id="notif-dropdown-menu">
        <h4>Notifications</h4>
        <div>
            <?php
            if ($notifs_query && $notifs_query->num_rows > 0) {
                while ($notif = $notifs_query->fetch_assoc()) {
                    $is_unread = $notif['is_read'] == 0 ? 'unread' : '';
                    $formatted_date = date('M d, Y - h:i A', strtotime($notif['created_at']));
                    echo "<div class='notif-item $is_unread'>
                            {$notif['message']}
                            <span class='notif-date'>$formatted_date</span>
                          </div>";
                }
            } else {
                echo "<div class='notif-empty'>No new notifications.</div>";
            }
            ?>
        </div>
    </div>
</div>

<!-- ✅ THE ORIGINAL LEFT SIDEBAR (Unchanged) -->
<nav class="heading" style="display: flex; flex-direction: column; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; width: 220px;">
    <div>
        <img src="./assets/hero.png" alt="Logo" class="logo" style="max-width: 20vh; display: block; margin: 0 auto;">
        
        <div class="nav-section">
            <ul class="nav-button">
                <li><a href="dashboard.php"><i class="fa-solid fa-store"></i> Shop list</a></li>
            </ul>
            <ul class="nav-button">
                <li><a href="order_list.php"><i class="fa-solid fa-clipboard-list"></i> Order list</a></li>
            </ul>
        </div>
    </div>

    <div class="nav-section" style="margin-bottom: 1rem;">
        <hr class="sb-divider-line">
        
        <div class="sb-profile-row">
            <div class="sb-profile-pic" style="overflow: hidden; display: flex; justify-content: center; align-items: center; background: saddlebrown; color: white;">
                <?php if (!empty($sb_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($sb_avatar); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo $sb_initials; ?>
                <?php endif; ?>
            </div>
            <div class="sb-profile-info">
                <p class="sb-profile-name"><?php echo htmlspecialchars($sb_fullname); ?></p>
            </div>
        </div>

        <ul class="nav-button">
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <ul class="nav-button">
            <li><a href="logout.php" style="color: #e07070;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<script>
    // ✅ Automatically mark notifications as read when the user hovers over the bell
    document.getElementById('notif-container').addEventListener('mouseenter', function() {
        const badge = document.getElementById('notif-badge-count');
        
        // If there's an active red badge, clear it in the database
        if (badge) {
            badge.style.display = 'none'; // Hide the red dot visually instantly
            
            const formData = new URLSearchParams();
            formData.append('action', 'mark_notifs_read');

            // Send silent background request to update the database
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            }).catch(err => console.error('Notification update failed:', err));

            // Remove the bold styling from the list items visually
            document.querySelectorAll('.notif-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
        }
    }, { once: true }); // Use {once: true} so it only triggers the database request the very first time they hover
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</script>