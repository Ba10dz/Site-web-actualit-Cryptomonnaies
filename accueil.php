<?php
session_start();
include('db_connection.php');
include('functions.php');
/**
 * Formate un prix :
 *  - affiche jusqu'à 8 décimales significatives
 *  - supprime les zéros inutiles
 *  - garantit au moins 2 décimales (centièmes)
 */
function format_price($price) {
    // Format sur 8 décimales fixes
    $txt = number_format($price, 8, '.', '');
    // Suppression des zéros et du point superflu
    $txt = rtrim(rtrim($txt, '0'), '.');
    // Garantir au moins 2 décimales
    if (strpos($txt, '.') === false) {
        $txt .= '.00';
    } else {
        $decimales = strlen(substr(strrchr($txt, '.'), 1));
        if ($decimales < 2) {
            $txt .= str_repeat('0', 2 - $decimales);
        }
    }
    return $txt;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>S.B.R.I</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="logo_white.png">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* RESET de base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Roboto', 'Open Sans', sans-serif;
      background-color: #ffffff;
    }

    /****************************************
     *           HEADER / TOP BAR           *
     ****************************************/
    header {
      background-color: #342e92;
      padding: 20px 40px; 
      position: relative;
    }
    .header-container {
      /* Sur desktop : colonne pour séparer la zone top (logo / titre / profil)
         et la zone nav. Sur mobile, on gérera encore plus petit. */
      display: flex;
      flex-direction: column;
      align-items: center;
      max-width: 1200px; /* Si tu veux centrer le tout, sinon retire */
      margin: 0 auto;
    }
    /* Zone top : contient logo, titre, bouton(s) connexion/profil */
    .header-top {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center; /* Titre centré */
      margin-bottom: 20px;
      position: relative; /* Pour positionner logo & boutons */
    }
    /* Logo */
    .header-logo {
      position: absolute;
      left: 0; /* Logo à gauche */
      display: flex;
      align-items: center;
    }
    .header-logo img {
      width: 70px;
      margin-right: 10px;
      position : relative;
      top : 7px; 
      left : -300px; 
    }
    /* Titre centré */
    .header-top h1 {
      font-size: 2rem;
      color: #fff;
      font-weight: 700;
    }
    /* Boutons d'authentification (ou profil) à droite */
    .auth-buttons,
    .profile-menu {
      position: absolute;
      right: 0; /* Aligné à droite */
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
      position: relative;
      top : 7px;
      left : 300px; 
    }
    /* Dropdown menu */
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 70px; /* juste sous l'image de profil */
      right: -325px; 
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
    .profile-menu.show .dropdown-menu {
      display: block;
    }

    /* Navigation en dessous de la zone top */
    nav {
      width: 100%;
      text-align: center;
    }
    nav ul {
      display: inline-flex;
      flex-wrap: wrap; /* Passe à la ligne si manque de place */
      list-style: none;
      padding: 0;
      margin: 0;
      justify-content: center;
    }
    nav ul li {
      margin: 0 15px;
    }
    nav ul li a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      font-size: 1rem;
    }

    /****************************************
     *              MAIN / TABLE            *
     ****************************************/
    main {
      width: 100%;
      padding: 20px;
    }
    .table-container {
      width: 100%;
      overflow-x: auto; 
      margin-bottom: 40px;
    }
    table {
      width: 100%; 
      border-collapse: collapse;
    }
    thead tr {
      background-color: #fff;
      color: black;
    }
    th, td {
      padding: 12px;
      text-align: center;
      border: none;
    }
    tbody tr {
      border-bottom: 1px solid #eee;
    }
    tbody tr:last-child {
      border-bottom: none;
    }
    tbody tr:hover {
      background-color: #dadcf5;
    }
      #popup-success {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #2ecc71;
      color: #fff;
      padding: 12px 24px;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    #popup-success.show {
      opacity: 1;
    }

    /****************************************
     *   MEDIA QUERIES POUR SMARTPHONE      *
     ****************************************/
    @media (max-width: 600px) {
      /* On passe tout en colonne, centré */
      .dropdown-menu {
        display: none;
        position: absolute;
        top: 75px; /* juste sous l'image de profil */
        right: -35px; 
        background-color: #fff;
        border: 1px solid #ddd;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 100;
        min-width: 150px;
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
        left : 150px; 
      }
      .header-logo img {
        width: 60px;
      }
      .header-top h1 {
        margin-bottom: 10px;
        font-size: 2rem;
      }
      /* Les boutons ou le profil descendent sous le titre */
      .auth-buttons,
      .profile-menu {
        position: static; 
        flex-direction: row; /* Les liens côté à côté ou ajuster comme tu veux */
        margin-bottom: 10px;
        justify-content: center;
      }
      .profile-menu img{
        position: relative;
        flex-direction: row; /* Les liens côté à côté ou ajuster comme tu veux */
        margin-bottom: 10px;
        justify-content: center;
        left : 150px;
        top : -110px;
      }
      .auth-buttons a {
        margin: 0 8px; 
      }
      /* Navigation */
      nav ul {
        display: flex;
        flex-direction: column;
      }
      nav ul li {
        margin: 5px 0;
      }
    }
  </style>
