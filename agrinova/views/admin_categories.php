<?php
/**
 * views/admin_categories.php — liste des catégories (Read) avec recherche et pagination.
 * Attend : $result (categories, total, page, pages, q), $csrfToken
 */
$title = 'Catégories';
$active = 'categories';
$query = ['action' => 'admin_categories', 'q' => $result['q']];
ob_start();
?>
<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_categories">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher une catégorie" maxlength="100">
        <button type="submit" class="btn btn-dark">Rechercher</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=admin_categorie_create">+ Ajouter une catégorie</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> catégorie(s)</p>
    <table class="table">
        <thead><tr><th>#</th><th>Nom</th><th>Description</th><th>Produits</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$result['categories']): ?>
            <tr><td colspan="5" class="empty">Aucune catégorie trouvée.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['categories'] as $c): ?>
            <tr>
                <td><?= e($c->getId()) ?></td>
                <td><b><?= e($c->getNom()) ?></b></td>
                <td><?= e($c->getDescription()) ?></td>
                <td><a href="index.php?action=admin_produits&amp;categorie=<?= e($c->getId()) ?>"><?= e($c->getNbProduits()) ?></a></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=admin_categorie_edit&amp;id=<?= e($c->getId()) ?>">Modifier</a>
                    <form method="post" action="index.php?action=admin_categorie_delete" class="inline"
                          data-confirm="Supprimer la catégorie « <?= e($c->getNom()) ?> » ?">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= e($c->getId()) ?>">
                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($result['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                <a class="<?= $i === $result['page'] ? 'current' : '' ?>" href="index.php?<?= e(http_build_query($query + ['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
