<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/PHPMailer/PHPMailer-6.8.0/src/PHPMailer.php';
require 'vendor/PHPMailer/PHPMailer-6.8.0/src/Exception.php';
require 'vendor/PHPMailer/PHPMailer-6.8.0/src/SMTP.php';

function envoyerMailAlerte($destinataire, $symbol, $prixActuel, $type, $seuil) {
    // URL de base de ton site
    $siteUrl = 'http://51.83.68.96/home/TS2/BOURSE/siteamelioration/';

    // 🔢 Formattage à 2 décimales
    $prixFormat  = number_format($prixActuel, 2);
    $seuilFormat = number_format($seuil, 2);

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    try {
        // Configuration SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sbri.crypto@gmail.com';
        $mail->Password   = 'bfst kldj derf xiyi';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Expéditeur & destinataire
        $mail->setFrom('sbri.crypto@gmail.com', 'Crypto Alert System');
        $mail->addAddress($destinataire);

        // Sujet & format HTML
        $mail->isHTML(true);
        $mail->Subject = "🚨 Alerte Crypto pour $symbol";

        // Corps HTML avec les valeurs formatées
        $mail->Body = "
        <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .header { font-size: 22px; color: #333333; margin-bottom: 10px; }
            .message { font-size: 16px; color: #555555; line-height: 1.5; }
            .alert { background-color: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; margin: 20px 0; }
            .alert strong { color: #856404; }
            .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: #ffffff !important; text-decoration: none; border-radius: 4px; margin-top: 10px; }
            .footer { font-size: 12px; color: #888888; margin-top: 30px; text-align: center; }
          </style>
        </head>
        <body>
          <div class='container'>
            <div class='header'>📈 Alerte sur $symbol</div>
            <div class='message'>
              <p>Bonjour,</p>
              <p>Le prix de <strong>$symbol</strong> est actuellement de <strong>\$$prixFormat USD</strong>.</p>
              <div class='alert'>
                ⚠️ Cela a dépassé votre seuil <strong>$type</strong> : <strong>\$$seuilFormat USD</strong>.
              </div>
              <p>Pour voir plus de détails ou ajuster votre alerte :</p>
              <a href='$siteUrl' class='button'>Voir sur le site</a>
            </div>
            <div class='footer'>
              Crypto Alert System – Ne répondez pas à cet email.
            </div>
          </div>
        </body>
        </html>
        ";

        $mail->send();
        echo "✅ Mail HTML envoyé à $destinataire<br>";
        return true;
    } catch (Exception $e) {
        echo "❌ Erreur PHPMailer : {$mail->ErrorInfo}<br>";
        return false;
    }
}
?>

