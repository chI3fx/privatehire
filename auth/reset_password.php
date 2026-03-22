<?php
include "../config/db.php";
include "../navbar.php";

$error = "";
$message = "";
$valid_token = false;
$user_id = null;

// 1. Validate the token from the URL
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Check if token exists and hasn't expired
    $sql = "SELECT id FROM users WHERE reset_token='$token' AND reset_expires_at > NOW() LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $valid_token = true;
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    } else {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $error = "No reset token provided.";
}

// 2. Handle the new password submission
if (isset($_POST['reset_password']) && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the password and clear the reset token data
        $update_sql = "UPDATE users SET password='$hashed_password', reset_token=NULL, reset_expires_at=NULL WHERE id=$user_id";
        
        if ($conn->query($update_sql)) {
            if (!headers_sent()) {
                header("Location: login.php?reset=success");
                exit();
            }

            $message = "Your password has been successfully reset! You can now login.";
            $valid_token = false;
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>

<div class="container mt-5" style="max-width:500px">
    <h2 class="mb-4">Create New Password</h2>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <?php if (!empty($message)) { ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?> <br><br>
            <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
        </div>
    <?php } ?>

    <?php if ($valid_token) { ?>
        <form method="POST">
            <input
                class="form-control mb-3"
                type="password"
                name="password"
                placeholder="New Password"
                required
            >

            <input
                class="form-control mb-3"
                type="password"
                name="confirm_password"
                placeholder="Confirm New Password"
                required
            >

            <button class="btn btn-dark w-100 mb-2" name="reset_password">Reset Password</button>
        </form>
    <?php } elseif(empty($message)) { ?>
        <a href="forgot_password.php" class="btn btn-secondary w-100">Request a new link</a>
    <?php } ?>
</div>
