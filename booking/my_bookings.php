<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

$userId = (int)$_SESSION['user_id'];
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = ["bookings.user_id={$userId}"];
if ($dateFrom !== '') {
    $safeFrom = $conn->real_escape_string($dateFrom);
    $where[] = "bookings.journey_date >= '{$safeFrom}'";
}
if ($dateTo !== '') {
    $safeTo = $conn->real_escape_string($dateTo);
    $where[] = "bookings.journey_date <= '{$safeTo}'";
}
$whereSql = implode(' AND ', $where);

$sql = "
    SELECT bookings.*, drivers.name AS driver_name, vehicles.name AS vehicle_name,
           reviews.id AS review_id, reviews.journey_rating
    FROM bookings
    LEFT JOIN drivers ON bookings.driver_id = drivers.id
    LEFT JOIN vehicles ON bookings.vehicle_id = vehicles.id
    LEFT JOIN reviews ON reviews.booking_id = bookings.id AND reviews.user_id = bookings.user_id
    WHERE {$whereSql}
    ORDER BY bookings.journey_date DESC, bookings.journey_time DESC
";
$result = $conn->query($sql);
?>

<div class="container mt-5">
    <h2>Booking History</h2>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input class="form-control" type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From date">
        </div>
        <div class="col-md-4">
            <input class="form-control" type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To date">
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-primary">Filter</button>
            <a class="btn btn-outline-secondary" href="my_bookings.php">Reset</a>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0) { ?>
        <table class="table table-bordered table-striped">
            <tr>
                <th>Date</th>
                <th>Journey</th>
                <th>Service Type</th>
                <th>Cost</th>
                <th>Driver</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <?php
                $journeyDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $row['journey_date'] . ' ' . $row['journey_time']);
                $reviewAllowed = $journeyDateTime && $journeyDateTime < new DateTime() && $row['status'] !== 'Cancelled';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['journey_date'] . ' ' . $row['journey_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['pickup'] . ' → ' . $row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['service_type'] ?? ($row['vehicle_name'] ?? 'Standard Ride')); ?></td>
                    <td><?php echo number_format((float)($row['final_cost'] ?? 0), 2); ?></td>
                    <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Not assigned'); ?></td>
                    <td>
                        <?php if (!empty($row['review_id'])) { ?>
                            <?php echo str_repeat('★', (int)$row['journey_rating']); ?> (<?php echo (int)$row['journey_rating']; ?>/5)
                        <?php } else { ?>
                            <span class="text-muted">Not rated</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Cancelled') { ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php } else { ?>
                            <span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="receipt.php?id=<?php echo (int)$row['id']; ?>">Receipt</a>
                    </td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm mb-1" href="book.php?rebook_id=<?php echo (int)$row['id']; ?>">Re-book</a>
                        <?php if ($row['status'] !== 'Cancelled') { ?>
                            <a class="btn btn-danger btn-sm mb-1" href="cancel.php?id=<?php echo (int)$row['id']; ?>">Cancel</a>
                        <?php } ?>
                        <?php if ($reviewAllowed && empty($row['review_id'])) { ?>
                            <a class="btn btn-warning btn-sm mb-1" href="review.php?booking_id=<?php echo (int)$row['id']; ?>">Leave Review</a>
                        <?php } elseif (!$reviewAllowed && empty($row['review_id'])) { ?>
                            <span class="text-muted d-block">Review available after trip</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <div class="alert alert-info">No booking history found.</div>
    <?php } ?>
</div>

