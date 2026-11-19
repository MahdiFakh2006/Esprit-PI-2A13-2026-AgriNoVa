<?php
/**
 * views/admin_space.php — tableau de bord du BACK OFFICE.
 * Attend : $stats (array : total, byRole, blocked, latest)
 */
$title = 'Tableau de bord';
$active = 'dashboard';
$max = max(1, ...array_values($stats['byRole']));
ob_start();
?>
<section class="kpis">
    <article class="kpi"><p>Utilisateurs</p><strong><?= e($stats['total']) ?></strong></article>
    <article class="kpi"><p>Agriculteurs</p><strong><?= e($stats['byRole']['agriculteur']) ?></strong></article>
    <article class="kpi"><p>Vendeurs</p><strong><?= e($stats['byRole']['vendeur']) ?></strong></article>
    <article class="kpi"><p>Parrains</p><strong><?= e($stats['byRole']['parrain']) ?></strong></article>
    <article class="kpi kpi-alert"><p>Comptes bloqués</p><strong><?= e($stats['blocked']) ?></strong></article>
</section>

<div class="admin-grid">
    <section class="panel">
        <h2>Répartition par rôle</h2>
        <?php foreach ($stats['byRole'] as $role => $nb): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e(roleLabel($role)) ?></span>
                <span class="bar-track"><span class="bar-fill bar-<?= e($role) ?>" style="width: <?= (int) round($nb / $max * 100) ?>%"></span></span>
                <span class="bar-value"><?= e($nb) ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Derniers inscrits</h2>
            <a class="btn btn-ghost" href="index.php?action=admin_users">Tout voir</a>
        </div>
        <table class="table">
            <thead><tr><th>Nom</th><th>Rôle</th><th>Inscription</th></tr></thead>
            <tbody>
            <?php foreach ($stats['latest'] as $u): ?>
                <tr>
                    <td><?= e($u->getNomComplet()) ?><br><small class="muted"><?= e($u->getEmail()) ?></small></td>
                    <td><span class="badge badge-<?= e($u->getRole()) ?>"><?= e(roleLabel($u->getRole())) ?></span></td>
                    <td><?= e(date('d/m/Y', strtotime($u->getDateCreation()) ?: time())) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
