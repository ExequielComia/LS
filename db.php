<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!function_exists('logAction')) {
    function logAction($conn, $action, $details) {
        $safe_action = mysqli_real_escape_string($conn, $action);
        $safe_details = mysqli_real_escape_string($conn, $details);
        
        $user_name = 'System/Guest';
        $user_role = 'System';

        $is_admin_folder = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_folder) {
            $user_name = $_SESSION['admin_name'] ?? 'Admin';
            $user_role = 'Admin';
        } elseif (isset($_SESSION['rider_id'])) {
            $user_name = $_SESSION['rider_name'] ?? 'Rider'; 
            $user_role = 'Rider';
        } elseif (isset($_SESSION['customer_id']) || isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
            $active_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];
            $user_name = $_SESSION['fname'] ?? 'Customer';
            $user_role = 'Customer';
            
            if ($user_name === 'Customer' && $active_id) {
                $name_check = $conn->query("SELECT fname FROM customer WHERE customer_id = '$active_id' LIMIT 1");
                if ($name_check && $row = $name_check->fetch_assoc()) {
                    $user_name = $row['fname'];
                }
            }
        }
        
        $safe_user_name = mysqli_real_escape_string($conn, $user_name);
        $safe_user_role = mysqli_real_escape_string($conn, $user_role);
        
        $sql = "INSERT INTO activity_logs (user_name, user_role, action, details) 
                VALUES ('$safe_user_name', '$safe_user_role', '$safe_action', '$safe_details')";
                
        if (!$conn->query($sql)) {
            $error_message = date('Y-m-d H:i:s') . " - DB ERROR: " . $conn->error . "\n";
            file_put_contents(__DIR__ . '/db_error.txt', $error_message, FILE_APPEND);
        }
    }
}
?>