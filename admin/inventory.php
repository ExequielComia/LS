<<<<<<< HEAD
<?php
include 'db.php';

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // --- UPDATE STOCK ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $quantity = (int)$_POST['quantity'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);

        $stock_result = $conn->query("SELECT stock_list, price, minimum_stock FROM inventory WHERE product = '$product'");

        if ($stock_result && $row = $stock_result->fetch_assoc()) {
            $current_stock = $row['stock_list'];
            $is_deduction = in_array($reason, ['Sold', 'Damaged']);
            $change = $is_deduction ? -$quantity : $quantity;
            $new_stock = $current_stock + $change;

            if ($new_stock < 0) {
                $response['message'] = "Cannot deduct more than current stock!";
                echo json_encode($response);
                exit;
            }

            if ($conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$product'")) {
                $new_status = 'in_stock';
                if ($new_stock <= 0) $new_status = 'out_of_stock';
                elseif ($new_stock <= $row['minimum_stock']) $new_status = 'low_stock';

                $conn->query("UPDATE inventory SET status = '$new_status' WHERE product = '$product'");

                // ✅ LOG THE STOCK UPDATE
                $action_word = $is_deduction ? 'Deducted' : 'Added';
                logAction($conn, 'Updated Stock', "$action_word $quantity units of $product. Reason: $reason");

                $response['success'] = true;
                $response['new_stock'] = $new_stock;
                $response['new_status'] = $new_status;
                $response['message'] = "Stock updated successfully!";
            } else {
                $response['message'] = "Database error: " . $conn->error;
            }
        } else {
            $response['message'] = "Product not found!";
        }

        echo json_encode($response);
        exit;
    }

    // --- UPDATE IMAGE ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_image') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $filepath = $dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                if ($conn->query("UPDATE inventory SET image = '$filepath' WHERE product = '$product'")) {
                    
                    // ✅ LOG THE IMAGE UPDATE
                    logAction($conn, 'Updated Product Image', "Uploaded a new image for $product");

                    $response['success'] = true;
                    $response['image_url'] = $filepath;
                    $response['message'] = "Image updated successfully!";
                } else {
                    $response['message'] = "Database error: " . $conn->error;
                }
            } else {
                $response['message'] = "Failed to move uploaded file.";
            }
        } else {
            $response['message'] = "No valid image file received.";
        }
        echo json_encode($response);
        exit;
    }

    // --- ADD PRODUCT ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $price = (float)$_POST['price'];
        $initial_stock = (int)$_POST['initial_stock'];
        $minimum_stock = (int)$_POST['minimum_stock'];

        $check_result = $conn->query("SELECT id FROM inventory WHERE product = '$product'");
        if ($check_result->num_rows > 0) {
            $response['message'] = "Product already exists!";
            echo json_encode($response);
            exit;
        }

        $status = 'in_stock';
        if ($initial_stock <= 0) $status = 'out_of_stock';
        elseif ($initial_stock <= $minimum_stock) $status = 'low_stock';

        // Handle Image Upload during Add
        $image_path = 'NULL'; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $filepath = $dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_path = "'$filepath'";
            }
        }

        if ($conn->query("INSERT INTO inventory (product, stock_list, price, initial_stock, minimum_stock, status, image) 
                          VALUES ('$product', $initial_stock, $price, $initial_stock, $minimum_stock, '$status', $image_path)")) {
            $response['success'] = true;
            $response['product_id'] = $conn->insert_id;
            
            // ✅ LOG THE NEW PRODUCT (Fixed variable names)
            logAction($conn, 'Added New Product', "Added $product to inventory with $initial_stock initial stock.");
            
            $response['message'] = "Product added successfully!";
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }

        echo json_encode($response);
        exit;
    }

    // --- DELETE PRODUCT ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);

        if ($conn->query("DELETE FROM inventory WHERE product = '$product'")) {
            
            // ✅ LOG THE DELETION
            logAction($conn, 'Deleted Product', "Removed $product from the inventory completely.");

            $response['success'] = true;
            $response['message'] = "Product deleted successfully!";
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }

        echo json_encode($response);
        exit;
    }

    $response['message'] = "Unknown action";
    echo json_encode($response);
    exit;
}

// ✅ STANDARD HTML RENDER
include 'header.php';
include 'sidebar.php';

