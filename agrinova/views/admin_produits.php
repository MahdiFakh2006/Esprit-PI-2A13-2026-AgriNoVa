<?php
/**
 * views/admin_produits.php — liste de TOUS les produits (Read) + statistiques du Marketplace.
 * Attend : $result (produits, total, page, pages, q, categorie, stock, sort), $stats, $categories, $csrfToken
 */
$title = 'Produits';
$active = 'produits';
$query = ['action' => 'admin_produits', 'q' => $result['q'], 'categorie' => $result['categorie'] ?: '', 'stock' => $result['stock'], 'sort' => $result['sort']];
$maxCat = max(1, ...array_map(fn($r) => (int) $r['nb'], $stats['parCategorie'] ?: [['nb' => 1]]));
ob_start();
?>
<section class="kpis kpis-3">
    <article class="kpi"><p>Produits</p><strong><?= e($stats['total']) ?></strong></article>
    <article class="kpi kpi-alert"><p>En rupture de stock</p><strong><?= e($stats['nbRuptures']) ?></strong></article>
    <article class="kpi"><p>Valeur du stock</p><strong class="kpi-small"><?= e(money($stats['valeurStock'])) ?></strong></article>
</section>

<div class="admin-grid">
    <section class="panel">
        <h2>Produits par catégorie</h2>
        <?php if (!$stats['parCategorie']): ?><p class="muted">Aucune catégorie.</p><?php endif; ?>
        <?php foreach ($stats['parCategorie'] as $row): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e($row['nom']) ?></span>
                <span class="bar-track"><span class="bar-fill bar-agriculteur" style="width: <?= (int) round($row['nb'] / $maxCat * 100) ?>%"></span></span>
                <span class="bar-value"><?= e($row['nb']) ?></span>
            </div>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <h2>Produits en rupture de stock</h2>
        <?php if (!$stats['ruptures']): ?>
            <p class="muted">Aucune rupture de stock.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Produit</th><th>Vendeur</th><th class="right"></th></tr></thead>
                <tbody>
                <?php foreach ($stats['ruptures'] as $p): ?>
                    <tr><td><?= e($p->getNom()) ?></td><td><?= e($p->getVendeurNom()) ?></td>
                        <td class="actions"><a class="btn btn-small" href="index.php?action=admin_produit_edit&amp;id=<?= e($p->getId()) ?>">Réapprovisionner</a></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_produits">
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
        <select name="sort">
            <option value="recent" <?= $result['sort'] === 'recent' ? 'selected' : '' ?>>Plus récents</option>
            <option value="nom" <?= $result['sort'] === 'nom' ? 'selected' : '' ?>>Nom (A → Z)</option>
            <option value="prix_asc" <?= $result['sort'] === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
            <option value="prix_desc" <?= $result['sort'] === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            <option value="stock" <?= $result['sort'] === 'stock' ? 'selected' : '' ?>>Stock croissant</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=admin_produit_create">+ Ajouter un produit</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> produit(s) trouvé(s)</p>
    <table class="table">
        <thead><tr><th>#</th><th>Produit</th><th>Catégorie</th><th>Vendeur</th><th>Prix</th><th>Stock</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$result['produits']): ?>
            <tr><td colspan="7" class="empty">Aucun produit ne correspond à la recherche.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['produits'] as $p): $img = produitImageUrl($p->getImage()); ?>
            <tr>
                <td><?= e($p->getId()) ?></td>
                <td class="with-thumb">
                    <?php if ($img !== ''): ?><img class="thumb" src="<?= e($img) ?>" alt=""><?php else: ?><span class="thumb thumb-empty"></span><?php endif; ?>
                    <b><?= e($p->getNom()) ?></b>
                </td>
                <td><?= e($p->getCategorieNom()) ?></td>
                <td><?= e($p->getVendeurNom()) ?></td>
                <td><?= e(money($p->getPrix())) ?></td>
                <td><?= $p->isEnRupture() ? '<span class="stock stock-out">Rupture</span>' : e($p->getStock()) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=admin_produit_edit&amp;id=<?= e($p->getId()) ?>">Modifier</a>
                    <form method="post" action="index.php?action=admin_produit_delete" class="inline"
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
require __DIR__ . '/layout_admin.php';
