<?php
session_start();
include('db_connection.php');
include('functions.php');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['id'];
$message = "";
$error = "";

// Récupérer les informations actuelles de l'utilisateur depuis la base, incluant le mot de passe
$query = "SELECT username, email, profil_img, password FROM users WHERE id = $user_id LIMIT 1";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    die("Utilisateur non trouvé.");
}

// Traitement du formulaire de modification du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer et sécuriser les données du formulaire
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Si l'utilisateur renseigne un nouveau mot de passe, on le hash, sinon on conserve l'ancien
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    } else {
        $password = $user['password']; // conserver l'ancien mot de passe
    }
    
    // Valeur par défaut : conserver l'image existante
    $profilImgPath = $user['profil_img'];
    
    // Traitement de l'upload de l'image de profil (si l'utilisateur a sélectionné un nouveau fichier)
    if (isset($_FILES['profil_img']) && $_FILES['profil_img']['error'] === 0) {
        $targetDir = "uploads/";
        // Créer le dossier s'il n'existe pas
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        // Générer un nom unique pour le fichier
        $fileName = time() . '_' . basename($_FILES['profil_img']['name']);
        $targetFile = $targetDir . $fileName;
        
        // Vérifier que le fichier est bien une image
        $check = getimagesize($_FILES['profil_img']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profil_img']['tmp_name'], $targetFile)) {
                $profilImgPath = $targetFile;
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error = "Le fichier téléchargé n'est pas une image valide.";
        }
    }
    
    // Mise à jour dans la base
    if (empty($error)) {
        $updateQuery = "UPDATE users SET username = '$username', email = '$email', password = '$password', profil_img = '$profilImgPath' WHERE id = $user_id";
        if (mysqli_query($conn, $updateQuery)) {
            $message = "Profil mis à jour avec succès.";
            // Rafraîchir les données de l'utilisateur
            $query = "SELECT username, email, profil_img, password FROM users WHERE id = $user_id LIMIT 1";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
            }
        } else {
            $error = "Erreur lors de la mise à jour : " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Profil</title>
    <link rel="stylesheet" href="style.css">
    <style>
  /* RESET de base */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  body {
    font-family: "Roboto", "Open Sans", sans-serif;
    background: #f4f7fa;
    color: #333;
    line-height: 1.6;
    padding-bottom: 40px;
  }
  /* Header */
  header {
    background: linear-gradient(135deg,rgb(119, 119, 236), #342e92);
    max-width: 700px;
    margin: 0 auto;
    padding: 20px 40px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    border-radius: 5px;
  }
  header nav ul {
    display: flex;
    justify-content: center;
    list-style: none;
  }
  header nav ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
    margin: 0 15px;
    transition: color 0.3s ease;
  }
  header nav ul li a:hover {
    color: #dedede;
  }
  /* Titre principal */
  h1 {
    text-align: center;
    margin: 2rem auto;
    font-size: 2.5rem;
    color: #342e92;
  }
  /* Avatar */
  .avatar {
    display: block;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin: 0 auto 20px;
    object-fit: cover;
    border: 4px solid #5a4fcf;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
  /* Formulaire */
  form {
    background: #fff;
    max-width: 700px;
    margin: 0 auto;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }
  form input[type="text"],
  form input[type="email"],
  form input[type="password"],
  form input[type="file"] {
    width: 100%;
    padding: 15px 20px;
    margin-bottom: 15px;
    font-size: 1.1rem;
    border: 1.2px solid #ddd;
    border-radius: 5px;
    transition: border 0.3s ease;
  }
  form input:focus {
    border-color: #5a4fcf;
    outline: none;
    box-shadow: 0 0 5px rgba(90,79,207,0.5);
  }
  form button {
    display: block;
    width: 100%;
    padding: 12px;
    font-size: 1.1rem;
    background: linear-gradient(135deg,rgb(119, 119, 236), #342e92);
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  form button:hover {
    background: linear-gradient(135deg, #342e92, #5a4fcf);
  }
  /* Messages */
  .message, .error {
    max-width: 500px;
    margin: 1rem auto;
    padding: 15px;
    text-align: center;
    border-radius: 5px;
    font-size: 0.95rem;
  }
  .message {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  .error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  /* Responsive */
  @media (max-width: 600px){
    header {
      padding: 15px 20px;
    }
    header nav ul li {
      margin: 0 8px;
    }
    h1 {
      font-size: 2rem;
      margin: 1.5rem 0;
    }
    form {
      padding: 1.5rem;
    }
  }
</style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
            </ul>
        </nav>
    </header>
    <h1>Modifier Votre Profil</h1>
    <?php 
    if (!empty($message)) {
        echo "<div class='message'>" . htmlspecialchars($message) . "</div>";
    }
    if (!empty($error)) {
        echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
    }
    ?>
    <!-- Afficher l'image de profil actuelle -->
    <img src="<?= htmlspecialchars($user['profil_img']) ?>" alt="Avatar" class="avatar">
    
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required value="<?= htmlspecialchars($user['username']) ?>">
        <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($user['email']) ?>">
        <input type="password" name="password" placeholder="Nouveau mot de passe (laisser vide pour conserver l'ancien)">
        <!-- Champ pour modifier l'image de profil -->
        <input type="file" name="profil_img" accept="image/*">
        <button type="submit">Mettre à jour</button>
    </form>
</body>
</html>
