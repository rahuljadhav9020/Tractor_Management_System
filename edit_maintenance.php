<?php
require_once 'config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: maintenance.php');
    exit();
}

$id = $_GET['id'];

// Handle form submission for updating maintenance record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $maintenance_date = $_POST['maintenance_date'];
    $maintenance_type = $_POST['maintenance_type'];
    $cost = $_POST['cost'];
    $description = $_POST['description'];
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : null;

    // First, check if the quantity column exists in the maintenance table
    try {
        $stmt = $conn->prepare("SELECT quantity FROM maintenance LIMIT 1");
        $stmt->execute();
        $quantityColumnExists = true;
    } catch (PDOException $e) {
        // Column doesn't exist, create it
        $quantityColumnExists = false;
        $conn->exec("ALTER TABLE maintenance ADD COLUMN quantity FLOAT DEFAULT NULL");
    }

    // Now update the record
    if (strpos($maintenance_type, 'fuel') !== false) {
        $stmt = $conn->prepare("UPDATE maintenance SET vehicle_id = ?, maintenance_date = ?, maintenance_type = ?, cost = ?, description = ?, quantity = ? WHERE id = ?");
        $stmt->execute([$vehicle_id, $maintenance_date, $maintenance_type, $cost, $description, $quantity, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE maintenance SET vehicle_id = ?, maintenance_date = ?, maintenance_type = ?, cost = ?, description = ?, quantity = NULL WHERE id = ?");
        $stmt->execute([$vehicle_id, $maintenance_date, $maintenance_type, $cost, $description, $id]);
    }

    // Update the related payment record
    $stmt = $conn->prepare("UPDATE payments SET amount = ? WHERE source_type = 'maintenance' AND source_id = ?");
    $stmt->execute([$cost, $id]);

    header('Location: maintenance.php');
    exit();
}

// Fetch the maintenance record
$stmt = $conn->prepare("SELECT m.*, v.name as vehicle_name, v.type as vehicle_type 
                        FROM maintenance m 
                        JOIN vehicles v ON m.vehicle_id = v.id 
                        WHERE m.id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// If record not found, redirect back
if (!$record) {
    header('Location: maintenance.php');
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
    <title>Edit Maintenance Record - Tractor Management System</title>
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
                    <h5 class="card-title">Edit Maintenance Record</h5>
                    <form method="POST" action="edit_maintenance.php?id=<?php echo $id; ?>">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="vehicle_id" class="form-label">Select Vehicle</label>
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['id']; ?>" <?php echo ($vehicle['id'] == $record['vehicle_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vehicle['name']) . ' (' . ucfirst($vehicle['type']) . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="maintenance_date" class="form-label">Maintenance Date</label>
                                <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" 
                                       value="<?php echo date('Y-m-d', strtotime($record['maintenance_date'])); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="maintenance_type" class="form-label">Maintenance Type</label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required onchange="toggleQuantityField()">
                                    <option value="diesel_fuel" <?php echo ($record['maintenance_type'] == 'diesel_fuel') ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="petrol_fuel" <?php echo ($record['maintenance_type'] == 'petrol_fuel') ? 'selected' : ''; ?>>Petrol</option>
                                    <option value="oil_change" <?php echo ($record['maintenance_type'] == 'oil_change') ? 'selected' : ''; ?>>Oil Change</option>
                                    <option value="tire_service" <?php echo ($record['maintenance_type'] == 'tire_service') ? 'selected' : ''; ?>>Tire Service</option>
                                    <option value="engine_service" <?php echo ($record['maintenance_type'] == 'engine_service') ? 'selected' : ''; ?>>Engine Service</option>
                                    <option value="general_service" <?php echo ($record['maintenance_type'] == 'general_service') ? 'selected' : ''; ?>>General Service</option>
                                    <option value="repair" <?php echo ($record['maintenance_type'] == 'repair') ? 'selected' : ''; ?>>Repair</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3" id="quantity_container" style="display: <?php echo (strpos($record['maintenance_type'], 'fuel') !== false) ? 'block' : 'none'; ?>">
                                <label for="quantity" class="form-label">Quantity (Liters)</label>
                                <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" 
                                       value="<?php echo isset($record['quantity']) ? $record['quantity'] : ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cost" class="form-label">Cost</label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                                       value="<?php echo $record['cost']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($record['description']); ?></textarea>
                        </div>
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary">Update Record</button>
                            <a href="maintenance.php" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleQuantityField() {
    const maintenanceType = document.getElementById('maintenance_type').value;
    const quantityContainer = document.getElementById('quantity_container');
    
    if (maintenanceType === 'diesel_fuel' || maintenanceType === 'petrol_fuel') {
        quantityContainer.style.display = 'block';
    } else {
        quantityContainer.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleQuantityField();
});
</script>
</body>
</html>