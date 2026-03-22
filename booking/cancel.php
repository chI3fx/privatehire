<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$error = "";

if ($id <= 0) {
    die("Invalid booking.");
}

$bookingResult = $conn->query("
    SELECT b.*, u.username, u.email
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id={$id} AND b.user_id={$userId}
    LIMIT 1
");

if (!$bookingResult || $bookingResult->num_rows !== 1) {
    die("Booking not found.");
}

$booking = $bookingResult->fetch_assoc();
$journeyAt = DateTime::createFromFormat('Y-m-d H:i:s', $booking['journey_date'] . ' ' . $booking['journey_time']);
$now = new DateTime();
$hoursToJourney = $journeyAt ? (($journeyAt->getTimestamp() - $now->getTimestamp()) / 3600) : -1;
$canCancel = $booking['status'] !== 'Cancelled' && $hoursToJourney >= 24;

if (isset($_POST['confirm_cancel'])) {
    if (!$canCancel) {
        $error = "Cancellations are blocked within 24 hours of journey time.";
    } else {
        $newPaymentStatus = $booking['payment_status'] === 'paid' ? "refunded" : $booking['payment_status'];
        $safePaymentStatus = $conn->real_escape_string($newPaymentStatus);
        $update = $conn->query("
            UPDATE bookings
            SET status='Cancelled',
                cancelled_at=NOW(),
                payment_status='{$safePaymentStatus}'
            WHERE id={$id} AND user_id={$userId} AND status!='Cancelled'
        ");

        if ($update) {
            $subject = "Booking Cancellation Confirmation - #{$id}";
            $body = "Hello {$booking['username']},\n\n"
                . "Your booking #{$id} has been cancelled successfully.\n"
                . "Journey: {$booking['pickup']} to {$booking['destination']} on {$booking['journey_date']} {$booking['journey_time']}.\n"
                . "Refund policy: If cancellation is more than 24 hours before pickup, eligible paid amounts are refunded.\n"
                . "Current payment status: {$newPaymentStatus}\n\n"
                . "Regards,\nPrivateHire Team";

            privatehire_send_email($booking['email'], $booking['username'], $subject, $body);
            header("Location: my_bookings.php");
            exit();
        } else {
            $error = "Failed to cancel booking. Please try again.";
        }
    }
}
?>

<div class="container mt-5" style="max-width: 700px;">
    <h2>Cancel Booking #<?php echo (int)$booking['id']; ?></h2>

    <?php if ($error !== "") { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <div class="card p-4 mb-3">
        <p><strong>Pickup:</strong> <?php echo htmlspecialchars($booking['pickup']); ?></p>
        <p><strong>Destination:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
        <p><strong>Journey Date/Time:</strong> <?php echo htmlspecialchars($booking['journey_date'] . ' ' . $booking['journey_time']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($booking['status']); ?></p>
    </div>

    <div class="alert alert-info">
        <strong>Refund Policy:</strong> Cancellations are allowed only if made at least 24 hours before pickup.
        Paid bookings cancelled before the 24-hour window are marked as refunded.
    </div>

    <?php if ($canCancel) { ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>">
            <button class="btn btn-danger" name="confirm_cancel" onclick="return confirm('Cancel this booking?');">
                Confirm Cancellation
            </button>
            <a href="my_bookings.php" class="btn btn-secondary">Back</a>
        </form>
    <?php } else { ?>
        <div class="alert alert-warning mb-3">
            Cancellation not allowed. This booking is either already cancelled or within 24 hours of travel.
        </div>
        <a href="my_bookings.php" class="btn btn-secondary">Back</a>
    <?php } ?>
</div>

