<?php
/**
 * controllers/Validator.php
 * Contrôles de saisie côté serveur, réutilisables par tous les contrôleurs.
 * Chaque méthode renvoie null si la valeur est valide, sinon le message d'erreur.
 * (Le JavaScript fait les mêmes contrôles côté navigateur, mais on ne lui fait jamais confiance.)
 */
class Validator
{
    public static function required(string $value, string $label): ?string
    {
        return trim($value) === '' ? "Le champ « $label » est obligatoire." : null;
    }

    /** Nom ou prénom : lettres (accents compris), espaces, tirets, apostrophes. 2 à 50 caractères. */
    public static function name(string $value, string $label): ?string
    {
        if (trim($value) === '') {
            return "Le champ « $label » est obligatoire.";
        }
        if (!preg_match("/^[\p{L}][\p{L} '\-]{1,49}$/u", $value)) {
            return "« $label » : 2 à 50 lettres (espaces, tirets et apostrophes acceptés).";
        }
        return null;
    }

    public static function email(string $value): ?string
    {
        if (trim($value) === '') {
            return "L'adresse e-mail est obligatoire.";
        }
        if (strlen($value) > 150 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "L'adresse e-mail n'est pas valide.";
        }
        return null;
    }

    /** Téléphone tunisien : 8 chiffres, éventuellement précédés de +216. */
    public static function phone(string $value): ?string
    {
        if (trim($value) === '') {
            return 'Le numéro de téléphone est obligatoire.';
        }
        if (!preg_match('/^(\+216)?[2-9]\d{7}$/', self::normalizePhone($value))) {
            return 'Le téléphone doit contenir 8 chiffres (ex : 22 123 456 ou +216 22 123 456).';
        }
        return null;
    }

    public static function normalizePhone(string $value): string
    {
        return preg_replace('/[\s.\-]/', '', trim($value)) ?? '';
    }

    /** Mot de passe : au moins 8 caractères, une majuscule, une minuscule et un chiffre. */
    public static function password(string $value): ?string
    {
        if (strlen($value) < 8 || strlen($value) > 72
            || !preg_match('/[A-Z]/', $value)
            || !preg_match('/[a-z]/', $value)
            || !preg_match('/\d/', $value)) {
            return 'Le mot de passe doit contenir 8 à 72 caractères, dont une majuscule, une minuscule et un chiffre.';
        }
        return null;
    }

    public static function matches(string $a, string $b): ?string
    {
        return $a !== $b ? 'Les deux mots de passe ne correspondent pas.' : null;
    }

    /** Vérifie qu'une valeur appartient à une liste autorisée (liste blanche). */
    public static function inList(string $value, array $allowed, string $label): ?string
    {
        return in_array($value, $allowed, true) ? null : "Valeur non autorisée pour « $label ».";
    }
}
