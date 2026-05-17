<<<<<<< HEAD
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 

// Fetch current name and picture
$query = mysqli_query($conn, "SELECT fullname, profile_pic FROM riders WHERE id = $rider_id");
$rider_data = mysqli_fetch_assoc($query);

$rider_name = $rider_data['fullname'] ?? 'Guest Rider';
$profile_pic = $rider_data['profile_pic'] ?? null;

// Initials Fallback
$name_parts = explode(' ', trim($rider_name));
$initials = count($name_parts) >= 2 ? strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1)) : strtoupper(substr($rider_name, 0, 2));
?>

<nav class="heading" style="display: flex; flex-direction: column; height: 100vh;">
    <div>
        <img src="../assets/hero.png" alt="Logo" class="logo" style="max-width: 20vh; display: block; margin: 0 auto;">
    </div>

    <div style="flex: 1;">
        <div class="nav-section">
            <ul class="nav-button">
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            </ul>
            <ul class="nav-button">
                <li><a href="live_order_map.php"><i class="fa-solid fa-coins"></i> Map</a></li>
            </ul>
        </div>
    </div>

    <div style="flex-shrink: 0;">
        <hr class="sb-divider-line">
        
        <div class="sb-profile-row">
            <?php if ($profile_pic): ?>
                <img src="<?= htmlspecialchars($profile_pic) ?>" class="sb-profile-pic" style="object-fit: cover;">
            <?php else: ?>
                <div class="sb-profile-pic"><?= htmlspecialchars($initials) ?></div>
            <?php endif; ?>
            
            <div class="sb-profile-info">
                <p class="sb-profile-name"><?= htmlspecialchars($rider_name) ?></p>
            </div>
        </div>
        
        <ul class="nav-button">
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <ul class="nav-button">
            <li><a href="logout.php" style="color: #e07070;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
=======
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 

// Fetch current name and picture
$query = mysqli_query($conn, "SELECT fullname, profile_pic FROM riders WHERE id = $rider_id");
$rider_data = mysqli_fetch_assoc($query);

$rider_name = $rider_data['fullname'] ?? 'Guest Rider';
$profile_pic = $rider_data['profile_pic'] ?? null;

// Initials Fallback
$name_parts = explode(' ', trim($rider_name));
$initials = count($name_parts) >= 2 ? strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1)) : strtoupper(substr($rider_name, 0, 2));
?>

<nav class="heading" style="display: flex; flex-direction: column; height: 100vh;">
    <div>
        <img src="../assets/hero.png" alt="Logo" class="logo" style="max-width: 20vh; display: block; margin: 0 auto;">
    </div>

    <div style="flex: 1;">
        <div class="nav-section">
            <ul class="nav-button">
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            </ul>
            <ul class="nav-button">
                <li><a href="live_order_map.php"><i class="fa-solid fa-coins"></i> Map</a></li>
            </ul>
        </div>
    </div>

    <div style="flex-shrink: 0;">
        <hr class="sb-divider-line">
        
        <div class="sb-profile-row">
            <?php if ($profile_pic): ?>
                <img src="<?= htmlspecialchars($profile_pic) ?>" class="sb-profile-pic" style="object-fit: cover;">
            <?php else: ?>
                <div class="sb-profile-pic"><?= htmlspecialchars($initials) ?></div>
            <?php endif; ?>
            
            <div class="sb-profile-info">
                <p class="sb-profile-name"><?= htmlspecialchars($rider_name) ?></p>
            </div>
        </div>
        
        <ul class="nav-button">
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <ul class="nav-button">
            <li><a href="logout.php" style="color: #e07070;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</nav>