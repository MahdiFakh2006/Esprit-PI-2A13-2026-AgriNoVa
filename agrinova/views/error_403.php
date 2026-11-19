<?php
/**
 * views/error_403.php — accès refusé (rôle insuffisant).
 * Page autonome : elle peut être affichée depuis n'importe quel contrôleur.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès refusé · AgriNoVa</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="front">
<main class="error-page">
    <p class="error-code">403</p>
    <h1>Accès refusé</h1>
    <p class="muted">Vous n’avez pas les droits nécessaires pour accéder à cette page.</p>
    <a class="btn btn-primary" href="index.php">Retour à mon espace</a>
</main>
</body>
</html>
