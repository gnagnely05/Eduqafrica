<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();
$fatalError = null;
$saveError = null;

try {
    // ---- Suppression ----
    if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
        $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        redirect('/admin/jobs.php');
    }

    // ---- Sauvegarde (création ou édition) ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
        $id           = (int)($_POST['id'] ?? 0);
        $title        = trim($_POST['title'] ?? '');
        $company      = trim($_POST['company'] ?? '') ?: null;
        $description  = trim($_POST['description'] ?? '');
        $location     = trim($_POST['location'] ?? '') ?: null;
        $country      = trim($_POST['country'] ?? '') ?: null;
        $contractType = trim($_POST['contract_type'] ?? '') ?: null;
        $category     = trim($_POST['category'] ?? '') ?: null;
        $sourceUrl    = trim($_POST['source_url'] ?? '');
        $deadline     = trim($_POST['deadline'] ?? '') ?: null;
        $postedAt     = trim($_POST['posted_at'] ?? '') ?: null;
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        if ($title !== '' && $description !== '' && $sourceUrl !== '') {
            $sourceDomain = parse_url($sourceUrl, PHP_URL_HOST);
            $hash = hash('sha256', mb_strtolower($title . '|' . ($company ?? '') . '|' . $sourceUrl));

            try {
                if ($id) {
                    $stmt = $pdo->prepare(
                        'UPDATE jobs SET title=?, company=?, description=?, location=?, country=?, contract_type=?,
                         category=?, source_url=?, source_domain=?, deadline=?, posted_at=?, is_active=?, content_hash=?
                         WHERE id=?'
                    );
                    $stmt->execute([$title, $company, $description, $location, $country, $contractType,
                        $category, $sourceUrl, $sourceDomain, $deadline, $postedAt, $isActive, $hash, $id]);
                } else {
                    $slug = uniqueSlug($pdo, 'jobs', slugify($title . '-' . ($company ?? '')));
                    $stmt = $pdo->prepare(
                        'INSERT INTO jobs (title, slug, company, description, location, country, contract_type,
                         category, source_url, source_domain, deadline, posted_at, is_active, content_hash)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$title, $slug, $company, $description, $location, $country, $contractType,
                        $category, $sourceUrl, $sourceDomain, $deadline, $postedAt, $isActive, $hash]);
                    $id = (int)$pdo->lastInsertId();
                }
                redirect('/admin/jobs.php?saved=1&edit=' . $id);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $saveError = 'Une offre avec des informations identiques (titre, entreprise, lien) existe déjà.';
                } else {
                    throw $e;
                }
            }
        }
    }

    $saved = isset($_GET['saved']);
    $fetched = isset($_GET['fetched']);
    $fetchFound = (int)($_GET['found'] ?? 0);
    $fetchInserted = (int)($_GET['inserted'] ?? 0);
    $editId = (int)($_GET['edit'] ?? 0);
    $editing = null;
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
    }
    $showForm = $editing || isset($_GET['new']);

    $jobs = $pdo->query('SELECT * FROM jobs ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    $fatalError = $e->getMessage();
}

adminHeader('Offres d\'emploi');

if ($fatalError) {
    echo '<div class="panel" style="border-color:#8C2F1B;"><h2>❌ Erreur</h2><p>' . e($fatalError) . '</p></div>';
    adminFooter();
    exit;
}
?>

<?php if ($saved): ?><div class="alert-success">✅ Offre enregistrée.</div><?php endif; ?>
<?php if ($fetched): ?><div class="alert-success">🤖 Recherche terminée : <?= $fetchFound ?> offre(s) trouvée(s), <?= $fetchInserted ?> ajoutée(s).</div><?php endif; ?>
<?php if ($saveError): ?><div class="alert-success" style="background:#FDEDEA; color:#8C2F1B;">❌ <?= e($saveError) ?></div><?php endif; ?>

