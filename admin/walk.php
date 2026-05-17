<<<<<<< HEAD
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';

    // --- SAVE ORDER ACTION ---
    if ($_POST['action'] === 'save_order') {
        // Multi-item cart comes in as a JSON string
        $cart_json = $_POST['cart'];
        $cart = json_decode($cart_json, true);

        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);

        $errors = [];

        if (empty($cart)) $errors[] = "Cart is empty.";
        if (empty($payment_method)) $errors[] = "Payment method is required.";
        if (empty($customer_name)) $customer_name = "Walk-in Customer";

        // 1. Validation Loop: Check stock for EVERY item before inserting anything
        if (empty($errors)) {
            foreach ($cart as $item) {
                $p = mysqli_real_escape_string($conn, $item['product']);
                $q = (int)$item['quantity'];

                $product_result = $conn->query("SELECT stock_list FROM inventory WHERE product = '$p'");
                if ($product_result->num_rows === 0) {
                    $errors[] = "Product '$p' not found in inventory.";
                } else {
                    $product_data = $product_result->fetch_assoc();
                    $current_stock = $product_data['stock_list'];
                    if ($q > $current_stock) {
                        $errors[] = "Insufficient stock for '$p'. Only $current_stock available.";
                    }
                }
            }
        }

        // 2. Execution Loop: If all stock checks pass, insert orders and update inventory
        if (empty($errors)) {
            foreach ($cart as $item) {
                $p = mysqli_real_escape_string($conn, $item['product']);
                $q = (int)$item['quantity'];
                
                // Fetch price and stock again for exact calculation
                $product_result = $conn->query("SELECT price, stock_list FROM inventory WHERE product = '$p'");
                $product_data = $product_result->fetch_assoc();
                $price = $product_data['price'];
                $current_stock = $product_data['stock_list'];
                
                $total_price = $price * $q;

                // Insert into orders
                $insert_query = "INSERT INTO orders (product, quantity, payment_method, customer_name, delivery_address, total_price, status, order_date, order_type) 
                                 VALUES ('$p', $q, '$payment_method', '$customer_name', '$delivery_address', $total_price, 'processing', NOW(), 'walk-in')";
                
                if ($conn->query($insert_query)) {
                    // Deduct stock
                    $new_stock = $current_stock - $q;
                    $conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$p'");
                    $conn->query("UPDATE inventory SET status = CASE 
                        WHEN stock_list <= 0 THEN 'out_of_stock'
                        WHEN stock_list <= minimum_stock THEN 'low_stock'
                        ELSE 'in_stock'
                    END WHERE product = '$p'");
                }
            } // <-- Loop ends here!

            // ✅ LOG IT ONCE AFTER THE LOOP
            logAction($conn, 'Created Walk-in Order', "Processed multi-item order for $customer_name via $payment_method");
            
            // Standardized completion message
            echo json_encode(['success' => true, 'message' => 'Order set!']);
        } else {
            echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
        }
        exit;
    }

    // --- COMPLETE ORDER ACTION ---
    if ($_POST['action'] === 'complete_order') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG THE COMPLETION
            logAction($conn, 'Completed Order', "Marked Order #$order_id as delivered");
            
            echo json_encode(['success' => true, 'message' => 'Order completed!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit;
    }

    // --- DELETE ORDER ACTION ---
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
            echo json_encode(['success' => false, 'message' => "Order #$order_id not found in database"]);
            exit;
        }

        $order = $result->fetch_assoc();
        $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
        $price_row = $price_result->fetch_assoc();
        $loss = $price_row['price'] * $order['quantity'];

        $extra_note = !empty($custom_reason) ? " Additional notes: $custom_reason" : '';
        $return_notes = "Returned from cancelled order #$order_id. Customer: {$order['customer_name']}.$extra_note";

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
                logAction($conn, 'Cancelled Order', "Cancelled Order #$order_id. Reason: $return_reason");

                echo json_encode(['success' => true, 'message' => 'Order cancelled and return record created']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $conn->error]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert return: ' . $conn->error]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}


// ✅ HTML starts here — only on GET requests
include 'header.php';
include 'sidebar.php';
include 'db.php';

$inventory_query = "SELECT product, stock_list, price, minimum_stock, status FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);

$orders_query = "SELECT id, product, quantity, total_price, payment_method, customer_name, delivery_address, order_date, status 
                 FROM orders 
                 WHERE order_type = 'walk-in' 
                 ORDER BY order_date DESC LIMIT 20";
$orders_result = $conn->query($orders_query);

$returns_count_query = "SELECT COUNT(*) as count FROM returns WHERE date = CURDATE()";
$returns_count_result = $conn->query($returns_count_query);
$returns_count = $returns_count_result->fetch_assoc()['count'];
?>

