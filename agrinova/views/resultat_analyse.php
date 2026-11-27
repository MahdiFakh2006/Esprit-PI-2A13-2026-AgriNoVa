<?php
/**
 * views/resultat_analyse.php — FRONT OFFICE : résultat d'une analyse.
 * Attend : $analyse (AnalyseModel), $maladie (MaladieModel|null)
 */
$title = 'Résultat du diagnostic';
$confiance = (float) $analyse->getConfiance();
$atteinte  = (float) $analyse->getTauxAtteinte();
$douteux   = $confiance < AnalyseController::SEUIL_CONFIANCE;
$graviteLabel = MaladieController::GRAVITES[$analyse->getGraviteMaladie()] ?? '';
ob_start();
?>
<link rel="stylesheet" href="assets/css/diagnostic.css">

<section class="panel diag-result">
    <div class="diag-result-photo">
        <img src="uploads/analyses/<?= e(rawurlencode(basename($analyse->getImage()))) ?>" alt="Feuille analysée">
        <p class="muted"><small><?= e($analyse->getCulture()) ?> · <?= e(date('d/m/Y à H:i', strtotime($analyse->getDateAnalyse()) ?: time())) ?></small></p>
    </div>

    <div class="diag-result-text">
        <?php if ($analyse->isSaine()): ?>
            <p class="badge diag-ok">Feuille saine</p>
            <h1>Aucune maladie détectée 🌿</h1>
            <p>Seulement <b><?= e(number_format($atteinte, 1, ',', ' ')) ?> %</b> de la feuille présente des taches.</p>
        <?php elseif ($analyse->isInconnue()): ?>
            <p class="badge diag-warn">Symptômes non identifiés</p>
            <h1>Des taches, mais pas de maladie connue</h1>
            <p><b><?= e(number_format($atteinte, 1, ',', ' ')) ?> %</b> de la feuille est touchée, mais aucune maladie de notre base ne correspond pour la culture « <?= e($analyse->getCulture()) ?> ».</p>
        <?php else: ?>
            <p class="badge diag-bad">Maladie probable · gravité <?= e(mb_strtolower($graviteLabel)) ?></p>
            <h1><?= e($analyse->getNomMaladie()) ?></h1>
            <p><b><?= e(number_format($atteinte, 1, ',', ' ')) ?> %</b> de la feuille est touchée.</p>
            <?php if ($maladie): ?>
                <h3>Symptômes</h3>
                <p><?= e($maladie->getSymptomes()) ?></p>
            <?php endif; ?>
            <h3>Traitement conseillé</h3>
            <p><?= e($analyse->getTraitement()) ?></p>
        <?php endif; ?>

        <div class="diag-meter">
            <span>Confiance</span>
            <span class="bar-track"><span class="bar-fill <?= $douteux ? 'diag-fill-warn' : 'diag-fill-ok' ?>" style="width: <?= (int) round($confiance) ?>%"></span></span>
            <b><?= e(number_format($confiance, 0)) ?> %</b>
        </div>

        <?php if ($douteux): ?>
            <div class="alert alert-error">
                Confiance faible : faites confirmer ce résultat par un expert. Déposez une réclamation au support (Module 5)
                en joignant cette analyse, ou consultez les formations sur les maladies des cultures (Module 6).
            </div>
        <?php endif; ?>

        <p class="muted"><small>Résultat indicatif : il ne remplace pas l’avis d’un agronome.</small></p>
        <p>
            <a class="btn btn-primary" href="index.php?action=diagnostic">Nouvelle analyse</a>
            <a class="btn btn-ghost" href="index.php?action=mes_analyses">Mes analyses</a>
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
