<<<<<<< HEAD
<?php 

include 'header.php';
include 'sidebar.php';
include 'db.php';

// Fetch from the newly renamed table
$logs_query = "SELECT user_name, user_role, action, details, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 50";
$logs_result = $conn->query($logs_query);
?>

<style>
    .log-action { font-weight: bold; color: saddlebrown; }
    .log-details { font-size: 13px; color: #555; }
    .log-time { font-size: 12px; color: #888; }
    .log-user { font-size: 13px; font-weight: bold; color: #3b2208; }
    
    /* Color-coded role badges */
    .role-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; color: white; margin-bottom: 4px; }
    .role-admin { background: #2980b9; }    /* Blue */
    .role-rider { background: #27ae60; }    /* Green */
    .role-customer { background: #d35400; } /* Orange */
    .role-system { background: #7f8c8d; }   /* Gray */
</style>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">System Activity Logs</h1>
        <hr class="divider">

        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Recent Activity Across All Users</span>
            </div>
            <hr class="section-divider">
            
            <table class="orders-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 20%;">User & Role</th>
                        <th style="width: 20%;">Action</th>
                        <th style="width: 45%;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($logs_result && $logs_result->num_rows > 0) {
                        while ($log = $logs_result->fetch_assoc()) {
                            $formatted_date = date('M d, Y - h:i A', strtotime($log['created_at']));
                            
                            // Determine badge color based on role
                            $role_class = 'role-system';
                            $role = strtolower($log['user_role']);
                            if ($role === 'admin') $role_class = 'role-admin';
                            elseif ($role === 'rider') $role_class = 'role-rider';
                            elseif ($role === 'customer') $role_class = 'role-customer';
                    ?>
                            <tr>
                                <td class="log-time"><?php echo $formatted_date; ?></td>
                                <td>
                                    <span class="role-badge <?php echo $role_class; ?>"><?php echo htmlspecialchars($log['user_role']); ?></span><br>
                                    <span class="log-user">👤 <?php echo htmlspecialchars($log['user_name']); ?></span>
                                </td>
                                <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #8b6340;">No recent activity found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
=======
<?php 

include 'header.php';
include 'sidebar.php';
include 'db.php';

// Fetch from the newly renamed table
$logs_query = "SELECT user_name, user_role, action, details, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 50";
$logs_result = $conn->query($logs_query);
?>

<style>
    .log-action { font-weight: bold; color: saddlebrown; }
    .log-details { font-size: 13px; color: #555; }
    .log-time { font-size: 12px; color: #888; }
    .log-user { font-size: 13px; font-weight: bold; color: #3b2208; }
    
    /* Color-coded role badges */
    .role-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; color: white; margin-bottom: 4px; }
    .role-admin { background: #2980b9; }    /* Blue */
    .role-rider { background: #27ae60; }    /* Green */
    .role-customer { background: #d35400; } /* Orange */
    .role-system { background: #7f8c8d; }   /* Gray */
</style>

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">System Activity Logs</h1>
        <hr class="divider">

        <div class="section-wrapper">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Recent Activity Across All Users</span>
            </div>
            <hr class="section-divider">
            
            <table class="orders-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 20%;">User & Role</th>
                        <th style="width: 20%;">Action</th>
                        <th style="width: 45%;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($logs_result && $logs_result->num_rows > 0) {
                        while ($log = $logs_result->fetch_assoc()) {
                            $formatted_date = date('M d, Y - h:i A', strtotime($log['created_at']));
                            
                            // Determine badge color based on role
                            $role_class = 'role-system';
                            $role = strtolower($log['user_role']);
                            if ($role === 'admin') $role_class = 'role-admin';
                            elseif ($role === 'rider') $role_class = 'role-rider';
                            elseif ($role === 'customer') $role_class = 'role-customer';
                    ?>
                            <tr>
                                <td class="log-time"><?php echo $formatted_date; ?></td>
                                <td>
                                    <span class="role-badge <?php echo $role_class; ?>"><?php echo htmlspecialchars($log['user_role']); ?></span><br>
                                    <span class="log-user">👤 <?php echo htmlspecialchars($log['user_name']); ?></span>
                                </td>
                                <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #8b6340;">No recent activity found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>