<style>
    .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e8dcc8; }
    .cart-item-name { font-weight: bold; color: #3b2208; font-size: 13px; }
    .cart-item-details { font-size: 11px; color: #8b6340; }
    .cart-remove-btn { color: #c0392b; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: bold; }
    .cart-remove-btn:hover { color: #e74c3c; }

    /* --- RECEIPT & PRINT STYLES --- */
    .receipt-container { width: 320px; background: white; padding: 25px; border-radius: 8px; font-family: monospace; color: #000; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .receipt-header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
    .receipt-header h2 { font-size: 20px; margin: 0 0 5px; text-transform: uppercase; letter-spacing: 2px; }
    .receipt-header p { margin: 0; font-size: 12px; }
    .receipt-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; }
    .receipt-total { border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; font-size: 16px; font-weight: bold; }
    .receipt-footer { margin-top: 20px; text-align: center; font-size: 11px; border-top: 1px dashed #000; padding-top: 10px; }
    
    @media print {
        body * { visibility: hidden; }
        #receiptModal, #receiptModal * { visibility: visible; }
        #receiptModal { position: absolute; left: 0; top: 0; width: 100%; height: 100%; background: white; align-items: flex-start; justify-content: flex-start; }
        .receipt-container { width: 100%; max-width: 300px; box-shadow: none; margin: 0; padding: 0; border: none; }
        .no-print { display: none !important; }
    }
</style>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Walk-in POS</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="receiptModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10001; align-items: center; justify-content: center;">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h2>LA SEANALE</h2>
                    <p>Walk-in POS Receipt</p>
                    <p id="receipt-date"></p>
                </div>
                
                <div style="font-size: 12px; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px;">
                    <div class="receipt-row"><span>Customer:</span> <span id="receipt-customer"></span></div>
                    <div class="receipt-row"><span>Payment:</span> <span id="receipt-payment"></span></div>
                </div>

                <div id="receipt-items-container">
                    </div>

                <div class="receipt-row receipt-total">
                    <span>TOTAL</span>
                    <span id="receipt-total-amount"></span>
                </div>

                <div class="receipt-footer">
                    <p>Thank you for your purchase!</p>
                </div>

                <div class="no-print" style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                    <button class="save-btn" onclick="window.print()" style="flex: 1;">🖨️ Print</button>
                    <button class="btn-sm" onclick="location.reload()" style="flex: 1;">Done</button>
                </div>
            </div>
        </div>

        <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; border: 2px solid #c0392b;">
                <h3 style="color: #c0392b; margin-bottom: 15px; font-family: cursive;">Cancel Order</h3>
                <p style="margin-bottom: 15px; color: #555;">Please select a reason for cancelling this order:</p>

                <select id="cancel-reason" class="inv-input" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <option value="customer return">Customer Request</option>
                    <option value="wrong item">Wrong Item Ordered</option>
                    <option value="damaged">Damaged Product</option>
                    <option value="unsold">Out of Stock / Unsold</option>
                    <option value="expired">Expired</option>
                    <option value="Other">Other (specify)</option>
                </select>

                <div id="custom-reason-wrap" style="display: none; margin-bottom: 15px;">
                    <input type="text" id="custom-reason" class="inv-input" placeholder="Please specify reason..." style="width: 100%; padding: 10px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" onclick="closeCancelModal()">Cancel</button>
                    <button class="save-btn" style="background: #c0392b; padding: 8px 20px;" onclick="confirmCancelOrder()">Confirm Cancel</button>
                </div>
            </div>
        </div>

        <div class="walkin-layout">
            <div class="walkin-form">
                <div class="section-wrapper walkin-form">
                    
                    <div>
                        <p class="section-title"><span class="section-dot"></span>Select Product</p>
                        <hr class="section-divider">
                        <div class="stock-grid" id="product-grid">
                            <?php
                            if ($inventory_result->num_rows > 0) {
                                while ($row = $inventory_result->fetch_assoc()) {
                                    $disabled = ($row['stock_list'] <= 0);
                                    $stock_class = ($row['status'] == 'low_stock') ? 'stock-warning' : (($row['status'] == 'out_of_stock') ? 'stock-low' : '');
                            ?>
                                    <div class="stock-item selectable <?php echo $stock_class; ?>"
                                        data-product="<?php echo htmlspecialchars($row['product']); ?>"
                                        data-price="<?php echo $row['price']; ?>"
                                        data-stock="<?php echo $row['stock_list']; ?>"
                                        onclick="<?php echo (!$disabled) ? "selectProduct(this, '" . addslashes($row['product']) . "', {$row['price']}, {$row['stock_list']})" : ''; ?>"
                                        style="<?php echo $disabled ? 'opacity:0.5; cursor:not-allowed;' : 'cursor:pointer;'; ?>">
                                        <span class="stock-name"><?php echo htmlspecialchars($row['product']); ?></span>
                                        <span class="stock-num" style="font-size: 24px;">₱<?php echo number_format($row['price'], 2); ?></span>
                                        <span class="stock-sub"><?php echo $row['stock_list']; ?> in stock</span>
                                    </div>
                            <?php
                                }
                            } else {
                                echo '<p>No products found in inventory.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Adjust Quantity</p>
                        <hr class="section-divider">
                        <div class="qty-row">
                            <button class="qty-btn" onclick="changeQty(-1)" id="qty-minus" disabled>−</button>
                            <span class="qty-display" id="qty-display">1</span>
                            <button class="qty-btn" onclick="changeQty(1)" id="qty-plus" disabled>+</button>
                        </div>
                        <div id="max-stock-warning" style="color: #e07070; font-size: 11px; margin-top: 5px; display: none;"></div>
                        
                        <button class="save-btn" id="add-cart-btn" onclick="addToCart()" style="margin-top: 15px; width: 100%;" disabled>
                            🛒 Add Item to Cart
                        </button>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Cart Summary</p>
                        <hr class="section-divider">
                        <div id="cart-list" style="background: cornsilk; padding: 10px; border-radius: 8px; border: 1px solid #e8dcc8; min-height: 50px;">
                            <p style="font-size: 12px; color: #8b6340; text-align: center; margin: 10px 0;">Cart is empty.</p>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #3b2208; margin-top: 15px;">
                            <span>Grand Total:</span><span id="grand-total-display">₱0.00</span>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Payment & Checkout</p>
                        <hr class="section-divider">
                        <div class="pay-row">
                            <button class="pay-btn active" onclick="selectPay(this, 'Cash')">💵 Cash</button>
                            <button class="pay-btn" onclick="selectPay(this, 'GCash')">📱 GCash</button>
                            <button class="pay-btn" onclick="selectPay(this, 'Cheque')">🧾 Cheque</button>
                        </div>

                        <details style="margin-top: 15px;">
                            <summary class="optional-toggle">+ Optional: Customer / Delivery Info</summary>
                            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 10px;">
                                <input type="text" id="customer-name" placeholder="Customer name" style="font-family: sans-serif; padding: 8px 12px; border: 1.5px solid #e8dcc8; border-radius: 8px; font-size: 13px; background: cornsilk;">
                                <input type="text" id="delivery-addr" placeholder="Delivery address (optional)" style="font-family: sans-serif; padding: 8px 12px; border: 1.5px solid #e8dcc8; border-radius: 8px; font-size: 13px; background: cornsilk;">
                            </div>
                        </details>

                        <button class="save-btn" onclick="saveOrder()" id="save-btn" style="margin-top: 15px; width: 100%;" disabled>
                            ✅ Complete Walk-in Order
                        </button>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <div class="section-header" style="margin-bottom: 10px;">
                            <span class="section-title"><span class="section-dot"></span> Recent Walk-in Orders</span>
                        </div>
                        <hr class="section-divider">
                        <table class="orders-table" id="order-list-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Customer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="order-list-body">
                                <?php
                                if ($orders_result->num_rows > 0) {
                                    while ($order = $orders_result->fetch_assoc()) {
                                ?>
                                        <tr data-id="<?php echo $order['id']; ?>">
                                            <td><?php echo htmlspecialchars($order['product']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td><span class="pay-badge"><?php echo $order['payment_method']; ?></span></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?: '—'); ?></td>
                                            <td style="display: flex; gap: 5px;">
                                                <?php if ($order['status'] !== 'delivered'): ?>
                                                    <button class="action-btn" style="background: saddlebrown; color: white; padding: 4px 8px; border-radius: 4px; border: none; font-size: 11px; cursor: pointer;" onclick="completeOrder(<?php echo $order['id']; ?>)" title="Mark as Completed">
                                                        ✅ Complete
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn delete" style="padding: 4px 8px; font-size: 11px;" onclick="openCancelModal(<?php echo $order['id']; ?>)" title="Cancel Order">
                                                    🔄 Cancel
                                                </button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr id="empty-row"><td colspan="6" style="text-align:center; color:#8b6340; padding:1.5rem;">No orders yet.</td></tr>';
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
                        $stock_query = "SELECT product, stock_list, status FROM inventory ORDER BY product";
                        $stock_result = $conn->query($stock_query);
                        while ($stock = $stock_result->fetch_assoc()) {
                            $status_icon = ($stock['status'] == 'low_stock') ? '⚠️' : (($stock['status'] == 'out_of_stock') ? '❌' : '✓');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['product']); ?></td>
                                <td class="side-val"><?php echo $stock['stock_list']; ?> units <?php echo $status_icon; ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // MULTI-ITEM CART STATE
        let cart = [];
        let selectedProduct = null;
        let selectedPrice = 0;
        let selectedStock = 0;
        let qty = 1;
        let selectedPayment = 'Cash';
        let pendingOrderId = null;

        function selectProduct(el, name, price, stock) {
            if (stock <= 0) {
                alert('This product is out of stock!');
                return;
            }

            document.querySelectorAll('.stock-item.selectable').forEach(i => i.classList.remove('selected'));
            el.classList.add('selected');
            
            selectedProduct = name;
            selectedPrice = price;
            selectedStock = stock;
            qty = 1;
            document.getElementById('qty-display').textContent = qty;

            document.getElementById('qty-minus').disabled = false;
            document.getElementById('qty-plus').disabled = false;
            document.getElementById('add-cart-btn').disabled = false;
            document.getElementById('max-stock-warning').style.display = 'none';
        }

        function changeQty(delta) {
            let newQty = qty + delta;
            if (newQty < 1) newQty = 1;
            
            // Check if quantity combined with what's already in the cart exceeds stock
            let existingCartItem = cart.find(item => item.product === selectedProduct);
            let totalRequested = newQty + (existingCartItem ? existingCartItem.quantity : 0);

            if (selectedProduct && totalRequested > selectedStock) {
                document.getElementById('max-stock-warning').style.display = 'block';
                document.getElementById('max-stock-warning').textContent = `⚠️ You can only add ${selectedStock - (existingCartItem ? existingCartItem.quantity : 0)} more.`;
                return;
            } else {
                document.getElementById('max-stock-warning').style.display = 'none';
            }
            
            qty = newQty;
            document.getElementById('qty-display').textContent = qty;
        }

        function addToCart() {
            if (!selectedProduct) return;

            let existingItemIndex = cart.findIndex(item => item.product === selectedProduct);
            
            if (existingItemIndex > -1) {
                cart[existingItemIndex].quantity += qty;
            } else {
                cart.push({
                    product: selectedProduct,
                    price: selectedPrice,
                    quantity: qty,
                    stock: selectedStock
                });
            }

            selectedProduct = null;
            document.querySelectorAll('.stock-item.selectable').forEach(i => i.classList.remove('selected'));
            document.getElementById('qty-minus').disabled = true;
            document.getElementById('qty-plus').disabled = true;
            document.getElementById('add-cart-btn').disabled = true;
            qty = 1;
            document.getElementById('qty-display').textContent = 1;

            renderCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        function renderCart() {
            const cartList = document.getElementById('cart-list');
            const grandTotalDisplay = document.getElementById('grand-total-display');
            const saveBtn = document.getElementById('save-btn');

            cartList.innerHTML = '';
            let grandTotal = 0;

            if (cart.length === 0) {
                cartList.innerHTML = '<p style="font-size: 12px; color: #8b6340; text-align: center; margin: 10px 0;">Cart is empty.</p>';
                grandTotalDisplay.textContent = '₱0.00';
                saveBtn.disabled = true;
                return;
            }

            cart.forEach((item, index) => {
                let itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;

                let div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div>
                        <div class="cart-item-name">${item.product}</div>
                        <div class="cart-item-details">${item.quantity} x ₱${item.price.toFixed(2)} = ₱${itemTotal.toFixed(2)}</div>
                    </div>
                    <button class="cart-remove-btn" onclick="removeFromCart(${index})">✖</button>
                `;
                cartList.appendChild(div);
            });

            grandTotalDisplay.textContent = '₱' + grandTotal.toFixed(2);
            saveBtn.disabled = false;
        }

        function selectPay(el, method) {
            document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            selectedPayment = method;
        }

        // --- SHOW RECEIPT FUNCTION ---
        function showReceipt(customer, payment) {
            document.getElementById('receipt-date').innerText = new Date().toLocaleString();
            document.getElementById('receipt-customer').innerText = customer;
            document.getElementById('receipt-payment').innerText = payment;

            const itemsContainer = document.getElementById('receipt-items-container');
            itemsContainer.innerHTML = '';
            let grandTotal = 0;

            cart.forEach((item) => {
                let itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;
                itemsContainer.innerHTML += `
                    <div class="receipt-row">
                        <span>${item.quantity}x ${item.product}</span>
                        <span>₱${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            });

            document.getElementById('receipt-total-amount').innerText = '₱' + grandTotal.toFixed(2);
            document.getElementById('receiptModal').style.display = 'flex';
        }

        // SAVE MULTI-ITEM ORDER
        function saveOrder() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }

            const customerName = document.getElementById('customer-name').value || 'Walk-in Customer';
            const deliveryAddress = document.getElementById('delivery-addr').value || '';

            document.getElementById('loading-overlay').style.display = 'flex';
            const saveBtn = document.getElementById('save-btn');
            saveBtn.disabled = true;

            const formData = new URLSearchParams();
            formData.append('action', 'save_order');
            formData.append('cart', JSON.stringify(cart)); 
            formData.append('payment_method', selectedPayment);
            formData.append('customer_name', customerName);
            formData.append('delivery_address', deliveryAddress);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        // Standardized success alert, then show receipt instead of reloading!
                        alert('✅ ' + data.message);
                        showReceipt(customerName, selectedPayment);
                    } else {
                        let msg = data.message.replace(/<br>/g, '\n');
                        alert('❌ Error:\n' + msg);
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('❌ Connection error. Please try again.');
                    saveBtn.disabled = false;
                });
        }

        // --- Complete Order Function ---
        function completeOrder(orderId) {
            if (!confirm('Mark order as completed?')) return;
            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                });
        }

        // --- Cancel Modals ---
        function openCancelModal(orderId) {
            pendingOrderId = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }
        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            pendingOrderId = null;
        }
        document.getElementById('cancel-reason').addEventListener('change', function() {
            document.getElementById('custom-reason-wrap').style.display = this.value === 'Other' ? 'block' : 'none';
        });

        function confirmCancelOrder() {
            if (!pendingOrderId) return;
            const reason = document.getElementById('cancel-reason').value;
            const customReason = document.getElementById('custom-reason').value.trim();

            if (reason === 'Other' && !customReason) {
                alert('Please specify a reason for cancellation.'); return;
            }

            const orderIdToCancel = pendingOrderId;
            document.getElementById('loading-overlay').style.display = 'flex';
            closeCancelModal();

            const formData = new URLSearchParams();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderIdToCancel);
            formData.append('return_reason', reason);
            formData.append('custom_reason', customReason);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { alert('✅ ' + data.message); location.reload(); }
                    else { alert('❌ Error: ' + data.message); document.getElementById('loading-overlay').style.display = 'none'; }
                });
        }
    </script>
