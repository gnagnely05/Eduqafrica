<?php
$pageTitle = "Mentions légales";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:720px;">
  <h1>Mentions légales</h1>
  <div class="article-body" style="margin-top:24px;">
    <h2>Éditeur du site</h2>
    <p><?= e(SITE_NAME) ?> — <em>[RAISON SOCIALE, forme juridique, RCCM À COMPLÉTER]</em><br>
    Siège : <em>[ADRESSE À COMPLÉTER]</em>, Abidjan, Côte d'Ivoire<br>
    Email : <?= e(SITE_EMAIL) ?><br>
    Directeur de la publication : <em>[NOM À COMPLÉTER]</em></p>

    <h2>Hébergement</h2>
    <p>Hostinger International Ltd — 61 Lordou Vironos Street, 6023 Larnaca, Chypre — hostinger.com</p>

    <h2>Propriété intellectuelle</h2>
    <p>L'ensemble des contenus originaux du site (textes, logo, design) est protégé. Les offres d'emploi et bourses référencées renvoient vers leurs sources officielles ; les marques citées appartiennent à leurs propriétaires respectifs.</p>

    <h2>Responsabilité</h2>
    <p>Les informations d'orientation fournies (y compris par le conseiller IA) le sont à titre indicatif et ne remplacent pas les informations officielles des établissements et organismes. Vérifie toujours les conditions de candidature sur les sites officiels. <?= e(SITE_NAME) ?> ne peut être tenu responsable des décisions prises sur la base de ces informations.</p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
