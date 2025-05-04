<?php
$servername = "51.83.68.96";
$username = "akkari";
$password = "20051002";
$dbname = "crypto_db";

// Créer la connexion
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Vérifier la connexion
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
