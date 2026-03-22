<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

if($_SESSION['role'] != 'admin'){
die("Access denied");
}

if(isset($_POST['add_vehicle'])){

$name=$_POST['name'];
$seats=$_POST['seats'];

$conn->query("INSERT INTO vehicles(name,seats)
VALUES('$name','$seats')");
}
?>

<div class="container mt-5">

<h2>Manage Vehicles</h2>

<form method="POST" class="mb-4">

<input class="form-control mb-2" name="name" placeholder="Vehicle Name">

<input class="form-control mb-2" name="seats" placeholder="Seats">

<button class="btn btn-dark" name="add_vehicle">Add Vehicle</button>

</form>

<table class="table">

<tr>
<th>Vehicle</th>
<th>Seats</th>
</tr>

<?php

$result=$conn->query("SELECT * FROM vehicles");

while($row=$result->fetch_assoc()){

echo "<tr>
<td>".$row['name']."</td>
<td>".$row['seats']."</td>
</tr>";

}

?>

</table>

</div>