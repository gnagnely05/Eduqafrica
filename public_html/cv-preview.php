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

  <?php if (!empty($data['rationale'])): ?>
    <div class="cv-rationale"><strong>💡 Pourquoi ce CV te correspond :</strong> <?= e($data['rationale']) ?></div>
  <?php endif; ?>

  <div class="cv-card">
    <div class="cv-header">
      <?php if (!$isPaid): ?>
        <div class="cv-watermark"><span>APERÇU — <?= e(SITE_NAME) ?></span></div>
      <?php endif; ?>
      <h2><?= e($data['full_name']) ?></h2>
      <?php if (!empty($data['headline'])): ?><p class="cv-headline"><?= e($data['headline']) ?></p><?php endif; ?>
    </div>

    <div class="cv-body-grid">
      <div class="cv-sidebar">
        <h3>Contact</h3>
        <?php if (!empty($data['email'])): ?><p class="cv-contact-line">✉ <?= e($data['email']) ?></p><?php endif; ?>
        <?php if (!empty($data['phone'])): ?><p class="cv-contact-line">☎ <?= e($data['phone']) ?></p><?php endif; ?>
        <?php if (!empty($data['location'])): ?><p class="cv-contact-line">📍 <?= e($data['location']) ?></p><?php endif; ?>
        <?php if (!empty($data['linkedin'])): ?><p class="cv-contact-line">🔗 <?= e($data['linkedin']) ?></p><?php endif; ?>

        <?php if (!empty($data['skills'])): ?>
          <h3>Compétences</h3>
          <?php foreach ($data['skills'] as $skill): ?>
            <?php if (trim($skill) !== ''): ?><span class="cv-tag"><?= e($skill) ?></span><?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($data['languages'])): ?>
          <h3>Langues</h3>
          <p class="meta"><?= nl2br(e($data['languages'])) ?></p>
        <?php endif; ?>
      </div>

      <div class="cv-main">
        <?php if (!empty($data['summary'])): ?>
          <h3>Profil</h3>
          <p style="margin-bottom:20px;"><?= nl2br(e($data['summary'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($data['experience'])): ?>
          <h3>Expériences</h3>
          <?php foreach ($data['experience'] as $exp): ?>
            <div class="cv-item">
              <p><strong><?= e($exp['title']) ?></strong> — <?= e($exp['company']) ?><br>
              <span class="meta"><?= e($exp['period']) ?><?= $exp['city'] ? ' · ' . e($exp['city']) : '' ?></span></p>
              <?php if ($exp['desc']): ?><p class="desc"><?= nl2br(e($exp['desc'])) ?></p><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($data['education'])): ?>
          <h3>Formation</h3>
          <?php foreach ($data['education'] as $edu): ?>
            <div class="cv-item">
              <p><strong><?= e($edu['degree']) ?></strong><br><?= e($edu['school']) ?><br>
              <span class="meta"><?= e($edu['years']) ?><?= $edu['city'] ? ' · ' . e($edu['city']) : '' ?></span></p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
