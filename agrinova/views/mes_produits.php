<?php
/**
 * views/mes_produits.php — « Mes produits » du vendeur (Front Office) : liste + accès au CRUD de SES produits.
 * Attend : $result (produits, total, page, pages, q, categorie, stock, sort), $categories, $csrfToken
 */
$title = 'Mes produits';
$query = ['action' => 'mes_produits', 'q' => $result['q'], 'categorie' => $result['categorie'] ?: '', 'stock' => $result['stock'], 'sort' => $result['sort']];
ob_start();
?>
<section class="hero"><h1>Mes produits</h1>
    <p class="muted">Ajoutez, modifiez ou supprimez les produits que vous vendez sur AgriNoVa.</p></section>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="mes_produits">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher un produit" maxlength="100">
        <select name="categorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c->getId()) ?>" <?= $result['categorie'] === $c->getId() ? 'selected' : '' ?>><?= e($c->getNom()) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="stock">
            <option value="">Tout le stock</option>
            <option value="dispo" <?= $result['stock'] === 'dispo' ? 'selected' : '' ?>>En stock</option>
            <option value="rupture" <?= $result['stock'] === 'rupture' ? 'selected' : '' ?>>En rupture</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=mes_produit_create">+ Ajouter un produit</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> produit(s)</p>
    <table class="table">
        <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$result['produits']): ?>
            <tr><td colspan="5" class="empty">Vous n’avez encore aucun produit.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['produits'] as $p): $img = produitImageUrl($p->getImage()); ?>
            <tr>
                <td class="with-thumb">
                    <?php if ($img !== ''): ?><img class="thumb" src="<?= e($img) ?>" alt=""><?php else: ?><span class="thumb thumb-empty"></span><?php endif; ?>
                    <a href="index.php?action=produit_detail&amp;id=<?= e($p->getId()) ?>"><b><?= e($p->getNom()) ?></b></a>
                </td>
                <td><?= e($p->getCategorieNom()) ?></td>
                <td><?= e(money($p->getPrix())) ?></td>
                <td><?= $p->isEnRupture() ? '<span class="stock stock-out">Rupture</span>' : e($p->getStock()) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=mes_produit_edit&amp;id=<?= e($p->getId()) ?>">Modifier</a>
                    <form method="post" action="index.php?action=mes_produit_delete" class="inline"
                          data-confirm="Supprimer définitivement « <?= e($p->getNom()) ?> » ?">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= e($p->getId()) ?>">
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
require __DIR__ . '/layout.php';
