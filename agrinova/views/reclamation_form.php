<?php
/**
 * views/reclamation_form.php — FRONT OFFICE : dépôt (Create) et modification (Update) d'une réclamation.
 * Attend : $editRec (ReclamationModel|null), $old, $errors, $csrfToken
 */
$isEdit = $editRec !== null;
$title = $isEdit ? 'Modifier ma réclamation' : 'Nouvelle réclamation';
$formAction = $isEdit
    ? 'index.php?action=reclamation_edit&id=' . $editRec->getId()
    : 'index.php?action=reclamation_new';
ob_start();
?>
<section class="hero">
    <h1><?= e($title) ?></h1>
    <p class="muted">Choisissez la catégorie concernée et décrivez le problème : notre équipe vous répond dans votre espace « Mes réclamations ».</p>
</section>

<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="field">
            <label for="categorie">Catégorie</label>
            <select id="categorie" name="categorie" class="<?= invalid($errors, 'categorie') ?>" data-rules="required">
                <option value="">— Choisir une catégorie —</option>
                <?php foreach (ReclamationController::CATEGORIES as $cle => $libelle): ?>
                    <option value="<?= e($cle) ?>" <?= ($old['categorie'] ?? '') === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'categorie') ?>
        </div>

        <div class="field">
            <label for="sujet">Sujet</label>
            <input type="text" id="sujet" name="sujet" value="<?= old($old, 'sujet') ?>" class="<?= invalid($errors, 'sujet') ?>"
                   data-rules="required|minlen:<?= ReclamationController::SUJET_MIN ?>|maxlen:<?= ReclamationController::SUJET_MAX ?>"
                   maxlength="<?= ReclamationController::SUJET_MAX ?>">
            <small class="hint">De <?= ReclamationController::SUJET_MIN ?> à <?= ReclamationController::SUJET_MAX ?> caractères.</small>
            <?= fieldError($errors, 'sujet') ?>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="7" class="<?= invalid($errors, 'description') ?>"
                      data-rules="required|minlen:<?= ReclamationController::DESC_MIN ?>|maxlen:<?= ReclamationController::DESC_MAX ?>"
                      maxlength="<?= ReclamationController::DESC_MAX ?>"><?= old($old, 'description') ?></textarea>
            <small class="hint"><?= ReclamationController::DESC_MIN ?> caractères minimum : indiquez la date, le produit ou l’équipement concerné et ce qui s’est passé.</small>
            <?= fieldError($errors, 'description') ?>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=mes_reclamations">Annuler</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer les modifications' : 'Envoyer la réclamation' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
