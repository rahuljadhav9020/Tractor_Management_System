<?php
require_once 'auth.php';
require_once 'config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $payment_id = $_POST['payment_id'];
    $paid_date = $_POST['paid_date'];
    $new_paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    
    // First, get the current paid amount if any
    $stmt = $conn->prepare("SELECT paid_amount FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_paid = isset($current['paid_amount']) ? floatval($current['paid_amount']) : 0;
    
    // Calculate the total paid amount (existing + new payment)
    $total_paid_amount = $current_paid + $new_paid_amount;
    
    // Calculate remaining amount
    $remaining_amount = $total_amount - $total_paid_amount;
    
    // Determine status based on payment amount
    if ($remaining_amount <= 0) {
        $status = 'paid';
        $remaining_amount = 0; // Set to 0 instead of null for consistency
        // Ensure we don't overpay
        $total_paid_amount = $total_amount;
    } else {
        $status = 'unpaid';
    }
    
    // Update payment record
    $stmt = $conn->prepare("UPDATE payments SET status = ?, paid_date = ?, paid_amount = ?, remaining_amount = ? WHERE id = ?");
    $stmt->execute([$status, $paid_date, $total_paid_amount, $remaining_amount, $payment_id]);
    
    header('Location: payments.php');
    exit();
}

// Check if the payments table has the necessary columns
try {
    $stmt = $conn->prepare("SELECT paid_amount, remaining_amount FROM payments LIMIT 1");
    $stmt->execute();
} catch (PDOException $e) {
    // Columns don't exist, create them
    $conn->exec("ALTER TABLE payments ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT NULL");
    $conn->exec("ALTER TABLE payments ADD COLUMN remaining_amount DECIMAL(10,2) DEFAULT NULL");
}

