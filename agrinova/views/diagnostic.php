<?php
/**
 * views/diagnostic.php — FRONT OFFICE (agriculteur) : envoyer la photo d'une feuille.
 * Attend : $errors, $old, $cultures (string[]), $csrfToken
 */
$title = 'Diagnostic IA';
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">

<section class="hero">
    <p class="badge badge-agriculteur">Module 3 · Diagnostic IA</p>
    <h1>Diagnostiquer une feuille</h1>
    <p class="muted">Prenez en photo une feuille qui vous inquiète : AgriNoVa repère les taches et propose la maladie la plus probable avec son traitement.</p>
</section>

<div class="diag-grid">
    <section class="panel">
        <?php if (!empty($errors['global'])): ?><div class="alert alert-error"><?= e($errors['global']) ?></div><?php endif; ?>

        <form method="post" action="index.php?action=diagnostic" enctype="multipart/form-data" class="js-validate" novalidate id="diag-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="field">
                <label for="culture">Culture</label>
                <select id="culture" name="culture" class="<?= invalid($errors, 'culture') ?>" data-rules="required">
                    <option value="">— Choisir —</option>
                    <?php foreach ($cultures as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($old['culture'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError($errors, 'culture') ?>
            </div>

            <div class="field">
                <label for="image">Photo de la feuille (JPG ou PNG, 5 Mo maximum)</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png" class="<?= invalid($errors, 'image') ?>" data-rules="required">
                <small class="hint">Une seule feuille, en gros plan, bien éclairée, sur un fond uni.</small>
                <?= fieldError($errors, 'image') ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Analyser la feuille</button>
        </form>
        <p class="muted"><small>Le résultat est indicatif et ne remplace pas l’avis d’un agronome. Limite : <?= (int) AnalyseController::MAX_PAR_JOUR ?> analyses par jour.</small></p>
        <p><a href="index.php?action=mes_analyses">Voir mes analyses →</a></p>
    </section>

    <section class="panel diag-preview" id="diag-preview" hidden>
        <h2>Aperçu de l’analyse</h2>
        <div class="diag-images">
            <figure><img id="diag-photo" alt="Photo choisie"><figcaption>Votre photo</figcaption></figure>
            <figure><canvas id="diag-carte"></canvas><figcaption>Taches repérées</figcaption></figure>
        </div>
        <div id="diag-barres"></div>
        <p class="muted"><small>Aperçu calculé dans votre navigateur. Le diagnostic final est fait par le serveur.</small></p>
    </section>
</div>

<script src="assets/js/diagnostic.js" defer></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
