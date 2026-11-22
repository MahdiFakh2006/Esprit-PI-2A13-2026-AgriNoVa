<?php
/**
 * views/admin_categorie_form.php — ajout (Create) et modification (Update) d'une catégorie.
 * Attend : $editCategorie (CategorieModel|null), $old, $errors, $csrfToken
 */
$isEdit = $editCategorie !== null;
$title = $isEdit ? 'Modifier une catégorie' : 'Ajouter une catégorie';
$active = 'categories';
$formAction = $isEdit ? 'index.php?action=admin_categorie_edit&id=' . $editCategorie->getId() : 'index.php?action=admin_categorie_create';
ob_start();
?>
<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="field">
            <label for="nom">Nom de la catégorie</label>
            <input type="text" id="nom" name="nom" value="<?= old($old, 'nom') ?>" class="<?= invalid($errors, 'nom') ?>" data-rules="required|minlen:2|maxlen:80" maxlength="80">
            <?= fieldError($errors, 'nom') ?>
        </div>
        <div class="field">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" value="<?= old($old, 'description') ?>" class="<?= invalid($errors, 'description') ?>" data-rules="maxlen:255" maxlength="255">
            <?= fieldError($errors, 'description') ?>
        </div>
        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=admin_categories">Annuler</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter la catégorie' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
