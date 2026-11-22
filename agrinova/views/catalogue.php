<?php
/**
 * views/catalogue.php — catalogue du Marketplace (Front Office).
 * Attend : $result (produits, total, page, pages, q, categorie, sort, prix_min, prix_max), $categories, $canBuy, $csrfToken
 */
$title = 'Catalogue';
$query = ['action' => 'catalogue', 'q' => $result['q'], 'categorie' => $result['categorie'] ?: '',
          'prix_min' => $result['prix_min'], 'prix_max' => $result['prix_max'], 'sort' => $result['sort']];
ob_start();
?>
<section class="hero">
    <h1>Catalogue du matériel intelligent</h1>
    <p class="muted">Capteurs, drones, stations météo et kits IA proposés par des vendeurs vérifiés.</p>
</section>

<form method="get" action="index.php" class="panel filters-panel">
    <input type="hidden" name="action" value="catalogue">
    <div class="filters">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher un produit" maxlength="100">
        <select name="categorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c->getId()) ?>" <?= $result['categorie'] === $c->getId() ? 'selected' : '' ?>><?= e($c->getNom()) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="prix_min" value="<?= e($result['prix_min']) ?>" placeholder="Prix min" min="0" step="0.01" class="narrow">
        <input type="number" name="prix_max" value="<?= e($result['prix_max']) ?>" placeholder="Prix max" min="0" step="0.01" class="narrow">
        <select name="sort">
            <option value="recent" <?= $result['sort'] === 'recent' ? 'selected' : '' ?>>Nouveautés</option>
            <option value="prix_asc" <?= $result['sort'] === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
            <option value="prix_desc" <?= $result['sort'] === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            <option value="nom" <?= $result['sort'] === 'nom' ? 'selected' : '' ?>>Nom (A → Z)</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
        <a class="btn btn-ghost" href="index.php?action=catalogue">Réinitialiser</a>
    </div>
</form>

<div class="toolbar">
    <p class="muted"><?= e($result['total']) ?> produit(s) trouvé(s)</p>
    <a class="btn btn-ghost" href="index.php?action=produit_comparer">Comparer deux équipements</a>
</div>

<?php if (!$result['produits']): ?>
    <div class="panel"><p class="empty">Aucun produit ne correspond à votre recherche.</p></div>
<?php else: ?>
    <section class="product-grid">
        <?php foreach ($result['produits'] as $p): ?>
            <?php require __DIR__ . '/_produit_card.php'; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'current' : '' ?>" href="index.php?<?= e(http_build_query($query + ['page' => $i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
