<?php
/**
 * views/admin_reclamations.php — BACK OFFICE : statistiques + liste des réclamations
 * filtrée par statut et par catégorie (recherche, tri, pagination).
 * Attend : $stats (total, byStatut, byCategorie, avgMinutes, pending), $result, $csrfToken
 */
$title = 'Réclamations et support';
$active = 'm5';
$query = [
    'action' => 'admin_reclamations', 'q' => $result['q'], 'statut' => $result['statut'],
    'categorie' => $result['categorie'], 'sort' => $result['sort'],
];
$maxCat = max(1, ...array_values($stats['byCategorie']));
ob_start();
?>
<section class="kpis">
    <article class="kpi"><p>Réclamations</p><strong><?= e($stats['total']) ?></strong></article>
    <article class="kpi kpi-alert"><p>Ouvertes</p><strong><?= e($stats['byStatut']['ouverte']) ?></strong></article>
    <article class="kpi"><p>En cours</p><strong><?= e($stats['byStatut']['en_cours']) ?></strong></article>
    <article class="kpi"><p>Résolues / clôturées</p><strong><?= e($stats['byStatut']['resolue'] + $stats['byStatut']['cloturee']) ?></strong></article>
    <article class="kpi"><p>Délai moyen de réponse</p><strong><?= e(ReclamationController::formatDelay($stats['avgMinutes'])) ?></strong></article>
</section>

<div class="admin-grid">
    <section class="panel">
        <h2>Réclamations par catégorie</h2>
        <?php foreach ($stats['byCategorie'] as $cle => $nb): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e(ReclamationController::categorieLabel($cle)) ?></span>
                <span class="bar-track"><span class="bar-fill bar-cat" style="width: <?= (int) round($nb / $maxCat * 100) ?>%"></span></span>
                <span class="bar-value"><?= e($nb) ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>À traiter en priorité</h2>
            <a class="btn btn-ghost" href="index.php?action=admin_reclamations&amp;statut=ouverte&amp;sort=ancien">Tout voir</a>
        </div>
        <table class="table">
            <thead><tr><th>Sujet</th><th>Catégorie</th><th>Depuis</th></tr></thead>
            <tbody>
            <?php if (!$stats['pending']): ?>
                <tr><td colspan="3" class="empty">Aucune réclamation en attente.</td></tr>
            <?php endif; ?>
            <?php foreach ($stats['pending'] as $r): ?>
                <tr>
                    <td><a href="index.php?action=admin_reclamation_detail&amp;id=<?= e($r->getId()) ?>"><?= e($r->getSujet()) ?></a><br><small class="muted"><?= e($r->getAuteurNom()) ?></small></td>
                    <td><?= e(ReclamationController::categorieLabel($r->getCategorie())) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($r->getDateCreation()) ?: time())) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_reclamations">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher un sujet, un nom ou un e-mail" maxlength="100">
        <select name="statut" aria-label="Statut">
            <option value="">Tous les statuts</option>
            <?php foreach (ReclamationController::STATUTS as $cle => $libelle): ?>
                <option value="<?= e($cle) ?>" <?= $result['statut'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="categorie" aria-label="Catégorie">
            <option value="">Toutes les catégories</option>
            <?php foreach (ReclamationController::CATEGORIES as $cle => $libelle): ?>
                <option value="<?= e($cle) ?>" <?= $result['categorie'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" aria-label="Tri">
            <option value="recent" <?= $result['sort'] === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
            <option value="ancien" <?= $result['sort'] === 'ancien' ? 'selected' : '' ?>>Plus anciennes</option>
            <option value="statut" <?= $result['sort'] === 'statut' ? 'selected' : '' ?>>Par statut</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> réclamation(s) trouvée(s)</p>
    <table class="table">
        <thead>
        <tr><th>#</th><th>Sujet</th><th>Catégorie</th><th>Statut</th><th>Réponses</th><th>Déposée le</th><th class="right">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$result['items']): ?>
            <tr><td colspan="7" class="empty">Aucune réclamation ne correspond à la recherche.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['items'] as $r): ?>
            <tr>
                <td><?= e($r->getId()) ?></td>
                <td><b><?= e($r->getSujet()) ?></b><br><small class="muted"><?= e($r->getAuteurNom()) ?> · <?= e($r->getAuteurEmail()) ?></small></td>
                <td><?= e(ReclamationController::categorieLabel($r->getCategorie())) ?></td>
                <td><span class="status status-<?= e($r->getStatut()) ?>"><?= e(ReclamationController::statutLabel($r->getStatut())) ?></span></td>
                <td><?= e($r->getNbReponses()) ?></td>
                <td><?= e(date('d/m/Y', strtotime($r->getDateCreation()) ?: time())) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=admin_reclamation_detail&amp;id=<?= e($r->getId()) ?>">Traiter</a>
                    <form method="post" action="index.php?action=admin_reclamation_delete" class="inline"
                          data-confirm="Supprimer définitivement cette réclamation et ses réponses ?">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= e($r->getId()) ?>">
                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($result['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a class="<?= $p === $result['page'] ? 'current' : '' ?>" href="index.php?<?= e(http_build_query($query + ['page' => $p])) ?>"><?= $p ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
