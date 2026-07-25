<?php
$pageTitle = "Bourses d'études ouvertes";
$pageDesc = "Les bourses d'études actuellement ouvertes pour les étudiants africains francophones : licence, master, doctorat. Mises à jour chaque semaine.";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$userId = $user ? (int)$user['id'] : null;
$hasBoursesPremium = $userId ? hasActiveSubscription($userId, 'bourses') : false;

// Filtres
$level = $_GET['niveau'] ?? '';
$where = 'is_active = 1';
$params = [];
if (in_array($level, ['Licence', 'Master', 'Doctorat'], true)) {
    $where .= ' AND (study_level = ? OR study_level = "Tous niveaux")';
    $params[] = $level;
}

$stmt = db()->prepare(
    "SELECT id, title, slug, organization, description, study_level, destination_country, deadline, is_premium
     FROM scholarships WHERE $where
     ORDER BY is_premium DESC, (deadline IS NULL), deadline ASC
     LIMIT 60"
);
$stmt->execute($params);
$bourses = $stmt->fetchAll();
?>

<div class="container section">
  <span class="eyebrow" style="color:var(--coral); font-size:.8rem; letter-spacing:.12em; text-transform:uppercase; font-weight:600;">Bourses d'études</span>
  <h1>Bourses ouvertes en ce moment</h1>
  <p style="color:var(--ink-soft); margin:12px 0 8px; max-width:640px;">
    Mises à jour chaque semaine. Les bourses <span class="badge badge-premium">Premium</span> sont les bourses 100% financées et prestigieuses —
    <?php if ($hasBoursesPremium): ?>ton abonnement est actif ✅<?php else: ?>accès via l'abonnement Orientation + Bourses à <?= price('orientation_bourses_monthly') ?> F/mois<?php endif; ?>.
  </p>

  <div style="display:flex; gap:10px; margin:20px 0 28px; flex-wrap:wrap;">
    <a href="/bourses.php" class="btn <?= $level === '' ? 'btn-primary' : 'btn-ghost' ?>">Toutes</a>
    <a href="?niveau=Licence" class="btn <?= $level === 'Licence' ? 'btn-primary' : 'btn-ghost' ?>">Licence</a>
    <a href="?niveau=Master" class="btn <?= $level === 'Master' ? 'btn-primary' : 'btn-ghost' ?>">Master</a>
    <a href="?niveau=Doctorat" class="btn <?= $level === 'Doctorat' ? 'btn-primary' : 'btn-ghost' ?>">Doctorat</a>
  </div>

  <?php if (!$bourses): ?>
    <div class="card" style="text-align:center; padding:48px;">
      <p>Aucune bourse pour le moment — la prochaine mise à jour arrive bientôt. Reviens lundi !</p>
    </div>
  <?php else: ?>
  <div class="listing">
    <?php foreach ($bourses as $b):
      $locked = $b['is_premium'] && !$hasBoursesPremium
                && !($userId && hasSingleUnlock($userId, 'bourses_view', (int)$b['id']));
    ?>
    <div class="listing-item">
      <div>
        <h3>
          <?php if ($b['is_premium']): ?><span class="badge badge-premium">Premium</span> <?php endif; ?>
          <a href="/bourse.php?slug=<?= e($b['slug']) ?>"><?= e($b['title']) ?></a>
        </h3>
        <p class="meta"><?= e($b['organization'] ?? '') ?><?= $b['destination_country'] ? ' · ' . e($b['destination_country']) : '' ?><?= $b['study_level'] ? ' · ' . e($b['study_level']) : '' ?></p>
        <?php if ($locked): ?>
          <p style="font-size:.92rem; color:var(--ink-soft); filter:blur(4px); user-select:none;"><?= e(mb_substr($b['description'], 0, 140)) ?>…</p>
          <p style="font-size:.88rem;"><a href="/bourse.php?slug=<?= e($b['slug']) ?>">🔒 Débloquer cette bourse</a></p>
        <?php else: ?>
          <p style="font-size:.92rem; color:var(--ink-soft);"><?= e(mb_substr($b['description'], 0, 140)) ?>…</p>
        <?php endif; ?>
      </div>
      <?php if ($b['deadline']): ?>
        <span class="deadline">Avant le <?= dateFr($b['deadline']) ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$hasBoursesPremium): ?>
  <div class="paywall-box" style="margin-top:32px; max-width:640px;">
    <p><strong>💎 Accès Premium bourses</strong> — toutes les bourses 100% financées, avec descriptions complètes et liens officiels.</p>
    <div class="paywall-actions">
      <a href="<?= $user ? '/payer.php?type=orientation_bourses' : '/register.php?back=/bourses.php' ?>" class="btn btn-coral">Orientation + Bourses — <?= price('orientation_bourses_monthly') ?> F/mois</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
