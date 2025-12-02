<?php
session_start();
include 'db_connection.php';
include 'functions.php';

if (!isLoggedIn($conn)) {
    header("Location: login.php");
    exit;
}

$cryptos = getCryptosLite($conn);
$selectedSymbol = $_GET['crypto'] ?? 'BTC';
$selectedPrice = getPrice($conn, $selectedSymbol);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer une alerte</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
    header {
      background-color: #342e92;
      padding: 20px 40px;
      position: relative;
    }
    .header-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
    }
    .header-top {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      position: relative;
    }
    .header-logo {
      position: absolute;
      left: 0;
      display: flex;
      align-items: center;
    }
    .header-logo img {
      width: 70px;
      margin-right: 10px;
      position: relative;
      top: 7px;
    }
    .header-top h1 {
      font-size: 2rem;
      color: #fff;
      font-weight: 700;
    }
    .auth-buttons,
    .profile-menu {
      position: absolute;
      right: 0;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }
    .auth-buttons a {
      color: #fff;
      text-decoration: none;
      font-weight: bold;
      margin: 5px 0;
    }
    .profile-menu img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 70px;
      right: 0;
      background-color: #fff;
      border: 1px solid #ddd;
      box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
      z-index: 100;
      min-width: 150px;
    }
    .dropdown-menu a {
      display: block;
      padding: 10px;
      text-decoration: none;
      color: #333;
      font-weight: 500;
    }
    .dropdown-menu a:hover {
      background-color: #dadcf5;
    }
    .dropdown-menu.active {
      display: block;
    }

    nav ul li {
      display: inline-block;
      margin: 0 10px;
    }
    nav ul li a {
      text-decoration: none;
      color: #333;
    }

    .container {
      padding: 30px;
      max-width: 500px;
      margin: 0 auto;
    }

    label { display: block; margin-top: 15px; }
    input, select, button {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      box-sizing: border-box;
    }
    button { margin-top: 20px; background-color: #4CAF50; color: white; border: none; }

    @media (max-width: 600px) {
      .dropdown-menu {
        right: 0;
      }
      .header-top {
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin-bottom: 10px;
      }
      .header-logo {
        position: relative;
        margin-bottom: 10px;
      }
      .header-logo img {
        width: 60px;
      }
      .header-top h1 {
        margin-bottom: 10px;
        font-size: 2rem;
      }
      .auth-buttons,
      .profile-menu {
        position: static;
        flex-direction: row;
        margin-bottom: 10px;
        justify-content: center;
      }
      .profile-menu {
        justify-content: center;
        align-items: center;
        width: 100%;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="header-container">
    <div class="header-top">
      <div class="header-logo">
        <a href="accueil.php">
          <img src="logo.png" alt="Logo">
        </a>
      </div>
      <h1>S.B</h1>
      <?php if (isLoggedIn($conn)) {
        $userId = intval($_SESSION['id']);
        $query  = "SELECT profil_img FROM users WHERE id = $userId";
        $result = mysqli_query($conn, $query);
        $profileImg = ($result && $row = mysqli_fetch_assoc($result)) ? $row['profil_img'] : "default_profile.png";
      ?>
        <div class="profile-menu">
          <img src="<?php echo $profileImg; ?>" alt="Profil" class="profile-icon">
          <div class="dropdown-menu">
            <a href="profil.php">Modifier le profil</a>
            <a href="portefeuille.php">Portefeuille</a>
            <a href="alerte.php">Notification</a>
            <a href="logout.php">Se déconnecter</a>
          </div>
        </div>
      <?php } else { ?>
        <div class="auth-buttons">
          <a href="login.php">Se connecter</a>
          <a href="register.php">S'inscrire</a>
        </div>
      <?php } ?>
    </div>
  </div>
</header>

<div class="container">
  <h2>Paramétrage d’alerte Crypto</h2>
  <form method="POST" action="enregistrer_alerte.php">
    <label for="crypto">Choisissez votre crypto :</label>
    <select id="crypto" name="crypto" onchange="location = '?crypto=' + this.value;">
      <?php foreach ($cryptos as $crypto): ?>
        <option value="<?= htmlspecialchars($crypto['symbol']) ?>" <?= $crypto['symbol'] === $selectedSymbol ? 'selected' : '' ?>>
          <?= htmlspecialchars($crypto['symbol']) ?> - <?= htmlspecialchars($crypto['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <p>Prix actuel : <strong><?= number_format($selectedPrice, 2) ?> USD</strong></p>

    <label for="seuilBas">Seuil bas (USD):</label>
    <input type="number" step="0.01" name="seuilBas" id="seuilBas" required>

    <label for="seuilHaut">Seuil haut (USD):</label>
    <input type="number" step="0.01" name="seuilHaut" id="seuilHaut" required>

    <label for="email">Votre adresse email :</label>
    <input type="email" name="email" id="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
    <button type="submit">Créer l'alerte</button>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const profileIcon = document.querySelector('.profile-icon');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (profileIcon && dropdownMenu) {
      profileIcon.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('active');
      });

      document.addEventListener('click', function () {
        dropdownMenu.classList.remove('active');
      });
    }
  });
</script>

</body>
</html>
