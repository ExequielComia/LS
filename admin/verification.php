<<<<<<< HEAD
<?php
// ✅ ADD THIS BLOCK AT THE VERY TOP - before include 'db.php'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    include 'db.php';
    header('Content-Type: application/json');

    $id = intval($_POST['id']);
    $status = $_POST['status'];

    $allowed = ['verified', 'rejected', 'pending', 'suspended'];
    if (!in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    // Get rider name for logging
    $rider_query = mysqli_query($conn, "SELECT fullname FROM riders WHERE id = $id");
    $rider = mysqli_fetch_assoc($rider_query);
    $rider_name = $rider ? $rider['fullname'] : "Rider #$id";

    if ($status === 'verified') {
        $query = "UPDATE riders SET status = ?, date_verified = CURDATE() WHERE id = ?";
    } else {
        $query = "UPDATE riders SET status = ?, date_verified = NULL WHERE id = ?";
    }

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $id);

    if (mysqli_stmt_execute($stmt)) {
        // ✅ LOG IT - db.php already has logAction()
        $action_label = ucfirst($status) . ' Rider';
        $details = "Rider '$rider_name' was $status.";
        logAction($conn, $action_label, $details);

        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    }
    exit;
}

include 'db.php'; // Ensure this file connects to your database and initializes $conn
include 'header.php';
include 'sidebar.php';

// Fetch all riders from the database
$riders_data = [];
$query = "SELECT id, fullname, contact, idtype, id_image, submitted, status FROM riders ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    // Replace your existing while loop with this:
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['id_image'])) {
            $filename = basename($row['id_image']);
            $row['id_image'] = '/pos/uploads/' . $filename;
        }
        $riders_data[] = $row;
    }
}
?>

<style>
    /* Make table container scrollable */
    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 500px;
        scrollbar-width: thin;
    }

    .table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f0ebe0;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: saddlebrown;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #6b3a1f;
    }

    /* Keep thead sticky when scrolling vertically */
    .order-table thead th {
        position: sticky;
        top: 0;
        background: #fdf8ef;
        z-index: 10;
    }
</style>

