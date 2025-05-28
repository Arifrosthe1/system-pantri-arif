<?php

include 'dbcon.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_stock':
                $menu_id = $_POST['menu_id'];
                $quantity = $_POST['quantity'];
                
                // Update stock quantity
                $stmt = $conn->prepare("UPDATE menu SET stock_quantity = stock_quantity + ? WHERE menu_id = ?");
                $stmt->bind_param("ii", $quantity, $menu_id);
                $stmt->execute();
                $stmt->close();
                
                header("Location: inventory.php?success=stock_added");
                exit();
                
            case 'add_purchase':
                $purchase_date = date('Y-m-d');
                $total_amount = $_POST['total_amount'];
                
                // Loop through items and insert into cost_purchase
                foreach ($_POST['items'] as $item) {
                    if ($item['quantity'] > 0) {
                        $menu_id = $item['menu_id'];
                        $quantity = $item['quantity'];
                        
                        // Get cost price from menu table
                        $cost_stmt = $conn->prepare("SELECT cost_price FROM menu WHERE menu_id = ?");
                        $cost_stmt->bind_param("i", $menu_id);
                        $cost_stmt->execute();
                        $cost_result = $cost_stmt->get_result();
                        $cost_row = $cost_result->fetch_assoc();
                        $unit_price = $cost_row['cost_price'];
                        $cost_stmt->close();
                        
                        $item_total_cost = $quantity * $unit_price;

                        // Insert into cost_purchase
                        $stmt = $conn->prepare("INSERT INTO cost_purchase (menu_id, quantity, purchase_date, total_cost) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iisd", $menu_id, $quantity, $purchase_date, $item_total_cost);
                        $stmt->execute();
                        $stmt->close();

                        // Update stock quantity
                        $stmt = $conn->prepare("UPDATE menu SET stock_quantity = stock_quantity + ? WHERE menu_id = ?");
                        $stmt->bind_param("ii", $quantity, $menu_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                header("Location: inventory.php?success=purchase_added");
                exit();
        }
    }
}

