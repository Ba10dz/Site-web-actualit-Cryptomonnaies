<?php
session_start();
require_once 'db_connection.php'; // Remplace par le nom de ton fichier de connexion à la BDD

if (isset($_SESSION['id'])) {
    $userId = intval($_SESSION['id']);

    // Connexion à la base de données
    $conn = $GLOBALS['conn']; // si $conn est global, sinon utilise ta méthode

    // Mettre à jour le statut
    $query = "UPDATE users SET status = 'disconnected' WHERE id = $userId";
    mysqli_query($conn, $query);
}

// Détruire la session
session_unset();
session_destroy();

// Rediriger vers la page de login
header("Location: index.php");
exit;
