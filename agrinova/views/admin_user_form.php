<?php
/**
 * views/admin_user_form.php — formulaire d'ajout (Create) et de modification (Update).
 * Attend : $editUser (UserModel|null), $old, $errors, $roles (RoleModel[]), $csrfToken
 */
$isEdit = $editUser !== null;
$title = $isEdit ? 'Modifier un utilisateur' : 'Ajouter un utilisateur';
$active = 'users';
$formAction = $isEdit ? 'index.php?action=admin_user_edit&id=' . $editUser->getId() : 'index.php?action=admin_user_create';
// En modification, le mot de passe est facultatif : « password? » = vérifié seulement s'il est rempli
$pwdRules = $isEdit ? 'password?' : 'required|password';
ob_start();
?>
<section class="panel form-panel">
    <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" class="js-validate" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="grid-2">
            <div class="field">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?= old($old, 'prenom') ?>" class="<?= invalid($errors, 'prenom') ?>" data-rules="required|name" maxlength="50">
                <?= fieldError($errors, 'prenom') ?>
            </div>
            <div class="field">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= old($old, 'nom') ?>" class="<?= invalid($errors, 'nom') ?>" data-rules="required|name" maxlength="50">
                <?= fieldError($errors, 'nom') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?= old($old, 'email') ?>" class="<?= invalid($errors, 'email') ?>" data-rules="required|email" maxlength="150">
                <?= fieldError($errors, 'email') ?>
            </div>
            <div class="field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= old($old, 'telephone') ?>" class="<?= invalid($errors, 'telephone') ?>" data-rules="required|phone" maxlength="20">
                <?= fieldError($errors, 'telephone') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="role">Rôle</label>
                <select id="role" name="role" class="<?= invalid($errors, 'role') ?>" data-rules="required">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r->getLibelle()) ?>" <?= ($old['role'] ?? '') === $r->getLibelle() ? 'selected' : '' ?>>
                            <?= e(roleLabel($r->getLibelle())) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'role') ?>
            </div>
            <div class="field">
                <label for="statut">Statut</label>
                <select id="statut" name="statut" class="<?= invalid($errors, 'statut') ?>">
                    <option value="actif" <?= ($old['statut'] ?? 'actif') === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="bloque" <?= ($old['statut'] ?? '') === 'bloque' ? 'selected' : '' ?>>Bloqué</option>
                </select>
                <?= fieldError($errors, 'statut') ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="mot_de_passe">Mot de passe<?= $isEdit ? ' (laisser vide pour ne pas changer)' : '' ?></label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" class="<?= invalid($errors, 'mot_de_passe') ?>" data-rules="<?= e($pwdRules) ?>" autocomplete="new-password">
                <small class="hint">8 caractères minimum, avec une majuscule, une minuscule et un chiffre.</small>
                <?= fieldError($errors, 'mot_de_passe') ?>
            </div>
            <div class="field">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation" class="<?= invalid($errors, 'confirmation') ?>" data-rules="match:mot_de_passe" autocomplete="new-password">
                <?= fieldError($errors, 'confirmation') ?>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="index.php?action=admin_users">Annuler</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter l’utilisateur' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
