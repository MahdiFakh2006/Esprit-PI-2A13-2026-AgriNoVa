<?php
/**
 * views/panier.php — panier de l'agriculteur (Front Office).
 * Attend : $cart (lignes, total), $csrfToken
 */
$title = 'Mon panier';
ob_start();
?>
<section class="hero"><h1>Mon panier</h1></section>

<?php if (!$cart['lignes']): ?>
    <div class="panel">
        <p class="empty">Votre panier est vide.</p>
        <a class="btn btn-primary" href="index.php?action=catalogue">Parcourir le catalogue</a>
    </div>
<?php else: ?>
<section class="panel">
    <table class="table">
        <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($cart['lignes'] as $l): $p = $l['produit']; ?>
            <tr>
                <td><a href="index.php?action=produit_detail&amp;id=<?= e($p->getId()) ?>"><b><?= e($p->getNom()) ?></b></a>
                    <br><small class="muted"><?= e($p->getCategorieNom()) ?></small></td>
                <td><?= e(money($p->getPrix())) ?></td>
                <td>
                    <form method="post" action="index.php?action=panier_modifier" class="qty-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= e($p->getId()) ?>">
                        <input type="number" name="quantite" value="<?= e($l['quantite']) ?>" min="0" max="<?= e(min(99, $p->getStock())) ?>" class="narrow">
                        <button type="submit" class="btn btn-small">OK</button>
                    </form>
                    <?php if ($l['quantite'] > $p->getStock()): ?><small class="field-error">Stock : <?= e($p->getStock()) ?></small><?php endif; ?>
                </td>
                <td><b><?= e(money($l['sous_total'])) ?></b></td>
                <td class="actions">
                    <form method="post" action="index.php?action=panier_retirer" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= e($p->getId()) ?>">
                        <button type="submit" class="btn btn-small btn-danger">Retirer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="cart-total">
        <span>Total</span> <strong><?= e(money($cart['total'])) ?></strong>
    </div>
    <div class="form-actions">
        <a class="btn btn-ghost" href="index.php?action=catalogue">Continuer mes achats</a>
        <form method="post" action="index.php?action=commande_valider" class="inline"
              data-confirm="Valider la commande de <?= e(money($cart['total'])) ?> ?">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <button type="submit" class="btn btn-primary">Valider la commande</button>
        </form>
    </div>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
