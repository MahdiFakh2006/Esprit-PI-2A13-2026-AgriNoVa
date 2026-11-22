<?php
/**
 * views/produit_detail.php — fiche détaillée d'un produit (Front Office).
 * Attend : $produit (ProduitModel), $canBuy (bool), $csrfToken
 */
$title = $produit->getNom();
$img = produitImageUrl($produit->getImage());
ob_start();
?>
<p><a href="index.php?action=catalogue">← Retour au catalogue</a></p>
<section class="panel detail">
    <div class="detail-img">
        <?php if ($img !== ''): ?>
            <img src="<?= e($img) ?>" alt="<?= e($produit->getNom()) ?>">
        <?php else: ?>
            <span class="no-img">Pas d’image</span>
        <?php endif; ?>
    </div>
    <div class="detail-info">
        <span class="chip"><?= e($produit->getCategorieNom()) ?></span>
        <h1><?= e($produit->getNom()) ?></h1>
        <p class="price price-big"><?= e(money($produit->getPrix())) ?></p>
        <p><?= nl2br(e($produit->getDescription() !== '' ? $produit->getDescription() : 'Aucune description.')) ?></p>
        <table class="info">
            <tr><th>Vendeur</th><td><?= e($produit->getVendeurNom()) ?></td></tr>
            <tr><th>Disponibilité</th>
                <td><?= $produit->isEnRupture()
                    ? '<span class="stock stock-out">Rupture de stock</span>'
                    : '<span class="stock stock-ok">' . e($produit->getStock()) . ' en stock</span>' ?></td></tr>
        </table>

        <?php if ($canBuy && !$produit->isEnRupture()): ?>
            <form method="post" action="index.php?action=panier_ajouter" class="js-validate add-cart-big" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= e($produit->getId()) ?>">
                <div class="field">
                    <label for="quantite">Quantité</label>
                    <input type="number" id="quantite" name="quantite" value="1" min="1" max="<?= e(min(99, $produit->getStock())) ?>"
                           data-rules="required|integer|min:1|max:<?= e(min(99, $produit->getStock())) ?>">
                    <small class="field-error"></small>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter au panier</button>
            </form>
        <?php endif; ?>
        <p><a class="btn btn-ghost" href="index.php?action=produit_comparer&amp;a=<?= e($produit->getId()) ?>">Comparer avec un autre produit</a></p>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
