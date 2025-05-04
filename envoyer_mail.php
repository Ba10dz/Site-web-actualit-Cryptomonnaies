<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclusion manuelle de PHPMailer
require 'vendor/PHPMailer/PHPMailer-6.8.0/src/PHPMailer.php';
require 'vendor/PHPMailer/PHPMailer-6.8.0/src/Exception.php';
require 'vendor/PHPMailer/PHPMailer-6.8.0/src/SMTP.php';

function envoyerMailAlerte($destinataire, $symbol, $prixActuel, $type, $seuil) {
    // 1) Formatage des nombres à 2 décimales
    $prixFormat  = number_format($prixActuel, 2);
    $seuilFormat = number_format($seuil, 2);

    // 2) URL de base et chemins vers tes images (dossier "image/")
    $base      = 'http://51.83.68.96/home/TS2/BOURSE/siteamelioration/';
    $imgHeader = $base . 'image/email-header.jpg';   // header bandeau
    $logo      = $base . 'image/logo.png';           // ton logo
    $gif       = $base . 'image/alert.gif';          // GIF animé optionnel

    // 3) Initialisation de PHPMailer
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    try {
        // Configuration SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sbri.crypto@gmail.com';
        $mail->Password   = 'bfst kldj derf xiyi';  // mot de passe d'application Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Expéditeur & destinataire
        $mail->setFrom('sbri.crypto@gmail.com', 'Crypto Alert System');
        $mail->addAddress($destinataire);

        // Sujet & format HTML
        $mail->isHTML(true);
        $mail->Subject = "?? Alerte Crypto pour $symbol";

        // 4) Corps HTML du mail
        $mail->Body = "
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0;padding:0;background:#f0f2f5;font-family:Arial,sans-serif;'>
          <!-- HEADER bandeau -->
          <div style='text-align:center;'>
            <img src='{$imgHeader}' alt='Alerte {$symbol}' style='width:100%;max-width:600px;display:block;border:none;'>
          </div>

          <!-- CONTENU PRINCIPAL -->
          <div style='max-width:600px;margin:20px auto;background:#ffffff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
            <!-- Logo + titre -->
            <div style='text-align:center;margin-bottom:20px;'>
              <img src='{$logo}' alt='Logo' width='80' style='vertical-align:middle;'>
              <h1 style='display:inline-block;color:#0d47a1;font-size:24px;margin-left:10px;'>Alerte sur {$symbol}</h1>
            </div>

            <!-- Message -->
            <p style='color:#333333;font-size:16px;line-height:1.5;margin:0 0 10px;'>
              Bonjour,<br>
              Le prix de <strong>{$symbol}</strong> est actuellement de <strong>\${$prixFormat} USD</strong>.
            </p>

            <!-- Boîte d'alerte -->
            <div style='background:#fff3cd;border-left:5px solid #ffc107;padding:15px;border-radius:4px;margin:15px 0;'>
              ?? Cela a dépassé votre seuil <strong>{$type}</strong> : <strong>\${$seuilFormat} USD</strong>.
            </div>

            <!-- Bouton CTA -->
            <div style='text-align:center;margin-top:20px;'>
              <a href='{$base}' style='display:inline-block;padding:12px 24px;background:#1976d2;color:#ffffff;text-decoration:none;border-radius:4px;font-size:16px;'>
                Voir sur le site
              </a>
            </div>
          </div>

          <!-- GIF animé (optionnel) -->
          <div style='max-width:600px;margin:0 auto;text-align:center;'>
            <img src='{$gif}' alt='Animation Alerte' style='width:100%;max-width:600px;display:block;border:none;margin-top:10px;'>
          </div>

          <!-- FOOTER TEXTE SIMPLE -->
          <div style='max-width:600px;margin:20px auto;text-align:center;font-size:12px;color:#777777;'>
            Crypto Alert System – Ne répondez pas à cet email.
          </div>
        </body>
        </html>
        ";

        // 5) Envoi
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erreur PHPMailer : {$mail->ErrorInfo}");
        return false;
    }
}
?>
