<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Make sure active_users_count is available
if (!isset($active_users_count) && file_exists('active_users.php')) {
    require_once 'active_users.php';
    $active_users_count = countActiveUsers();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <i class="fas fa-tractor fa-2x me-2"></i>
            <b style="text-align:center;">राहुल शेती फार्म & <br>वॉटर सप्लायर्स, कोरडेवाडी</b>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'home.php' ? 'active bg-primary-dark' : ''; ?>" href="home.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'vehicles.php' ? 'active bg-primary-dark' : ''; ?>" href="vehicles.php">
                    <i class="fas fa-tractor me-2"></i>Vehicles
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'trips.php' ? 'active bg-primary-dark' : ''; ?>" href="trips.php">
                        <i class="fas fa-route me-2"></i>Trips
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'maintenance.php' ? 'active bg-primary-dark' : ''; ?>" href="maintenance.php">
                        <i class="fas fa-wrench me-2"></i>Maintenance
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'rentals.php' ? 'active bg-primary-dark' : ''; ?>" href="rentals.php">
                        <i class="fas fa-calendar-alt me-2"></i>Rentals
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'payments.php' ? 'active bg-primary-dark' : ''; ?>" href="payments.php">
                        <i class="fas fa-rupee-sign me-2"></i>Payments
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 <?php echo $current_page === 'active_users_list.php' ? 'active bg-primary-dark' : ''; ?>" href="active_users_list.php">
                        <i class="fas fa-users me-2"></i>
                        <?php if (isset($active_users_count)): ?>
                            <span class="badge bg-success"><?php echo $active_users_count; ?></span>
                        <?php endif; ?>
                        Users
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link rounded px-3 text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>