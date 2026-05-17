<<<<<<< HEAD
<?php
include 'header.php';
include 'sidebar.php';
include 'db.php';

// ✅ FIX: Force PHP to always use Philippine Time
date_default_timezone_set('Asia/Manila');

// ✅ FIX: Use MySQL's CURDATE() instead of PHP's $today variable

// Today's Total Sales (Exclude Cancelled Orders!)
$sales_query = "SELECT COALESCE(SUM(total_price), 0) as total_sales, COUNT(*) as order_count 
                FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'";
$sales_result = $conn->query($sales_query);
$sales_data = $sales_result->fetch_assoc();
$today_sales = $sales_data['total_sales'];
$today_orders = $sales_data['order_count'];

// Walk-in Sales (Exclude Cancelled Orders)
$walkin_query = "SELECT COALESCE(SUM(total_price), 0) as walkin_sales, COUNT(*) as walkin_count 
                 FROM orders WHERE DATE(order_date) = CURDATE() AND order_type = 'walk-in' AND status != 'cancelled'";
$walkin_result = $conn->query($walkin_query);
$walkin_data = $walkin_result->fetch_assoc();
$walkin_sales = $walkin_data['walkin_sales'];
$walkin_orders = $walkin_data['walkin_count'];

// Online Sales (Exclude Cancelled Orders)
$online_query = "SELECT COALESCE(SUM(total_price), 0) as online_sales, COUNT(*) as online_count 
                 FROM orders WHERE DATE(order_date) = CURDATE() AND order_type = 'online' AND status != 'cancelled'";
$online_result = $conn->query($online_query);
$online_data = $online_result->fetch_assoc();
$online_sales = $online_data['online_sales'];
$online_orders = $online_data['online_count'];

// Active Deliveries (Count all online orders that are NOT delivered or cancelled yet)
$active_query = "SELECT COUNT(*) as active_count 
                 FROM orders WHERE order_type = 'online' AND status NOT IN ('delivered', 'cancelled')";
$active_result = $conn->query($active_query);
$active_data = $active_result->fetch_assoc();
$active_deliveries = $active_data['active_count'];

// Low Stock Items (from inventory)
$lowstock_query = "SELECT COUNT(*) as lowstock_count 
                   FROM inventory WHERE status IN ('low_stock', 'out_of_stock')";
$lowstock_result = $conn->query($lowstock_query);
$lowstock_data = $lowstock_result->fetch_assoc();
$lowstock_items = $lowstock_data['lowstock_count'];

// Stock Levels (get all products)
$stock_query = "SELECT product, stock_list, price, minimum_stock, status 
                FROM inventory ORDER BY status = 'low_stock' DESC, stock_list ASC LIMIT 6";
$stock_result = $conn->query($stock_query);

// Recent Orders
$recent_query = "SELECT id, order_date, payment_method, customer_name, product, quantity, total_price, status, order_type 
                 FROM orders ORDER BY order_date DESC LIMIT 10";
$recent_result = $conn->query($recent_query);

// Pending Riders Verification
$pending_riders_query = "SELECT COUNT(*) as pending_count FROM riders WHERE status = 'pending'";
$pending_riders_result = $conn->query($pending_riders_query);
$pending_riders = $pending_riders_result->fetch_assoc();
$pending_riders_count = $pending_riders['pending_count'];

// Monthly Returns Loss
$monthly_loss_query = "SELECT COALESCE(SUM(loss), 0) as total_loss 
                       FROM returns WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
$monthly_loss_result = $conn->query($monthly_loss_query);
$monthly_loss_data = $monthly_loss_result->fetch_assoc();
$monthly_loss = $monthly_loss_data['total_loss'];
?>

