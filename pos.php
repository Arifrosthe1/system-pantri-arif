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
    
    // Insert transaction record
    $stmt = $conn->prepare("INSERT INTO trans (trans_date, trans_time, total_amount, receipt_image) VALUES (?, ?, ?, ?)");
    $trans_date = date('Y-m-d');
    $trans_time = date('H:i:s');
    $stmt->bind_param("ssds", $trans_date, $trans_time, $total_amount, $receipt_image);
    $stmt->execute();
    $trans_id = $conn->insert_id;
    $stmt->close();
    
    // Insert transaction items and reduce stock
    foreach ($items as $item) {
        if ($item['quantity'] > 0) {
            // Insert trans item
            $stmt = $conn->prepare("INSERT INTO trans_item (trans_id, menu_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $trans_id, $item['menu_id'], $item['quantity'], $item['unit_price']);
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

// Get all menu items with stock ordered by menu_id
$result = $conn->query("SELECT * FROM menu WHERE stock_quantity > 0 ORDER BY menu_id");
$menu_items = [];
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>POS - System Pantri ARIF</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .main-container {
            min-height: 100vh;
            padding: 80px 16px 20px 16px;
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            z-index: 1000;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .back-btn {
            background: #f1f5f9;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            background: #e2e8f0;
            color: #334155;
            transform: scale(1.05);
        }
        
        .header-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: #1e293b;
            letter-spacing: -0.5px;
        }
        
        .header-spacer {
            width: 44px;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Menu Items */
        .menu-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 4px;
        }
        
        .menu-item {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .item-price {
            font-size: 1rem;
            color: #059669;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .item-stock {
            font-size: 0.85rem;
            color: #64748b;
            padding: 4px 12px;
            background: #f1f5f9;
            border-radius: 12px;
            display: inline-block;
        }
        
        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .qty-btn {
            background: #e2e8f0;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .qty-btn:hover:not(:disabled) {
            background: #cbd5e1;
            transform: scale(1.1);
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .qty-display {
            font-weight: 700;
            font-size: 1.2rem;
            min-width: 40px;
            text-align: center;
            color: #1e293b;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        /* Total Section */
        .total-section {
            flex-shrink: 0;
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-top: 20px;
        }
        
        .total-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #059669;
            text-align: center;
            margin-bottom: 20px;
            padding: 16px;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-radius: 16px;
        }
        
        /* Camera Section */
        .camera-section {
            margin-bottom: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .camera-btn {
            width: 100%;
            height: 60px;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .camera-btn:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
            transform: scale(1.02);
        }
        
        .camera-btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .photo-preview {
            max-width: 100%;
            max-height: 120px;
            border-radius: 12px;
            margin-top: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Process Button */
        .btn-primary {
            background: linear-gradient(135deg, #059669, #047857);
            border: none;
            border-radius: 16px;
            font-weight: 600;
            padding: 18px 24px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-height: 60px;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, #047857, #065f46);
        }
        
        .btn-primary:disabled {
            background: #d1d5db;
            border-color: #d1d5db;
            transform: none;
            box-shadow: none;
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 16px;
            border: none;
            padding: 16px 20px;
            font-weight: 600;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
        }
        
        /* Custom Scrollbar */
        .menu-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .menu-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .menu-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        
        .menu-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .main-container {
                padding: 80px 12px 20px 12px;
            }
            
            .menu-item {
                padding: 16px;
            }
            
            .qty-btn {
                width: 40px;
                height: 40px;
            }
            
            .total-amount {
                font-size: 1.8rem;
            }
            
            .total-section {
                padding: 20px;
            }
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Animation for quantity changes */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .qty-pulse {
            animation: pulse 0.3s ease;
        }
        
        /* Auto-hide success messages */
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="header-title">POINT OF SALE</div>
        <div class="header-spacer"></div>
    </div>
    
    <div class="container main-container">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'sale_processed'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                Sale processed successfully!
            </div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <div class="menu-list">
                <?php if (empty($menu_items)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No Items Available</h3>
                        <p>No menu items with stock are currently available for sale.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['menu_name']); ?></div>
                                <div class="item-price">
                                    <i class="fas fa-tag me-1"></i>
                                    RM<?php echo number_format($item['sale_price'], 2); ?>
                                </div>
                                <div class="item-stock">
                                    <i class="fas fa-boxes me-1"></i>
                                    Stock: <?php echo $item['stock_quantity']; ?>
                                </div>
                            </div>
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="changeQuantity(<?php echo $item['menu_id']; ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <div class="qty-display" id="qty-<?php echo $item['menu_id']; ?>">0</div>
                                <button class="qty-btn" onclick="changeQuantity(<?php echo $item['menu_id']; ?>, 1)" 
                                        data-max="<?php echo $item['stock_quantity']; ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="total-section">
                <div class="total-amount">
                    <i class="fas fa-calculator me-2"></i>
                    Total: RM<span id="totalAmount">0.00</span>
                </div>
                
                <div class="camera-section">
                    <label for="receiptPhoto" class="camera-btn">
                        <i class="fas fa-camera"></i>
                        Take Receipt Photo (Optional)
                    </label>
                    <input type="file" id="receiptPhoto" accept="image/*" capture="environment" style="display: none;">
                    <img id="photoPreview" class="photo-preview" style="display: none;">
                </div>
                
                <button class="btn btn-primary w-100" id="processBtn" onclick="processSale()" disabled>
                    <i class="fas fa-credit-card me-2"></i>
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
            const qtyDisplay = document.getElementById(`qty-${menuId}`);
            qtyDisplay.textContent = newQty;
            
            // Add pulse animation
            qtyDisplay.classList.add('qty-pulse');
            setTimeout(() => {
                qtyDisplay.classList.remove('qty-pulse');
            }, 300);
            
            updateTotal();
            updateProcessButton();
            
            // Add haptic feedback for supported devices
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
        }
        
        function updateTotal() {
            let total = 0;
            for (const menuId in cart) {
                total += cart[menuId].quantity * cart[menuId].price;
            }
            
            const totalElement = document.getElementById('totalAmount');
            totalElement.textContent = total.toFixed(2);
            
            // Add visual feedback for total change
            if (total > 0) {
                const totalSection = document.querySelector('.total-amount');
                totalSection.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    totalSection.style.transform = 'scale(1)';
                }, 200);
            }
        }
        
        function updateProcessButton() {
            const hasItems = Object.values(cart).some(item => item.quantity > 0);
            const processBtn = document.getElementById('processBtn');
            processBtn.disabled = !hasItems;
            
            if (hasItems) {
                processBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Process Sale';
            } else {
                processBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Select Items First';
            }
        }
        
        function processSale() {
            const totalAmount = parseFloat(document.getElementById('totalAmount').textContent);
            
            if (totalAmount === 0) {
                alert('Please select items before processing sale');
                return;
            }
            
            // Add loading state
            const processBtn = document.getElementById('processBtn');
            processBtn.classList.add('loading');
            processBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            // Prepare items for submission
            const items = [];
            for (const menuId in cart) {
                if (cart[menuId].quantity > 0) {
                    items.push({
                        menu_id: parseInt(menuId),
                        quantity: cart[menuId].quantity,
                        unit_price: cart[menuId].price
                    });
                }
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            
            // Add action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'process_sale';
            form.appendChild(actionInput);
            
            // Add total amount
            const totalInput = document.createElement('input');
            totalInput.type = 'hidden';
            totalInput.name = 'total_amount';
            totalInput.value = totalAmount;
            form.appendChild(totalInput);
            
            // Add items
            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items';
            itemsInput.value = JSON.stringify(items);
            form.appendChild(itemsInput);
            
            // Add receipt image if available
            const fileInput = document.getElementById('receiptPhoto');
            if (fileInput.files.length > 0) {
                const fileInputClone = fileInput.cloneNode(true);
                fileInputClone.name = 'receipt_image';
                form.appendChild(fileInputClone);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Handle photo upload
        document.getElementById('receiptPhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    hasPhoto = true;
                    
                    // Update camera button text
                    document.querySelector('.camera-btn').innerHTML = '<i class="fas fa-check me-2"></i>Photo Captured';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Initialize the interface
        document.addEventListener('DOMContentLoaded', function() {
            updateTotal();
            updateProcessButton();
            
            // Add haptic feedback for supported devices
            if ('vibrate' in navigator) {
                document.querySelectorAll('.qty-btn, .btn-primary').forEach(btn => {
                    btn.addEventListener('click', () => {
                        navigator.vibrate(50);
                    });
                });
            }
        });
        
        // Prevent zoom on input focus for iOS
        document.addEventListener('touchstart', function() {}, true);
        
        // Auto-hide success messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'fadeOut 0.5s ease forwards';
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>

<?php
$conn->close();
?>