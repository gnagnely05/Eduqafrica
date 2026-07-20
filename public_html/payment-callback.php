<?php
/** Retour de paiement Moneroo — vérification serveur systématique. */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/moneroo.php';

$paymentId = (int)($_GET['pid'] ?? 0);
if (!$paymentId) redirect('/');

$status = monerooVerifyAndActivate($paymentId);

$stmt = db()->prepare('SELECT purpose, related_id FROM payment_transactions WHERE id = ?');
$stmt->execute([$paymentId]);
$tx = $stmt->fetch();

$pageTitle = 'Paiement';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:560px;">
<?php if ($status === 'success'): ?>
  <div class="alert alert-success"><strong>✅ Paiement confirmé !</strong> Ton accès est activé.</div>
  <?php
  $next = '/'; $label = 'Retour à l\'accueil';
  if ($tx) {
      switch ($tx['purpose']) {
          case 'cv_download':
              $next = '/cv-preview.php?id=' . (int)$tx['related_id'];
              $label = 'Télécharger mon CV'; break;
          case 'chat_single_unlock':
          case 'chat_subscription_monthly':
              $next = '/chat.php'; $label = 'Retourner au chat'; break;
          case 'bourses_single_unlock':
          case 'bourses_subscription_monthly':
              $next = '/bourses.php'; $label = 'Voir les bourses'; break;
          case 'bundle_subscription_monthly':
              $next = '/chat.php'; $label = 'Profiter de mon abonnement'; break;
      }
  }
  ?>
  <a href="<?= e($next) ?>" class="btn btn-primary"><?= e($label) ?></a>
<?php elseif ($status === 'pending'): ?>
  <div class="alert" style="background:#FFF8E6; color:#7A5C00;"><strong>⏳ Paiement en cours de confirmation.</strong> Si tu as validé le paiement sur ton téléphone, ton accès sera activé d'ici quelques minutes. Recharge cette page pour vérifier.</div>
  <a href="?pid=<?= $paymentId ?>" class="btn btn-ghost">Vérifier à nouveau</a>
<?php else: ?>
  <div class="alert alert-error"><strong>❌ Paiement non abouti.</strong> Aucun montant ne t'a été facturé si tu as annulé. Tu peux réessayer.</div>
  <a href="javascript:history.back()" class="btn btn-primary">Réessayer</a>
<?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
