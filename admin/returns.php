<<<<<<< HEAD
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';
    header('Content-Type: application/json');

    // --- APPROVE CUSTOMER REFUND ---
    if ($_POST['action'] === 'approve_refund') {
        $order_id = (int)$_POST['order_id'];
        
        $order_result = $conn->query("SELECT * FROM orders WHERE id = $order_id AND status = 'Refund Requested'");
        if ($order_result && $order_result->num_rows > 0) {
            $order = $order_result->fetch_assoc();
            $product = $order['product'];
            $qty = $order['quantity'];
            
            // Get price for loss calculation
            $price_result = $conn->query("SELECT price FROM inventory WHERE product = '$product'");
            $loss = ($price_result && $price_result->num_rows > 0) ? ($price_result->fetch_assoc()['price'] * $qty) : 0;
            
            $reason = "Customer Refund";
            $notes = "Approved refund for Order #" . $order_id . " - Customer: " . $order['customer_name'];
            
            $insert_query = "INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                             VALUES (CURDATE(), '$product', $qty, $loss, '$reason', '$notes')";
                             
            if ($conn->query($insert_query)) {
                // Delete the original order
                $conn->query("DELETE FROM orders WHERE id = $order_id");
                
                // ✅ LOG THE REFUND APPROVAL
                logAction($conn, 'Approved Refund', "Approved refund and logged return for Order #$order_id ($product)");
                
                echo json_encode(['success' => true, 'message' => 'Refund approved and logged!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error while logging return.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found or already processed.']);
        }
        exit;
    }

    // --- REJECT CUSTOMER REFUND ---
    if ($_POST['action'] === 'reject_refund') {
        $order_id = (int)$_POST['order_id'];
        
        // Revert status to 'delivered' so it goes back to the customer
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG THE REJECTION
            logAction($conn, 'Rejected Refund', "Rejected refund request for Order #$order_id");
            
            echo json_encode(['success' => true, 'message' => 'Refund request rejected. Order marked as Delivered.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error while rejecting refund.']);
        }
        exit;
    }

    // --- MANUAL ADD RETURN ---
    if ($_POST['action'] === 'add_return') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $quantity = (int)$_POST['quantity'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        
        // Get product price from inventory
        $price_query = "SELECT price FROM inventory WHERE product = '$product'";
        $price_result = $conn->query($price_query);
        
        if ($price_result && $row = $price_result->fetch_assoc()) {
            $price = $row['price'];
            $loss = $price * $quantity;
            
            $insert_query = "INSERT INTO returns (date, product, quantity, loss, reason) 
                             VALUES (CURDATE(), '$product', $quantity, $loss, '$reason')";
            
            if ($conn->query($insert_query)) {
                // ✅ LOG THE MANUAL RETURN
                logAction($conn, 'Logged Manual Return', "Logged a return for $quantity x $product. Reason: $reason");
                
                echo json_encode(['success' => true, 'message' => 'Return logged successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        exit;
    }

    // --- CLEAR ALL RETURNS ---
    if ($_POST['action'] === 'clear_returns') {
        if ($conn->query("DELETE FROM returns")) {
            // ✅ LOG THE DELETION
            logAction($conn, 'Cleared All Returns', "Deleted the entire returns history database.");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    // --- DELETE SINGLE RETURN ---
    if ($_POST['action'] === 'delete_return') {
        $return_id = (int)$_POST['return_id'];
        if ($conn->query("DELETE FROM returns WHERE id = $return_id")) {
            // ✅ LOG THE DELETION
            logAction($conn, 'Deleted Single Return', "Deleted Return Record #$return_id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
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

// Fetch all returns for initial load
$returns_result = $conn->query("SELECT * FROM returns ORDER BY date DESC, id DESC");
$total_losses = $conn->query("SELECT COALESCE(SUM(loss), 0) as total FROM returns")->fetch_assoc()['total'];
$products_result = $conn->query("SELECT product FROM inventory ORDER BY product");

// Fetch pending refund requests from customers
$refunds_query = $conn->query("SELECT id, customer_name, product, quantity, total_price, order_date FROM orders WHERE status = 'Refund Requested' ORDER BY order_date ASC");
?>

<body>
    <main class="main" id="main">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="dashboard-title">Returns & Refunds</h1>
            </div>
            <div style="background: white; border: 2px solid #c0392b; border-radius: 8px; padding: 8px 18px; font-family: sans-serif;">
                <span style="font-size: 11px; color: #c0392b; font-weight: 500;">Total Losses:</span>
                <span style="font-size: 15px; font-weight: 500; color: #c0392b;" id="total-losses">₱<?php echo number_format($total_losses, 2); ?></span>
            </div>
        </div>
        <hr class="divider">

        <div class="returns-layout" style="display: flex; gap: 20px; align-items: flex-start;">

            <div style="display: flex; flex-direction: column; gap: 20px; flex: 1;">
                
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title" style="color: #c0392b;">⚠️ Pending Refund Requests</span>
                    </div>
                    <hr class="section-divider">
                    
                    <?php if ($refunds_query && $refunds_query->num_rows > 0): ?>
                        <table class="orders-table" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th>Price</th> <th>Customer</th>
                                    <th>Item</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ref = $refunds_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><b>₱<?php echo number_format($ref['total_price'], 2); ?></b></td>
                                        <td><?php echo htmlspecialchars($ref['customer_name']); ?></td>
                                        <td><?php echo $ref['quantity']; ?>x <?php echo htmlspecialchars($ref['product']); ?></td>
                                        <td style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button class="action-btn" style="background:#27ae60; color:white; padding: 4px 8px; border-radius: 6px;" onclick="approveRefund(<?php echo $ref['id']; ?>)">✔️ Approve</button>
                                            <button class="action-btn" style="background:#c0392b; color:white; padding: 4px 8px; border-radius: 6px;" onclick="rejectRefund(<?php echo $ref['id']; ?>)">❌ Reject</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #888; text-align: center; padding: 15px 0;">No pending refund requests.</p>
                    <?php endif; ?>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title">🔁 Log a Return Manually</span>
                    </div>
                    <hr class="section-divider">

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div class="new-product-field">
                            <label class="field-label">Select Product <span style="color:#c0392b;">*</span></label>
                            <select class="inv-select" id="return-product" style="width: 100%; padding: 9px 12px;">
                                <option value="">— Select a product —</option>
                                <?php while($prod = $products_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($prod['product']); ?>"><?php echo htmlspecialchars($prod['product']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="new-product-field">
                            <label class="field-label">Quantity Returned <span style="color:#c0392b;">*</span></label>
                            <input type="number" id="return-qty" class="inv-input" style="width:100%;" placeholder="Enter qty" min="1">
                        </div>

                        <div class="new-product-field">
                            <label class="field-label">Reason</label>
                            <select class="inv-select" id="return-reason" style="width: 100%; padding: 9px 12px;">
                                <option value="Unsold / Expired">Unsold / Expired</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Wrong Item">Wrong Item</option>
                                <option value="Customer Complaint">Customer Complaint</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="new-product-field" id="other-reason-wrap" style="display:none;">
                            <label class="field-label">Specify Reason</label>
                            <input type="text" id="return-other" class="inv-input" style="width:100%;" placeholder="Enter reason...">
                        </div>

                        <button class="save-btn" style="background: #e0a422;" onclick="logReturn()">🔄 Log Return</button>
                    </div>
                </div>

            </div>

            <div class="section-wrapper" style="flex: 2;">
                <div class="section-header">
                    <span class="section-title">📋 Returns History</span>
                    <button class="btn-sm" style="background: #c0392b; color: white;" onclick="clearReturns()">Clear All</button>
                </div>
                <hr class="section-divider">
                <table class="orders-table" id="returns-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Loss</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="returns-body">
                        <?php if($returns_result && $returns_result->num_rows > 0): ?>
                            <?php while($row = $returns_result->fetch_assoc()): ?>
                                <tr data-id="<?php echo $row['id']; ?>">
                                    <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                    <td>
                                        <b><?php echo htmlspecialchars($row['product']); ?></b>
                                        <?php if (!empty($row['notes'])): ?>
                                            <br><small style="color: #888; font-size: 11px;"><?php echo htmlspecialchars($row['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td>₱<?php echo number_format($row['loss'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><button class="action-btn delete" onclick="deleteReturn(<?php echo $row['id']; ?>)">🗑️</button></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">No returns logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        // Approve Refund Request
        function approveRefund(orderId) {
            if (!confirm("Are you sure you want to approve this refund? It will be moved to the Returns log.")) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'approve_refund',
                    'order_id': orderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error.'));
        }

        // ✅ NEW: Reject Refund Request
        function rejectRefund(orderId) {
            if (!confirm("Are you sure you want to REJECT this refund? The order will be marked as Delivered again.")) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'reject_refund',
                    'order_id': orderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error.'));
        }

        // Show/hide Other reason field
        document.getElementById('return-reason').addEventListener('change', function() {
            const otherWrap = document.getElementById('other-reason-wrap');
            if (this.value === 'Other') {
                otherWrap.style.display = 'block';
            } else {
                otherWrap.style.display = 'none';
            }
        });

        // Log Manual Return
        function logReturn() {
            const product = document.getElementById('return-product').value;
            const qty = document.getElementById('return-qty').value;
            let reason = document.getElementById('return-reason').value;
            
            if (!product) { alert('Please select a product'); return; }
            if (!qty || qty < 1) { alert('Please enter a valid quantity'); return; }
            
            if (reason === 'Other') {
                const otherReason = document.getElementById('return-other').value.trim();
                if (!otherReason) { alert('Please specify the reason'); return; }
                reason = otherReason;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'add_return',
                    'product': product,
                    'quantity': qty,
                    'reason': reason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error. Please try again.'));
        }
        
        // Clear All Returns
        function clearReturns() {
            if (confirm('⚠️ WARNING: This will delete ALL return records. This action cannot be undone!\n\nAre you sure?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 'action': 'clear_returns' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error clearing returns');
                });
            }
        }
        
        // Delete Single Return
        function deleteReturn(returnId) {
            if (confirm('Delete this return record?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'delete_return',
                        'return_id': returnId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error deleting return');
                });
            }
        }
    </script>
</body>
=======
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';
    header('Content-Type: application/json');

    // --- APPROVE CUSTOMER REFUND ---
    if ($_POST['action'] === 'approve_refund') {
        $order_id = (int)$_POST['order_id'];
        
        $order_result = $conn->query("SELECT * FROM orders WHERE id = $order_id AND status = 'Refund Requested'");
        if ($order_result && $order_result->num_rows > 0) {
            $order = $order_result->fetch_assoc();
            $product = $order['product'];
            $qty = $order['quantity'];
            
            // Get price for loss calculation
            $price_result = $conn->query("SELECT price FROM inventory WHERE product = '$product'");
            $loss = ($price_result && $price_result->num_rows > 0) ? ($price_result->fetch_assoc()['price'] * $qty) : 0;
            
            $reason = "Customer Refund";
            $notes = "Approved refund for Order #" . $order_id . " - Customer: " . $order['customer_name'];
            
            $insert_query = "INSERT INTO returns (date, product, quantity, loss, reason, notes) 
                             VALUES (CURDATE(), '$product', $qty, $loss, '$reason', '$notes')";
                             
            if ($conn->query($insert_query)) {
                // Delete the original order
                $conn->query("DELETE FROM orders WHERE id = $order_id");
                
                // ✅ LOG THE REFUND APPROVAL
                logAction($conn, 'Approved Refund', "Approved refund and logged return for Order #$order_id ($product)");
                
                echo json_encode(['success' => true, 'message' => 'Refund approved and logged!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error while logging return.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found or already processed.']);
        }
        exit;
    }

    // --- REJECT CUSTOMER REFUND ---
    if ($_POST['action'] === 'reject_refund') {
        $order_id = (int)$_POST['order_id'];
        
        // Revert status to 'delivered' so it goes back to the customer
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG THE REJECTION
            logAction($conn, 'Rejected Refund', "Rejected refund request for Order #$order_id");
            
            echo json_encode(['success' => true, 'message' => 'Refund request rejected. Order marked as Delivered.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error while rejecting refund.']);
        }
        exit;
    }

    // --- MANUAL ADD RETURN ---
    if ($_POST['action'] === 'add_return') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $quantity = (int)$_POST['quantity'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        
        // Get product price from inventory
        $price_query = "SELECT price FROM inventory WHERE product = '$product'";
        $price_result = $conn->query($price_query);
        
        if ($price_result && $row = $price_result->fetch_assoc()) {
            $price = $row['price'];
            $loss = $price * $quantity;
            
            $insert_query = "INSERT INTO returns (date, product, quantity, loss, reason) 
                             VALUES (CURDATE(), '$product', $quantity, $loss, '$reason')";
            
            if ($conn->query($insert_query)) {
                // ✅ LOG THE MANUAL RETURN
                logAction($conn, 'Logged Manual Return', "Logged a return for $quantity x $product. Reason: $reason");
                
                echo json_encode(['success' => true, 'message' => 'Return logged successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        exit;
    }

    // --- CLEAR ALL RETURNS ---
    if ($_POST['action'] === 'clear_returns') {
        if ($conn->query("DELETE FROM returns")) {
            // ✅ LOG THE DELETION
            logAction($conn, 'Cleared All Returns', "Deleted the entire returns history database.");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    // --- DELETE SINGLE RETURN ---
    if ($_POST['action'] === 'delete_return') {
        $return_id = (int)$_POST['return_id'];
        if ($conn->query("DELETE FROM returns WHERE id = $return_id")) {
            // ✅ LOG THE DELETION
            logAction($conn, 'Deleted Single Return', "Deleted Return Record #$return_id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
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

// Fetch all returns for initial load
$returns_result = $conn->query("SELECT * FROM returns ORDER BY date DESC, id DESC");
$total_losses = $conn->query("SELECT COALESCE(SUM(loss), 0) as total FROM returns")->fetch_assoc()['total'];
$products_result = $conn->query("SELECT product FROM inventory ORDER BY product");

// Fetch pending refund requests from customers
$refunds_query = $conn->query("SELECT id, customer_name, product, quantity, total_price, order_date FROM orders WHERE status = 'Refund Requested' ORDER BY order_date ASC");
?>

<body>
    <main class="main" id="main">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="dashboard-title">Returns & Refunds</h1>
            </div>
            <div style="background: white; border: 2px solid #c0392b; border-radius: 8px; padding: 8px 18px; font-family: sans-serif;">
                <span style="font-size: 11px; color: #c0392b; font-weight: 500;">Total Losses:</span>
                <span style="font-size: 15px; font-weight: 500; color: #c0392b;" id="total-losses">₱<?php echo number_format($total_losses, 2); ?></span>
            </div>
        </div>
        <hr class="divider">

        <div class="returns-layout" style="display: flex; gap: 20px; align-items: flex-start;">

            <div style="display: flex; flex-direction: column; gap: 20px; flex: 1;">
                
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title" style="color: #c0392b;">⚠️ Pending Refund Requests</span>
                    </div>
                    <hr class="section-divider">
                    
                    <?php if ($refunds_query && $refunds_query->num_rows > 0): ?>
                        <table class="orders-table" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th>Price</th> <th>Customer</th>
                                    <th>Item</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ref = $refunds_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><b>₱<?php echo number_format($ref['total_price'], 2); ?></b></td>
                                        <td><?php echo htmlspecialchars($ref['customer_name']); ?></td>
                                        <td><?php echo $ref['quantity']; ?>x <?php echo htmlspecialchars($ref['product']); ?></td>
                                        <td style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button class="action-btn" style="background:#27ae60; color:white; padding: 4px 8px; border-radius: 6px;" onclick="approveRefund(<?php echo $ref['id']; ?>)">✔️ Approve</button>
                                            <button class="action-btn" style="background:#c0392b; color:white; padding: 4px 8px; border-radius: 6px;" onclick="rejectRefund(<?php echo $ref['id']; ?>)">❌ Reject</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #888; text-align: center; padding: 15px 0;">No pending refund requests.</p>
                    <?php endif; ?>
                </div>

                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title">🔁 Log a Return Manually</span>
                    </div>
                    <hr class="section-divider">

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div class="new-product-field">
                            <label class="field-label">Select Product <span style="color:#c0392b;">*</span></label>
                            <select class="inv-select" id="return-product" style="width: 100%; padding: 9px 12px;">
                                <option value="">— Select a product —</option>
                                <?php while($prod = $products_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($prod['product']); ?>"><?php echo htmlspecialchars($prod['product']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="new-product-field">
                            <label class="field-label">Quantity Returned <span style="color:#c0392b;">*</span></label>
                            <input type="number" id="return-qty" class="inv-input" style="width:100%;" placeholder="Enter qty" min="1">
                        </div>

                        <div class="new-product-field">
                            <label class="field-label">Reason</label>
                            <select class="inv-select" id="return-reason" style="width: 100%; padding: 9px 12px;">
                                <option value="Unsold / Expired">Unsold / Expired</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Wrong Item">Wrong Item</option>
                                <option value="Customer Complaint">Customer Complaint</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="new-product-field" id="other-reason-wrap" style="display:none;">
                            <label class="field-label">Specify Reason</label>
                            <input type="text" id="return-other" class="inv-input" style="width:100%;" placeholder="Enter reason...">
                        </div>

                        <button class="save-btn" style="background: #e0a422;" onclick="logReturn()">🔄 Log Return</button>
                    </div>
                </div>

            </div>

            <div class="section-wrapper" style="flex: 2;">
                <div class="section-header">
                    <span class="section-title">📋 Returns History</span>
                    <button class="btn-sm" style="background: #c0392b; color: white;" onclick="clearReturns()">Clear All</button>
                </div>
                <hr class="section-divider">
                <table class="orders-table" id="returns-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Loss</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="returns-body">
                        <?php if($returns_result && $returns_result->num_rows > 0): ?>
                            <?php while($row = $returns_result->fetch_assoc()): ?>
                                <tr data-id="<?php echo $row['id']; ?>">
                                    <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                    <td>
                                        <b><?php echo htmlspecialchars($row['product']); ?></b>
                                        <?php if (!empty($row['notes'])): ?>
                                            <br><small style="color: #888; font-size: 11px;"><?php echo htmlspecialchars($row['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td>₱<?php echo number_format($row['loss'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><button class="action-btn delete" onclick="deleteReturn(<?php echo $row['id']; ?>)">🗑️</button></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">No returns logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        // Approve Refund Request
        function approveRefund(orderId) {
            if (!confirm("Are you sure you want to approve this refund? It will be moved to the Returns log.")) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'approve_refund',
                    'order_id': orderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error.'));
        }

        // ✅ NEW: Reject Refund Request
        function rejectRefund(orderId) {
            if (!confirm("Are you sure you want to REJECT this refund? The order will be marked as Delivered again.")) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'reject_refund',
                    'order_id': orderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error.'));
        }

        // Show/hide Other reason field
        document.getElementById('return-reason').addEventListener('change', function() {
            const otherWrap = document.getElementById('other-reason-wrap');
            if (this.value === 'Other') {
                otherWrap.style.display = 'block';
            } else {
                otherWrap.style.display = 'none';
            }
        });

        // Log Manual Return
        function logReturn() {
            const product = document.getElementById('return-product').value;
            const qty = document.getElementById('return-qty').value;
            let reason = document.getElementById('return-reason').value;
            
            if (!product) { alert('Please select a product'); return; }
            if (!qty || qty < 1) { alert('Please enter a valid quantity'); return; }
            
            if (reason === 'Other') {
                const otherReason = document.getElementById('return-other').value.trim();
                if (!otherReason) { alert('Please specify the reason'); return; }
                reason = otherReason;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'add_return',
                    'product': product,
                    'quantity': qty,
                    'reason': reason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error. Please try again.'));
        }
        
        // Clear All Returns
        function clearReturns() {
            if (confirm('⚠️ WARNING: This will delete ALL return records. This action cannot be undone!\n\nAre you sure?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 'action': 'clear_returns' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error clearing returns');
                });
            }
        }
        
        // Delete Single Return
        function deleteReturn(returnId) {
            if (confirm('Delete this return record?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'delete_return',
                        'return_id': returnId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error deleting return');
                });
            }
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>