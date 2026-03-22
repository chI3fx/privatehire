<?php
include "../config/db.php";
include "../config/services.php";

date_default_timezone_set('Africa/Nairobi');

$windowStart = new DateTime('+10 minutes');
$windowEnd = new DateTime('+15 minutes');
$startDate = $windowStart->format('Y-m-d');
$endDate = $windowEnd->format('Y-m-d');
$startTime = $windowStart->format('H:i:s');
$endTime = $windowEnd->format('H:i:s');

$sql = "
    SELECT b.*, u.username, u.email, u.phone, d.name AS driver_name,
           v.registration_number, v.colour, v.make, v.model
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN drivers d ON b.driver_id = d.id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status IN ('Booked', 'On Route')
      AND b.reminder_sent = 0
      AND (
            (b.journey_date = '{$startDate}' AND b.journey_time >= '{$startTime}')
            OR
            (b.journey_date = '{$endDate}' AND b.journey_time <= '{$endTime}')
      )
";

$result = $conn->query($sql);
if (!$result) {
    echo "No reminders processed.\n";
    exit();
}

$sent = 0;
while ($booking = $result->fetch_assoc()) {
    $details = "Driver {$booking['driver_name']}; Reg {$booking['registration_number']}; Car "
        . trim(($booking['colour'] ?? '') . ' ' . ($booking['make'] ?? '') . ' ' . ($booking['model'] ?? ''));
    $smsBody = "PrivateHire reminder: pickup soon. {$details}";

    $ok = privatehire_send_sms((string)($booking['phone'] ?? ''), $smsBody);
    if (!$ok) {
        $subject = "Pickup Reminder - Booking #{$booking['id']}";
        $body = "Hello {$booking['username']},\n\nYour pickup is in about 10-15 minutes.\n{$details}\n\nRegards,\nPrivateHire Team";
        $ok = privatehire_send_email($booking['email'], $booking['username'], $subject, $body);
    }

    if ($ok) {
        $id = (int)$booking['id'];
        $conn->query("UPDATE bookings SET reminder_sent=1 WHERE id={$id}");
        $sent++;
    }
}

echo "Reminders sent: {$sent}\n";

