<<<<<<< HEAD
<?php
// export_returns.php — No external libraries needed, works on any XAMPP setup
include 'db.php';

// Build WHERE clause (same logic as returns.php)
$where_clause = "";
$filename_suffix = "all";

if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
    $to_date   = mysqli_real_escape_string($conn, $_GET['to_date']);
    $where_clause  = "WHERE date BETWEEN '$from_date' AND '$to_date'";
    $filename_suffix = "{$from_date}_to_{$to_date}";
}

// Fetch returns
$returns_result = $conn->query(
    "SELECT date, product, quantity, loss, reason, notes FROM returns $where_clause ORDER BY date DESC, id DESC"
);

$total_result = $conn->query(
    "SELECT COALESCE(SUM(loss),0) as tl, COUNT(*) as tc FROM returns $where_clause"
);
$totals = $total_result->fetch_assoc();

// Stream CSV file — opens natively in Excel
$filename = "returns_report_{$filename_suffix}.csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// UTF-8 BOM so Excel opens with correct encoding
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Title rows
fputcsv($out, ['La Seanale Bake Shop - Returns Report']);
fputcsv($out, ['Generated:', date('Y-m-d H:i:s')]);
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    fputcsv($out, ['Period:', $_GET['from_date'] . ' to ' . $_GET['to_date']]);
} else {
    fputcsv($out, ['Period:', 'All records']);
}
fputcsv($out, []); // blank row

// Column headers
fputcsv($out, ['Date', 'Product', 'Quantity', 'Loss (PHP)', 'Reason', 'Notes']);

// Data rows
$row_count  = 0;
$total_qty  = 0;
$total_loss = 0;

while ($row = $returns_result->fetch_assoc()) {
    $reason = ucfirst(str_replace('_', ' ', $row['reason']));
    fputcsv($out, [
        $row['date'],
        $row['product'],
        (int)$row['quantity'],
        number_format((float)$row['loss'], 2, '.', ''),
        $reason,
        $row['notes'] ?: '',
    ]);
    $row_count++;
    $total_qty  += (int)$row['quantity'];
    $total_loss += (float)$row['loss'];
}

// Totals row
fputcsv($out, []); // blank separator
fputcsv($out, [
    'TOTAL',
    "$row_count record(s)",
    $total_qty,
    number_format($total_loss, 2, '.', ''),
    '',
    '',
]);

fclose($out);
=======
<?php
// export_returns.php — No external libraries needed, works on any XAMPP setup
include 'db.php';

// Build WHERE clause (same logic as returns.php)
$where_clause = "";
$filename_suffix = "all";

if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
    $to_date   = mysqli_real_escape_string($conn, $_GET['to_date']);
    $where_clause  = "WHERE date BETWEEN '$from_date' AND '$to_date'";
    $filename_suffix = "{$from_date}_to_{$to_date}";
}

// Fetch returns
$returns_result = $conn->query(
    "SELECT date, product, quantity, loss, reason, notes FROM returns $where_clause ORDER BY date DESC, id DESC"
);

$total_result = $conn->query(
    "SELECT COALESCE(SUM(loss),0) as tl, COUNT(*) as tc FROM returns $where_clause"
);
$totals = $total_result->fetch_assoc();

// Stream CSV file — opens natively in Excel
$filename = "returns_report_{$filename_suffix}.csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// UTF-8 BOM so Excel opens with correct encoding
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Title rows
fputcsv($out, ['La Seanale Bake Shop - Returns Report']);
fputcsv($out, ['Generated:', date('Y-m-d H:i:s')]);
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    fputcsv($out, ['Period:', $_GET['from_date'] . ' to ' . $_GET['to_date']]);
} else {
    fputcsv($out, ['Period:', 'All records']);
}
fputcsv($out, []); // blank row

// Column headers
fputcsv($out, ['Date', 'Product', 'Quantity', 'Loss (PHP)', 'Reason', 'Notes']);

// Data rows
$row_count  = 0;
$total_qty  = 0;
$total_loss = 0;

while ($row = $returns_result->fetch_assoc()) {
    $reason = ucfirst(str_replace('_', ' ', $row['reason']));
    fputcsv($out, [
        $row['date'],
        $row['product'],
        (int)$row['quantity'],
        number_format((float)$row['loss'], 2, '.', ''),
        $reason,
        $row['notes'] ?: '',
    ]);
    $row_count++;
    $total_qty  += (int)$row['quantity'];
    $total_loss += (float)$row['loss'];
}

// Totals row
fputcsv($out, []); // blank separator
fputcsv($out, [
    'TOTAL',
    "$row_count record(s)",
    $total_qty,
    number_format($total_loss, 2, '.', ''),
    '',
    '',
]);

fclose($out);
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
exit;