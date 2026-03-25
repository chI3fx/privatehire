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

if (isset($_POST['generate_offer'])) {
    $targetUser = (int)($_POST['user_id'] ?? 0);
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $expiresDays = (int)($_POST['expires_days'] ?? 7);
    if ($targetUser <= 0 || $discount <= 0 || $expiresDays < 1) {
        $error = "Invalid offer input.";
    } else {
        $expiresAt = (new DateTime("+{$expiresDays} days"))->format('Y-m-d H:i:s');
        $offer = privatehire_generate_offer_code($conn, $targetUser, $discount, $expiresAt, 'manual_admin');
        if ($offer) {
            $userRes = $conn->query("SELECT username, email FROM users WHERE id={$targetUser} LIMIT 1");
            if ($userRes && $userRes->num_rows === 1) {
                $u = $userRes->fetch_assoc();
                privatehire_send_email(
                    $u['email'],
                    $u['username'],
                    'Your PrivateHire Offer Code',
                    "Hello {$u['username']},\n\nYour offer code is {$offer['code']} for {$offer['discount_percent']}% off.\nExpires: {$offer['expires_at']}\nSingle-use only.\n\nRegards,\nPrivateHire Team"
                );
            }
            privatehire_log_admin_activity($conn, $adminId, 'generate_offer', 'offer_code', (int)$offer['id'], "Offer {$offer['code']} created.");
            $message = "Offer code created and emailed.";
        } else {
            $error = "Failed to create offer code.";
        }
    }
}

if (isset($_POST['run_loyalty'])) {
    $reportCustomers = $conn->query("
        SELECT b.user_id, u.username, u.email,
               COUNT(*) AS booking_count,
               SUM(COALESCE(b.final_cost,0)) AS total_spend
        FROM bookings b
        JOIN users u ON u.id=b.user_id
        WHERE b.journey_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
          AND b.status <> 'Cancelled'
        GROUP BY b.user_id
        HAVING booking_count >= 3 OR total_spend >= 150
    ");

    if ($reportCustomers) {
        while ($c = $reportCustomers->fetch_assoc()) {
            $uid = (int)$c['user_id'];
            $conn->query("UPDATE users SET loyalty_tier='LOYAL' WHERE id={$uid}");
            $expiresAt = (new DateTime('+14 days'))->format('Y-m-d H:i:s');
            $offer = privatehire_generate_offer_code($conn, $uid, 10.00, $expiresAt, 'loyalty_auto');
            if ($offer) {
                privatehire_send_email(
                    $c['email'],
                    $c['username'],
                    'Loyal Customer Reward - PrivateHire',
                    "Hello {$c['username']},\n\nThank you for being a loyal customer.\nUse code {$offer['code']} for {$offer['discount_percent']}% off your next booking.\nExpires: {$offer['expires_at']}\nSingle-use.\n\nRegards,\nPrivateHire Team"
                );
            }
        }
    }
    privatehire_log_admin_activity($conn, $adminId, 'run_loyalty', 'users', null, 'Loyalty process executed.');
    $message = "Loyalty process completed.";
}

$topVehicles = $conn->query("
    SELECT v.id, v.name, COUNT(*) AS bookings_count
    FROM bookings b
    JOIN vehicles v ON v.id = b.vehicle_id
    WHERE b.journey_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY v.id, v.name
    ORDER BY bookings_count DESC
    LIMIT 10
");

$topCustomers = $conn->query("
    SELECT u.id, u.username, u.email,
           COUNT(*) AS bookings_count,
           SUM(COALESCE(b.final_cost,0)) AS total_spend
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.journey_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY u.id, u.username, u.email
    ORDER BY bookings_count DESC, total_spend DESC
    LIMIT 10
");

$allCustomers = $conn->query("SELECT id, username, email FROM users WHERE role='customer' ORDER BY username ASC");
$latestOffers = $conn->query("
    SELECT o.*, u.username, u.email
    FROM offer_codes o
    JOIN users u ON u.id=o.user_id
    ORDER BY o.id DESC
    LIMIT 20
");
?>

<div class="container mt-5 mb-5">
    <h2>Reports, Revenue & Loyalty</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <div class="mb-4">
        <form method="POST" class="d-inline">
            <button class="btn btn-dark" name="run_loyalty">Run Loyalty Auto-Tag + Offers</button>
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Top 10 Vehicles (Last 6 Months)</strong></div>
        <div class="card-body">
            <table class="table table-bordered table-striped mb-0">
                <tr><th>Vehicle</th><th>Booking Frequency</th></tr>
                <?php if ($topVehicles && $topVehicles->num_rows > 0) { while ($v = $topVehicles->fetch_assoc()) { ?>
                    <tr><td><?php echo htmlspecialchars($v['name']); ?></td><td><?php echo (int)$v['bookings_count']; ?></td></tr>
                <?php }} else { ?>
                    <tr><td colspan="2">No data.</td></tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Most Active Customers + Spend (Last 6 Months)</strong></div>
        <div class="card-body">
            <table class="table table-bordered table-striped mb-0">
                <tr><th>Customer</th><th>Email</th><th>Bookings</th><th>Total Spend</th></tr>
                <?php if ($topCustomers && $topCustomers->num_rows > 0) { while ($c = $topCustomers->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['username']); ?></td>
                        <td><?php echo htmlspecialchars($c['email']); ?></td>
                        <td><?php echo (int)$c['bookings_count']; ?></td>
                        <td><?php echo number_format((float)$c['total_spend'], 2); ?></td>
                    </tr>
                <?php }} else { ?>
                    <tr><td colspan="4">No data.</td></tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Create Offer Code</strong></div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <div class="col-md-5">
                    <select class="form-control" name="user_id" required>
                        <option value="">Select customer</option>
                        <?php while ($u = $allCustomers->fetch_assoc()) { ?>
                            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['username'] . ' (' . $u['email'] . ')'); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="discount_percent" placeholder="% off" required></div>
                <div class="col-md-3"><input class="form-control" type="number" name="expires_days" value="7" min="1" placeholder="Expires in days" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100" name="generate_offer">Generate</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Recent Offer Codes</strong></div>
        <div class="card-body">
            <table class="table table-bordered table-striped mb-0">
                <tr><th>Code</th><th>Customer</th><th>Discount</th><th>Expires</th><th>Used</th><th>Source</th></tr>
                <?php if ($latestOffers && $latestOffers->num_rows > 0) { while ($o = $latestOffers->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($o['code']); ?></td>
                        <td><?php echo htmlspecialchars($o['username']); ?></td>
                        <td><?php echo number_format((float)$o['discount_percent'], 2); ?>%</td>
                        <td><?php echo htmlspecialchars($o['expires_at']); ?></td>
                        <td><?php echo (int)$o['is_used'] === 1 ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($o['source']); ?></td>
                    </tr>
                <?php }} else { ?>
                    <tr><td colspan="6">No offers yet.</td></tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>

