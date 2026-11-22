<?php
/**
 * views/admin_reclamation_detail.php — BACK OFFICE : traiter une réclamation
 * (lire, répondre, changer le statut, modifier / supprimer une réponse).
 * Attend : $rec (ReclamationModel), $reponses (ReponseModel[]), $errors, $old, $csrfToken
 */
$title = 'Réclamation #' . $rec->getId();
$active = 'm5';
$next = ReclamationController::nextStatuts($rec->getStatut());
$isClosed = $rec->getStatut() === 'cloturee';
ob_start();
?>
<p><a href="index.php?action=admin_reclamations">← Toutes les réclamations</a></p>

<div class="admin-grid">
    <section class="panel">
        <h2><?= e($rec->getSujet()) ?></h2>
        <table class="info">
            <tr><th>Client</th><td><?= e($rec->getAuteurNom()) ?><br><small class="muted"><?= e($rec->getAuteurEmail()) ?></small></td></tr>
            <tr><th>Catégorie</th><td><?= e(ReclamationController::categorieLabel($rec->getCategorie())) ?></td></tr>
            <tr><th>Déposée le</th><td><?= e(date('d/m/Y à H:i', strtotime($rec->getDateCreation()) ?: time())) ?></td></tr>
            <tr><th>Statut</th><td><span class="status status-<?= e($rec->getStatut()) ?>"><?= e(ReclamationController::statutLabel($rec->getStatut())) ?></span></td></tr>
        </table>
        <p class="msg-text"><?= nl2br(e($rec->getDescription())) ?></p>

        <?php if ($next): ?>
            <form method="post" action="index.php?action=admin_reclamation_status" class="status-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= e($rec->getId()) ?>">
                <label for="statut">Faire avancer le statut</label>
                <select id="statut" name="statut">
                    <?php foreach ($next as $cle): ?>
                        <option value="<?= e($cle) ?>"><?= e(ReclamationController::statutLabel($cle)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-dark">Changer le statut</button>
            </form>
        <?php else: ?>
            <p class="muted">Réclamation clôturée : le statut ne peut plus changer.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Fil de réponses (<?= count($reponses) ?>)</h2>
        <?php if (!$reponses): ?>
            <p class="muted">Aucune réponse pour le moment.</p>
        <?php endif; ?>
        <div class="thread">
            <?php foreach ($reponses as $p): ?>
                <article class="msg msg-staff">
                    <header>
                        <b><?= e($p->getAuteurNom()) ?></b>
                        <small class="muted"><?= e(date('d/m/Y à H:i', strtotime($p->getDateReponse()) ?: time())) ?></small>
                    </header>
                    <p class="msg-text"><?= nl2br(e($p->getMessage())) ?></p>
                    <div class="actions">
                        <a class="btn btn-small" href="index.php?action=admin_reponse_edit&amp;id=<?= e($p->getId()) ?>">Modifier</a>
                        <form method="post" action="index.php?action=admin_reponse_delete" class="inline"
                              data-confirm="Supprimer cette réponse ?">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= e($p->getId()) ?>">
                            <input type="hidden" name="id_reclamation" value="<?= e($rec->getId()) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($isClosed): ?>
            <p class="muted">Réclamation clôturée : il n’est plus possible de répondre.</p>
        <?php else: ?>
            <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>
            <form method="post" action="index.php?action=admin_reclamation_reply" class="js-validate" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id_reclamation" value="<?= e($rec->getId()) ?>">
                <div class="field">
                    <label for="message">Votre réponse au client</label>
                    <textarea id="message" name="message" rows="5" class="<?= invalid($errors, 'message') ?>"
                              data-rules="required|minlen:<?= ReponseController::MSG_MIN ?>|maxlen:<?= ReponseController::MSG_MAX ?>"
                              maxlength="<?= ReponseController::MSG_MAX ?>"><?= old($old, 'message') ?></textarea>
                    <?= fieldError($errors, 'message') ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Envoyer la réponse</button>
                </div>
            </form>
            <?php if ($rec->getStatut() === 'ouverte'): ?>
                <small class="hint">En répondant, la réclamation passe automatiquement « en cours ».</small>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
