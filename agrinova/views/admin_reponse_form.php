<?php
/**
 * views/admin_reponse_form.php — BACK OFFICE : modification d'une réponse (Update).
 * Attend : $reponse (ReponseModel), $old, $errors, $csrfToken
 */
$title = 'Modifier une réponse';
$active = 'm5';
ob_start();
?>
<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

    <form method="post" action="index.php?action=admin_reponse_edit&amp;id=<?= e($reponse->getId()) ?>" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="field">
            <label for="message">Réponse</label>
            <textarea id="message" name="message" rows="7" class="<?= invalid($errors, 'message') ?>"
                      data-rules="required|minlen:<?= ReponseController::MSG_MIN ?>|maxlen:<?= ReponseController::MSG_MAX ?>"
                      maxlength="<?= ReponseController::MSG_MAX ?>"><?= old($old, 'message') ?></textarea>
            <?= fieldError($errors, 'message') ?>
        </div>
        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=admin_reclamation_detail&amp;id=<?= e($reponse->getIdReclamation()) ?>">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
