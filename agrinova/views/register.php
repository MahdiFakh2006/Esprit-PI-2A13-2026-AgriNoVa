<?php
/**
 * views/register.php
 * Attend : $errors (array), $old (array), $csrfToken (string)
 */
$title = 'Créer un compte';
$roleChoices = [
    'agriculteur' => ['Agriculteur', 'J’achète, je loue, je fais diagnostiquer mes cultures.'],
    'vendeur'     => ['Vendeur', 'Je vends ou je loue du matériel agricole intelligent.'],
    'parrain'     => ['Parrain', 'J’adopte un olivier et je soutiens un agriculteur.'],
];
$selectedRole = $old['role'] ?? 'agriculteur';
ob_start();
?>
<section class="auth-card auth-wide">
    <h1>Créer un compte</h1>
    <p class="muted">Rejoignez la communauté AgriNoVa.</p>

    <?php if (!empty($errors['global'])): ?>
        <div class="alert alert-error"><?= e($errors['global']) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?action=register" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <fieldset class="field">
            <legend>Type de compte</legend>
            <div class="role-choices">
                <?php foreach ($roleChoices as $value => [$label, $desc]): ?>
                    <label class="role-choice">
                        <input type="radio" name="role" value="<?= e($value) ?>" <?= $selectedRole === $value ? 'checked' : '' ?>>
                        <span><b><?= e($label) ?></b><small><?= e($desc) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?= fieldError($errors, 'role') ?>
        </fieldset>

        <div class="grid-2">
            <div class="field">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?= old($old, 'prenom') ?>"
                       class="<?= invalid($errors, 'prenom') ?>" data-rules="required|name" maxlength="50">
                <?= fieldError($errors, 'prenom') ?>
            </div>
            <div class="field">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= old($old, 'nom') ?>"
                       class="<?= invalid($errors, 'nom') ?>" data-rules="required|name" maxlength="50">
                <?= fieldError($errors, 'nom') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?= old($old, 'email') ?>"
                       class="<?= invalid($errors, 'email') ?>" data-rules="required|email" maxlength="150" autocomplete="email">
                <?= fieldError($errors, 'email') ?>
            </div>
            <div class="field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= old($old, 'telephone') ?>"
                       class="<?= invalid($errors, 'telephone') ?>" data-rules="required|phone" placeholder="22 123 456" maxlength="20">
                <?= fieldError($errors, 'telephone') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                       class="<?= invalid($errors, 'mot_de_passe') ?>" data-rules="required|password" autocomplete="new-password">
                <small class="hint">8 caractères minimum, avec une majuscule, une minuscule et un chiffre.</small>
                <?= fieldError($errors, 'mot_de_passe') ?>
            </div>
            <div class="field">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation"
                       class="<?= invalid($errors, 'confirmation') ?>" data-rules="required|match:mot_de_passe" autocomplete="new-password">
                <?= fieldError($errors, 'confirmation') ?>
            </div>
        </div>

        <div class="field">
            <label class="check">
                <input type="checkbox" name="cgu" value="1" data-rules="checked" <?= !empty($old['cgu']) ? 'checked' : '' ?>>
                J’accepte les conditions d’utilisation d’AgriNoVa.
            </label>
            <?= fieldError($errors, 'cgu') ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
    </form>
    <p class="auth-switch">Déjà inscrit ? <a href="index.php?action=login">Se connecter</a></p>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
