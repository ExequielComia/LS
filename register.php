<<<<<<< HEAD
<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include 'db.php';

$fname    = trim($_POST['fname'] ?? '');
$lname    = trim($_POST['lname'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$address  = trim($_POST['address'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm'] ?? '';

// ── Validation ──
if (!$fname || !$lname)         { echo json_encode(['success' => false, 'message' => 'First and last name are required.']); exit; }
if (!$dob)                       { echo json_encode(['success' => false, 'message' => 'Date of birth is required.']); exit; }
if (!$address || strlen($address) < 10) { echo json_encode(['success' => false, 'message' => 'Please enter a complete address.']); exit; }
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']); exit; }
if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') { echo json_encode(['success' => false, 'message' => 'Please enter a valid PH mobile number.']); exit; }
if (!$password || strlen($password) < 6) { echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']); exit; }
if ($password !== $confirm)      { echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit; }

// ── Age check ──
$birth = new DateTime($dob);
$today = new DateTime();
$age   = $today->diff($birth)->y;
if ($birth > $today)  { echo json_encode(['success' => false, 'message' => 'Date of birth cannot be in the future.']); exit; }
if ($age < 13)        { echo json_encode(['success' => false, 'message' => 'You must be at least 13 years old.']); exit; }

// ── Check duplicate email ──
$stmt = $conn->prepare("SELECT customer_id FROM customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    exit;
}
$stmt->close();

// ── Hash password and insert ──
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO customer (fname, lname, password, dob, address, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $fname, $lname, $hashed, $dob, $address, $email, $phone);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account created! Welcome, ' . $fname . '!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}

$stmt->close();
$conn->close();
=======
<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include 'db.php';

$fname    = trim($_POST['fname'] ?? '');
$lname    = trim($_POST['lname'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$address  = trim($_POST['address'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm'] ?? '';

// ── Validation ──
if (!$fname || !$lname)         { echo json_encode(['success' => false, 'message' => 'First and last name are required.']); exit; }
if (!$dob)                       { echo json_encode(['success' => false, 'message' => 'Date of birth is required.']); exit; }
if (!$address || strlen($address) < 10) { echo json_encode(['success' => false, 'message' => 'Please enter a complete address.']); exit; }
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']); exit; }
if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') { echo json_encode(['success' => false, 'message' => 'Please enter a valid PH mobile number.']); exit; }
if (!$password || strlen($password) < 6) { echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']); exit; }
if ($password !== $confirm)      { echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit; }

// ── Age check ──
$birth = new DateTime($dob);
$today = new DateTime();
$age   = $today->diff($birth)->y;
if ($birth > $today)  { echo json_encode(['success' => false, 'message' => 'Date of birth cannot be in the future.']); exit; }
if ($age < 13)        { echo json_encode(['success' => false, 'message' => 'You must be at least 13 years old.']); exit; }

// ── Check duplicate email ──
$stmt = $conn->prepare("SELECT customer_id FROM customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    exit;
}
$stmt->close();

// ── Hash password and insert ──
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO customer (fname, lname, password, dob, address, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $fname, $lname, $hashed, $dob, $address, $email, $phone);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account created! Welcome, ' . $fname . '!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}

$stmt->close();
$conn->close();
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>