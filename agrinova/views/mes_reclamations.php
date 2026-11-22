<?php
/**
 * views/mes_reclamations.php — FRONT OFFICE : mes réclamations avec leur statut.
 * Attend : $result (items, total, page, pages, statut), $csrfToken
 */
$title = 'Mes réclamations';
$query = ['action' => 'mes_reclamations', 'statut' => $result['statut']];
ob_start();
?>
<section class="hero">
    <h1>Mes réclamations</h1>
    <p class="muted">Suivez l’avancement de vos demandes d’aide. Consultez d’abord la <a href="index.php?action=faq">FAQ</a> : la réponse s’y trouve peut-être déjà.</p>
</section>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="mes_reclamations">
        <select name="statut" aria-label="Filtrer par statut">
            <option value="">Tous les statuts</option>
            <?php foreach (ReclamationController::STATUTS as $cle => $libelle): ?>
                <option value="<?= e($cle) ?>" <?= $result['statut'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=reclamation_new">+ Nouvelle réclamation</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> réclamation(s)</p>
    <table class="table">
        <thead>
        <tr><th>#</th><th>Sujet</th><th>Catégorie</th><th>Statut</th><th>Réponses</th><th>Déposée le</th><th class="right">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$result['items']): ?>
            <tr><td colspan="7" class="empty">Vous n’avez aucune réclamation. Un problème avec une commande, une location ou une formation ? <a href="index.php?action=reclamation_new">Déposez-en une</a>.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['items'] as $r): ?>
            <tr>
                <td><?= e($r->getId()) ?></td>
                <td><b><?= e($r->getSujet()) ?></b></td>
                <td><?= e(ReclamationController::categorieLabel($r->getCategorie())) ?></td>
                <td><span class="status status-<?= e($r->getStatut()) ?>"><?= e(ReclamationController::statutLabel($r->getStatut())) ?></span></td>
                <td><?= e($r->getNbReponses()) ?></td>
                <td><?= e(date('d/m/Y', strtotime($r->getDateCreation()) ?: time())) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=reclamation_detail&amp;id=<?= e($r->getId()) ?>">Voir</a>
                    <?php if (ReclamationController::isEditable($r)): ?>
                        <a class="btn btn-small" href="index.php?action=reclamation_edit&amp;id=<?= e($r->getId()) ?>">Modifier</a>
                        <form method="post" action="index.php?action=reclamation_delete" class="inline"
                              data-confirm="Supprimer définitivement cette réclamation ?">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= e($r->getId()) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                        </form>
                    <?php endif; ?>
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
require __DIR__ . '/layout.php';