<body>
    <?php if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true): ?>
        <div id="loader-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fdf6ed; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: opacity 0.5s ease;">
            <img src="../assets/hero.png" alt="Loading..." style="max-width: 200px; animation: pulseLogo 1.5s infinite ease-in-out;">
            <h3 style="color: saddlebrown; font-family: cursive; margin-top: 20px; font-weight: 500;">Preparing your dashboard...</h3>
            <style>
                @keyframes pulseLogo {
                    0% { transform: scale(0.95); opacity: 0.7; }
                    50% { transform: scale(1.05); opacity: 1; }
                    100% { transform: scale(0.95); opacity: 0.7; }
                }
                body { overflow: hidden; } /* Prevent scrolling while loading */
            </style>
        </div>
        <?php
        // Destroy the flag so the loader doesn't show on page refreshes
        unset($_SESSION['just_logged_in']);
        ?>
    <?php endif; ?>
    <main class="main" id="main">
        <h1 style="font-size: 28px; margin-bottom: 5px;">Dashboard</h1>
        <hr class="divider">

        <!-- STATS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <!-- Today's Total Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #4CAF50;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">TODAY'S TOTAL SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($today_sales, 2); ?></p>
                <span style="color:#777"><?php echo $today_orders; ?> Orders</span>
            </div>

            <!-- Walk-in Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #2196F3;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">WALK-IN SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($walkin_sales, 2); ?></p>
                <span style="color:#777"><?php echo $walkin_orders; ?> Orders</span>
            </div>

            <!-- Online Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #FF9800;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">ONLINE SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($online_sales, 2); ?></p>
                <span style="color:#777"><?php echo $online_orders; ?> Orders</span>
            </div>

            <!-- Active Deliveries -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #F44336;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">ACTIVE DELIVERIES</p>
                <p style="font-size: 22px; font-weight: bold;"><?php echo $active_deliveries; ?></p>
                <span style="color:#777">Pending Orders</span>
            </div>
        </div>
        <!-- ❌ I REMOVED THE EXTRA BROKEN </div> TAG THAT WAS HERE! -->

        <!-- STOCK LEVELS SECTION -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Stock Levels</span>
                <button class="btn-sm" onclick="window.location.href='inventory.php'">Manage</button>
            </div>
            <hr class="section-divider">
            <div class="stock-grid">
                <?php
                if ($stock_result->num_rows > 0) {
                    while ($row = $stock_result->fetch_assoc()) {
                        $stock_status = '';
                        $status_class = '';
                        if ($row['status'] == 'low_stock') {
                            $stock_status = '⚠️ Low Stock';
                            $status_class = 'stock-warning';
                        } elseif ($row['status'] == 'out_of_stock') {
                            $stock_status = '❌ Out of Stock';
                            $status_class = 'stock-low';
                        } else {
                            $stock_status = '✓ OK';
                            $status_class = 'stock-ok';
                        }
                ?>
                        <div class="stock-item">
                            <span class="stock-name"><?php echo htmlspecialchars($row['product']); ?></span>
                            <span class="stock-num"><?php echo $row['stock_list']; ?></span>
                            <span class="stock-sub">₱<?php echo number_format($row['price'], 2); ?> each</span>
                            <span class="<?php echo $status_class; ?>"><?php echo $stock_status; ?></span>
                        </div>
                <?php
                    }
                } else {
                    echo '<p>No inventory items found.</p>';
                }
                ?>
            </div>
        </div>

        <!-- RECENT ORDERS SECTION -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Recent Orders</span>
                <button class="btn-sm" onclick="window.location.href='walk.php'">View All</button>
            </div>
            <hr class="section-divider">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($recent_result->num_rows > 0) {
                        while ($order = $recent_result->fetch_assoc()) {
                            // Determine order type
                            $db_type = isset($order['order_type']) ? $order['order_type'] : 'online';

                            // Format for display and CSS classes
                            if ($db_type === 'walk-in') {
                                $order_type = 'Walk in';
                                $type_class = 'type-walkin';
                            } else {
                                $order_type = 'Online';
                                $type_class = 'type-online';
                            }

                            // Determine status class
                            $status_class = '';
                            if ($order['status'] == 'pending' || $order['status'] == 'pending_verification' || $order['status'] == 'ready_for_rider') {
                                $status_class = 'status-pending';
                            } elseif ($order['status'] == 'processing') {
                                $status_class = 'status-processing';
                            } else {
                                $status_class = 'status-delivered';
                            }

                            // Format payment method
                            $payment_display = '';
                            if ($order['payment_method'] == 'cod') {
                                $payment_display = 'Cash';
                            } elseif ($order['payment_method'] == 'gcash') {
                                $payment_display = 'GCash';
                            } elseif ($order['payment_method'] == 'bank_transfer') {
                                $payment_display = 'Bank Transfer';
                            } else {
                                $payment_display = ucfirst($order['payment_method']);
                            }

                            // Format time
                            $order_time = date('h:i A', strtotime($order['order_date']));
                    ?>
                            <tr>
                                <td><?php echo $order_time; ?></td>
                                <td><span class="type-badge <?php echo $type_class; ?>"><?php echo $order_type; ?></span></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><span class="pay-badge"><?php echo $payment_display; ?></span></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="8" style="text-align:center; padding:20px;">No orders found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Wait for the page to fully load, wait 1.5 seconds for effect, then fade out
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loader = document.getElementById('loader-overlay');
                if (loader) {
                    loader.style.opacity = '0'; // Start fade out
                    document.body.style.overflow = 'auto'; // Restore scrolling

                    // Remove from DOM after fade completes
                    setTimeout(() => loader.style.display = 'none', 500);
                }
            }, 1500);
        });
    </script>

</body>
=======
<?php
include 'header.php';
include 'sidebar.php';
include 'db.php';

