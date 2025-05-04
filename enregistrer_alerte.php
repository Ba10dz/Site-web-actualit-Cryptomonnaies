<?php
session_start();
include 'db_connection.php';

$symbol = $_POST['crypto'] ?? '';
$seuilBas = $_POST['seuilBas'] ?? '';
$seuilHaut = $_POST['seuilHaut'] ?? '';
$email = $_POST['email'] ?? '';
$userId = $_SESSION['id'] ?? null;

if ($symbol && $seuilBas && $seuilHaut && $email && $userId) {
    if ($seuilBas >= $seuilHaut) {
        die("❌ Le seuil bas doit être inférieur au seuil haut.");
    }

    $stmt = $conn->prepare("INSERT INTO alertes (symbol, seuil_bas, seuil_haut, email, user_id) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Erreur dans la requête SQL : " . $conn->error);
    }
    $stmt->bind_param("sddsi", $symbol, $seuilBas, $seuilHaut, $email, $userId);
    if ($stmt->execute()) {
        echo "✅ Alerte enregistrée avec succès !";
    } else {
        echo "❌ Erreur : " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "❌ Tous les champs sont obligatoires.";
}
