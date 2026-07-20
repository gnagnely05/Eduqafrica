<?php
$pageTitle = "Offres d'emploi et stages";
$pageDesc = "Offres d'emploi et de stage récentes pour jeunes diplômés en Afrique francophone. Mises à jour chaque semaine, liens directs vers les sources.";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$country = $_GET['pays'] ?? '';
$contract = $_GET['contrat'] ?? '';
$where = 'is_active = 1';
$params = [];
if ($country !== '') { $where .= ' AND country = ?'; $params[] = $country; }
if ($contract !== '') { $where .= ' AND contract_type = ?'; $params[] = $contract; }

$stmt = db()->prepare(
    "SELECT title, slug, company, description, location, country, contract_type, deadline, posted_at
     FROM jobs WHERE $where ORDER BY (posted_at IS NULL), posted_at DESC, fetched_at DESC LIMIT 60"
);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$countries = db()->query("SELECT DISTINCT country FROM jobs WHERE is_active = 1 AND country IS NOT NULL ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
$contracts = db()->query("SELECT DISTINCT contract_type FROM jobs WHERE is_active = 1 AND contract_type IS NOT NULL ORDER BY contract_type")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container section">
  <span class="eyebrow" style="color:var(--coral); font-size:.8rem; letter-spacing:.12em; text-transform:uppercase; font-weight:600;">Emplois &amp; stages</span>
  <h1>Offres récentes pour jeunes diplômés</h1>
  <p style="color:var(--ink-soft); margin:12px 0 24px;">Sélection mise à jour chaque semaine, avec lien direct vers l'annonce originale. Gratuit.</p>

  <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:28px;">
    <select name="pays" class="form-control" style="max-width:220px;" onchange="this.form.submit()">
      <option value="">Tous les pays</option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= e($c) ?>" <?= $c === $country ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="contrat" class="form-control" style="max-width:220px;" onchange="this.form.submit()">
      <option value="">Tous les contrats</option>
      <?php foreach ($contracts as $c): ?>
        <option value="<?= e($c) ?>" <?= $c === $contract ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if (!$jobs): ?>
    <div class="card" style="text-align:center; padding:48px;">
      <p>Aucune offre pour ces critères. La prochaine mise à jour arrive lundi !</p>
    </div>
  <?php else: ?>
  <div class="listing">
    <?php foreach ($jobs as $j): ?>
    <div class="listing-item">
      <div>
        <h3><a href="/emploi.php?slug=<?= e($j['slug']) ?>"><?= e($j['title']) ?></a></h3>
        <p class="meta">
          <?= e($j['company'] ?? '') ?>
          <?= $j['location'] ? ' · ' . e($j['location']) : '' ?><?= $j['country'] ? ', ' . e($j['country']) : '' ?>
          <?= $j['contract_type'] ? ' · ' . e($j['contract_type']) : '' ?>
        </p>
        <p style="font-size:.92rem; color:var(--ink-soft);"><?= e(mb_substr($j['description'], 0, 150)) ?>…</p>
      </div>
      <?php if ($j['deadline']): ?><span class="deadline">Avant le <?= dateFr($j['deadline']) ?></span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