// ✅ FIX: Force PHP to always use Philippine Time
date_default_timezone_set('Asia/Manila');

// ✅ FIX: Use MySQL's CURDATE() instead of PHP's $today variable

// Today's Total Sales (Exclude Cancelled Orders!)
$sales_query = "SELECT COALESCE(SUM(total_price), 0) as total_sales, COUNT(*) as order_count 
                FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'";
$sales_result = $conn->query($sales_query);
$sales_data = $sales_result->fetch_assoc();
$today_sales = $sales_data['total_sales'];
$today_orders = $sales_data['order_count'];

// Walk-in Sales (Exclude Cancelled Orders)
$walkin_query = "SELECT COALESCE(SUM(total_price), 0) as walkin_sales, COUNT(*) as walkin_count 
                 FROM orders WHERE DATE(order_date) = CURDATE() AND order_type = 'walk-in' AND status != 'cancelled'";
$walkin_result = $conn->query($walkin_query);
$walkin_data = $walkin_result->fetch_assoc();
$walkin_sales = $walkin_data['walkin_sales'];
$walkin_orders = $walkin_data['walkin_count'];

// Online Sales (Exclude Cancelled Orders)
$online_query = "SELECT COALESCE(SUM(total_price), 0) as online_sales, COUNT(*) as online_count 
                 FROM orders WHERE DATE(order_date) = CURDATE() AND order_type = 'online' AND status != 'cancelled'";
$online_result = $conn->query($online_query);
$online_data = $online_result->fetch_assoc();
$online_sales = $online_data['online_sales'];
$online_orders = $online_data['online_count'];

// Active Deliveries (Count all online orders that are NOT delivered or cancelled yet)
$active_query = "SELECT COUNT(*) as active_count 
                 FROM orders WHERE order_type = 'online' AND status NOT IN ('delivered', 'cancelled')";
$active_result = $conn->query($active_query);
$active_data = $active_result->fetch_assoc();
$active_deliveries = $active_data['active_count'];

// Low Stock Items (from inventory)
$lowstock_query = "SELECT COUNT(*) as lowstock_count 
                   FROM inventory WHERE status IN ('low_stock', 'out_of_stock')";
$lowstock_result = $conn->query($lowstock_query);
$lowstock_data = $lowstock_result->fetch_assoc();
$lowstock_items = $lowstock_data['lowstock_count'];

// Stock Levels (get all products)
$stock_query = "SELECT product, stock_list, price, minimum_stock, status 
                FROM inventory ORDER BY status = 'low_stock' DESC, stock_list ASC LIMIT 6";
$stock_result = $conn->query($stock_query);

// Recent Orders
$recent_query = "SELECT id, order_date, payment_method, customer_name, product, quantity, total_price, status, order_type 
                 FROM orders ORDER BY order_date DESC LIMIT 10";
$recent_result = $conn->query($recent_query);

// Pending Riders Verification
$pending_riders_query = "SELECT COUNT(*) as pending_count FROM riders WHERE status = 'pending'";
$pending_riders_result = $conn->query($pending_riders_query);
$pending_riders = $pending_riders_result->fetch_assoc();
$pending_riders_count = $pending_riders['pending_count'];

// Monthly Returns Loss
$monthly_loss_query = "SELECT COALESCE(SUM(loss), 0) as total_loss 
                       FROM returns WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
$monthly_loss_result = $conn->query($monthly_loss_query);
$monthly_loss_data = $monthly_loss_result->fetch_assoc();
$monthly_loss = $monthly_loss_data['total_loss'];
?>

