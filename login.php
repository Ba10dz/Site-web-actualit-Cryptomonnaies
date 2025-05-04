<?php
include('db_connection.php');
include('functions.php');
session_start();

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

        header('Location: index.php');
        exit;
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Se connecter</title>
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
    <h1>Se connecter</h1>
    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe">
	<button type="submit">Se connecter</button>
    </form>

    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
</body>
</html>