// Fetch all inventory items
$inventory_query = "SELECT * FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Inventory</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="productCropModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive; text-align: center;">Adjust Product Image</h3>
                
                <div style="height: 300px; width: 100%; margin-bottom: 15px; background: #eee; display: flex; align-items: center; justify-content: center;">
                    <img id="productImageToCrop" src="" style="max-width: 100%; max-height: 100%; display: block;">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button class="save-changes-btn" id="productCropBtn" style="flex: 1; padding: 10px; border-radius: 8px;">Crop & Save</button>
                    <button class="edit-btn" onclick="closeProductCropper()" style="flex: 1; padding: 10px; border-radius: 8px;">Cancel</button>
                </div>
            </div>
        </div>

        <div class="stock-grid" id="product-grid" style="margin-bottom: 1.5rem;">
            <?php
            if ($inventory_result->num_rows > 0) {
                while ($row = $inventory_result->fetch_assoc()) {
                    $product_id = strtolower(str_replace(' ', '-', $row['product']));
                    $status_class = '';
                    $status_text = '';

                    if ($row['status'] == 'low_stock') {
                        $status_class = 'low';
                        $status_text = '⚠ Low stock';
                    } elseif ($row['status'] == 'out_of_stock') {
                        $status_class = 'critical';
                        $status_text = '✕ Out of stock!';
                    } else {
                        $status_class = 'ok';
                        $status_text = '✓ Stock level OK';
                    }
                    
                    // Fallback image if they haven't uploaded one yet
                    $image_src = !empty($row['image']) ? $row['image'] : '../assets/hero.png'; 
            ?>
                    <div class="inv-card" data-product="<?php echo htmlspecialchars($row['product']); ?>" id="card-<?php echo $product_id; ?>">
                        
                        <div style="text-align: center; margin-bottom: 10px; position: relative;">
                            <img src="<?php echo htmlspecialchars($image_src); ?>" id="img-preview-<?php echo $product_id; ?>" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px; border: 2px solid #e8dcc8; background: #fdf8ef;">
                            
                            <input type="file" id="upload-<?php echo $product_id; ?>" accept="image/*" style="display:none;" onchange="openProductCropper('<?php echo addslashes($row['product']); ?>', this, '<?php echo $product_id; ?>')">
                            
                            <button class="btn-sm" style="position: absolute; bottom: 5px; right: 5px; background: rgba(139, 69, 19, 0.85); color: white; border: none; border-radius: 20px; padding: 4px 10px; font-size: 11px; cursor: pointer;" onclick="document.getElementById('upload-<?php echo $product_id; ?>').click()">
                                📸 Edit
                            </button>
                        </div>

                        <div class="inv-card-header">
                            <span class="inv-product-name"><?php echo htmlspecialchars($row['product']); ?></span>
                        </div>
                        <div class="inv-qty" id="qty-<?php echo $product_id; ?>" style="color: <?php echo ($row['stock_list'] <= 0) ? '#c0392b' : (($row['stock_list'] <= $row['minimum_stock']) ? '#e0a422' : '#3a7d44'); ?>;">
                            <?php echo $row['stock_list']; ?>
                        </div>
                        <div class="inv-unit">units in stock</div>
                        <div class="inv-meta">
                            <span>Min: <?php echo $row['minimum_stock']; ?></span>
                            <span>Price: ₱<?php echo number_format($row['price'], 2); ?></span>
                        </div>
                        <div class="inv-status <?php echo $status_class; ?>" id="status-<?php echo $product_id; ?>">
                            <?php echo $status_text; ?>
                        </div>
                        <div class="inv-actions">
                            <input type="number" class="inv-input" id="input-<?php echo $product_id; ?>" placeholder="Enter quantity" min="1">
                            <div class="inv-select-wrap">
                                <select class="inv-select" id="reason-<?php echo $product_id; ?>">
                                    <option value="Restock">📦 Restock</option>
                                    <option value="Sold">💰 Sold</option>
                                    <option value="Damaged">⚠️ Damaged</option>
                                    <option value="Adjustment">📝 Adjustment</option>
                                </select>
                            </div>
                        </div>
                        <button class="inv-add-btn" onclick="updateStock('<?php echo addslashes($row['product']); ?>', '<?php echo $product_id; ?>')">
                            + Update Stock
                        </button>
                        <button class="inv-delete-btn" onclick="deleteProduct('<?php echo addslashes($row['product']); ?>', this)">
                            🗑️ Delete Product
                        </button>
                    </div>
            <?php
                }
            } else {
                echo '<p style="text-align:center; color:#8b6340; width: 100%;">No products found. Add your first product below.</p>';
            }
            ?>
        </div>

        <div class="section-wrapper" style="margin-top: 1.5rem;">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Add New Product</span>
            </div>
            <hr class="section-divider">
            <div class="new-product-grid">
                <div class="new-product-field">
                    <label class="field-label">Product Name</label>
                    <input type="text" id="new-name" placeholder="e.g. Ensaymada" class="inv-input" style="width:100%;">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Price (₱)</label>
                    <input type="number" id="new-price" placeholder="e.g. 35" class="inv-input" style="width:100%;" min="0" step="0.01">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Initial Stock</label>
                    <input type="number" id="new-stock" placeholder="e.g. 50" class="inv-input" style="width:100%;" min="0">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Minimum Stock</label>
                    <input type="number" id="new-min" placeholder="e.g. 20" class="inv-input" style="width:100%;" min="0">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Product Image (Optional)</label>
                    <input type="file" id="new-image" accept="image/*" class="inv-input" style="width:100%; padding: 5px;">
                </div>
            </div>
            <button class="inv-add-btn" style="margin-top: 1rem; background: saddlebrown;" onclick="addNewProduct()">
                + Add Product
            </button>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <script>
        const currentUrl = window.location.pathname;

        // --- IMAGE CROPPING LOGIC ---
        let productCropper;
        let targetProductName = '';
        let targetProductId = '';

        function openProductCropper(productName, inputElement, productId) {
            if (inputElement.files && inputElement.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    targetProductName = productName;
                    targetProductId = productId;
                    
                    const imgToCrop = document.getElementById('productImageToCrop');
                    document.getElementById('productCropModal').style.display = 'flex';
                    
                    if (productCropper) { 
                        productCropper.destroy(); 
                        productCropper = null;
                    }
                    
                    imgToCrop.onload = function() {
                        // Using the setTimeout fix so the cropper renders flawlessly!
                        setTimeout(function() {
                            productCropper = new Cropper(imgToCrop, {
                                aspectRatio: 1, // Creates a nice square crop
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.8,
                                background: false
                            });
                        }, 100);
                    };

                    imgToCrop.src = e.target.result;
                };
                
                reader.readAsDataURL(inputElement.files[0]);
                inputElement.value = ''; // Reset input to allow re-selecting the same file
            }
        }

        function closeProductCropper() {
            document.getElementById('productCropModal').style.display = 'none';
            if (productCropper) { 
                productCropper.destroy(); 
                productCropper = null;
            }
            targetProductName = '';
            targetProductId = '';
        }

        document.getElementById('productCropBtn').addEventListener('click', function() {
            if (!productCropper || !targetProductName) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            productCropper.getCroppedCanvas({
                width: 400, // Slightly higher res for product images
                height: 400
            }).toBlob(function(blob) {
                
                const formData = new FormData();
                formData.append('image', blob, 'product.jpg');
                formData.append('action', 'update_image');
                formData.append('product', targetProductName);

                fetch(currentUrl, { 
                    method: 'POST', 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData 
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const preview = document.getElementById('img-preview-' + targetProductId);
                        const url = URL.createObjectURL(blob);
                        preview.src = url; // Instantly update the image on the card!
                        closeProductCropper();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Upload failed. Please try again.");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Crop & Save';
                });
                
            }, 'image/jpeg', 0.85);
        });


        // --- Update Stock Function ---
        function updateStock(productName, productId) {
            const inputEl = document.getElementById('input-' + productId);
            const reasonEl = document.getElementById('reason-' + productId);
            const quantity = parseInt(inputEl.value);

            if (!quantity || quantity < 1) {
                alert('Please enter a valid quantity.'); return;
            }
            const reason = reasonEl.value;

            document.getElementById('loading-overlay').style.display = 'flex';

            fetch(currentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=update_stock&product=' + encodeURIComponent(productName) + '&quantity=' + quantity + '&reason=' + encodeURIComponent(reason)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    const qtyElement = document.getElementById('qty-' + productId);
                    qtyElement.textContent = data.new_stock;

                    const statusElement = document.getElementById('status-' + productId);
                    if (data.new_status === 'low_stock') {
                        statusElement.className = 'inv-status low';
                        statusElement.textContent = '⚠ Low stock';
                        qtyElement.style.color = '#e0a422';
                    } else if (data.new_status === 'out_of_stock') {
                        statusElement.className = 'inv-status critical';
                        statusElement.textContent = '✕ Out of stock!';
                        qtyElement.style.color = '#c0392b';
                    } else {
                        statusElement.className = 'inv-status ok';
                        statusElement.textContent = '✓ Stock level OK';
                        qtyElement.style.color = '#3a7d44';
                    }
                    inputEl.value = '';
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }

        // --- Add New Product ---
        function addNewProduct() {
            const name = document.getElementById('new-name').value.trim();
            const price = parseFloat(document.getElementById('new-price').value);
            const stock = parseInt(document.getElementById('new-stock').value);
            const min = parseInt(document.getElementById('new-min').value);
            const imageFile = document.getElementById('new-image').files[0];

            if (!name) { alert('Please enter a product name.'); return; }
            if (isNaN(price) || price < 0) { alert('Please enter a valid price.'); return; }
            if (isNaN(stock) || stock < 0) { alert('Please enter an initial stock quantity.'); return; }
            if (isNaN(min) || min < 0) { alert('Please enter a minimum stock level.'); return; }

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new FormData();
            formData.append('action', 'add_product');
            formData.append('product', name);
            formData.append('price', price);
            formData.append('initial_stock', stock);
            formData.append('minimum_stock', min);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            fetch(currentUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    alert('Product added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }

        // --- Delete Product ---
        function deleteProduct(productName, buttonElement) {
            if (!confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                return;
            }

            document.getElementById('loading-overlay').style.display = 'flex';

            fetch(currentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=delete_product&product=' + encodeURIComponent(productName)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    const card = buttonElement.closest('.inv-card');
                    if (card) card.remove();
                    alert('Product deleted successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }
    </script>
</body>
=======
<?php
include 'db.php';

// ✅ POST/AJAX check runs BEFORE header.php is ever included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // --- UPDATE STOCK ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $quantity = (int)$_POST['quantity'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);

        $stock_result = $conn->query("SELECT stock_list, price, minimum_stock FROM inventory WHERE product = '$product'");

        if ($stock_result && $row = $stock_result->fetch_assoc()) {
            $current_stock = $row['stock_list'];
            $is_deduction = in_array($reason, ['Sold', 'Damaged']);
            $change = $is_deduction ? -$quantity : $quantity;
            $new_stock = $current_stock + $change;

            if ($new_stock < 0) {
                $response['message'] = "Cannot deduct more than current stock!";
                echo json_encode($response);
                exit;
            }

            if ($conn->query("UPDATE inventory SET stock_list = $new_stock WHERE product = '$product'")) {
                $new_status = 'in_stock';
                if ($new_stock <= 0) $new_status = 'out_of_stock';
                elseif ($new_stock <= $row['minimum_stock']) $new_status = 'low_stock';

                $conn->query("UPDATE inventory SET status = '$new_status' WHERE product = '$product'");

                // ✅ LOG THE STOCK UPDATE
                $action_word = $is_deduction ? 'Deducted' : 'Added';
                logAction($conn, 'Updated Stock', "$action_word $quantity units of $product. Reason: $reason");

                $response['success'] = true;
                $response['new_stock'] = $new_stock;
                $response['new_status'] = $new_status;
                $response['message'] = "Stock updated successfully!";
            } else {
                $response['message'] = "Database error: " . $conn->error;
            }
        } else {
            $response['message'] = "Product not found!";
        }

        echo json_encode($response);
        exit;
    }

    // --- UPDATE IMAGE ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_image') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $filepath = $dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                if ($conn->query("UPDATE inventory SET image = '$filepath' WHERE product = '$product'")) {
                    
                    // ✅ LOG THE IMAGE UPDATE
                    logAction($conn, 'Updated Product Image', "Uploaded a new image for $product");

                    $response['success'] = true;
                    $response['image_url'] = $filepath;
                    $response['message'] = "Image updated successfully!";
                } else {
                    $response['message'] = "Database error: " . $conn->error;
                }
            } else {
                $response['message'] = "Failed to move uploaded file.";
            }
        } else {
            $response['message'] = "No valid image file received.";
        }
        echo json_encode($response);
        exit;
    }

    // --- ADD PRODUCT ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);
        $price = (float)$_POST['price'];
        $initial_stock = (int)$_POST['initial_stock'];
        $minimum_stock = (int)$_POST['minimum_stock'];

        $check_result = $conn->query("SELECT id FROM inventory WHERE product = '$product'");
        if ($check_result->num_rows > 0) {
            $response['message'] = "Product already exists!";
            echo json_encode($response);
            exit;
        }

        $status = 'in_stock';
        if ($initial_stock <= 0) $status = 'out_of_stock';
        elseif ($initial_stock <= $minimum_stock) $status = 'low_stock';

        // Handle Image Upload during Add
        $image_path = 'NULL'; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = 'uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $filepath = $dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_path = "'$filepath'";
            }
        }

        if ($conn->query("INSERT INTO inventory (product, stock_list, price, initial_stock, minimum_stock, status, image) 
                          VALUES ('$product', $initial_stock, $price, $initial_stock, $minimum_stock, '$status', $image_path)")) {
            $response['success'] = true;
            $response['product_id'] = $conn->insert_id;
            
            // ✅ LOG THE NEW PRODUCT (Fixed variable names)
            logAction($conn, 'Added New Product', "Added $product to inventory with $initial_stock initial stock.");
            
            $response['message'] = "Product added successfully!";
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }

        echo json_encode($response);
        exit;
    }

    // --- DELETE PRODUCT ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        $product = mysqli_real_escape_string($conn, $_POST['product']);

        if ($conn->query("DELETE FROM inventory WHERE product = '$product'")) {
            
            // ✅ LOG THE DELETION
            logAction($conn, 'Deleted Product', "Removed $product from the inventory completely.");

            $response['success'] = true;
            $response['message'] = "Product deleted successfully!";
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }

        echo json_encode($response);
        exit;
    }

    $response['message'] = "Unknown action";
    echo json_encode($response);
    exit;
}

