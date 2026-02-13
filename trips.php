<?php
require_once 'auth.php';
require_once 'config.php';

// Handle delete operation
if (isset($_POST['delete_trip'])) {
    $trip_id = $_POST['trip_id'];
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete the associated payment record first (due to foreign key)
        $stmt = $conn->prepare("DELETE FROM payments WHERE source_type = 'trip' AND source_id = ?");
        $stmt->execute([$trip_id]);
        
        // Delete the trip record
        $stmt = $conn->prepare("DELETE FROM trips WHERE id = ?");
        $stmt->execute([$trip_id]);
        
        // Commit transaction
        $conn->commit();
        
        header('Location: trips.php');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_trip'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $start_location = $_POST['start_location'];
    $end_location = $_POST['end_location'];
    $date = $_POST['date'];
    $trips = $_POST['trips'];
    $trip_cost = $_POST['trip_cost']; 
    $notes = $_POST['notes'];
    $customer_name = $_POST['customer_name'];

    $stmt = $conn->prepare("INSERT INTO trips (vehicle_id, start_location, end_location, date, trips, trip_cost, notes, customer_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$vehicle_id, $start_location, $end_location, $date, $trips, $trip_cost, $notes, $customer_name]);

    // Create payment record for the trip
    $stmt = $conn->prepare("INSERT INTO payments (source_type, source_id, amount) VALUES ('trip', ?, ?)");
    $stmt->execute([$conn->lastInsertId(), $trip_cost]);

    header('Location: trips.php');
    exit();
}

// Get search parameter
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch all vehicles for the dropdown
$stmt = $conn->query("SELECT id, name, type FROM vehicles ORDER BY name");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch trips with vehicle details - modified to include search
$query = "SELECT t.*, v.name as vehicle_name, v.type as vehicle_type 
          FROM trips t 
          JOIN vehicles v ON t.vehicle_id = v.id 
          WHERE t.customer_name LIKE ? 
          ORDER BY t.date DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['%' . $search . '%']);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Record New Trip</h5>
                        <form method="POST" action="trips.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Vehicle</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Choose vehicle...</option>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['id']; ?>">
                                            <?php echo htmlspecialchars($vehicle['name']) . ' (' . ucfirst($vehicle['type']) . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="start_location" class="form-label">Start Location</label>
                                    <input type="text" class="form-control" id="start_location" name="start_location" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_location" class="form-label">End Location</label>
                                    <input type="text" class="form-control" id="end_location" name="end_location" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                            
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="trips" class="form-label">Number of Trips</label>
                                    <input type="number" step="1" class="form-control" id="trips" name="trips">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="trip_cost" class="form-label">Trip Cost (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="trip_cost" name="trip_cost">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Record Trip</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Trip History</h5>
                            <form class="d-flex" method="GET">
                                <input type="search" name="search" class="form-control me-2" placeholder="Search customer..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search): ?>
                                <a href="trips.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <?php if ($search): ?>
                        <div class="alert alert-info">
                            Showing trips for customer: "<?php echo htmlspecialchars($search); ?>"
                        </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Customer</th>
                                        <th>Route</th>
                                        <th>Date</th>
                                        <th>Number of Trips</th>
                                        <th>Cost</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($trips) > 0): ?>
                                        <?php foreach ($trips as $trip): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?php echo ucfirst($trip['vehicle_type']); ?></span><br>
                                                <?php echo htmlspecialchars($trip['vehicle_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($trip['customer_name']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($trip['start_location']); ?> →
                                                    <?php echo htmlspecialchars($trip['end_location']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo date('d-m-Y', strtotime($trip['date'])); ?></td>
                                            <td><?php echo $trip['trips'] ? $trip['trips'] . ' trips' : '-'; ?></td>
                                            <td><?php echo $trip['trip_cost'] ? '₹' . $trip['trip_cost'] : '-'; ?></td>
                                            <td><small><?php echo htmlspecialchars($trip['notes']); ?></small></td>
                                            <td>
                                                <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="showDeleteConfirmation(<?php echo $trip['id']; ?>, '<?php echo htmlspecialchars($trip['start_location']); ?> → <?php echo htmlspecialchars($trip['end_location']); ?>', '<?php echo htmlspecialchars($trip['customer_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <?php if ($search): ?>
                                                    No trips found for customer "<?php echo htmlspecialchars($search); ?>".
                                                <?php else: ?>
                                                    No trips recorded yet.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this trip record?</p>
                    <div class="trip-details">
                        <p><strong>Customer Name:</strong> <span id="delete-customer-name"></span></p>
                        <p><strong>Trip Route:</strong> <span id="delete-trip-route"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="trip_id" id="delete_trip_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_trip" class="btn btn-danger">Delete Trip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showDeleteConfirmation(tripId, tripRoute, customerName) {
        document.getElementById('delete_trip_id').value = tripId;
        document.getElementById('delete-trip-route').textContent = tripRoute;
        document.getElementById('delete-customer-name').textContent = customerName;
        var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }
    </script>
</body>
</html>