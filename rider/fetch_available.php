<<<<<<< HEAD
<?php
include 'db.php';
header('Content-Type: application/json');

// Fetch orders that are online, pending, and have no rider assigned yet
$query = "SELECT id, customer_name, delivery_address, total_price 
          FROM orders 
          WHERE order_type = 'online' 
          AND status = 'pending' 
          AND (rider_id IS NULL OR rider_id = 0)
          ORDER BY order_date ASC";

$result = $conn->query($query);
$orders = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Send the data back to the Javascript fetch request
echo json_encode($orders);
=======
<?php
include 'db.php';
header('Content-Type: application/json');

// Fetch orders that are online, pending, and have no rider assigned yet
$query = "SELECT id, customer_name, delivery_address, total_price 
          FROM orders 
          WHERE order_type = 'online' 
          AND status = 'pending' 
          AND (rider_id IS NULL OR rider_id = 0)
          ORDER BY order_date ASC";

$result = $conn->query($query);
$orders = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Send the data back to the Javascript fetch request
echo json_encode($orders);
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>