// ✅ STANDARD HTML RENDER
include 'header.php';
include 'sidebar.php';

// Fetch all inventory items
$inventory_query = "SELECT * FROM inventory ORDER BY product";
$inventory_result = $conn->query($inventory_query);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

<body>
    <main class="main" id="main">
        <h1 class="dashboard-title">Inventory</h1>
        <hr class="divider">

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 40px; color: saddlebrown;"></i>
                <p style="margin-top: 10px;">Processing...</p>
            </div>
        </div>

        <div id="productCropModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                <h3 style="color: saddlebrown; margin-bottom: 15px; font-family: cursive; text-align: center;">Adjust Product Image</h3>
                
                <div style="height: 300px; width: 100%; margin-bottom: 15px; background: #eee; display: flex; align-items: center; justify-content: center;">
                    <img id="productImageToCrop" src="" style="max-width: 100%; max-height: 100%; display: block;">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button class="save-changes-btn" id="productCropBtn" style="flex: 1; padding: 10px; border-radius: 8px;">Crop & Save</button>
                    <button class="edit-btn" onclick="closeProductCropper()" style="flex: 1; padding: 10px; border-radius: 8px;">Cancel</button>
                </div>
            </div>
        </div>

        <div class="stock-grid" id="product-grid" style="margin-bottom: 1.5rem;">
            <?php
            if ($inventory_result->num_rows > 0) {
                while ($row = $inventory_result->fetch_assoc()) {
                    $product_id = strtolower(str_replace(' ', '-', $row['product']));
                    $status_class = '';
                    $status_text = '';

                    if ($row['status'] == 'low_stock') {
                        $status_class = 'low';
                        $status_text = '⚠ Low stock';
                    } elseif ($row['status'] == 'out_of_stock') {
                        $status_class = 'critical';
                        $status_text = '✕ Out of stock!';
                    } else {
                        $status_class = 'ok';
                        $status_text = '✓ Stock level OK';
                    }
                    
                    // Fallback image if they haven't uploaded one yet
                    $image_src = !empty($row['image']) ? $row['image'] : '../assets/hero.png'; 
            ?>
                    <div class="inv-card" data-product="<?php echo htmlspecialchars($row['product']); ?>" id="card-<?php echo $product_id; ?>">
                        
                        <div style="text-align: center; margin-bottom: 10px; position: relative;">
                            <img src="<?php echo htmlspecialchars($image_src); ?>" id="img-preview-<?php echo $product_id; ?>" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px; border: 2px solid #e8dcc8; background: #fdf8ef;">
                            
                            <input type="file" id="upload-<?php echo $product_id; ?>" accept="image/*" style="display:none;" onchange="openProductCropper('<?php echo addslashes($row['product']); ?>', this, '<?php echo $product_id; ?>')">
                            
                            <button class="btn-sm" style="position: absolute; bottom: 5px; right: 5px; background: rgba(139, 69, 19, 0.85); color: white; border: none; border-radius: 20px; padding: 4px 10px; font-size: 11px; cursor: pointer;" onclick="document.getElementById('upload-<?php echo $product_id; ?>').click()">
                                📸 Edit
                            </button>
                        </div>

                        <div class="inv-card-header">
                            <span class="inv-product-name"><?php echo htmlspecialchars($row['product']); ?></span>
                        </div>
                        <div class="inv-qty" id="qty-<?php echo $product_id; ?>" style="color: <?php echo ($row['stock_list'] <= 0) ? '#c0392b' : (($row['stock_list'] <= $row['minimum_stock']) ? '#e0a422' : '#3a7d44'); ?>;">
                            <?php echo $row['stock_list']; ?>
                        </div>
                        <div class="inv-unit">units in stock</div>
                        <div class="inv-meta">
                            <span>Min: <?php echo $row['minimum_stock']; ?></span>
                            <span>Price: ₱<?php echo number_format($row['price'], 2); ?></span>
                        </div>
                        <div class="inv-status <?php echo $status_class; ?>" id="status-<?php echo $product_id; ?>">
                            <?php echo $status_text; ?>
                        </div>
                        <div class="inv-actions">
                            <input type="number" class="inv-input" id="input-<?php echo $product_id; ?>" placeholder="Enter quantity" min="1">
                            <div class="inv-select-wrap">
                                <select class="inv-select" id="reason-<?php echo $product_id; ?>">
                                    <option value="Restock">📦 Restock</option>
                                    <option value="Sold">💰 Sold</option>
                                    <option value="Damaged">⚠️ Damaged</option>
                                    <option value="Adjustment">📝 Adjustment</option>
                                </select>
                            </div>
                        </div>
                        <button class="inv-add-btn" onclick="updateStock('<?php echo addslashes($row['product']); ?>', '<?php echo $product_id; ?>')">
                            + Update Stock
                        </button>
                        <button class="inv-delete-btn" onclick="deleteProduct('<?php echo addslashes($row['product']); ?>', this)">
                            🗑️ Delete Product
                        </button>
                    </div>
            <?php
                }
            } else {
                echo '<p style="text-align:center; color:#8b6340; width: 100%;">No products found. Add your first product below.</p>';
            }
            ?>
        </div>

        <div class="section-wrapper" style="margin-top: 1.5rem;">
            <div class="section-header">
                <span class="section-title"><span class="section-dot"></span> Add New Product</span>
            </div>
            <hr class="section-divider">
            <div class="new-product-grid">
                <div class="new-product-field">
                    <label class="field-label">Product Name</label>
                    <input type="text" id="new-name" placeholder="e.g. Ensaymada" class="inv-input" style="width:100%;">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Price (₱)</label>
                    <input type="number" id="new-price" placeholder="e.g. 35" class="inv-input" style="width:100%;" min="0" step="0.01">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Initial Stock</label>
                    <input type="number" id="new-stock" placeholder="e.g. 50" class="inv-input" style="width:100%;" min="0">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Minimum Stock</label>
                    <input type="number" id="new-min" placeholder="e.g. 20" class="inv-input" style="width:100%;" min="0">
                </div>
                <div class="new-product-field">
                    <label class="field-label">Product Image (Optional)</label>
                    <input type="file" id="new-image" accept="image/*" class="inv-input" style="width:100%; padding: 5px;">
                </div>
            </div>
            <button class="inv-add-btn" style="margin-top: 1rem; background: saddlebrown;" onclick="addNewProduct()">
                + Add Product
            </button>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <script>
        const currentUrl = window.location.pathname;

        // --- IMAGE CROPPING LOGIC ---
        let productCropper;
        let targetProductName = '';
        let targetProductId = '';

        function openProductCropper(productName, inputElement, productId) {
            if (inputElement.files && inputElement.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    targetProductName = productName;
                    targetProductId = productId;
                    
                    const imgToCrop = document.getElementById('productImageToCrop');
                    document.getElementById('productCropModal').style.display = 'flex';
                    
                    if (productCropper) { 
                        productCropper.destroy(); 
                        productCropper = null;
                    }
                    
                    imgToCrop.onload = function() {
                        // Using the setTimeout fix so the cropper renders flawlessly!
                        setTimeout(function() {
                            productCropper = new Cropper(imgToCrop, {
                                aspectRatio: 1, // Creates a nice square crop
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.8,
                                background: false
                            });
                        }, 100);
                    };

                    imgToCrop.src = e.target.result;
                };
                
                reader.readAsDataURL(inputElement.files[0]);
                inputElement.value = ''; // Reset input to allow re-selecting the same file
            }
        }

        function closeProductCropper() {
            document.getElementById('productCropModal').style.display = 'none';
            if (productCropper) { 
                productCropper.destroy(); 
                productCropper = null;
            }
            targetProductName = '';
            targetProductId = '';
        }

        document.getElementById('productCropBtn').addEventListener('click', function() {
            if (!productCropper || !targetProductName) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            productCropper.getCroppedCanvas({
                width: 400, // Slightly higher res for product images
                height: 400
            }).toBlob(function(blob) {
                
                const formData = new FormData();
                formData.append('image', blob, 'product.jpg');
                formData.append('action', 'update_image');
                formData.append('product', targetProductName);

                fetch(currentUrl, { 
                    method: 'POST', 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData 
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const preview = document.getElementById('img-preview-' + targetProductId);
                        const url = URL.createObjectURL(blob);
                        preview.src = url; // Instantly update the image on the card!
                        closeProductCropper();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Upload failed. Please try again.");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Crop & Save';
                });
                
            }, 'image/jpeg', 0.85);
        });


        // --- Update Stock Function ---
        function updateStock(productName, productId) {
            const inputEl = document.getElementById('input-' + productId);
            const reasonEl = document.getElementById('reason-' + productId);
            const quantity = parseInt(inputEl.value);

            if (!quantity || quantity < 1) {
                alert('Please enter a valid quantity.'); return;
            }
            const reason = reasonEl.value;

            document.getElementById('loading-overlay').style.display = 'flex';

            fetch(currentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=update_stock&product=' + encodeURIComponent(productName) + '&quantity=' + quantity + '&reason=' + encodeURIComponent(reason)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    const qtyElement = document.getElementById('qty-' + productId);
                    qtyElement.textContent = data.new_stock;

                    const statusElement = document.getElementById('status-' + productId);
                    if (data.new_status === 'low_stock') {
                        statusElement.className = 'inv-status low';
                        statusElement.textContent = '⚠ Low stock';
                        qtyElement.style.color = '#e0a422';
                    } else if (data.new_status === 'out_of_stock') {
                        statusElement.className = 'inv-status critical';
                        statusElement.textContent = '✕ Out of stock!';
                        qtyElement.style.color = '#c0392b';
                    } else {
                        statusElement.className = 'inv-status ok';
                        statusElement.textContent = '✓ Stock level OK';
                        qtyElement.style.color = '#3a7d44';
                    }
                    inputEl.value = '';
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }

        // --- Add New Product ---
        function addNewProduct() {
            const name = document.getElementById('new-name').value.trim();
            const price = parseFloat(document.getElementById('new-price').value);
            const stock = parseInt(document.getElementById('new-stock').value);
            const min = parseInt(document.getElementById('new-min').value);
            const imageFile = document.getElementById('new-image').files[0];

            if (!name) { alert('Please enter a product name.'); return; }
            if (isNaN(price) || price < 0) { alert('Please enter a valid price.'); return; }
            if (isNaN(stock) || stock < 0) { alert('Please enter an initial stock quantity.'); return; }
            if (isNaN(min) || min < 0) { alert('Please enter a minimum stock level.'); return; }

            document.getElementById('loading-overlay').style.display = 'flex';

            const formData = new FormData();
            formData.append('action', 'add_product');
            formData.append('product', name);
            formData.append('price', price);
            formData.append('initial_stock', stock);
            formData.append('minimum_stock', min);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            fetch(currentUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    alert('Product added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }

        // --- Delete Product ---
        function deleteProduct(productName, buttonElement) {
            if (!confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                return;
            }

            document.getElementById('loading-overlay').style.display = 'flex';

            fetch(currentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=delete_product&product=' + encodeURIComponent(productName)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = 'none';
                if (data.success) {
                    const card = buttonElement.closest('.inv-card');
                    if (card) card.remove();
                    alert('Product deleted successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert('Network error.');
            });
        }
    </script>
</body>
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>