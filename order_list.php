<<<<<<< HEAD
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';
    header('Content-Type: application/json');

    // --- CANCEL ORDER LOGIC ---
    if ($_POST['action'] === 'cancel_order') {
        $order_id = (int)$_POST['order_id'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        // Fetch order details to restore stock
        $result = $conn->query("SELECT product, quantity, customer_name FROM orders WHERE id = $order_id");
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();

            $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
            $loss = ($price_result->fetch_assoc()['price'] ?? 0) * $order['quantity'];

            $return_notes = "Customer Cancelled: $reason. " . $notes;

            $conn->query("INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                          VALUES (CURDATE(), '{$order['product']}', {$order['quantity']}, $loss, 'customer return', '$return_notes')");

            if ($conn->query("DELETE FROM orders WHERE id = $order_id")) {
                $conn->query("UPDATE inventory SET stock_list = stock_list + {$order['quantity']} WHERE product = '{$order['product']}'");

                // ✅ LOG THE CANCELLATION
                // 🚨 "LOUD" LOGGING DIRECT TO THE POPUP
                $safe_reason = mysqli_real_escape_string($conn, $reason);
                $action_name = "Customer Cancelled Order";
                $details_text = "Cancelled Order #$order_id. Reason: $safe_reason";
                $user_name = isset($_SESSION['fname']) ? mysqli_real_escape_string($conn, $_SESSION['fname']) : 'Unknown Customer';

                $debug_sql = "INSERT INTO activity_logs (user_name, user_role, action, details) VALUES ('$user_name', 'Customer', '$action_name', '$details_text')";

                if (!$conn->query($debug_sql)) {
                    // If the log fails, force the exact database error into the Javascript popup!
                    echo json_encode(['success' => false, 'message' => 'Order cancelled, BUT Log Failed: ' . $conn->error]);
                    exit;
                }

                // ✅ ONLY ONE ECHO, AND ADD AN EXIT!
                echo json_encode(['success' => true, 'message' => 'Order successfully cancelled and logged!']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error cancelling order.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
        }
        exit;
    }

    // --- REFUND ORDER LOGIC ---
    if ($_POST['action'] === 'refund_order') {
        $order_id = (int)$_POST['order_id'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        if ($conn->query("UPDATE orders SET status = 'Refund Requested' WHERE id = $order_id")) {

            // ✅ LOG THE REFUND REQUEST
            $safe_reason = mysqli_real_escape_string($conn, $reason);
            logAction($conn, 'Requested Refund', "Requested a refund for Order #$order_id. Reason: $safe_reason");

            echo json_encode(['success' => true, 'message' => 'Refund request submitted!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error submitting refund.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

include 'header.php';
include 'sidebar.php';


$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;
$cust_query = $conn->query("SELECT fname, lname FROM customer WHERE customer_id = $customer_id");
$cust_data = $cust_query ? $cust_query->fetch_assoc() : null;
$auto_name = trim(($cust_data['fname'] ?? '') . ' ' . ($cust_data['lname'] ?? ''));

$query = "SELECT o.id, o.product, o.quantity, o.total_price, o.status, o.order_date, i.image 
          FROM orders o 
          LEFT JOIN inventory i ON o.product = i.product 
          WHERE o.customer_name = '$auto_name' 
          ORDER BY o.order_date DESC";

$orders_result = $conn->query($query);
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Order History</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> My Orders</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select class="inv-select" id="filter-status" onchange="filterOrders()" style="font-size: 12px; padding: 5px 10px;">
                        <option value="all">All Status</option>
                        <option value="pending">Preparing</option>
                        <option value="ready_for_rider">Waiting for Rider</option>
                        <option value="processing">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>
            <hr class="section-divider">

            <div id="order-history-list">
                <?php
                if ($orders_result && $orders_result->num_rows > 0) {
                    while ($row = $orders_result->fetch_assoc()) {

                        $db_status = trim(strtolower((string)$row['status']));
                        $display_status = '';
                        $status_color = '';

                        if ($db_status === 'pending_verification' || $db_status === '') {
                            $display_status = 'Verifying GCash';
                            $status_color = '#c0392b';
                        } elseif ($db_status === 'pending') {
                            $display_status = 'Preparing';
                            $status_color = '#e0a422';
                        } elseif ($db_status === 'ready_for_rider') {
                            $display_status = 'Waiting for Rider';
                            $status_color = '#8e44ad';
                        } elseif ($db_status === 'processing') {
                            $display_status = 'Out for Delivery';
                            $status_color = '#2196F3';
                        } elseif ($db_status === 'delivered') {
                            $display_status = 'Delivered';
                            $status_color = '#2e7d32';
                        } elseif ($db_status === 'refund requested') {
                            $display_status = 'Refund Requested';
                            $status_color = '#8e44ad';
                        } else {
                            $display_status = ucfirst($db_status);
                            if (empty($display_status)) $display_status = 'Unknown';
                            $status_color = '#7f8c8d';
                        }

                        // SMART IMAGE FINDER
                        $db_image = $row['image'];
                        $final_image_src = '';

                        if (!empty($db_image)) {
                            $clean_path = str_replace(['../', './'], '', $db_image);

                            if (file_exists('admin/' . $clean_path)) {
                                $final_image_src = 'admin/' . $clean_path;
                            } elseif (file_exists($clean_path)) {
                                $final_image_src = $clean_path;
                            } else {
                                $final_image_src = $db_image;
                            }
                        }
                ?>
                        <div class="oh-item" data-status="<?php echo htmlspecialchars($db_status); ?>">
                            <div class="oh-status-col">
                                <span class="oh-status-badge" style="background: <?php echo $status_color; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block;">
                                    <?php echo htmlspecialchars($display_status); ?>
                                </span>
                                <div style="font-size: 10px; color: #888; margin-top: 5px;">
                                    <?php echo date('M d, Y', strtotime($row['order_date'])); ?>
                                </div>
                            </div>

                            <div class="oh-image" style="border-radius:8px; overflow: hidden; width: 80px; height: 80px; flex-shrink: 0; background: #e8dcc8; border: 1px solid #ddd;">
                                <?php if (!empty($final_image_src)): ?>
                                    <div style="background-image: url('<?php echo htmlspecialchars($final_image_src); ?>'); background-size: cover; background-position: center; width: 100%; height: 100%;"></div>
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; justify-content:center; width: 100%; height: 100%; text-align: center; font-size:11px; color:#8b6340; padding: 5px; font-family: sans-serif;">
                                        <?php echo htmlspecialchars($row['product']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="oh-info">
                                <p class="oh-name"><?php echo htmlspecialchars($row['product']); ?></p>
                                <p class="oh-qty">Quantity: x<?php echo $row['quantity']; ?></p>
                                <p class="oh-price">₱<?php echo number_format($row['total_price'], 2); ?></p>
                            </div>
                            <div class="oh-actions">
                                <?php if ($db_status === 'delivered'): ?>
                                    <button class="oh-btn refund" onclick="openRefundModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['product']); ?>')">Request Refund</button>
                                <?php elseif ($db_status === 'pending' || $db_status === 'ready_for_rider' || $db_status === 'processing' || $db_status === 'pending_verification' || $db_status === ''): ?>
                                    <button class="oh-btn cancel" onclick="openCancelModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['product']); ?>')">Cancel Order</button>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo '<p class="oh-empty" id="oh-empty">No orders found.</p>';
                }
                ?>
            </div>
        </div>

        <div class="oh-modal-overlay" id="cancel-modal" style="display:none;">
            <div class="oh-modal">
                <h3 class="settings-card-title" style="margin-bottom: 8px;">Cancel Order</h3>
                <p class="settings-card-desc" id="cancel-product-name" style="margin-bottom: 12px; font-weight: bold;"></p>

                <div class="field-group" style="margin-bottom: 12px;">
                    <label class="field-label">Reason for Cancellation</label>
                    <select class="inv-select" id="cancel-reason" style="width:100%; padding: 9px 12px;">
                        <option value="Changed my mind">Changed my mind</option>
                        <option value="Ordered by mistake">Ordered by mistake</option>
                        <option value="Found a better price">Found a better price</option>
                        <option value="Taking too long">Taking too long</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <textarea id="cancel-notes" placeholder="Additional notes (optional)..."
                    style="width:100%; height:70px; padding:10px; border:1.5px solid #e8dcc8; border-radius:8px; font-family:sans-serif; font-size:13px; background:cornsilk; resize:none; color:#3b2208;"></textarea>

                <div style="display:flex; gap:10px; margin-top:12px;">
                    <button class="save-changes-btn" style="background:#c0392b;" onclick="submitCancel()">Confirm Cancel</button>
                    <button class="edit-btn" style="flex:1;" onclick="closeModal('cancel-modal')">Keep Order</button>
                </div>
            </div>
        </div>

        <div class="oh-modal-overlay" id="refund-modal" style="display:none;">
            <div class="oh-modal">
                <h3 class="settings-card-title" style="margin-bottom: 8px;">Request a Refund</h3>
                <p class="settings-card-desc" id="refund-product-name" style="margin-bottom: 12px; font-weight: bold;"></p>
                <div class="field-group" style="margin-bottom: 12px;">
                    <label class="field-label">Reason for Refund</label>
                    <select class="inv-select" id="refund-reason" style="width:100%; padding: 9px 12px;">
                        <option value="Wrong Item">Wrong Item Delivered</option>
                        <option value="Damaged">Damaged/Spoiled</option>
                        <option value="Not as Described">Not as Described</option>
                        <option value="Missing Items">Missing Items</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <textarea id="refund-notes" placeholder="Please describe the issue in detail..."
                    style="width:100%; height:70px; padding:10px; border:1.5px solid #e8dcc8; border-radius:8px; font-family:sans-serif; font-size:13px; background:cornsilk; resize:none; color:#3b2208;"></textarea>
                <div style="display:flex; gap:10px; margin-top:12px;">
                    <button class="save-changes-btn" style="background:#c0392b;" onclick="submitRefund()">Submit Refund Request</button>
                    <button class="edit-btn" style="flex:1;" onclick="closeModal('refund-modal')">Cancel</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        let targetOrderId = null;

        function filterOrders() {
            const filterValue = document.getElementById('filter-status').value;
            const items = document.querySelectorAll('.oh-item');

            items.forEach(item => {
                const status = item.getAttribute('data-status');
                if (filterValue === 'all' || status === filterValue || (filterValue === 'pending' && (status === 'pending_verification' || status === ''))) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            targetOrderId = null;
        }

        function openCancelModal(id, productName) {
            targetOrderId = id;
            document.getElementById('cancel-product-name').textContent = "Cancelling: " + productName;
            document.getElementById('cancel-modal').style.display = 'flex';
        }

        function openRefundModal(id, productName) {
            targetOrderId = id;
            document.getElementById('refund-product-name').textContent = "Refunding: " + productName;
            document.getElementById('refund-modal').style.display = 'flex';
        }

        function submitCancel() {
            if (!targetOrderId) return;
            const reason = document.getElementById('cancel-reason').value;
            const notes = document.getElementById('cancel-notes').value;

            // ✅ FIX: Save the ID before closing the modal!
            const orderIdToCancel = targetOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeModal('cancel-modal'); // This visually closes it and resets targetOrderId to null

            const formData = new URLSearchParams();
            formData.append('action', 'cancel_order');
            formData.append('order_id', orderIdToCancel); // ✅ Use the saved variable
            formData.append('reason', reason);
            formData.append('notes', notes);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error. Please try again.');
                });
        }

        function submitRefund() {
            if (!targetOrderId) return;
            const reason = document.getElementById('refund-reason').value;
            const notes = document.getElementById('refund-notes').value;

            if (reason === 'Other' && !notes.trim()) {
                alert('Please provide some details in the notes section.');
                return;
            }

            // ✅ FIX: Save the ID before closing the modal!
            const orderIdToRefund = targetOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeModal('refund-modal');

            const formData = new URLSearchParams();
            formData.append('action', 'refund_order');
            formData.append('order_id', orderIdToRefund); // ✅ Use the saved variable
            formData.append('reason', reason);
            formData.append('notes', notes);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error. Please try again.');
                });
        }
    </script>
</body>

=======
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';
    header('Content-Type: application/json');

    // --- CANCEL ORDER LOGIC ---
    if ($_POST['action'] === 'cancel_order') {
        $order_id = (int)$_POST['order_id'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        // Fetch order details to restore stock
        $result = $conn->query("SELECT product, quantity, customer_name FROM orders WHERE id = $order_id");
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();

            $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
            $loss = ($price_result->fetch_assoc()['price'] ?? 0) * $order['quantity'];

            $return_notes = "Customer Cancelled: $reason. " . $notes;

            $conn->query("INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                          VALUES (CURDATE(), '{$order['product']}', {$order['quantity']}, $loss, 'customer return', '$return_notes')");

            if ($conn->query("DELETE FROM orders WHERE id = $order_id")) {
                $conn->query("UPDATE inventory SET stock_list = stock_list + {$order['quantity']} WHERE product = '{$order['product']}'");

                // ✅ LOG THE CANCELLATION
                // 🚨 "LOUD" LOGGING DIRECT TO THE POPUP
                $safe_reason = mysqli_real_escape_string($conn, $reason);
                $action_name = "Customer Cancelled Order";
                $details_text = "Cancelled Order #$order_id. Reason: $safe_reason";
                $user_name = isset($_SESSION['fname']) ? mysqli_real_escape_string($conn, $_SESSION['fname']) : 'Unknown Customer';

                $debug_sql = "INSERT INTO activity_logs (user_name, user_role, action, details) VALUES ('$user_name', 'Customer', '$action_name', '$details_text')";

                if (!$conn->query($debug_sql)) {
                    // If the log fails, force the exact database error into the Javascript popup!
                    echo json_encode(['success' => false, 'message' => 'Order cancelled, BUT Log Failed: ' . $conn->error]);
                    exit;
                }

                // ✅ ONLY ONE ECHO, AND ADD AN EXIT!
                echo json_encode(['success' => true, 'message' => 'Order successfully cancelled and logged!']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error cancelling order.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
        }
        exit;
    }

    // --- REFUND ORDER LOGIC ---
    if ($_POST['action'] === 'refund_order') {
        $order_id = (int)$_POST['order_id'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        if ($conn->query("UPDATE orders SET status = 'Refund Requested' WHERE id = $order_id")) {

            // ✅ LOG THE REFUND REQUEST
            $safe_reason = mysqli_real_escape_string($conn, $reason);
            logAction($conn, 'Requested Refund', "Requested a refund for Order #$order_id. Reason: $safe_reason");

            echo json_encode(['success' => true, 'message' => 'Refund request submitted!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error submitting refund.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

include 'header.php';
include 'sidebar.php';


$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;
$cust_query = $conn->query("SELECT fname, lname FROM customer WHERE customer_id = $customer_id");
$cust_data = $cust_query ? $cust_query->fetch_assoc() : null;
$auto_name = trim(($cust_data['fname'] ?? '') . ' ' . ($cust_data['lname'] ?? ''));

$query = "SELECT o.id, o.product, o.quantity, o.total_price, o.status, o.order_date, i.image 
          FROM orders o 
          LEFT JOIN inventory i ON o.product = i.product 
          WHERE o.customer_name = '$auto_name' 
          ORDER BY o.order_date DESC";

$orders_result = $conn->query($query);
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Order History</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> My Orders</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select class="inv-select" id="filter-status" onchange="filterOrders()" style="font-size: 12px; padding: 5px 10px;">
                        <option value="all">All Status</option>
                        <option value="pending">Preparing</option>
                        <option value="ready_for_rider">Waiting for Rider</option>
                        <option value="processing">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>
            <hr class="section-divider">

            <div id="order-history-list">
                <?php
                if ($orders_result && $orders_result->num_rows > 0) {
                    while ($row = $orders_result->fetch_assoc()) {

                        $db_status = trim(strtolower((string)$row['status']));
                        $display_status = '';
                        $status_color = '';

                        if ($db_status === 'pending_verification' || $db_status === '') {
                            $display_status = 'Verifying GCash';
                            $status_color = '#c0392b';
                        } elseif ($db_status === 'pending') {
                            $display_status = 'Preparing';
                            $status_color = '#e0a422';
                        } elseif ($db_status === 'ready_for_rider') {
                            $display_status = 'Waiting for Rider';
                            $status_color = '#8e44ad';
                        } elseif ($db_status === 'processing') {
                            $display_status = 'Out for Delivery';
                            $status_color = '#2196F3';
                        } elseif ($db_status === 'delivered') {
                            $display_status = 'Delivered';
                            $status_color = '#2e7d32';
                        } elseif ($db_status === 'refund requested') {
                            $display_status = 'Refund Requested';
                            $status_color = '#8e44ad';
                        } else {
                            $display_status = ucfirst($db_status);
                            if (empty($display_status)) $display_status = 'Unknown';
                            $status_color = '#7f8c8d';
                        }

                        // SMART IMAGE FINDER
                        $db_image = $row['image'];
                        $final_image_src = '';

                        if (!empty($db_image)) {
                            $clean_path = str_replace(['../', './'], '', $db_image);

                            if (file_exists('admin/' . $clean_path)) {
                                $final_image_src = 'admin/' . $clean_path;
                            } elseif (file_exists($clean_path)) {
                                $final_image_src = $clean_path;
                            } else {
                                $final_image_src = $db_image;
                            }
                        }
                ?>
                        <div class="oh-item" data-status="<?php echo htmlspecialchars($db_status); ?>">
                            <div class="oh-status-col">
                                <span class="oh-status-badge" style="background: <?php echo $status_color; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block;">
                                    <?php echo htmlspecialchars($display_status); ?>
                                </span>
                                <div style="font-size: 10px; color: #888; margin-top: 5px;">
                                    <?php echo date('M d, Y', strtotime($row['order_date'])); ?>
                                </div>
                            </div>

                            <div class="oh-image" style="border-radius:8px; overflow: hidden; width: 80px; height: 80px; flex-shrink: 0; background: #e8dcc8; border: 1px solid #ddd;">
                                <?php if (!empty($final_image_src)): ?>
                                    <div style="background-image: url('<?php echo htmlspecialchars($final_image_src); ?>'); background-size: cover; background-position: center; width: 100%; height: 100%;"></div>
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; justify-content:center; width: 100%; height: 100%; text-align: center; font-size:11px; color:#8b6340; padding: 5px; font-family: sans-serif;">
                                        <?php echo htmlspecialchars($row['product']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="oh-info">
                                <p class="oh-name"><?php echo htmlspecialchars($row['product']); ?></p>
                                <p class="oh-qty">Quantity: x<?php echo $row['quantity']; ?></p>
                                <p class="oh-price">₱<?php echo number_format($row['total_price'], 2); ?></p>
                            </div>
                            <div class="oh-actions">
                                <?php if ($db_status === 'delivered'): ?>
                                    <button class="oh-btn refund" onclick="openRefundModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['product']); ?>')">Request Refund</button>
                                <?php elseif ($db_status === 'pending' || $db_status === 'ready_for_rider' || $db_status === 'processing' || $db_status === 'pending_verification' || $db_status === ''): ?>
                                    <button class="oh-btn cancel" onclick="openCancelModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['product']); ?>')">Cancel Order</button>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo '<p class="oh-empty" id="oh-empty">No orders found.</p>';
                }
                ?>
            </div>
        </div>

        <div class="oh-modal-overlay" id="cancel-modal" style="display:none;">
            <div class="oh-modal">
                <h3 class="settings-card-title" style="margin-bottom: 8px;">Cancel Order</h3>
                <p class="settings-card-desc" id="cancel-product-name" style="margin-bottom: 12px; font-weight: bold;"></p>

                <div class="field-group" style="margin-bottom: 12px;">
                    <label class="field-label">Reason for Cancellation</label>
                    <select class="inv-select" id="cancel-reason" style="width:100%; padding: 9px 12px;">
                        <option value="Changed my mind">Changed my mind</option>
                        <option value="Ordered by mistake">Ordered by mistake</option>
                        <option value="Found a better price">Found a better price</option>
                        <option value="Taking too long">Taking too long</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <textarea id="cancel-notes" placeholder="Additional notes (optional)..."
                    style="width:100%; height:70px; padding:10px; border:1.5px solid #e8dcc8; border-radius:8px; font-family:sans-serif; font-size:13px; background:cornsilk; resize:none; color:#3b2208;"></textarea>

                <div style="display:flex; gap:10px; margin-top:12px;">
                    <button class="save-changes-btn" style="background:#c0392b;" onclick="submitCancel()">Confirm Cancel</button>
                    <button class="edit-btn" style="flex:1;" onclick="closeModal('cancel-modal')">Keep Order</button>
                </div>
            </div>
        </div>

        <div class="oh-modal-overlay" id="refund-modal" style="display:none;">
            <div class="oh-modal">
                <h3 class="settings-card-title" style="margin-bottom: 8px;">Request a Refund</h3>
                <p class="settings-card-desc" id="refund-product-name" style="margin-bottom: 12px; font-weight: bold;"></p>
                <div class="field-group" style="margin-bottom: 12px;">
                    <label class="field-label">Reason for Refund</label>
                    <select class="inv-select" id="refund-reason" style="width:100%; padding: 9px 12px;">
                        <option value="Wrong Item">Wrong Item Delivered</option>
                        <option value="Damaged">Damaged/Spoiled</option>
                        <option value="Not as Described">Not as Described</option>
                        <option value="Missing Items">Missing Items</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <textarea id="refund-notes" placeholder="Please describe the issue in detail..."
                    style="width:100%; height:70px; padding:10px; border:1.5px solid #e8dcc8; border-radius:8px; font-family:sans-serif; font-size:13px; background:cornsilk; resize:none; color:#3b2208;"></textarea>
                <div style="display:flex; gap:10px; margin-top:12px;">
                    <button class="save-changes-btn" style="background:#c0392b;" onclick="submitRefund()">Submit Refund Request</button>
                    <button class="edit-btn" style="flex:1;" onclick="closeModal('refund-modal')">Cancel</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        let targetOrderId = null;

        function filterOrders() {
            const filterValue = document.getElementById('filter-status').value;
            const items = document.querySelectorAll('.oh-item');

            items.forEach(item => {
                const status = item.getAttribute('data-status');
                if (filterValue === 'all' || status === filterValue || (filterValue === 'pending' && (status === 'pending_verification' || status === ''))) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            targetOrderId = null;
        }

        function openCancelModal(id, productName) {
            targetOrderId = id;
            document.getElementById('cancel-product-name').textContent = "Cancelling: " + productName;
            document.getElementById('cancel-modal').style.display = 'flex';
        }

        function openRefundModal(id, productName) {
            targetOrderId = id;
            document.getElementById('refund-product-name').textContent = "Refunding: " + productName;
            document.getElementById('refund-modal').style.display = 'flex';
        }

        function submitCancel() {
            if (!targetOrderId) return;
            const reason = document.getElementById('cancel-reason').value;
            const notes = document.getElementById('cancel-notes').value;

            // ✅ FIX: Save the ID before closing the modal!
            const orderIdToCancel = targetOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeModal('cancel-modal'); // This visually closes it and resets targetOrderId to null

            const formData = new URLSearchParams();
            formData.append('action', 'cancel_order');
            formData.append('order_id', orderIdToCancel); // ✅ Use the saved variable
            formData.append('reason', reason);
            formData.append('notes', notes);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error. Please try again.');
                });
        }

        function submitRefund() {
            if (!targetOrderId) return;
            const reason = document.getElementById('refund-reason').value;
            const notes = document.getElementById('refund-notes').value;

            if (reason === 'Other' && !notes.trim()) {
                alert('Please provide some details in the notes section.');
                return;
            }

            // ✅ FIX: Save the ID before closing the modal!
            const orderIdToRefund = targetOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeModal('refund-modal');

            const formData = new URLSearchParams();
            formData.append('action', 'refund_order');
            formData.append('order_id', orderIdToRefund); // ✅ Use the saved variable
            formData.append('reason', reason);
            formData.append('notes', notes);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error. Please try again.');
                });
        }
    </script>
</body>

>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>