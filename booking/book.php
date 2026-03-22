<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

$error = "";
$bookingPreview = null;

$form = [
    'pickup' => trim($_POST['pickup'] ?? ''),
    'destination' => trim($_POST['destination'] ?? ''),
    'date' => $_POST['date'] ?? '',
    'time' => $_POST['time'] ?? '',
    'passengers' => (int)($_POST['passengers'] ?? 0),
    'vehicle' => (int)($_POST['vehicle'] ?? 0),
    'notification_preference' => $_POST['notification_preference'] ?? 'sms'
];

function build_booking_datetime(string $date, string $time): ?DateTime
{
    if ($date === '' || $time === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    return $dt ?: null;
}

function validate_booking_input(array $form): array
{
    if (
        $form['pickup'] === '' ||
        $form['destination'] === '' ||
        $form['date'] === '' ||
        $form['time'] === '' ||
        $form['passengers'] < 1 ||
        $form['vehicle'] < 1
    ) {
        return ['valid' => false, 'message' => 'Please fill in all booking fields correctly.'];
    }

    $journeyAt = build_booking_datetime($form['date'], $form['time']);
    if (!$journeyAt) {
        return ['valid' => false, 'message' => 'Invalid journey date or time.'];
    }

    $now = new DateTime();
    if ($journeyAt <= $now) {
        return ['valid' => false, 'message' => 'Journey date and time must be in the future.'];
    }

    if (!in_array($form['notification_preference'], ['sms', 'email', 'both'], true)) {
        return ['valid' => false, 'message' => 'Invalid notification preference selected.'];
    }

    return ['valid' => true];
}

function get_vehicle(mysqli $conn, int $vehicleId): ?array
{
    $vehicleId = (int)$vehicleId;
    $result = $conn->query("SELECT * FROM vehicles WHERE id={$vehicleId} LIMIT 1");
    if (!$result || $result->num_rows !== 1) {
        return null;
    }
    return $result->fetch_assoc();
}

function is_vehicle_available(mysqli $conn, int $vehicleId, string $date, string $time): bool
{
    $vehicleId = (int)$vehicleId;
    $safeDate = $conn->real_escape_string($date);
    $safeTime = $conn->real_escape_string($time);

    $check = $conn->query("
        SELECT id FROM bookings
        WHERE vehicle_id={$vehicleId}
          AND journey_date='{$safeDate}'
          AND journey_time='{$safeTime}'
          AND status IN ('Booked', 'On Route')
        LIMIT 1
    ");

    return !$check || $check->num_rows === 0;
}

function get_available_driver(mysqli $conn, int $vehicleId): ?array
{
    $vehicleId = (int)$vehicleId;
    $driverQuery = $conn->query("SELECT * FROM drivers WHERE vehicle_id={$vehicleId} ORDER BY RAND() LIMIT 1");
    if (!$driverQuery || $driverQuery->num_rows === 0) {
        return null;
    }
    return $driverQuery->fetch_assoc();
}

function build_costs(array $vehicle): array
{
    $distanceKm = 10;
    $pricePerKm = (float)($vehicle['price_per_km'] ?? 0);
    $totalCost = round($distanceKm * $pricePerKm, 2);
    $discountPercent = 15.00;
    $discountAmount = round($totalCost * 0.15, 2);
    $finalCost = round($totalCost - $discountAmount, 2);

    return [
        'distance_km' => $distanceKm,
        'total_cost' => $totalCost,
        'discount_percent' => $discountPercent,
        'discount_amount' => $discountAmount,
        'final_cost' => $finalCost
    ];
}

if (isset($_POST['preview_booking'])) {
    $validation = validate_booking_input($form);
    if (!$validation['valid']) {
        $error = $validation['message'];
    } else {
        $vehicle = get_vehicle($conn, $form['vehicle']);
        if (!$vehicle) {
            $error = "Selected vehicle not found.";
        } elseif (!is_vehicle_available($conn, $form['vehicle'], $form['date'], $form['time'])) {
            $error = "Vehicle not available at this time.";
        } else {
            $costs = build_costs($vehicle);
            $bookingPreview = [
                'vehicle' => $vehicle,
                'costs' => $costs
            ];
        }
    }
}

if (isset($_POST['confirm_booking'])) {
    $validation = validate_booking_input($form);
    if (!$validation['valid']) {
        $error = $validation['message'];
    } else {
        $vehicle = get_vehicle($conn, $form['vehicle']);
        if (!$vehicle) {
            $error = "Selected vehicle not found.";
        } elseif (!is_vehicle_available($conn, $form['vehicle'], $form['date'], $form['time'])) {
            $error = "Vehicle not available at this time.";
        } else {
            $driver = get_available_driver($conn, $form['vehicle']);
            if (!$driver) {
                $error = "No driver is currently assigned to this vehicle.";
            } else {
                $costs = build_costs($vehicle);
                $paymentMethod = $_POST['payment_method'] ?? '';
                $cardBrand = $_POST['card_brand'] ?? null;
                $cardNumber = $_POST['card_number'] ?? null;

                $payment = privatehire_process_payment(
                    $paymentMethod,
                    (float)$costs['final_cost'],
                    $cardBrand,
                    $cardNumber
                );

                if (!$payment['success']) {
                    $error = $payment['message'] ?? 'Payment failed. Please try again.';
                    $bookingPreview = ['vehicle' => $vehicle, 'costs' => $costs];
                } else {
                    $userId = (int)$_SESSION['user_id'];
                    $safePickup = $conn->real_escape_string($form['pickup']);
                    $safeDestination = $conn->real_escape_string($form['destination']);
                    $safeDate = $conn->real_escape_string($form['date']);
                    $safeTime = $conn->real_escape_string($form['time']);
                    $safeNotification = $conn->real_escape_string($form['notification_preference']);
                    $safePaymentMethod = $conn->real_escape_string($paymentMethod);
                    $safePaymentStatus = $conn->real_escape_string($payment['status']);
                    $safeReference = $conn->real_escape_string((string)$payment['reference']);
                    $safeCardBrand = $payment['card_brand'] ? "'" . $conn->real_escape_string($payment['card_brand']) . "'" : "NULL";
                    $safeCardLast4 = $payment['card_last4'] ? "'" . $conn->real_escape_string($payment['card_last4']) . "'" : "NULL";

                    $insertSql = "
                        INSERT INTO bookings (
                            user_id, pickup, destination, journey_date, journey_time, passengers,
                            vehicle_id, driver_id, status, notification_preference, booking_channel,
                            total_cost, discount_percent, discount_amount, final_cost,
                            payment_method, payment_status, payment_reference, card_brand, card_last4,
                            confirmation_sent, reminder_sent
                        ) VALUES (
                            {$userId}, '{$safePickup}', '{$safeDestination}', '{$safeDate}', '{$safeTime}', {$form['passengers']},
                            {$form['vehicle']}, {$driver['id']}, 'Booked', '{$safeNotification}', 'online',
                            {$costs['total_cost']}, {$costs['discount_percent']}, {$costs['discount_amount']}, {$costs['final_cost']},
                            '{$safePaymentMethod}', '{$safePaymentStatus}', '{$safeReference}', {$safeCardBrand}, {$safeCardLast4},
                            0, 0
                        )
                    ";

                    if ($conn->query($insertSql)) {
                        $bookingId = (int)$conn->insert_id;
                        $userResult = $conn->query("SELECT username, email, phone FROM users WHERE id={$userId} LIMIT 1");
                        $userData = $userResult && $userResult->num_rows === 1 ? $userResult->fetch_assoc() : null;

                        $deliveryOk = false;
                        if ($userData) {
                            $subject = 'Booking Confirmation - PrivateHire';
                            $body = "Hello {$userData['username']},\n\n"
                                . "Your booking has been confirmed.\n\n"
                                . "Booking ID: {$bookingId}\n"
                                . "Pickup: {$form['pickup']}\n"
                                . "Destination: {$form['destination']}\n"
                                . "Date: {$form['date']}\n"
                                . "Time: {$form['time']}\n"
                                . "Vehicle: {$vehicle['name']}\n"
                                . "Total Cost: {$costs['total_cost']}\n"
                                . "Online Discount (15%): {$costs['discount_amount']}\n"
                                . "Final Cost: {$costs['final_cost']}\n"
                                . "Payment Method: {$paymentMethod}\n"
                                . "Payment Ref: {$payment['reference']}\n\n"
                                . "Thank you for choosing PrivateHire.";

                            $smsBody = "PrivateHire booking #{$bookingId} confirmed. {$form['date']} {$form['time']}, "
                                . "{$form['pickup']} to {$form['destination']}. Fare {$costs['final_cost']}.";

                            if (in_array($form['notification_preference'], ['email', 'both'], true)) {
                                $deliveryOk = privatehire_send_email($userData['email'], $userData['username'], $subject, $body) || $deliveryOk;
                            }

                            if (in_array($form['notification_preference'], ['sms', 'both'], true)) {
                                $smsOk = privatehire_send_sms((string)($userData['phone'] ?? ''), $smsBody);
                                $deliveryOk = $smsOk || $deliveryOk;

                                if (!$smsOk) {
                                    $deliveryOk = privatehire_send_email($userData['email'], $userData['username'], $subject, $body) || $deliveryOk;
                                }
                            }
                        }

                        if ($deliveryOk) {
                            $conn->query("UPDATE bookings SET confirmation_sent = 1 WHERE id={$bookingId}");
                        }

                        header("Location: receipt.php?id=" . $bookingId);
                        exit();
                    }

                    $error = "Failed to create booking. Please try again.";
                    $bookingPreview = ['vehicle' => $vehicle, 'costs' => $costs];
                }
            }
        }
    }
}

$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY seats ASC, name ASC");
?>

<div class="container mt-5">
    <h2>Book Taxi</h2>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <input class="form-control mb-3" name="pickup" placeholder="Pickup location" value="<?php echo htmlspecialchars($form['pickup']); ?>" required>
            </div>
            <div class="col-md-6">
                <input class="form-control mb-3" name="destination" placeholder="Destination" value="<?php echo htmlspecialchars($form['destination']); ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <input class="form-control mb-3" type="date" name="date" value="<?php echo htmlspecialchars($form['date']); ?>" required>
            </div>
            <div class="col-md-6">
                <input class="form-control mb-3" type="time" name="time" value="<?php echo htmlspecialchars($form['time']); ?>" required>
            </div>
        </div>

        <input class="form-control mb-3" type="number" name="passengers" placeholder="Number of passengers" min="1" value="<?php echo (int)$form['passengers'] ?: ''; ?>" required>

        <label class="form-label">Vehicle Size</label>
        <select class="form-control mb-3" name="vehicle" required>
            <option value="">Select vehicle size</option>
            <?php while ($row = $vehicles->fetch_assoc()) { ?>
                <option value="<?php echo (int)$row['id']; ?>" <?php echo ((int)$form['vehicle'] === (int)$row['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($row['name']); ?> (<?php echo (int)$row['seats']; ?> seats)
                </option>
            <?php } ?>
        </select>

        <label class="form-label">Notification Preference</label>
        <select class="form-control mb-3" name="notification_preference" required>
            <option value="sms" <?php echo $form['notification_preference'] === 'sms' ? 'selected' : ''; ?>>SMS</option>
            <option value="email" <?php echo $form['notification_preference'] === 'email' ? 'selected' : ''; ?>>Email</option>
            <option value="both" <?php echo $form['notification_preference'] === 'both' ? 'selected' : ''; ?>>Both SMS and Email</option>
        </select>

        <button class="btn btn-dark" name="preview_booking">Continue to Summary</button>
    </form>

    <?php if ($bookingPreview) { ?>
        <div class="card p-4">
            <h4>Booking Summary</h4>
            <p><strong>Pickup:</strong> <?php echo htmlspecialchars($form['pickup']); ?></p>
            <p><strong>Destination:</strong> <?php echo htmlspecialchars($form['destination']); ?></p>
            <p><strong>Date/Time:</strong> <?php echo htmlspecialchars($form['date'] . ' ' . $form['time']); ?></p>
            <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($bookingPreview['vehicle']['name']); ?></p>
            <p><strong>Total Cost:</strong> <?php echo number_format((float)$bookingPreview['costs']['total_cost'], 2); ?></p>
            <p><strong>Online Discount (15%):</strong> -<?php echo number_format((float)$bookingPreview['costs']['discount_amount'], 2); ?></p>
            <p><strong>Final Cost:</strong> <?php echo number_format((float)$bookingPreview['costs']['final_cost'], 2); ?></p>

            <form method="POST">
                <?php foreach ($form as $key => $value) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars((string)$value); ?>">
                <?php } ?>

                <label class="form-label mt-2">Payment Method</label>
                <select class="form-control mb-3" name="payment_method" required>
                    <option value="">Choose payment method</option>
                    <option value="paypal">PayPal</option>
                    <option value="card">Credit/Debit Card (Visa/Mastercard/Amex)</option>
                </select>

                <label class="form-label">Card Type (only for card payments)</label>
                <select class="form-control mb-3" name="card_brand">
                    <option value="">Select card type</option>
                    <option value="visa">VISA</option>
                    <option value="mastercard">Mastercard</option>
                    <option value="amex">Amex</option>
                </select>

                <label class="form-label">Card Number (only for card payments)</label>
                <input class="form-control mb-3" type="text" name="card_number" placeholder="4111111111111111">

                <button class="btn btn-success" name="confirm_booking">Confirm & Pay</button>
            </form>
        </div>
    <?php } ?>
</div>

