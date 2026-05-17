<<<<<<< HEAD
<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $rider_id = (int)$_POST['rider_id'];

    // STRICT CHECK: Make sure the order is STILL pending. 
    // This prevents two riders from accidentally accepting the exact same order at the same time.
    $check_query = "SELECT status FROM orders WHERE id = $order_id AND status = 'pending'";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Update the order to 'processing' and assign this rider's ID
        $update_query = "UPDATE orders 
                         SET status = 'processing', rider_id = $rider_id 
                         WHERE id = $order_id";
                         
        if ($conn->query($update_query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error. Could not accept order.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Too late! Another rider already accepted this order, or it was cancelled.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
=======
<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $rider_id = (int)$_POST['rider_id'];

    // STRICT CHECK: Make sure the order is STILL pending. 
    // This prevents two riders from accidentally accepting the exact same order at the same time.
    $check_query = "SELECT status FROM orders WHERE id = $order_id AND status = 'pending'";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Update the order to 'processing' and assign this rider's ID
        $update_query = "UPDATE orders 
                         SET status = 'processing', rider_id = $rider_id 
                         WHERE id = $order_id";
                         
        if ($conn->query($update_query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error. Could not accept order.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Too late! Another rider already accepted this order, or it was cancelled.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>