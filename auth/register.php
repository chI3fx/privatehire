<?php
include "../config/db.php";
include "../config/services.php";
include "../navbar.php";

$error = "";
$username = "";
$email = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password_raw)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $username)) {
        $error = "Username can only contain letters, numbers, spaces, and underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password_raw) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $safe_username = $conn->real_escape_string($username);
        $safe_email = $conn->real_escape_string($email);

        $check_sql = "SELECT id FROM users WHERE email='$safe_email' LIMIT 1";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, email, password, role)
                    VALUES ('$safe_username', '$safe_email', '$password', 'customer')";

            if ($conn->query($sql)) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['role'] = 'customer';
                $_SESSION['username'] = $username;

                privatehire_send_email(
                    $email,
                    $username,
                    'Welcome to PrivateHire!',
                    "Hello $username,\n\nThank you for registering with PrivateHire! We look forward to serving you.\n\nBest regards,\nThe PrivateHire Team"
                );

                header("Location: ../booking/book.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="container mt-5" style="max-width:500px">
    <h2 class="mb-4">Signup</h2>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="POST" novalidate>
        <input
            class="form-control mb-3"
            name="username"
            placeholder="Username"
            value="<?php echo htmlspecialchars($username); ?>"
            required
        >

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

        <button class="btn btn-primary w-100 mb-2" name="register">Signup</button>
    </form>

    <div class="text-center">
        <span>Already have an account? </span>
        <a href="login.php" class="text-decoration-none">Login</a>
    </div>
</div>
