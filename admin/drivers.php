<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

if($_SESSION['role'] != 'admin'){
die("Access denied");
}

if(isset($_POST['add_driver'])){

$name=$_POST['name'];
$phone=$_POST['phone'];
$vehicle=$_POST['vehicle'];

$conn->query("INSERT INTO drivers(name,phone,vehicle_id)
VALUES('$name','$phone','$vehicle')");
}
?>

<div class="container mt-5">

<h2>Manage Drivers</h2>

<form method="POST" class="mb-4">

<input class="form-control mb-2" name="name" placeholder="Driver Name">

<input class="form-control mb-2" name="phone" placeholder="Phone">

<select class="form-control mb-2" name="vehicle">

<?php
$v=$conn->query("SELECT * FROM vehicles");
while($row=$v->fetch_assoc()){
echo "<option value='".$row['id']."'>".$row['name']."</option>";
}
?>

</select>

<button class="btn btn-dark" name="add_driver">Add Driver</button>

</form>

<table class="table">

<tr>
<th>Name</th>
<th>Phone</th>
<th>Vehicle</th>
</tr>

<?php

$sql="SELECT drivers.*, vehicles.name AS vehicle
FROM drivers
LEFT JOIN vehicles ON drivers.vehicle_id=vehicles.id";

$result=$conn->query($sql);

while($row=$result->fetch_assoc()){

echo "<tr>
<td>".$row['name']."</td>
<td>".$row['phone']."</td>
<td>".$row['vehicle']."</td>
</tr>";

}

?>

</table>

</div>