<?php
session_start();
include('db_connection.php');
include('functions.php');

if (!isLoggedIn($conn)) {
    header("Location: login.php");
    exit();
}

$userId = intval($_SESSION['id']);

// Récupérer le solde
$balanceQuery = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$balanceQuery->bind_param("i", $userId);
$balanceQuery->execute();
$balanceResult = $balanceQuery->get_result();
$balance = $balanceResult->fetch_assoc()['balance'] ?? 0;

// Gestion d'achat
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symbol'], $_POST['amount'])) {
    $symbol = strtoupper(trim($_POST['symbol']));
    $amount = floatval($_POST['amount']);

    $price = getPrice($conn, $symbol);
    if ($price <= 0) {
        $errors[] = "Crypto invalide ou introuvable.";
    } elseif ($amount <= 0) {
        $errors[] = "Montant non valide.";
    } else {
        $cost = $price * $amount;

        if ($balance >= $cost) {
            // Mise à jour du portefeuille
            $stmt = $conn->prepare("INSERT INTO user_wallet (user_id, symbol, amount)
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)");
            $stmt->bind_param("isd", $userId, $symbol, $amount);
            $stmt->execute();

            // Déduire du solde
            $newBalance = $balance - $cost;
            $updateBalance = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updateBalance->bind_param("di", $newBalance, $userId);
            $updateBalance->execute();

            $success = "Achat de $amount $symbol effectué avec succès !";
            $balance = $newBalance; // Mise à jour locale
        } else {
            $errors[] = "Solde insuffisant pour cet achat.";
        }
    }
}

// Gestion de vente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_symbol'], $_POST['sell_amount'])) {
    $symbol = strtoupper(trim($_POST['sell_symbol']));
    $amount = floatval($_POST['sell_amount']);

    $price = getPrice($conn, $symbol);

    if ($price <= 0) {
        $errors[] = "Crypto invalide ou introuvable.";
    } elseif ($amount <= 0) {
        $errors[] = "Montant non valide.";
    } else {
        // Vérifie si l'utilisateur possède cette crypto
        $checkStmt = $conn->prepare("SELECT amount FROM user_wallet WHERE user_id = ? AND symbol = ?");
        $checkStmt->bind_param("is", $userId, $symbol);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $row = $checkResult->fetch_assoc()) {
            $ownedAmount = $row['amount'];
            if ($ownedAmount >= $amount) {
                $gain = $amount * $price;

                // Calcul de la nouvelle quantité
                $newAmount = round($ownedAmount - $amount, 8);

                // Supprimer si quantité = 0.00000000
                if ($newAmount == 0.00000000) {
                    $deleteWallet = $conn->prepare("DELETE FROM user_wallet WHERE user_id = ? AND symbol = ?");
                    $deleteWallet->bind_param("is", $userId, $symbol);
                    $deleteWallet->execute();
                    $success = "Vente complète : vous avez totalement vendu votre $symbol.";
                } else {
                    // Mettre à jour la quantité
                    $updateWallet = $conn->prepare("UPDATE user_wallet SET amount = ? WHERE user_id = ? AND symbol = ?");
                    $updateWallet->bind_param("dis", $newAmount, $userId, $symbol);
                    $updateWallet->execute();
                    $success = "Vente de $amount $symbol réussie.";
                }

                // Mettre à jour le solde
                $newBalance = $balance + $gain;
                $updateBalance = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $updateBalance->bind_param("di", $newBalance, $userId);
                $updateBalance->execute();

                $balance = $newBalance;
            } else {
                $errors[] = "Vous ne possédez pas suffisamment de $symbol.";
            }
        } else {
            $errors[] = "Vous ne possédez pas cette cryptomonnaie.";
        }
    }
}