// Get maintenance statistics
$stmt = $conn->query("SELECT 
    SUM(CASE 
        WHEN p.source_type = 'maintenance' THEN m.cost
        ELSE p.amount 
    END) as total_maintenance_cost,
    SUM(COALESCE(p.paid_amount, CASE WHEN p.status = 'paid' THEN m.cost ELSE 0 END)) as paid_maintenance_cost
    FROM payments p
    LEFT JOIN maintenance m ON p.source_type = 'maintenance' AND p.source_id = m.id
    WHERE p.source_type = 'maintenance'");
$maintenance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch grouped payments summary with status filtering - excluding maintenance
$query = "
    SELECT 
        CASE 
            WHEN p.source_type = 'trip' THEN t.customer_name
            WHEN p.source_type = 'rental' THEN r.customer_name
        END as customer_name,
        COUNT(*) as total_transactions,
        SUM(p.amount) as total_amount,
        SUM(COALESCE(p.paid_amount, CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END)) as paid_amount,
        SUM(p.amount) - SUM(COALESCE(p.paid_amount, CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END)) as pending_amount
    FROM payments p
    LEFT JOIN trips t ON p.source_type = 'trip' AND p.source_id = t.id
    LEFT JOIN rentals r ON p.source_type = 'rental' AND p.source_id = r.id
    WHERE p.source_type != 'maintenance'
    AND CASE 
        WHEN p.source_type = 'trip' THEN t.customer_name
        WHEN p.source_type = 'rental' THEN r.customer_name
    END LIKE ?";

// Add status filter to the summary query
if ($status_filter !== 'all') {
    if ($status_filter === 'pending') {
        $query .= " AND (p.status = 'unpaid' OR (p.amount > COALESCE(p.paid_amount, 0)))";
    } else if ($status_filter === 'paid') {
        $query .= " AND p.status = 'paid'";
    }
} 

// Rest of the query remains the same
$query .= " GROUP BY customer_name
    ORDER BY " . ($status_filter === 'pending' ? "pending_amount" : "total_amount") . " DESC";

$stmt = $conn->prepare($query);
$stmt->execute(['%' . $search . '%']);
$grouped_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch detailed payments with status filtering - excluding maintenance
$query = "
    SELECT p.*, 
           CASE 
               WHEN p.source_type = 'trip' THEN t.customer_name
               WHEN p.source_type = 'rental' THEN r.customer_name
           END as customer_name,
           CASE
               WHEN p.source_type = 'trip' THEN 'Trip Payment'
               WHEN p.source_type = 'rental' THEN 'Rental Payment'
           END as payment_type,
           CASE
               WHEN p.source_type = 'trip' THEN v_trip.type
               WHEN p.source_type = 'rental' THEN v_rental.type
           END as vehicle_type,
           CASE
               WHEN p.source_type = 'trip' THEN CONCAT(t.start_location, ' → ', t.end_location)
               ELSE NULL
           END as route,
           CASE
               WHEN p.source_type = 'trip' THEN t.notes
               WHEN p.source_type = 'rental' THEN r.notes
               ELSE NULL
           END as notes,
           CASE
               WHEN p.source_type = 'trip' THEN t.date
               WHEN p.source_type = 'rental' THEN r.end_date 
           END as source_date
    FROM payments p
    LEFT JOIN trips t ON p.source_type = 'trip' AND p.source_id = t.id
    LEFT JOIN rentals r ON p.source_type = 'rental' AND p.source_id = r.id
    LEFT JOIN vehicles v_trip ON t.vehicle_id = v_trip.id
    LEFT JOIN vehicles v_rental ON r.vehicle_id = v_rental.id
    WHERE p.source_type != 'maintenance'
    AND CASE 
        WHEN p.source_type = 'trip' THEN t.customer_name
        WHEN p.source_type = 'rental' THEN r.customer_name
    END LIKE ?";

// Add status filter to the query
if ($status_filter !== 'all') {
    if ($status_filter === 'pending') {
        $query .= " AND (p.status = 'unpaid' OR (p.amount > COALESCE(p.paid_amount, 0)))";
    } else if ($status_filter === 'paid') {
        $query .= " AND p.status = 'paid'";
    }
}

$query .= " ORDER BY source_date DESC, p.created_at DESC";

$stmt = $conn->prepare($query);
$params = ['%' . $search . '%'];
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Payment Summary Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Payment Summary by Customer</h5>
                            <form class="d-flex" method="GET">
                                <input type="search" name="search" class="form-control me-2" placeholder="Search customer..." value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search): ?>
                                <a href="payments.php?status=<?php echo htmlspecialchars($status_filter); ?>" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <div class="filter-buttons mb-3">
                            <a href="payments.php?status=all<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn filter-btn filter-btn-all <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                                All Payments
                            </a>
                            <a href="payments.php?status=paid<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn filter-btn filter-btn-paid <?php echo $status_filter === 'paid' ? 'active' : ''; ?>">
                                <i class="fas fa-check-circle"></i> Paid
                            </a>
                            <a href="payments.php?status=pending<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn filter-btn filter-btn-pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                <i class="fas fa-clock"></i> Pending
                            </a>
                        </div>
                        
                        <?php if ($search): ?>
                        <div class="alert alert-info">
                            Showing results for: "<?php echo htmlspecialchars($search); ?>"
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($status_filter !== 'all'): ?>
                        <div class="alert alert-<?php echo $status_filter === 'paid' ? 'success' : 'warning'; ?>">
                            Showing <?php echo ucfirst($status_filter); ?> payments only
                        </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer/Vehicle</th>
                                        <th>Total Transactions</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Pending Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped_payments as $group): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($group['customer_name']); ?></td>
                                        <td><?php echo $group['total_transactions']; ?></td>
                                        <td>₹<?php echo number_format($group['total_amount'], 2); ?></td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($group['paid_amount'], 2); ?></td>
                                        <td class="text-danger fw-bold">₹<?php echo number_format($group['pending_amount'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="filterPayments('<?php echo htmlspecialchars($group['customer_name']); ?>')">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Details Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Records</h5>
                        <div id="filter-info" class="alert alert-info d-none mb-3">
                            Showing payments for: <span id="filter-customer"></span>
                            <button class="btn btn-sm btn-secondary ms-2" onclick="resetFilter()">Show All</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="payment-details">
                                <thead>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Customer</th>
                                        <th>Vehicle Type</th>
                                        <th>Route & Notes</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Remaining</th>
                                        <th>Status</th>
                                        <th>Paid Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                
                                
                                <tbody>
                                    <?php 
                                    $serial_number = 1;
                                    foreach ($payments as $payment): 
                                    ?>
                                    <tr>
                                        <td><?php echo $serial_number++; ?></td>
                                        <td><?php echo $payment['source_date'] ? date('d-m-Y', strtotime($payment['source_date'])) : '-'; ?></td>
                                        <td><?php echo $payment['payment_type']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($payment['vehicle_type'] ?: 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['source_type'] == 'trip' && !empty($payment['route'])): ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($payment['route']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted d-block">-</small>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($payment['notes'])): ?>
                                                <small class="text-dark d-block mt-1"><strong>Notes:</strong> <?php echo htmlspecialchars($payment['notes']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($payment['paid_amount'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php 
                                            // Calculate remaining amount
                                            $remaining = $payment['amount'] - ($payment['paid_amount'] ?? 0);
                                            
                                            // Update status if paid amount equals total amount
                                            if ($remaining <= 0 && $payment['status'] !== 'paid') {
                                                // Update status to paid in database
                                                $update_stmt = $conn->prepare("UPDATE payments SET status = 'paid', remaining_amount = 0 WHERE id = ?");
                                                $update_stmt->execute([$payment['id']]);
                                                $payment['status'] = 'paid';
                                                $payment['remaining_amount'] = 0;
                                            }
                                            
                                            // Show remaining amount
                                            if ($payment['status'] !== 'paid'): 
                                            ?>
                                            <span class="text-danger fw-bold">₹<?php echo number_format($remaining, 2); ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'bg-warning';
                                            $statusText = 'Unpaid';
                                            
                                            if ($payment['status'] === 'paid') {
                                                $badgeClass = 'bg-success';
                                                $statusText = 'Paid';
                                            } elseif (isset($payment['paid_amount']) && $payment['paid_amount'] > 0) {
                                                $badgeClass = 'bg-warning';
                                                $statusText = 'Unpaid';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $payment['paid_date'] ? date('d-m-Y', strtotime($payment['paid_date'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($payment['status'] !== 'paid'): ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="showPaymentModal(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>, <?php echo $payment['paid_amount'] ?? 0; ?>, <?php echo $payment['remaining_amount'] ?? $payment['amount']; ?>, '<?php echo $payment['source_date'] ? date('Y-m-d', strtotime($payment['source_date'])) : date('Y-m-d'); ?>')">
                                                <i class="fas fa-check"></i> Mark as Paid
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <input type="hidden" name="total_amount" id="total_amount">
                        
                        <div class="mb-3">
                            <label for="total_display" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="total_display" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="existing_payment_div">
                            <label for="existing_payment" class="form-label">Previously Paid</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control bg-light" id="existing_payment" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="paid_amount" class="form-label">Amount Being Paid Now</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" required>
                            </div>
                            <div class="form-text" id="remaining_text"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="paid_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="paid_date" name="paid_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_payment" class="btn btn-success">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showPaymentModal(paymentId, totalAmount, paidAmount, remainingAmount, tripDate = null) {
        document.getElementById('payment_id').value = paymentId;
        document.getElementById('total_amount').value = totalAmount;
        document.getElementById('total_display').value = totalAmount.toFixed(2);
        
        // Set existing payment amount
        const existingPaymentDiv = document.getElementById('existing_payment_div');
        const existingPayment = document.getElementById('existing_payment');
        
        // If there's an existing payment, show it
        if (paidAmount > 0) {
            existingPayment.value = paidAmount.toFixed(2);
            existingPaymentDiv.style.display = 'block';
        } else {
            // Otherwise hide this section
            existingPaymentDiv.style.display = 'none';
        }
        
        // Set default paid amount to the full remaining amount
        const paidAmountInput = document.getElementById('paid_amount');
        // Calculate the remaining amount
        const remaining = totalAmount - paidAmount;
        paidAmountInput.value = remaining.toFixed(2);
        
        if (paidAmountInput.hasAttribute('max')) {
            paidAmountInput.removeAttribute('max');
        }
        
        // Update remaining text
        updateRemainingText(paidAmountInput.value, totalAmount, paidAmount);
        
        // Add event listener to update remaining text when paid amount changes
        paidAmountInput.addEventListener('input', function() {
            updateRemainingText(this.value, totalAmount, paidAmount);
        });

        // Set the payment date
        const paidDateInput = document.getElementById('paid_date');
        if (tripDate) {
            // If trip date is provided, use it as the default payment date
            paidDateInput.value = tripDate;
            // Set max date to today
            paidDateInput.max = new Date().toISOString().split('T')[0];
        } else {
            // Otherwise use today's date
            paidDateInput.value = new Date().toISOString().split('T')[0];
            paidDateInput.max = paidDateInput.value;
        }
        
        var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }
    
    function updateRemainingText(newPaymentAmount, totalAmount, existingPaidAmount) {
        const newPaid = parseFloat(newPaymentAmount) || 0;
        const existing = parseFloat(existingPaidAmount) || 0;
        const total = parseFloat(totalAmount) || 0;
        
        // Calculate what will remain after this payment
        const totalPaid = existing + newPaid;
        const remaining = total - totalPaid;
        
        const remainingText = document.getElementById('remaining_text');
        
        if (remaining <= 0) {
            remainingText.innerHTML = '<span class="text-success">Full payment</span>';
        } else {
            remainingText.innerHTML = '<span class="text-warning">Will remain: ₹' + remaining.toFixed(2) + '</span>';
        }
    }
    
    function filterPayments(customerName) {
        const rows = document.querySelectorAll('#payment-details tbody tr');
        const filterInfo = document.getElementById('filter-info');
        const filterCustomer = document.getElementById('filter-customer');
        
        filterInfo.classList.remove('d-none');
        filterCustomer.textContent = customerName;
        
        rows.forEach(row => {
            const customerCell = row.querySelector('td:nth-child(4)'); // Customer is in the 4th column
            if (customerCell && customerCell.textContent.trim() === customerName) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Scroll to the payment details section
        document.getElementById('payment-details').scrollIntoView({behavior: 'smooth'});
    }
    
    function resetFilter() {
        const rows = document.querySelectorAll('#payment-details tbody tr');
        const filterInfo = document.getElementById('filter-info');
        
        filterInfo.classList.add('d-none');
        rows.forEach(row => row.style.display = '');
    }
    </script>
</body>
</html>