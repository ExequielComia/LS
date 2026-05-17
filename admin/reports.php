<<<<<<< HEAD
<?php
include 'db.php';

// 2. Handle AJAX request for filtering (MUST be before ANY HTML output)
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $where_clause = "";

    if (isset($_GET['from_date'], $_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
        $to_date = mysqli_real_escape_string($conn, $_GET['to_date']);
        $where_clause = "WHERE DATE(date) >= '$from_date' AND DATE(date) <= '$to_date'";
    }

    $returns_query = "SELECT * FROM returns $where_clause ORDER BY date DESC, id DESC";
    $returns_result = $conn->query($returns_query);

    $returns_data = [];
    while ($row = $returns_result->fetch_assoc()) {
        $returns_data[] = $row;
    }

    $total_losses_query = "SELECT COALESCE(SUM(loss), 0) as total_loss FROM returns $where_clause";
    $total_losses_result = $conn->query($total_losses_query);
    $total_losses = $total_losses_result->fetch_assoc()['total_loss'];

    // Clear any accidental buffer and send JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'returns' => $returns_data,
        'total_loss' => $total_losses
    ]);
    exit; // Stop execution here so no HTML is appended
}

// Handle Add Return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_return') {
    $product = mysqli_real_escape_string($conn, $_POST['product']);
    $quantity = (int)$_POST['quantity'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $price_query = "SELECT price, stock_list FROM inventory WHERE product = '$product'";
    $price_result = $conn->query($price_query);

    if ($price_result && $row = $price_result->fetch_assoc()) {
        $price = $row['price'];
        $loss = $price * $quantity;
        $current_stock = $row['stock_list'];

        $insert_query = "INSERT INTO returns 
            (date, product, quantity, loss, reason, notes) 
            VALUES (NOW(), '$product', $quantity, $loss, '$reason', '$notes')";

        if ($conn->query($insert_query)) {
            $new_stock = $current_stock - $quantity;
            if ($new_stock < 0) $new_stock = 0;

            $update_stock = "UPDATE inventory 
                            SET stock_list = $new_stock 
                            WHERE product = '$product'";
            $conn->query($update_stock);

            $conn->query("UPDATE inventory SET status = CASE
                WHEN stock_list <= 0 THEN 'out_of_stock'
                WHEN stock_list <= minimum_stock THEN 'low_stock'
                ELSE 'in_stock'
            END WHERE product = '$product'");

            $_SESSION['success_message'] = "Return logged successfully!";
        } else {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Product not found!";
    }

    header('Location: returns.php');
    exit;
}

// Handle Delete Return
if (isset($_GET['delete'])) {
    $return_id = (int)$_GET['delete'];

    $get_return = "SELECT product, quantity FROM returns WHERE id = $return_id";
    $return_result = $conn->query($get_return);

    if ($return_result && $return = $return_result->fetch_assoc()) {
        $delete_query = "DELETE FROM returns WHERE id = $return_id";

        if ($conn->query($delete_query)) {
            $update_stock = "UPDATE inventory 
                            SET stock_list = stock_list + {$return['quantity']} 
                            WHERE product = '{$return['product']}'";
            $conn->query($update_stock);

            $conn->query("UPDATE inventory SET status = CASE
                WHEN stock_list <= 0 THEN 'out_of_stock'
                WHEN stock_list <= minimum_stock THEN 'low_stock'
                ELSE 'in_stock'
            END WHERE product = '{$return['product']}'");

            $_SESSION['success_message'] = "Return record deleted and stock restored!";
        } else {
            $_SESSION['error_message'] = "Error deleting return record";
        }
    }

    header('Location: returns.php');
    exit;
}

include 'header.php';
include 'sidebar.php';

// Initial load
$initial_where = "";

if (
    isset($_GET['from_date']) &&
    isset($_GET['to_date']) &&
    !empty($_GET['from_date']) &&
    !empty($_GET['to_date'])
) {
    $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
    $to_date = mysqli_real_escape_string($conn, $_GET['to_date']);

    $initial_where = "WHERE DATE(date) >= '$from_date' AND DATE(date) <= '$to_date'";
}

$initial_query = "SELECT * FROM returns $initial_where ORDER BY date DESC, id DESC";
$initial_result = $conn->query($initial_query);

$returns_rows = [];
while ($r = $initial_result->fetch_assoc()) {
    $returns_rows[] = $r;
}

$returns_count_total = count($returns_rows);

$total_losses_query = "SELECT COALESCE(SUM(loss), 0) as total_loss FROM returns $initial_where";
$total_losses_result = $conn->query($total_losses_query);
$total_losses = $total_losses_result->fetch_assoc()['total_loss'];

$products_query = "SELECT product, stock_list FROM inventory WHERE stock_list > 0 ORDER BY product";
$products_result = $conn->query($products_query);
?>

<body>
    <main class="main" id="main">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="dashboard-title">Reports</h1>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px;" onclick="clearFilters()">🗑️ Clear Filters</button>
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px; background: #1a7a3c; color: white;" onclick="exportExcel()">📥 Export Excel</button>
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px; background: saddlebrown; color: white;" onclick="toggleReturnForm()">+ Log New Return</button>
            </div>
        </div>
        <hr class="divider">

        <!-- Return Form (Hidden by default) -->
        <div id="return-form-container" style="display: none; margin-bottom: 1.5rem;">
            <div class="section-wrapper" style="border-color: #c0392b;">
                <div class="section-header">
                    <span class="section-title" style="color: #c0392b;">📝 Log a Return</span>
                    <button class="btn-sm" onclick="toggleReturnForm()">✕ Close</button>
                </div>
                <hr class="section-divider">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_return">
                    <div class="new-product-grid">
                        <div class="new-product-field">
                            <label class="field-label">Select Product *</label>
                            <select name="product" id="return-product" class="inv-input" required style="width:100%;">
                                <option value="">-- Select a product --</option>
                                <?php
                                $all_products_query = "SELECT product, stock_list FROM inventory ORDER BY product";
                                $all_products = $conn->query($all_products_query);
                                while ($prod = $all_products->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($prod['product']) . '" data-stock="' . $prod['stock_list'] . '">' . htmlspecialchars($prod['product']) . ' (' . $prod['stock_list'] . ' in stock)</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Quantity Returned *</label>
                            <input type="number" name="quantity" id="return-qty" class="inv-input" placeholder="Enter qty" min="1" required style="width:100%;">
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Reason *</label>
                            <select name="reason" id="return-reason" class="inv-input" required style="width:100%;">
                                <option value="unsold">Unsold / Expired</option>
                                <option value="expired">Expired</option>
                                <option value="damaged">Damaged</option>
                                <option value="customer_return">Customer Return</option>
                                <option value="wrong_item">Wrong Item Delivered</option>
                            </select>
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Notes (Optional)</label>
                            <input type="text" name="notes" class="inv-input" placeholder="Additional notes" style="width:100%;">
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="inv-add-btn" style="background: #c0392b; width: auto; padding: 10px 30px;">📝 Log Return</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="dash-card" style="margin-bottom: 1.5rem;">
            <div class="dash-wrapper" style="border-color: saddlebrown;">
                <p class="dash-label" style="font-size: 11px; color: #8b6340; font-family: sans-serif;">TOTAL RETURNS</p>
                <p class="dash-value" id="total-returns" style="color: saddlebrown; font-size: 26px;">
                    <?php echo $returns_count_total; ?>
                </p>
            </div>
            <div class="dash-wrapper" style="border-color: #c0392b;">
                <p class="dash-label" style="font-size: 11px; color: #8b6340; font-family: sans-serif;">TOTAL LOSSES</p>
                <p class="dash-value" id="total-losses" style="color: #c0392b; font-size: 26px;">
                    ₱<?php echo number_format($total_losses, 2); ?>
                </p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="section-wrapper" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-family: sans-serif; font-size: 13px; color: #8b6340;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label>FROM</label>
                    <input type="date" id="date-from" class="inv-input" style="width: auto;" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label>TO</label>
                    <input type="date" id="date-to" class="inv-input" style="width: auto;" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">
                </div>
                <button class="inv-add-btn" style="width: auto; padding: 8px 20px; margin: 0;" onclick="filterReturns()">📊 Filter</button>
                <button class="btn-sm" onclick="setThisWeek()">This Week</button>
                <button class="btn-sm" onclick="setThisMonth()">This Month</button>
            </div>
        </div>

        <!-- Returns History Table -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Returns History</span>
            </div>
            <hr class="section-divider">
            <div id="no-data-alert" style="display: none; background-color: #fdf2f2; border: 1px solid #f8b4b4; color: #c81e1e; padding: 15px; border-radius: 8px; margin-bottom: 15px; align-items: center; gap: 10px;">
                <span>⚠️</span>
                <span><strong>No records found!</strong></span>
            </div>
            <div style="overflow-x: auto;">
                <table class="orders-table" id="returns-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>PRODUCT</th>
                            <th>QTY</th>
                            <th>LOSS</th>
                            <th>REASON</th>
                            <th>NOTES</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="returns-table-body">
                        <?php if (count($returns_rows) > 0): ?>
                            <?php foreach ($returns_rows as $return):
                                $reason_display = ucfirst(str_replace('_', ' ', $return['reason']));
                            ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($return['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($return['product']); ?></td>
                                    <td><?php echo $return['quantity']; ?></td>
                                    <td style="color: #c0392b; font-weight: 500;">₱<?php echo number_format($return['loss'], 2); ?></td>
                                    <td><span class="pay-badge"><?php echo $reason_display; ?></span></td>
                                    <td><?php echo htmlspecialchars($return['notes'] ?: '—'); ?></td>
                                    <td>
                                        <button class="action-btn delete" onclick="deleteReturn(<?php echo $return['id']; ?>)" title="Delete">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- This script runs ONLY if the table is empty on page load -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const noDataAlert = document.getElementById('no-data-alert');
                                    const table = document.getElementById('returns-table');
                                    if (noDataAlert) noDataAlert.style.display = 'flex';
                                    if (table) table.style.display = 'none';
                                });
                            </script>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#8b6340; padding:1.5rem;">
                                    No returns found for this period.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer note -->
        <p id="rpt-footer" style="font-family: sans-serif; font-size: 12px; color: #8b6340; text-align: right; margin-top: 10px;">
            <?php
            if (isset($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
                echo "Showing returns from " . $_GET['from_date'] . " to " . $_GET['to_date'];
            } else {
                echo "Showing all returns";
            }
            ?>
        </p>
    </main>

    <script>
        // Toggle Return Form
        function toggleReturnForm() {
            const form = document.getElementById('return-form-container');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

        // Validate quantity against stock
        const productSelect = document.getElementById('return-product');
        const qtyInput = document.getElementById('return-qty');

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxStock = selectedOption.getAttribute('data-stock');
                if (qtyInput && maxStock) {
                    qtyInput.max = maxStock;
                    qtyInput.placeholder = `Max ${maxStock}`;
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', function() {
                const max = parseInt(this.max);
                const val = parseInt(this.value);
                if (val > max) {
                    alert(`Maximum quantity available is ${max}`);
                    this.value = max;
                }
                if (val < 1 || isNaN(val)) this.value = 1;
            });
        }

        // AJAX Filter
        function filterReturns() {
            const fromDate = document.getElementById('date-from').value;
            const toDate = document.getElementById('date-to').value;

            if (!fromDate || !toDate) {
                alert('Please select both From and To dates.');
                return;
            }

            fetchReturns(fromDate, toDate);
        }

        function fetchReturns(fromDate, toDate) {
            let url = window.location.pathname + '?ajax=1';
            if (fromDate && toDate) {
                url += `&from_date=${fromDate}&to_date=${toDate}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    renderTable(data.returns);
                    // Update total returns count
                    document.getElementById('total-returns').textContent = data.returns.length;
                    // Update total losses
                    const totalLoss = parseFloat(data.total_loss).toFixed(2);
                    document.getElementById('total-losses').textContent = '₱' + parseFloat(totalLoss).toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    // Update footer note
                    const footer = document.getElementById('rpt-footer');
                    if (fromDate && toDate) {
                        footer.textContent = `Showing returns from ${fromDate} to ${toDate}`;
                    } else {
                        footer.textContent = 'Showing all returns';
                    }
                })
                .catch(error => {
                    console.error('Filter error:', error);
                    alert('Error filtering returns. Please try again.');
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('returns-table-body');
            const tableContainer = document.getElementById('returns-table').parentElement;
            const noDataAlert = document.getElementById('no-data-alert');

            // Check if rows exist and have content
            if (!rows || rows.length === 0) {
                // 1. Show the warning box
                noDataAlert.style.display = 'flex';
                // 2. Hide the table to keep the UI clean
                tableContainer.style.display = 'none';
                // 3. Clear the tbody just in case
                tbody.innerHTML = '';
                return;
            }

            // If data exists:
            noDataAlert.style.display = 'none'; // Hide warning
            tableContainer.style.display = 'block'; // Show table

            let html = '';
            for (const r of rows) {
                const reason = r.reason ? r.reason.charAt(0).toUpperCase() + r.reason.slice(1).replace(/_/g, ' ') : '—';
                const loss = parseFloat(r.loss).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                const notes = r.notes || '—';
                html += `<tr>
            <td>${escapeHtml(r.date)}</td>
            <td>${escapeHtml(r.product)}</td>
            <td>${r.quantity}</td>
            <td style="color:#c0392b;font-weight:500;">₱${loss}</td>
            <td><span class="pay-badge">${escapeHtml(reason)}</span></td>
            <td>${escapeHtml(notes)}</td>
            <td>
                <button class="action-btn delete" onclick="deleteReturn(${r.id})" title="Delete">🗑️</button>
            </td>
        </tr>`;
            }
            tbody.innerHTML = html;
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // Set This Week
        function setThisWeek() {
            const today = new Date();
            const day = today.getDay();
            const monday = new Date(today);
            const diffToMonday = (day === 0 ? 6 : day - 1);
            monday.setDate(today.getDate() - diffToMonday);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);

            document.getElementById('date-from').value = monday.toISOString().split('T')[0];
            document.getElementById('date-to').value = sunday.toISOString().split('T')[0];
            filterReturns();
        }

        // Set This Month
        function setThisMonth() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.getElementById('date-from').value = firstDay.toISOString().split('T')[0];
            document.getElementById('date-to').value = lastDay.toISOString().split('T')[0];
            filterReturns();
        }

        // Clear Filters
        function clearFilters() {
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
            // Reload page to show all returns
            window.location.href = window.location.pathname;
        }

        // Delete Return
        function deleteReturn(returnId) {
            if (confirm('Delete this return record? This will restore stock to inventory.')) {
                window.location.href = `returns.php?delete=${returnId}`;
            }
        }

        // Export Excel
        function exportExcel() {
            const fromDate = document.getElementById('date-from').value;
            const toDate = document.getElementById('date-to').value;
            let url = 'export_returns.php';
            if (fromDate && toDate) {
                url += `?from_date=${fromDate}&to_date=${toDate}`;
            }
            window.location.href = url;
        }

        // Show success/error messages
        <?php if (isset($_SESSION['success_message'])): ?>
            alert('✅ <?php echo $_SESSION['success_message']; ?>');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            alert('❌ Error: <?php echo $_SESSION['error_message']; ?>');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

=======
<?php
include 'db.php';

// 2. Handle AJAX request for filtering (MUST be before ANY HTML output)
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $where_clause = "";

    if (isset($_GET['from_date'], $_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
        $to_date = mysqli_real_escape_string($conn, $_GET['to_date']);
        $where_clause = "WHERE DATE(date) >= '$from_date' AND DATE(date) <= '$to_date'";
    }

    $returns_query = "SELECT * FROM returns $where_clause ORDER BY date DESC, id DESC";
    $returns_result = $conn->query($returns_query);

    $returns_data = [];
    while ($row = $returns_result->fetch_assoc()) {
        $returns_data[] = $row;
    }

    $total_losses_query = "SELECT COALESCE(SUM(loss), 0) as total_loss FROM returns $where_clause";
    $total_losses_result = $conn->query($total_losses_query);
    $total_losses = $total_losses_result->fetch_assoc()['total_loss'];

    // Clear any accidental buffer and send JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'returns' => $returns_data,
        'total_loss' => $total_losses
    ]);
    exit; // Stop execution here so no HTML is appended
}

// Handle Add Return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_return') {
    $product = mysqli_real_escape_string($conn, $_POST['product']);
    $quantity = (int)$_POST['quantity'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $price_query = "SELECT price, stock_list FROM inventory WHERE product = '$product'";
    $price_result = $conn->query($price_query);

    if ($price_result && $row = $price_result->fetch_assoc()) {
        $price = $row['price'];
        $loss = $price * $quantity;
        $current_stock = $row['stock_list'];

        $insert_query = "INSERT INTO returns 
            (date, product, quantity, loss, reason, notes) 
            VALUES (NOW(), '$product', $quantity, $loss, '$reason', '$notes')";

        if ($conn->query($insert_query)) {
            $new_stock = $current_stock - $quantity;
            if ($new_stock < 0) $new_stock = 0;

            $update_stock = "UPDATE inventory 
                            SET stock_list = $new_stock 
                            WHERE product = '$product'";
            $conn->query($update_stock);

            $conn->query("UPDATE inventory SET status = CASE
                WHEN stock_list <= 0 THEN 'out_of_stock'
                WHEN stock_list <= minimum_stock THEN 'low_stock'
                ELSE 'in_stock'
            END WHERE product = '$product'");

            $_SESSION['success_message'] = "Return logged successfully!";
        } else {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Product not found!";
    }

    header('Location: returns.php');
    exit;
}

// Handle Delete Return
if (isset($_GET['delete'])) {
    $return_id = (int)$_GET['delete'];

    $get_return = "SELECT product, quantity FROM returns WHERE id = $return_id";
    $return_result = $conn->query($get_return);

    if ($return_result && $return = $return_result->fetch_assoc()) {
        $delete_query = "DELETE FROM returns WHERE id = $return_id";

        if ($conn->query($delete_query)) {
            $update_stock = "UPDATE inventory 
                            SET stock_list = stock_list + {$return['quantity']} 
                            WHERE product = '{$return['product']}'";
            $conn->query($update_stock);

            $conn->query("UPDATE inventory SET status = CASE
                WHEN stock_list <= 0 THEN 'out_of_stock'
                WHEN stock_list <= minimum_stock THEN 'low_stock'
                ELSE 'in_stock'
            END WHERE product = '{$return['product']}'");

            $_SESSION['success_message'] = "Return record deleted and stock restored!";
        } else {
            $_SESSION['error_message'] = "Error deleting return record";
        }
    }

    header('Location: returns.php');
    exit;
}

include 'header.php';
include 'sidebar.php';

// Initial load
$initial_where = "";

if (
    isset($_GET['from_date']) &&
    isset($_GET['to_date']) &&
    !empty($_GET['from_date']) &&
    !empty($_GET['to_date'])
) {
    $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
    $to_date = mysqli_real_escape_string($conn, $_GET['to_date']);

    $initial_where = "WHERE DATE(date) >= '$from_date' AND DATE(date) <= '$to_date'";
}

$initial_query = "SELECT * FROM returns $initial_where ORDER BY date DESC, id DESC";
$initial_result = $conn->query($initial_query);

$returns_rows = [];
while ($r = $initial_result->fetch_assoc()) {
    $returns_rows[] = $r;
}

$returns_count_total = count($returns_rows);

$total_losses_query = "SELECT COALESCE(SUM(loss), 0) as total_loss FROM returns $initial_where";
$total_losses_result = $conn->query($total_losses_query);
$total_losses = $total_losses_result->fetch_assoc()['total_loss'];

$products_query = "SELECT product, stock_list FROM inventory WHERE stock_list > 0 ORDER BY product";
$products_result = $conn->query($products_query);
?>

<body>
    <main class="main" id="main">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="dashboard-title">Reports</h1>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px;" onclick="clearFilters()">🗑️ Clear Filters</button>
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px; background: #1a7a3c; color: white;" onclick="exportExcel()">📥 Export Excel</button>
                <button class="btn-sm" style="font-size: 13px; padding: 8px 18px; background: saddlebrown; color: white;" onclick="toggleReturnForm()">+ Log New Return</button>
            </div>
        </div>
        <hr class="divider">

        <!-- Return Form (Hidden by default) -->
        <div id="return-form-container" style="display: none; margin-bottom: 1.5rem;">
            <div class="section-wrapper" style="border-color: #c0392b;">
                <div class="section-header">
                    <span class="section-title" style="color: #c0392b;">📝 Log a Return</span>
                    <button class="btn-sm" onclick="toggleReturnForm()">✕ Close</button>
                </div>
                <hr class="section-divider">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_return">
                    <div class="new-product-grid">
                        <div class="new-product-field">
                            <label class="field-label">Select Product *</label>
                            <select name="product" id="return-product" class="inv-input" required style="width:100%;">
                                <option value="">-- Select a product --</option>
                                <?php
                                $all_products_query = "SELECT product, stock_list FROM inventory ORDER BY product";
                                $all_products = $conn->query($all_products_query);
                                while ($prod = $all_products->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($prod['product']) . '" data-stock="' . $prod['stock_list'] . '">' . htmlspecialchars($prod['product']) . ' (' . $prod['stock_list'] . ' in stock)</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Quantity Returned *</label>
                            <input type="number" name="quantity" id="return-qty" class="inv-input" placeholder="Enter qty" min="1" required style="width:100%;">
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Reason *</label>
                            <select name="reason" id="return-reason" class="inv-input" required style="width:100%;">
                                <option value="unsold">Unsold / Expired</option>
                                <option value="expired">Expired</option>
                                <option value="damaged">Damaged</option>
                                <option value="customer_return">Customer Return</option>
                                <option value="wrong_item">Wrong Item Delivered</option>
                            </select>
                        </div>
                        <div class="new-product-field">
                            <label class="field-label">Notes (Optional)</label>
                            <input type="text" name="notes" class="inv-input" placeholder="Additional notes" style="width:100%;">
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="inv-add-btn" style="background: #c0392b; width: auto; padding: 10px 30px;">📝 Log Return</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="dash-card" style="margin-bottom: 1.5rem;">
            <div class="dash-wrapper" style="border-color: saddlebrown;">
                <p class="dash-label" style="font-size: 11px; color: #8b6340; font-family: sans-serif;">TOTAL RETURNS</p>
                <p class="dash-value" id="total-returns" style="color: saddlebrown; font-size: 26px;">
                    <?php echo $returns_count_total; ?>
                </p>
            </div>
            <div class="dash-wrapper" style="border-color: #c0392b;">
                <p class="dash-label" style="font-size: 11px; color: #8b6340; font-family: sans-serif;">TOTAL LOSSES</p>
                <p class="dash-value" id="total-losses" style="color: #c0392b; font-size: 26px;">
                    ₱<?php echo number_format($total_losses, 2); ?>
                </p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="section-wrapper" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-family: sans-serif; font-size: 13px; color: #8b6340;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label>FROM</label>
                    <input type="date" id="date-from" class="inv-input" style="width: auto;" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label>TO</label>
                    <input type="date" id="date-to" class="inv-input" style="width: auto;" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">
                </div>
                <button class="inv-add-btn" style="width: auto; padding: 8px 20px; margin: 0;" onclick="filterReturns()">📊 Filter</button>
                <button class="btn-sm" onclick="setThisWeek()">This Week</button>
                <button class="btn-sm" onclick="setThisMonth()">This Month</button>
            </div>
        </div>

        <!-- Returns History Table -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Returns History</span>
            </div>
            <hr class="section-divider">
            <div id="no-data-alert" style="display: none; background-color: #fdf2f2; border: 1px solid #f8b4b4; color: #c81e1e; padding: 15px; border-radius: 8px; margin-bottom: 15px; align-items: center; gap: 10px;">
                <span>⚠️</span>
                <span><strong>No records found!</strong></span>
            </div>
            <div style="overflow-x: auto;">
                <table class="orders-table" id="returns-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>PRODUCT</th>
                            <th>QTY</th>
                            <th>LOSS</th>
                            <th>REASON</th>
                            <th>NOTES</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="returns-table-body">
                        <?php if (count($returns_rows) > 0): ?>
                            <?php foreach ($returns_rows as $return):
                                $reason_display = ucfirst(str_replace('_', ' ', $return['reason']));
                            ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($return['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($return['product']); ?></td>
                                    <td><?php echo $return['quantity']; ?></td>
                                    <td style="color: #c0392b; font-weight: 500;">₱<?php echo number_format($return['loss'], 2); ?></td>
                                    <td><span class="pay-badge"><?php echo $reason_display; ?></span></td>
                                    <td><?php echo htmlspecialchars($return['notes'] ?: '—'); ?></td>
                                    <td>
                                        <button class="action-btn delete" onclick="deleteReturn(<?php echo $return['id']; ?>)" title="Delete">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- This script runs ONLY if the table is empty on page load -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const noDataAlert = document.getElementById('no-data-alert');
                                    const table = document.getElementById('returns-table');
                                    if (noDataAlert) noDataAlert.style.display = 'flex';
                                    if (table) table.style.display = 'none';
                                });
                            </script>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#8b6340; padding:1.5rem;">
                                    No returns found for this period.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer note -->
        <p id="rpt-footer" style="font-family: sans-serif; font-size: 12px; color: #8b6340; text-align: right; margin-top: 10px;">
            <?php
            if (isset($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
                echo "Showing returns from " . $_GET['from_date'] . " to " . $_GET['to_date'];
            } else {
                echo "Showing all returns";
            }
            ?>
        </p>
    </main>

    <script>
        // Toggle Return Form
        function toggleReturnForm() {
            const form = document.getElementById('return-form-container');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

        // Validate quantity against stock
        const productSelect = document.getElementById('return-product');
        const qtyInput = document.getElementById('return-qty');

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxStock = selectedOption.getAttribute('data-stock');
                if (qtyInput && maxStock) {
                    qtyInput.max = maxStock;
                    qtyInput.placeholder = `Max ${maxStock}`;
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', function() {
                const max = parseInt(this.max);
                const val = parseInt(this.value);
                if (val > max) {
                    alert(`Maximum quantity available is ${max}`);
                    this.value = max;
                }
                if (val < 1 || isNaN(val)) this.value = 1;
            });
        }

        // AJAX Filter
        function filterReturns() {
            const fromDate = document.getElementById('date-from').value;
            const toDate = document.getElementById('date-to').value;

            if (!fromDate || !toDate) {
                alert('Please select both From and To dates.');
                return;
            }

            fetchReturns(fromDate, toDate);
        }

        function fetchReturns(fromDate, toDate) {
            let url = window.location.pathname + '?ajax=1';
            if (fromDate && toDate) {
                url += `&from_date=${fromDate}&to_date=${toDate}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    renderTable(data.returns);
                    // Update total returns count
                    document.getElementById('total-returns').textContent = data.returns.length;
                    // Update total losses
                    const totalLoss = parseFloat(data.total_loss).toFixed(2);
                    document.getElementById('total-losses').textContent = '₱' + parseFloat(totalLoss).toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    // Update footer note
                    const footer = document.getElementById('rpt-footer');
                    if (fromDate && toDate) {
                        footer.textContent = `Showing returns from ${fromDate} to ${toDate}`;
                    } else {
                        footer.textContent = 'Showing all returns';
                    }
                })
                .catch(error => {
                    console.error('Filter error:', error);
                    alert('Error filtering returns. Please try again.');
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('returns-table-body');
            const tableContainer = document.getElementById('returns-table').parentElement;
            const noDataAlert = document.getElementById('no-data-alert');

            // Check if rows exist and have content
            if (!rows || rows.length === 0) {
                // 1. Show the warning box
                noDataAlert.style.display = 'flex';
                // 2. Hide the table to keep the UI clean
                tableContainer.style.display = 'none';
                // 3. Clear the tbody just in case
                tbody.innerHTML = '';
                return;
            }

            // If data exists:
            noDataAlert.style.display = 'none'; // Hide warning
            tableContainer.style.display = 'block'; // Show table

            let html = '';
            for (const r of rows) {
                const reason = r.reason ? r.reason.charAt(0).toUpperCase() + r.reason.slice(1).replace(/_/g, ' ') : '—';
                const loss = parseFloat(r.loss).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                const notes = r.notes || '—';
                html += `<tr>
            <td>${escapeHtml(r.date)}</td>
            <td>${escapeHtml(r.product)}</td>
            <td>${r.quantity}</td>
            <td style="color:#c0392b;font-weight:500;">₱${loss}</td>
            <td><span class="pay-badge">${escapeHtml(reason)}</span></td>
            <td>${escapeHtml(notes)}</td>
            <td>
                <button class="action-btn delete" onclick="deleteReturn(${r.id})" title="Delete">🗑️</button>
            </td>
        </tr>`;
            }
            tbody.innerHTML = html;
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // Set This Week
        function setThisWeek() {
            const today = new Date();
            const day = today.getDay();
            const monday = new Date(today);
            const diffToMonday = (day === 0 ? 6 : day - 1);
            monday.setDate(today.getDate() - diffToMonday);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);

            document.getElementById('date-from').value = monday.toISOString().split('T')[0];
            document.getElementById('date-to').value = sunday.toISOString().split('T')[0];
            filterReturns();
        }

        // Set This Month
        function setThisMonth() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.getElementById('date-from').value = firstDay.toISOString().split('T')[0];
            document.getElementById('date-to').value = lastDay.toISOString().split('T')[0];
            filterReturns();
        }

        // Clear Filters
        function clearFilters() {
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
            // Reload page to show all returns
            window.location.href = window.location.pathname;
        }

        // Delete Return
        function deleteReturn(returnId) {
            if (confirm('Delete this return record? This will restore stock to inventory.')) {
                window.location.href = `returns.php?delete=${returnId}`;
            }
        }

        // Export Excel
        function exportExcel() {
            const fromDate = document.getElementById('date-from').value;
            const toDate = document.getElementById('date-to').value;
            let url = 'export_returns.php';
            if (fromDate && toDate) {
                url += `?from_date=${fromDate}&to_date=${toDate}`;
            }
            window.location.href = url;
        }

        // Show success/error messages
        <?php if (isset($_SESSION['success_message'])): ?>
            alert('✅ <?php echo $_SESSION['success_message']; ?>');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            alert('❌ Error: <?php echo $_SESSION['error_message']; ?>');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>