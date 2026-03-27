<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$totalDrivers = $conn->query("SELECT COUNT(*) AS total FROM drivers")->fetch_assoc()['total'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
$totalEnquiries = $conn->query("SELECT COUNT(*) AS total FROM enquiries")->fetch_assoc()['total'];
$phoneBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE booking_channel='phone'")->fetch_assoc()['total'] ?? 0;
$totalReviews = $conn->query("SELECT COUNT(*) AS total FROM reviews")->fetch_assoc()['total'] ?? 0;
$loyalCustomers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE loyalty_tier='LOYAL'")->fetch_assoc()['total'] ?? 0;

if (isset($_GET['eta_booking']) && isset($_GET['eta_minutes'])) {
    $bid = (int)$_GET['eta_booking'];
    $eta = (int)$_GET['eta_minutes'];
    if ($bid > 0 && $eta >= 0) {
        $conn->query("UPDATE bookings SET eta_minutes={$eta}, delayed_notified=0 WHERE id={$bid}");
        privatehire_log_admin_activity($conn, (int)$_SESSION['user_id'], 'set_eta', 'booking', $bid, "ETA set to {$eta} mins.");
        header("Location: dashboard.php");
        exit();
    }
}

$recentBookings = $conn->query("
    SELECT bookings.*, users.username, vehicles.name AS vehicle_name, drivers.name AS driver_name
    FROM bookings
    LEFT JOIN users ON bookings.user_id = users.id
    LEFT JOIN vehicles ON bookings.vehicle_id = vehicles.id
    LEFT JOIN drivers ON bookings.driver_id = drivers.id
    ORDER BY bookings.id DESC
    LIMIT 5
");

$recentEnquiries = $conn->query("
    SELECT * FROM enquiries
    ORDER BY id DESC
    LIMIT 5
");
?>

<div class="container mt-5">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-bg-dark">
                <div class="card-body">
                    <h5 class="card-title">Bookings</h5>
                    <p class="card-text fs-3"><?php echo $totalBookings; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Drivers</h5>
                    <p class="card-text fs-3"><?php echo $totalDrivers; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-bg-success">
                <div class="card-body">
                    <h5 class="card-title">Vehicles</h5>
                    <p class="card-text fs-3"><?php echo $totalVehicles; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Enquiries</h5>
                    <p class="card-text fs-3"><?php echo $totalEnquiries; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <a class="btn btn-primary me-2" href="drivers.php">Manage Drivers</a>
        <a class="btn btn-success me-2" href="vehicles.php">Manage Vehicles</a>
        <a class="btn btn-dark me-2" href="call_bookings.php">Call Centre Bookings</a>
        <a class="btn btn-info me-2" href="reviews.php">Reviews</a>
        <a class="btn btn-secondary me-2" href="reports.php">Reports & Loyalty</a>
        <a class="btn btn-outline-primary me-2" href="enquiries.php">Enquiries</a>
        <a class="btn btn-outline-dark me-2" href="gps_dashboard.php">GPS Dashboard</a>
    </div>

    <div class="alert alert-secondary">
        <strong>Phone Bookings:</strong> <?php echo (int)$phoneBookings; ?> |
        <strong>Reviews:</strong> <?php echo (int)$totalReviews; ?> |
        <strong>Loyal Customers:</strong> <?php echo (int)$loyalCustomers; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Recent Bookings</strong>
        </div>
        <div class="card-body">
            <?php if ($recentBookings && $recentBookings->num_rows > 0) { ?>
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>Customer</th>
                        <th>Pickup</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>ETA (mins)</th>
                        <th>Actions</th>
                    </tr>

                    <?php while ($row = $recentBookings->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($row['pickup']); ?></td>
                            <td><?php echo htmlspecialchars($row['destination']); ?></td>
                            <td><?php echo htmlspecialchars($row['journey_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['journey_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle_name'] ?? 'Not set'); ?></td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($row['booking_channel'] ?? 'online'); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Cancelled') { ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php } elseif ($row['status'] === 'On Route') { ?>
                                    <span class="badge bg-primary">On Route</span>
                                <?php } else { ?>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span>
                                <?php } ?>
                            </td>
                            <td><?php echo (int)($row['eta_minutes'] ?? 0); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Booked') { ?>
                                    <a class="btn btn-sm btn-outline-primary" href="mark_on_route.php?id=<?php echo (int)$row['id']; ?>">
                                        Mark On Route
                                    </a>
                                    <a class="btn btn-sm btn-outline-warning" href="dashboard.php?eta_booking=<?php echo (int)$row['id']; ?>&eta_minutes=15">
                                        Report Delay 15m
                                    </a>
                                <?php } elseif ($row['status'] === 'On Route') { ?>
                                    <a class="btn btn-sm btn-outline-warning" href="dashboard.php?eta_booking=<?php echo (int)$row['id']; ?>&eta_minutes=15">
                                        Delay 15m
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">N/A</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <div class="alert alert-info mb-0">No bookings found.</div>
            <?php } ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Customer Contact Messages</strong>
        </div>
        <div class="card-body">
            <?php if ($recentEnquiries && $recentEnquiries->num_rows > 0) { ?>
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>

                    <?php while ($msg = $recentEnquiries->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td><?php echo htmlspecialchars($msg['message']); ?></td>
                            <td><?php echo htmlspecialchars($msg['status'] ?? 'open'); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <div class="alert alert-info mb-0">No contact messages found.</div>
            <?php } ?>
        </div>
    </div>
</div>
