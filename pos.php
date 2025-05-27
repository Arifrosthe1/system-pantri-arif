<?php
include 'dbcon.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_sale') {
    $total_amount = $_POST['total_amount'];
    $items = json_decode($_POST['items'], true);
    
    // Handle receipt image upload
    $receipt_image = null;
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $receipt_image = 'receipt_' . time() . '.' . $file_extension;
        move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_dir . $receipt_image);
    }
    
    // Insert purchase record
    $stmt = $conn->prepare("INSERT INTO purchase (purchase_date, purchase_time, total_amount, receipt_image) VALUES (?, ?, ?, ?)");
    $purchase_date = date('Y-m-d');
    $purchase_time = date('H:i:s');
    $stmt->bind_param("ssds", $purchase_date, $purchase_time, $total_amount, $receipt_image);
    $stmt->execute();
    $purchase_id = $conn->insert_id;
    $stmt->close();
    
    // Insert purchase items and reduce stock
    foreach ($items as $item) {
        if ($item['quantity'] > 0) {
            // Insert purchase item
            $stmt = $conn->prepare("INSERT INTO purchase_item (purchase_id, menu_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $purchase_id, $item['menu_id'], $item['quantity'], $item['unit_price']);
            $stmt->execute();
            $stmt->close();
            
            // Reduce stock
            $stmt = $conn->prepare("UPDATE menu SET stock_quantity = stock_quantity - ? WHERE menu_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['menu_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: pos.php?success=sale_processed");
    exit();
}

// Get all menu items with stock
$result = $conn->query("SELECT * FROM menu WHERE stock_quantity > 0 ORDER BY menu_name");
$menu_items = [];
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - System Pantri ARIF</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            min-height: 100vh;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        
        .header h1 {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #666;
            text-decoration: none;
            z-index: 1000;
        }
        
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .menu-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 4px;
        }
        
        .menu-item {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .item-price {
            font-size: 0.85rem;
            color: #059669;
            font-weight: 500;
        }
        
        .item-stock {
            font-size: 0.8rem;
            color: #666;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .qty-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            background: #ffffff;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
        }
        
        .qty-btn:hover {
            background: #f8f9fa;
            border-color: #d0d7de;
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .qty-display {
            font-weight: 600;
            font-size: 1rem;
            min-width: 30px;
            text-align: center;
        }
        
        .total-section {
            flex-shrink: 0;
            background: #ffffff;
            border-top: 1px solid #e5e5e5;
            padding: 20px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #059669;
            text-align: center;
            margin-bottom: 16px;
        }
        
        .btn-primary {
            background: #059669;
            border-color: #059669;
            border-radius: 8px;
            font-weight: 500;
            padding: 14px 20px;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: #047857;
            border-color: #047857;
        }
        
        .btn-primary:disabled {
            background: #d1d5db;
            border-color: #d1d5db;
        }
        
        .camera-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e5e5;
        }
        
        .camera-btn {
            width: 100%;
            height: 50px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-bottom: 12px;
        }
        
        .camera-btn:hover {
            border-color: #9ca3af;
            background: #f3f4f6;
        }
        
        .photo-preview {
            max-width: 100%;
            max-height: 100px;
            border-radius: 8px;
            margin-top: 8px;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        
        /* Custom scrollbar for webkit browsers */
        .menu-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .menu-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        
        .menu-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
        
        .menu-list::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 16px;
            }
            
            .menu-item {
                padding: 12px;
            }
            
            .total-section {
                padding: 16px 0;
            }
        }
        
        @media (max-height: 600px) {
            .header {
                margin-bottom: 16px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="header">
            <h1>POINT OF SALE</h1>
        </div>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'sale_processed'): ?>
            <div class="alert alert-success">
                Sale processed successfully!
            </div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <div class="menu-list">
                <?php if (empty($menu_items)): ?>
                    <div class="text-center">
                        <p class="text-muted">No items with stock available</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['menu_name']); ?></div>
                                <div class="item-price">RM<?php echo number_format($item['sale_price'], 2); ?></div>
                                <div class="item-stock">Stock: <?php echo $item['stock_quantity']; ?></div>
                            </div>
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="changeQuantity(<?php echo $item['menu_id']; ?>, -1)">-</button>
                                <div class="qty-display" id="qty-<?php echo $item['menu_id']; ?>">0</div>
                                <button class="qty-btn" onclick="changeQuantity(<?php echo $item['menu_id']; ?>, 1)" 
                                        data-max="<?php echo $item['stock_quantity']; ?>">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="total-section">
                <div class="total-amount">
                    Total: RM<span id="totalAmount">0.00</span>
                </div>
                
                <div class="camera-section">
                    <label for="receiptPhoto" class="camera-btn">
                        <i class="fas fa-camera"></i>&nbsp;&nbsp;Take Receipt Photo
                    </label>
                    <input type="file" id="receiptPhoto" accept="image/*" capture="environment" style="display: none;">
                    <img id="photoPreview" class="photo-preview" style="display: none;">
                </div>
                
                <button class="btn btn-primary w-100" id="processBtn" onclick="processSale()" disabled>
                    Process Sale
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuItems = <?php echo json_encode($menu_items); ?>;
        const cart = {};
        let hasPhoto = false;
        
        // Initialize cart
        menuItems.forEach(item => {
            cart[item.menu_id] = {
                quantity: 0,
                price: parseFloat(item.sale_price),
                max_stock: parseInt(item.stock_quantity),
                name: item.menu_name
            };
        });
        
        function changeQuantity(menuId, change) {
            const currentQty = cart[menuId].quantity;
            const newQty = currentQty + change;
            
            if (newQty < 0 || newQty > cart[menuId].max_stock) {
                return;
            }
            
            cart[menuId].quantity = newQty;
            document.getElementById(`qty-${menuId}`).textContent = newQty;
            
            updateTotal();
            updateProcessButton();
        }
        
        function updateTotal() {
            let total = 0;
            Object.values(cart).forEach(item => {
                total += item.quantity * item.price;
            });
            
            document.getElementById('totalAmount').textContent = total.toFixed(2);
        }
        
        function updateProcessButton() {
            const hasItems = Object.values(cart).some(item => item.quantity > 0);
            const btn = document.getElementById('processBtn');
            btn.disabled = !hasItems || !hasPhoto;
        }
        
        // Handle photo capture
        document.getElementById('receiptPhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    hasPhoto = true;
                    updateProcessButton();
                };
                reader.readAsDataURL(file);
            }
        });
        
        function processSale() {
            const total = parseFloat(document.getElementById('totalAmount').textContent);
            
            if (total <= 0) {
                alert('Please select items to purchase');
                return;
            }
            
            if (!hasPhoto) {
                alert('Please take a photo of the receipt');
                return;
            }
            
            // Prepare items data
            const items = [];
            Object.keys(cart).forEach(menuId => {
                if (cart[menuId].quantity > 0) {
                    items.push({
                        menu_id: parseInt(menuId),
                        quantity: cart[menuId].quantity,
                        unit_price: cart[menuId].price
                    });
                }
            });
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            
            // Add hidden fields
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'process_sale';
            form.appendChild(actionInput);
            
            const totalInput = document.createElement('input');
            totalInput.type = 'hidden';
            totalInput.name = 'total_amount';
            totalInput.value = total.toFixed(2);
            form.appendChild(totalInput);
            
            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items';
            itemsInput.value = JSON.stringify(items);
            form.appendChild(itemsInput);
            
            // Add file input
            const fileInput = document.getElementById('receiptPhoto');
            if (fileInput.files[0]) {
                const newFileInput = document.createElement('input');
                newFileInput.type = 'file';
                newFileInput.name = 'receipt_image';
                newFileInput.files = fileInput.files;
                form.appendChild(newFileInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Add touch feedback for mobile
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            btn.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>