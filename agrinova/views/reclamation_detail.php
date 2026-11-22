<?php
/**
 * views/reclamation_detail.php — FRONT OFFICE : détail d'une de mes réclamations et fil de réponses.
 * Attend : $rec (ReclamationModel), $reponses (ReponseModel[]), $csrfToken
 */
$title = 'Réclamation #' . $rec->getId();
ob_start();
?>
<section class="hero">
    <p><a href="index.php?action=mes_reclamations">← Mes réclamations</a></p>
    <h1><?= e($rec->getSujet()) ?></h1>
    <p>
        <span class="status status-<?= e($rec->getStatut()) ?>"><?= e(ReclamationController::statutLabel($rec->getStatut())) ?></span>
        <span class="muted"> · <?= e(ReclamationController::categorieLabel($rec->getCategorie())) ?> · déposée le <?= e(date('d/m/Y à H:i', strtotime($rec->getDateCreation()) ?: time())) ?></span>
    </p>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Ma demande</h2>
        <?php if (ReclamationController::isEditable($rec)): ?>
            <span class="actions">
                <a class="btn btn-small" href="index.php?action=reclamation_edit&amp;id=<?= e($rec->getId()) ?>">Modifier</a>
                <form method="post" action="index.php?action=reclamation_delete" class="inline"
                      data-confirm="Supprimer définitivement cette réclamation ?">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= e($rec->getId()) ?>">
                    <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                </form>
            </span>
        <?php endif; ?>
    </div>
    <p class="msg-text"><?= nl2br(e($rec->getDescription())) ?></p>
</section>

<section class="panel">
    <h2>Réponses du support (<?= count($reponses) ?>)</h2>
    <?php if (!$reponses): ?>
        <p class="muted">Aucune réponse pour le moment. Vous serez informé ici dès que l’équipe AgriNoVa aura répondu.</p>
    <?php endif; ?>
    <div class="thread">
        <?php foreach ($reponses as $p): ?>
            <article class="msg msg-staff">
                <header>
                    <b><?= e($p->getAuteurNom()) ?></b> <span class="badge badge-admin">Support</span>
                    <small class="muted"><?= e(date('d/m/Y à H:i', strtotime($p->getDateReponse()) ?: time())) ?></small>
                </header>
                <p class="msg-text"><?= nl2br(e($p->getMessage())) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($rec->getStatut() === 'cloturee'): ?>
        <p class="muted">Cette réclamation est clôturée. Pour un nouveau problème, <a href="index.php?action=reclamation_new">déposez une nouvelle réclamation</a>.</p>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
