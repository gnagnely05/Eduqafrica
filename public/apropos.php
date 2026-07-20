<?php
$pageTitle = "À propos";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:720px;">
  <h1>À propos de <?= e(SITE_NAME) ?></h1>
  <div class="article-body" style="margin-top:24px;">
    <p><strong><?= e(SITE_NAME) ?></strong> est né d'un constat simple : en Afrique francophone, des milliers d'élèves et d'étudiants choisissent leur filière sans accompagnement, passent à côté de bourses auxquelles ils étaient éligibles, et peinent à produire un CV qui valorise leur parcours.</p>
    <p>Notre mission : mettre entre les mains de chaque jeune francophone les outils d'orientation qu'il mérite — un conseiller disponible à toute heure, les opportunités de bourses et d'emploi du moment, et de quoi se présenter professionnellement.</p>
    <h2>Ce que nous proposons</h2>
    <p>Un <strong>conseiller d'orientation IA</strong> qui connaît les réalités des systèmes éducatifs africains francophones ; une <strong>veille hebdomadaire des bourses</strong> ouvertes aux étudiants africains ; des <strong>offres d'emploi et de stage</strong> sélectionnées pour les jeunes diplômés ; et un <strong>générateur de CV</strong> simple et professionnel.</p>
    <h2>Notre modèle</h2>
    <p>L'essentiel de la plateforme est gratuit. Certaines fonctionnalités avancées (analyses d'orientation approfondies, bourses premium, téléchargement de CV en PDF) sont proposées à des tarifs pensés pour les étudiants, payables par mobile money.</p>
    <p><em>[À PERSONNALISER : ajoute ici ton histoire, ton équipe, ta photo — Google et tes lecteurs apprécient de savoir qui est derrière le site.]</em></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
