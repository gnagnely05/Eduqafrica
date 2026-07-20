<?php
/** Aperçu du CV (gratuit, filigrane) + téléchargement PDF (payant). */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$cvId = (int)($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM cv_documents WHERE id = ?');
$stmt->execute([$cvId]);
$cv = $stmt->fetch();

if (!$cv) { http_response_code(404); exit('CV introuvable.'); }

$user = currentUser();
$isOwner = ($user && (int)$cv['user_id'] === (int)$user['id'])
        || in_array($cvId, $_SESSION['own_cvs'] ?? [], true);
if (!$isOwner && !isAdmin()) { http_response_code(403); exit('Accès refusé.'); }

$data = json_decode($cv['data_json'], true);
$isPaid = (bool)$cv['is_paid'];

if (isset($_GET['download']) && $isPaid) {
    require_once __DIR__ . '/../includes/cv-pdf.php';
    generateCvPdf($cv, $data);
}

$pageTitle = "Aperçu de ton CV";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:820px;">
  <h1>Ton CV est prêt 🎉</h1>
  <p style="color:var(--ink-soft); margin:10px 0 24px;">
    <?php if ($isPaid): ?>
      Paiement confirmé — télécharge ton PDF sans filigrane.
    <?php else: ?>
      Voici l'aperçu gratuit. Pour obtenir le PDF professionnel sans filigrane : <?= number_format(price('cv_download'), 0, ',', ' ') ?> F CFA.
    <?php endif; ?>
  </p>

  <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px;">
    <?php if ($isPaid): ?>
      <a href="?id=<?= $cvId ?>&download=1" class="btn btn-primary">⬇️ Télécharger le PDF</a>
    <?php elseif ($user): ?>
      <a href="/payer.php?type=cv&cvid=<?= $cvId ?>" class="btn btn-coral">Débloquer le téléchargement — <?= number_format(price('cv_download'), 0, ',', ' ') ?> F</a>
    <?php else: ?>
      <a href="/register.php?back=<?= urlencode('/cv-preview.php?id=' . $cvId) ?>" class="btn btn-coral">Créer un compte pour télécharger — <?= number_format(price('cv_download'), 0, ',', ' ') ?> F</a>
    <?php endif; ?>
    <a href="/cv-generator.php" class="btn btn-ghost">Modifier / recommencer</a>
  </div>

  <div class="card" style="position:relative; overflow:hidden; padding:40px;">
    <?php if (!$isPaid): ?>
      <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none;">
        <span style="transform:rotate(-30deg); font-size:3rem; font-weight:700; color:rgba(27,36,82,.08); font-family:var(--font-display); white-space:nowrap;">
          APERÇU — <?= e(SITE_NAME) ?>
        </span>
      </div>
    <?php endif; ?>

    <h2 style="margin-bottom:2px;"><?= e($data['full_name']) ?></h2>
    <?php if (!empty($data['headline'])): ?><p style="color:var(--coral); font-weight:600;"><?= e($data['headline']) ?></p><?php endif; ?>
    <p class="meta" style="margin:6px 0 20px;">
      <?= e($data['email']) ?><?= $data['phone'] ? ' · ' . e($data['phone']) : '' ?><?= $data['location'] ? ' · ' . e($data['location']) : '' ?>
    </p>

    <?php if (!empty($data['summary'])): ?>
      <h3 style="border-bottom:2px solid var(--amber); padding-bottom:4px; margin-bottom:8px;">Profil</h3>
      <p style="margin-bottom:20px;"><?= nl2br(e($data['summary'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($data['experience'])): ?>
      <h3 style="border-bottom:2px solid var(--amber); padding-bottom:4px; margin-bottom:8px;">Expériences</h3>
      <?php foreach ($data['experience'] as $exp): ?>
        <p style="margin-bottom:4px;"><strong><?= e($exp['title']) ?></strong> — <?= e($exp['company']) ?>
        <span class="meta"><?= e($exp['period']) ?><?= $exp['city'] ? ' · ' . e($exp['city']) : '' ?></span></p>
        <?php if ($exp['desc']): ?><p style="margin-bottom:14px; font-size:.95rem;"><?= nl2br(e($exp['desc'])) ?></p><?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($data['education'])): ?>
      <h3 style="border-bottom:2px solid var(--amber); padding-bottom:4px; margin:16px 0 8px;">Formation</h3>
      <?php foreach ($data['education'] as $edu): ?>
        <p style="margin-bottom:8px;"><strong><?= e($edu['degree']) ?></strong> — <?= e($edu['school']) ?>
        <span class="meta"><?= e($edu['years']) ?><?= $edu['city'] ? ' · ' . e($edu['city']) : '' ?></span></p>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($data['skills'])): ?>
      <h3 style="border-bottom:2px solid var(--amber); padding-bottom:4px; margin:16px 0 8px;">Compétences</h3>
      <p><?= e(implode(' · ', $data['skills'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($data['languages'])): ?>
      <h3 style="border-bottom:2px solid var(--amber); padding-bottom:4px; margin:16px 0 8px;">Langues</h3>
      <p><?= e($data['languages']) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
