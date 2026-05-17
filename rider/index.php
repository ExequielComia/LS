<<<<<<< HEAD
<?php
session_start();
include 'db.php';

// ✅ AJAX ENDPOINTS BUILT DIRECTLY INTO THIS FILE

// 1. Fetch Available Orders
if (isset($_GET['action']) && $_GET['action'] === 'fetch_available') {
    header('Content-Type: application/json');
    
    // Fetch orders that the Admin marked as 'ready_for_rider'
    $q = $conn->query("SELECT id, customer_name, delivery_address, total_price FROM orders WHERE status = 'ready_for_rider' AND order_type = 'online' ORDER BY order_date ASC");
    $orders = [];
    if ($q) {
        while($r = $q->fetch_assoc()) {
            $orders[] = $r;
        }
    }
    echo json_encode($orders);
    exit;
}

// 2. Accept Order Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept_order') {
    header('Content-Type: application/json');
    $oid = (int)$_POST['order_id'];
    $rid = (int)$_POST['rider_id'];
    
    // Assign to rider and change status to 'processing' (Out for Delivery)
    $update = $conn->query("UPDATE orders SET rider_id = $rid, status = 'processing' WHERE id = $oid AND status = 'ready_for_rider'");
    if ($update && $conn->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order was already taken by another rider or cancelled.']);
    }
    exit;
}

// ✅ HTML Starts Here
include 'header.php';
include 'sidebar.php';

// IMPORTANT: Assume the rider is logged in and their ID is stored in the session.
$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 

// Fetch Rider Stats
$today_query = mysqli_query($conn, "SELECT COUNT(*) as today_count FROM orders WHERE rider_id = $rider_id AND DATE(order_date) = CURDATE() AND status = 'delivered'");
$today_data = $today_query ? mysqli_fetch_assoc($today_query) : null;
$today_deliveries = $today_data['today_count'] ?? 0;

$rider_query = mysqli_query($conn, "SELECT earnings, total_deliveries FROM riders WHERE id = $rider_id");
$rider_data = $rider_query ? mysqli_fetch_assoc($rider_query) : null;
$total_earnings = $rider_data['earnings'] ?? 0.00;
$total_completed = $rider_data['total_deliveries'] ?? 0;
// 2. Fetch completed orders TODAY specifically for THIS RIDER
$completed_query = "SELECT COUNT(id) as total_completed, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE status = 'delivered' 
                    AND rider_id = $rider_id 
                    AND DATE(order_date) = CURDATE()";
$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_today = $completed_data['total_completed'] ?? 0;
$daily_earnings = $completed_data['total_earnings'] ?? 0;

