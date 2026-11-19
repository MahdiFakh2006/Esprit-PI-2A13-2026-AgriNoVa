<?php
/**
 * views/login.php
 * Attend : $errors (array), $oldEmail (string), $csrfToken (string)
 */
$title = 'Connexion';
ob_start();
?>
<section class="auth-card">
    <h1>Connexion</h1>
    <p class="muted">Accédez à votre espace AgriNoVa.</p>

    <?php if (!empty($errors['global'])): ?>
        <div class="alert alert-error"><?= e($errors['global']) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?action=login" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>"
                   data-rules="required|email" autocomplete="email" maxlength="150">
            <?= fieldError([], 'email') ?>
        </div>

        <div class="field">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   data-rules="required" autocomplete="current-password">
            <?= fieldError([], 'mot_de_passe') ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
    </form>
    <p class="auth-switch">Pas encore de compte ? <a href="index.php?action=register">Créer un compte</a></p>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