<p style="margin-bottom:18px; display:flex; gap:8px; flex-wrap:wrap;">
  <?php if (!$showForm): ?><a href="?new=1" class="btn btn-amber">+ Ajouter une offre</a><?php endif; ?>
  <a href="/admin/run-fetch.php?type=jobs&csrf=<?= e(csrfToken()) ?>" class="btn btn-primary"
     onclick="return confirm('Lancer la recherche IA sur le web ? Cela peut prendre jusqu\'à une minute.')">🔍 Lancer la recherche IA</a>
</p>

<?php if ($showForm): ?>
<div class="panel">
  <h2><?= $editing ? 'Modifier : ' . e($editing['title']) : 'Nouvelle offre' ?></h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="grid-2">
      <div class="form-group"><label>Titre du poste *</label>
        <input class="form-control" name="title" required value="<?= e($editing['title'] ?? '') ?>"></div>
      <div class="form-group"><label>Entreprise</label>
        <input class="form-control" name="company" value="<?= e($editing['company'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Description *</label>
      <textarea class="form-control" name="description" rows="6" required><?= e($editing['description'] ?? '') ?></textarea></div>
    <div class="grid-2">
      <div class="form-group"><label>Ville</label>
        <input class="form-control" name="location" value="<?= e($editing['location'] ?? '') ?>"></div>
      <div class="form-group"><label>Pays</label>
        <input class="form-control" name="country" value="<?= e($editing['country'] ?? '') ?>"></div>
      <div class="form-group"><label>Type de contrat</label>
        <select class="form-control" name="contract_type">
          <option value="">— Non précisé —</option>
          <?php foreach (['CDI', 'CDD', 'Stage', 'Freelance', 'Alternance'] as $ct): ?>
            <option value="<?= e($ct) ?>" <?= ($editing['contract_type'] ?? '') === $ct ? 'selected' : '' ?>><?= e($ct) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-group"><label>Domaine</label>
        <input class="form-control" name="category" placeholder="Informatique, Finance..." value="<?= e($editing['category'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Lien de l'offre (candidature) *</label>
      <input class="form-control" type="url" name="source_url" required placeholder="https://..." value="<?= e($editing['source_url'] ?? '') ?>"></div>
    <div class="grid-2">
      <div class="form-group"><label>Date limite</label>
        <input class="form-control" type="date" name="deadline" value="<?= e($editing['deadline'] ?? '') ?>"></div>
      <div class="form-group"><label>Date de publication</label>
        <input class="form-control" type="date" name="posted_at" value="<?= e($editing['posted_at'] ?? date('Y-m-d')) ?>"></div>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Offre active (visible sur le site)</label>
    </div>
    <button class="btn btn-primary">Enregistrer</button>
    <a href="/admin/jobs.php" class="btn btn-danger" style="margin-left:8px;">Annuler</a>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Toutes les offres (<?= count($jobs) ?>)</h2>
  <?php if (!$jobs): ?>
    <p>Aucune offre pour le moment.</p>
  <?php else: ?>
  <table>
    <tr><th>Titre</th><th>Entreprise</th><th>Ville / Pays</th><th>Statut</th><th>Date limite</th><th></th></tr>
    <?php foreach ($jobs as $j): ?>
    <tr>
      <td><strong><?= e($j['title']) ?></strong></td>
      <td><?= e($j['company'] ?? '—') ?></td>
      <td><?= e($j['location'] ?? '') ?><?= $j['country'] ? ', ' . e($j['country']) : '' ?></td>
      <td><span class="badge <?= $j['is_active'] ? 'badge-ok' : 'badge-warn' ?>"><?= $j['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td><?= $j['deadline'] ? e(date('d/m/Y', strtotime($j['deadline']))) : '—' ?></td>
      <td style="white-space:nowrap;">
        <a href="?edit=<?= (int)$j['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
        <a href="?action=delete&id=<?= (int)$j['id'] ?>&csrf=<?= e(csrfToken()) ?>"
           onclick="return confirm('Supprimer définitivement cette offre ?')"
           class="btn btn-sm btn-danger">Suppr.</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
