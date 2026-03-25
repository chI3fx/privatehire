<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$error = "";
$message = "";

if ($bookingId <= 0) {
    die("Invalid booking.");
}

$bookingRes = $conn->query("
    SELECT bookings.*, vehicles.name AS vehicle_name, drivers.name AS driver_name
    FROM bookings
    LEFT JOIN vehicles ON vehicles.id = bookings.vehicle_id
    LEFT JOIN drivers ON drivers.id = bookings.driver_id
    WHERE bookings.id={$bookingId} AND bookings.user_id={$userId}
    LIMIT 1
");
if (!$bookingRes || $bookingRes->num_rows !== 1) {
    die("Booking not found.");
}
$booking = $bookingRes->fetch_assoc();

$journeyAt = DateTime::createFromFormat('Y-m-d H:i:s', $booking['journey_date'] . ' ' . $booking['journey_time']);
$reviewAllowed = $journeyAt && $journeyAt < new DateTime() && $booking['status'] !== 'Cancelled';
if (!$reviewAllowed) {
    die("Review is available only after the completed journey date.");
}

$existing = $conn->query("SELECT id FROM reviews WHERE booking_id={$bookingId} AND user_id={$userId} LIMIT 1");
if ($existing && $existing->num_rows > 0) {
    die("You have already reviewed this booking.");
}

if (isset($_POST['submit_review'])) {
    $journeyRating = (int)($_POST['journey_rating'] ?? 0);
    $vehicleRating = (int)($_POST['vehicle_rating'] ?? 0);
    $driverRating = (int)($_POST['driver_rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($journeyRating < 1 || $journeyRating > 5 || $vehicleRating < 1 || $vehicleRating > 5 || $driverRating < 1 || $driverRating > 5) {
        $error = "All star ratings must be between 1 and 5.";
    } else {
        $safeText = $reviewText !== '' ? "'" . $conn->real_escape_string($reviewText) . "'" : "NULL";
        $ok = $conn->query("
            INSERT INTO reviews (booking_id, user_id, journey_rating, vehicle_rating, driver_rating, review_text)
            VALUES ({$bookingId}, {$userId}, {$journeyRating}, {$vehicleRating}, {$driverRating}, {$safeText})
        ");
        if ($ok) {
            $message = "Thank you. Your review has been submitted.";
        } else {
            $error = "Unable to submit review. Please try again.";
        }
    }
}
?>

<div class="container mt-5" style="max-width: 700px;">
    <h2>Leave Review for Booking #<?php echo (int)$bookingId; ?></h2>
    <p><?php echo htmlspecialchars($booking['pickup'] . ' → ' . $booking['destination']); ?> | <?php echo htmlspecialchars($booking['journey_date'] . ' ' . $booking['journey_time']); ?></p>
    <p>Vehicle: <?php echo htmlspecialchars($booking['vehicle_name'] ?? 'N/A'); ?> | Driver: <?php echo htmlspecialchars($booking['driver_name'] ?? 'N/A'); ?></p>

    <?php if ($error !== '') { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <?php if ($message !== '') { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <a class="btn btn-primary" href="my_bookings.php">Back to Booking History</a>
    <?php } else { ?>
        <form method="POST">
            <input type="hidden" name="booking_id" value="<?php echo (int)$bookingId; ?>">

            <label class="form-label">Journey Rating (1-5)</label>
            <input class="form-control mb-3" type="number" name="journey_rating" min="1" max="5" required>

            <label class="form-label">Vehicle Rating (1-5)</label>
            <input class="form-control mb-3" type="number" name="vehicle_rating" min="1" max="5" required>

            <label class="form-label">Driver Rating (1-5)</label>
            <input class="form-control mb-3" type="number" name="driver_rating" min="1" max="5" required>

            <label class="form-label">Written Review (optional)</label>
            <textarea class="form-control mb-3" name="review_text" rows="4"></textarea>

            <button class="btn btn-success" name="submit_review">Submit Review</button>
            <a class="btn btn-secondary" href="my_bookings.php">Cancel</a>
        </form>
    <?php } ?>
</div>

