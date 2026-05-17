<<<<<<< HEAD
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';

    // --- CANCEL ONLINE ORDER ---
    if ($_POST['action'] === 'delete_order') {
        $order_id = (int)$_POST['order_id'];
        $return_reason = mysqli_real_escape_string($conn, $_POST['return_reason']);
        $custom_reason = isset($_POST['custom_reason']) ? mysqli_real_escape_string($conn, $_POST['custom_reason']) : '';

        $valid_enum_values = ['unsold', 'expired', 'damaged', 'customer return', 'wrong item'];
        if (!in_array($return_reason, $valid_enum_values)) {
            $return_reason = 'customer return';
        }

        $result = $conn->query("SELECT product, quantity, total_price, customer_name FROM orders WHERE id = $order_id");

        if (!$result || $result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => "Order #$order_id not found"]);
            exit;
        }

        $order = $result->fetch_assoc();
        $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
        $price_row = $price_result->fetch_assoc();
        $loss = $price_row['price'] * $order['quantity'];

        $extra_note = !empty($custom_reason) ? " Additional notes: $custom_reason" : '';
        $return_notes = "Cancelled ONLINE order #$order_id. Customer: {$order['customer_name']}.$extra_note";

        $insert_return = "INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                          VALUES (CURDATE(), '{$order['product']}', {$order['quantity']}, $loss, '$return_reason', '$return_notes')";

        if ($conn->query($insert_return)) {
            if ($conn->query("DELETE FROM orders WHERE id = $order_id")) {
                $conn->query("UPDATE inventory SET stock_list = stock_list + {$order['quantity']} WHERE product = '{$order['product']}'");
                $conn->query("UPDATE inventory SET status = CASE 
                    WHEN stock_list <= 0 THEN 'out_of_stock'
                    WHEN stock_list <= minimum_stock THEN 'low_stock'
                    ELSE 'in_stock'
                END WHERE product = '{$order['product']}'");

                // ✅ LOG THE CANCELLATION
                $safe_reason = mysqli_real_escape_string($conn, $return_reason);
                logAction($conn, 'Cancelled Online Order', "Cancelled Online Order #$order_id. Reason: $safe_reason");

                echo json_encode(['success' => true, 'message' => 'Online order cancelled and stock returned.']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order.']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Failed to insert return.']);
        exit;
    }

    // --- VERIFY GCASH PAYMENT ---
    if ($_POST['action'] === 'verify_payment') {
        $order_id = (int)$_POST['order_id'];
        
        // Pushes it to pending so Admin can now pack it
        if ($conn->query("UPDATE orders SET status = 'pending' WHERE id = $order_id")) {
            
            // ✅ LOG THE PAYMENT VERIFICATION
            logAction($conn, 'Verified Payment', "Verified GCash payment for Online Order #$order_id");
            
            echo json_encode(['success' => true, 'message' => 'Payment verified! You can now pack this order.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to verify payment.']);
        }
        exit;
    }

    // --- COMPLETE PREPARATION (Hand off to Rider) ---
    if ($_POST['action'] === 'complete_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Push the order to 'ready_for_rider' instead of 'delivered'
        if ($conn->query("UPDATE orders SET status = 'ready_for_rider' WHERE id = $order_id")) {
            
            // ✅ LOG THE PACKING COMPLETION
            logAction($conn, 'Packed Online Order', "Marked Online Order #$order_id as packed and sent to Rider dashboard");
            
            echo json_encode(['success' => true, 'message' => 'Packing complete! Sent to Rider dashboard.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to complete order.']);
        }
        exit;
    }

    // --- OUT FOR DELIVERY ---
    if ($_POST['action'] === 'out_for_delivery') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'processing' WHERE id = $order_id")) {
            
            // ✅ LOG DISPATCH
            logAction($conn, 'Dispatched Order', "Online Order #$order_id is now Out for Delivery.");
            
            echo json_encode(['success' => true, 'message' => 'Order is now out for delivery!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
        }
        exit;
    }

    // --- MARK AS DELIVERED ---
    if ($_POST['action'] === 'mark_delivered') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG DELIVERY
            logAction($conn, 'Order Delivered', "Online Order #$order_id was successfully delivered.");
            
            echo json_encode(['success' => true, 'message' => 'Order completed and delivered!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';
include 'db.php';

// Fetch inventory for the right-side panel
$inventory_query = "SELECT product, stock_list, price, minimum_stock, status FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);

// Fetch ONLINE orders
$orders_query = "SELECT id, product, quantity, total_price, payment_method, payment_reference, customer_name, delivery_address, order_date, status 
                 FROM orders 
                 WHERE order_type = 'online'
                 ORDER BY order_date DESC LIMIT 50";
$orders_result = $conn->query($orders_query);
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title"> Online</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; border: 2px solid #c0392b;">
                <h3 style="color: #c0392b; margin-bottom: 15px; font-family: cursive;">Cancel Online Order</h3>
                <p style="margin-bottom: 15px; color: #555;">Please select a reason for cancelling this delivery:</p>

                <select id="cancel-reason" class="inv-input" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <option value="customer return">Customer Cancelled</option>
                    <option value="wrong item">Wrong Item Ordered</option>
                    <option value="unsold">Out of Stock</option>
                    <option value="Other">Other (specify)</option>
                </select>

                <div id="custom-reason-wrap" style="display: none; margin-bottom: 15px;">
                    <input type="text" id="custom-reason" class="inv-input" placeholder="Please specify reason..." style="width: 100%; padding: 10px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" onclick="closeCancelModal()">Go Back</button>
                    <button class="save-btn" style="background: #c0392b; padding: 8px 20px;" onclick="confirmCancelOrder()">Confirm Cancel</button>
                </div>
            </div>
        </div>

        <div class="walkin-layout">

            <div class="walkin-form">
                <div class="section-wrapper walkin-form">

                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> Incoming Deliveries Queue</span>
                        <button class="btn-sm" style="background: saddlebrown; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" onclick="location.reload()">
                            🔄 Refresh Data
                        </button>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table" id="order-list-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($orders_result->num_rows > 0) {
                                    while ($order = $orders_result->fetch_assoc()) {
                                        // Status styling
                                        $status_color = '#e0a422'; 
                                        $status_text = '⏳ Needs Packing'; // Changed text to reflect Admin duty
                                        
                                        if ($order['status'] === 'pending_verification') {
                                            $status_color = '#c0392b'; 
                                            $status_text = '🛑 Needs Verification';
                                        } elseif ($order['status'] === 'ready_for_rider') {
                                            $status_color = '#8e44ad'; // Purple
                                            $status_text = '🛵 Waiting for Rider';
                                        } elseif ($order['status'] === 'processing') {
                                            $status_color = '#2196F3'; 
                                            $status_text = '🚚 Out for Delivery';
                                        } elseif ($order['status'] === 'delivered') {
                                            $status_color = '#2e7d32'; 
                                            $status_text = '✅ Delivered';
                                        }
                                ?>
                                        <tr data-id="<?php echo $order['id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($order['delivery_address']); ?>">
                                                <?php echo htmlspecialchars($order['delivery_address']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['product']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            
                                            <td>
                                                <span class="pay-badge"><?php echo $order['payment_method']; ?></span>
                                                <?php if ($order['payment_method'] === 'GCash'): ?>
                                                    <div style="font-size: 10px; margin-top: 4px; color: #555;">
                                                        Ref: <strong><?php echo htmlspecialchars($order['payment_reference']); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <?php if ($order['status'] === 'pending_verification'): ?>
                                                    <button class="action-btn" style="background: #2e7d32; color: white; padding: 4px 8px; font-size: 11px; margin-bottom: 4px;" onclick="verifyPayment(<?php echo $order['id']; ?>)">
                                                        ✅ Verify Pay
                                                    </button>
                                                <?php elseif ($order['status'] === 'pending'): ?>
                                                    <!-- ✅ ONLY SHOW COMPLETE BUTTON IF IT NEEDS PACKING -->
                                                    <button class="action-btn" style="background: #2196F3; color: white; padding: 4px 8px; font-size: 11px; margin-bottom: 4px;" onclick="completeOrder(<?php echo $order['id']; ?>)">
                                                        📦 Finish Packing
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($order['status'] !== 'delivered'): ?>
                                                <button class="action-btn delete" style="padding: 4px 8px; font-size: 11px;" onclick="openCancelModal(<?php echo $order['id']; ?>)">
                                                    🔄 Cancel
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="8" style="text-align:center; color:#8b6340; padding:3rem;">No online deliveries currently active. Waiting for customers to order...</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="walkin-sidebar">
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Stock Levels</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table" id="stock-levels">
                        <?php
                        mysqli_data_seek($inventory_result, 0);
                        while ($stock = $inventory_result->fetch_assoc()) {
                            $status_icon = ($stock['status'] == 'low_stock') ? '⚠️' : (($stock['status'] == 'out_of_stock') ? '❌' : '✓');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['product']); ?></td>
                                <td class="side-val"><?php echo $stock['stock_list']; ?> units <?php echo $status_icon; ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Price List</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <?php
                        mysqli_data_seek($inventory_result, 0);
                        while ($price = $inventory_result->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($price['product']); ?></td>
                                <td class="side-val price-val">₱<?php echo number_format($price['price'], 2); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Today's Online Stats</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <?php
                        $today = date('Y-m-d');
                        $today_summary = "SELECT COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_sales 
                                          FROM orders WHERE DATE(order_date) = '$today' AND order_type = 'online'";
                        $summary_result = $conn->query($today_summary);
                        $summary = $summary_result->fetch_assoc();
                        ?>
                        <tr>
                            <td>Total Online Orders</td>
                            <td class="side-val"><?php echo $summary['order_count']; ?></td>
                        </tr>
                        <tr>
                            <td>Total Online Sales</td>
                            <td class="side-val price-val">₱<?php echo number_format($summary['total_sales'], 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let pendingOrderId = null;

        // --- Cancel Modal Functions ---
        function openCancelModal(orderId) {
            pendingOrderId = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            pendingOrderId = null;
            document.getElementById('cancel-reason').value = 'customer return';
            document.getElementById('custom-reason-wrap').style.display = 'none';
            document.getElementById('custom-reason').value = '';
        }

        document.getElementById('cancel-reason').addEventListener('change', function() {
            const customWrap = document.getElementById('custom-reason-wrap');
            if (this.value === 'Other') {
                customWrap.style.display = 'block';
            } else {
                customWrap.style.display = 'none';
            }
        });

        function confirmCancelOrder() {
            if (!pendingOrderId) return;

            const reason = document.getElementById('cancel-reason').value;
            const customReason = document.getElementById('custom-reason').value.trim();

            if (reason === 'Other' && !customReason) {
                alert('Please specify a reason for cancellation.');
                return;
            }

            const orderIdToCancel = pendingOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeCancelModal();

            const formData = new URLSearchParams();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderIdToCancel);
            formData.append('return_reason', reason);
            formData.append('custom_reason', customReason);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (data.message || 'Could not process return'));
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    console.error('Error:', error);
                    alert('Error cancelling order. Please try again.');
                });
        }
        
        // --- Verify GCash Payment Function ---
        function verifyPayment(orderId) {
            if (!confirm('Are you sure you have received the GCash payment?')) return;

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'verify_payment');
            formData.append('order_id', orderId);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload(); 
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error verifying payment.');
                });
        }

        // --- Complete Order Function ---
        function completeOrder(orderId) {
            if (!confirm('Is this order packed and ready for the rider to pick up?')) return;

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload(); 
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error completing order.');
                });
        }
    </script>