// Fetch My Delivery History
$history_query = mysqli_query($conn, "SELECT * FROM orders WHERE rider_id = $rider_id AND status IN ('delivered', 'cancelled') ORDER BY order_date DESC LIMIT 10");
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Dashboard</h1>
        <hr class="divider">

        <!-- Reverted to your native beige/brown layout! -->
        <div class="walkin-layout">
            
            <div class="walkin-form">
                
                <!-- Available Deliveries Table -->
                <div class="section-wrapper walkin-form">
                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> Available Deliveries (Packed & Ready)</span>
                        <button class="btn-sm" style="background: saddlebrown; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" onclick="fetchAvailableOrders()">
                            🔄 Refresh
                        </button>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="availableOrdersBody">
                                <tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">Loading available deliveries...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Delivery History Table -->
                <div class="section-wrapper walkin-form" style="margin-top: 20px;">
                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> My Delivery History</span>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Date</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history_query && mysqli_num_rows($history_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($history_query)): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['customer_name']); ?></strong></td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['delivery_address']); ?>">
                                                <?= htmlspecialchars($row['delivery_address']); ?>
                                            </td>
                                            <td><?= date('Y-m-d h:i A', strtotime($row['order_date'])); ?></td>
                                            <td>₱<?= number_format($row['total_price'], 2); ?></td>
                                            <td>
                                                <span style="background: <?= $row['status'] == 'delivered' ? '#2e7d32' : '#c0392b' ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                                    <?= ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">No history found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Sidebar Stats (Native Design) -->
            <div class="walkin-sidebar">
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Rider Stats</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <tr>
                            <td>Today's Deliveries</td>
                            <td class="side-val"><?= $today_deliveries; ?></td>
                        </tr>
                        <tr>
                            <td>Total Earnings</td>
                            <td class="side-val price-val">₱<?= number_format($daily_earnings, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Completed Orders</td>
                            <td class="side-val"><?= $completed_today  ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="./js/script.js" onerror="console.log('script.js not found, skipping.');"></script>
    
    <script>
        const riderId = <?= $rider_id; ?>;

        function fetchAvailableOrders() {
            fetch('?action=fetch_available')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('availableOrdersBody');
                    tbody.innerHTML = ''; 

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">No available deliveries right now.</td></tr>';
                        return;
                    }

                    data.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${order.customer_name}</td>
                            <td>${order.delivery_address}</td>
                            <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                            <td>
                                <button style="background: #2e7d32; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;" onclick="acceptOrder(${order.id})">
                                    ✅ Accept
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(err => console.error("Error fetching orders: ", err));
        }

        function acceptOrder(orderId) {
            if(!confirm("Are you sure you want to accept the order?")) return;

            const formData = new URLSearchParams();
            formData.append('action', 'accept_order');
            formData.append('order_id', orderId);
            formData.append('rider_id', riderId);

            fetch('', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Order accepted successfully!'.");
                    fetchAvailableOrders(); 
                    location.reload(); 
                } else {
                    alert("❌ " + data.message); 
                    fetchAvailableOrders(); 
                }
            })
            .catch(err => alert('Network error processing your acceptance.'));
        }

        // Poll the database every 3 seconds for new orders
        setInterval(fetchAvailableOrders, 3000);
        
        // Initial load
        fetchAvailableOrders();
    </script>
</body>
=======
<?php
session_start();
include 'db.php';

// ✅ AJAX ENDPOINTS BUILT DIRECTLY INTO THIS FILE

// 1. Fetch Available Orders
if (isset($_GET['action']) && $_GET['action'] === 'fetch_available') {
    header('Content-Type: application/json');
    
    // Fetch orders that the Admin marked as 'ready_for_rider'
    $q = $conn->query("SELECT id, customer_name, delivery_address, total_price FROM orders WHERE status = 'ready_for_rider' AND order_type = 'online' ORDER BY order_date ASC");
    $orders = [];
    if ($q) {
        while($r = $q->fetch_assoc()) {
            $orders[] = $r;
        }
    }
    echo json_encode($orders);
    exit;
}

// 2. Accept Order Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept_order') {
    header('Content-Type: application/json');
    $oid = (int)$_POST['order_id'];
    $rid = (int)$_POST['rider_id'];
    
    // Assign to rider and change status to 'processing' (Out for Delivery)
    $update = $conn->query("UPDATE orders SET rider_id = $rid, status = 'processing' WHERE id = $oid AND status = 'ready_for_rider'");
    if ($update && $conn->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order was already taken by another rider or cancelled.']);
    }
    exit;
}

// ✅ HTML Starts Here
include 'header.php';
include 'sidebar.php';

// IMPORTANT: Assume the rider is logged in and their ID is stored in the session.
$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 

// Fetch Rider Stats
$today_query = mysqli_query($conn, "SELECT COUNT(*) as today_count FROM orders WHERE rider_id = $rider_id AND DATE(order_date) = CURDATE() AND status = 'delivered'");
$today_data = $today_query ? mysqli_fetch_assoc($today_query) : null;
$today_deliveries = $today_data['today_count'] ?? 0;

$rider_query = mysqli_query($conn, "SELECT earnings, total_deliveries FROM riders WHERE id = $rider_id");
$rider_data = $rider_query ? mysqli_fetch_assoc($rider_query) : null;
$total_earnings = $rider_data['earnings'] ?? 0.00;
$total_completed = $rider_data['total_deliveries'] ?? 0;
// 2. Fetch completed orders TODAY specifically for THIS RIDER
$completed_query = "SELECT COUNT(id) as total_completed, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE status = 'delivered' 
                    AND rider_id = $rider_id 
                    AND DATE(order_date) = CURDATE()";
