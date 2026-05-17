<<<<<<< HEAD
<?php
session_start();
include 'db.php';

header('Content-Type: application/json');
ob_start();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, fname, password FROM customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
    exit;
}

$stmt->bind_result($customer_id, $fname, $hashed);
$stmt->fetch();

if (!password_verify($password, $hashed)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

$_SESSION['customer_id'] = $customer_id;
$_SESSION['fname']       = $fname;

ob_clean();
echo json_encode(['success' => true, 'message' => 'Welcome, ' . $fname . '!']);

$stmt->close();
$conn->close();
=======
<?php
session_start();
include 'db.php';

header('Content-Type: application/json');
ob_start();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id, fname, password FROM customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
    exit;
}

$stmt->bind_result($customer_id, $fname, $hashed);
$stmt->fetch();

if (!password_verify($password, $hashed)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

$_SESSION['customer_id'] = $customer_id;
$_SESSION['fname']       = $fname;

ob_clean();
echo json_encode(['success' => true, 'message' => 'Welcome, ' . $fname . '!']);

$stmt->close();
$conn->close();
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>