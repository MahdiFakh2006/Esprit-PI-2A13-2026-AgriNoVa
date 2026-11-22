<?php
/**
 * views/produit_form.php — formulaire d'ajout (Create) et de modification (Update) d'un produit.
 * Partagé par le vendeur (Front Office) et l'admin (Back Office).
 * Attend : $mode ('vendeur'|'admin'), $editProduit (ProduitModel|null), $old, $errors, $categories, $vendeurs (admin), $csrfToken
 */
$isEdit = $editProduit !== null;
$isAdmin = $mode === 'admin';
$title = $isEdit ? 'Modifier un produit' : 'Ajouter un produit';
$active = 'produits';
$layout = $isAdmin ? 'layout_admin.php' : 'layout.php';
$base = $isAdmin ? 'admin_produit' : 'mes_produit';
$listAction = $isAdmin ? 'admin_produits' : 'mes_produits';
$formAction = 'index.php?action=' . $base . ($isEdit ? '_edit&id=' . $editProduit->getId() : '_create');
$currentImage = $isEdit ? produitImageUrl($editProduit->getImage()) : '';
ob_start();
?>
<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="field">
            <label for="nom">Nom du produit</label>
            <input type="text" id="nom" name="nom" value="<?= old($old, 'nom') ?>" class="<?= invalid($errors, 'nom') ?>" data-rules="required|minlen:2|maxlen:120" maxlength="120">
            <?= fieldError($errors, 'nom') ?>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="<?= invalid($errors, 'description') ?>" data-rules="maxlen:2000" maxlength="2000"><?= old($old, 'description') ?></textarea>
            <?= fieldError($errors, 'description') ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="prix">Prix (DT)</label>
                <input type="number" id="prix" name="prix" value="<?= old($old, 'prix') ?>" class="<?= invalid($errors, 'prix') ?>" data-rules="required|number|min:0.01|max:99999999" step="0.01" min="0.01">
                <small class="hint">Strictement positif, 2 décimales maximum.</small>
                <?= fieldError($errors, 'prix') ?>
            </div>
            <div class="field">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" value="<?= old($old, 'stock', '0') ?>" class="<?= invalid($errors, 'stock') ?>" data-rules="required|integer|min:0|max:999999" step="1" min="0">
                <small class="hint">Entier supérieur ou égal à 0.</small>
                <?= fieldError($errors, 'stock') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="id_categorie">Catégorie</label>
                <select id="id_categorie" name="id_categorie" class="<?= invalid($errors, 'id_categorie') ?>" data-rules="required">
                    <option value="">— Choisir —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c->getId()) ?>" <?= (string) ($old['id_categorie'] ?? '') === (string) $c->getId() ? 'selected' : '' ?>><?= e($c->getNom()) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'id_categorie') ?>
            </div>
            <?php if ($isAdmin): ?>
                <div class="field">
                    <label for="id_vendeur">Vendeur</label>
                    <select id="id_vendeur" name="id_vendeur" class="<?= invalid($errors, 'id_vendeur') ?>" data-rules="required">
                        <option value="">— Choisir —</option>
                        <?php foreach ($vendeurs as $vid => $vnom): ?>
                            <option value="<?= e($vid) ?>" <?= (string) ($old['id_vendeur'] ?? '') === (string) $vid ? 'selected' : '' ?>><?= e($vnom) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= fieldError($errors, 'id_vendeur') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="image">Image (JPG ou PNG, 2 Mo maximum)</label>
            <?php if ($currentImage !== ''): ?>
                <p><img class="thumb thumb-big" src="<?= e($currentImage) ?>" alt="Image actuelle"></p>
                <label class="check"><input type="checkbox" name="supprimer_image" value="1"> Supprimer l’image actuelle</label>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png" class="<?= invalid($errors, 'image') ?>" data-rules="image">
            <?= fieldError($errors, 'image') ?>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=<?= e($listAction) ?>">Annuler</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/' . $layout;