// Récupération du portefeuille
$wallet = [];
$query = $conn->prepare("SELECT uw.symbol, uw.amount, cp.name, cp.price, cp.image_url
                         FROM user_wallet uw
                         JOIN crypto_prices cp ON uw.symbol = cp.symbol
                         WHERE uw.user_id = ?");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();

$total = 0;
while ($row = $result->fetch_assoc()) {
    $value = $row['amount'] * $row['price'];
    $row['value'] = $value;
    $total += $value;
    $wallet[] = $row;
}

$cryptosList = getCryptosLite($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon portefeuille</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial; margin: 30px; background-color: #f7f7f7; }
        h2 { text-align: center; }
        .balance { text-align: center; font-size: 1.2em; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: center; }
        img { width: 30px; }
        form { margin-top: 30px; text-align: center; background: #fff; padding: 20px; border-radius: 8px; }
        .success { color: green; text-align: center; }
        .error { color: red; text-align: center; }
        select, input[type=number] { padding: 5px; margin: 10px; }
        header a:hover {text-decoration: underline; }
        button { background-color: #342e92!important; color: white; border: none; padding: 10px 20px; cursor: pointer;  }
        .forms-container {
            display: flex;
            flex-wrap: wrap;      /* passe en colonne si écran trop étroit */
            gap: 1.5rem;          /* espace horizontal et vertical */
        }
  /* chaque bloc prend 50% mini-100% */
        .forms-container .form-block {
            flex: 1 1 45%;
            min-width: 300px;     /* en dessous, ils se superposent verticalement */
        }
        .forms-container .form-block input[type="text"],
        .forms-container .form-block input[type="number"] {
            display: block;      /* pour que margin auto fonctionne */
            width: 80%;          /* ou la largeur que vous préférez */
            margin: 1rem auto;   /* 1rem en haut/bas, auto à gauche/droite */
        }
    </style>
</head>
<body>
<header style="background-color: #342e92; padding: 20px 40px; display: flex; align-items: center; justify-content: space-between;">
    <div class="logo-container" style="display: flex; align-items: center;">
        <a href="index.php">
            <img src="logo.png" alt="Logo" style="width: 50px; height: auto; margin-right: 10px;">
        </a>
        <h1 style="color: white; font-size: 1.5rem; margin: 0;">Mon Portefeuille</h1>
    </div>
    <div>
        <a href="index.php" style="color: white; text-decoration: none; font-weight: bold; font-size: 1rem;">🏠 Accueil</a>
    </div>
</header>


    <h2>Portefeuille de cryptomonnaies</h2>
    <div class="balance">💰 Solde disponible : <strong>$<?= number_format($balance, 2) ?></strong></div>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
        <p class="error"><?= $err ?></p>
    <?php endforeach; ?>

    <table>
        <thead>
            <tr>
                <th>Logo</th>
                <th>Nom</th>
                <th>Symbole</th>
                <th>Quantité</th>
                <th>Prix</th>
                <th>Valeur</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($wallet as $crypto): ?>
                <tr>
                    <td><img src="<?= $crypto['image_url'] ?>"></td>
                    <td><?= htmlspecialchars($crypto['name']) ?></td>
                    <td><?= htmlspecialchars($crypto['symbol']) ?></td>
                    <td><?= $crypto['amount'] ?></td>
                    <td>$<?= number_format($crypto['price'], 2) ?></td>
                    <td>$<?= number_format($crypto['value'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="font-weight:bold;">
                <td colspan="5">Total portefeuille</td>
                <td>$<?= number_format($total, 2) ?></td>
            </tr>
        </tbody>
    </table>
<div class="forms-container">
    <div class="form-block">
        <form method="post" style="margin-bottom: 30px;">
        <h3>🛒 Acheter une cryptomonnaie</h3>
        <label>Choisir une crypto :
            <select name="symbol" id="crypto-select" required>
                <?php foreach ($cryptosList as $crypto): ?>
                    <option value="<?= $crypto['symbol'] ?>" data-price="<?= getPrice($conn, $crypto['symbol']) ?>">
                        <?= $crypto['symbol'] ?> - <?= $crypto['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Montant à acheter :
            <input type="number" step="0.0001" name="amount" id="crypto-amount" required>
        </label>
        <div id="crypto-conversion" style="margin-top: 10px; font-weight: bold;"></div>
        <button type="submit">Acheter</button>
    </form>
    </div>
    <div class="form-block">
    <form method="post">
        <h3>📤 Vendre une cryptomonnaie</h3>
        <label>Choisir une crypto à vendre :
            <select name="sell_symbol" id="sell-select" required>
                <?php foreach ($wallet as $crypto): ?>
                    <option value="<?= $crypto['symbol'] ?>" data-price="<?= $crypto['price'] ?>">
                        <?= $crypto['symbol'] ?> - <?= $crypto['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantité à vendre :
            <input type="number" step="0.0001" name="sell_amount" id="sell-amount" required>
        </label>
        <div id="sell-conversion" style="margin-top: 10px; font-weight: bold;"></div>
        <button type="submit">Vendre</button>
    </form>
    </div>

<script>
function updateConversion(selectId, inputId, outputId) {
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    const output = document.getElementById(outputId);

    function calculate() {
        const option = select.options[select.selectedIndex];
        const price = parseFloat(option.dataset.price);
        const quantity = parseFloat(input.value);
        if (!isNaN(price) && !isNaN(quantity)) {
            const total = price * quantity;
            output.textContent = `💵 Cela correspond à environ : $${total.toFixed(2)}`;
        } else {
            output.textContent = '';
        }
    }

    select.addEventListener("change", calculate);
    input.addEventListener("input", calculate);
}

// Initialiser les convertisseurs
document.addEventListener("DOMContentLoaded", () => {
    updateConversion("crypto-select", "crypto-amount", "crypto-conversion");
    updateConversion("sell-select", "sell-amount", "sell-conversion");
});
</script>

</body>
</html>
