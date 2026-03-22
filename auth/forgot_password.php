<?php
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

$message = "";
$error = "";

if (isset($_POST['reset_request'])) {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $safe_email = $conn->real_escape_string($email);
        $sql = "SELECT id, username FROM users WHERE email='$safe_email' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate a secure token and set expiration to 24 hours from now
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 86400);

            $update_sql = "UPDATE users SET reset_token='$token', reset_expires_at='$expires_at' WHERE id=" . $user['id'];
            
            if ($conn->query($update_sql)) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/privatehire/auth/reset_password.php?token=" . $token;
                $subject = 'Password Reset Request';
                $body = "Hello " . $user['username'] . ",\n\n"
                    . "You recently requested to reset your password for PrivateHire.\n\n"
                    . "Click the link below to reset it:\n$reset_link\n\n"
                    . "If you did not request this, ignore this message. The link expires in 24 hours.\n\n"
                    . "Best regards,\nThe PrivateHire Team";

                privatehire_send_email($email, $user['username'], $subject, $body);
            }
        }
        
        // We always show a success message to prevent malicious actors from guessing valid emails
        $message = "If an account with that email exists, a password reset link has been sent.";
    }
}
?>

<div class="container mt-5" style="max-width:500px">
    <h2 class="mb-4">Reset Password</h2>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <?php if (!empty($message)) { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form method="POST" novalidate>
        <input
            class="form-control mb-3"
            name="email"
            type="email"
            placeholder="Enter your email address"
            required
        >

        <button class="btn btn-dark w-100 mb-2" name="reset_request">Send Reset Link</button>
    </form>
</div>
