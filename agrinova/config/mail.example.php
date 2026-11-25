<?php
/**
 * config/mail.example.php — MODÈLE de configuration de l'envoi d'e-mails.
 *
 * 1. Copiez ce fichier en config/mail.php
 * 2. Remplissez vos identifiants Gmail (voir README : « mot de passe d'application »)
 * 3. config/mail.php est dans .gitignore : vos identifiants ne partent JAMAIS sur GitHub.
 *
 * Tant que MAIL_ENABLED vaut false (ou que config/mail.php n'existe pas),
 * l'application fonctionne en MODE DÉMO : le code est affiché à l'écran
 * au lieu d'être envoyé (pratique pour une démonstration sans Internet).
 */
const MAIL_ENABLED   = false;

const MAIL_HOST      = 'smtp.gmail.com';
const MAIL_PORT      = 587;                         // 587 = STARTTLS
const MAIL_USERNAME  = 'votre.adresse@gmail.com';
const MAIL_PASSWORD  = 'xxxx xxxx xxxx xxxx';        // mot de passe d'APPLICATION Gmail (16 caractères)
const MAIL_FROM      = 'votre.adresse@gmail.com';
const MAIL_FROM_NAME = 'AgriNoVa';
