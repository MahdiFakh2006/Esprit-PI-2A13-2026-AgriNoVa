<?php
/**
 * views/admin_commandes.php — suivi des commandes du Marketplace (Back Office).
 * Attend : $result (commandes, total, page, pages, statut), $csrfToken
 */
$title = 'Commandes';
$active = 'commandes';
$query = ['action' => 'admin_commandes', 'statut' => $result['statut']];
ob_start();
?>
<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_commandes">
        <select name="statut">
            <option value="">Tous les statuts</option>
            <?php foreach (CommandeController::STATUTS as $s): ?>
                <option value="<?= e($s) ?>" <?= $result['statut'] === $s ? 'selected' : '' ?>><?= e(commandeStatutLabel($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
</div>

<?php if (!$result['commandes']): ?><section class="panel"><p class="empty">Aucune commande.</p></section><?php endif; ?>
<?php foreach ($result['commandes'] as $c): ?>
    <section class="panel">
        <div class="panel-head">
            <h2>Commande n° <?= e($c->getId()) ?> · <?= e($c->getAgriculteurNom()) ?>
                <span class="status status-cmd-<?= e($c->getStatut()) ?>"><?= e(commandeStatutLabel($c->getStatut())) ?></span></h2>
            <span class="muted"><?= e(date('d/m/Y H:i', strtotime($c->getDateCommande()) ?: time())) ?></span>
        </div>
        <table class="table">
            <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th></tr></thead>
            <tbody>
            <?php foreach ($c->getLignes() as $l): ?>
                <tr><td><?= e($l['nom_produit']) ?></td><td><?= e(money($l['prix_unitaire'])) ?></td>
                    <td><?= e($l['quantite']) ?></td><td><?= e(money($l['prix_unitaire'] * $l['quantite'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="cart-total"><span>Total</span> <strong><?= e(money($c->getTotal())) ?></strong></div>
        <?php if ($c->getStatut() === 'en_attente'): ?>
            <div class="form-actions">
                <form method="post" action="index.php?action=admin_commande_statut" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= e($c->getId()) ?>">
                    <input type="hidden" name="statut" value="confirmee">
                    <button type="submit" class="btn btn-small btn-primary">Confirmer</button>
                </form>
                <form method="post" action="index.php?action=admin_commande_statut" class="inline"
                      data-confirm="Annuler la commande n° <?= e($c->getId()) ?> ? Le stock sera remis à jour.">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= e($c->getId()) ?>">
                    <input type="hidden" name="statut" value="annulee">
                    <button type="submit" class="btn btn-small btn-danger">Annuler</button>
                </form>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'current' : '' ?>" href="index.php?<?= e(http_build_query($query + ['page' => $i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
