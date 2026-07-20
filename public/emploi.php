<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM jobs WHERE slug = ?');
$stmt->execute([$slug]);
$j = $stmt->fetch();
if (!$j) { http_response_code(404); $pageTitle = 'Offre introuvable'; require_once __DIR__ . '/../includes/header.php'; echo '<div class="container section"><p>Cette offre n\'existe plus. <a href="/emplois.php">Voir les offres actuelles</a>.</p></div>'; require_once __DIR__ . '/../includes/footer.php'; exit; }

$pageTitle = $j['title'] . ($j['company'] ? ' — ' . $j['company'] : '');
$pageDesc = mb_substr($j['description'], 0, 155);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <p><a href="/emplois.php">← Toutes les offres</a></p>
  <h1 style="margin-top:8px;"><?= e($j['title']) ?></h1>
  <p class="meta" style="margin:8px 0 20px;">
    <?= e($j['company'] ?? '') ?>
    <?= $j['location'] ? ' · ' . e($j['location']) : '' ?><?= $j['country'] ? ', ' . e($j['country']) : '' ?>
    <?= $j['contract_type'] ? ' · ' . e($j['contract_type']) : '' ?>
    <?= $j['category'] ? ' · ' . e($j['category']) : '' ?>
  </p>

  <?php if (!$j['is_active']): ?>
    <div class="alert alert-error">⚠️ Cette offre a probablement expiré.</div>
  <?php elseif ($j['deadline']): ?>
    <p class="deadline" style="font-size:1rem; margin-bottom:20px;">📅 Date limite : <?= dateFr($j['deadline']) ?></p>
  <?php endif; ?>

  <div class="card">
    <p style="white-space:pre-wrap;"><?= e($j['description']) ?></p>
    <a href="<?= e($j['source_url']) ?>" class="btn btn-primary" style="margin-top:18px;" target="_blank" rel="noopener nofollow">Voir l'annonce complète et postuler ↗</a>
    <p class="meta" style="margin-top:10px;">Source : <?= e($j['source_domain'] ?? '') ?> — postule toujours via le site original.</p>
  </div>

  <div class="card" style="margin-top:20px; background:var(--paper-dim);">
    <p><strong>💡 Ton CV n'est pas prêt ?</strong> Crée un CV professionnel en 5 minutes avec notre générateur.</p>
    <a href="/cv-generator.php" class="btn btn-amber" style="margin-top:10px;">Créer mon CV</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
