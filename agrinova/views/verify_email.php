<?php
/**
 * views/verify_email.php — saisie du code de vérification reçu par e-mail.
 * Attend : $pendingEmail (string), $errors (array), $csrfToken (string)
 */
$title = 'Vérifier mon adresse e-mail';
ob_start();
?>
<section class="auth-card">
    <h1>Vérifiez votre adresse e-mail</h1>
    <p class="muted">
        Un code à 6 chiffres a été envoyé à <b><?= maskEmail($pendingEmail) ?></b>.
        Il est valable <?= (int) AuthController::CODE_MINUTES ?> minutes.
    </p>

    <?php if (!empty($errors['global'])): ?>
        <div class="alert alert-error"><?= e($errors['global']) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?action=verify" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="field">
            <label for="code">Code de vérification</label>
            <input type="text" id="code" name="code" class="<?= invalid($errors, 'code') ?>"
                   data-rules="required|number" inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" autocomplete="one-time-code" placeholder="123456"
                   style="font-size:1.6rem;letter-spacing:.5rem;text-align:center">
            <?= fieldError($errors, 'code') ?>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Vérifier et activer mon compte</button>
    </form>

    <form method="post" action="index.php?action=verify_resend" class="auth-switch">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        Vous n’avez rien reçu ? Vérifiez vos spams, puis
        <button type="submit" class="btn btn-ghost">Renvoyer un code</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
