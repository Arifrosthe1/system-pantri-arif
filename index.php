<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Pantri ARIF</title>
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
            padding: 40px 20px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .header h1 {
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 8px;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }
        
        .header p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }
        
        .menu-button {
            width: 100%;
            height: 80px;
            margin-bottom: 16px;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            background: #ffffff;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 24px;
            transition: all 0.2s ease;
            box-shadow: none;
        }
        
        .menu-button:hover {
            background: #f8f9fa;
            border-color: #d0d7de;
            color: #333;
            text-decoration: none;
            transform: none;
        }
        
        .menu-button i {
            font-size: 1.5rem;
            margin-right: 20px;
            width: 24px;
            text-align: center;
        }
        
        .menu-button span {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .pos-btn i {
            color: #059669;
        }
        
        .report-btn i {
            color: #2563eb;
        }
        
        .inventory-btn i {
            color: #d97706;
        }
        
        .footer {
            text-align: center;
            margin-top: 60px;
            color: #999;
            font-size: 0.85rem;
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 30px 16px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .menu-button {
                height: 72px;
                padding: 0 20px;
            }
            
            .menu-button i {
                font-size: 1.3rem;
                margin-right: 16px;
            }
        }
    </style>
</head>
<body>
    <br>
    <div class="container main-container">
        <div class="header">
            <h1>SYSTEM PANTRI ARIF</h1>
            <p>Pantry Management System</p>
        </div>
        
        <div class="row">
            <div class="col-12">
                <a href="pos.php" class="menu-button pos-btn">
                    <i class="fas fa-cash-register"></i>
                    <span>Point of Sale</span>
                </a>
            </div>
            
            <div class="col-12">
                <a href="report.php" class="menu-button report-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
            </div>
            
            <div class="col-12">
                <a href="inventory.php" class="menu-button inventory-btn">
                    <i class="fas fa-warehouse"></i>
                    <span>Inventory</span>
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 SYSTEM PANTRI ARIF. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function navigateTo(page) {
            // Replace these with your actual page URLs
            switch(page) {
                case 'pos':
                    // window.location.href = 'pos.html';
                    alert('Navigate to POS page');
                    break;
                case 'report':
                    // window.location.href = 'report.html';
                    alert('Navigate to Report page');
                    break;
                case 'inventory':
                    // window.location.href = 'inventory.html';
                    alert('Navigate to Inventory page');
                    break;
                default:
                    alert('Page not found');
            }
        }
        
        // Add touch feedback for mobile
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>