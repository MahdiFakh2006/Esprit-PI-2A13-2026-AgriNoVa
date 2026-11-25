<?php
/**
 * controllers/Mailer.php
 * Envoi des e-mails de l'application (ici : le code de vérification d'inscription)
 * avec la bibliothèque PHPMailer, via le serveur SMTP configuré dans config/mail.php.
 */
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

// config/mail.php contient les vrais identifiants ; sans lui, on charge le modèle (mode démo)
if (is_file(__DIR__ . '/../config/mail.php')) {
    require_once __DIR__ . '/../config/mail.php';
} else {
    require_once __DIR__ . '/../config/mail.example.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    /** Vrai si un vrai serveur SMTP est configuré ; faux = mode démo (code affiché à l'écran). */
    public static function isEnabled(): bool
    {
        return MAIL_ENABLED === true;
    }

    /**
     * Envoie le code de vérification. Renvoie true si l'e-mail est parti.
     * En cas d'échec, l'erreur technique est écrite dans le journal PHP (jamais affichée).
     */
    public static function sendVerificationCode(string $email, string $prenom, string $code, int $minutes): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->Port       = MAIL_PORT;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;   // ne pas bloquer la page si le serveur SMTP ne répond pas

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($email, $prenom);

            $nom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
            $mail->isHTML(true);
            $mail->Subject = "Votre code de vérification AgriNoVa : $code";
            $mail->Body = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#1f2d24">
  <h2 style="color:#1f7a4d">Bienvenue sur AgriNoVa, $nom !</h2>
  <p>Pour activer votre compte, saisissez ce code sur la page de vérification :</p>
  <p style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;
            background:#eef7f1;border-radius:10px;padding:16px">$code</p>
  <p>Ce code est valable <b>$minutes minutes</b>.</p>
  <p style="color:#6b7a70;font-size:13px">Si vous n'avez pas créé de compte, ignorez cet e-mail.</p>
</div>
HTML;
            $mail->AltBody = "Bienvenue sur AgriNoVa, $prenom !\n"
                . "Votre code de vérification : $code\n"
                . "Il est valable $minutes minutes.";

            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('Envoi e-mail impossible vers ' . $email . ' : ' . $mail->ErrorInfo);
            return false;
        }
    }
}
