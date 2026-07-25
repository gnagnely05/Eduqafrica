<?php
/**
 * Point d'entrée des paiements — Chariow.
 * /payer.php?type=cv&cvid=12 | orientation_bourses
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chariow.php';

requireLogin();
$userId = (int)currentUser()['id'];
$type = $_GET['type'] ?? '';

switch ($type) {
    case 'cv':
        $cvId = (int)($_GET['cvid'] ?? 0);
        $stmt = db()->prepare('SELECT id, is_paid FROM cv_documents WHERE id = ? AND user_id = ?');
        $stmt->execute([$cvId, $userId]);
        $cv = $stmt->fetch();
        if (!$cv) { http_response_code(404); exit('CV introuvable.'); }
        if ($cv['is_paid']) { redirect('/cv-preview.php?id=' . $cvId); }
        chariowCreatePendingTx($userId, price('cv_download'), 'cv_download', $cvId);
        $productId = CHARIOW_PRODUCT_CV;
        $label = 'Téléchargement CV PDF';
        $amount = price('cv_download');
        $backUrl = '/cv-preview.php?id=' . $cvId;
        $backLabel = 'Retourner à mon CV';
        break;

    case 'orientation_bourses':
        chariowCreatePendingTx($userId, price('orientation_bourses_monthly'), 'orientation_bourses_monthly');
        $productId = CHARIOW_PRODUCT_ORIENTATION;
        $label = 'Abonnement Orientation + Bourses (1 mois)';
        $amount = price('orientation_bourses_monthly');
        $backUrl = '/chat.php';
        $backLabel = 'Retourner au chat';
        break;

    default:
        http_response_code(400);
        exit('Type de paiement inconnu.');
}

$pageTitle = 'Paiement';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:520px; text-align:center;">
  <h1 style="margin-bottom:6px;"><?= e($label) ?></h1>
  <p class="meta" style="margin-bottom:28px;"><?= xof($amount) ?></p>

  <div class="card" style="padding:32px; display:flex; justify-content:center;">
    <?= chariowWidgetHtml($productId) ?>
  </div>

  <p style="margin-top:24px; color:var(--ink-soft); font-size:.92rem;">
    Après le paiement, ton accès s'active automatiquement en quelques secondes.
  </p>
  <a href="<?= e($backUrl) ?>" class="btn btn-ghost" style="margin-top:12px;"><?= e($backLabel) ?></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
