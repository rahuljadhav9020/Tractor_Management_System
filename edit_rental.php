<?php
require_once 'auth.php';
require_once 'config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: rentals.php');
    exit();
}

$id = $_GET['id'];

// Handle form submission for updating rental
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $customer_name = $_POST['customer_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $daily_rate = $_POST['daily_rate'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];

    // Calculate total amount based on number of days and daily rate
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    $total_amount = $daily_rate * $days;

    // Update rental record
    $stmt = $conn->prepare("UPDATE rentals SET vehicle_id = ?, customer_name = ?, start_date = ?, end_date = ?, daily_rate = ?, total_amount = ?, notes = ?, status = ? WHERE id = ?");
    $stmt->execute([$vehicle_id, $customer_name, $start_date, $end_date, $daily_rate, $total_amount, $notes, $status, $id]);

    // Update the related payment record
    $stmt = $conn->prepare("UPDATE payments SET amount = ? WHERE source_type = 'rental' AND source_id = ?");
    $stmt->execute([$total_amount, $id]);

    header('Location: rentals.php');
    exit();
}

// Fetch the rental record
$stmt = $conn->prepare("SELECT * FROM rentals WHERE id = ?");
$stmt->execute([$id]);
$rental = $stmt->fetch(PDO::FETCH_ASSOC);

// If record not found, redirect back
if (!$rental) {
    header('Location: rentals.php');
    exit();
}

// Fetch all tankers for the dropdown
$stmt = $conn->query("SELECT id, name, type FROM vehicles WHERE type = 'tanker' ORDER BY name");
$tankers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Rental - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Edit Rental</h5>
                    <form method="POST" action="edit_rental.php?id=<?php echo $id; ?>">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="vehicle_id" class="form-label">Select Tanker</label>
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <?php foreach ($tankers as $tanker): ?>
                                    <option value="<?php echo $tanker['id']; ?>" <?php echo ($tanker['id'] == $rental['vehicle_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tanker['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customer_name" class="form-label">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($rental['customer_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="daily_rate" class="form-label">Daily Rate (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate" value="<?php echo $rental['daily_rate']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="text" class="form-control datepicker" id="start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime($rental['start_date'])); ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="text" class="form-control datepicker" id="end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime($rental['end_date'])); ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo ($rental['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo ($rental['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($rental['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="1"><?php echo htmlspecialchars($rental['notes']); ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary">Update Rental</button>
                            <a href="rentals.php" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
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
</script>
</body>
</html>