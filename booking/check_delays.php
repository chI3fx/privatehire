<?php
include "../config/db.php";
include "../config/services.php";

$callCentre = '+254700000000';
$rows = $conn->query("
    SELECT b.*, u.username, u.email, u.phone
    FROM bookings b
    JOIN users u ON u.id=b.user_id
    WHERE b.status IN ('Booked', 'On Route')
      AND b.eta_minutes IS NOT NULL
      AND b.eta_minutes > 10
      AND b.delayed_notified = 0
");

$sent = 0;
if ($rows) {
    while ($b = $rows->fetch_assoc()) {
        $newEta = (new DateTime())->modify('+' . (int)$b['eta_minutes'] . ' minutes')->format('Y-m-d H:i');
        $msg = "PrivateHire delay update for booking #{$b['id']}: your cab is delayed. New ETA: {$newEta}. Need help? Call centre: {$callCentre}.";
        $via = 'sms';
        $ok = privatehire_send_sms((string)($b['phone'] ?? ''), $msg);
        if (!$ok) {
            $via = 'email';
            $ok = privatehire_send_email($b['email'], $b['username'], "Delay Notification - Booking #{$b['id']}", $msg);
        }
        if ($ok) {
            $bid = (int)$b['id'];
            $uid = (int)$b['user_id'];
            $safeMsg = $conn->real_escape_string($msg);
            $safeVia = $conn->real_escape_string($via);
            $conn->query("
                INSERT INTO delay_notifications (booking_id, user_id, message, eta_minutes, sent_via)
                VALUES ({$bid}, {$uid}, '{$safeMsg}', " . (int)$b['eta_minutes'] . ", '{$safeVia}')
            ");
            $conn->query("UPDATE bookings SET delayed_notified=1, actual_eta='{$newEta}:00' WHERE id={$bid}");
            $sent++;
        }
    }
}

echo "Delay notifications sent: {$sent}\n";

