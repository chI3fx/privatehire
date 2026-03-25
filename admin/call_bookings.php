<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$adminId = (int)$_SESSION['user_id'];
$message = "";
$error = "";
$searchTerm = trim($_GET['q'] ?? '');
$searchResults = null;
$selectedUser = null;

if (isset($_POST['create_phone_customer'])) {
    $username = trim($_POST['new_username'] ?? '');
    $email = trim($_POST['new_email'] ?? '');
    $phone = trim($_POST['new_phone'] ?? '');
    $password = $_POST['new_password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = "New customer creation requires username, email, and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email for new customer.";
    } else {
        $safeEmail = $conn->real_escape_string($email);
        $exists = $conn->query("SELECT id FROM users WHERE email='{$safeEmail}' LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            $error = "A user with this email already exists.";
        } else {
            $safeUsername = $conn->real_escape_string($username);
            $safePhone = $phone !== '' ? "'" . $conn->real_escape_string($phone) . "'" : "NULL";
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $safeHashed = $conn->real_escape_string($hashed);

            $ok = $conn->query("
                INSERT INTO users (username, email, password, phone, role, account_source)
                VALUES ('{$safeUsername}', '{$safeEmail}', '{$safeHashed}', {$safePhone}, 'customer', 'phone')
            ");
            if ($ok) {
                $newId = (int)$conn->insert_id;
                privatehire_log_admin_activity($conn, $adminId, 'create', 'user', $newId, 'Phone call customer account created.');
                $message = "New phone-call customer account created.";
                $_GET['user_id'] = $newId;
            } else {
                $error = "Failed to create new customer account.";
            }
        }
    }
}

if ($searchTerm !== '') {
    $safe = $conn->real_escape_string($searchTerm);
    $searchSql = "
        SELECT id, username, email, phone, account_source
        FROM users
        WHERE role='customer' AND (
            username LIKE '%{$safe}%'
            OR email LIKE '%{$safe}%'
            OR phone LIKE '%{$safe}%'
        )
        ORDER BY id DESC
        LIMIT 20
    ";
    $searchResults = $conn->query($searchSql);
}

$selectedUserId = (int)($_POST['selected_user_id'] ?? $_GET['user_id'] ?? 0);
if ($selectedUserId > 0) {
    $userRes = $conn->query("SELECT id, username, email, phone, account_source FROM users WHERE id={$selectedUserId} LIMIT 1");
    if ($userRes && $userRes->num_rows === 1) {
        $selectedUser = $userRes->fetch_assoc();
    }
}

if (isset($_POST['create_phone_booking'])) {
    $verificationOk = isset($_POST['verified_identity']) && $_POST['verified_identity'] === '1';
    $pickup = trim($_POST['pickup'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $passengers = (int)($_POST['passengers'] ?? 0);
    $vehicleId = (int)($_POST['vehicle'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'card';
    $notificationPreference = $_POST['notification_preference'] ?? 'email';

    if (!$verificationOk) {
        $error = "Verification step is required before creating a phone booking.";
    } elseif (!$selectedUser) {
        $error = "Please select a valid customer first.";
    } elseif ($pickup === '' || $destination === '' || $date === '' || $time === '' || $passengers < 1 || $vehicleId < 1) {
        $error = "Please fill all phone booking fields correctly.";
    } else {
        $journey = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if (!$journey || $journey <= new DateTime()) {
            $error = "Journey date/time must be in the future.";
        } else {
            $safeDate = $conn->real_escape_string($date);
            $safeTime = $conn->real_escape_string($time);
            $availability = $conn->query("
                SELECT id FROM bookings
                WHERE vehicle_id={$vehicleId}
                  AND journey_date='{$safeDate}'
                  AND journey_time='{$safeTime}'
                  AND status IN ('Booked', 'On Route')
                LIMIT 1
            ");

            if ($availability && $availability->num_rows > 0) {
                $error = "Vehicle is not available at that date/time.";
            } else {
                $vehicleRes = $conn->query("SELECT * FROM vehicles WHERE id={$vehicleId} AND active=1 LIMIT 1");
                $driverRes = $conn->query("SELECT * FROM drivers WHERE vehicle_id={$vehicleId} AND active=1 ORDER BY RAND() LIMIT 1");
                if (!$vehicleRes || $vehicleRes->num_rows !== 1) {
                    $error = "Vehicle not found or inactive.";
                } elseif (!$driverRes || $driverRes->num_rows !== 1) {
                    $error = "No active driver assigned for this vehicle.";
                } else {
                    $vehicle = $vehicleRes->fetch_assoc();
                    $driver = $driverRes->fetch_assoc();
                    $distanceKm = 10;
                    $totalCost = round(((float)$vehicle['price_per_km']) * $distanceKm, 2);
                    $finalCost = $totalCost;

                    $safePickup = $conn->real_escape_string($pickup);
                    $safeDestination = $conn->real_escape_string($destination);
                    $safeNotif = $conn->real_escape_string($notificationPreference);
                    $safePaymentMethod = $conn->real_escape_string($paymentMethod);
                    $paymentRef = 'PHONE-' . strtoupper(bin2hex(random_bytes(4)));
                    $safePaymentRef = $conn->real_escape_string($paymentRef);

                    $insertSql = "
                        INSERT INTO bookings (
                            user_id, pickup, destination, journey_date, journey_time, passengers,
                            vehicle_id, driver_id, status, notification_preference, booking_channel,
                            total_cost, discount_percent, discount_amount, final_cost,
                            payment_method, payment_status, payment_reference, confirmation_sent, reminder_sent, service_type
                        ) VALUES (
                            {$selectedUser['id']}, '{$safePickup}', '{$safeDestination}', '{$safeDate}', '{$safeTime}', {$passengers},
                            {$vehicleId}, {$driver['id']}, 'Booked', '{$safeNotif}', 'phone',
                            {$totalCost}, 0.00, 0.00, {$finalCost},
                            '{$safePaymentMethod}', 'pending', '{$safePaymentRef}', 0, 0, 'Phone Booking'
                        )
                    ";

                    if ($conn->query($insertSql)) {
                        $bookingId = (int)$conn->insert_id;
                        privatehire_log_admin_activity($conn, $adminId, 'create', 'booking', $bookingId, 'Phone booking created by call centre.');

                        $subject = "Phone Booking Confirmation - #{$bookingId}";
                        $body = "Hello {$selectedUser['username']},\n\n"
                            . "Your phone booking has been recorded.\n"
                            . "Booking ID: {$bookingId}\n"
                            . "Pickup: {$pickup}\nDestination: {$destination}\n"
                            . "Date/Time: {$date} {$time}\n"
                            . "Final Cost: {$finalCost}\n\nRegards,\nPrivateHire Team";

                        $delivery = privatehire_send_email($selectedUser['email'], $selectedUser['username'], $subject, $body);
                        if ($delivery) {
                            $conn->query("UPDATE bookings SET confirmation_sent=1 WHERE id={$bookingId}");
                        }
                        $message = "Phone booking created (Booking #{$bookingId}).";
                    } else {
                        $error = "Failed to create phone booking.";
                    }
                }
            }
        }
    }
}

$vehicles = $conn->query("SELECT id, name, seats FROM vehicles WHERE active=1 ORDER BY seats ASC, name ASC");
$recentCards = null;
if ($selectedUser) {
    $recentCards = $conn->query("
        SELECT card_brand, card_last4, payment_status
        FROM bookings
        WHERE user_id={$selectedUser['id']} AND card_last4 IS NOT NULL
        ORDER BY id DESC LIMIT 5
    ");
}
?>

<div class="container mt-5 mb-5">
    <h2>Call Centre Booking</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <div class="card p-3 mb-4">
        <h5>Create New Phone-Call Customer Account</h5>
        <form method="POST" class="row g-2">
            <div class="col-md-3"><input class="form-control" name="new_username" placeholder="Username" required></div>
            <div class="col-md-3"><input class="form-control" name="new_email" placeholder="Email" required></div>
            <div class="col-md-2"><input class="form-control" name="new_phone" placeholder="Phone"></div>
            <div class="col-md-2"><input class="form-control" type="password" name="new_password" placeholder="Password" required></div>
            <div class="col-md-2"><button class="btn btn-dark w-100" name="create_phone_customer">Create Account</button></div>
        </form>
    </div>

    <form class="mb-4" method="GET">
        <label class="form-label">Search Customer (name, phone, or email)</label>
        <div class="input-group">
            <input class="form-control" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" required>
            <button class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if ($searchResults) { ?>
        <div class="card p-3 mb-4">
            <h5>Search Results</h5>
            <table class="table table-bordered">
                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Account Source</th><th>Action</th></tr>
                <?php while ($u = $searchResults->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($u['account_source'] ?? 'web'); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="?q=<?php echo urlencode($searchTerm); ?>&user_id=<?php echo (int)$u['id']; ?>">Select</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

    <?php if ($selectedUser) { ?>
        <div class="card p-3 mb-3">
            <h5>Selected Customer</h5>
            <p><b>Name:</b> <?php echo htmlspecialchars($selectedUser['username']); ?></p>
            <p><b>Email:</b> <?php echo htmlspecialchars($selectedUser['email']); ?></p>
            <p><b>Phone:</b> <?php echo htmlspecialchars($selectedUser['phone'] ?? 'N/A'); ?></p>
            <p><b>Account Source:</b> <?php echo htmlspecialchars($selectedUser['account_source'] ?? 'web'); ?></p>
        </div>

        <div class="card p-3 mb-4">
            <h6>Masked Card History (Staff View)</h6>
            <?php if ($recentCards && $recentCards->num_rows > 0) { ?>
                <ul class="mb-0">
                    <?php while ($card = $recentCards->fetch_assoc()) { ?>
                        <li><?php echo htmlspecialchars(privatehire_mask_card($card['card_brand'], $card['card_last4'])); ?> (<?php echo htmlspecialchars($card['payment_status']); ?>)</li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="mb-0 text-muted">No previous card records.</p>
            <?php } ?>
        </div>

        <div class="card p-4">
            <h5>Create Phone Booking</h5>
            <form method="POST">
                <input type="hidden" name="selected_user_id" value="<?php echo (int)$selectedUser['id']; ?>">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="verified_identity" name="verified_identity">
                    <label class="form-check-label" for="verified_identity">I have verified caller identity.</label>
                </div>

                <input class="form-control mb-3" name="pickup" placeholder="Pickup location" required>
                <input class="form-control mb-3" name="destination" placeholder="Destination" required>
                <input class="form-control mb-3" type="date" name="date" required>
                <input class="form-control mb-3" type="time" name="time" required>
                <input class="form-control mb-3" type="number" min="1" name="passengers" placeholder="Passengers" required>

                <label class="form-label">Vehicle Size</label>
                <select class="form-control mb-3" name="vehicle" required>
                    <option value="">Select vehicle</option>
                    <?php while ($v = $vehicles->fetch_assoc()) { ?>
                        <option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?> (<?php echo (int)$v['seats']; ?> seats)</option>
                    <?php } ?>
                </select>

                <label class="form-label">Notification Preference</label>
                <select class="form-control mb-3" name="notification_preference">
                    <option value="email">Email</option><option value="sms">SMS</option><option value="both">Both</option>
                </select>

                <label class="form-label">Payment Method</label>
                <select class="form-control mb-3" name="payment_method">
                    <option value="card">Card</option><option value="paypal">PayPal</option>
                </select>

                <button class="btn btn-success" name="create_phone_booking">Create Phone Booking</button>
            </form>
        </div>
    <?php } ?>
</div>

