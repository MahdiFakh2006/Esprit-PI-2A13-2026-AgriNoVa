<?php
/**
 * views/admin_users.php — liste des utilisateurs (Read) avec recherche, filtres, tri et pagination.
 * Attend : $result (users, total, page, pages, q, role, statut, sort), $csrfToken
 */
$title = 'Utilisateurs';
$active = 'users';
$query = ['action' => 'admin_users', 'q' => $result['q'], 'role' => $result['role'], 'statut' => $result['statut'], 'sort' => $result['sort']];
ob_start();
?>
<div class="toolbar">
    <form method="get" action="index.php" class="filters">
        <input type="hidden" name="action" value="admin_users">
        <input type="search" name="q" value="<?= e($result['q']) ?>" placeholder="Rechercher un nom ou un e-mail" maxlength="100">
        <select name="role">
            <option value="">Tous les rôles</option>
            <?php foreach (AuthController::ALL_ROLES as $r): ?>
                <option value="<?= e($r) ?>" <?= $result['role'] === $r ? 'selected' : '' ?>><?= e(roleLabel($r)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="statut">
            <option value="">Tous les statuts</option>
            <option value="actif" <?= $result['statut'] === 'actif' ? 'selected' : '' ?>>Actifs</option>
            <option value="bloque" <?= $result['statut'] === 'bloque' ? 'selected' : '' ?>>Bloqués</option>
        </select>
        <select name="sort">
            <option value="recent" <?= $result['sort'] === 'recent' ? 'selected' : '' ?>>Plus récents</option>
            <option value="ancien" <?= $result['sort'] === 'ancien' ? 'selected' : '' ?>>Plus anciens</option>
            <option value="nom" <?= $result['sort'] === 'nom' ? 'selected' : '' ?>>Nom (A → Z)</option>
        </select>
        <button type="submit" class="btn btn-dark">Filtrer</button>
    </form>
    <a class="btn btn-primary" href="index.php?action=admin_user_create">+ Ajouter un utilisateur</a>
</div>

<section class="panel">
    <p class="muted"><?= e($result['total']) ?> utilisateur(s) trouvé(s)</p>
    <table class="table">
        <thead>
        <tr><th>#</th><th>Utilisateur</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th class="right">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$result['users']): ?>
            <tr><td colspan="7" class="empty">Aucun utilisateur ne correspond à la recherche.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['users'] as $u): ?>
            <tr>
                <td><?= e($u->getId()) ?></td>
                <td><b><?= e($u->getNomComplet()) ?></b><br><small class="muted"><?= e($u->getEmail()) ?></small></td>
                <td><?= e($u->getTelephone()) ?></td>
                <td><span class="badge badge-<?= e($u->getRole()) ?>"><?= e(roleLabel($u->getRole())) ?></span></td>
                <td><span class="status status-<?= e($u->getStatut()) ?>"><?= $u->isBloque() ? 'Bloqué' : 'Actif' ?></span></td>
                <td><?= e(date('d/m/Y', strtotime($u->getDateCreation()) ?: time())) ?></td>
                <td class="actions">
                    <a class="btn btn-small" href="index.php?action=admin_user_edit&amp;id=<?= e($u->getId()) ?>">Modifier</a>
                    <?php if ($u->getId() !== AuthController::currentUserId()): ?>
                        <form method="post" action="index.php?action=admin_user_toggle" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= e($u->getId()) ?>">
                            <button type="submit" class="btn btn-small"><?= $u->isBloque() ? 'Débloquer' : 'Bloquer' ?></button>
                        </form>
                        <form method="post" action="index.php?action=admin_user_delete" class="inline"
                              data-confirm="Supprimer définitivement <?= e($u->getNomComplet()) ?> ?">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= e($u->getId()) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                        </form>
                    <?php else: ?>
                        <small class="muted">(vous)</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($result['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a class="<?= $p === $result['page'] ? 'current' : '' ?>" href="index.php?<?= e(http_build_query($query + ['page' => $p])) ?>"><?= $p ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout_admin.php';
