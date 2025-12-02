<?php
include('db_connection.php');
include('functions.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sécurisation des données du formulaire
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Valeur par défaut pour l'image de profil
    $profilImgPath = 'uploads/default.png';
    
    // Traitement de l'upload de l'image via le champ "profil_img"
    if (isset($_FILES['profil_img']) && $_FILES['profil_img']['error'] === 0) {
        $targetDir = "uploads/";
        // Créer le dossier uploads s'il n'existe pas
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        // Générer un nom unique pour éviter les conflits
        $fileName = time() . '_' . basename($_FILES['profil_img']['name']);
        $targetFile = $targetDir . $fileName;
        
        // Vérifier que le fichier est une image
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
    
    // Insertion de l'utilisateur dans la base avec le chemin de l'image dans la colonne "profil_img"
    $query = "INSERT INTO users (username, email, password, profil_img) 
              VALUES ('$username', '$email', '$password', '$profilImgPath')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['id'] = mysqli_insert_id($conn);
        $_SESSION['isLoggedIn'] = true;
        header('Location: login.php');
        exit;
    } else {
        $error = "Erreur lors de l'inscription : " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 2rem auto; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        input { padding: 0.5rem; font-size: 1rem; }
        button { padding: 0.5rem; font-size: 1rem; background-color: #008aff; color: #fff; border: none; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Inscription</h1>
    <?php if(isset($error)) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
    <!-- N'oublie pas l'attribut enctype pour permettre l'upload de fichier -->
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <input type="file" name="profil_img" placeholder="insérer une image de profil" accept="image/*">
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>
