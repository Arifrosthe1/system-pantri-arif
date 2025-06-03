<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Reports - System Pantri ARIF</title>
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
            padding: 80px 16px 40px 16px;
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
        
        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease;
        }
        
        .header h1 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: #1e293b;
            letter-spacing: -0.8px;
        }
        
        .header p {
            color: #64748b;
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }
        
        /* Menu Buttons */
        .menu-button {
            width: 100%;
            margin-bottom: 16px;
            border: none;
            border-radius: 20px;
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }
        
        .menu-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .menu-button:hover::before {
            left: 100%;
        }
        
        .menu-button:hover {
            color: #1e293b;
            text-decoration: none;
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .menu-button:active {
            transform: translateY(-2px);
        }
        
        .menu-button i {
            font-size: 1.5rem;
            margin-right: 20px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .menu-button span {
            font-size: 1.1rem;
            font-weight: 600;
            flex: 1;
        }
        
        .menu-button .arrow {
            font-size: 1rem;
            color: #cbd5e1;
            transition: all 0.3s ease;
        }
        
        .menu-button:hover .arrow {
            color: #64748b;
            transform: translateX(4px);
        }
        
        /* Individual button styles */
        .inventory-btn {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-left: 4px solid #059669;
        }
        
        .inventory-btn i {
            background: #059669;
            color: white;
        }
        
        .sales-btn {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-left: 4px solid #2563eb;
        }
        
        .sales-btn i {
            background: #2563eb;
            color: white;
        }
        
        .profit-btn {
            background: linear-gradient(135deg, #fed7aa, #fdba74);
            border-left: 4px solid #d97706;
        }
        
        .profit-btn i {
            background: #d97706;
            color: white;
        }
        
        .receipt-btn {
            background: linear-gradient(135deg, #e9d5ff, #d8b4fe);
            border-left: 4px solid #7c3aed;
        }
        
        .receipt-btn i {
            background: #7c3aed;
            color: white;
        }
        
        /* Back button special styling */
        .back-menu-btn {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important;
            border-left: 4px solid #6c757d !important;
            margin-top: 30px;
        }
        
        .back-menu-btn i {
            background: #6c757d !important;
            color: white !important;
        }
        
        .back-menu-btn:hover {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1) !important;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .menu-button {
            animation: fadeInUp 0.6s ease;
            animation-fill-mode: both;
        }
        
        .menu-button:nth-child(1) { animation-delay: 0.1s; }
        .menu-button:nth-child(2) { animation-delay: 0.2s; }
        .menu-button:nth-child(3) { animation-delay: 0.3s; }
        .menu-button:nth-child(4) { animation-delay: 0.4s; }
        .menu-button:nth-child(5) { animation-delay: 0.5s; }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .main-container {
                padding: 80px 12px 40px 12px;
            }
            
            .header h1 {
                font-size: 1.6rem;
            }
            
            .menu-button {
                padding: 20px;
            }
            
            .menu-button i {
                font-size: 1.3rem;
                margin-right: 16px;
                width: 28px;
                height: 28px;
            }
            
            .menu-button span {
                font-size: 1rem;
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
        
        /* Ripple effect for touch */
        .menu-button {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="header-title">REPORTS</div>
        <div class="header-spacer"></div>
    </div>
    
    <div class="container main-container">
        <div class="header">
            <h1>LAPORAN SISTEM</h1>
            <p>System Reports Dashboard</p>
        </div>
        
        <div class="row">
            <div class="col-12">
                <a href="reports/inventory_report.php" class="menu-button inventory-btn">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Laporan Inventori Harian</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
            </div>
            
            <div class="col-12">
                <a href="reports/sales_report.php" class="menu-button sales-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>Rekod Jualan Harian</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
            </div>
            
            <div class="col-12">
                <a href="reports/profit_report.php" class="menu-button profit-btn">
                    <i class="fas fa-calculator"></i>
                    <span>Analisis Untung/Rugi Harian</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
            </div>
            
            <div class="col-12">
                <a href="reports/receipt_gallery.php" class="menu-button receipt-btn">
                    <i class="fas fa-receipt"></i>
                    <span>Pengurusan Resit</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
            </div>
            
            <div class="col-12">
                <a href="index.php" class="menu-button back-menu-btn">
                    <i class="fas fa-home"></i>
                    <span>Kembali ke Menu Utama</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 System Pantri ARIF. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add ripple effect for touch feedback
        function createRipple(event) {
            const button = event.currentTarget;
            const circle = document.createElement("span");
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            
            const rect = button.getBoundingClientRect();
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - rect.left - radius}px`;
            circle.style.top = `${event.clientY - rect.top - radius}px`;
            circle.classList.add("ripple");
            
            const ripple = button.getElementsByClassName("ripple")[0];
            if (ripple) {
                ripple.remove();
            }
            
            button.appendChild(circle);
        }
        
        // Add touch feedback and ripple effect
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('click', createRipple);
            
            button.addEventListener('touchstart', function() {
                this.style.transform = 'translateY(-2px) scale(0.98)';
            });
            
            button.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
        
        // Add haptic feedback for supported devices
        if ('vibrate' in navigator) {
            document.querySelectorAll('.menu-button, .back-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    navigator.vibrate(50);
                });
            });
        }
        
        // Prevent zoom on input focus for iOS
        document.addEventListener('touchstart', function() {}, true);
        
        // Add loading state when navigating
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('click', function(e) {
                // Don't add loading state for back button
                if (!this.classList.contains('back-menu-btn')) {
                    this.classList.add('loading');
                    const icon = this.querySelector('i:first-child');
                    const originalIcon = icon.className;
                    icon.className = 'fas fa-spinner fa-spin';
                    
                    // Restore after 2 seconds as fallback
                    setTimeout(() => {
                        this.classList.remove('loading');
                        icon.className = originalIcon;
                    }, 2000);
                }
            });
        });
        
        // Page load animation
        document.addEventListener('DOMContentLoaded', function() {
            // Animate header
            const header = document.querySelector('.header');
            header.style.opacity = '0';
            header.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                header.style.transition = 'all 0.6s ease';
                header.style.opacity = '1';
                header.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>