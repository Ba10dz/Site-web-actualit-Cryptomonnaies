<?php
function isLoggedIn($conn) {
    if (isset($_SESSION['id'])) {
        $userId = intval($_SESSION['id']);
        $query = "SELECT status FROM users WHERE id = $userId";
        $result = mysqli_query($conn, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['status'] === 'online';
        }
    }
    return false;
}

function getCryptos($conn) {
    $query = "SELECT * FROM crypto_prices";
    $result = mysqli_query($conn, $query);
    $cryptos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cryptos[] = $row;
    }
    return $cryptos;
}

// ✅ Nouvelle version allégée pour sélection dans un menu déroulant
function getCryptosLite(mysqli $conn): array {
    $cryptos = [];
    $sql = "SELECT symbol, name FROM crypto_prices ORDER BY symbol ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cryptos[] = $row;
        }
    }
    return $cryptos;
}

// ✅ Récupère le prix d’une crypto par son symbole
function getPrice(mysqli $conn, string $symbol): float {
    $stmt = $conn->prepare("SELECT price FROM crypto_prices WHERE symbol = ?");
    if (!$stmt) {
        die("Erreur dans la requête getPrice : " . $conn->error);
    }
    $stmt->bind_param("s", $symbol);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();
    return $price ?? 0;
}



function getIndices($conn) {
    $query = "SELECT * FROM indices";
    $result = mysqli_query($conn, $query);
    $indices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $indices[] = $row;
    }
    return $indices;
}
?>
