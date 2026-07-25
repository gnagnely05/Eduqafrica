<?php
/** Page de retour après paiement Chariow. Ton accès est activé côté serveur via le webhook Pulse,
 * indépendamment de cette page — elle sert uniquement à rassurer le client visuellement. */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
$pageTitle = 'Merci pour ton paiement';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:520px; text-align:center;">
  <div class="alert alert-success"><strong>✅ Paiement reçu !</strong> Ton accès s'active automatiquement en quelques secondes.</div>
  <div style="margin-top:24px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
    <a href="/compte.php" class="btn btn-primary">Voir mon compte</a>
    <a href="/chat.php" class="btn btn-ghost">Aller au chat</a>
    <a href="/cv-generator.php" class="btn btn-ghost">Mes CV</a>
  </div>
  <p class="meta" style="margin-top:20px;">Si ton accès n'apparaît pas après une minute, <a href="/contact.php">contacte-nous</a>.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
