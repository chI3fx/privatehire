<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

$user = $_SESSION['user_id'];

$sql = "SELECT bookings.*, drivers.name AS driver_name
        FROM bookings
        LEFT JOIN drivers ON bookings.driver_id = drivers.id
        WHERE bookings.user_id='$user'
        ORDER BY bookings.id DESC";

$result = $conn->query($sql);
?>

<div class="container mt-5">
    <h2>My Bookings</h2>

    <?php if ($result && $result->num_rows > 0) { ?>
        <table class="table table-bordered table-striped">
            <tr>
                <th>Pickup</th>
                <th>Destination</th>
                <th>Date</th>
                <th>Time</th>
                <th>Driver</th>
                <th>Channel</th>
                <th>Final Cost</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Action</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['pickup']); ?></td>
                    <td><?php echo htmlspecialchars($row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['journey_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['journey_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Not assigned'); ?></td>
                    <td><?php echo htmlspecialchars($row['booking_channel'] ?? 'online'); ?></td>
                    <td><?php echo number_format((float)($row['final_cost'] ?? 0), 2); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_status'] ?? 'pending'); ?></td>
                    <td>
                        <?php if ($row['status'] === 'Cancelled') { ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php } else { ?>
                            <span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="receipt.php?id=<?php echo $row['id']; ?>">
                            View Receipt
                        </a>
                    </td>
                    <td>
                        <?php if ($row['status'] !== 'Cancelled') { ?>
                            <a class="btn btn-danger btn-sm" href="cancel.php?id=<?php echo $row['id']; ?>">
                                Cancel
                            </a>
                        <?php } else { ?>
                            <span class="text-muted">Already cancelled</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <div class="alert alert-info">You have no bookings yet.</div>
    <?php } ?>
</div>
