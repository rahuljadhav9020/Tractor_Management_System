<?php
require_once 'auth.php';
require_once 'config.php';

// Handle form submission for adding new vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_vehicle'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $registration = $_POST['registration'];

    $stmt = $conn->prepare("INSERT INTO vehicles (name, type, registration_number) VALUES (?, ?, ?)");
    $stmt->execute([$name, $type, $registration]);

    header('Location: vehicles.php');
    exit();
}

// Fetch all vehicles
$stmt = $conn->query("SELECT * FROM vehicles ORDER BY created_at DESC");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - Tractor Management System</title>
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
                        <h5 class="card-title">Add New Vehicle</h5>
                        <form method="POST" action="vehicles.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="name" class="form-label">Vehicle Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="type" class="form-label">Vehicle Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="tractor">Tractor</option>
                                        <option value="trolley">Trolley</option>
                                        <option value="tanker">Tanker</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="registration" class="form-label">Registration Number</label>
                                    <input type="text" class="form-control" id="registration" name="registration">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Vehicle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Vehicle List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Registration</th>
                                        <th>Added Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($vehicle['type'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($vehicle['created_at'])); ?></td>
                                        <td>
                                            <a href="trips.php?vehicle=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-route"></i></a>
                                            <a href="maintenance.php?vehicle=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-wrench"></i></a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>