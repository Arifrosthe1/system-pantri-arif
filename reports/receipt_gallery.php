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

// Handle PDF/Text export
if (isset($_GET['export'])) {
    // Get receipts for export
    $export_query = "
        SELECT t.trans_id, t.trans_date, t.trans_time, t.total_amount, t.receipt_image,
               ti.quantity, ti.unit_price, m.menu_name
        FROM trans t
        LEFT JOIN trans_item ti ON t.trans_id = ti.trans_id
        LEFT JOIN menu m ON ti.menu_id = m.menu_id
        WHERE t.trans_date BETWEEN ? AND ? AND t.receipt_image IS NOT NULL
        ORDER BY t.trans_date DESC, t.trans_time DESC
    ";
    
    $stmt = $conn->prepare($export_query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $export_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($_GET['export'] == 'pdf') {
        // Try to use TCPDF if available, otherwise use simple text export
        if (file_exists('../tcpdf/tcpdf.php')) {
            require_once('../tcpdf/tcpdf.php');
            
            // Create PDF using TCPDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('System Pantri ARIF');
            $pdf->SetTitle('Daily Receipts - ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)));
            $pdf->SetHeaderData('', 0, 'Daily Receipts Report', 'Period: ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)));
            
            $current_trans = '';
            $html = '';
            
            foreach ($export_data as $row) {
                if ($current_trans != $row['trans_id']) {
                    if ($current_trans != '') {
                        $html .= '</table><hr><p><strong>Total Amount: RM ' . number_format($row['total_amount'], 2) . '</strong></p>';
                        $pdf->writeHTML($html, true, false, true, false, '');
                        $pdf->AddPage();
                    } else {
                        $pdf->AddPage();
                    }
                    
                    // Receipt header
                    $html = '<h2>Receipt #' . $row['trans_id'] . '</h2>';
                    $html .= '<p><strong>Date:</strong> ' . date('d/m/Y', strtotime($row['trans_date'])) . '</p>';
                    $html .= '<p><strong>Time:</strong> ' . date('h:i A', strtotime($row['trans_time'])) . '</p>';
                    $html .= '<hr>';
                    $html .= '<table border="1" cellpadding="5">';
                    $html .= '<tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>';
                    
                    $current_trans = $row['trans_id'];
                }
                
                if ($row['menu_name']) {
                    $item_total = $row['quantity'] * $row['unit_price'];
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($row['menu_name']) . '</td>';
                    $html .= '<td>' . $row['quantity'] . '</td>';
                    $html .= '<td>RM ' . number_format($row['unit_price'], 2) . '</td>';
                    $html .= '<td>RM ' . number_format($item_total, 2) . '</td>';
                    $html .= '</tr>';
                }
            }
            
            if (!empty($export_data)) {
                $html .= '</table><hr><p><strong>Total Amount: RM ' . number_format(end($export_data)['total_amount'], 2) . '</strong></p>';
                $pdf->writeHTML($html, true, false, true, false, '');
            }
            
            $pdf->Output('daily_receipts_' . $start_date . '_to_' . $end_date . '.pdf', 'D');
        } else {
            // Fallback to simple text export
            require_once('../tcpdf/tcpdf.php');
            $content = SimpleReceiptGenerator::generateTextReceipt($export_data, $start_date, $end_date);
            SimpleReceiptGenerator::downloadTextFile($content, 'daily_receipts_' . $start_date . '_to_' . $end_date . '.txt');
        }
        exit();
    }
}

// Get receipt data
$query = "
    SELECT t.trans_id, t.trans_date, t.trans_time, t.total_amount, t.receipt_image,
           COUNT(ti.trans_item_id) as item_count
    FROM trans t
    LEFT JOIN trans_item ti ON t.trans_id = ti.trans_id
    WHERE t.trans_date BETWEEN ? AND ? AND t.receipt_image IS NOT NULL
    GROUP BY t.trans_id
    ORDER BY t.trans_date DESC, t.trans_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$receipts = $result->fetch_all(MYSQLI_ASSOC);

// Group receipts by date
$grouped_receipts = [];
foreach ($receipts as $receipt) {
    $date = $receipt['trans_date'];
    if (!isset($grouped_receipts[$date])) {
        $grouped_receipts[$date] = [];
    }
    $grouped_receipts[$date][] = $receipt;
}

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(*) as total_receipts,
        COALESCE(SUM(total_amount), 0) as total_amount,
        MIN(trans_date) as first_date,
        MAX(trans_date) as last_date
    FROM trans 
    WHERE trans_date BETWEEN ? AND ? AND receipt_image IS NOT NULL
";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Gallery - System Pantri ARIF</title>
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
            color: #198754;
        }
        
        .summary-card p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .gallery-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .date-header {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 20px 0 15px 0;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .receipt-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .receipt-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .receipt-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
        }
        
        .receipt-image:hover {
            border-color: #0d6efd;
        }
        
        .receipt-info {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .receipt-id {
            font-weight: 600;
            color: #495057;
        }
        
        .receipt-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .receipt-amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: #198754;
            text-align: center;
        }
        
        .receipt-items {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
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
            background: #dc3545;
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
            background: #c82333;
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
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        /* Modal styles */
        .modal-img {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .header, .filter-section, .gallery-section {
                padding: 15px;
            }
            
            .receipt-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .date-header {
                padding: 12px 15px;
                font-size: 0.95rem;
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
                    <h1 class="h3 mb-1">Receipt Gallery</h1>
                    <p class="text-muted mb-0">View and manage receipt images</p>
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
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=pdf" 
                           class="export-btn">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3><?php echo number_format($summary['total_receipts']); ?></h3>
                <p>Total Receipts</p>
            </div>
            <div class="summary-card">
                <h3>RM<?php echo number_format($summary['total_amount'], 2); ?></h3>
                <p>Total Amount</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $summary['total_receipts'] > 0 ? date('d/m/Y', strtotime($summary['first_date'])) : '-'; ?></h3>
                <p>First Receipt</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $summary['total_receipts'] > 0 ? date('d/m/Y', strtotime($summary['last_date'])) : '-'; ?></h3>
                <p>Last Receipt</p>
            </div>
        </div>

        <!-- Gallery Section -->
        <div class="gallery-section">
            <?php if (empty($grouped_receipts)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h4>No Receipts Found</h4>
                    <p>No receipt images found for the selected date range.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_receipts as $date => $receipts): ?>
                    <div class="date-header">
                        <i class="fas fa-calendar-day"></i>
                        <?php echo date('l, d F Y', strtotime($date)); ?>
                        <span class="ms-auto badge bg-primary"><?php echo count($receipts); ?> receipt(s)</span>
                    </div>
                    
                    <div class="receipt-grid">
                        <?php foreach ($receipts as $receipt): ?>
                            <div class="receipt-card">
                                <img src="../receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>" 
                                     alt="Receipt #<?php echo $receipt['trans_id']; ?>" 
                                     class="receipt-image" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imageModal"
                                     data-src="../receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>"
                                     data-title="Receipt #<?php echo $receipt['trans_id']; ?>">
                                
                                <div class="receipt-info">
                                    <div class="receipt-id">Receipt #<?php echo $receipt['trans_id']; ?></div>
                                    <div class="receipt-time"><?php echo date('h:i A', strtotime($receipt['trans_time'])); ?></div>
                                </div>
                                
                                <div class="receipt-amount">RM <?php echo number_format($receipt['total_amount'], 2); ?></div>
                                <div class="receipt-items"><?php echo $receipt['item_count']; ?> item(s)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Receipt Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="modal-img">
                </div>
            </div>
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
        
        // Handle image modal
        const imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const imageSrc = button.getAttribute('data-src');
            const imageTitle = button.getAttribute('data-title');
            
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('imageModalTitle');
            
            modalImage.src = imageSrc;
            modalImage.alt = imageTitle;
            modalTitle.textContent = imageTitle;
        });
        
        // Handle image loading errors
        document.querySelectorAll('.receipt-image').forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2Yzc1N2QiPkltYWdlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4=';
                this.style.cursor = 'default';
                this.removeAttribute('data-bs-toggle');
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>