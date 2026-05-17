<<<<<<< HEAD
<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Ensure only valid statuses are passed
    if (!in_array($status, ['verified', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update the database. Also updates the date_verified if it's being verified.
    if ($status === 'verified') {
        $query = "UPDATE riders SET status = '$status', date_verified = CURDATE() WHERE id = $id";
    } else {
        $query = "UPDATE riders SET status = '$status' WHERE id = $id";
    }

    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
=======
<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Ensure only valid statuses are passed
    if (!in_array($status, ['verified', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update the database. Also updates the date_verified if it's being verified.
    if ($status === 'verified') {
        $query = "UPDATE riders SET status = '$status', date_verified = CURDATE() WHERE id = $id";
    } else {
        $query = "UPDATE riders SET status = '$status' WHERE id = $id";
    }

    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>