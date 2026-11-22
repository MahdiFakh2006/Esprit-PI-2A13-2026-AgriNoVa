<?php
/**
 * views/produit_comparer.php — comparaison de deux équipements côte à côte.
 * Attend : $options (ProduitModel[]), $a, $b (ProduitModel|null), $selA, $selB (int)
 */
$title = 'Comparer deux équipements';
ob_start();
?>
<p><a href="index.php?action=catalogue">← Retour au catalogue</a></p>
<section class="panel">
    <h2>Choisissez deux équipements</h2>
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="produit_comparer">
        <?php foreach (['a' => $selA, 'b' => $selB] as $name => $selected): ?>
            <select name="<?= e($name) ?>" aria-label="Produit <?= e(strtoupper($name)) ?>">
                <option value="">— Produit <?= e(strtoupper($name)) ?> —</option>
                <?php foreach ($options as $o): ?>
                    <option value="<?= e($o->getId()) ?>" <?= $selected === $o->getId() ? 'selected' : '' ?>>
                        <?= e($o->getNom()) ?> (<?= e(money($o->getPrix())) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-dark">Comparer</button>
    </form>
    <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
</section>

<?php if ($a && $b): ?>
<section class="panel">
    <table class="table compare">
        <thead>
        <tr><th></th>
            <?php foreach ([$a, $b] as $p): $img = produitImageUrl($p->getImage()); ?>
                <th>
                    <?php if ($img !== ''): ?><img class="compare-img" src="<?= e($img) ?>" alt=""><?php endif; ?>
                    <a href="index.php?action=produit_detail&amp;id=<?= e($p->getId()) ?>"><?= e($p->getNom()) ?></a>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php
        $rows = [
            'Catégorie'      => fn($p) => $p->getCategorieNom(),
            'Prix'           => fn($p) => money($p->getPrix()),
            'Stock'          => fn($p) => $p->isEnRupture() ? 'Rupture de stock' : $p->getStock() . ' disponible(s)',
            'Vendeur'        => fn($p) => $p->getVendeurNom(),
            'Description'    => fn($p) => $p->getDescription() !== '' ? $p->getDescription() : '—',
        ];
        foreach ($rows as $label => $fn): ?>
            <tr><th><?= e($label) ?></th><td><?= e($fn($a)) ?></td><td><?= e($fn($b)) ?></td></tr>
        <?php endforeach; ?>
        <tr><th>Écart de prix</th>
            <td colspan="2"><?= e(money(abs($a->getPrix() - $b->getPrix()))) ?> d’écart
                (le moins cher : <?= e($a->getPrix() <= $b->getPrix() ? $a->getNom() : $b->getNom()) ?>)</td></tr>
        </tbody>
    </table>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