<body>
    <?php if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true): ?>
        <div id="loader-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fdf6ed; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: opacity 0.5s ease;">
            <img src="../assets/hero.png" alt="Loading..." style="max-width: 200px; animation: pulseLogo 1.5s infinite ease-in-out;">
            <h3 style="color: saddlebrown; font-family: cursive; margin-top: 20px; font-weight: 500;">Preparing your dashboard...</h3>
            <style>
                @keyframes pulseLogo {
                    0% { transform: scale(0.95); opacity: 0.7; }
                    50% { transform: scale(1.05); opacity: 1; }
                    100% { transform: scale(0.95); opacity: 0.7; }
                }
                body { overflow: hidden; } /* Prevent scrolling while loading */
            </style>
        </div>
        <?php
        // Destroy the flag so the loader doesn't show on page refreshes
        unset($_SESSION['just_logged_in']);
        ?>
    <?php endif; ?>
    <main class="main" id="main">
        <h1 style="font-size: 28px; margin-bottom: 5px;">Dashboard</h1>
        <hr class="divider">

        <!-- STATS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <!-- Today's Total Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #4CAF50;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">TODAY'S TOTAL SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($today_sales, 2); ?></p>
                <span style="color:#777"><?php echo $today_orders; ?> Orders</span>
            </div>

            <!-- Walk-in Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #2196F3;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">WALK-IN SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($walkin_sales, 2); ?></p>
                <span style="color:#777"><?php echo $walkin_orders; ?> Orders</span>
            </div>

            <!-- Online Sales -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #FF9800;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">ONLINE SALES</p>
                <p style="font-size: 22px; font-weight: bold;">₱<?php echo number_format($online_sales, 2); ?></p>
                <span style="color:#777"><?php echo $online_orders; ?> Orders</span>
            </div>

            <!-- Active Deliveries -->
            <div style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-top: 4px solid #F44336;">
                <p style="font-size: 12px; color: #777; margin-bottom: 5px;">ACTIVE DELIVERIES</p>
                <p style="font-size: 22px; font-weight: bold;"><?php echo $active_deliveries; ?></p>
                <span style="color:#777">Pending Orders</span>
            </div>
        </div>
        <!-- ❌ I REMOVED THE EXTRA BROKEN </div> TAG THAT WAS HERE! -->

        <!-- STOCK LEVELS SECTION -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Stock Levels</span>
                <button class="btn-sm" onclick="window.location.href='inventory.php'">Manage</button>
            </div>
            <hr class="section-divider">
            <div class="stock-grid">
                <?php
                if ($stock_result->num_rows > 0) {
                    while ($row = $stock_result->fetch_assoc()) {
                        $stock_status = '';
                        $status_class = '';
                        if ($row['status'] == 'low_stock') {
                            $stock_status = '⚠️ Low Stock';
                            $status_class = 'stock-warning';
                        } elseif ($row['status'] == 'out_of_stock') {
                            $stock_status = '❌ Out of Stock';
                            $status_class = 'stock-low';
                        } else {
                            $stock_status = '✓ OK';
                            $status_class = 'stock-ok';
                        }
                ?>
                        <div class="stock-item">
                            <span class="stock-name"><?php echo htmlspecialchars($row['product']); ?></span>
                            <span class="stock-num"><?php echo $row['stock_list']; ?></span>
                            <span class="stock-sub">₱<?php echo number_format($row['price'], 2); ?> each</span>
                            <span class="<?php echo $status_class; ?>"><?php echo $stock_status; ?></span>
                        </div>
                <?php
                    }
                } else {
                    echo '<p>No inventory items found.</p>';
                }
                ?>
            </div>
        </div>

        <!-- RECENT ORDERS SECTION -->
        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Recent Orders</span>
                <button class="btn-sm" onclick="window.location.href='walk.php'">View All</button>
            </div>
            <hr class="section-divider">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($recent_result->num_rows > 0) {
                        while ($order = $recent_result->fetch_assoc()) {
                            // Determine order type
                            $db_type = isset($order['order_type']) ? $order['order_type'] : 'online';

                            // Format for display and CSS classes
                            if ($db_type === 'walk-in') {
                                $order_type = 'Walk in';
                                $type_class = 'type-walkin';
                            } else {
                                $order_type = 'Online';
                                $type_class = 'type-online';
                            }

                            // Determine status class
                            $status_class = '';
                            if ($order['status'] == 'pending' || $order['status'] == 'pending_verification' || $order['status'] == 'ready_for_rider') {
                                $status_class = 'status-pending';
                            } elseif ($order['status'] == 'processing') {
                                $status_class = 'status-processing';
                            } else {
                                $status_class = 'status-delivered';
                            }

                            // Format payment method
                            $payment_display = '';
                            if ($order['payment_method'] == 'cod') {
                                $payment_display = 'Cash';
                            } elseif ($order['payment_method'] == 'gcash') {
                                $payment_display = 'GCash';
                            } elseif ($order['payment_method'] == 'bank_transfer') {
                                $payment_display = 'Bank Transfer';
                            } else {
                                $payment_display = ucfirst($order['payment_method']);
                            }

                            // Format time
                            $order_time = date('h:i A', strtotime($order['order_date']));
                    ?>
                            <tr>
                                <td><?php echo $order_time; ?></td>
                                <td><span class="type-badge <?php echo $type_class; ?>"><?php echo $order_type; ?></span></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><span class="pay-badge"><?php echo $payment_display; ?></span></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="8" style="text-align:center; padding:20px;">No orders found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Wait for the page to fully load, wait 1.5 seconds for effect, then fade out
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loader = document.getElementById('loader-overlay');
                if (loader) {
                    loader.style.opacity = '0'; // Start fade out
                    document.body.style.overflow = 'auto'; // Restore scrolling

                    // Remove from DOM after fade completes
                    setTimeout(() => loader.style.display = 'none', 500);
                }
            }, 1500);
        });
    </script>

</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>