</head>
<body>
<?php
  // Affiche le pop-up si on a été redirigé avec ?alert=success
  if (isset($_GET['alert']) && $_GET['alert']==='success'): 
?>
  <div id="popup-success">✅ Alerte enregistrée avec succès !</div>
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const popup = document.getElementById('popup-success');
      popup.classList.add('show');
      setTimeout(() => popup.classList.remove('show'), 3000);
    });
  </script>
<?php 
  endif; 
?>

  <header>
    <div class="header-container">
      <div class="header-top">
        <div class="header-logo">
          <a href="accueil.php"><img src="logo_white.png" alt="Logo"></a>
        </div>
        <h1>S.B.R.I</h1>
        <?php if (isLoggedIn($conn)) {
          $userId = intval($_SESSION['id']);
          $query  = "SELECT profil_img FROM users WHERE id = $userId";
          $result = mysqli_query($conn, $query);
          $profileImg = ($result && $row = mysqli_fetch_assoc($result)) ? $row['profil_img'] : 'default_profile.png';
        ?>
        <div class="profile-menu">
          <img src="<?php echo $profileImg; ?>" alt="Profil">
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

  <main>
    <section id="cryptos">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Classement</th>
              <th>Logo</th>
              <th>Symbole</th>
              <th>Nom</th>
              <th>Prix actuel</th>
              <th>Changement 1h</th>
              <th>Changement 24h</th>
              <th>Changement 7j</th>
              <th>Graphique</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cryptos = getCryptos($conn);
            $rank = 1;
            foreach ($cryptos as $crypto) {
                $change1h  = $crypto['change_1h'];
                $change24h = $crypto['change_24h'];
                $change7d  = $crypto['change_7d'];
                $color1h   = ($change1h > 0) ? 'green' : (($change1h < 0) ? '#e33f24' : 'black');
                $color24h  = ($change24h > 0) ? 'green' : (($change24h < 0) ? '#e33f24' : 'black');
                $color7d   = ($change7d > 0) ? 'green' : (($change7d < 0) ? '#e33f24' : 'black');
                echo "<tr onclick=\"window.location.href='{$crypto['CryptoPage']}'\" style='cursor:pointer;'>";
                echo "<td>{$rank}</td>";
                echo "<td><img src='{$crypto['image_url']}' alt='{$crypto['symbol']}' style='width:40px;height:40px;'></td>";
                echo "<td>{$crypto['symbol']}</td>";
                echo "<td>{$crypto['name']}</td>";
                echo '<td>$'.format_price($crypto['price']).'</td>';
                echo "<td style='color:{$color1h};'>{$change1h}%</td>";
                echo "<td style='color:{$color24h};'>{$change24h}%</td>";
                echo "<td style='color:{$color7d};'>{$change7d}%</td>";
                $symbolLower = strtolower($crypto['symbol']);
                $imgUrl = "http://51.83.68.96/home/TS2/BOURSE/siteamelioration/graphiqueaccueil/{$symbolLower}.png";
                echo "<td><img src='{$imgUrl}' alt='Graphique {$crypto['symbol']}' style='width:80px;height:40px;'></td>";
                echo "</tr>";
                $rank++;
            }
            ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var profileMenu = document.querySelector('.profile-menu');
      if(profileMenu){
        profileMenu.addEventListener('click', function(e){
          e.stopPropagation();
          this.classList.toggle('show');
        });
        document.addEventListener('click', function(){
          profileMenu.classList.remove('show');
        });
      }
    });
  </script>
</body>
</html>
