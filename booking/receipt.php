<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($id <= 0) {
    die("Invalid booking.");
}

$where = $isAdmin ? "bookings.id={$id}" : "bookings.id={$id} AND bookings.user_id={$userId}";
$sql = "
    SELECT bookings.*, drivers.name AS driver_name, drivers.phone AS driver_phone,
           vehicles.name AS vehicle_name, vehicles.registration_number, vehicles.colour, vehicles.make, vehicles.model,
           users.username, users.email
    FROM bookings
    LEFT JOIN drivers ON bookings.driver_id = drivers.id
    LEFT JOIN vehicles ON bookings.vehicle_id = vehicles.id
    LEFT JOIN users ON bookings.user_id = users.id
    WHERE {$where}
    LIMIT 1
";
$result = $conn->query($sql);
if (!$result || $result->num_rows !== 1) {
    die("Receipt not found.");
}
$row = $result->fetch_assoc();
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Booking Receipt</h2>
        <button class="btn btn-outline-secondary" onclick="window.print()">Print Receipt</button>
    </div>

    <div class="card p-4 mt-3">
        <h5 class="mb-3">Journey Details</h5>
        <p><b>Booking ID:</b> <?php echo (int)$row['id']; ?></p>
        <p><b>Customer:</b> <?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?>)</p>
        <p><b>Channel:</b> <?php echo htmlspecialchars($row['booking_channel'] ?? 'online'); ?></p>
        <p><b>Pickup:</b> <?php echo htmlspecialchars($row['pickup']); ?></p>
        <p><b>Destination:</b> <?php echo htmlspecialchars($row['destination']); ?></p>
        <p><b>Date:</b> <?php echo htmlspecialchars($row['journey_date']); ?></p>
        <p><b>Time:</b> <?php echo htmlspecialchars($row['journey_time']); ?></p>
        <p><b>Status:</b> <?php echo htmlspecialchars($row['status']); ?></p>
    </div>

    <div class="card p-4 mt-3">
        <h5 class="mb-3">Driver & Vehicle</h5>
        <p><b>Driver:</b> <?php echo htmlspecialchars($row['driver_name'] ?? 'Not assigned'); ?></p>
        <p><b>Driver Phone:</b> <?php echo htmlspecialchars($row['driver_phone'] ?? 'N/A'); ?></p>
        <p><b>Vehicle:</b> <?php echo htmlspecialchars($row['vehicle_name'] ?? 'N/A'); ?></p>
        <p><b>Registration:</b> <?php echo htmlspecialchars($row['registration_number'] ?? 'N/A'); ?></p>
        <p><b>Car Details:</b> <?php echo htmlspecialchars(trim(($row['colour'] ?? '') . ' ' . ($row['make'] ?? '') . ' ' . ($row['model'] ?? '')) ?: 'N/A'); ?></p>
    </div>

    <div class="card p-4 mt-3">
        <h5 class="mb-3">Payment</h5>
        <p><b>Total Cost:</b> <?php echo number_format((float)($row['total_cost'] ?? 0), 2); ?></p>
        <p><b>Discount %:</b> <?php echo number_format((float)($row['discount_percent'] ?? 0), 2); ?>%</p>
        <p><b>Discount Amount:</b> <?php echo number_format((float)($row['discount_amount'] ?? 0), 2); ?></p>
        <p><b>Offer Discount:</b> <?php echo number_format((float)($row['offer_discount_amount'] ?? 0), 2); ?></p>
        <p><b>Final Cost:</b> <?php echo number_format((float)($row['final_cost'] ?? 0), 2); ?></p>
        <p><b>Payment Method:</b> <?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></p>
        <p><b>Payment Status:</b> <?php echo htmlspecialchars($row['payment_status'] ?? 'N/A'); ?></p>
        <p><b>Payment Reference:</b> <?php echo htmlspecialchars($row['payment_reference'] ?? 'N/A'); ?></p>
        <p><b>Card:</b> <?php echo htmlspecialchars(privatehire_mask_card($row['card_brand'] ?? null, $row['card_last4'] ?? null)); ?></p>
    </div>
</div>
