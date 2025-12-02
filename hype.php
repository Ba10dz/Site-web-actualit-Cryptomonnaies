<?php
session_start();
include_once("db_connection.php");
include_once("functions.php");

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn($conn)) {
    header("Location: login.php");
    exit();
}
$userId = intval($_SESSION['id']);

// Récupérer le solde de l'utilisateur
$balanceQuery = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$balanceQuery->bind_param("i", $userId);
$balanceQuery->execute();
$balanceResult = $balanceQuery->get_result();
$balance = $balanceResult->fetch_assoc()['balance'] ?? 0;

// Symbole de la crypto fixe
$symbol = "HYPE";

// Récupérer les données de Bitcoin
$query = "SELECT * FROM crypto_prices WHERE symbol = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $symbol);
$stmt->execute();
$result = $stmt->get_result();
$crypto = $result->fetch_assoc();
if (!$crypto) {
    echo "Cryptomonnaie HYPE non trouvée.";
    exit;
}

// Messages d'erreurs et succès
$errors = [];
$success = null;

// Gestion d'achat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_amount'])) {
    $amount = floatval($_POST['buy_amount']);
    $price = getPrice($conn, $symbol);
    if ($price <= 0) {
        $errors[] = "Prix introuvable pour HYPE.";
    } elseif ($amount <= 0) {
        $errors[] = "Montant d'achat non valide.";
    } else {
        $cost = $price * $amount;
        if ($balance >= $cost) {
            $stmtIns = $conn->prepare(
                "INSERT INTO user_wallet (user_id, symbol, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)"
            );
            $stmtIns->bind_param("isd", $userId, $symbol, $amount);
            $stmtIns->execute();

            $balance -= $cost;
            $updBal = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updBal->bind_param("di", $balance, $userId);
            $updBal->execute();

            $success = "Achat de $amount HYPE effectué avec succès !";
        } else {
            $errors[] = "Solde insuffisant. Coût total: $" . number_format($cost, 2);
        }
    }
}

// Gestion de vente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_amount'])) {
    $amount = floatval($_POST['sell_amount']);
    $price = getPrice($conn, $symbol);
    if ($price <= 0) {
        $errors[] = "Prix introuvable pour HYPE.";
    } elseif ($amount <= 0) {
        $errors[] = "Montant de vente non valide.";
    } else {
        $check = $conn->prepare("SELECT amount FROM user_wallet WHERE user_id = ? AND symbol = ?");
        $check->bind_param("is", $userId, $symbol);
        $check->execute();
        $res = $check->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['amount'] >= $amount) {
                $gain = $price * $amount;
                $remaining = round($row['amount'] - $amount, 8);
                if ($remaining <= 0) {
                    $del = $conn->prepare("DELETE FROM user_wallet WHERE user_id = ? AND symbol = ?");
                    $del->bind_param("is", $userId, $symbol);
                    $del->execute();
                    $success = "Vous avez vendu entièrement vos HYPE.";
                } else {
                    $upd = $conn->prepare("UPDATE user_wallet SET amount = ? WHERE user_id = ? AND symbol = ?");
                    $upd->bind_param("dis", $remaining, $userId, $symbol);
                    $upd->execute();
                    $success = "Vente de $amount HYPE réussie.";
                }
                $balance += $gain;
                $updBal2 = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $updBal2->bind_param("di", $balance, $userId);
                $updBal2->execute();
            } else {
                $errors[] = "Quantité insuffisante (vous possédez {$row['amount']} HYPE).";
            }
        } else {
            $errors[] = "Vous ne possédez aucun HYPE.";
        }
    }
}

// Récupération de la quantité détenue
$holdings = 0;
$chk2 = $conn->prepare("SELECT amount FROM user_wallet WHERE user_id = ? AND symbol = ?");
$chk2->bind_param("is", $userId, $symbol);
$chk2->execute();
$res2 = $chk2->get_result();
if ($res2 && $row2 = $res2->fetch_assoc()) {
    $holdings = $row2['amount'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hyperliquid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin:0; padding:0; }
        body { font-family: Arial, sans-serif; background:#f7f7f7; }
        header { background:#342e92; color:#fff; padding:20px 40px; display:flex; align-items:center; justify-content:space-between; }
        header a { color:#fff; text-decoration:none; font-weight:bold; }
        .balance { font-size:1em; }
        .main { display:flex; max-width:1200px; margin:30px auto; gap:20px; }
        .info-section, .trade-section { background:#fff; border-radius:8px; padding:20px; flex:1; }
        .info-section table { width:100%; margin-bottom:20px; }
        .info-section td { padding:8px; }
        .info-section img { max-width:100%; border-radius:8px; }
        form { margin-top:20px; }
        form h3 { margin-bottom:10px; }
        input[type=number] { width:100%; padding:8px; margin-bottom:10px; }
        button { width:100%; padding:10px; background:#342e92; color:#fff; border:none; border-radius:4px; cursor:pointer; }
        .success { color:green; text-align:center; margin-bottom:10px; }
        .error { color:red; text-align:center; margin-bottom:10px; }
    </style>
</head>
<body>
<header>
    <a href="accueil.php">🏠 Accueil</a>
    <h1>Hyperliquid</h1>
    <div class="balance">💰 Solde: $<?php echo number_format($balance,2); ?></div>
</header>
<div class="main">
    <div class="info-section">
        <?php if ($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>
        <?php foreach($errors as $err): ?><p class="error"><?php echo $err; ?></p><?php endforeach; ?>
        <table>
            <tr><td>Prix actuel:</td><td>$<?php echo number_format($crypto['price'],2); ?></td></tr>
            <tr><td>Changement 1h:</td><td><?php echo $crypto['change_1h']; ?>%</td></tr>
            <tr><td>Changement 24h:</td><td><?php echo $crypto['change_24h']; ?>%</td></tr>
            <tr><td>Changement 7j:</td><td><?php echo $crypto['change_7d']; ?>%</td></tr>
        </table>
        <div><h2>Graphique</h2><img src="<?php echo htmlspecialchars($crypto['graph_url']); ?>" alt="Graph HYPE"></div>
    </div>
    <div class="trade-section">
        <form method="post">
            <h3>🛒 Acheter </h3>
            <input type="number" name="buy_amount" step="0.0001" placeholder="Quantité" required>
            <div>Total estimé: $<span id="buy-total">0.00</span></div>
            <button type="submit">Acheter</button>
        </form>
        <form method="post">
            <h3>📤 Vendre </h3>
            <input type="number" name="sell_amount" step="0.0001" max="<?php echo $holdings; ?>" placeholder="Quantité (max <?php echo $holdings; ?>)" required>
            <div>Revenu estimé: $<span id="sell-total">0.00</span></div>
            <button type="submit">Vendre</button>
        </form>
    </div>
</div>
<script>
    const price = <?php echo $crypto['price']; ?>;
    document.querySelector('input[name="buy_amount"]').addEventListener('input', e => {
        document.getElementById('buy-total').textContent = (price * parseFloat(e.target.value || 0)).toFixed(2);
    });
    document.querySelector('input[name="sell_amount"]').addEventListener('input', e => {
        document.getElementById('sell-total').textContent = (price * parseFloat(e.target.value || 0)).toFixed(2);
    });
</script>
</body>
</html>
