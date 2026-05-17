<<<<<<< HEAD
<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Check the riders table
    $query = "SELECT id, fullname, password, status FROM riders WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $rider = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $rider['password'])) {
            
            // Check if Admin approved them
            if ($rider['status'] === 'pending') {
                echo json_encode(['success' => false, 'message' => 'Your account is still pending admin verification.']);
                exit;
            } elseif ($rider['status'] === 'rejected') {
                echo json_encode(['success' => false, 'message' => 'Your application was rejected.']);
                exit;
            }

            // Success: Set session variables
            $_SESSION['rider_id'] = $rider['id'];
            $_SESSION['rider_name'] = $rider['fullname'];
            $_SESSION['role'] = 'rider';

            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Rider account not found.']);
    }
}
=======
<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Check the riders table
    $query = "SELECT id, fullname, password, status FROM riders WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $rider = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $rider['password'])) {
            
            // Check if Admin approved them
            if ($rider['status'] === 'pending') {
                echo json_encode(['success' => false, 'message' => 'Your account is still pending admin verification.']);
                exit;
            } elseif ($rider['status'] === 'rejected') {
                echo json_encode(['success' => false, 'message' => 'Your application was rejected.']);
                exit;
            }

            // Success: Set session variables
            $_SESSION['rider_id'] = $rider['id'];
            $_SESSION['rider_name'] = $rider['fullname'];
            $_SESSION['role'] = 'rider';

            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Rider account not found.']);
    }
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>