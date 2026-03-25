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

if (isset($_POST['add_vehicle'])) {
    $name = trim($_POST['name'] ?? '');
    $seats = (int)($_POST['seats'] ?? 0);
    $pricePerKm = (float)($_POST['price_per_km'] ?? 0);
    $registration = trim($_POST['registration_number'] ?? '');
    $colour = trim($_POST['colour'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');

    if ($name === '' || $seats < 1) {
        $error = "Please provide required vehicle fields.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safeReg = $registration !== '' ? "'" . $conn->real_escape_string($registration) . "'" : "NULL";
        $safeColour = $colour !== '' ? "'" . $conn->real_escape_string($colour) . "'" : "NULL";
        $safeMake = $make !== '' ? "'" . $conn->real_escape_string($make) . "'" : "NULL";
        $safeModel = $model !== '' ? "'" . $conn->real_escape_string($model) . "'" : "NULL";

        $ok = $conn->query("
            INSERT INTO vehicles(name, seats, price_per_km, registration_number, colour, make, model, active)
            VALUES ('{$safeName}', {$seats}, {$pricePerKm}, {$safeReg}, {$safeColour}, {$safeMake}, {$safeModel}, 1)
        ");
        if ($ok) {
            $vehicleId = (int)$conn->insert_id;
            privatehire_log_admin_activity($conn, $adminId, 'create', 'vehicle', $vehicleId, "Vehicle {$name} added.");

            $admins = $conn->query("SELECT email, username FROM users WHERE role='admin'");
            if ($admins) {
                while ($a = $admins->fetch_assoc()) {
                    if (!empty($a['email'])) {
                        privatehire_send_email($a['email'], $a['username'] ?? 'Admin', 'New Vehicle Added', "Vehicle {$name} has been added.");
                    }
                }
            }
            $message = "Vehicle added successfully.";
        } else {
            $error = "Failed to add vehicle.";
        }
    }
}

if (isset($_POST['update_vehicle'])) {
    $id = (int)($_POST['vehicle_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $seats = (int)($_POST['seats'] ?? 0);
    $pricePerKm = (float)($_POST['price_per_km'] ?? 0);
    $registration = trim($_POST['registration_number'] ?? '');
    $colour = trim($_POST['colour'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');

    if ($id <= 0 || $name === '' || $seats < 1) {
        $error = "Invalid update data.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safeReg = $registration !== '' ? "'" . $conn->real_escape_string($registration) . "'" : "NULL";
        $safeColour = $colour !== '' ? "'" . $conn->real_escape_string($colour) . "'" : "NULL";
        $safeMake = $make !== '' ? "'" . $conn->real_escape_string($make) . "'" : "NULL";
        $safeModel = $model !== '' ? "'" . $conn->real_escape_string($model) . "'" : "NULL";

        $ok = $conn->query("
            UPDATE vehicles
            SET name='{$safeName}', seats={$seats}, price_per_km={$pricePerKm},
                registration_number={$safeReg}, colour={$safeColour}, make={$safeMake}, model={$safeModel}
            WHERE id={$id}
        ");
        if ($ok) {
            privatehire_log_admin_activity($conn, $adminId, 'update', 'vehicle', $id, "Vehicle {$id} updated.");
            $message = "Vehicle updated.";
        } else {
            $error = "Failed to update vehicle.";
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)($_GET['toggle'] ?? 0);
    if ($id > 0) {
        $conn->query("UPDATE vehicles SET active = IF(active=1,0,1) WHERE id={$id}");
        privatehire_log_admin_activity($conn, $adminId, 'toggle_active', 'vehicle', $id, "Vehicle active status toggled.");
        header("Location: vehicles.php");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)($_GET['delete'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM vehicles WHERE id={$id}");
        privatehire_log_admin_activity($conn, $adminId, 'delete', 'vehicle', $id, "Vehicle deleted.");
        header("Location: vehicles.php");
        exit();
    }
}

$editVehicle = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM vehicles WHERE id={$id} LIMIT 1");
    if ($res && $res->num_rows === 1) {
        $editVehicle = $res->fetch_assoc();
    }
}

$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
?>

<div class="container mt-5">
    <h2>Manage Vehicles</h2>
    <?php if ($message !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <form method="POST" class="mb-4 card card-body">
        <input type="hidden" name="vehicle_id" value="<?php echo (int)($editVehicle['id'] ?? 0); ?>">
        <div class="row">
            <div class="col-md-6"><input class="form-control mb-2" name="name" placeholder="Vehicle Name" value="<?php echo htmlspecialchars($editVehicle['name'] ?? ''); ?>" required></div>
            <div class="col-md-3"><input class="form-control mb-2" name="seats" type="number" min="1" placeholder="Seats" value="<?php echo htmlspecialchars((string)($editVehicle['seats'] ?? '')); ?>" required></div>
            <div class="col-md-3"><input class="form-control mb-2" name="price_per_km" type="number" step="0.01" placeholder="Price/Km" value="<?php echo htmlspecialchars((string)($editVehicle['price_per_km'] ?? '')); ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-3"><input class="form-control mb-2" name="registration_number" placeholder="Registration" value="<?php echo htmlspecialchars($editVehicle['registration_number'] ?? ''); ?>"></div>
            <div class="col-md-3"><input class="form-control mb-2" name="colour" placeholder="Colour" value="<?php echo htmlspecialchars($editVehicle['colour'] ?? ''); ?>"></div>
            <div class="col-md-3"><input class="form-control mb-2" name="make" placeholder="Make" value="<?php echo htmlspecialchars($editVehicle['make'] ?? ''); ?>"></div>
            <div class="col-md-3"><input class="form-control mb-2" name="model" placeholder="Model" value="<?php echo htmlspecialchars($editVehicle['model'] ?? ''); ?>"></div>
        </div>
        <?php if ($editVehicle) { ?>
            <button class="btn btn-primary" name="update_vehicle">Update Vehicle</button>
            <a class="btn btn-secondary" href="vehicles.php">Cancel Edit</a>
        <?php } else { ?>
            <button class="btn btn-dark" name="add_vehicle">Add Vehicle</button>
        <?php } ?>
    </form>

    <table class="table table-bordered table-striped">
        <tr>
            <th>Name</th>
            <th>Seats</th>
            <th>Price/Km</th>
            <th>Registration</th>
            <th>Car Details</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $vehicles->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo (int)$row['seats']; ?></td>
                <td><?php echo number_format((float)($row['price_per_km'] ?? 0), 2); ?></td>
                <td><?php echo htmlspecialchars($row['registration_number'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars(trim(($row['colour'] ?? '') . ' ' . ($row['make'] ?? '') . ' ' . ($row['model'] ?? '')) ?: 'N/A'); ?></td>
                <td><?php echo (int)$row['active'] === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Suspended</span>'; ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-primary" href="vehicles.php?edit=<?php echo (int)$row['id']; ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-warning" href="vehicles.php?toggle=<?php echo (int)$row['id']; ?>">Toggle</a>
                    <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this vehicle?');" href="vehicles.php?delete=<?php echo (int)$row['id']; ?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

