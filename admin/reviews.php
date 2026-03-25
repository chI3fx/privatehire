<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$reviews = $conn->query("
    SELECT r.*, u.username, b.pickup, b.destination, b.journey_date, d.name AS driver_name, v.name AS vehicle_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    LEFT JOIN bookings b ON b.id = r.booking_id
    LEFT JOIN drivers d ON d.id = b.driver_id
    LEFT JOIN vehicles v ON v.id = b.vehicle_id
    ORDER BY r.created_at DESC
");
?>

<div class="container mt-5">
    <h2>Customer Reviews</h2>
    <?php if ($reviews && $reviews->num_rows > 0) { ?>
        <table class="table table-bordered table-striped">
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Journey</th>
                <th>Journey Rating</th>
                <th>Vehicle Rating</th>
                <th>Driver Rating</th>
                <th>Comment</th>
            </tr>
            <?php while ($r = $reviews->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($r['username'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars(($r['pickup'] ?? '') . ' → ' . ($r['destination'] ?? '')); ?></td>
                    <td><?php echo (int)$r['journey_rating']; ?>/5</td>
                    <td><?php echo (int)$r['vehicle_rating']; ?>/5</td>
                    <td><?php echo (int)$r['driver_rating']; ?>/5</td>
                    <td><?php echo htmlspecialchars($r['review_text'] ?? ''); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <div class="alert alert-info">No reviews submitted yet.</div>
    <?php } ?>
</div>

