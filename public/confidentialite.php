<?php
$pageTitle = "Politique de confidentialité";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:720px;">
  <h1>Politique de confidentialité</h1>
  <div class="article-body" style="margin-top:24px;">
    <p class="meta">Dernière mise à jour : <?= date('d/m/Y') ?></p>

    <h2>1. Qui sommes-nous</h2>
    <p><?= e(SITE_NAME) ?> (<?= e(SITE_URL) ?>) est édité par <em>[NOM / RAISON SOCIALE À COMPLÉTER]</em>, basé à Abidjan, Côte d'Ivoire. Contact : <?= e(SITE_EMAIL) ?>.</p>

    <h2>2. Données collectées</h2>
    <p>Nous collectons : les informations de compte (nom, email, pays, mot de passe chiffré) ; les contenus que tu saisis (questions au conseiller IA, données de CV) ; les données de transaction (montant, référence de paiement — les paiements sont traités par notre prestataire Moneroo, nous ne stockons aucun numéro de carte ni code mobile money) ; et des données techniques (cookies de session).</p>

    <h2>3. Utilisation des données</h2>
    <p>Tes données servent à fournir les services (réponses d'orientation, génération de CV, accès premium), à traiter les paiements, et à améliorer la plateforme. Les questions posées au conseiller IA sont transmises à notre prestataire d'IA (via OpenRouter) pour générer la réponse ; ne partage pas d'informations sensibles inutiles dans le chat.</p>

    <h2>4. Publicité et cookies</h2>
    <p>Ce site utilise Google AdSense, un service de publicité fourni par Google. Google utilise des cookies pour diffuser des annonces en fonction de tes visites sur ce site et d'autres sites. Tu peux désactiver la publicité personnalisée dans les <a href="https://adssettings.google.com" rel="noopener" target="_blank">paramètres des annonces Google</a>. Pour en savoir plus : <a href="https://policies.google.com/technologies/partner-sites" rel="noopener" target="_blank">Comment Google utilise les données</a>.</p>

    <h2>5. Conservation et suppression</h2>
    <p>Tes données de compte sont conservées tant que ton compte est actif. Tu peux demander la suppression de ton compte et de tes données à tout moment en écrivant à <?= e(SITE_EMAIL) ?> ; nous traitons la demande sous 30 jours.</p>

    <h2>6. Sécurité</h2>
    <p>Les mots de passe sont chiffrés (hachage bcrypt), les échanges se font en HTTPS, et l'accès aux données est restreint.</p>

    <h2>7. Tes droits</h2>
    <p>Conformément à la loi ivoirienne n°2013-450 relative à la protection des données à caractère personnel (et au RGPD pour les utilisateurs concernés), tu disposes d'un droit d'accès, de rectification, d'opposition et de suppression de tes données. Exercice de ces droits : <?= e(SITE_EMAIL) ?>.</p>

    <p><em>[À FAIRE : vérifier/adapter ce texte avec un juriste, notamment ta raison sociale et l'éventuelle déclaration ARTCI.]</em></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
