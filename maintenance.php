<?php
require_once 'auth.php';
require_once 'config.php';

// Check if the quantity column exists in the maintenance table
try {
    $stmt = $conn->prepare("SELECT quantity FROM maintenance LIMIT 1");
    $stmt->execute();
} catch (PDOException $e) {
    // Column doesn't exist, create it
    $conn->exec("ALTER TABLE maintenance ADD COLUMN quantity FLOAT DEFAULT NULL");
}

// Handle form submission for adding new maintenance record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_maintenance'])) {
    // Initialize variables with default values
    $vehicle_id = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
    $maintenance_date = isset($_POST['maintenance_date']) ? $_POST['maintenance_date'] : null;
    $maintenance_type = isset($_POST['maintenance_type']) ? $_POST['maintenance_type'] : null;
    $cost = isset($_POST['cost']) ? $_POST['cost'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : null;

    // Validate required fields
    if ($vehicle_id && $maintenance_date && $maintenance_type && $cost !== null) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Check if this is a fuel type maintenance to include quantity
            if (strpos($maintenance_type, 'fuel') !== false) {
                $stmt = $conn->prepare("INSERT INTO maintenance (vehicle_id, maintenance_date, maintenance_type, cost, description, quantity) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$vehicle_id, $maintenance_date, $maintenance_type, $cost, $description, $quantity]);
            } else {
                $stmt = $conn->prepare("INSERT INTO maintenance (vehicle_id, maintenance_date, maintenance_type, cost, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$vehicle_id, $maintenance_date, $maintenance_type, $cost, $description]);
            }

            // Create payment record for the maintenance
            $maintenance_id = $conn->lastInsertId();
            $stmt = $conn->prepare("INSERT INTO payments (source_type, source_id, amount) VALUES ('maintenance', ?, ?)");
            $stmt->execute([$maintenance_id, $cost]);

            // Commit transaction
            $conn->commit();

            header('Location: maintenance.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "<div class='alert alert-danger'>Please fill in all required fields.</div>";
    }
}

// Handle delete operation
if (isset($_POST['delete_maintenance'])) {
    $maintenance_id = $_POST['maintenance_id'];
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete the associated payment record first
        $stmt = $conn->prepare("DELETE FROM payments WHERE source_type = 'maintenance' AND source_id = ?");
        $stmt->execute([$maintenance_id]);
        
        // Delete the maintenance record
        $stmt = $conn->prepare("DELETE FROM maintenance WHERE id = ?");
        $stmt->execute([$maintenance_id]);
        
        // Commit transaction
        $conn->commit();
        
        header('Location: maintenance.php');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Get filter parameter
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch all vehicles for the dropdown
$stmt = $conn->query("SELECT id, name, type FROM vehicles ORDER BY name");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique vehicle types for filter buttons
$stmt = $conn->query("SELECT DISTINCT type FROM vehicles ORDER BY type");
$vehicle_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch maintenance records with vehicle details and filtering
$query = "SELECT m.*, v.name as vehicle_name, v.type as vehicle_type 
          FROM maintenance m 
          JOIN vehicles v ON m.vehicle_id = v.id 
          WHERE 1=1";

// Add type filter
if ($type_filter !== 'all') {
    $query .= " AND v.type = :type";
}

// Add search if provided
if ($search) {
    $query .= " AND (v.name LIKE :search OR m.description LIKE :search)";
}

$query .= " ORDER BY m.maintenance_date DESC";
$stmt = $conn->prepare($query);

// Bind parameters
if ($type_filter !== 'all') {
    $stmt->bindParam(':type', $type_filter);
}

if ($search) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records - Tractor Management System</title>
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
                    <h5 class="card-title">Add Maintenance Record</h5>
                    <form method="POST" action="maintenance.php">
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
                                <label for="maintenance_date" class="form-label">Maintenance Date</label>
                                <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="maintenance_type" class="form-label">Maintenance Type</label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required onchange="toggleQuantityField()">
                                    <option value="diesel_fuel">Diesel</option>
                                    <option value="petrol_fuel">Petrol</option>
                                    <option value="oil_change">Oil Change</option>
                                    <option value="tire_service">Tire Service</option>
                                    <option value="engine_service">Engine Service</option>
                                    <option value="general_service">General Service</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3" id="quantity_container" style="display: none;">
                                <label for="quantity" class="form-label">Quantity (Liters)</label>
                                <input type="number" step="0.01" class="form-control" id="quantity" name="quantity">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cost" class="form-label">Cost</label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Maintenance History</h5>
                        <form class="d-flex" method="GET">
                            <input type="search" name="search" class="form-control me-2" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if ($search): ?>
                            <a href="maintenance.php?type=<?php echo htmlspecialchars($type_filter); ?>" class="btn btn-secondary ms-2">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="filter-buttons mb-3">
                        <a href="maintenance.php?type=all<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn filter-btn <?php echo $type_filter === 'all' ? 'active filter-btn-all' : 'filter-btn-all'; ?>">
                            All Vehicles
                        </a>
                        <?php foreach ($vehicle_types as $type): ?>
                        <a href="maintenance.php?type=<?php echo urlencode($type); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="btn filter-btn <?php echo $type_filter === $type ? 'active' : ''; ?>"
                           style="background-color: <?php echo $type_filter === $type ? 'var(--primary-dark)' : 'var(--light-bg)'; ?>; 
                                  color: <?php echo $type_filter === $type ? 'white' : 'var(--primary-color)'; ?>; 
                                  border-color: var(--primary-color);">
                            <i class="fas fa-<?php echo $type === 'tractor' ? 'tractor' : ($type === 'truck' ? 'truck' : 'car'); ?>"></i>
                            <?php echo ucfirst($type); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($search): ?>
                    <div class="alert alert-info">
                        Showing results for: "<?php echo htmlspecialchars($search); ?>"
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($type_filter !== 'all'): ?>
                    <div class="alert alert-primary">
                        Filtering by vehicle type: <?php echo ucfirst($type_filter); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($records) > 0): ?>
                                    <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($record['vehicle_type']); ?></span>
                                            <?php echo htmlspecialchars($record['vehicle_name']); ?>
                                        </td>
                                        <td><?php echo ucwords(str_replace('_', ' ', $record['maintenance_type'])); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($record['maintenance_date'])); ?></td>
                                        <td><?php echo $record['cost'] ? '₹' . number_format($record['cost'], 2) : '-'; ?></td>
                                        <td><small><?php echo htmlspecialchars($record['description']); ?></small></td>
                                        <td>
                                            <a href="edit_maintenance.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="showDeleteModal(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['maintenance_type']); ?>', '<?php echo htmlspecialchars($record['vehicle_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <?php if ($search || $type_filter !== 'all'): ?>
                                                No maintenance records found with the current filters.
                                            <?php else: ?>
                                                No maintenance records found.
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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this maintenance record?</p>
                <p class="text-muted" id="maintenanceDetails"></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="maintenance_id" id="deleteMaintenance_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_maintenance" class="btn btn-danger">Delete Record</button>
                </form>
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

function showDeleteModal(maintenanceId, maintenanceType, vehicleName) {
    document.getElementById('deleteMaintenance_id').value = maintenanceId;
    document.getElementById('maintenanceDetails').textContent = 'Maintenance Type: ' + maintenanceType + ' | Vehicle: ' + vehicleName;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
</body>
</html>