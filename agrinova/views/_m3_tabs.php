<?php
/** views/_m3_tabs.php — onglets du Back Office du Module 3 (inclus par les vues admin M3). */
$m3Tab = $m3Tab ?? 'maladies';
?>
<nav class="m3-tabs">
    <a class="<?= $m3Tab === 'maladies' ? 'active' : '' ?>" href="index.php?action=admin_maladies">Maladies</a>
    <a class="<?= $m3Tab === 'analyses' ? 'active' : '' ?>" href="index.php?action=admin_analyses">Analyses et statistiques</a>
</nav>
