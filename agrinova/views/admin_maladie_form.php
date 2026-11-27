<?php
/**
 * views/admin_maladie_form.php — BACK OFFICE : ajout (Create) et modification (Update) d'une maladie.
 * Attend : $editMaladie (MaladieModel|null), $old, $errors, $cultures, $csrfToken
 */
$isEdit = $editMaladie !== null;
$title = $isEdit ? 'Modifier une maladie' : 'Ajouter une maladie';
$active = 'm3';
$m3Tab = 'maladies';
$formAction = $isEdit ? 'index.php?action=admin_maladie_edit&id=' . $editMaladie->getId() : 'index.php?action=admin_maladie_create';
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">
<?php require __DIR__ . '/_m3_tabs.php'; ?>

<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="grid-2">
            <div class="field">
                <label for="nom">Nom de la maladie</label>
                <input type="text" id="nom" name="nom" value="<?= old($old, 'nom') ?>" class="<?= invalid($errors, 'nom') ?>"
                       data-rules="required|minlen:2|maxlen:120" maxlength="120">
                <?= fieldError($errors, 'nom') ?>
            </div>
            <div class="field">
                <label for="culture">Culture</label>
                <input type="text" id="culture" name="culture" value="<?= old($old, 'culture') ?>" class="<?= invalid($errors, 'culture') ?>"
                       data-rules="required|name" maxlength="50" list="cultures-connues">
                <datalist id="cultures-connues">
                    <?php foreach ($cultures as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                </datalist>
                <?= fieldError($errors, 'culture') ?>
            </div>
        </div>

        <div class="field">
            <label for="symptomes">Symptômes</label>
            <textarea id="symptomes" name="symptomes" rows="3" class="<?= invalid($errors, 'symptomes') ?>"
                      data-rules="required|minlen:10|maxlen:1000"><?= old($old, 'symptomes') ?></textarea>
            <?= fieldError($errors, 'symptomes') ?>
        </div>

        <div class="field">
            <label for="traitement">Traitement conseillé</label>
            <textarea id="traitement" name="traitement" rows="3" class="<?= invalid($errors, 'traitement') ?>"
                      data-rules="required|minlen:10|maxlen:1000"><?= old($old, 'traitement') ?></textarea>
            <?= fieldError($errors, 'traitement') ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="couleur_symptome">Couleur des symptômes (utilisée par l’analyse d’image)</label>
                <select id="couleur_symptome" name="couleur_symptome" class="<?= invalid($errors, 'couleur_symptome') ?>" data-rules="required">
                    <option value="">— Choisir —</option>
                    <?php foreach (MaladieController::COULEURS as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= ($old['couleur_symptome'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'couleur_symptome') ?>
            </div>
            <div class="field">
                <label for="gravite">Gravité</label>
                <select id="gravite" name="gravite" class="<?= invalid($errors, 'gravite') ?>" data-rules="required">
                    <?php foreach (MaladieController::GRAVITES as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= ($old['gravite'] ?? '') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'gravite') ?>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=admin_maladies">Annuler</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter la maladie' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
