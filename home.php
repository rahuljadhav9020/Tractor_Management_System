<?php
require_once 'auth.php';
require_once 'config.php';

// Get active users count for display
require_once 'active_users.php';
$active_users_count = countActiveUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* Subtle shadow */
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2); /* Enhanced shadow on hover */
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s;
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        .feature-card .btn {
            transition: transform 0.2s;
            border-radius: 5px; /* Rounded button corners */
        }
        .feature-card .btn:hover {
            transform: translateY(-2px);
            background-color: #0056b3; /* Darker blue on hover */
        }
        .revenue-box {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .revenue-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .revenue-item:last-child {
            border-bottom: none;
        }
        .revenue-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .active-users-link {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .active-users-count {
            background-color: #198754;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php 
    include 'navbar.php'; 
    
    // Get payment statistics with source details
    $stmt = $conn->query("SELECT 
        SUM(CASE 
            WHEN p.source_type = 'trip' THEN t.trip_cost
            WHEN p.source_type = 'rental' THEN r.total_amount
            ELSE p.amount 
        END) as total_revenue,
        SUM(COALESCE(p.paid_amount, CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END)) as collected_payment
        FROM payments p
        LEFT JOIN trips t ON p.source_type = 'trip' AND p.source_id = t.id
        LEFT JOIN rentals r ON p.source_type = 'rental' AND p.source_id = r.id
        WHERE p.source_type != 'maintenance'");
    $payment_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate pending payment as difference between total and collected
    $payment_stats['pending_payment'] = $payment_stats['total_revenue'] - $payment_stats['collected_payment'];
    
    // Get maintenance statistics with updated costs
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
    ?>
    
    <div class="container mt-5">
        <div class="text-center mb-4">
            <h1 class="display-4 mb-3">Welcome to राहुल शेती फार्म & वॉटर सप्लायर्स, कोरडेवाडी</h1>
            <p class="lead text-muted">Efficiently manage your agricultural vehicles and operations</p>
        </div>
    
        <!-- Revenue Summary Box -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-10">
                <div class="revenue-box">
                    <h4 class="text-center mb-3"><i class="fas fa-chart-line me-2"></i> Financial Overview</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="revenue-item text-center">
                                <div>Total Revenue</div>
                                <div class="revenue-value">₹<?php echo number_format($payment_stats['total_revenue'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="revenue-item text-center">
                                <div>Collected Payment</div>
                                <div class="revenue-value">₹<?php echo number_format($payment_stats['collected_payment'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="revenue-item text-center">
                                <div>Pending Payment</div>
                                <div class="revenue-value">₹<?php echo number_format($payment_stats['pending_payment'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="revenue-item text-center">
                                <div>Maintenance Cost</div>
                                <div class="revenue-value">₹<?php echo number_format($maintenance_stats['total_maintenance_cost'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="payments.php" class="btn btn-light me-2">View Payments</a><br><br>
                        <a href="maintenance.php" class="btn btn-light">View Maintenance</a>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10">
                            <i class="fas fa-tractor fa-2x text-primary"></i>
                        </div>
                        <h5 class="card-title">Manage Vehicles</h5>
                        <p class="card-text text-muted mb-4">Add and manage your tractors, trolleys, and tankers with ease.</p>
                        <a href="vehicles.php" class="btn btn-primary w-100">View Vehicles</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-opacity-10">
                            <i class="fas fa-route fa-2x text-success"></i>
                        </div>
                        <h5 class="card-title">Record Trips</h5>
                        <p class="card-text text-muted mb-4">Log and track all your vehicle trips efficiently.</p>
                        <a href="trips.php" class="btn btn-success w-100">View Trips</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-warning bg-opacity-10">
                            <i class="fas fa-wrench fa-2x text-warning"></i>
                        </div>
                        <h5 class="card-title">Maintenance</h5>
                        <p class="card-text text-muted mb-4">Keep track of vehicle maintenance and repairs.</p>
                        <a href="maintenance.php" class="btn btn-warning w-100">View Maintenance</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-opacity-10">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
                        </div>
                        <h5 class="card-title">Manage Rentals</h5>
                        <p class="card-text text-muted mb-4">Track tanker rentals and manage bookings.</p>
                        <a href="rentals.php" class="btn btn-info w-100">View Rentals</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Active Users Button -->
    <a href="active_users_list.php" class="btn btn-success position-fixed" style="bottom: 20px; right: 20px; border-radius: 50px; padding: 10px 20px; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
        <i class="fas fa-users me-2"></i> <?php echo $active_users_count; ?> Active
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>