<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>System Pantri ARIF</title>
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
            padding: 60px 16px 40px 16px;
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInUp 0.8s ease;
        }
        
        .logo-container {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #059669, #047857);
            border-radius: 32px;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(5, 150, 105, 0.3);
            animation: pulse 2s infinite;
        }
        
        .logo-container i {
            font-size: 3rem;
            color: white;
        }
        
        .header h1 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 12px;
            color: #1e293b;
            letter-spacing: -1px;
            line-height: 1.2;
        }
        
        .header p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
        }
        
        .menu-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .menu-button {
            width: 100%;
            height: 88px;
            border: none;
            border-radius: 20px;
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 28px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
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
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        
        .menu-button:active {
            transform: translateY(-2px);
        }
        
        .menu-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            transition: all 0.3s ease;
        }
        
        .menu-icon i {
            font-size: 1.5rem;
        }
        
        .menu-text {
            flex: 1;
        }
        
        .menu-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .menu-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .menu-arrow {
            color: #cbd5e1;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .menu-button:hover .menu-arrow {
            color: #059669;
            transform: translateX(4px);
        }
        
        /* Individual button themes */
        .pos-btn:hover .menu-icon {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        }
        
        .pos-btn .menu-icon {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        
        .pos-btn .menu-icon i {
            color: #059669;
        }
        
        .report-btn:hover .menu-icon {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }
        
        .report-btn .menu-icon {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }
        
        .report-btn .menu-icon i {
            color: #2563eb;
        }
        
        .inventory-btn:hover .menu-icon {
            background: linear-gradient(135deg, #fed7aa, #fdba74);
        }
        
        .inventory-btn .menu-icon {
            background: linear-gradient(135deg, #fff7ed, #fed7aa);
        }
        
        .inventory-btn .menu-icon i {
            color: #d97706;
        }
        
        .footer {
            text-align: center;
            margin-top: 60px;
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
            animation: fadeIn 1s ease 0.4s both;
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
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Loading animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .loading .menu-button {
            transform: scale(0.98);
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .main-container {
                padding: 40px 12px 30px 12px;
            }
            
            .header h1 {
                font-size: 1.7rem;
            }
            
            .header p {
                font-size: 1rem;
            }
            
            .logo-container {
                width: 100px;
                height: 100px;
                border-radius: 28px;
            }
            
            .logo-container i {
                font-size: 2.5rem;
            }
            
            .menu-button {
                height: 80px;
                padding: 0 24px;
            }
            
            .menu-icon {
                width: 48px;
                height: 48px;
                margin-right: 16px;
            }
            
            .menu-icon i {
                font-size: 1.3rem;
            }
            
            .menu-title {
                font-size: 1.1rem;
            }
            
            .menu-subtitle {
                font-size: 0.85rem;
            }
        }
        
        /* Hover effects for desktop */
        @media (hover: hover) {
            .menu-button:hover {
                animation: buttonHover 0.3s ease forwards;
            }
        }
        
        @keyframes buttonHover {
            0% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
            100% { transform: translateY(-4px); }
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
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="header">
            <div class="logo-container">
                <i class="fas fa-store"></i>
            </div>
            <h1>SYSTEM PANTRI ARIF</h1>
            <p>Pantry Management System</p>
        </div>
        
        <div class="menu-grid">
            <a href="pos.php" class="menu-button pos-btn">
                <div class="menu-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="menu-text">
                    <div class="menu-title">Point of Sale</div>
                    <div class="menu-subtitle">Process orders & payments</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="report.php" class="menu-button report-btn">
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="menu-text">
                    <div class="menu-title">Reports</div>
                    <div class="menu-subtitle">Sales analytics & insights</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <a href="inventory.php" class="menu-button inventory-btn">
                <div class="menu-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="menu-text">
                    <div class="menu-title">Inventory</div>
                    <div class="menu-subtitle">Stock management & tracking</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 SYSTEM PANTRI ARIF. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading state when navigating
        function addLoadingState(link) {
            document.body.classList.add('loading');
            
            // Visual feedback
            link.style.transform = 'translateY(-2px)';
            link.style.opacity = '0.8';
            
            // Add spinner to clicked button
            const icon = link.querySelector('.menu-icon i');
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';
            
            // Restore after delay (fallback)
            setTimeout(() => {
                document.body.classList.remove('loading');
                icon.className = originalClass;
                link.style.transform = '';
                link.style.opacity = '';
            }, 2000);
        }
        
        // Add touch feedback and loading states
        document.querySelectorAll('.menu-button').forEach(button => {
            // Touch feedback for mobile
            button.addEventListener('touchstart', function(e) {
                this.style.transform = 'translateY(-2px) scale(0.98)';
            });
            
            button.addEventListener('touchend', function(e) {
                this.style.transform = '';
            });
            
            // Click handler for loading state
            button.addEventListener('click', function(e) {
                addLoadingState(this);
            });
            
            // Prevent context menu on long press
            button.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
        });
        
        // Add haptic feedback for supported devices
        if ('vibrate' in navigator) {
            document.querySelectorAll('.menu-button').forEach(btn => {
                btn.addEventListener('click', () => {
                    navigator.vibrate(50);
                });
            });
        }
        
        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            const buttons = document.querySelectorAll('.menu-button');
            const currentFocus = document.activeElement;
            const currentIndex = Array.from(buttons).indexOf(currentFocus);
            
            if (e.key === 'ArrowDown' && currentIndex < buttons.length - 1) {
                e.preventDefault();
                buttons[currentIndex + 1].focus();
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                e.preventDefault();
                buttons[currentIndex - 1].focus();
            } else if (e.key === 'Enter' && currentFocus.classList.contains('menu-button')) {
                e.preventDefault();
                currentFocus.click();
            }
        });
        
        // Page load animation
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger animations after a short delay
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
            
            // Preload other pages for faster navigation
            const pages = ['pos.php', 'report.php', 'inventory.php'];
            pages.forEach(page => {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = page;
                document.head.appendChild(link);
            });
        });
        
        // Prevent zoom on input focus for iOS
        document.addEventListener('touchstart', function() {}, true);
    </script>
</body>
</html>