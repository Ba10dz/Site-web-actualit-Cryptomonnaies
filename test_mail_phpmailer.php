<?php
require_once 'envoyer_mail.php';

$ok = envoyerMailAlerte("ryiadgrz@gmail.com", "Test direct PHPMailer", "Ceci est un test manuel.");
if ($ok) {
    echo "✅ Test : mail envoyé !";
} else {
    echo "❌ Test : échec de l'envoi.";
}

