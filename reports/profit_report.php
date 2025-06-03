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
    header('Content-Disposition: attachment;filename="profit_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
}

// Get profit/loss data
$query = "
    SELECT 
        t.trans_date,
        m.menu_name,
        SUM(ti.quantity) as qty_sold,
        m.cost_price,
        ti.unit_price as sale_price,
        (SUM(ti.quantity) * m.cost_price) as total_cost,
        (SUM(ti.quantity) * ti.unit_price) as total_revenue,
        ((SUM(ti.quantity) * ti.unit_price) - (SUM(ti.quantity) * m.cost_price)) as profit_loss
    FROM trans t
    JOIN trans_item ti ON t.trans_id = ti.trans_id
    JOIN menu m ON ti.menu_id = m.menu_id
    WHERE t.trans_date BETWEEN ? AND ?
    GROUP BY t.trans_date, m.menu_id, ti.unit_price
    ORDER BY t.trans_date DESC, m.menu_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$profit_data = $result->fetch_all(MYSQLI_ASSOC);

// Group data by date
$grouped_data = [];
foreach ($profit_data as $row) {
    $date = $row['trans_date'];
    if (!isset($grouped_data[$date])) {
        $grouped_data[$date] = [];
    }
    $grouped_data[$date][] = $row;
}

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(DISTINCT t.trans_id) as total_transactions,
        SUM(ti.quantity) as total_items_sold,
        SUM(ti.quantity * m.cost_price) as total_cost,
        SUM(ti.quantity * ti.unit_price) as total_revenue,
        SUM((ti.quantity * ti.unit_price) - (ti.quantity * m.cost_price)) as total_profit,
        AVG((ti.quantity * ti.unit_price) - (ti.quantity * m.cost_price)) as avg_profit_per_transaction
    FROM trans t
    JOIN trans_item ti ON t.trans_id = ti.trans_id
    JOIN menu m ON ti.menu_id = m.menu_id
    WHERE t.trans_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Calculate profit margin
