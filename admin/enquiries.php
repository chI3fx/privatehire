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

if (isset($_POST['reply_enquiry'])) {
    $id = (int)($_POST['enquiry_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    if ($id <= 0 || $reply === '') {
        $error = "Reply cannot be empty.";
    } else {
        $safeReply = $conn->real_escape_string($reply);
        $enqRes = $conn->query("SELECT * FROM enquiries WHERE id={$id} LIMIT 1");
        if ($enqRes && $enqRes->num_rows === 1) {
            $enq = $enqRes->fetch_assoc();
            $conn->query("
                UPDATE enquiries
                SET response='{$safeReply}', response_date=NOW(), status='resolved'
                WHERE id={$id}
            ");
            privatehire_send_email(
                $enq['email'],
                $enq['name'],
                'Response to Your PrivateHire Enquiry',
                "Hello {$enq['name']},\n\n{$reply}\n\nRegards,\nPrivateHire Team"
            );
            privatehire_log_admin_activity($conn, $adminId, 'reply_enquiry', 'enquiry', $id, 'Enquiry replied and resolved.');
            $message = "Reply sent and enquiry marked as resolved.";
        } else {
            $error = "Enquiry not found.";
        }
    }
}

$openOnly = isset($_GET['open_only']) ? 1 : 0;
$where = $openOnly ? "WHERE e.status='open'" : "";
$enquiries = $conn->query("
    SELECT e.*, u.username, b.pickup, b.destination, b.journey_date, b.journey_time
    FROM enquiries e
    LEFT JOIN users u ON u.id = e.user_id
    LEFT JOIN bookings b ON b.id = e.booking_id
    {$where}
    ORDER BY e.id DESC
");
?>

<div class="container mt-5 mb-5">
    <h2>Customer Enquiries</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <a class="btn btn-outline-primary mb-3" href="enquiries.php?open_only=1">Show Open Only</a>
    <a class="btn btn-outline-secondary mb-3" href="enquiries.php">Show All</a>

    <?php if ($enquiries && $enquiries->num_rows > 0) { ?>
        <?php while ($e = $enquiries->fetch_assoc()) { ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($e['name']); ?> (<?php echo htmlspecialchars($e['email']); ?>)</h5>
                        <span class="badge <?php echo $e['status'] === 'resolved' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo htmlspecialchars($e['status'] ?? 'open'); ?></span>
                    </div>
                    <p class="mb-1"><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($e['message'])); ?></p>
                    <p class="mb-1"><strong>Customer Account:</strong> <?php echo htmlspecialchars($e['username'] ?? 'Not linked'); ?></p>
                    <p class="mb-1"><strong>Booking Context:</strong> <?php echo htmlspecialchars(($e['pickup'] ?? '-') . ' → ' . ($e['destination'] ?? '-') . ' ' . ($e['journey_date'] ?? '')); ?></p>
                    <?php if (!empty($e['response'])) { ?>
                        <p class="mb-1"><strong>Response:</strong> <?php echo nl2br(htmlspecialchars($e['response'])); ?></p>
                        <p class="text-muted mb-0"><small>Replied: <?php echo htmlspecialchars($e['response_date']); ?></small></p>
                    <?php } else { ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="enquiry_id" value="<?php echo (int)$e['id']; ?>">
                            <textarea class="form-control mb-2" name="reply" rows="3" placeholder="Write reply..." required></textarea>
                            <button class="btn btn-primary btn-sm" name="reply_enquiry">Send Reply</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="alert alert-info">No enquiries found.</div>
    <?php } ?>
</div>

