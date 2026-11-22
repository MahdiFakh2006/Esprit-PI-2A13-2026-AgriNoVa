<?php
/**
 * views/faq.php — FRONT OFFICE : questions fréquentes (contenu statique, aucune donnée à charger).
 */
$title = 'FAQ';
$faq = [
    'Commande' => [
        ['Ma commande n’est pas arrivée, que faire ?', 'Vérifiez d’abord le délai de livraison indiqué sur la fiche du produit. Au-delà, déposez une réclamation dans la catégorie « Commande » avec le numéro et la date de la commande.'],
        ['Le produit reçu est défectueux.', 'Déposez une réclamation « Commande » en décrivant le défaut. Le vendeur et l’équipe AgriNoVa vous répondent dans le fil de la réclamation.'],
    ],
    'Location' => [
        ['Je souhaite signaler un problème sur un équipement loué.', 'Utilisez la catégorie « Location » en précisant l’équipement et les dates de réservation.'],
        ['Puis-je annuler ou modifier une réservation ?', 'Oui, tant qu’elle n’a pas commencé. Si ce n’est plus possible, déposez une réclamation « Location ».'],
    ],
    'Diagnostic IA' => [
        ['Le diagnostic de ma culture me semble incorrect.', 'Déposez une réclamation « Diagnostic » en indiquant la culture et la date de l’analyse. Une photo nette de la feuille améliore toujours la fiabilité du résultat.'],
    ],
    'Formation' => [
        ['Je n’arrive pas à m’inscrire à une formation.', 'Vérifiez qu’il reste des places, puis déposez une réclamation « Formation » avec le titre de la formation.'],
    ],
    'Parrainage' => [
        ['Je n’ai pas reçu le journal de mon olivier.', 'Déposez une réclamation « Parrainage » en précisant l’arbre parrainé.'],
    ],
    'Ma réclamation' => [
        ['Que signifient les statuts ?', 'Ouverte : bien reçue. En cours : l’équipe a répondu ou la traite. Résolue : une solution a été apportée. Clôturée : dossier terminé.'],
        ['Puis-je modifier ma réclamation ?', 'Oui, tant qu’elle est « ouverte » et qu’aucune réponse n’a été donnée. Ensuite, elle est figée pour garder l’historique.'],
        ['Qui peut voir mes réclamations ?', 'Vous seul, et l’équipe d’administration d’AgriNoVa.'],
    ],
];
ob_start();
?>
<section class="hero">
    <p><a href="index.php?action=mes_reclamations">← Mes réclamations</a></p>
    <h1>Questions fréquentes</h1>
    <p class="muted">Vous ne trouvez pas votre réponse ? <a href="index.php?action=reclamation_new">Déposez une réclamation</a>.</p>
</section>

<?php foreach ($faq as $groupe => $questions): ?>
    <section class="panel faq">
        <h2><?= e($groupe) ?></h2>
        <?php foreach ($questions as [$question, $reponse]): ?>
            <details>
                <summary><?= e($question) ?></summary>
                <p><?= e($reponse) ?></p>
            </details>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
