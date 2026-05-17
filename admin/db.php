<<<<<<< HEAD
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Helper function to easily log admin actions safely
function logAction($conn, $action, $details) {
    // Escaping strings prevents SQL crashes
    $safe_action = mysqli_real_escape_string($conn, $action);
    $safe_details = mysqli_real_escape_string($conn, $details);
    
    // Default fallback values
    $user_name = 'System/Guest';
    $user_role = 'System';

    // 🕵️‍♂️ BULLETPROOF CHECK: Is this script running inside the /admin/ folder?
    $is_admin_folder = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

    if ($is_admin_folder) {
        // If we are in the admin dashboard, force the Admin role!
        $user_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
        $user_role = 'Admin';
    } 
    // Otherwise, check for Rider or Customer sessions
    elseif (isset($_SESSION['rider_id'])) {
        $user_name = isset($_SESSION['rider_name']) ? $_SESSION['rider_name'] : 'Rider'; 
        $user_role = 'Rider';
    } 
    elseif (isset($_SESSION['customer_id'])) {
        $user_name = isset($_SESSION['fname']) ? $_SESSION['fname'] : 'Customer';
        $user_role = 'Customer';
    }
    
    // Insert into the new unified table
    $conn->query("INSERT INTO activity_logs (user_name, user_role, action, details) 
                  VALUES ('$user_name', '$user_role', '$safe_action', '$safe_details')");
}
=======
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Helper function to easily log admin actions safely
function logAction($conn, $action, $details) {
    // Escaping strings prevents SQL crashes
    $safe_action = mysqli_real_escape_string($conn, $action);
    $safe_details = mysqli_real_escape_string($conn, $details);
    
    // Default fallback values
    $user_name = 'System/Guest';
    $user_role = 'System';

    // 🕵️‍♂️ BULLETPROOF CHECK: Is this script running inside the /admin/ folder?
    $is_admin_folder = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

    if ($is_admin_folder) {
        // If we are in the admin dashboard, force the Admin role!
        $user_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
        $user_role = 'Admin';
    } 
    // Otherwise, check for Rider or Customer sessions
    elseif (isset($_SESSION['rider_id'])) {
        $user_name = isset($_SESSION['rider_name']) ? $_SESSION['rider_name'] : 'Rider'; 
        $user_role = 'Rider';
    } 
    elseif (isset($_SESSION['customer_id'])) {
        $user_name = isset($_SESSION['fname']) ? $_SESSION['fname'] : 'Customer';
        $user_role = 'Customer';
    }
    
    // Insert into the new unified table
    $conn->query("INSERT INTO activity_logs (user_name, user_role, action, details) 
                  VALUES ('$user_name', '$user_role', '$safe_action', '$safe_details')");
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>