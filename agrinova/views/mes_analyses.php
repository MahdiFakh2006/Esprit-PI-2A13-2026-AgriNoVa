<?php
/**
 * views/mes_analyses.php — FRONT OFFICE : historique des analyses de l'agriculteur connecté.
 * Attend : $analyses (AnalyseModel[]), $csrfToken
 */
$title = 'Mes analyses';
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">

<section class="panel">
    <div class="panel-head">
        <h2>Mes analyses (<?= count($analyses) ?>)</h2>
        <a class="btn btn-primary" href="index.php?action=diagnostic">+ Nouvelle analyse</a>
    </div>

    <?php if (!$analyses): ?>
        <p class="muted">Vous n’avez encore analysé aucune feuille.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Photo</th><th>Date</th><th>Culture</th><th>Résultat</th><th>Atteinte</th><th>Confiance</th><th class="right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($analyses as $a): ?>
                <tr>
                    <td><img class="diag-thumb" src="uploads/analyses/<?= e(rawurlencode(basename($a->getImage()))) ?>" alt=""></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($a->getDateAnalyse()) ?: time())) ?></td>
                    <td><?= e($a->getCulture()) ?></td>
                    <td>
                        <?php if ($a->isSaine()): ?><span class="badge diag-ok">Saine</span>
                        <?php elseif ($a->isInconnue()): ?><span class="badge diag-warn">Non identifiée</span>
                        <?php else: ?><b><?= e($a->getNomMaladie()) ?></b><?php endif; ?>
                    </td>
                    <td><?= e(number_format((float) $a->getTauxAtteinte(), 1, ',', ' ')) ?> %</td>
                    <td><?= e(number_format((float) $a->getConfiance(), 0)) ?> %</td>
                    <td class="actions">
                        <a class="btn btn-small" href="index.php?action=analyse_resultat&amp;id=<?= (int) $a->getId() ?>">Voir</a>
                        <form method="post" action="index.php?action=analyse_supprimer" class="inline" data-confirm="Supprimer cette analyse ?">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= (int) $a->getId() ?>">
                            <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
