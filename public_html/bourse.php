<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM scholarships WHERE slug = ? AND is_active = 1');
$stmt->execute([$slug]);
$b = $stmt->fetch();
if (!$b) { http_response_code(404); $pageTitle = 'Bourse introuvable'; require_once __DIR__ . '/../includes/header.php'; echo '<div class="container section"><p>Cette bourse n\'existe plus ou a expiré. <a href="/bourses.php">Voir les bourses ouvertes</a>.</p></div>'; require_once __DIR__ . '/../includes/footer.php'; exit; }

$user = currentUser();
$userId = $user ? (int)$user['id'] : null;
$unlocked = !$b['is_premium']
    || ($userId && hasPremiumAccess($userId, 'bourses', (int)$b['id']));

$pageTitle = $b['title'];
$pageDesc = mb_substr($b['description'], 0, 155);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <p><a href="/bourses.php">← Toutes les bourses</a></p>
  <?php if ($b['is_premium']): ?><span class="badge badge-premium">Bourse Premium</span><?php endif; ?>
  <h1 style="margin-top:8px;"><?= e($b['title']) ?></h1>
  <p class="meta" style="margin:8px 0 24px;">
    <?= e($b['organization'] ?? '') ?>
    <?= $b['destination_country'] ? ' · Destination : ' . e($b['destination_country']) : '' ?>
    <?= $b['study_level'] ? ' · ' . e($b['study_level']) : '' ?>
    <?= $b['field'] ? ' · ' . e($b['field']) : '' ?>
  </p>

  <?php if ($b['deadline']): ?>
    <p class="deadline" style="font-size:1rem; margin-bottom:20px;">📅 Date limite : <?= dateFr($b['deadline']) ?></p>
  <?php endif; ?>

  <?= shareButtons($b['title'], SITE_URL . '/bourse.php?slug=' . $b['slug']) ?>

  <?php if ($unlocked): ?>
    <div class="card">
      <p style="white-space:pre-wrap;"><?= e($b['description']) ?></p>
      <?php if ($b['country']): ?><p style="margin-top:12px;"><strong>Éligibilité :</strong> <?= e($b['country']) ?></p><?php endif; ?>
      <a href="<?= e($b['source_url']) ?>" class="btn btn-primary" style="margin-top:18px;" target="_blank" rel="noopener nofollow">Candidater sur le site officiel ↗</a>
      <p class="meta" style="margin-top:10px;">Source : <?= e($b['source_domain'] ?? '') ?> — vérifie toujours les conditions sur le site officiel.</p>
    </div>
  <?php else: ?>
    <div class="card" style="position:relative;">
      <p style="filter:blur(5px); user-select:none;"><?= e(mb_substr($b['description'], 0, 250)) ?>…</p>
      <p style="filter:blur(5px); user-select:none;">Lien officiel de candidature : ██████████████</p>
    </div>
    <div class="paywall-box" style="margin-top:16px;">
      <p><strong>🔒 Bourse Premium.</strong> Débloque la description complète, les critères d'éligibilité et le lien officiel de candidature.</p>
      <div class="paywall-actions">
        <?php if ($user): ?>
          <a href="/payer.php?type=orientation_bourses" class="btn btn-amber">Orientation + Bourses — <?= price('orientation_bourses_monthly') ?> F/mois</a>
        <?php else: ?>
          <a href="/register.php?back=<?= urlencode('/bourse.php?slug=' . $b['slug']) ?>" class="btn btn-coral">Créer un compte pour débloquer</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
