<<<<<<< HEAD
<?php
include 'db.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
// Temporarily assume Customer ID 1 until login is built
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;

// Fetch their saved data to auto-fill the checkout form!
$cust_query = $conn->query("SELECT fname, lname, address FROM customer WHERE customer_id = $customer_id");
$cust_data = $cust_query ? $cust_query->fetch_assoc() : null;

$auto_name = trim(($cust_data['fname'] ?? '') . ' ' . ($cust_data['lname'] ?? ''));
$auto_address = $cust_data['address'] ?? '';

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    include 'db.php';
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $cart = json_decode($_POST['cart'], true);

    if (empty($customer_name) || empty($delivery_address) || empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all delivery details.']);
        exit;
    }

    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    $all_successful = true;
    $errors = [];

    // Grab the reference number from the Javascript request
    $payment_reference = isset($_POST['payment_reference']) ? mysqli_real_escape_string($conn, $_POST['payment_reference']) : '';

    // STRICT CHECK: If GCash is selected, they MUST provide a reference number
    if ($payment_method === 'GCash' && empty($payment_reference)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your GCash Reference Number.']);
        exit;
    }

    // ✅ THE GCASH FIX: We MUST use 'pending' here so the database doesn't crash on the strict ENUM rule!
    $initial_status = 'pending';

    foreach ($cart as $item) {
        $product = mysqli_real_escape_string($conn, $item['name']);
        $quantity = (int)$item['qty'];

        $stock_check = $conn->query("SELECT price, stock_list FROM inventory WHERE product = '$product'");
        if ($stock_check && $stock_check->num_rows > 0) {
            $row = $stock_check->fetch_assoc();
            if ($quantity > $row['stock_list']) {
                $all_successful = false;
                $errors[] = "Not enough stock for $product.";
                continue;
            }

            $price = $row['price'];
            $total_price = $price * $quantity;

            // Insert into orders
            $insert_query = "INSERT INTO orders (product, quantity, payment_method, payment_reference, customer_name, delivery_address, total_price, status, order_date, order_type) 
                             VALUES ('$product', $quantity, '$payment_method', '$payment_reference', '$customer_name', '$delivery_address', $total_price, '$initial_status', NOW(), 'online')";

            if ($conn->query($insert_query)) {
                // Deduct stock
                $new_stock = $row['stock_list'] - $quantity;
                $conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$product'");
                $conn->query("UPDATE inventory SET status = CASE 
                    WHEN stock_list <= 0 THEN 'out_of_stock'
                    WHEN stock_list <= minimum_stock THEN 'low_stock'
                    ELSE 'in_stock'
                END WHERE product = '$product'");
            } else {
                $all_successful = false;
                $errors[] = "Failed to process $product.";
            }
        }
    }

    if ($all_successful) {
        echo json_encode(['success' => true, 'message' => 'Order placed successfully! We will process it shortly.']);
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';

// Fetch only available inventory
$inventory_query = "SELECT product, stock_list, price, image FROM inventory WHERE stock_list > 0 ORDER BY product";
$inventory_result = $conn->query($inventory_query);
?>

<body>
    <main class="main shop-main" id="main">
        <h1 class="dashboard-title">Shop List</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing Order...</p>
            </div>
        </div>

        <div id="checkoutModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive;">Complete Your Order</h3>

                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Full Name *</label>
                        <input type="text" id="cust-name" class="inv-input" style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($auto_name); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Delivery Address *</label>
                        <input type="text" id="cust-address" class="inv-input" style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($auto_address); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Payment Method *</label>
                        <select id="cust-payment" class="inv-input" style="width: 100%; padding: 10px;" onchange="togglePaymentUI()">
                            <option value="Cash">Cash on Delivery (COD)</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>

                    <div id="gcash-ui" style="display: none; background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #cce5ff; text-align: center;">
                        <p style="font-size: 13px; color: #004085; margin-bottom: 10px;"><strong>Scan to Pay via GCash</strong><br>La Seanale Bakeshop: 0912-345-6789</p>

                        <img src="./assets/gcash-qr.png" alt="GCash QR Code" style="width: 150px; height: 150px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #0056b3;">

                        <p style="font-size: 11px; color: #555; margin-bottom: 5px;">Please enter the Reference No. below after paying:</p>
                        <input type="text" id="gcash-ref" class="inv-input" style="width: 100%; padding: 8px; text-align: center;" placeholder="e.g. 10023948572">
                    </div>

                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" style="background: #ccc; border: none; padding: 10px 15px;" onclick="closeCheckout()">Cancel</button>
                    <button class="save-btn" style="background: #2e7d32; padding: 10px 20px;" onclick="submitOrder()">Confirm Order</button>
                </div>
            </div>
        </div>

        <div class="shop-layout">

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Browse Products</span>
                </div>
                <hr class="section-divider">

                <div class="food-grid" id="foodGrid">
                    <?php
                    if ($inventory_result->num_rows > 0) {
                        while ($row = $inventory_result->fetch_assoc()) {
                            
                            $product_name = htmlspecialchars($row['product']);
                            $price = $row['price'];
                            $stock = $row['stock_list'];
                            
                            // ✅ SMART IMAGE FINDER LOGIC
                            $db_image = $row['image'];
                            $final_image_src = '';

                            if (!empty($db_image)) {
                                // Strip out any relative path dots/slashes
                                $clean_path = ltrim(str_replace(['../', './'], '', $db_image), '/');
                                
                                // Ask the server exactly where this file lives relative to dashboard.php
                                if (file_exists('admin/' . $clean_path)) {
                                    $final_image_src = 'admin/' . $clean_path; // Found it inside the admin folder
                                } elseif (file_exists($clean_path)) {
                                    $final_image_src = $clean_path; // Found it in the main folder
                                } else {
                                    $final_image_src = $db_image; // Fallback
                                }
                            }
                            
                            $has_image = !empty($final_image_src);
                    ?>
                            <div class="food-card" data-name="<?php echo $product_name; ?>" data-price="<?php echo $price; ?>" data-stock="<?php echo $stock; ?>">
                                <?php if ($has_image): ?>
                                    <div class="food-img" style="background-image: url('<?php echo htmlspecialchars($final_image_src); ?>'); background-size: cover; background-position: center; height: 150px;"></div>
                                <?php else: ?>
                                    <div class="food-img" style="background:#f3ece0; display:flex; align-items:center; justify-content:center; font-size:13px; color:#8b6340; font-family:sans-serif; height: 150px;">
                                        <?php echo $product_name; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="food-card-body">
                                    <p class="food-name"><?php echo $product_name; ?></p>
                                    <p class="food-desc"><?php echo $stock; ?> items available in stock.</p>
                                    <p class="food-price">₱<?php echo number_format($price, 2); ?></p>
                                    <button class="add-btn" onclick="addToCart('<?php echo addslashes($product_name); ?>', <?php echo $price; ?>, <?php echo $stock; ?>)">+ Add to cart</button>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<p style="text-align: center; color: #8b6340; width: 100%; padding: 2rem;">No products currently available.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="cart-panel">
                <div class="cart-title">
                    <i class="fa-solid fa-cart-shopping"></i> Your Cart
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>
                <hr class="cart-section-divider">

                <div id="cartItems">
                    <p class="cart-empty" id="emptyCartText">No items yet.<br>Add something to get started!</p>
                </div>

                <hr class="cart-divider">

                <div class="cart-total-row">
                    <span>Total</span>
                    <span id="cartTotal">₱0.00</span>
                </div>
                <button class="checkout-btn" id="checkoutBtn" onclick="openCheckout()" disabled>Place Order</button>
                <button class="clear-btn" id="clearBtn" onclick="clearCart()">Clear cart</button>
            </div>

        </div>
    </main>

    <script>
        let cart = [];

        function addToCart(name, price, maxStock) {
            const existingItem = cart.find(item => item.name === name);

            if (existingItem) {
                if (existingItem.qty < maxStock) {
                    existingItem.qty++;
                } else {
                    alert('Cannot add more! Only ' + maxStock + ' available in stock.');
                }
            } else {
                cart.push({
                    name: name,
                    price: price,
                    qty: 1,
                    maxStock: maxStock
                });
            }
            renderCart();
        }

        function updateItemQty(name, delta) {
            const item = cart.find(i => i.name === name);
            if (item) {
                const newQty = item.qty + delta;
                if (newQty > item.maxStock) {
                    alert('Cannot add more! Only ' + item.maxStock + ' available.');
                    return;
                }
                if (newQty <= 0) {
                    cart = cart.filter(i => i.name !== name);
                } else {
                    item.qty = newQty;
                }
                renderCart();
            }
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            const badge = document.getElementById('cartBadge');
            const totalEl = document.getElementById('cartTotal');
            const checkoutBtn = document.getElementById('checkoutBtn');

            container.innerHTML = '';
            let total = 0;
            let totalItems = 0;

            if (cart.length === 0) {
                container.innerHTML = '<p class="cart-empty" id="emptyCartText">No items yet.<br>Add something to get started!</p>';
                badge.textContent = '0';
                totalEl.textContent = '₱0.00';
                checkoutBtn.disabled = true;
                return;
            }

            cart.forEach(item => {
                total += item.price * item.qty;
                totalItems += item.qty;

                const itemDiv = document.createElement('div');
                itemDiv.style.display = 'flex';
                itemDiv.style.justifyContent = 'space-between';
                itemDiv.style.alignItems = 'center';
                itemDiv.style.marginBottom = '10px';
                itemDiv.style.fontSize = '14px';
                itemDiv.style.fontFamily = 'sans-serif';

                itemDiv.innerHTML = `
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: #3b2208;">${item.name}</div>
                        <div style="color: #8b6340;">₱${item.price.toFixed(2)} x ${item.qty}</div>
                    </div>
                    <div style="display: flex; gap: 5px; align-items: center;">
                        <button onclick="updateItemQty('${item.name}', -1)" style="width: 25px; height: 25px; background: #e8dcc8; border: none; border-radius: 4px; cursor: pointer; color: saddlebrown; font-weight: bold;">-</button>
                        <span>${item.qty}</span>
                        <button onclick="updateItemQty('${item.name}', 1)" style="width: 25px; height: 25px; background: saddlebrown; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold;">+</button>
                    </div>
                `;
                container.appendChild(itemDiv);
            });

            badge.textContent = totalItems;
            totalEl.textContent = '₱' + total.toFixed(2);
            checkoutBtn.disabled = false;
        }

        function openCheckout() {
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        function submitOrder() {
            const name = document.getElementById('cust-name').value.trim();
            const address = document.getElementById('cust-address').value.trim();
            const payment = document.getElementById('cust-payment').value;
            const gcashRef = document.getElementById('gcash-ref').value.trim();

            if (!name || !address) {
                alert('Please fill out your Name and Address to continue.');
                return;
            }

            document.getElementById('loading-overlay').style.display = 'flex';
            closeCheckout();

            const formData = new URLSearchParams();
            formData.append('action', 'checkout');
            formData.append('customer_name', name);
            formData.append('delivery_address', address);
            formData.append('payment_method', payment);
            formData.append('payment_reference', gcashRef);
            formData.append('cart', JSON.stringify(cart)); 

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
                        alert(data.message);
                        clearCart();
                        window.location.href = 'order_list.php'; 
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    console.error(err);
                    alert('Network error. Please try again.');
                });
        }

        function togglePaymentUI() {
            const paymentMethod = document.getElementById('cust-payment').value;
            const gcashUI = document.getElementById('gcash-ui');

            if (paymentMethod === 'GCash') {
                gcashUI.style.display = 'block';
            } else {
                gcashUI.style.display = 'none';
                document.getElementById('gcash-ref').value = ''; 
            }
        }
    </script>
</body>
=======
<?php
include 'db.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
// Temporarily assume Customer ID 1 until login is built
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;

// Fetch their saved data to auto-fill the checkout form!
$cust_query = $conn->query("SELECT fname, lname, address FROM customer WHERE customer_id = $customer_id");
$cust_data = $cust_query ? $cust_query->fetch_assoc() : null;

$auto_name = trim(($cust_data['fname'] ?? '') . ' ' . ($cust_data['lname'] ?? ''));
$auto_address = $cust_data['address'] ?? '';

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    include 'db.php';
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $cart = json_decode($_POST['cart'], true);

    if (empty($customer_name) || empty($delivery_address) || empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all delivery details.']);
        exit;
    }

    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    $all_successful = true;
    $errors = [];

    // Grab the reference number from the Javascript request
    $payment_reference = isset($_POST['payment_reference']) ? mysqli_real_escape_string($conn, $_POST['payment_reference']) : '';

    // STRICT CHECK: If GCash is selected, they MUST provide a reference number
    if ($payment_method === 'GCash' && empty($payment_reference)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your GCash Reference Number.']);
        exit;
    }

    // ✅ THE GCASH FIX: We MUST use 'pending' here so the database doesn't crash on the strict ENUM rule!
    $initial_status = 'pending';

    foreach ($cart as $item) {
        $product = mysqli_real_escape_string($conn, $item['name']);
        $quantity = (int)$item['qty'];

        $stock_check = $conn->query("SELECT price, stock_list FROM inventory WHERE product = '$product'");
        if ($stock_check && $stock_check->num_rows > 0) {
            $row = $stock_check->fetch_assoc();
            if ($quantity > $row['stock_list']) {
                $all_successful = false;
                $errors[] = "Not enough stock for $product.";
                continue;
            }

            $price = $row['price'];
            $total_price = $price * $quantity;

            // Insert into orders
            $insert_query = "INSERT INTO orders (product, quantity, payment_method, payment_reference, customer_name, delivery_address, total_price, status, order_date, order_type) 
                             VALUES ('$product', $quantity, '$payment_method', '$payment_reference', '$customer_name', '$delivery_address', $total_price, '$initial_status', NOW(), 'online')";

            if ($conn->query($insert_query)) {
                // Deduct stock
                $new_stock = $row['stock_list'] - $quantity;
                $conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$product'");
                $conn->query("UPDATE inventory SET status = CASE 
                    WHEN stock_list <= 0 THEN 'out_of_stock'
                    WHEN stock_list <= minimum_stock THEN 'low_stock'
                    ELSE 'in_stock'
                END WHERE product = '$product'");
            } else {
                $all_successful = false;
                $errors[] = "Failed to process $product.";
            }
        }
    }

    if ($all_successful) {
        echo json_encode(['success' => true, 'message' => 'Order placed successfully! We will process it shortly.']);
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }
    exit;
}

// ✅ HTML starts here
include 'header.php';
include 'sidebar.php';

// Fetch only available inventory
$inventory_query = "SELECT product, stock_list, price, image FROM inventory WHERE stock_list > 0 ORDER BY product";
$inventory_result = $conn->query($inventory_query);
?>

<body>
    <main class="main shop-main" id="main">
        <h1 class="dashboard-title">Shop List</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing Order...</p>
            </div>
        </div>

        <div id="checkoutModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; padding: 25px; width: 400px; max-width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive;">Complete Your Order</h3>

                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Full Name *</label>
                        <input type="text" id="cust-name" class="inv-input" style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($auto_name); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Delivery Address *</label>
                        <input type="text" id="cust-address" class="inv-input" style="width: 100%; padding: 10px;" value="<?php echo htmlspecialchars($auto_address); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-family: sans-serif; color: #555;">Payment Method *</label>
                        <select id="cust-payment" class="inv-input" style="width: 100%; padding: 10px;" onchange="togglePaymentUI()">
                            <option value="Cash">Cash on Delivery (COD)</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>

                    <div id="gcash-ui" style="display: none; background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #cce5ff; text-align: center;">
                        <p style="font-size: 13px; color: #004085; margin-bottom: 10px;"><strong>Scan to Pay via GCash</strong><br>La Seanale Bakeshop: 0912-345-6789</p>

                        <img src="./assets/gcash-qr.png" alt="GCash QR Code" style="width: 150px; height: 150px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #0056b3;">

                        <p style="font-size: 11px; color: #555; margin-bottom: 5px;">Please enter the Reference No. below after paying:</p>
                        <input type="text" id="gcash-ref" class="inv-input" style="width: 100%; padding: 8px; text-align: center;" placeholder="e.g. 10023948572">
                    </div>

                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="btn-sm" style="background: #ccc; border: none; padding: 10px 15px;" onclick="closeCheckout()">Cancel</button>
                    <button class="save-btn" style="background: #2e7d32; padding: 10px 20px;" onclick="submitOrder()">Confirm Order</button>
                </div>
            </div>
        </div>

        <div class="shop-layout">

            <div class="section-wrapper">
                <div class="section-header">
                    <span class="section-title"><span class="section-dot"></span> Browse Products</span>
                </div>
                <hr class="section-divider">

                <div class="food-grid" id="foodGrid">
                    <?php
                    if ($inventory_result->num_rows > 0) {
                        while ($row = $inventory_result->fetch_assoc()) {
                            
                            $product_name = htmlspecialchars($row['product']);
                            $price = $row['price'];
                            $stock = $row['stock_list'];
                            
                            // ✅ SMART IMAGE FINDER LOGIC
                            $db_image = $row['image'];
                            $final_image_src = '';

                            if (!empty($db_image)) {
                                // Strip out any relative path dots/slashes
                                $clean_path = ltrim(str_replace(['../', './'], '', $db_image), '/');
                                
                                // Ask the server exactly where this file lives relative to dashboard.php
                                if (file_exists('admin/' . $clean_path)) {
                                    $final_image_src = 'admin/' . $clean_path; // Found it inside the admin folder
                                } elseif (file_exists($clean_path)) {
                                    $final_image_src = $clean_path; // Found it in the main folder
                                } else {
                                    $final_image_src = $db_image; // Fallback
                                }
                            }
                            
                            $has_image = !empty($final_image_src);
                    ?>
                            <div class="food-card" data-name="<?php echo $product_name; ?>" data-price="<?php echo $price; ?>" data-stock="<?php echo $stock; ?>">
                                <?php if ($has_image): ?>
                                    <div class="food-img" style="background-image: url('<?php echo htmlspecialchars($final_image_src); ?>'); background-size: cover; background-position: center; height: 150px;"></div>
                                <?php else: ?>
                                    <div class="food-img" style="background:#f3ece0; display:flex; align-items:center; justify-content:center; font-size:13px; color:#8b6340; font-family:sans-serif; height: 150px;">
                                        <?php echo $product_name; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="food-card-body">
                                    <p class="food-name"><?php echo $product_name; ?></p>
                                    <p class="food-desc"><?php echo $stock; ?> items available in stock.</p>
                                    <p class="food-price">₱<?php echo number_format($price, 2); ?></p>
                                    <button class="add-btn" onclick="addToCart('<?php echo addslashes($product_name); ?>', <?php echo $price; ?>, <?php echo $stock; ?>)">+ Add to cart</button>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<p style="text-align: center; color: #8b6340; width: 100%; padding: 2rem;">No products currently available.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="cart-panel">
                <div class="cart-title">
                    <i class="fa-solid fa-cart-shopping"></i> Your Cart
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>
                <hr class="cart-section-divider">

                <div id="cartItems">
                    <p class="cart-empty" id="emptyCartText">No items yet.<br>Add something to get started!</p>
                </div>

                <hr class="cart-divider">

                <div class="cart-total-row">
                    <span>Total</span>
                    <span id="cartTotal">₱0.00</span>
                </div>
                <button class="checkout-btn" id="checkoutBtn" onclick="openCheckout()" disabled>Place Order</button>
                <button class="clear-btn" id="clearBtn" onclick="clearCart()">Clear cart</button>
            </div>

        </div>
    </main>

    <script>
        let cart = [];

        function addToCart(name, price, maxStock) {
            const existingItem = cart.find(item => item.name === name);

            if (existingItem) {
                if (existingItem.qty < maxStock) {
                    existingItem.qty++;
                } else {
                    alert('Cannot add more! Only ' + maxStock + ' available in stock.');
                }
            } else {
                cart.push({
                    name: name,
                    price: price,
                    qty: 1,
                    maxStock: maxStock
                });
            }
            renderCart();
        }

        function updateItemQty(name, delta) {
            const item = cart.find(i => i.name === name);
            if (item) {
                const newQty = item.qty + delta;
                if (newQty > item.maxStock) {
                    alert('Cannot add more! Only ' + item.maxStock + ' available.');
                    return;
                }
                if (newQty <= 0) {
                    cart = cart.filter(i => i.name !== name);
                } else {
                    item.qty = newQty;
                }
                renderCart();
            }
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            const badge = document.getElementById('cartBadge');
            const totalEl = document.getElementById('cartTotal');
            const checkoutBtn = document.getElementById('checkoutBtn');

            container.innerHTML = '';
            let total = 0;
            let totalItems = 0;

            if (cart.length === 0) {
                container.innerHTML = '<p class="cart-empty" id="emptyCartText">No items yet.<br>Add something to get started!</p>';
                badge.textContent = '0';
                totalEl.textContent = '₱0.00';
                checkoutBtn.disabled = true;
                return;
            }

            cart.forEach(item => {
                total += item.price * item.qty;
                totalItems += item.qty;

                const itemDiv = document.createElement('div');
                itemDiv.style.display = 'flex';
                itemDiv.style.justifyContent = 'space-between';
                itemDiv.style.alignItems = 'center';
                itemDiv.style.marginBottom = '10px';
                itemDiv.style.fontSize = '14px';
                itemDiv.style.fontFamily = 'sans-serif';

                itemDiv.innerHTML = `
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: #3b2208;">${item.name}</div>
                        <div style="color: #8b6340;">₱${item.price.toFixed(2)} x ${item.qty}</div>
                    </div>
                    <div style="display: flex; gap: 5px; align-items: center;">
                        <button onclick="updateItemQty('${item.name}', -1)" style="width: 25px; height: 25px; background: #e8dcc8; border: none; border-radius: 4px; cursor: pointer; color: saddlebrown; font-weight: bold;">-</button>
                        <span>${item.qty}</span>
                        <button onclick="updateItemQty('${item.name}', 1)" style="width: 25px; height: 25px; background: saddlebrown; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold;">+</button>
                    </div>
                `;
                container.appendChild(itemDiv);
            });

            badge.textContent = totalItems;
            totalEl.textContent = '₱' + total.toFixed(2);
            checkoutBtn.disabled = false;
        }

        function openCheckout() {
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        function submitOrder() {
            const name = document.getElementById('cust-name').value.trim();
            const address = document.getElementById('cust-address').value.trim();
            const payment = document.getElementById('cust-payment').value;
            const gcashRef = document.getElementById('gcash-ref').value.trim();

            if (!name || !address) {
                alert('Please fill out your Name and Address to continue.');
                return;
            }

            document.getElementById('loading-overlay').style.display = 'flex';
            closeCheckout();

            const formData = new URLSearchParams();
            formData.append('action', 'checkout');
            formData.append('customer_name', name);
            formData.append('delivery_address', address);
            formData.append('payment_method', payment);
            formData.append('payment_reference', gcashRef);
            formData.append('cart', JSON.stringify(cart)); 

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
                        alert(data.message);
                        clearCart();
                        window.location.href = 'order_list.php'; 
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    console.error(err);
                    alert('Network error. Please try again.');
                });
        }

        function togglePaymentUI() {
            const paymentMethod = document.getElementById('cust-payment').value;
            const gcashUI = document.getElementById('gcash-ui');

            if (paymentMethod === 'GCash') {
                gcashUI.style.display = 'block';
            } else {
                gcashUI.style.display = 'none';
                document.getElementById('gcash-ref').value = ''; 
            }
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>