<?php
include "config/db.php";
include "navbar.php";

$message = "";
$error = "";
$userId = (int)($_SESSION['user_id'] ?? 0);

if (isset($_POST['send'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $text = trim($_POST['message'] ?? '');
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($name === '' || $email === '' || $text === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safeEmail = $conn->real_escape_string($email);
        $safeText = $conn->real_escape_string($text);
        $uidSql = $userId > 0 ? $userId : "NULL";
        $bidSql = $bookingId > 0 ? $bookingId : "NULL";

        $ok = $conn->query("
            INSERT INTO enquiries(name, email, message, user_id, booking_id, status)
            VALUES ('{$safeName}', '{$safeEmail}', '{$safeText}', {$uidSql}, {$bidSql}, 'open')
        ");
        if ($ok) {
            $message = "Message sent successfully.";
        } else {
            $error = "Failed to send message.";
        }
    }
}

$bookings = null;
if ($userId > 0) {
    $bookings = $conn->query("SELECT id, pickup, destination, journey_date FROM bookings WHERE user_id={$userId} ORDER BY id DESC");
}
?>

<div class="container mt-5">
    <h2>Contact Us</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <form method="POST">
        <input class="form-control mb-3" name="name" placeholder="Full Name" required>
        <input class="form-control mb-3" name="email" placeholder="Email" required>

        <?php if ($bookings && $bookings->num_rows > 0) { ?>
            <label class="form-label">Related Booking (optional)</label>
            <select class="form-control mb-3" name="booking_id">
                <option value="">No specific booking</option>
                <?php while ($b = $bookings->fetch_assoc()) { ?>
                    <option value="<?php echo (int)$b['id']; ?>">
                        #<?php echo (int)$b['id']; ?> - <?php echo htmlspecialchars($b['pickup'] . ' → ' . $b['destination'] . ' (' . $b['journey_date'] . ')'); ?>
                    </option>
                <?php } ?>
            </select>
        <?php } ?>

        <textarea class="form-control mb-3" name="message" rows="5" placeholder="Your message..." required></textarea>
        <button class="btn btn-dark" name="send">Submit</button>
    </form>
</div>

