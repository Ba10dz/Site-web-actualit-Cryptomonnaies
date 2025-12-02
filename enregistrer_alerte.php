<?php
session_start();
include 'db_connection.php';

// Récupération des données du formulaire
$symbol    = $_POST['crypto']    ?? '';
$seuilBas  = $_POST['seuilBas']  ?? '';
$seuilHaut = $_POST['seuilHaut'] ?? '';
$email     = $_POST['email']     ?? '';
$userId    = $_SESSION['id']     ?? null;

if ($symbol && $seuilBas && $seuilHaut && $email && $userId) {
    // Vérification que le seuil bas est bien inférieur au seuil haut
    if ($seuilBas >= $seuilHaut) {
        die("❌ Le seuil bas doit être inférieur au seuil haut.");
    }

    // Préparation et exécution de l’insertion
    $stmt = $conn->prepare(
      "INSERT INTO alertes (symbol, seuil_bas, seuil_haut, email, user_id) VALUES (?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        die("Erreur SQL : " . $conn->error);
    }

    $stmt->bind_param("sddsi", $symbol, $seuilBas, $seuilHaut, $email, $userId);
    if ($stmt->execute()) {
        // Redirection vers accueil.php avec flag de succès
        header("Location: accueil.php?alert=success");
        exit;
    } else {
        die("❌ Erreur lors de l’enregistrement : " . $stmt->error);
    }
    $stmt->close();
} else {
    die("❌ Tous les champs sont obligatoires.");
}
?>

