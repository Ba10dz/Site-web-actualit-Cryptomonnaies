<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/vendor/PHPMailer/PHPMailer-6.8.0/src/Exception.php';
require_once __DIR__.'/vendor/PHPMailer/PHPMailer-6.8.0/src/PHPMailer.php';
require_once __DIR__.'/vendor/PHPMailer/PHPMailer-6.8.0/src/SMTP.php';

/**
 * Récupère le prix d'une crypto depuis CoinGecko en USD.
 */
function getCryptoPrice(string $crypto): float {
    $url = "https://api.coingecko.com/api/v3/simple/price?ids={$crypto}&vs_currencies=usd";
    $json = @file_get_contents($url);
    if (!$json) return -1;
    $data = json_decode($json, true);
    return $data[$crypto]['usd'] ?? -1;
}

$coinMap = [
    'btc'   => 'bitcoin',
    'eth'   => 'ethereum',
    'xrp'   => 'ripple',
    'usdc'  => 'usd-coin',
    'usdt'  => 'tether',
    'seth'  => 'seth',
    'sol'   => 'solana',
    'doge'  => 'dogecoin',
    'bnb'   => 'binancecoin',
    'trx'   => 'tron'
];

// Si le paramètre "check" est présent, on effectue une vérification AJAX
if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    if (isset($_SESSION['crypto'], $_SESSION['seuilBas'], $_SESSION['seuilHaut'], $_SESSION['email'])) {
        $crypto = $_SESSION['crypto'];
        $seuilBas = $_SESSION['seuilBas'];
        $seuilHaut = $_SESSION['seuilHaut'];
        $email = $_SESSION['email'];
        $price = getCryptoPrice($crypto);
        if ($price < 0 && isset($_SESSION['last_price'])) {
            $price = $_SESSION['last_price'];
        }
        if ($price >= 0) {
            $_SESSION['last_price'] = $price;
        }
        $alertMsg = null;
        if ($price >= 0) {
            if ($price <= $seuilBas) {
                $alertMsg = "🚨 Prix ≤ seuil bas ({$seuilBas} USD)";
            } elseif ($price >= $seuilHaut) {
                $alertMsg = "🚨 Prix ≥ seuil haut ({$seuilHaut} USD)";
            }
        }
        // Envoi de l'email si seuil atteint et si aucun mail n'a été envoyé encore
        if ($alertMsg && !isset($_SESSION['alert_sent'])) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'sbri.crypto@gmail.com';
                $mail->Password   = 'jipm olse wzat cclv'; // Remplace par ton mot de passe d'application
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->setFrom('sbri.crypto@gmail.com','Crypto Alert');
                $mail->addAddress($email);
                $mail->Subject = 'Alerte Crypto';
                $mail->Body    = "{$alertMsg}\nPrix actuel : {$price} USD";
                $mail->send();
                $_SESSION['alert_sent'] = true;
            } catch (Exception $e) {
                // Optionnel : enregistrer l'erreur dans les logs
            }
        }
        echo json_encode(['price' => $price, 'alert' => $alertMsg]);
    } else {
        echo json_encode(['error' => "Paramètres non définis"]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Seuil Alerte Crypto</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 2rem auto; }
        form { display: grid; gap: 1rem; }
        input, select, button { padding: 0.5rem; font-size: 1rem; width: 100%; }
        .alert { padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<h1>Paramétrage d’alerte Crypto</h1>
<form method="post">
    <label>Choisissez votre crypto:
        <select name="crypto" required>
            <?php foreach($coinMap as $abbr => $id): ?>
                <option value="<?= htmlspecialchars($abbr) ?>" <?= (($_POST['crypto'] ?? '') === $abbr) ? 'selected' : '' ?>>
                    <?= strtoupper(htmlspecialchars($abbr)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <p>Prix actuel : <span id="currentPrice">–</span> USD</p>
    <label>Seuil bas (USD):
        <input type="number" step="0.01" name="seuilBas" required value="<?= htmlspecialchars($_POST['seuilBas'] ?? '') ?>">
    </label>
    <label>Seuil haut (USD):
        <input type="number" step="0.01" name="seuilHaut" required value="<?= htmlspecialchars($_POST['seuilHaut'] ?? '') ?>">
    </label>
    <label>Entrez votre email:
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label>
    <button type="submit">Valider</button>
</form>

<?php
// Si le formulaire est soumis, on stocke les paramètres dans la session et affiche le prix actuel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $abbr = $_POST['crypto'];
    $coinId = $coinMap[$abbr];
    $_SESSION['crypto']    = $coinId;
    $_SESSION['seuilBas']  = floatval($_POST['seuilBas']);
    $_SESSION['seuilHaut'] = floatval($_POST['seuilHaut']);
    $_SESSION['email']     = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    $price = getCryptoPrice($coinId);
    if ($price < 0 && isset($_SESSION['last_price'])) {
        $price = $_SESSION['last_price'];
    }
    if ($price >= 0) {
        $_SESSION['last_price'] = $price;
    }
    
    echo "<p>Prix actuel de <strong>" . strtoupper($abbr) . "</strong> : " . number_format($price, 2, ',', ' ') . " USD</p>";
}
?>

<script>
const coinMap = <?= json_encode($coinMap) ?>;
async function updatePrice(){
    const sel = document.querySelector('select[name="crypto"]');
    const id = coinMap[sel.value];
    try {
        const res = await fetch(`https://api.coingecko.com/api/v3/simple/price?ids=${id}&vs_currencies=usd`);
        const data = await res.json();
        document.getElementById('currentPrice').innerText = data[id].usd.toFixed(2);
    } catch (e) { }
}
document.querySelector('select[name="crypto"]').addEventListener('change', updatePrice);
window.addEventListener('load', updatePrice);

// Mise en place d'un polling toutes les 60 secondes pour vérifier si le seuil est atteint
setInterval(async () => {
    const sel = document.querySelector('select[name="crypto"]');
    const id = coinMap[sel.value];
    try {
        const res = await fetch(`?check=1`);
        const result = await res.json();
        if(result.alert) {
            alert(result.alert);
            // Optionnel : arrêter le polling ou réinitialiser le flag
        }
    } catch (e) { }
}, 60000);
</script>
</body>
</html>

