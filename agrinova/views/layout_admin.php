<?php
/**
 * views/layout_admin.php — habillage du BACK OFFICE (visuellement différent du Front Office).
 * Attend : $title, $content, et optionnellement $active (menu actif).
 */
$flashes = AuthController::takeFlashes();
$active = $active ?? '';
// Menus des autres modules : chaque membre remplace « # » par son action quand son module est intégré
$modules = [
    ['M3', 'Analyses IA', '#'], ['M4', 'Locations', '#'],
    ['M5', 'Réclamations', '#'], ['M6', 'Formations', '#'], ['M7', 'Parrainages', '#'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Back Office') ?> · Back Office AgriNoVa</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/validation.js" defer></script>
</head>
<body class="admin">
<aside class="sidebar">
    <a class="brand brand-light" href="index.php?action=admin"><span class="brand-mark">A</span>AgriNoVa</a>
    <span class="bo-tag">BACK OFFICE</span>
    <nav class="sidenav">
        <p class="sidenav-title">Module 1</p>
        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php?action=admin">Tableau de bord</a>
        <a class="<?= $active === 'users' ? 'active' : '' ?>" href="index.php?action=admin_users">Utilisateurs</a>
        <a class="<?= $active === 'profile' ? 'active' : '' ?>" href="index.php?action=profile">Mon profil</a>
        <p class="sidenav-title">Module 2 · Marketplace</p>
        <a class="<?= $active === 'categories' ? 'active' : '' ?>" href="index.php?action=admin_categories">Catégories</a>
        <a class="<?= $active === 'produits' ? 'active' : '' ?>" href="index.php?action=admin_produits">Produits</a>
        <a class="<?= $active === 'commandes' ? 'active' : '' ?>" href="index.php?action=admin_commandes">Commandes</a>
        <p class="sidenav-title">Autres modules</p>
        <?php foreach ($modules as [$code, $label, $href]): ?>
            <a class="<?= $href === '#' ? 'soon' : '' ?>" href="<?= e($href) ?>"><?= e($code) ?> · <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <p><?= e($_SESSION['user_name'] ?? '') ?><br><small>Administrateur</small></p>
        <form method="post" action="index.php?action=logout">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <button type="submit" class="btn btn-light">Déconnexion</button>
        </form>
    </div>
</aside>

<main class="admin-main">
    <h1 class="admin-title"><?= e($title ?? '') ?></h1>
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
