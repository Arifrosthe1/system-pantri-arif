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
    header('Content-Disposition: attachment;filename="sales_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
}

// Get sales data
$query = "
    SELECT 
        t.trans_date,
        t.trans_time,
        t.trans_id,
        t.total_amount,
        ti.quantity,
        ti.unit_price,
        m.menu_name
    FROM trans t
    JOIN trans_item ti ON t.trans_id = ti.trans_id
    JOIN menu m ON ti.menu_id = m.menu_id
    WHERE t.trans_date BETWEEN ? AND ?
    ORDER BY t.trans_date DESC, t.trans_time DESC, t.trans_id
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$sales_data = $result->fetch_all(MYSQLI_ASSOC);

// Group data by transaction
$grouped_data = [];
foreach ($sales_data as $row) {
    $trans_id = $row['trans_id'];
    if (!isset($grouped_data[$trans_id])) {
        $grouped_data[$trans_id] = [
            'trans_date' => $row['trans_date'],
            'trans_time' => $row['trans_time'],
            'total_amount' => $row['total_amount'],
            'items' => []
        ];
    }
    $grouped_data[$trans_id]['items'][] = [
        'menu_name' => $row['menu_name'],
        'quantity' => $row['quantity'],
        'unit_price' => $row['unit_price']
    ];
}

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(DISTINCT t.trans_id) as total_transactions,
        COALESCE(SUM(ti.quantity), 0) as total_items_sold,
        COALESCE(SUM(t.total_amount), 0) as total_revenue,
        COALESCE(AVG(t.total_amount), 0) as avg_transaction_value
    FROM trans t
    LEFT JOIN trans_item ti ON t.trans_id = ti.trans_id
    WHERE t.trans_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Excel export output
    echo "<table border='1'>";
    echo "<tr><td colspan='6' style='font-weight:bold; font-size:16px;'>Daily Sales Report</td></tr>";
    echo "<tr><td colspan='6'>Period: " . date('d/m/Y', strtotime($start_date)) . " to " . date('d/m/Y', strtotime($end_date)) . "</td></tr>";
    echo "<tr><td></td></tr>";
    echo "<tr><td><b>Date</b></td><td><b>Time</b></td><td><b>Transaction ID</b></td><td><b>Items Sold</b></td><td><b>Quantities</b></td><td><b>Total Amount</b></td></tr>";
    
    foreach ($grouped_data as $trans_id => $transaction) {
        $items_list = [];
        $quantities_list = [];
        foreach ($transaction['items'] as $item) {
            $items_list[] = $item['menu_name'];
            $quantities_list[] = $item['quantity'];
        }
        
        echo "<tr>";
        echo "<td>" . date('d/m/Y', strtotime($transaction['trans_date'])) . "</td>";
        echo "<td>" . date('H:i', strtotime($transaction['trans_time'])) . "</td>";
        echo "<td>#" . $trans_id . "</td>";
        echo "<td>" . implode(', ', $items_list) . "</td>";
        echo "<td>" . implode(', ', $quantities_list) . "</td>";
        echo "<td>RM" . number_format($transaction['total_amount'], 2) . "</td>";
        echo "</tr>";
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
    <title>Sales Report - System Pantri ARIF</title>
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
        
        .summary-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #0d6efd;
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
        
        .transaction-header {
            background: #e3f2fd;
            font-weight: 600;
            color: #1976d2;
        }
        
        .item-row {
            background: #fafafa;
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
        
        .transaction-id {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .total-amount {
            font-weight: 600;
            color: #198754;
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
            
            .table th, .table td {
                padding: 8px;
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
                    <h1 class="h3 mb-1">Daily Sales Report</h1>
                    <p class="text-muted mb-0">Track daily sales transactions and revenue</p>
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
                <h3>RM<?php echo number_format($summary['avg_transaction_value'], 2); ?></h3>
                <p>Avg Transaction</p>
            </div>
        </div>

        <!-- Report Table -->
        <div class="report-table">
            <?php if (empty($grouped_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h4>No Sales Found</h4>
                    <p>No sales transactions found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Transaction ID</th>
                                <th>Items Sold</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_data as $trans_id => $transaction): ?>
                                <?php $first_item = true; ?>
                                <?php foreach ($transaction['items'] as $item): ?>
                                    <tr class="<?php echo $first_item ? 'transaction-header' : 'item-row'; ?>">
                                        <?php if ($first_item): ?>
                                            <td rowspan="<?php echo count($transaction['items']); ?>">
                                                <div class="fw-medium"><?php echo date('d/m/Y', strtotime($transaction['trans_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($transaction['trans_time'])); ?></small>
                                            </td>
                                            <td rowspan="<?php echo count($transaction['items']); ?>">
                                                <span class="transaction-id">#<?php echo $trans_id; ?></span>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($item['menu_name']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $item['quantity']; ?></span>
                                        </td>
                                        <td class="text-end">RM<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <?php if ($first_item): ?>
                                            <td rowspan="<?php echo count($transaction['items']); ?>" class="text-end">
                                                <span class="total-amount">RM<?php echo number_format($transaction['total_amount'], 2); ?></span>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php $first_item = false; ?>
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