// Get all menu items ordered by menu_id instead of menu_name
$result = $conn->query("SELECT * FROM menu ORDER BY menu_id");
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
    <title>Inventory - System Pantri ARIF</title>
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
            padding-bottom: 90px; /* Space for bottom nav */
            overflow-x: hidden;
        }
        
        .main-container {
            min-height: calc(100vh - 90px);
            padding: 80px 16px 20px 16px;
            max-width: 500px;
            margin: 0 auto;
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
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 12px 0 20px 0;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        }
        
        .nav-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .nav-btn {
            flex: 1;
            background: #f1f5f9;
            border: none;
            padding: 16px 8px;
            border-radius: 16px;
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            min-height: 60px;
        }
        
        .nav-btn.active {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        
        .nav-btn i {
            font-size: 1.1rem;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .content-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Inventory Items */
        .inventory-item {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }
        
        .inventory-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .item-details {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 12px;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #fef3c7, #fcd34d);
            color: #92400e;
        }
        
        .stock-ok {
            background: linear-gradient(135deg, #dcfce7, #86efac);
            color: #166534;
        }
        
        .stock-out {
            background: linear-gradient(135deg, #fecaca, #f87171);
            color: #991b1b;
        }
        
        /* Forms */
        .form-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 16px;
            font-size: 1rem;
            transition: all 0.2s ease;
            min-height: 56px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #059669;
            box-shadow: 0 0 0 0.25rem rgba(5, 150, 105, 0.15);
            transform: scale(1.02);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #059669, #047857);
            border: none;
            border-radius: 16px;
            font-weight: 600;
            padding: 18px 24px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-height: 56px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, #047857, #065f46);
        }
        
        /* Purchase Items */
        .purchase-item {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .purchase-item:focus-within {
            border-color: #059669;
            transform: scale(1.02);
        }
        
        .purchase-item-name {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .purchase-item-price {
            font-size: 0.9rem;
            color: #059669;
            font-weight: 600;
            margin-bottom: 12px;
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
        
        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }
        
        .quantity-btn {
            background: #e2e8f0;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background: #cbd5e1;
            transform: scale(1.1);
        }
        
        .quantity-input {
            flex: 1;
            text-align: center;
            font-weight: 600;
            min-height: 40px;
        }
        
        /* Total Amount Display */
        .total-display {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin: 20px 0;
            font-size: 1.2rem;
            font-weight: 700;
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
            
            .nav-btn {
                font-size: 0.75rem;
                padding: 12px 6px;
                min-height: 55px;
            }
            
            .nav-btn i {
                font-size: 1rem;
            }
            
            .form-container {
                padding: 20px;
            }
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="header-title">INVENTORY</div>
        <div class="header-spacer"></div>
    </div>
    
    <div class="container main-container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php
                switch ($_GET['success']) {
                    case 'stock_added':
                        echo 'Stock added successfully!';
                        break;
                    case 'purchase_added':
                        echo 'Purchase recorded successfully!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <!-- View Stock Section -->
        <div class="content-section active" id="view-section">
            <?php foreach ($menu_items as $item): ?>
                <div class="inventory-item">
                    <div class="item-name"><?php echo htmlspecialchars($item['menu_name']); ?></div>
                    <div class="item-details">
                        Cost: RM<?php echo number_format($item['cost_price'], 2); ?> | 
                        Sale: RM<?php echo number_format($item['sale_price'], 2); ?>
                    </div>
                    <div>
                        <span class="stock-badge <?php 
                            if ($item['stock_quantity'] == 0) echo 'stock-out';
                            elseif ($item['stock_quantity'] < 5) echo 'stock-low';
                            else echo 'stock-ok';
                        ?>">
                            <i class="fas fa-boxes me-1"></i>
                            Stock: <?php echo $item['stock_quantity']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Add Stock Section -->
        <div class="content-section" id="add-section">
            <div class="form-container">
                <form method="POST" id="addStockForm">
                    <input type="hidden" name="action" value="add_stock">
                    
                    <div class="mb-4">
                        <label for="menu_id" class="form-label">
                            <i class="fas fa-utensils me-2"></i>Select Item
                        </label>
                        <select class="form-select" id="menu_id" name="menu_id" required>
                            <option value="">Choose item...</option>
                            <?php foreach ($menu_items as $item): ?>
                                <option value="<?php echo $item['menu_id']; ?>">
                                    <?php echo htmlspecialchars($item['menu_name']); ?> (Current: <?php echo $item['stock_quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="quantity" class="form-label">
                            <i class="fas fa-plus me-2"></i>Quantity to Add
                        </label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Add Stock
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Purchase Section -->
        <div class="content-section" id="purchase-section">
            <div class="form-container">
                <form method="POST" id="purchaseForm">
                    <input type="hidden" name="action" value="add_purchase">
                    
                    <div id="purchaseItems">
                        <?php foreach ($menu_items as $index => $item): ?>
                            <div class="purchase-item">
                                <div class="purchase-item-name">
                                    <i class="fas fa-utensils me-2"></i>
                                    <?php echo htmlspecialchars($item['menu_name']); ?>
                                </div>
                                <div class="purchase-item-price">
                                    <i class="fas fa-tag me-1"></i>
                                    Cost Price: RM<?php echo number_format($item['cost_price'], 2); ?>
                                </div>
                                <input type="hidden" name="items[<?php echo $index; ?>][menu_id]" value="<?php echo $item['menu_id']; ?>">
                                
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $index; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control quantity-input" 
                                           name="items[<?php echo $index; ?>][quantity]" 
                                           id="qty_<?php echo $index; ?>"
                                           min="0" value="0" onchange="calculateTotal()">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $index; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="total-display">
                        <i class="fas fa-calculator me-2"></i>
                        Total Amount: RM<span id="total_display">0.00</span>
                        <input type="hidden" id="total_amount" name="total_amount">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-shopping-cart me-2"></i>Record Purchase
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="nav-container">
            <button class="nav-btn active" onclick="showSection('view')" id="view-nav">
                <i class="fas fa-list"></i>
                <span>View</span>
            </button>
            <button class="nav-btn" onclick="showSection('add')" id="add-nav">
                <i class="fas fa-plus"></i>
                <span>Add</span>
            </button>
            <button class="nav-btn" onclick="showSection('purchase')" id="purchase-nav">
                <i class="fas fa-shopping-cart"></i>
                <span>Purchase</span>
            </button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation functionality
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(el => {
                el.classList.remove('active');
            });
            
            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-btn').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(section + '-section').classList.add('active');
            document.getElementById(section + '-nav').classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Quantity control functions
        function changeQuantity(index, delta) {
            const input = document.getElementById('qty_' + index);
            const currentValue = parseInt(input.value) || 0;
            const newValue = Math.max(0, currentValue + delta);
            input.value = newValue;
            calculateTotal();
            
            // Add visual feedback
            input.style.transform = 'scale(1.1)';
            setTimeout(() => {
                input.style.transform = 'scale(1)';
            }, 150);
        }
        
        // Calculate total function
        function calculateTotal() {
            let total = 0;
            const items = document.querySelectorAll('#purchaseItems .purchase-item');
            
            items.forEach((item, index) => {
                const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
                const costPriceText = item.querySelector('.purchase-item-price').textContent;
                const costPrice = parseFloat(costPriceText.match(/RM([\d.]+)/)[1]) || 0;
                total += quantity * costPrice;
            });
            
            document.getElementById('total_amount').value = total.toFixed(2);
            document.getElementById('total_display').textContent = total.toFixed(2);
            
            // Add visual feedback for total change
            const totalDisplay = document.querySelector('.total-display');
            totalDisplay.style.transform = 'scale(1.05)';
            setTimeout(() => {
                totalDisplay.style.transform = 'scale(1)';
            }, 200);
        }
        
        // Form submission with loading state
        function addLoadingState(form) {
            form.classList.add('loading');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
                form.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
            
            // Add loading states to forms
            document.getElementById('addStockForm').addEventListener('submit', function() {
                addLoadingState(this);
            });
            
            document.getElementById('purchaseForm').addEventListener('submit', function() {
                addLoadingState(this);
            });
            
            // Add haptic feedback for supported devices
            if ('vibrate' in navigator) {
                document.querySelectorAll('.nav-btn, .quantity-btn, .btn-primary').forEach(btn => {
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
    
    <style>
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
    </style>
</body>
</html>

<?php
$conn->close();
?>