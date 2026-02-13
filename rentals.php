<?php
require_once 'auth.php';
require_once 'config.php';

// Handle form submission for adding new rental
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_rental'])) {
    // Initialize variables with default values
    $vehicle_id = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $daily_rate = isset($_POST['daily_rate']) ? $_POST['daily_rate'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Validate required fields
    if ($vehicle_id && $customer_name && $start_date && $end_date && $daily_rate !== null) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Calculate total amount based on number of days and daily rate
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $days = $end->diff($start)->days + 1;
            $total_amount = $daily_rate * $days;

            // Insert rental record
            $stmt = $conn->prepare("INSERT INTO rentals (vehicle_id, customer_name, start_date, end_date, daily_rate, total_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehicle_id, $customer_name, $start_date, $end_date, $daily_rate, $total_amount, $notes]);

            // Create payment record for the rental
            $rental_id = $conn->lastInsertId();
            $stmt = $conn->prepare("INSERT INTO payments (source_type, source_id, amount) VALUES ('rental', ?, ?)");
            $stmt->execute([$rental_id, $total_amount]);

            // Commit transaction
            $conn->commit();

            header('Location: rentals.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Please fill in all required fields.</div>";
    }
}

// Handle delete operation
if (isset($_POST['delete_rental'])) {
    $rental_id = $_POST['rental_id'];
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete the associated payment record first
        $stmt = $conn->prepare("DELETE FROM payments WHERE source_type = 'rental' AND source_id = ?");
        $stmt->execute([$rental_id]);
        
        // Delete the rental record
        $stmt = $conn->prepare("DELETE FROM rentals WHERE id = ?");
        $stmt->execute([$rental_id]);
        
        // Commit transaction
        $conn->commit();
        
        header('Location: rentals.php');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch all tankers for the dropdown
$stmt = $conn->query("SELECT id, name, type FROM vehicles WHERE type = 'tanker' ORDER BY name");
$tankers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all rentals with vehicle details
$stmt = $conn->query("SELECT r.*, v.name as vehicle_name FROM rentals r JOIN vehicles v ON r.vehicle_id = v.id ORDER BY r.start_date DESC");
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rentals - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add New Rental</h5>
                        <form method="POST" action="rentals.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Tanker</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Choose tanker...</option>
                                        <?php foreach ($tankers as $tanker): ?>
                                        <option value="<?php echo $tanker['id']; ?>">
                                            <?php echo htmlspecialchars($tanker['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="daily_rate" class="form-label">Daily Rate (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="text" class="form-control datepicker" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="text" class="form-control datepicker" id="end_date" name="end_date" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Rental</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Rental List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                
                                <thead>
                                    <tr>
                                        <th>Tanker</th>
                                        <th>Customer</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Daily Rate</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['vehicle_name']); ?></td>
                                        <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($rental['start_date'])); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($rental['end_date'])); ?></td>
                                        <td>₹<?php echo number_format($rental['daily_rate'], 2); ?></td>
                                        <td>₹<?php echo number_format($rental['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $rental['status'] === 'active' ? 'success' : ($rental['status'] === 'completed' ? 'primary' : 'secondary'); ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($rental['notes']); ?></td>
                                        <td>
                                            <a href="edit_rental.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="showDeleteConfirmation(<?php echo $rental['id']; ?>, '<?php echo date('Y-m-d', strtotime($rental['start_date'])); ?>', '<?php echo htmlspecialchars($rental['customer_name']); ?>')">
                                                <i class="fas fa-trash"></i>
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
                    <p>Are you sure you want to delete this rental record?</p>
                    <div class="rental-details">
                        <p><strong>Customer Name:</strong> <span id="delete-customer-name"></span></p>
                        <p><strong>Rental Date:</strong> <span id="delete-rental-date"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="rental_id" id="delete_rental_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_rental" class="btn btn-danger">Delete Rental</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
            });
        });

        function showDeleteConfirmation(rentalId, rentalDate, customerName) {
            document.getElementById('delete_rental_id').value = rentalId;
            document.getElementById('delete-rental-date').textContent = rentalDate;
            document.getElementById('delete-customer-name').textContent = customerName;
            var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }
    </script>
</body>
</html>