</body>
=======
<?php
// ✅ MUST be the very first thing — no includes, no output before this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include 'db.php';

    // --- SAVE ORDER ACTION ---
    if ($_POST['action'] === 'save_order') {
        // Multi-item cart comes in as a JSON string
        $cart_json = $_POST['cart'];
        $cart = json_decode($cart_json, true);

        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);

        $errors = [];

        if (empty($cart)) $errors[] = "Cart is empty.";
        if (empty($payment_method)) $errors[] = "Payment method is required.";
        if (empty($customer_name)) $customer_name = "Walk-in Customer";

        // 1. Validation Loop: Check stock for EVERY item before inserting anything
        if (empty($errors)) {
            foreach ($cart as $item) {
                $p = mysqli_real_escape_string($conn, $item['product']);
                $q = (int)$item['quantity'];

                $product_result = $conn->query("SELECT stock_list FROM inventory WHERE product = '$p'");
                if ($product_result->num_rows === 0) {
                    $errors[] = "Product '$p' not found in inventory.";
                } else {
                    $product_data = $product_result->fetch_assoc();
                    $current_stock = $product_data['stock_list'];
                    if ($q > $current_stock) {
                        $errors[] = "Insufficient stock for '$p'. Only $current_stock available.";
                    }
                }
            }
        }

        // 2. Execution Loop: If all stock checks pass, insert orders and update inventory
        if (empty($errors)) {
            foreach ($cart as $item) {
                $p = mysqli_real_escape_string($conn, $item['product']);
                $q = (int)$item['quantity'];
                
                // Fetch price and stock again for exact calculation
                $product_result = $conn->query("SELECT price, stock_list FROM inventory WHERE product = '$p'");
                $product_data = $product_result->fetch_assoc();
                $price = $product_data['price'];
                $current_stock = $product_data['stock_list'];
                
                $total_price = $price * $q;

                // Insert into orders
                $insert_query = "INSERT INTO orders (product, quantity, payment_method, customer_name, delivery_address, total_price, status, order_date, order_type) 
                                 VALUES ('$p', $q, '$payment_method', '$customer_name', '$delivery_address', $total_price, 'processing', NOW(), 'walk-in')";
                
                if ($conn->query($insert_query)) {
                    // Deduct stock
                    $new_stock = $current_stock - $q;
                    $conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$p'");
                    $conn->query("UPDATE inventory SET status = CASE 
                        WHEN stock_list <= 0 THEN 'out_of_stock'
                        WHEN stock_list <= minimum_stock THEN 'low_stock'
                        ELSE 'in_stock'
                    END WHERE product = '$p'");
                }
            } // <-- Loop ends here!

            // ✅ LOG IT ONCE AFTER THE LOOP
            logAction($conn, 'Created Walk-in Order', "Processed multi-item order for $customer_name via $payment_method");
            
            // Standardized completion message
            echo json_encode(['success' => true, 'message' => 'Order set!']);
        } else {
            echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
        }
        exit;
    }

    // --- COMPLETE ORDER ACTION ---
    if ($_POST['action'] === 'complete_order') {
        $order_id = (int)$_POST['order_id'];
        if ($conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id")) {
            
            // ✅ LOG THE COMPLETION
            logAction($conn, 'Completed Order', "Marked Order #$order_id as delivered");
            
            echo json_encode(['success' => true, 'message' => 'Order completed!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit;
    }

    // --- DELETE ORDER ACTION ---
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
            echo json_encode(['success' => false, 'message' => "Order #$order_id not found in database"]);
            exit;
        }

        $order = $result->fetch_assoc();
        $price_result = $conn->query("SELECT price FROM inventory WHERE product = '{$order['product']}'");
        $price_row = $price_result->fetch_assoc();
        $loss = $price_row['price'] * $order['quantity'];

        $extra_note = !empty($custom_reason) ? " Additional notes: $custom_reason" : '';
        $return_notes = "Returned from cancelled order #$order_id. Customer: {$order['customer_name']}.$extra_note";

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
                logAction($conn, 'Cancelled Order', "Cancelled Order #$order_id. Reason: $return_reason");

                echo json_encode(['success' => true, 'message' => 'Order cancelled and return record created']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $conn->error]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert return: ' . $conn->error]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}


// ✅ HTML starts here — only on GET requests
include 'header.php';
include 'sidebar.php';
include 'db.php';

$inventory_query = "SELECT product, stock_list, price, minimum_stock, status FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);

$orders_query = "SELECT id, product, quantity, total_price, payment_method, customer_name, delivery_address, order_date, status 
                 FROM orders 
                 WHERE order_type = 'walk-in' 
                 ORDER BY order_date DESC LIMIT 20";
$orders_result = $conn->query($orders_query);

$returns_count_query = "SELECT COUNT(*) as count FROM returns WHERE date = CURDATE()";
$returns_count_result = $conn->query($returns_count_query);
$returns_count = $returns_count_result->fetch_assoc()['count'];
?>

<style>
    .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e8dcc8; }
    .cart-item-name { font-weight: bold; color: #3b2208; font-size: 13px; }
    .cart-item-details { font-size: 11px; color: #8b6340; }
    .cart-remove-btn { color: #c0392b; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: bold; }
    .cart-remove-btn:hover { color: #e74c3c; }

    /* --- RECEIPT & PRINT STYLES --- */
    .receipt-container { width: 320px; background: white; padding: 25px; border-radius: 8px; font-family: monospace; color: #000; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .receipt-header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
    .receipt-header h2 { font-size: 20px; margin: 0 0 5px; text-transform: uppercase; letter-spacing: 2px; }
    .receipt-header p { margin: 0; font-size: 12px; }
    .receipt-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; }
    .receipt-total { border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; font-size: 16px; font-weight: bold; }
    .receipt-footer { margin-top: 20px; text-align: center; font-size: 11px; border-top: 1px dashed #000; padding-top: 10px; }
    
    @media print {
        body * { visibility: hidden; }
        #receiptModal, #receiptModal * { visibility: visible; }
        #receiptModal { position: absolute; left: 0; top: 0; width: 100%; height: 100%; background: white; align-items: flex-start; justify-content: flex-start; }
        .receipt-container { width: 100%; max-width: 300px; box-shadow: none; margin: 0; padding: 0; border: none; }
        .no-print { display: none !important; }
    }
</style>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Walk-in POS</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="receiptModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10001; align-items: center; justify-content: center;">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h2>LA SEANALE</h2>
                    <p>Walk-in POS Receipt</p>
                    <p id="receipt-date"></p>
                </div>
                
                <div style="font-size: 12px; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px;">
                    <div class="receipt-row"><span>Customer:</span> <span id="receipt-customer"></span></div>
                    <div class="receipt-row"><span>Payment:</span> <span id="receipt-payment"></span></div>
                </div>

                <div id="receipt-items-container">
                    </div>

                <div class="receipt-row receipt-total">
                    <span>TOTAL</span>
                    <span id="receipt-total-amount"></span>
                </div>

                <div class="receipt-footer">
                    <p>Thank you for your purchase!</p>
                </div>

                <div class="no-print" style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                    <button class="save-btn" onclick="window.print()" style="flex: 1;">🖨️ Print</button>
                    <button class="btn-sm" onclick="location.reload()" style="flex: 1;">Done</button>
                </div>
            </div>
        </div>

        <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; border: 2px solid #c0392b;">
                <h3 style="color: #c0392b; margin-bottom: 15px; font-family: cursive;">Cancel Order</h3>
                <p style="margin-bottom: 15px; color: #555;">Please select a reason for cancelling this order:</p>

                <select id="cancel-reason" class="inv-input" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <option value="customer return">Customer Request</option>
                    <option value="wrong item">Wrong Item Ordered</option>
                    <option value="damaged">Damaged Product</option>
                    <option value="unsold">Out of Stock / Unsold</option>
                    <option value="expired">Expired</option>
                    <option value="Other">Other (specify)</option>
                </select>

                <div id="custom-reason-wrap" style="display: none; margin-bottom: 15px;">
                    <input type="text" id="custom-reason" class="inv-input" placeholder="Please specify reason..." style="width: 100%; padding: 10px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" onclick="closeCancelModal()">Cancel</button>
                    <button class="save-btn" style="background: #c0392b; padding: 8px 20px;" onclick="confirmCancelOrder()">Confirm Cancel</button>
                </div>
            </div>
        </div>

        <div class="walkin-layout">
            <div class="walkin-form">
                <div class="section-wrapper walkin-form">
                    
                    <div>
                        <p class="section-title"><span class="section-dot"></span>Select Product</p>
                        <hr class="section-divider">
                        <div class="stock-grid" id="product-grid">
                            <?php
                            if ($inventory_result->num_rows > 0) {
                                while ($row = $inventory_result->fetch_assoc()) {
                                    $disabled = ($row['stock_list'] <= 0);
                                    $stock_class = ($row['status'] == 'low_stock') ? 'stock-warning' : (($row['status'] == 'out_of_stock') ? 'stock-low' : '');
                            ?>
                                    <div class="stock-item selectable <?php echo $stock_class; ?>"
                                        data-product="<?php echo htmlspecialchars($row['product']); ?>"
                                        data-price="<?php echo $row['price']; ?>"
                                        data-stock="<?php echo $row['stock_list']; ?>"
                                        onclick="<?php echo (!$disabled) ? "selectProduct(this, '" . addslashes($row['product']) . "', {$row['price']}, {$row['stock_list']})" : ''; ?>"
                                        style="<?php echo $disabled ? 'opacity:0.5; cursor:not-allowed;' : 'cursor:pointer;'; ?>">
                                        <span class="stock-name"><?php echo htmlspecialchars($row['product']); ?></span>
                                        <span class="stock-num" style="font-size: 24px;">₱<?php echo number_format($row['price'], 2); ?></span>
                                        <span class="stock-sub"><?php echo $row['stock_list']; ?> in stock</span>
                                    </div>
                            <?php
                                }
                            } else {
                                echo '<p>No products found in inventory.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Adjust Quantity</p>
                        <hr class="section-divider">
                        <div class="qty-row">
                            <button class="qty-btn" onclick="changeQty(-1)" id="qty-minus" disabled>−</button>
                            <span class="qty-display" id="qty-display">1</span>
                            <button class="qty-btn" onclick="changeQty(1)" id="qty-plus" disabled>+</button>
                        </div>
                        <div id="max-stock-warning" style="color: #e07070; font-size: 11px; margin-top: 5px; display: none;"></div>
                        
                        <button class="save-btn" id="add-cart-btn" onclick="addToCart()" style="margin-top: 15px; width: 100%;" disabled>
                            🛒 Add Item to Cart
                        </button>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Cart Summary</p>
                        <hr class="section-divider">
                        <div id="cart-list" style="background: cornsilk; padding: 10px; border-radius: 8px; border: 1px solid #e8dcc8; min-height: 50px;">
                            <p style="font-size: 12px; color: #8b6340; text-align: center; margin: 10px 0;">Cart is empty.</p>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #3b2208; margin-top: 15px;">
                            <span>Grand Total:</span><span id="grand-total-display">₱0.00</span>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <p class="section-title"><span class="section-dot"></span>Payment & Checkout</p>
                        <hr class="section-divider">
                        <div class="pay-row">
                            <button class="pay-btn active" onclick="selectPay(this, 'Cash')">💵 Cash</button>
                            <button class="pay-btn" onclick="selectPay(this, 'GCash')">📱 GCash</button>
                            <button class="pay-btn" onclick="selectPay(this, 'Cheque')">🧾 Cheque</button>
                        </div>

                        <details style="margin-top: 15px;">
                            <summary class="optional-toggle">+ Optional: Customer / Delivery Info</summary>
                            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 10px;">
                                <input type="text" id="customer-name" placeholder="Customer name" style="font-family: sans-serif; padding: 8px 12px; border: 1.5px solid #e8dcc8; border-radius: 8px; font-size: 13px; background: cornsilk;">
                                <input type="text" id="delivery-addr" placeholder="Delivery address (optional)" style="font-family: sans-serif; padding: 8px 12px; border: 1.5px solid #e8dcc8; border-radius: 8px; font-size: 13px; background: cornsilk;">
                            </div>
                        </details>

                        <button class="save-btn" onclick="saveOrder()" id="save-btn" style="margin-top: 15px; width: 100%;" disabled>
                            ✅ Complete Walk-in Order
                        </button>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <div class="section-header" style="margin-bottom: 10px;">
                            <span class="section-title"><span class="section-dot"></span> Recent Walk-in Orders</span>
                        </div>
                        <hr class="section-divider">
                        <table class="orders-table" id="order-list-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Customer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="order-list-body">
                                <?php
                                if ($orders_result->num_rows > 0) {
                                    while ($order = $orders_result->fetch_assoc()) {
                                ?>
                                        <tr data-id="<?php echo $order['id']; ?>">
                                            <td><?php echo htmlspecialchars($order['product']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td><span class="pay-badge"><?php echo $order['payment_method']; ?></span></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?: '—'); ?></td>
                                            <td style="display: flex; gap: 5px;">
                                                <?php if ($order['status'] !== 'delivered'): ?>
                                                    <button class="action-btn" style="background: saddlebrown; color: white; padding: 4px 8px; border-radius: 4px; border: none; font-size: 11px; cursor: pointer;" onclick="completeOrder(<?php echo $order['id']; ?>)" title="Mark as Completed">
                                                        ✅ Complete
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn delete" style="padding: 4px 8px; font-size: 11px;" onclick="openCancelModal(<?php echo $order['id']; ?>)" title="Cancel Order">
                                                    🔄 Cancel
                                                </button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr id="empty-row"><td colspan="6" style="text-align:center; color:#8b6340; padding:1.5rem;">No orders yet.</td></tr>';
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
                        $stock_query = "SELECT product, stock_list, status FROM inventory ORDER BY product";
                        $stock_result = $conn->query($stock_query);
                        while ($stock = $stock_result->fetch_assoc()) {
                            $status_icon = ($stock['status'] == 'low_stock') ? '⚠️' : (($stock['status'] == 'out_of_stock') ? '❌' : '✓');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['product']); ?></td>
                                <td class="side-val"><?php echo $stock['stock_list']; ?> units <?php echo $status_icon; ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // MULTI-ITEM CART STATE
        let cart = [];
        let selectedProduct = null;
        let selectedPrice = 0;
        let selectedStock = 0;
        let qty = 1;
        let selectedPayment = 'Cash';
        let pendingOrderId = null;

        function selectProduct(el, name, price, stock) {
            if (stock <= 0) {
                alert('This product is out of stock!');
                return;
            }

            document.querySelectorAll('.stock-item.selectable').forEach(i => i.classList.remove('selected'));
            el.classList.add('selected');
            
            selectedProduct = name;
            selectedPrice = price;
            selectedStock = stock;
            qty = 1;
            document.getElementById('qty-display').textContent = qty;

            document.getElementById('qty-minus').disabled = false;
            document.getElementById('qty-plus').disabled = false;
            document.getElementById('add-cart-btn').disabled = false;
            document.getElementById('max-stock-warning').style.display = 'none';
        }

        function changeQty(delta) {
            let newQty = qty + delta;
            if (newQty < 1) newQty = 1;
            
            // Check if quantity combined with what's already in the cart exceeds stock
            let existingCartItem = cart.find(item => item.product === selectedProduct);
            let totalRequested = newQty + (existingCartItem ? existingCartItem.quantity : 0);

            if (selectedProduct && totalRequested > selectedStock) {
                document.getElementById('max-stock-warning').style.display = 'block';
                document.getElementById('max-stock-warning').textContent = `⚠️ You can only add ${selectedStock - (existingCartItem ? existingCartItem.quantity : 0)} more.`;
                return;
            } else {
                document.getElementById('max-stock-warning').style.display = 'none';
            }
            
            qty = newQty;
            document.getElementById('qty-display').textContent = qty;
        }

        function addToCart() {
            if (!selectedProduct) return;

            let existingItemIndex = cart.findIndex(item => item.product === selectedProduct);
            
            if (existingItemIndex > -1) {
                cart[existingItemIndex].quantity += qty;
            } else {
                cart.push({
                    product: selectedProduct,
                    price: selectedPrice,
                    quantity: qty,
                    stock: selectedStock
                });
            }

            selectedProduct = null;
            document.querySelectorAll('.stock-item.selectable').forEach(i => i.classList.remove('selected'));
            document.getElementById('qty-minus').disabled = true;
            document.getElementById('qty-plus').disabled = true;
            document.getElementById('add-cart-btn').disabled = true;
            qty = 1;
            document.getElementById('qty-display').textContent = 1;

            renderCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        function renderCart() {
            const cartList = document.getElementById('cart-list');
            const grandTotalDisplay = document.getElementById('grand-total-display');
            const saveBtn = document.getElementById('save-btn');

            cartList.innerHTML = '';
            let grandTotal = 0;

            if (cart.length === 0) {
                cartList.innerHTML = '<p style="font-size: 12px; color: #8b6340; text-align: center; margin: 10px 0;">Cart is empty.</p>';
                grandTotalDisplay.textContent = '₱0.00';
                saveBtn.disabled = true;
                return;
            }

            cart.forEach((item, index) => {
                let itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;

                let div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div>
                        <div class="cart-item-name">${item.product}</div>
                        <div class="cart-item-details">${item.quantity} x ₱${item.price.toFixed(2)} = ₱${itemTotal.toFixed(2)}</div>
                    </div>
                    <button class="cart-remove-btn" onclick="removeFromCart(${index})">✖</button>
                `;
                cartList.appendChild(div);
            });

            grandTotalDisplay.textContent = '₱' + grandTotal.toFixed(2);
            saveBtn.disabled = false;
        }

        function selectPay(el, method) {
            document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            selectedPayment = method;
        }

        // --- SHOW RECEIPT FUNCTION ---
        function showReceipt(customer, payment) {
            document.getElementById('receipt-date').innerText = new Date().toLocaleString();
            document.getElementById('receipt-customer').innerText = customer;
            document.getElementById('receipt-payment').innerText = payment;

            const itemsContainer = document.getElementById('receipt-items-container');
            itemsContainer.innerHTML = '';
            let grandTotal = 0;

            cart.forEach((item) => {
                let itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;
                itemsContainer.innerHTML += `
                    <div class="receipt-row">
                        <span>${item.quantity}x ${item.product}</span>
                        <span>₱${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            });

            document.getElementById('receipt-total-amount').innerText = '₱' + grandTotal.toFixed(2);
            document.getElementById('receiptModal').style.display = 'flex';
        }

        // SAVE MULTI-ITEM ORDER
        function saveOrder() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }

            const customerName = document.getElementById('customer-name').value || 'Walk-in Customer';
            const deliveryAddress = document.getElementById('delivery-addr').value || '';

            document.getElementById('loading-overlay').style.display = 'flex';
            const saveBtn = document.getElementById('save-btn');
            saveBtn.disabled = true;

            const formData = new URLSearchParams();
            formData.append('action', 'save_order');
            formData.append('cart', JSON.stringify(cart)); 
            formData.append('payment_method', selectedPayment);
            formData.append('customer_name', customerName);
            formData.append('delivery_address', deliveryAddress);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    if (data.success) {
                        // Standardized success alert, then show receipt instead of reloading!
                        alert('✅ ' + data.message);
                        showReceipt(customerName, selectedPayment);
                    } else {
                        let msg = data.message.replace(/<br>/g, '\n');
                        alert('❌ Error:\n' + msg);
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('❌ Connection error. Please try again.');
                    saveBtn.disabled = false;
                });
        }

        // --- Complete Order Function ---
        function completeOrder(orderId) {
            if (!confirm('Mark order as completed?')) return;
            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new URLSearchParams();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                });
        }

        // --- Cancel Modals ---
        function openCancelModal(orderId) {
            pendingOrderId = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }
        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            pendingOrderId = null;
        }
        document.getElementById('cancel-reason').addEventListener('change', function() {
            document.getElementById('custom-reason-wrap').style.display = this.value === 'Other' ? 'block' : 'none';
        });

        function confirmCancelOrder() {
            if (!pendingOrderId) return;
            const reason = document.getElementById('cancel-reason').value;
            const customReason = document.getElementById('custom-reason').value.trim();

            if (reason === 'Other' && !customReason) {
                alert('Please specify a reason for cancellation.'); return;
            }

            const orderIdToCancel = pendingOrderId;
            document.getElementById('loading-overlay').style.display = 'flex';
            closeCancelModal();

            const formData = new URLSearchParams();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderIdToCancel);
            formData.append('return_reason', reason);
            formData.append('custom_reason', customReason);

            fetch('walk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { alert('✅ ' + data.message); location.reload(); }
                    else { alert('❌ Error: ' + data.message); document.getElementById('loading-overlay').style.display = 'none'; }
                });
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>