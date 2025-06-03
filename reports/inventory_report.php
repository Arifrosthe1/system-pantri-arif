<?php
include '../dbcon.php';

// Set default date range (last 7 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Get filter parameters
if (isset($_GET['start_date']) && $_GET['start_date']) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date']) && $_GET['end_date']) {
    $end_date = $_GET['end_date'];
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="inventory_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
}

// Get inventory data
$query = "
    SELECT 
        DATE(COALESCE(t.trans_date, cp.purchase_date)) as report_date,
        m.menu_name,
        COALESCE(SUM(cp.quantity), 0) as stock_in,
        COALESCE(SUM(ti.quantity), 0) as stock_out,
        m.stock_quantity as current_stock
    FROM menu m
    LEFT JOIN cost_purchase cp ON m.menu_id = cp.menu_id 
        AND cp.purchase_date BETWEEN ? AND ?
    LEFT JOIN trans_item ti ON m.menu_id = ti.menu_id
    LEFT JOIN trans t ON ti.trans_id = t.trans_id 
        AND t.trans_date BETWEEN ? AND ?
    WHERE (cp.purchase_date BETWEEN ? AND ? OR t.trans_date BETWEEN ? AND ?)
    GROUP BY m.menu_id, DATE(COALESCE(t.trans_date, cp.purchase_date))
    ORDER BY report_date DESC, m.menu_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssssssss', $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$inventory_data = $result->fetch_all(MYSQLI_ASSOC);

// Group data by date
$grouped_data = [];
foreach ($inventory_data as $row) {
    $date = $row['report_date'];
    if (!isset($grouped_data[$date])) {
        $grouped_data[$date] = [];
    }
    $grouped_data[$date][] = $row;
}

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(DISTINCT t.trans_id) as total_transactions,
        COALESCE(SUM(ti.quantity), 0) as total_items_sold,
        COALESCE(SUM(t.total_amount), 0) as total_revenue,
        COUNT(DISTINCT cp.id) as total_purchases,
        COALESCE(SUM(cp.quantity), 0) as total_items_purchased,
        COALESCE(SUM(cp.total_cost), 0) as total_purchase_cost
    FROM trans t
    LEFT JOIN trans_item ti ON t.trans_id = ti.trans_id
    LEFT JOIN cost_purchase cp ON cp.purchase_date BETWEEN ? AND ?
    WHERE t.trans_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Excel export output
    echo "<table border='1'>";
    echo "<tr><td colspan='5' style='font-weight:bold; font-size:16px;'>Daily Inventory Report</td></tr>";
    echo "<tr><td colspan='5'>Period: " . date('d/m/Y', strtotime($start_date)) . " to " . date('d/m/Y', strtotime($end_date)) . "</td></tr>";
    echo "<tr><td></td></tr>";
    echo "<tr><td><b>Date</b></td><td><b>Item Name</b></td><td><b>Stock IN</b></td><td><b>Stock OUT</b></td><td><b>Current Stock</b></td></tr>";
    
    foreach ($grouped_data as $date => $items) {
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($item['report_date'])) . "</td>";
            echo "<td>" . htmlspecialchars($item['menu_name']) . "</td>";
            echo "<td>" . $item['stock_in'] . "</td>";
            echo "<td>" . $item['stock_out'] . "</td>";
            echo "<td>" . $item['current_stock'] . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - System Pantri ARIF</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }
        
        .main-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #198754;
        }
        
        .summary-card p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .report-table {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            padding: 12px;
            font-weight: 600;
            color: #495057;
        }
        
        .table td {
            border: none;
            padding: 12px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .date-header {
            background: #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        
        .export-btn {
            background: #198754;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .export-btn:hover {
            background: #157347;
            color: white;
            text-decoration: none;
        }
        
        .btn-filter {
            background: #0d6efd;
            border-color: #0d6efd;
        }
        
        .btn-filter:hover {
            background: #0b5ed7;
            border-color: #0a58ca;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .header, .filter-section, .report-table {
                padding: 15px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Daily Inventory Report</h1>
                    <p class="text-muted mb-0">Track stock movements and inventory levels</p>
                </div>
                <a href="../report.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-filter flex-fill">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=excel" 
                           class="export-btn">
                            <i class="fas fa-download"></i> Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3><?php echo number_format($summary['total_transactions']); ?></h3>
                <p>Total Transactions</p>
            </div>
            <div class="summary-card">
                <h3><?php echo number_format($summary['total_items_sold']); ?></h3>
                <p>Items Sold</p>
            </div>
            <div class="summary-card">
                <h3>RM<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-card">
                <h3><?php echo number_format($summary['total_items_purchased']); ?></h3>
                <p>Items Purchased</p>
            </div>
        </div>

        <!-- Report Table -->
        <div class="report-table">
            <?php if (empty($grouped_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <h4>No Data Found</h4>
                    <p>No inventory movements found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th class="text-center">Stock IN</th>
                                <th class="text-center">Stock OUT</th>
                                <th class="text-center">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_data as $date => $items): ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <?php if ($index === 0): ?>
                                        <tr class="date-header">
                                            <td colspan="5">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                <?php echo date('l, d F Y', strtotime($date)); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($item['report_date'])); ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($item['menu_name']); ?></td>
                                        <td class="text-center">
                                            <?php if ($item['stock_in'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $item['stock_in']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($item['stock_out'] > 0): ?>
                                                <span class="badge bg-danger"><?php echo $item['stock_out']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $item['current_stock']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set max date to today
        document.getElementById('end_date').max = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').max = new Date().toISOString().split('T')[0];
        
        // Auto-adjust start date when end date changes
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date');
            if (startDate.value > this.value) {
                startDate.value = this.value;
            }
        });
        
        // Auto-adjust end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            if (endDate.value < this.value) {
                endDate.value = this.value;
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>