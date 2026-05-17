<<<<<<< HEAD
<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$query = "SELECT id_image FROM riders WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row || empty($row['id_image'])) {
    http_response_code(404);
    echo "No image found for this rider.";
    exit;
}

$filename = basename($row['id_image']);
$filepath = __DIR__ . '/../uploads/ids/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "Image not found: " . $filepath;
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filepath);
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
readfile($filepath);
exit;
=======
<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$query = "SELECT id_image FROM riders WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row || empty($row['id_image'])) {
    http_response_code(404);
    echo "No image found for this rider.";
    exit;
}

$filename = basename($row['id_image']);
$filepath = __DIR__ . '/../uploads/ids/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "Image not found: " . $filepath;
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filepath);
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
readfile($filepath);
exit;
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
?>