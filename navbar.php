<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<nav class="navbar navbar-dark bg-secondary navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="/privatehire/index.php">PrivateHire Cars</a>

        <div>
            <?php if (isset($_SESSION['user_id'])) { ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                    <a class="btn btn-warning me-2" href="/privatehire/admin/dashboard.php">Admin Dashboard</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/drivers.php">Drivers</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/vehicles.php">Vehicles</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/call_bookings.php">Call Bookings</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/reviews.php">Reviews</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/reports.php">Reports</a>
                    <a class="btn btn-light me-2" href="/privatehire/admin/enquiries.php">Enquiries</a>
                <?php } else { ?>
                    <a class="btn btn-light me-2" href="/privatehire/booking/book.php">Book Ride</a>
                    <a class="btn btn-light me-2" href="/privatehire/booking/my_bookings.php">Booking History</a>
                    <a class="btn btn-light me-2" href="/privatehire/contact.php">Contact</a>
                <?php } ?>

                <a class="btn btn-danger" href="/privatehire/auth/logout.php">Logout</a>

            <?php } else { ?>

                <a class="btn btn-success me-2" href="/privatehire/auth/login.php">Login</a>
                <a class="btn btn-primary" href="/privatehire/auth/register.php">Signup</a>

            <?php } ?>
        </div>
    </div>
</nav>
