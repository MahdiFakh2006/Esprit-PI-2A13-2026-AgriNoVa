<?php
/**
 * views/mes_commandes.php — historique des commandes de l'agriculteur (Front Office).
 * Attend : $commandes (CommandeModel[]), $csrfToken
 */
$title = 'Mes commandes';
ob_start();
?>
<section class="hero"><h1>Mes commandes</h1></section>
<?php if (!$commandes): ?>
    <div class="panel"><p class="empty">Vous n’avez encore passé aucune commande.</p>
        <a class="btn btn-primary" href="index.php?action=catalogue">Parcourir le catalogue</a></div>
<?php endif; ?>
<?php foreach ($commandes as $c): ?>
    <section class="panel">
        <div class="panel-head">
            <h2>Commande n° <?= e($c->getId()) ?>
                <span class="status status-cmd-<?= e($c->getStatut()) ?>"><?= e(commandeStatutLabel($c->getStatut())) ?></span></h2>
            <span class="muted"><?= e(date('d/m/Y H:i', strtotime($c->getDateCommande()) ?: time())) ?></span>
        </div>
        <table class="table">
            <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th></tr></thead>
            <tbody>
            <?php foreach ($c->getLignes() as $l): ?>
                <tr>
                    <td><?= e($l['nom_produit']) ?></td>
                    <td><?= e(money($l['prix_unitaire'])) ?></td>
                    <td><?= e($l['quantite']) ?></td>
                    <td><?= e(money($l['prix_unitaire'] * $l['quantite'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="cart-total"><span>Total</span> <strong><?= e(money($c->getTotal())) ?></strong></div>
        <?php if ($c->getStatut() === 'en_attente'): ?>
            <form method="post" action="index.php?action=commande_annuler" class="form-actions"
                  data-confirm="Annuler la commande n° <?= e($c->getId()) ?> ?">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= e($c->getId()) ?>">
                <button type="submit" class="btn btn-small btn-danger">Annuler la commande</button>
            </form>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
