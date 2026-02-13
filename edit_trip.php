<?php
require_once 'auth.php';
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: trips.php');
    exit();
}

$trip_id = $_GET['id'];

// Handle form submission for updating trip
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $start_location = $_POST['start_location'];
    $end_location = $_POST['end_location'];
    $date = $_POST['date'];
    $trips = $_POST['trips'];
    $trip_cost = $_POST['trip_cost'];
    $notes = $_POST['notes'];
    $customer_name = $_POST['customer_name'];

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update trip record
        $stmt = $conn->prepare("UPDATE trips SET vehicle_id = ?, start_location = ?, end_location = ?, date = ?, trips = ?, trip_cost = ?, notes = ?, customer_name = ? WHERE id = ?");
        $stmt->execute([$vehicle_id, $start_location, $end_location, $date, $trips, $trip_cost, $notes, $customer_name, $trip_id]);

        // Update the corresponding payment record
        $stmt = $conn->prepare("UPDATE payments SET amount = ? WHERE source_type = 'trip' AND source_id = ?");
        $stmt->execute([$trip_cost, $trip_id]);

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

// Fetch trip details
$stmt = $conn->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    header('Location: trips.php');
    exit();
}

// Fetch all vehicles for the dropdown
$stmt = $conn->query("SELECT id, name, type FROM vehicles ORDER BY name");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Edit Trip</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Vehicle</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['id']; ?>" <?php echo ($vehicle['id'] == $trip['vehicle_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['name']) . ' (' . ucfirst($vehicle['type']) . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="start_location" class="form-label">Start Location</label>
                                    <input type="text" class="form-control" id="start_location" name="start_location" value="<?php echo htmlspecialchars($trip['start_location']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_location" class="form-label">End Location</label>
                                    <input type="text" class="form-control" id="end_location" name="end_location" value="<?php echo htmlspecialchars($trip['end_location']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d', strtotime($trip['date'])); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="trips" class="form-label">Number of Trips</label>
                                    <input type="number" step="0.01" class="form-control" id="trips" name="trips" value="<?php echo $trip['trips']; ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="trip_cost" class="form-label">Trip Cost (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="trip_cost" name="trip_cost" value="<?php echo $trip['trip_cost']; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($trip['customer_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($trip['notes']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Trip</button>
                            <a href="trips.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>