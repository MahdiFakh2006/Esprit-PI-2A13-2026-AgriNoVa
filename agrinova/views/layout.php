<?php
/**
 * views/layout.php — habillage du FRONT OFFICE.
 * Attend : $title (string), $content (HTML préparé par la vue).
 */
$flashes = AuthController::takeFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AgriNoVa') ?> · AgriNoVa</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/validation.js" defer></script>
</head>
<body class="front">
<header class="topbar">
    <a class="brand" href="index.php"><span class="brand-mark">A</span>AgriNoVa</a>
    <nav class="topnav">
        <?php if (AuthController::isAuthenticated()): ?>
            <a href="index.php?action=dashboard">Mon espace</a>
            <?php if (AuthController::hasRole('agriculteur')): ?>
                <a href="index.php?action=catalogue">Catalogue</a>
                <a href="index.php?action=panier">Panier (<?= e(CommandeController::cartCount()) ?>)</a>
                <a href="index.php?action=mes_commandes">Mes commandes</a>
            <?php elseif (AuthController::hasRole('vendeur')): ?>
                <a href="index.php?action=catalogue">Catalogue</a>
                <a href="index.php?action=mes_produits">Mes produits</a>
            <?php endif; ?>
            <a href="index.php?action=profile">Mon profil</a>
            <span class="who"><?= e($_SESSION['user_name'] ?? '') ?> · <?= e(roleLabel(AuthController::currentRole() ?? '')) ?></span>
            <form method="post" action="index.php?action=logout" class="inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-ghost">Déconnexion</button>
            </form>
        <?php else: ?>
            <a href="index.php?action=login">Connexion</a>
            <a class="btn btn-primary" href="index.php?action=register">Créer un compte</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="footer">AgriNoVa · L’intelligence au service de la terre</footer>
</body>
</html>
