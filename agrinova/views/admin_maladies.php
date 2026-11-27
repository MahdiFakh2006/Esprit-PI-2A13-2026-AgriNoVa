<?php
/**
 * views/admin_maladies.php — BACK OFFICE : liste des maladies (Read) avec recherche, filtre, tri, pagination.
 * Attend : $result (maladies, total, page, pages, q, culture, sort), $cultures, $csrfToken
 */
$title = 'Diagnostic IA · Maladies';
$active = 'm3';
$m3Tab = 'maladies';
$query = ['action' => 'admin_maladies', 'q' => $result['q'], 'culture' => $result['culture'], 'sort' => $result['sort']];
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">
<?php require __DIR__ . '/_m3_tabs.php'; ?>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_maladies">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher un nom ou un symptôme" maxlength="100">
        <select name="culture">
            <option value="">Toutes les cultures</option>
            <?php foreach ($cultures as $c): ?>
                <option value="<?= e($c) ?>" <?= $result['culture'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort">
            <option value="nom" <?= $result['sort'] === 'nom' ? 'selected' : '' ?>>Nom (A → Z)</option>
            <option value="culture" <?= $result['sort'] === 'culture' ? 'selected' : '' ?>>Culture</option>
            <option value="analyses" <?= $result['sort'] === 'analyses' ? 'selected' : '' ?>>Les plus détectées</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=admin_maladie_create">+ Ajouter une maladie</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> maladie(s)</p>
    <table class="table">
        <thead><tr><th>#</th><th>Maladie</th><th>Culture</th><th>Symptôme</th><th>Gravité</th><th>Détectée</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$result['maladies']): ?>
            <tr><td colspan="7" class="empty">Aucune maladie ne correspond à la recherche.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['maladies'] as $m): ?>
            <tr>
                <td><?= (int) $m->getId() ?></td>
                <td><b><?= e($m->getNom()) ?></b><br><small class="muted"><?= e(mb_strimwidth($m->getSymptomes(), 0, 70, '…')) ?></small></td>
                <td><?= e($m->getCulture()) ?></td>
                <td><span class="diag-dot diag-dot-<?= e((string) $m->getCouleurSymptome()) ?>"></span><?= e(MaladieController::COULEURS[$m->getCouleurSymptome()] ?? '—') ?></td>
                <td><span class="badge diag-grav-<?= e($m->getGravite()) ?>"><?= e(MaladieController::GRAVITES[$m->getGravite()] ?? $m->getGravite()) ?></span></td>
                <td><?= (int) $m->getNbAnalyses() ?> fois</td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=admin_maladie_edit&amp;id=<?= (int) $m->getId() ?>">Modifier</a>
                    <form method="post" action="index.php?action=admin_maladie_delete" class="inline" data-confirm="Supprimer la maladie « <?= e($m->getNom()) ?> » ?">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $m->getId() ?>">
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