</body>
=======
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';

    // --- CANCEL ONLINE ORDER ---
    if ($_POST['action'] === 'delete_order') {
        $order_id = (int)$_POST['order_id'];
        $return_reason = mysqli_real_escape_string($conn, $_POST['return_reason']);
        $custom_reason = isset($_POST['custom_reason']) ? mysqli_real_escape_string($conn, $_POST['custom_reason']) : '';

        $valid_enum_values = ['unsold', 'expired', 'damaged', 'customer return', 'wrong item'];
        if (!in_array($return_reason, $valid_enum_values)) {
            $return_reason = 'customer return';
        }

        $result = $conn->query("SELECT product, quantity, total_price, customer_name FROM orders WHERE id = $order_id");

        if (!$result || $result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => "Order #$order_id not found"]);
            exit;
        }

        $order = $result->fetch_assoc();
        $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
        $price_row = $price_result->fetch_assoc();
        $loss = $price_row['price'] * $order['quantity'];

        $extra_note = !empty($custom_reason) ? " Additional notes: $custom_reason" : '';
        $return_notes = "Cancelled ONLINE order #$order_id. Customer: {$order['customer_name']}.$extra_note";

        $insert_return = "INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                          VALUES (CURDATE(), '{$order['product']}', {$order['quantity']}, $loss, '$return_reason', '$return_notes')";

        if ($conn->query($insert_return)) {
            if ($conn->query("DELETE FROM orders WHERE id = $order_id")) {
                $conn->query("UPDATE inventory SET stock_list = stock_list + {$order['quantity']} WHERE product = '{$order['product']}'");
                $conn->query("UPDATE inventory SET status = CASE 
                    WHEN stock_list <= 0 THEN 'out_of_stock'
                    WHEN stock_list <= minimum_stock THEN 'low_stock'
                    ELSE 'in_stock'
                END WHERE product = '{$order['product']}'");

                // ✅ LOG THE CANCELLATION
                $safe_reason = mysqli_real_escape_string($conn, $return_reason);
                logAction($conn, 'Cancelled Online Order', "Cancelled Online Order #$order_id. Reason: $safe_reason");

                echo json_encode(['success' => true, 'message' => 'Online order cancelled and stock returned.']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order.']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Failed to insert return.']);
        exit;
    }

    // --- VERIFY GCASH PAYMENT ---
    if ($_POST['action'] === 'verify_payment') {
        $order_id = (int)$_POST['order_id'];
        
        // Pushes it to pending so Admin can now pack it
        if ($conn->query("UPDATE orders SET status = 'pending' WHERE id = $order_id")) {
            
            // ✅ LOG THE PAYMENT VERIFICATION
            logAction($conn, 'Verified Payment', "Verified GCash payment for Online Order #$order_id");
            
            echo json_encode(['success' => true, 'message' => 'Payment verified! You can now pack this order.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to verify payment.']);
        }
        exit;
    }

    // --- COMPLETE PREPARATION (Hand off to Rider) ---
    if ($_POST['action'] === 'complete_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Push the order to 'ready_for_rider' instead of 'delivered'
        if ($conn->query("UPDATE orders SET status = 'ready_for_rider' WHERE id = $order_id")) {
            
            // ✅ LOG THE PACKING COMPLETION
            logAction($conn, 'Packed Online Order', "Marked Online Order #$order_id as packed and sent to Rider dashboard");
            
            echo json_encode(['success' => true, 'message' => 'Packing complete! Sent to Rider dashboard.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to complete order.']);
        }
        exit;
    }

    // --- OUT FOR DELIVERY ---
    if ($_POST['action'] === 'out_for_delivery') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'processing' WHERE id = $order_id")) {
            
            // ✅ LOG DISPATCH
            logAction($conn, 'Dispatched Order', "Online Order #$order_id is now Out for Delivery.");
            
            echo json_encode(['success' => true, 'message' => 'Order is now out for delivery!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
        }
        exit;
    }

    // --- MARK AS DELIVERED ---
    if ($_POST['action'] === 'mark_delivered') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG DELIVERY
            logAction($conn, 'Order Delivered', "Online Order #$order_id was successfully delivered.");
            
            echo json_encode(['success' => true, 'message' => 'Order completed and delivered!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';
include 'db.php';

// Fetch inventory for the right-side panel
$inventory_query = "SELECT product, stock_list, price, minimum_stock, status FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);

// Fetch ONLINE orders
$orders_query = "SELECT id, product, quantity, total_price, payment_method, payment_reference, customer_name, delivery_address, order_date, status 
                 FROM orders 
                 WHERE order_type = 'online'
                 ORDER BY order_date DESC LIMIT 50";
$orders_result = $conn->query($orders_query);
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title"> Online</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; border: 2px solid #c0392b;">
                <h3 style="color: #c0392b; margin-bottom: 15px; font-family: cursive;">Cancel Online Order</h3>
                <p style="margin-bottom: 15px; color: #555;">Please select a reason for cancelling this delivery:</p>

                <select id="cancel-reason" class="inv-input" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <option value="customer return">Customer Cancelled</option>
                    <option value="wrong item">Wrong Item Ordered</option>
                    <option value="unsold">Out of Stock</option>
                    <option value="Other">Other (specify)</option>
                </select>

                <div id="custom-reason-wrap" style="display: none; margin-bottom: 15px;">
                    <input type="text" id="custom-reason" class="inv-input" placeholder="Please specify reason..." style="width: 100%; padding: 10px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" onclick="closeCancelModal()">Go Back</button>
                    <button class="save-btn" style="background: #c0392b; padding: 8px 20px;" onclick="confirmCancelOrder()">Confirm Cancel</button>
                </div>
            </div>
        </div>

        <div class="walkin-layout">

            <div class="walkin-form">
                <div class="section-wrapper walkin-form">

                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> Incoming Deliveries Queue</span>
                        <button class="btn-sm" style="background: saddlebrown; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" onclick="location.reload()">
                            🔄 Refresh Data
                        </button>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table" id="order-list-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($orders_result->num_rows > 0) {
                                    while ($order = $orders_result->fetch_assoc()) {
                                        // Status styling
                                        $status_color = '#e0a422'; 
                                        $status_text = '⏳ Needs Packing'; // Changed text to reflect Admin duty
                                        
                                        if ($order['status'] === 'pending_verification') {
                                            $status_color = '#c0392b'; 
                                            $status_text = '🛑 Needs Verification';
                                        } elseif ($order['status'] === 'ready_for_rider') {
                                            $status_color = '#8e44ad'; // Purple
                                            $status_text = '🛵 Waiting for Rider';
                                        } elseif ($order['status'] === 'processing') {
                                            $status_color = '#2196F3'; 
                                            $status_text = '🚚 Out for Delivery';
                                        } elseif ($order['status'] === 'delivered') {
                                            $status_color = '#2e7d32'; 
                                            $status_text = '✅ Delivered';
                                        }
                                ?>
                                        <tr data-id="<?php echo $order['id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($order['delivery_address']); ?>">
                                                <?php echo htmlspecialchars($order['delivery_address']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['product']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            
                                            <td>
                                                <span class="pay-badge"><?php echo $order['payment_method']; ?></span>
                                                <?php if ($order['payment_method'] === 'GCash'): ?>
                                                    <div style="font-size: 10px; margin-top: 4px; color: #555;">
                                                        Ref: <strong><?php echo htmlspecialchars($order['payment_reference']); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <?php if ($order['status'] === 'pending_verification'): ?>
                                                    <button class="action-btn" style="background: #2e7d32; color: white; padding: 4px 8px; font-size: 11px; margin-bottom: 4px;" onclick="verifyPayment(<?php echo $order['id']; ?>)">
                                                        ✅ Verify Pay
                                                    </button>
                                                <?php elseif ($order['status'] === 'pending'): ?>
                                                    <!-- ✅ ONLY SHOW COMPLETE BUTTON IF IT NEEDS PACKING -->
                                                    <button class="action-btn" style="background: #2196F3; color: white; padding: 4px 8px; font-size: 11px; margin-bottom: 4px;" onclick="completeOrder(<?php echo $order['id']; ?>)">
                                                        📦 Finish Packing
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($order['status'] !== 'delivered'): ?>
                                                <button class="action-btn delete" style="padding: 4px 8px; font-size: 11px;" onclick="openCancelModal(<?php echo $order['id']; ?>)">
                                                    🔄 Cancel
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="8" style="text-align:center; color:#8b6340; padding:3rem;">No online deliveries currently active. Waiting for customers to order...</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="walkin-sidebar">
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Stock Levels</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table" id="stock-levels">
                        <?php
                        mysqli_data_seek($inventory_result, 0);
                        while ($stock = $inventory_result->fetch_assoc()) {
                            $status_icon = ($stock['status'] == 'low_stock') ? '⚠️' : (($stock['status'] == 'out_of_stock') ? '❌' : '✓');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['product']); ?></td>
                                <td class="side-val"><?php echo $stock['stock_list']; ?> units <?php echo $status_icon; ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Price List</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <?php
                        mysqli_data_seek($inventory_result, 0);
                        while ($price = $inventory_result->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($price['product']); ?></td>
                                <td class="side-val price-val">₱<?php echo number_format($price['price'], 2); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Today's Online Stats</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <?php
                        $today = date('Y-m-d');
                        $today_summary = "SELECT COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_sales 
                                          FROM orders WHERE DATE(order_date) = '$today' AND order_type = 'online'";
                        $summary_result = $conn->query($today_summary);
                        $summary = $summary_result->fetch_assoc();
                        ?>
                        <tr>
                            <td>Total Online Orders</td>
                            <td class="side-val"><?php echo $summary['order_count']; ?></td>
                        </tr>
                        <tr>
                            <td>Total Online Sales</td>
                            <td class="side-val price-val">₱<?php echo number_format($summary['total_sales'], 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let pendingOrderId = null;

        // --- Cancel Modal Functions ---
        function openCancelModal(orderId) {
            pendingOrderId = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            pendingOrderId = null;
            document.getElementById('cancel-reason').value = 'customer return';
            document.getElementById('custom-reason-wrap').style.display = 'none';
            document.getElementById('custom-reason').value = '';
        }

        document.getElementById('cancel-reason').addEventListener('change', function() {
            const customWrap = document.getElementById('custom-reason-wrap');
            if (this.value === 'Other') {
                customWrap.style.display = 'block';
            } else {
                customWrap.style.display = 'none';
            }
        });

        function confirmCancelOrder() {
            if (!pendingOrderId) return;

            const reason = document.getElementById('cancel-reason').value;
            const customReason = document.getElementById('custom-reason').value.trim();

            if (reason === 'Other' && !customReason) {
                alert('Please specify a reason for cancellation.');
                return;
            }

            const orderIdToCancel = pendingOrderId;

            document.getElementById('loading-overlay').style.display = 'flex';
            closeCancelModal();

            const formData = new URLSearchParams();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderIdToCancel);
            formData.append('return_reason', reason);
            formData.append('custom_reason', customReason);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (data.message || 'Could not process return'));
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    console.error('Error:', error);
                    alert('Error cancelling order. Please try again.');
                });
        }
        
        // --- Verify GCash Payment Function ---
        function verifyPayment(orderId) {
            if (!confirm('Are you sure you have received the GCash payment?')) return;

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'verify_payment');
            formData.append('order_id', orderId);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload(); 
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error verifying payment.');
                });
        }

        // --- Complete Order Function ---
        function completeOrder(orderId) {
            if (!confirm('Is this order packed and ready for the rider to pick up?')) return;

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);

            fetch('online.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload(); 
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Connection error completing order.');
                });
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>