<<<<<<< HEAD
<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$rider_id = $_SESSION['rider_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact']);
        $vehicle = mysqli_real_escape_string($conn, $_POST['vehicle_type']);

        $query = "UPDATE riders SET fullname='$fullname', email='$email', contact='$contact', vehicle_type='$vehicle' WHERE id=$rider_id";
        if(mysqli_query($conn, $query)) {
            $_SESSION['rider_name'] = $fullname; // Update session name
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }

    elseif ($action === 'update_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $file_name = time() . '_' . basename($_FILES['profile_pic']['name']);
            $file_path = $dir . $file_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                mysqli_query($conn, "UPDATE riders SET profile_pic='$file_path' WHERE id=$rider_id");
                $_SESSION['profile_pic'] = $file_path; // Update session
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload.']);
            }
        }
    }

    elseif ($action === 'update_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];

        $result = mysqli_query($conn, "SELECT password FROM riders WHERE id=$rider_id");
        $rider = mysqli_fetch_assoc($result);

        if (password_verify($current, $rider['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE riders SET password='$hashed' WHERE id=$rider_id");
            echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        }
    }
}
=======
<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$rider_id = $_SESSION['rider_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact']);
        $vehicle = mysqli_real_escape_string($conn, $_POST['vehicle_type']);

        $query = "UPDATE riders SET fullname='$fullname', email='$email', contact='$contact', vehicle_type='$vehicle' WHERE id=$rider_id";
        if(mysqli_query($conn, $query)) {
            $_SESSION['rider_name'] = $fullname; // Update session name
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }

    elseif ($action === 'update_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $file_name = time() . '_' . basename($_FILES['profile_pic']['name']);
            $file_path = $dir . $file_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                mysqli_query($conn, "UPDATE riders SET profile_pic='$file_path' WHERE id=$rider_id");
                $_SESSION['profile_pic'] = $file_path; // Update session
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload.']);
            }
        }
    }

    elseif ($action === 'update_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];

        $result = mysqli_query($conn, "SELECT password FROM riders WHERE id=$rider_id");
        $rider = mysqli_fetch_assoc($result);

        if (password_verify($current, $rider['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE riders SET password='$hashed' WHERE id=$rider_id");
            echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        }
    }
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>