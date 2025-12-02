<?php
include 'db_connection.php';
include 'envoyer_mail.php'; // 📩 Fichier contenant la fonction PHPMailer

$sql = "SELECT * FROM alertes";
$result = mysqli_query($conn, $sql);

while ($alerte = mysqli_fetch_assoc($result)) {
    $symbol = $alerte['symbol'];
    $seuilBas = $alerte['seuil_bas'];
    $seuilHaut = $alerte['seuil_haut'];
    $email = $alerte['email'];
    $id = $alerte['id'];

    // 🔄 Récupérer le prix actuel depuis la table crypto_prices
    $stmt = $conn->prepare("SELECT price FROM crypto_prices WHERE symbol = ?");
    $stmt->bind_param("s", $symbol);
    $stmt->execute();
    $stmt->bind_result($priceActuel);
    $stmt->fetch();
    $stmt->close();

    if (!$priceActuel) {
        continue; // 🔁 On passe à l'alerte suivante si prix non dispo
    }

    // 🔔 Vérifier si l’un des seuils est atteint
    if ($priceActuel <= $seuilBas) {
        $mailEnvoye = envoyerMailAlerte($email, $symbol, $priceActuel, 'bas', $seuilBas);
    } elseif ($priceActuel >= $seuilHaut) {
        $mailEnvoye = envoyerMailAlerte($email, $symbol, $priceActuel, 'haut', $seuilHaut);
    } else {
        $mailEnvoye = false;
    }

    // ✅ Si mail envoyé, on supprime l’alerte de la BDD
    if ($mailEnvoye) {
        $delete = $conn->prepare("DELETE FROM alertes WHERE id = ?");
        $delete->bind_param("i", $id);
        $delete->execute();
        $delete->close();
    }
}
?>

