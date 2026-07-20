<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$u = currentUser();
$uid = (int)$u['id'];

$subs = db()->prepare(
    "SELECT plan_type, started_at, expires_at, status FROM subscriptions
     WHERE user_id = ? ORDER BY expires_at DESC LIMIT 10"
);
$subs->execute([$uid]);
$subs = $subs->fetchAll();

$cvs = db()->prepare(
    'SELECT id, full_name, is_paid, created_at FROM cv_documents WHERE user_id = ? ORDER BY created_at DESC'
);
$cvs->execute([$uid]);
$cvs = $cvs->fetchAll();

$planLabels = ['chat' => 'Orientation IA', 'bourses' => 'Bourses premium', 'bundle' => 'Bundle chat + bourses'];

$pageTitle = "Mon compte";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <h1>Bonjour, <?= e($u['name']) ?> 👋</h1>
  <p class="meta" style="margin:6px 0 32px;"><?= e($u['email']) ?><?= $u['country'] ? ' · ' . e($u['country']) : '' ?> — <a href="/logout.php">Se déconnecter</a></p>

  <h2>Mes abonnements</h2>
  <?php if (!$subs): ?>
    <div class="card"><p>Aucun abonnement pour le moment.</p>
    <div class="paywall-actions" style="margin-top:12px;">
      <a href="/payer.php?type=chat_monthly" class="btn btn-ghost">Orientation IA — <?= price('chat_monthly') ?> F/mois</a>
      <a href="/payer.php?type=bourses_monthly" class="btn btn-ghost">Bourses — <?= price('bourses_monthly') ?> F/mois</a>
      <a href="/payer.php?type=bundle" class="btn btn-amber">Bundle — <?= price('bundle_monthly') ?> F/mois</a>
    </div></div>
  <?php else: ?>
    <div class="listing">
      <?php foreach ($subs as $s):
        $active = $s['status'] === 'active' && strtotime($s['expires_at']) > time(); ?>
      <div class="listing-item">
        <div>
          <h3><?= e($planLabels[$s['plan_type']] ?? $s['plan_type']) ?></h3>
          <p class="meta">Du <?= dateFr($s['started_at']) ?> au <?= dateFr($s['expires_at']) ?></p>
        </div>
        <span class="badge <?= $active ? 'badge-premium' : '' ?>"><?= $active ? 'Actif' : 'Expiré' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2 style="margin-top:40px;">Mes CV</h2>
  <?php if (!$cvs): ?>
    <div class="card"><p>Aucun CV créé. <a href="/cv-generator.php">Créer mon premier CV</a></p></div>
  <?php else: ?>
    <div class="listing">
      <?php foreach ($cvs as $cv): ?>
      <div class="listing-item">
        <div>
          <h3><a href="/cv-preview.php?id=<?= (int)$cv['id'] ?>">CV — <?= e($cv['full_name']) ?></a></h3>
          <p class="meta">Créé le <?= dateFr($cv['created_at']) ?></p>
        </div>
        <?php if ($cv['is_paid']): ?>
          <a href="/cv-preview.php?id=<?= (int)$cv['id'] ?>&download=1" class="btn btn-primary">⬇️ PDF</a>
        <?php else: ?>
          <a href="/payer.php?type=cv&cvid=<?= (int)$cv['id'] ?>" class="btn btn-coral">Débloquer — <?= number_format(price('cv_download'), 0, ',', ' ') ?> F</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
