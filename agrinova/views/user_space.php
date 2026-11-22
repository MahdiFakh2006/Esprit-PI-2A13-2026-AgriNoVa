<?php
/**
 * views/user_space.php — espace personnel du FRONT OFFICE.
 * Attend : $user (UserModel)
 */
$title = 'Mon espace';
// Raccourcis vers les modules selon le rôle (chaque membre branchera son action ici)
$shortcuts = [
    'agriculteur' => [
        ['M2', 'Catalogue', 'Acheter capteurs, drones et kits IA.', 'catalogue'],
        ['M2', 'Mon panier', 'Voir mon panier et valider ma commande.', 'panier'],
        ['M2', 'Mes commandes', 'Suivre mes achats.', 'mes_commandes'],
        ['M3', 'Diagnostic IA', 'Envoyer la photo d’une feuille malade.'],
        ['M4', 'Location', 'Réserver un équipement pour quelques jours.'],
        ['M6', 'Formations', 'S’inscrire à une formation.'],
        ['M7', 'Mes oliviers', 'Proposer mes arbres au parrainage.'],
        ['M5', 'Support', 'Déposer une réclamation.'],
    ],
    'vendeur' => [
        ['M2', 'Mes produits', 'Ajouter et gérer mes produits.', 'mes_produits'],
        ['M2', 'Catalogue', 'Voir tous les produits de la plateforme.', 'catalogue'],
        ['M4', 'Mes locations', 'Gérer le matériel mis en location.'],
        ['M6', 'Mes formations', 'Proposer une formation.'],
        ['M5', 'Support', 'Répondre aux réclamations.'],
    ],
    'parrain' => [
        ['M7', 'Adopter un olivier', 'Choisir un arbre sur la carte.'],
        ['M7', 'Mes parrainages', 'Suivre le journal de mon arbre.'],
        ['M5', 'Support', 'Déposer une réclamation.'],
    ],
][$user->getRole()] ?? [];
ob_start();
?>
<section class="hero">
    <p class="badge badge-<?= e($user->getRole()) ?>"><?= e(roleLabel($user->getRole())) ?></p>
    <h1>Bonjour, <?= e($user->getPrenom()) ?></h1>
    <p class="muted">Bienvenue dans votre espace personnel (Front Office).</p>
</section>

<section class="cards">
    <?php foreach ($shortcuts as $sc):
        [$code, $label, $desc] = $sc;
        $target = $sc[3] ?? null; ?>
        <?php if (!empty($target)): ?>
            <a class="card card-link" href="index.php?action=<?= e($target) ?>">
                <span class="card-code"><?= e($code) ?></span>
                <h3><?= e($label) ?></h3>
                <p><?= e($desc) ?></p>
                <small>Ouvrir →</small>
            </a>
        <?php else: ?>
            <article class="card card-soon">
                <span class="card-code"><?= e($code) ?></span>
                <h3><?= e($label) ?></h3>
                <p><?= e($desc) ?></p>
                <small>Bientôt disponible</small>
            </article>
        <?php endif; ?>
    <?php endforeach; ?>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Mes informations</h2>
        <a class="btn btn-ghost" href="index.php?action=profile">Modifier</a>
    </div>
    <table class="info">
        <tr><th>Nom complet</th><td><?= e($user->getNomComplet()) ?></td></tr>
        <tr><th>E-mail</th><td><?= e($user->getEmail()) ?></td></tr>
        <tr><th>Téléphone</th><td><?= e($user->getTelephone()) ?></td></tr>
        <tr><th>Rôle</th><td><?= e(roleLabel($user->getRole())) ?></td></tr>
        <tr><th>Membre depuis</th><td><?= e(date('d/m/Y', strtotime($user->getDateCreation()) ?: time())) ?></td></tr>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
