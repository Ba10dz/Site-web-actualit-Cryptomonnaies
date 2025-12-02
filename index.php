<?php
session_start();
include('db_connection.php');
include('functions.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Projet Salle des Marchés - Index</title>
  <style>
    :root {
      --primary: #342e92;
      --light: #f9f9f9;
      --dark: #1e1e2f;
      --white: #fff;
      --font: 'Segoe UI', Tahoma, sans-serif;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: var(--font); color: var(--white); background: var(--dark); line-height: 1.6; }
    header { background-color: var(--primary); padding: 20px 40px; position: relative; }
    .header-container { display: flex; flex-direction: column; align-items: center; max-width: 1200px; margin: auto; }
    .header-top { width: 100%; display: flex; align-items: center; justify-content: center; position: relative; margin-bottom: 20px; }
    .header-logo { position: absolute; left: 0; display: flex; align-items: center; }
    .header-logo img { height: 50px; }
    .auth-buttons, .profile-menu { position: absolute; right: 0; display: flex; flex-direction: column; align-items: flex-end; }
    .auth-buttons a { color: #fff; text-decoration: none; font-weight: bold; margin: 5px 0; }
    .profile-menu img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; cursor: pointer; }
    .dropdown-menu { display: none; position: absolute; top: 70px; right: 0; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .dropdown-menu a { display: block; padding: 10px 20px; color: #333; text-decoration: none; white-space: nowrap; }
    .dropdown-menu a:hover { background-color: #f0f0f0; }
    .profile-menu.show .dropdown-menu { display: block; }
    nav ul { list-style: none; display: inline-flex; flex-wrap: wrap; }
    nav ul li { margin: 0 15px; }
    nav ul li a { color: var(--white); text-decoration: none; font-weight: 500; }
    #hero { display: flex; justify-content: center; align-items: center; padding: 60px 20px; position: relative; overflow: hidden; }
    .phone-frame { width: 300px; height: auto; border: 16px solid #000; border-radius: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); overflow: hidden; background: #000; }
    .phone-frame img { display: block; width: 100%; height: auto; }
    section { padding: 80px 20px; position: relative; }
    section:nth-of-type(odd) { background: var(--dark); }
    section:nth-of-type(even) { background: #2b2b3f; }
    h2 { text-align: center; margin-bottom: 40px; font-size: 2rem; color: var(--light); }
    p { max-width: 800px; margin: 0 auto 20px; color: var(--light); }
    .team { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap: 40px; }
    .member { background: #3a3a5c; padding: 20px; border-radius: 16px; position: relative; overflow: hidden; }
    .member h3 { margin-bottom: 15px; font-size: 1.5rem; }
    .member ul { list-style: disc inside; }
    .member li { margin-bottom: 8px; }
    .parallax { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; transform: translateY(0); }
  </style>
</head>
<body>
  <header>
    <div class="header-container">
      <div class="header-top">
        <div class="header-logo"><a href="index.php"><img src="logo_white.png" alt="Logo Projet"></a></div>
        <h1>Projet Technique BTS IR - Salle des Marchés</h1>
        <?php if (isLoggedIn($conn)): 
          $userId = intval($_SESSION['id']);
          $query  = "SELECT profil_img FROM users WHERE id = $userId";
          $result = mysqli_query($conn, $query);
          $profileImg = ($result && $row = mysqli_fetch_assoc($result)) ? $row['profil_img'] : 'default_profile.png';
        ?>
        <div class="profile-menu" onclick="this.classList.toggle('show')">
          <img src="images/<?php echo htmlspecialchars($profileImg); ?>" alt="Profil">
          <div class="dropdown-menu">
            <a href="profil.php">Modifier le profil</a>
            <a href="portefeuille.php">Portefeuille</a>
            <a href="alerte.php">Notifications</a>
            <a href="logout.php">Se déconnecter</a>
          </div>
        </div>
        <?php else: ?>
        <div class="auth-buttons">
          <a href="login.php">Se connecter</a>
          <a href="register.php">S'inscrire</a>
        </div>
        <?php endif; ?>
      </div>
      <nav>
        <ul>
          <li><a href="#presentation">Présentation</a></li>
          <li><a href="#taches">Tâches</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <section id="hero">
    <div class="phone-frame">
      <img src="image/ui_phone.gif" alt="Interface mobile du projet">
    </div>
  </section>

  <section id="presentation">
    <h2>Présentation du projet</h2>
    <p>Le projet consiste à mettre en place un système d'affichage automatique sur écrans de télévision 
       des cours des cryptomonnaies, pour assister les courtiers dans une salle de marchés. Il inclut la récupération de données en temps réel, la 
       configuration d'alertes, la sécurisation des accès, et la génération de graphiques de suivi.
    </p>
    <img class="parallax" data-speed="0.2" src="image/bg-finance.jpg" alt="">
  </section>

  <section id="taches">
    <h2>Répartition des tâches</h2>
    <div class="team">
      <!-- Membre 1 -->
      <div class="member">
        <h3>Étudiant 1</h3>
        <ul>
          <li>Rédaction du cahier des charges & UML</li>
          <li>Prise en main du capteur bourse</li>
          <li>Script récupération & formatage</li>
          <li>Environnements Python</li>
          <li>Application stockage</li>
          <li>Recette & validation</li>
        </ul>
        <img class="parallax" data-speed="0.1" src="image/student1.jpg" alt="Étudiant 1">
      </div>
      <!-- Membre 2 -->
      <div class="member">
        <h3>Étudiant 2</h3>
        <ul>
          <li>UML & planification</li>
          <li>Graphiques d'alerte</li>
          <li>Comparaison périodique</li>
          <li>Envoi messages serveur</li>
          <li>Module SQL client</li>
          <li>Dev programme client</li>
          <li>Recette & validation</li>
        </ul>
        <img class="parallax" data-speed="0.1" src="image/student2.png" alt="Étudiant 2">
      </div>
      <!-- Membre 3 -->
      <div class="member">
        <h3>Étudiant 3</h3>
        <ul>
          <li>Cahier des charges UML</li>
          <li>Paramétrage écrans HDMI</li>
          <li>Programme affichage messages</li>
          <li>Environnements PHP-HTML/CSS</li>
          <li>Dev serveur d'affichage</li>
          <li>Module graphique affichage</li>
          <li>Recette & validation</li>
        </ul>
        <img class="parallax" data-speed="0.1" src="image/student3.png" alt="Étudiant 3">
      </div>
      <!-- Membre 4 -->
      <div class="member">
        <h3>Étudiant 4</h3>
        <ul>
          <li>Installation serveur LAMP</li>
          <li>Création & intégrité tables</li>
          <li>Jeu d'essais BD</li>
          <li>Environnements Python</li>
          <li>Application graphique clients</li>
          <li>Gestion portefeuilles</li>
          <li>Recette & validation</li>
        </ul>
        <img class="parallax" data-speed="0.1" src="image/student4.jpg" alt="Étudiant 4">
      </div>
    </div>
  </section>

  <section id="contact">
    <h2>Contact</h2>
    <p>Lycée LT Bergson, Paris 19
    </p>
    <img class="parallax" data-speed="0.15" src="images/bg-contact.jpg" alt="Contact">
  </section>

  <script>
    // Effet parallax au scroll
    document.addEventListener('scroll', function() {
      document.querySelectorAll('.parallax').forEach(function(el) {
        const speed = parseFloat(el.getAttribute('data-speed')) || 0;
        el.style.transform = 'translateY(' + (window.scrollY * speed) + 'px)';
      });
    });
  </script>
</body>
</html>
