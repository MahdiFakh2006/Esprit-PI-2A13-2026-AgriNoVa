# Vérification de l'adresse e-mail à l'inscription

Après l'inscription, l'utilisateur reçoit un **code à 6 chiffres** par e-mail.
Tant que ce code n'est pas saisi, le compte **ne peut pas se connecter**.

## Parcours

1. `index.php?action=register` : création du compte avec `email_verifie = 0`, génération du code, envoi de l'e-mail.
2. Redirection vers `index.php?action=verify` : l'utilisateur saisit le code.
3. Code correct : `email_verifie = 1`, le code est effacé, redirection vers la connexion.
4. Connexion avec un compte non vérifié : redirection vers la page de vérification.

## Règles de sécurité

| Règle | Où |
|---|---|
| Code aléatoire sûr (`random_int`) | `AuthController::issueVerificationCode()` |
| Code stocké **haché** (`password_hash`), jamais en clair | idem |
| Code valable 15 minutes | `CODE_MINUTES` |
| 5 codes faux au maximum, puis il faut un nouveau code | `CODE_MAX_ATTEMPTS` |
| 60 secondes minimum entre deux envois | `CODE_RESEND_SECONDS` |
| Formulaires en POST avec jeton CSRF | `verify_email.php` |
| Un compte créé par l'administrateur est vérifié d'office | `UserController::create()` |
| Les comptes existants sont marqués vérifiés | `migration_verification_email.sql` |

## Installation

1. **Base de données** : phpMyAdmin → base `agrinova` → onglet **SQL** → coller le contenu de
   `database/migration_verification_email.sql` → **Exécuter** (une seule fois).
2. **Fichiers** : copier les fichiers de ce dossier dans le projet (mêmes chemins).
3. **`.gitignore`** : ajouter la ligne `config/mail.php` (vos identifiants ne doivent pas aller sur GitHub).

## Configuration de l'envoi (Gmail)

Sans configuration, l'application fonctionne en **mode démo** : le code s'affiche à l'écran
après l'inscription (pratique pour une démonstration sans Internet).

Pour envoyer de vrais e-mails :

1. Sur le compte Google qui enverra les e-mails, activer la **validation en deux étapes**.
2. Créer un **mot de passe d'application** : Compte Google → Sécurité → Mots de passe des applications
   (16 caractères).
3. Copier `config/mail.example.php` en `config/mail.php` et remplir :
   ```php
   const MAIL_ENABLED   = true;
   const MAIL_USERNAME  = 'votre.adresse@gmail.com';
   const MAIL_PASSWORD  = 'abcd efgh ijkl mnop';   // le mot de passe d'application
   const MAIL_FROM      = 'votre.adresse@gmail.com';
   ```
4. Dans `C:\xampp\php\php.ini`, vérifier que la ligne `extension=openssl` n'est **pas** commentée
   (pas de `;` devant), puis redémarrer Apache.

En cas d'échec d'envoi, le message « L'e-mail n'a pas pu être envoyé » s'affiche et l'erreur exacte
est écrite dans `C:\xampp\php\logs\php_error_log`.

## Bibliothèque

L'envoi utilise [PHPMailer](https://github.com/PHPMailer/PHPMailer) (licence LGPL 2.1),
copiée dans `lib/PHPMailer/` : aucune installation avec Composer n'est nécessaire.