$profit_margin = $summary['total_revenue'] > 0 ? 
    (($summary['total_profit'] / $summary['total_revenue']) * 100) : 0;

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Excel export output
    echo "<table border='1'>";
    echo "<tr><td colspan='8' style='font-weight:bold; font-size:16px;'>Daily Profit/Loss Report</td></tr>";
    echo "<tr><td colspan='8'>Period: " . date('d/m/Y', strtotime($start_date)) . " to " . date('d/m/Y', strtotime($end_date)) . "</td></tr>";
    echo "<tr><td></td></tr>";
    echo "<tr><td><b>Date</b></td><td><b>Item</b></td><td><b>Qty Sold</b></td><td><b>Cost Price</b></td><td><b>Sale Price</b></td><td><b>Total Cost</b></td><td><b>Total Revenue</b></td><td><b>Profit/Loss</b></td></tr>";
    
    foreach ($grouped_data as $date => $items) {
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($item['trans_date'])) . "</td>";
            echo "<td>" . htmlspecialchars($item['menu_name']) . "</td>";
            echo "<td>" . $item['qty_sold'] . "</td>";
            echo "<td>RM" . number_format($item['cost_price'], 2) . "</td>";
            echo "<td>RM" . number_format($item['sale_price'], 2) . "</td>";
            echo "<td>RM" . number_format($item['total_cost'], 2) . "</td>";
            echo "<td>RM" . number_format($item['total_revenue'], 2) . "</td>";
            echo "<td>RM" . number_format($item['profit_loss'], 2) . "</td>";
            echo "</tr>";
        }
    }
    
    // Summary row
    echo "<tr><td></td></tr>";
    echo "<tr style='font-weight:bold;'>";
    echo "<td colspan='5'>TOTAL SUMMARY</td>";
    echo "<td>RM" . number_format($summary['total_cost'], 2) . "</td>";
    echo "<td>RM" . number_format($summary['total_revenue'], 2) . "</td>";
    echo "<td>RM" . number_format($summary['total_profit'], 2) . "</td>";
    echo "</tr>";
    echo "</table>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit/Loss Report - System Pantri ARIF</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        }
        
        .main-container {
            max-width: 1200px;
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
        
        .summary-card.profit {
            border-left: 4px solid #198754;
        }
        
        .summary-card.cost {
            border-left: 4px solid #dc3545;
        }
        
        .summary-card.revenue {
            border-left: 4px solid #0d6efd;
        }
        
        .summary-card.margin {
            border-left: 4px solid #fd7e14;
        }
        
        .summary-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .summary-card h3.profit {
            color: #198754;
        }
        
        .summary-card h3.cost {
            color: #dc3545;
        }
        
        .summary-card h3.revenue {
            color: #0d6efd;
        }
        
        .summary-card h3.margin {
            color: #fd7e14;
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
            font-size: 0.9rem;
        }
        
        .table td {
            border: none;
            padding: 12px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.9rem;
        }
        
        .date-header {
            background: #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        
        .profit-positive {
            color: #198754;
            font-weight: 600;
        }
        
        .profit-negative {
            color: #dc3545;
            font-weight: 600;
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
        
        .total-row {
            background: #f8f9fa;
            font-weight: 600;
            border-top: 2px solid #dee2e6;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .header, .filter-section, .report-table {
                padding: 15px;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table th, .table td {
                padding: 8px;
                font-size: 0.8rem;
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
                    <h1 class="h3 mb-1">Daily Profit/Loss Report</h1>
                    <p class="text-muted mb-0">Analyze daily profitability and business performance</p>
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
            <div class="summary-card revenue">
                <h3 class="revenue">RM<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="summary-card cost">
                <h3 class="cost">RM<?php echo number_format($summary['total_cost'], 2); ?></h3>
                <p>Total Cost</p>
            </div>
            <div class="summary-card profit">
                <h3 class="profit">RM<?php echo number_format($summary['total_profit'], 2); ?></h3>
                <p>Total Profit</p>
            </div>
            <div class="summary-card margin">
                <h3 class="margin"><?php echo number_format($profit_margin, 1); ?>%</h3>
                <p>Profit Margin</p>
            </div>
        </div>

        <!-- Report Table -->
        <div class="report-table">
            <?php if (empty($grouped_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h4>No Data Found</h4>
                    <p>No sales transactions found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Cost Price</th>
                                <th class="text-end">Sale Price</th>
                                <th class="text-end">Total Cost</th>
                                <th class="text-end">Total Revenue</th>
                                <th class="text-end">Profit/Loss</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $daily_totals = [];
                            foreach ($grouped_data as $date => $items): 
                                $daily_cost = 0;
                                $daily_revenue = 0;
                                $daily_profit = 0;
                            ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <?php if ($index === 0): ?>
                                        <tr class="date-header">
                                            <td colspan="8">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                <?php echo date('l, d F Y', strtotime($date)); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($item['trans_date'])); ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($item['menu_name']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $item['qty_sold']; ?></span>
                                        </td>
                                        <td class="text-end">RM<?php echo number_format($item['cost_price'], 2); ?></td>
                                        <td class="text-end">RM<?php echo number_format($item['sale_price'], 2); ?></td>
                                        <td class="text-end">RM<?php echo number_format($item['total_cost'], 2); ?></td>
                                        <td class="text-end">RM<?php echo number_format($item['total_revenue'], 2); ?></td>
                                        <td class="text-end <?php echo $item['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                            RM<?php echo number_format($item['profit_loss'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    $daily_cost += $item['total_cost'];
                                    $daily_revenue += $item['total_revenue'];
                                    $daily_profit += $item['profit_loss'];
                                    ?>
                                <?php endforeach; ?>
                                
                                <!-- Daily Total Row -->
                                <tr class="total-row">
                                    <td colspan="5" class="text-end"><strong>Daily Total:</strong></td>
                                    <td class="text-end"><strong>RM<?php echo number_format($daily_cost, 2); ?></strong></td>
                                    <td class="text-end"><strong>RM<?php echo number_format($daily_revenue, 2); ?></strong></td>
                                    <td class="text-end <?php echo $daily_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                        <strong>RM<?php echo number_format($daily_profit, 2); ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Grand Total Row -->
                            <tr class="total-row" style="border-top: 3px solid #0d6efd;">
                                <td colspan="5" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                <td class="text-end"><strong>RM<?php echo number_format($summary['total_cost'], 2); ?></strong></td>
                                <td class="text-end"><strong>RM<?php echo number_format($summary['total_revenue'], 2); ?></strong></td>
                                <td class="text-end <?php echo $summary['total_profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                    <strong>RM<?php echo number_format($summary['total_profit'], 2); ?></strong>
                                </td>
                            </tr>
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