<body>
    <main class="main">
        <h1 class="dashboard-title">
            <i class="fa-solid fa-shield-haltered"></i> Rider Verification
        </h1>
        <hr class="divider">

        <div class="verification-stats">
            <div class="stat-card">
                <div class="stat-left">
                    <h4>PENDING VERIFICATION</h4>
                    <div class="stat-number" id="pendingCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h4>VERIFIED RIDERS</h4>
                    <div class="stat-number" id="verifiedCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-regular fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h4>TOTAL RIDERS</h4>
                    <div class="stat-number" id="totalCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-motorcycle"></i></div>
            </div>
        </div>

        <div class="verify-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle" style="font-size: 12px; color: saddlebrown;"></i> Rider ID Verification Requests</h2>
                <select id="statusFilter" class="filter-select">
                    <option value="all">All riders</option>
                    <option value="pending">Pending verification</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="table-container">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>FULL NAME</th>
                            <th>CONTACT</th>
                            <th>ID TYPE</th>
                            <th>ID IMAGE</th>
                            <th>SUBMITTED</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="riderTableBody"></tbody>
                </table>
            </div>
        </div>

        <div id="imageModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <i class="fa-solid fa-id-card" style="font-size: 2rem; color: saddlebrown; margin-bottom: 12px;"></i>
                <h3 style="color:saddlebrown; font-family:cursive;">ID Document Preview</h3>
                <div style="margin: 15px 0;">
                    <img id="previewImage" src="" alt="ID Image"
                        onerror="findImageFallback(this)"
                        data-rawpath=""
                        data-attempt="0"
                        style="max-width: 100%; border-radius: 8px; border: 2px solid #e8dcc8; display: block; margin: 0 auto;">
                </div>
                <button class="modal-cancel" onclick="closeImageModal()">Close</button>
            </div>
        </div>

        <div id="actionModal" class="modal">
            <div class="modal-content">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.5rem; color:#e0a422; margin-bottom: 12px;"></i>
                <h3 id="modalTitle" style="color:saddlebrown; font-family:cursive;">Confirm verification</h3>
                <p id="modalMessage" style="margin-top: 8px;">Are you sure you want to verify this rider?</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" id="modalConfirmBtn">Confirm</button>
                    <button class="modal-cancel" id="modalCancelBtn">Cancel</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Load data dynamically from PHP database query
        let riders = <?php echo json_encode($riders_data); ?>;

        function renderVerificationTable() {
            const filterValue = document.getElementById('statusFilter').value;
            let filtered = riders;
            if (filterValue !== 'all') filtered = riders.filter(r => r.status === filterValue);

            const tbody = document.getElementById('riderTableBody');
            tbody.innerHTML = '';

            if (filtered.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `<td colspan="7" style="text-align:center; padding:2rem; color:#8b6340;">No riders match the filter</td>`;
                tbody.appendChild(emptyRow);
            } else {
                filtered.forEach(rider => {
                    const row = document.createElement('tr');
                    let statusClass = '';
                    let statusText = '';

                    if (rider.status === 'pending') {
                        statusClass = 'status-pending';
                        statusText = 'Pending';
                    } else if (rider.status === 'verified') {
                        statusClass = 'status-verified';
                        statusText = 'Verified';
                    } else if (rider.status === 'rejected') {
                        statusClass = 'status-rejected';
                        statusText = 'Rejected';
                    }

                    // ✅ SMART FALLBACKS: Fixes the "null" name and the "undefined" ID crash!
                    const safeId = rider.id || rider.rider_id;
                    const safeName = rider.fullname || 'Unknown Rider';

                    row.innerHTML = `
                    <td>${safeName}</td>
                    <td>${rider.contact || 'N/A'}</td>
                    <td>${rider.idtype || 'N/A'}</td>
                    <td>
                        ${rider.id_image && rider.id_image !== '' ? 
                        `<button class="view-id-btn" data-id="${safeId}"><i class="fa-regular fa-eye"></i> View ID</button>` 
                        : '<span style="color:#888; font-size:12px;">No Image</span>'}
                    </td>
                    <td>${rider.submitted || 'N/A'}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td class="action-btns">
                        ${rider.status === 'pending' ? 
                            `<button class="btn-verify" data-id="${safeId}"><i class="fa-regular fa-thumbs-up"></i> Verify</button>
                             <button class="btn-reject" data-id="${safeId}"><i class="fa-regular fa-thumbs-down"></i> Reject</button>` : 
                            `<span class="action-status-text"><i class="fa-regular ${rider.status === 'verified' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${rider.status === 'verified' ? 'Verified' : 'Rejected'}</span>`}
                    </td>
                `;
                    tbody.appendChild(row);
                });

                // Attach view ID button events
                document.querySelectorAll('.view-id-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const riderId = btn.getAttribute('data-id');
                        window.open('view_id.php?id=' + riderId, '_blank');
                    });
                });

                // Attach verify/reject events
                document.querySelectorAll('.btn-verify').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const targetId = btn.getAttribute('data-id');
                        openModal(targetId, 'verify');
                    });
                });
                document.querySelectorAll('.btn-reject').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const targetId = btn.getAttribute('data-id');
                        openModal(targetId, 'reject');
                    });
                });
            }
            updateStats();
        }

        function updateStats() {
            const pending = riders.filter(r => r.status === 'pending').length;
            const verified = riders.filter(r => r.status === 'verified').length;
            const total = riders.length;
            document.getElementById('pendingCount').innerText = pending;
            document.getElementById('verifiedCount').innerText = verified;
            document.getElementById('totalCount').innerText = total;
        }

        // Image Modal functions
        function openImageModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const previewImg = document.getElementById('previewImage');

            previewImg.setAttribute('data-attempt', '0');
            previewImg.setAttribute('data-rawpath', imageUrl);
            previewImg.src = imageUrl;
            modal.style.display = 'flex';
        }

        function findImageFallback(img) {
            let attempt = parseInt(img.getAttribute('data-attempt') || '0');
            let rawPath = img.getAttribute('data-rawpath');
            if (!rawPath) return;

            let filename = rawPath.split('/').pop().split('\\').pop();
            attempt++;
            img.setAttribute('data-attempt', attempt.toString());

            if (attempt === 1) {
                img.src = '../uploads/' + filename;
            } else if (attempt === 2) {
                img.src = 'uploads/' + filename;
            } else if (attempt === 3) {
                img.src = '../' + rawPath;
            } else {
                img.onerror = null;
                img.alt = "⚠️ Image not found in system.";
            }
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            const previewImg = document.getElementById('previewImage');
            previewImg.src = "";
            previewImg.onerror = findImageFallback;
        }

        // Action Modal functions
        let pendingRiderId = null;
        let pendingAction = null;
        const modal = document.getElementById('actionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');

        function openModal(targetId, action) {
            pendingRiderId = targetId;
            pendingAction = action;

            // ✅ SMART LOOKUP: Checks for both id and rider_id
            const rider = riders.find(r => (r.id || r.rider_id) == targetId);

            if (!rider) {
                alert("Error: Could not find rider data.");
                return;
            }

            const safeName = rider.fullname || 'Unknown Rider';

            if (action === 'verify') {
                modalTitle.innerText = 'Verify Rider';
                modalMessage.innerText = `Confirm verification for ${safeName}? This will approve their account.`;
            } else {
                modalTitle.innerText = 'Reject Rider';
                modalMessage.innerText = `Confirm rejection for ${safeName}? This will deny their verification request.`;
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
            pendingRiderId = null;
            pendingAction = null;
        }

        function processVerification() {
            if (!pendingRiderId || !pendingAction) return;

            const newStatus = pendingAction === 'verify' ? 'verified' : 'rejected';
            document.getElementById('modalConfirmBtn').disabled = true;

            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('id', pendingRiderId);
            formData.append('status', newStatus);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const index = riders.findIndex(r => (r.id || r.rider_id) == pendingRiderId);
                        if (index !== -1) {
                            riders[index].status = newStatus;
                            renderVerificationTable();
                        }
                        closeModal();
                    } else {
                        alert("Failed to update status: " + (data.message || "Unknown error"));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred. Please try again.");
                })
                .finally(() => {
                    document.getElementById('modalConfirmBtn').disabled = false;
                });
        }

        // Event listeners
        document.getElementById('modalConfirmBtn').addEventListener('click', processVerification);
        document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
        window.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
            if (e.target === document.getElementById('imageModal')) closeImageModal();
        });
        document.getElementById('statusFilter').addEventListener('change', () => renderVerificationTable());

        renderVerificationTable();
    </script>
</body>

=======
<?php
// ✅ ADD THIS BLOCK AT THE VERY TOP - before include 'db.php'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    include 'db.php';
    header('Content-Type: application/json');

    $id = intval($_POST['id']);
    $status = $_POST['status'];

    $allowed = ['verified', 'rejected', 'pending', 'suspended'];
    if (!in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    // Get rider name for logging
    $rider_query = mysqli_query($conn, "SELECT fullname FROM riders WHERE id = $id");
    $rider = mysqli_fetch_assoc($rider_query);
    $rider_name = $rider ? $rider['fullname'] : "Rider #$id";

    if ($status === 'verified') {
        $query = "UPDATE riders SET status = ?, date_verified = CURDATE() WHERE id = ?";
    } else {
        $query = "UPDATE riders SET status = ?, date_verified = NULL WHERE id = ?";
    }

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $id);

    if (mysqli_stmt_execute($stmt)) {
        // ✅ LOG IT - db.php already has logAction()
        $action_label = ucfirst($status) . ' Rider';
        $details = "Rider '$rider_name' was $status.";
        logAction($conn, $action_label, $details);

        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    }
    exit;
}

include 'db.php'; // Ensure this file connects to your database and initializes $conn
include 'header.php';
include 'sidebar.php';

// Fetch all riders from the database
$riders_data = [];
$query = "SELECT id, fullname, contact, idtype, id_image, submitted, status FROM riders ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    // Replace your existing while loop with this:
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['id_image'])) {
            $filename = basename($row['id_image']);
            $row['id_image'] = '/pos/uploads/' . $filename;
        }
        $riders_data[] = $row;
    }
}
?>

<style>
    /* Make table container scrollable */
    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 500px;
        scrollbar-width: thin;
    }

    .table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f0ebe0;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: saddlebrown;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #6b3a1f;
    }

    /* Keep thead sticky when scrolling vertically */
    .order-table thead th {
        position: sticky;
        top: 0;
        background: #fdf8ef;
        z-index: 10;
    }
</style>

<body>
    <main class="main">
        <h1 class="dashboard-title">
            <i class="fa-solid fa-shield-haltered"></i> Rider Verification
        </h1>
        <hr class="divider">

        <div class="verification-stats">
            <div class="stat-card">
                <div class="stat-left">
                    <h4>PENDING VERIFICATION</h4>
                    <div class="stat-number" id="pendingCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h4>VERIFIED RIDERS</h4>
                    <div class="stat-number" id="verifiedCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-regular fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-left">
                    <h4>TOTAL RIDERS</h4>
                    <div class="stat-number" id="totalCount">0</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-motorcycle"></i></div>
            </div>
        </div>

        <div class="verify-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle" style="font-size: 12px; color: saddlebrown;"></i> Rider ID Verification Requests</h2>
                <select id="statusFilter" class="filter-select">
                    <option value="all">All riders</option>
                    <option value="pending">Pending verification</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="table-container">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>FULL NAME</th>
                            <th>CONTACT</th>
                            <th>ID TYPE</th>
                            <th>ID IMAGE</th>
                            <th>SUBMITTED</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="riderTableBody"></tbody>
                </table>
            </div>
        </div>

        <div id="imageModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <i class="fa-solid fa-id-card" style="font-size: 2rem; color: saddlebrown; margin-bottom: 12px;"></i>
                <h3 style="color:saddlebrown; font-family:cursive;">ID Document Preview</h3>
                <div style="margin: 15px 0;">
                    <img id="previewImage" src="" alt="ID Image"
                        onerror="findImageFallback(this)"
                        data-rawpath=""
                        data-attempt="0"
                        style="max-width: 100%; border-radius: 8px; border: 2px solid #e8dcc8; display: block; margin: 0 auto;">
                </div>
                <button class="modal-cancel" onclick="closeImageModal()">Close</button>
            </div>
        </div>

        <div id="actionModal" class="modal">
            <div class="modal-content">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.5rem; color:#e0a422; margin-bottom: 12px;"></i>
                <h3 id="modalTitle" style="color:saddlebrown; font-family:cursive;">Confirm verification</h3>
                <p id="modalMessage" style="margin-top: 8px;">Are you sure you want to verify this rider?</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" id="modalConfirmBtn">Confirm</button>
                    <button class="modal-cancel" id="modalCancelBtn">Cancel</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Load data dynamically from PHP database query
        let riders = <?php echo json_encode($riders_data); ?>;

        function renderVerificationTable() {
            const filterValue = document.getElementById('statusFilter').value;
            let filtered = riders;
            if (filterValue !== 'all') filtered = riders.filter(r => r.status === filterValue);

            const tbody = document.getElementById('riderTableBody');
            tbody.innerHTML = '';

            if (filtered.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `<td colspan="7" style="text-align:center; padding:2rem; color:#8b6340;">No riders match the filter</td>`;
                tbody.appendChild(emptyRow);
            } else {
                filtered.forEach(rider => {
                    const row = document.createElement('tr');
                    let statusClass = '';
                    let statusText = '';

                    if (rider.status === 'pending') {
                        statusClass = 'status-pending';
                        statusText = 'Pending';
                    } else if (rider.status === 'verified') {
                        statusClass = 'status-verified';
                        statusText = 'Verified';
                    } else if (rider.status === 'rejected') {
                        statusClass = 'status-rejected';
                        statusText = 'Rejected';
                    }

                    // ✅ SMART FALLBACKS: Fixes the "null" name and the "undefined" ID crash!
                    const safeId = rider.id || rider.rider_id;
                    const safeName = rider.fullname || 'Unknown Rider';

                    row.innerHTML = `
                    <td>${safeName}</td>
                    <td>${rider.contact || 'N/A'}</td>
                    <td>${rider.idtype || 'N/A'}</td>
                    <td>
                        ${rider.id_image && rider.id_image !== '' ? 
                        `<button class="view-id-btn" data-id="${safeId}"><i class="fa-regular fa-eye"></i> View ID</button>` 
                        : '<span style="color:#888; font-size:12px;">No Image</span>'}
                    </td>
                    <td>${rider.submitted || 'N/A'}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td class="action-btns">
                        ${rider.status === 'pending' ? 
                            `<button class="btn-verify" data-id="${safeId}"><i class="fa-regular fa-thumbs-up"></i> Verify</button>
                             <button class="btn-reject" data-id="${safeId}"><i class="fa-regular fa-thumbs-down"></i> Reject</button>` : 
                            `<span class="action-status-text"><i class="fa-regular ${rider.status === 'verified' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${rider.status === 'verified' ? 'Verified' : 'Rejected'}</span>`}
                    </td>
                `;
                    tbody.appendChild(row);
                });

                // Attach view ID button events
                document.querySelectorAll('.view-id-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const riderId = btn.getAttribute('data-id');
                        window.open('view_id.php?id=' + riderId, '_blank');
                    });
                });

                // Attach verify/reject events
                document.querySelectorAll('.btn-verify').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const targetId = btn.getAttribute('data-id');
                        openModal(targetId, 'verify');
                    });
                });
                document.querySelectorAll('.btn-reject').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const targetId = btn.getAttribute('data-id');
                        openModal(targetId, 'reject');
                    });
                });
            }
            updateStats();
        }

        function updateStats() {
            const pending = riders.filter(r => r.status === 'pending').length;
            const verified = riders.filter(r => r.status === 'verified').length;
            const total = riders.length;
            document.getElementById('pendingCount').innerText = pending;
            document.getElementById('verifiedCount').innerText = verified;
            document.getElementById('totalCount').innerText = total;
        }

        // Image Modal functions
        function openImageModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const previewImg = document.getElementById('previewImage');

            previewImg.setAttribute('data-attempt', '0');
            previewImg.setAttribute('data-rawpath', imageUrl);
            previewImg.src = imageUrl;
            modal.style.display = 'flex';
        }

        function findImageFallback(img) {
            let attempt = parseInt(img.getAttribute('data-attempt') || '0');
            let rawPath = img.getAttribute('data-rawpath');
            if (!rawPath) return;

            let filename = rawPath.split('/').pop().split('\\').pop();
            attempt++;
            img.setAttribute('data-attempt', attempt.toString());

            if (attempt === 1) {
                img.src = '../uploads/' + filename;
            } else if (attempt === 2) {
                img.src = 'uploads/' + filename;
            } else if (attempt === 3) {
                img.src = '../' + rawPath;
            } else {
                img.onerror = null;
                img.alt = "⚠️ Image not found in system.";
            }
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            const previewImg = document.getElementById('previewImage');
            previewImg.src = "";
            previewImg.onerror = findImageFallback;
        }

        // Action Modal functions
        let pendingRiderId = null;
        let pendingAction = null;
        const modal = document.getElementById('actionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');

        function openModal(targetId, action) {
            pendingRiderId = targetId;
            pendingAction = action;

            // ✅ SMART LOOKUP: Checks for both id and rider_id
            const rider = riders.find(r => (r.id || r.rider_id) == targetId);

            if (!rider) {
                alert("Error: Could not find rider data.");
                return;
            }

            const safeName = rider.fullname || 'Unknown Rider';

            if (action === 'verify') {
                modalTitle.innerText = 'Verify Rider';
                modalMessage.innerText = `Confirm verification for ${safeName}? This will approve their account.`;
            } else {
                modalTitle.innerText = 'Reject Rider';
                modalMessage.innerText = `Confirm rejection for ${safeName}? This will deny their verification request.`;
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
            pendingRiderId = null;
            pendingAction = null;
        }

        function processVerification() {
            if (!pendingRiderId || !pendingAction) return;

            const newStatus = pendingAction === 'verify' ? 'verified' : 'rejected';
            document.getElementById('modalConfirmBtn').disabled = true;

            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('id', pendingRiderId);
            formData.append('status', newStatus);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const index = riders.findIndex(r => (r.id || r.rider_id) == pendingRiderId);
                        if (index !== -1) {
                            riders[index].status = newStatus;
                            renderVerificationTable();
                        }
                        closeModal();
                    } else {
                        alert("Failed to update status: " + (data.message || "Unknown error"));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred. Please try again.");
                })
                .finally(() => {
                    document.getElementById('modalConfirmBtn').disabled = false;
                });
        }

        // Event listeners
        document.getElementById('modalConfirmBtn').addEventListener('click', processVerification);
        document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
        window.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
            if (e.target === document.getElementById('imageModal')) closeImageModal();
        });
        document.getElementById('statusFilter').addEventListener('change', () => renderVerificationTable());

        renderVerificationTable();
    </script>
</body>

>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>