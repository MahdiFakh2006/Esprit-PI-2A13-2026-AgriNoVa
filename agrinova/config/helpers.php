<?php
/**
 * config/helpers.php
 * Petites fonctions d'affichage utilisées par les vues.
 */

/** Échappe une valeur avant de l'afficher dans le HTML (protection XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Valeur précédemment saisie (pour réafficher un formulaire après une erreur). */
function old(array $old, string $field, string $default = ''): string
{
    return e($old[$field] ?? $default);
}

/** Message d'erreur sous un champ (vide s'il n'y a pas d'erreur). */
function fieldError(array $errors, string $field): string
{
    return '<small class="field-error" data-for="' . e($field) . '">' . e($errors[$field] ?? '') . '</small>';
}

/** Classe CSS ajoutée à un champ en erreur. */
function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

/** Libellé lisible d'un rôle. */
function roleLabel(string $role): string
{
    return [
        'admin'       => 'Administrateur',
        'agriculteur' => 'Agriculteur',
        'vendeur'     => 'Vendeur',
        'parrain'     => 'Parrain',
    ][$role] ?? ucfirst($role);
}
