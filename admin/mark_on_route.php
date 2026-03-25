<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}
$adminId = (int)$_SESSION['user_id'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$sql = "
    SELECT b.*, u.username, u.email, u.phone,
           d.name AS driver_name,
           v.registration_number, v.colour, v.make, v.model
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN drivers d ON b.driver_id = d.id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id={$id}
    LIMIT 1
";
$res = $conn->query($sql);
if (!$res || $res->num_rows !== 1) {
    header("Location: dashboard.php");
    exit();
}

$booking = $res->fetch_assoc();
if ($booking['status'] === 'Cancelled') {
    header("Location: dashboard.php");
    exit();
}

$conn->query("UPDATE bookings SET status='On Route', eta_minutes=5, delayed_notified=0 WHERE id={$id}");
privatehire_log_admin_activity($conn, $adminId, 'mark_on_route', 'booking', $id, 'Booking marked on route with initial ETA 5 mins.');

$driverDetails = "Driver {$booking['driver_name']}; Reg {$booking['registration_number']}; "
    . "Car " . trim(($booking['colour'] ?? '') . ' ' . ($booking['make'] ?? '') . ' ' . ($booking['model'] ?? ''));
$sms = "PrivateHire: Your driver is on route. {$driverDetails}";

$smsSent = privatehire_send_sms((string)($booking['phone'] ?? ''), $sms);
if (!$smsSent) {
    $subject = "Driver On Route - Booking #{$id}";
    $body = "Hello {$booking['username']},\n\nYour driver is on route.\n{$driverDetails}\n\nRegards,\nPrivateHire Team";
    privatehire_send_email($booking['email'], $booking['username'], $subject, $body);
}

header("Location: dashboard.php");
exit();
