<?php
include "config/db.php";
include "navbar.php";

if(isset($_POST['send'])){

$name=$_POST['name'];
$email=$_POST['email'];
$message=$_POST['message'];

$conn->query("INSERT INTO enquiries(name,email,message)
VALUES('$name','$email','$message')");

echo "<div class='alert alert-success'>Message Sent</div>";
}
?>

<div class="container mt-5">

<h2>Contact Us</h2>

<form method="POST">

<input class="form-control mb-3" name="name" placeholder="Full Name">

<input class="form-control mb-3" name="email" placeholder="Email">

<textarea class="form-control mb-3" name="message"></textarea>

<button class="btn btn-dark" name="send">Submit</button>

</form>

</div>