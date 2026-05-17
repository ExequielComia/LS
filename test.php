<<<<<<< HEAD
<?php
// Turn on ALL error reporting so nothing can hide
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

echo "<h2>🕵️‍♂️ Database Diagnostic Test</h2>";

// 1. Let's try forcing a log directly into the database
$sql = "INSERT INTO activity_logs (user_name, user_role, action, details) 
        VALUES ('Test User', 'System', 'Diagnostic Test', 'Testing if MySQL allows this.')";

if ($conn->query($sql)) {
    echo "<h3 style='color:green;'>✅ TEST 1 PASSED: Direct Insert worked!</h3>";
} else {
    echo "<h3 style='color:red;'>❌ TEST 1 FAILED! MySQL Error: " . $conn->error . "</h3>";
}

// 2. Let's test the helper function
echo "<hr>";
logAction($conn, 'Function Test', 'Testing the logAction function directly');
echo "<h3 style='color:blue;'>✅ TEST 2 COMPLETE: The logAction function ran without crashing.</h3>";
=======
<?php
// Turn on ALL error reporting so nothing can hide
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

echo "<h2>🕵️‍♂️ Database Diagnostic Test</h2>";

// 1. Let's try forcing a log directly into the database
$sql = "INSERT INTO activity_logs (user_name, user_role, action, details) 
        VALUES ('Test User', 'System', 'Diagnostic Test', 'Testing if MySQL allows this.')";

if ($conn->query($sql)) {
    echo "<h3 style='color:green;'>✅ TEST 1 PASSED: Direct Insert worked!</h3>";
} else {
    echo "<h3 style='color:red;'>❌ TEST 1 FAILED! MySQL Error: " . $conn->error . "</h3>";
}

// 2. Let's test the helper function
echo "<hr>";
logAction($conn, 'Function Test', 'Testing the logAction function directly');
echo "<h3 style='color:blue;'>✅ TEST 2 COMPLETE: The logAction function ran without crashing.</h3>";
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>