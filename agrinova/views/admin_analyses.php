<?php
/**
 * views/admin_analyses.php — BACK OFFICE : toutes les analyses + statistiques.
 * Attend : $result (analyses, total, page, pages, culture, etat), $stats, $cultures, $csrfToken
 */
$title = 'Diagnostic IA · Analyses';
$active = 'm3';
$m3Tab = 'analyses';
$query = ['action' => 'admin_analyses', 'culture' => $result['culture'], 'etat' => $result['etat']];
$tauxSaines = $stats['total'] ? round(100 * $stats['saines'] / $stats['total']) : 0;
$maxTop = max(1, ...array_map(fn($r) => (int) $r['nb'], $stats['top'] ?: [['nb' => 1]]));
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">
<?php require __DIR__ . '/_m3_tabs.php'; ?>

<section class="kpis">
    <article class="kpi"><p>Analyses</p><strong><?= (int) $stats['total'] ?></strong></article>
    <article class="kpi"><p>Feuilles saines</p><strong><?= (int) $tauxSaines ?> %</strong></article>
    <article class="kpi"><p>Maladies détectées</p><strong><?= (int) array_sum(array_column($stats['top'], 'nb')) ?></strong></article>
    <article class="kpi kpi-alert"><p>Confiance faible</p><strong><?= (int) $stats['douteux'] ?></strong></article>
</section>

<div class="admin-grid">
    <section class="panel">
        <h2>Maladies les plus détectées</h2>
        <?php if (!$stats['top']): ?><p class="muted">Aucune maladie détectée pour l’instant.</p><?php endif; ?>
        <?php foreach ($stats['top'] as $row): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e($row['nom']) ?> <small class="muted">(<?= e($row['culture']) ?>)</small></span>
                <span class="bar-track"><span class="bar-fill diag-fill-bad" style="width: <?= (int) round($row['nb'] / $maxTop * 100) ?>%"></span></span>
                <span class="bar-value"><?= (int) $row['nb'] ?></span>
            </div>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <h2>Analyses par culture</h2>
        <?php if (!$stats['parCulture']): ?><p class="muted">Aucune analyse pour l’instant.</p><?php endif; ?>
        <?php foreach ($stats['parCulture'] as $row): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e($row['culture'] ?: '—') ?></span>
                <span class="bar-track"><span class="bar-fill diag-fill-ok" style="width: <?= (int) round($row['nb'] / max(1, $stats['total']) * 100) ?>%"></span></span>
                <span class="bar-value"><?= (int) $row['nb'] ?></span>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_analyses">
        <select name="culture">
            <option value="">Toutes les cultures</option>
            <?php foreach ($cultures as $c): ?>
                <option value="<?= e($c) ?>" <?= $result['culture'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="etat">
            <option value="">Tous les résultats</option>
            <option value="malade" <?= $result['etat'] === 'malade' ? 'selected' : '' ?>>Maladie détectée</option>
            <option value="saine" <?= $result['etat'] === 'saine' ? 'selected' : '' ?>>Feuille saine</option>
            <option value="douteux" <?= $result['etat'] === 'douteux' ? 'selected' : '' ?>>Confiance faible (&lt; <?= (int) AnalyseController::SEUIL_CONFIANCE ?> %)</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
</div>

<section class="panel">
    <p class="muted"><?= (int) $result['total'] ?> analyse(s)</p>
    <table class="table">
        <thead><tr><th>Photo</th><th>Date</th><th>Agriculteur</th><th>Culture</th><th>Résultat</th><th>Atteinte</th><th>Confiance</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$result['analyses']): ?>
            <tr><td colspan="8" class="empty">Aucune analyse ne correspond aux filtres.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['analyses'] as $a): ?>
            <tr>
                <td><img class="diag-thumb" src="uploads/analyses/<?= e(rawurlencode(basename($a->getImage()))) ?>" alt=""></td>
                <td><?= e(date('d/m/Y H:i', strtotime($a->getDateAnalyse()) ?: time())) ?></td>
                <td><?= e($a->getNomAgriculteur()) ?></td>
                <td><?= e($a->getCulture()) ?></td>
                <td>
                    <?php if ($a->isSaine()): ?><span class="badge diag-ok">Saine</span>
                    <?php elseif ($a->isInconnue()): ?><span class="badge diag-warn">Non identifiée</span>
                    <?php else: ?><b><?= e($a->getNomMaladie()) ?></b><?php endif; ?>
                </td>
                <td><?= e(number_format((float) $a->getTauxAtteinte(), 1, ',', ' ')) ?> %</td>
                <td class="<?= (float) $a->getConfiance() < AnalyseController::SEUIL_CONFIANCE ? 'diag-low' : '' ?>"><?= e(number_format((float) $a->getConfiance(), 0)) ?> %</td>
                <td class="actions">
                    <form method="post" action="index.php?action=admin_analyse_delete" class="inline" data-confirm="Supprimer cette analyse ?">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int) $a->getId() ?>">
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
