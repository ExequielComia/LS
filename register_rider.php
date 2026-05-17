<<<<<<< HEAD
<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Catching the merged name from JavaScript
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    
    // Hash the password for security
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Default values for new riders
    $status = 'pending'; 
    $idtype = 'Government ID'; 
    $submitted = date('Y-m-d'); // Your DB requires a 'submitted' date

    // Handle File Upload
    $upload_dir = 'uploads/ids/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); 
    }

    $id_image = '';
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['id_image']['tmp_name'];
        // Create a unique file name so images don't overwrite each other
        $file_name = time() . '_' . basename($_FILES['id_image']['name']);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            $id_image = $file_name; // Just store the file name to the DB
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload ID image.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID image is required.']);
        exit;
    }

    // Check if email already exists
    $check_query = "SELECT id FROM riders WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }

    // Insert into database matching your EXACT columns
    $query = "INSERT INTO riders (fullname, contact, idtype, submitted, status, email, password, id_image, vehicle_type) 
              VALUES ('$fullname', '$contact', '$idtype', '$submitted', '$status', '$email', '$password', '$id_image', '$vehicle_type')";

    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Application submitted! Please wait for admin approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
=======
<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Catching the merged name from JavaScript
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    
    // Hash the password for security
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Default values for new riders
    $status = 'pending'; 
    $idtype = 'Government ID'; 
    $submitted = date('Y-m-d'); // Your DB requires a 'submitted' date

    // Handle File Upload
    $upload_dir = 'uploads/ids/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); 
    }

    $id_image = '';
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['id_image']['tmp_name'];
        // Create a unique file name so images don't overwrite each other
        $file_name = time() . '_' . basename($_FILES['id_image']['name']);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            $id_image = $file_name; // Just store the file name to the DB
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload ID image.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID image is required.']);
        exit;
    }

    // Check if email already exists
    $check_query = "SELECT id FROM riders WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }

    // Insert into database matching your EXACT columns
    $query = "INSERT INTO riders (fullname, contact, idtype, submitted, status, email, password, id_image, vehicle_type) 
              VALUES ('$fullname', '$contact', '$idtype', '$submitted', '$status', '$email', '$password', '$id_image', '$vehicle_type')";

    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Application submitted! Please wait for admin approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>