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

if (isset($_POST['add_driver'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $licence = trim($_POST['licence_number'] ?? '');
    $vehicle = (int)($_POST['vehicle'] ?? 0);

    if ($name === '' || $phone === '' || $licence === '' || $vehicle <= 0) {
        $error = "Please fill all required fields.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safePhone = $conn->real_escape_string($phone);
        $safeEmail = $email !== '' ? "'" . $conn->real_escape_string($email) . "'" : "NULL";
        $safeLicence = $conn->real_escape_string($licence);

        $ok = $conn->query("
            INSERT INTO drivers(name, phone, email, licence_number, vehicle_id, active)
            VALUES ('{$safeName}', '{$safePhone}', {$safeEmail}, '{$safeLicence}', {$vehicle}, 1)
        ");

        if ($ok) {
            $driverId = (int)$conn->insert_id;
            privatehire_log_admin_activity($conn, $adminId, 'create', 'driver', $driverId, "Driver {$name} added.");

            if ($email !== '') {
                privatehire_send_email($email, $name, 'PrivateHire Driver Profile Created', "Hello {$name},\n\nYour driver profile has been added to PrivateHire.\n\nRegards,\nPrivateHire Team");
            }
            $message = "Driver added successfully.";
        } else {
            $error = "Failed to add driver.";
        }
    }
}

if (isset($_POST['update_driver'])) {
    $id = (int)($_POST['driver_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $licence = trim($_POST['licence_number'] ?? '');
    $vehicle = (int)($_POST['vehicle'] ?? 0);

    if ($id <= 0 || $name === '' || $phone === '' || $licence === '' || $vehicle <= 0) {
        $error = "Invalid update data.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safePhone = $conn->real_escape_string($phone);
        $safeEmail = $email !== '' ? "'" . $conn->real_escape_string($email) . "'" : "NULL";
        $safeLicence = $conn->real_escape_string($licence);

        $ok = $conn->query("
            UPDATE drivers
            SET name='{$safeName}', phone='{$safePhone}', email={$safeEmail}, licence_number='{$safeLicence}', vehicle_id={$vehicle}
            WHERE id={$id}
        ");
        if ($ok) {
            privatehire_log_admin_activity($conn, $adminId, 'update', 'driver', $id, "Driver {$id} updated.");
            $message = "Driver updated.";
        } else {
            $error = "Failed to update driver.";
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)($_GET['toggle'] ?? 0);
    if ($id > 0) {
        $conn->query("UPDATE drivers SET active = IF(active=1,0,1) WHERE id={$id}");
        privatehire_log_admin_activity($conn, $adminId, 'toggle_active', 'driver', $id, "Driver active status toggled.");
        header("Location: drivers.php");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)($_GET['delete'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM drivers WHERE id={$id}");
        privatehire_log_admin_activity($conn, $adminId, 'delete', 'driver', $id, "Driver deleted.");
        header("Location: drivers.php");
        exit();
    }
}

$editDriver = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM drivers WHERE id={$id} LIMIT 1");
    if ($res && $res->num_rows === 1) {
        $editDriver = $res->fetch_assoc();
    }
}

$vehicles = $conn->query("SELECT id, name FROM vehicles WHERE active=1 ORDER BY name ASC");
$drivers = $conn->query("
    SELECT d.*, v.name AS vehicle_name
    FROM drivers d
    LEFT JOIN vehicles v ON d.vehicle_id=v.id
    ORDER BY d.id DESC
");
?>

<div class="container mt-5">
    <h2>Manage Drivers</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <form method="POST" class="mb-4 card card-body">
        <input type="hidden" name="driver_id" value="<?php echo (int)($editDriver['id'] ?? 0); ?>">
        <div class="row">
            <div class="col-md-6"><input class="form-control mb-2" name="name" placeholder="Driver Name" value="<?php echo htmlspecialchars($editDriver['name'] ?? ''); ?>" required></div>
            <div class="col-md-6"><input class="form-control mb-2" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($editDriver['phone'] ?? ''); ?>" required></div>
        </div>
        <div class="row">
            <div class="col-md-6"><input class="form-control mb-2" name="email" placeholder="Email (optional)" value="<?php echo htmlspecialchars($editDriver['email'] ?? ''); ?>"></div>
            <div class="col-md-6"><input class="form-control mb-2" name="licence_number" placeholder="Licence Number" value="<?php echo htmlspecialchars($editDriver['licence_number'] ?? ''); ?>" required></div>
        </div>
        <select class="form-control mb-2" name="vehicle" required>
            <option value="">Assign Vehicle</option>
            <?php while ($v = $vehicles->fetch_assoc()) { ?>
                <option value="<?php echo (int)$v['id']; ?>" <?php echo ((int)($editDriver['vehicle_id'] ?? 0) === (int)$v['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($v['name']); ?>
                </option>
            <?php } ?>
        </select>
        <?php if ($editDriver) { ?>
            <button class="btn btn-primary" name="update_driver">Update Driver</button>
            <a class="btn btn-secondary" href="drivers.php">Cancel Edit</a>
        <?php } else { ?>
            <button class="btn btn-dark" name="add_driver">Add Driver</button>
        <?php } ?>
    </form>

    <table class="table table-bordered table-striped">
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Licence</th>
            <th>Vehicle</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $drivers->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['licence_number'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_name'] ?? 'None'); ?></td>
                <td><?php echo (int)$row['active'] === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Suspended</span>'; ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-primary" href="drivers.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-warning" href="drivers.php?toggle=<?php echo (int)$row['id']; ?>">Toggle</a>
                    <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this driver?');" href="drivers.php?delete=<?php echo (int)$row['id']; ?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

