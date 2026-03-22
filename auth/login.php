<?php
include "../config/db.php";
include "../navbar.php";

$error = "";
$email = "";
$message = "";

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $message = "Password reset successful. Please login with your new password.";
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $safe_email = $conn->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email='$safe_email' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'] ?? 'customer';
                $_SESSION['username'] = $user['username'];

                if (($_SESSION['role']) === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../booking/book.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="container mt-5" style="max-width:500px">
    <h2 class="mb-4">Login</h2>

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
            placeholder="Email"
            value="<?php echo htmlspecialchars($email); ?>"
            required
        >

        <input
            class="form-control mb-3"
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button class="btn btn-dark w-100 mb-2" name="login">Login</button>
    </form>

    <div class="text-center">
        <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
    </div>
</div>
