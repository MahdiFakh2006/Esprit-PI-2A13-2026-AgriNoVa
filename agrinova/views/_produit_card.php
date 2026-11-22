<?php
/**
 * views/_produit_card.php — carte d'un produit dans le catalogue (fragment inclus par catalogue.php).
 * Attend : $p (ProduitModel), $canBuy (bool), $csrfToken
 */
$img = produitImageUrl($p->getImage());
?>
<article class="product-card">
    <a class="product-img" href="index.php?action=produit_detail&amp;id=<?= e($p->getId()) ?>">
        <?php if ($img !== ''): ?>
            <img src="<?= e($img) ?>" alt="<?= e($p->getNom()) ?>" loading="lazy">
        <?php else: ?>
            <span class="no-img">Pas d’image</span>
        <?php endif; ?>
    </a>
    <div class="product-body">
        <span class="chip"><?= e($p->getCategorieNom()) ?></span>
        <h3><a href="index.php?action=produit_detail&amp;id=<?= e($p->getId()) ?>"><?= e($p->getNom()) ?></a></h3>
        <p class="price"><?= e(money($p->getPrix())) ?></p>
        <?php if ($p->isEnRupture()): ?>
            <p class="stock stock-out">Rupture de stock</p>
        <?php else: ?>
            <p class="stock stock-ok"><?= e($p->getStock()) ?> en stock</p>
        <?php endif; ?>
        <?php if ($canBuy && !$p->isEnRupture()): ?>
            <form method="post" action="index.php?action=panier_ajouter" class="add-cart">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= e($p->getId()) ?>">
                <input type="hidden" name="quantite" value="1">
                <button type="submit" class="btn btn-primary btn-small">Ajouter au panier</button>
            </form>
        <?php endif; ?>
    </div>
</article>
