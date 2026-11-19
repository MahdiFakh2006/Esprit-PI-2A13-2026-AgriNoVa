<?php
/**
 * views/profile.php — modification de son propre profil et de son mot de passe.
 * Attend : $user (UserModel), $errors, $pwdErrors, $old, $csrfToken, $layout
 */
$title = 'Mon profil';
$active = 'profile';
$values = $old ?: [
    'nom' => $user->getNom(), 'prenom' => $user->getPrenom(),
    'email' => $user->getEmail(), 'telephone' => $user->getTelephone(),
];
ob_start();
?>
<div class="profile-grid">
    <section class="panel">
        <h2>Informations personnelles</h2>
        <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>
        <form method="post" action="index.php?action=profile" class="js-validate" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="form" value="profil">
            <div class="grid-2">
                <div class="field">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?= old($values, 'prenom') ?>" class="<?= invalid($errors, 'prenom') ?>" data-rules="required|name" maxlength="50">
                    <?= fieldError($errors, 'prenom') ?>
                </div>
                <div class="field">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= old($values, 'nom') ?>" class="<?= invalid($errors, 'nom') ?>" data-rules="required|name" maxlength="50">
                    <?= fieldError($errors, 'nom') ?>
                </div>
            </div>
            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?= old($values, 'email') ?>" class="<?= invalid($errors, 'email') ?>" data-rules="required|email" maxlength="150">
                <?= fieldError($errors, 'email') ?>
            </div>
            <div class="field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= old($values, 'telephone') ?>" class="<?= invalid($errors, 'telephone') ?>" data-rules="required|phone" maxlength="20">
                <?= fieldError($errors, 'telephone') ?>
            </div>
            <p class="muted">Rôle : <b><?= e(roleLabel($user->getRole())) ?></b> (modifiable uniquement par l’administrateur)</p>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </section>

    <section class="panel">
        <h2>Changer de mot de passe</h2>
        <?php if (!empty($pwdErrors['global'])): ?><div class="alert alert-error"><?= e($pwdErrors['global']) ?></div><?php endif; ?>
        <form method="post" action="index.php?action=profile" class="js-validate" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="form" value="motdepasse">
            <div class="field">
                <label for="actuel">Mot de passe actuel</label>
                <input type="password" id="actuel" name="actuel" class="<?= invalid($pwdErrors, 'actuel') ?>" data-rules="required" autocomplete="current-password">
                <?= fieldError($pwdErrors, 'actuel') ?>
            </div>
            <div class="field">
                <label for="nouveau">Nouveau mot de passe</label>
                <input type="password" id="nouveau" name="nouveau" class="<?= invalid($pwdErrors, 'nouveau') ?>" data-rules="required|password" autocomplete="new-password">
                <small class="hint">8 caractères minimum, avec une majuscule, une minuscule et un chiffre.</small>
                <?= fieldError($pwdErrors, 'nouveau') ?>
            </div>
            <div class="field">
                <label for="confirmation_mdp">Confirmer</label>
                <input type="password" id="confirmation_mdp" name="confirmation" class="<?= invalid($pwdErrors, 'confirmation') ?>" data-rules="required|match:nouveau" autocomplete="new-password">
                <?= fieldError($pwdErrors, 'confirmation') ?>
            </div>
            <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
        </form>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/' . $layout;