$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_today = $completed_data['total_completed'] ?? 0;
$daily_earnings = $completed_data['total_earnings'] ?? 0;

// Fetch My Delivery History
$history_query = mysqli_query($conn, "SELECT * FROM orders WHERE rider_id = $rider_id AND status IN ('delivered', 'cancelled') ORDER BY order_date DESC LIMIT 10");
?>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Dashboard</h1>
        <hr class="divider">

        <!-- Reverted to your native beige/brown layout! -->
        <div class="walkin-layout">
            
            <div class="walkin-form">
                
                <!-- Available Deliveries Table -->
                <div class="section-wrapper walkin-form">
                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> Available Deliveries (Packed & Ready)</span>
                        <button class="btn-sm" style="background: saddlebrown; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" onclick="fetchAvailableOrders()">
                            🔄 Refresh
                        </button>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="availableOrdersBody">
                                <tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">Loading available deliveries...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Delivery History Table -->
                <div class="section-wrapper walkin-form" style="margin-top: 20px;">
                    <div class="section-header" style="margin-bottom: 15px;">
                        <span class="section-title"><span class="section-dot"></span> My Delivery History</span>
                    </div>
                    <hr class="section-divider">

                    <div style="overflow-x: auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Date</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history_query && mysqli_num_rows($history_query) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($history_query)): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['customer_name']); ?></strong></td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['delivery_address']); ?>">
                                                <?= htmlspecialchars($row['delivery_address']); ?>
                                            </td>
                                            <td><?= date('Y-m-d h:i A', strtotime($row['order_date'])); ?></td>
                                            <td>₱<?= number_format($row['total_price'], 2); ?></td>
                                            <td>
                                                <span style="background: <?= $row['status'] == 'delivered' ? '#2e7d32' : '#c0392b' ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                                    <?= ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">No history found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Sidebar Stats (Native Design) -->
            <div class="walkin-sidebar">
                <div class="section-wrapper">
                    <div class="section-header">
                        <span class="section-title"><span class="section-dot"></span> Rider Stats</span>
                    </div>
                    <hr class="section-divider">
                    <table class="side-table">
                        <tr>
                            <td>Today's Deliveries</td>
                            <td class="side-val"><?= $today_deliveries; ?></td>
                        </tr>
                        <tr>
                            <td>Total Earnings</td>
                            <td class="side-val price-val">₱<?= number_format($daily_earnings, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Completed Orders</td>
                            <td class="side-val"><?= $completed_today  ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="./js/script.js" onerror="console.log('script.js not found, skipping.');"></script>
    
    <script>
        const riderId = <?= $rider_id; ?>;

        function fetchAvailableOrders() {
            fetch('?action=fetch_available')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('availableOrdersBody');
                    tbody.innerHTML = ''; 

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #8b6340; padding: 3rem;">No available deliveries right now.</td></tr>';
                        return;
                    }

                    data.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${order.customer_name}</td>
                            <td>${order.delivery_address}</td>
                            <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                            <td>
                                <button style="background: #2e7d32; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;" onclick="acceptOrder(${order.id})">
                                    ✅ Accept
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(err => console.error("Error fetching orders: ", err));
        }

        function acceptOrder(orderId) {
            if(!confirm("Are you sure you want to accept the order?")) return;

            const formData = new URLSearchParams();
            formData.append('action', 'accept_order');
            formData.append('order_id', orderId);
            formData.append('rider_id', riderId);

            fetch('', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Order accepted successfully!'.");
                    fetchAvailableOrders(); 
                    location.reload(); 
                } else {
                    alert("❌ " + data.message); 
                    fetchAvailableOrders(); 
                }
            })
            .catch(err => alert('Network error processing your acceptance.'));
        }

        // Poll the database every 3 seconds for new orders
        setInterval(fetchAvailableOrders, 3000);
        
        // Initial load
        fetchAvailableOrders();
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>