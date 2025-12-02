<?php
session_start();
include('db_connection.php');
include('functions.php');
// Si déjà connecté, redirection
if (isLoggedIn($conn)) {
    header('Location: accueil.php');
    exit;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['isLoggedIn'] = true;

        // 🔥 Mise à jour du statut dans la base de données
        $userId = intval($user['id']);
        $updateQuery = "UPDATE users SET status = 'online' WHERE id = $userId";
        mysqli_query($conn, $updateQuery);

        header('Location: accueil.php');
        exit;
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion - Salle des Marchés</title>
  <style>
    :root {
      --primary: #342e92;
      --light: #f9f9f9;
      --dark: #1e1e2f;
      --white: #fff;
      --font: 'Segoe UI', Tahoma, sans-serif;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: var(--font); background: var(--dark); color: var(--white); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .container { background: #2b2b3f; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); width: 100%; max-width: 400px; }
    h2 { text-align: center; margin-bottom: 20px; color: var(--light); }
    form { display: flex; flex-direction: column; }
    label { margin-bottom: 8px; font-size: 0.9rem; }
    input[type="username"], input[type="password"] { padding: 10px; margin-bottom: 20px; border: none; border-radius: 8px; }
    button { background: var(--primary); color: var(--white); padding: 12px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; }
    button:hover { opacity: 0.9; }
    .message { text-align: center; margin-bottom: 15px; color: #ff6b6b; }
    .links { text-align: center; margin-top: 15px; }
    .links a { color: var(--light); text-decoration: none; font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Se connecter</h2>
    <?php if ($message): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <label for="username">Nom d'utilisateur</label>
      <input type="username" id="username" name="username" required autofocus />
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required />
      <button type="submit">Connexion</button>
    </form>
    <div class="links">
      <a href="register.php">Pas encore de compte ? Inscrivez-vous</a>
    </div>
  </div>
</body>
</html>
