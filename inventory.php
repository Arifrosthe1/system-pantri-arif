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
                // Loop through items and insert into cost_purchase
                foreach ($_POST['items'] as $item) {
                    if ($item['quantity'] > 0) {
                        $menu_id = $item['menu_id'];
                        $quantity = $item['quantity'];
                        $unit_price = $item['unit_price'];
                        $total_cost = $quantity * $unit_price;

                        // Insert into cost_purchase
                        $stmt = $conn->prepare("INSERT INTO cost_purchase (menu_id, quantity, purchase_date, total_cost) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iisd", $menu_id, $quantity, $purchase_date, $total_cost);
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

// Get all menu items
$result = $conn->query("SELECT * FROM menu ORDER BY menu_name");
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
    <title>Inventory - System Pantri ARIF</title>
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
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
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
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-link {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            margin-right: 8px;
            color: #666;
            background: #ffffff;
            font-size: 0.9rem;
            padding: 12px 16px;
        }
        
        .nav-tabs .nav-link.active {
            background: #059669;
            color: white;
            border-color: #059669;
        }
        
        .inventory-item {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .item-details {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-low {
            background:rgb(255, 254, 188);
            color:rgb(146, 146, 18);
        }
        
        .stock-ok {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .stock-out {
            background: #fef2f2;
            color: #ef4444;
        }
        
        .btn-primary {
            background: #059669;
            border-color: #059669;
            border-radius: 8px;
            font-weight: 500;
            padding: 12px 20px;
        }
        
        .btn-primary:hover {
            background: #047857;
            border-color: #047857;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            padding: 12px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #059669;
            box-shadow: 0 0 0 0.2rem rgba(5, 150, 105, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .purchase-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 16px;
            }
            
            .nav-tabs .nav-link {
                font-size: 0.8rem;
                padding: 10px 12px;
                margin-right: 4px;
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
            <h1>INVENTORY</h1>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
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
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="view-tab" data-bs-toggle="tab" data-bs-target="#view" type="button">
                    View Stock
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button">
                    Add Stock
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchase" type="button">
                    Purchase
                </button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="inventoryTabContent">
            <!-- View Stock Tab -->
            <div class="tab-pane fade show active" id="view" role="tabpanel">
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
                                Stock: <?php echo $item['stock_quantity']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Add Stock Tab -->
            <div class="tab-pane fade" id="add" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="add_stock">
                    
                    <div class="mb-3">
                        <label for="menu_id" class="form-label">Select Item</label>
                        <select class="form-control" id="menu_id" name="menu_id" required>
                            <option value="">Choose item...</option>
                            <?php foreach ($menu_items as $item): ?>
                                <option value="<?php echo $item['menu_id']; ?>">
                                    <?php echo htmlspecialchars($item['menu_name']); ?> (Current: <?php echo $item['stock_quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity to Add</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Add Stock</button>
                </form>
            </div>
            
            <!-- Purchase Tab -->
            <div class="tab-pane fade" id="purchase" role="tabpanel">
                <form method="POST" id="purchaseForm">
                    <input type="hidden" name="action" value="add_purchase">
                    
                    <div id="purchaseItems">
                        <?php foreach ($menu_items as $index => $item): ?>
                            <div class="purchase-item">
                                <div style="font-weight: 500; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($item['menu_name']); ?>
                                </div>
                                <input type="hidden" name="items[<?php echo $index; ?>][menu_id]" value="<?php echo $item['menu_id']; ?>">
                                
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label" style="font-size: 0.8rem;">Quantity</label>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="items[<?php echo $index; ?>][quantity]" 
                                               min="0" value="0" onchange="calculateTotal()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label" style="font-size: 0.8rem;">Unit Price (RM)</label>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="items[<?php echo $index; ?>][unit_price]" 
                                               step="0.01" min="0" value="<?php echo $item['cost_price']; ?>" onchange="calculateTotal()">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="total_amount" class="form-label">Total Amount (RM)</label>
                        <input type="number" class="form-control" id="total_amount" name="total_amount" 
                               step="0.01" min="0" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Record Purchase</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateTotal() {
            let total = 0;
            const items = document.querySelectorAll('#purchaseItems .purchase-item');
            
            items.forEach(item => {
                const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
                const unitPrice = parseFloat(item.querySelector('input[name*="[unit_price]"]').value) || 0;
                total += quantity * unitPrice;
            });
            
            document.getElementById('total_amount').value = total.toFixed(2);
        }
        
        // Initialize total calculation
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
        
        // Add touch feedback for mobile
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            tab.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>