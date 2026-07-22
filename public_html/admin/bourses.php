<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();
$fatalError = null;
$saveError = null;

try {
    // ---- Suppression ----
    if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
        $stmt = $pdo->prepare('DELETE FROM scholarships WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        redirect('/admin/bourses.php');
    }

    // ---- Bascule Premium ----
    if (($_GET['action'] ?? '') === 'toggle-premium' && csrfCheck($_GET['csrf'] ?? null)) {
        $stmt = $pdo->prepare('UPDATE scholarships SET is_premium = NOT is_premium WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        redirect('/admin/bourses.php');
    }

    // ---- Sauvegarde (création ou édition) ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
        $id                 = (int)($_POST['id'] ?? 0);
        $title              = trim($_POST['title'] ?? '');
        $organization       = trim($_POST['organization'] ?? '') ?: null;
        $description        = trim($_POST['description'] ?? '');
        $studyLevel         = trim($_POST['study_level'] ?? '') ?: null;
        $field              = trim($_POST['field'] ?? '') ?: null;
        $country            = trim($_POST['country'] ?? '') ?: null;
        $destinationCountry = trim($_POST['destination_country'] ?? '') ?: null;
        $sourceUrl          = trim($_POST['source_url'] ?? '');
        $deadline           = trim($_POST['deadline'] ?? '') ?: null;
        $isPremium          = isset($_POST['is_premium']) ? 1 : 0;
        $isActive           = isset($_POST['is_active']) ? 1 : 0;

        if ($title !== '' && $description !== '' && $sourceUrl !== '') {
            $sourceDomain = parse_url($sourceUrl, PHP_URL_HOST);
            $hash = hash('sha256', mb_strtolower($title . '|' . ($organization ?? '') . '|' . $sourceUrl));

            try {
                if ($id) {
                    $stmt = $pdo->prepare(
                        'UPDATE scholarships SET title=?, organization=?, description=?, study_level=?, field=?,
                         country=?, destination_country=?, source_url=?, source_domain=?, deadline=?, is_premium=?,
                         is_active=?, content_hash=?
                         WHERE id=?'
                    );
                    $stmt->execute([$title, $organization, $description, $studyLevel, $field, $country,
                        $destinationCountry, $sourceUrl, $sourceDomain, $deadline, $isPremium, $isActive, $hash, $id]);
                } else {
                    $slug = uniqueSlug($pdo, 'scholarships', slugify($title));
                    $stmt = $pdo->prepare(
                        'INSERT INTO scholarships (title, slug, organization, description, study_level, field,
                         country, destination_country, source_url, source_domain, deadline, is_premium, is_active, content_hash)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$title, $slug, $organization, $description, $studyLevel, $field, $country,
                        $destinationCountry, $sourceUrl, $sourceDomain, $deadline, $isPremium, $isActive, $hash]);
                    $id = (int)$pdo->lastInsertId();
                }
                redirect('/admin/bourses.php?saved=1&edit=' . $id);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $saveError = 'Une bourse avec des informations identiques (titre, organisme, lien) existe déjà.';
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
        $stmt = $pdo->prepare('SELECT * FROM scholarships WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
    }
    $showForm = $editing || isset($_GET['new']);

    $bourses = $pdo->query('SELECT * FROM scholarships ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    $fatalError = $e->getMessage();
}

adminHeader('Bourses d\'études');

if ($fatalError) {
    echo '<div class="panel" style="border-color:#8C2F1B;"><h2>❌ Erreur</h2><p>' . e($fatalError) . '</p></div>';
    adminFooter();
    exit;
}
?>

<?php if ($saved): ?><div class="alert-success">✅ Bourse enregistrée.</div><?php endif; ?>
<?php if ($fetched): ?><div class="alert-success">🤖 Recherche terminée : <?= $fetchFound ?> bourse(s) trouvée(s), <?= $fetchInserted ?> ajoutée(s).</div><?php endif; ?>
<?php if ($saveError): ?><div class="alert-success" style="background:#FDEDEA; color:#8C2F1B;">❌ <?= e($saveError) ?></div><?php endif; ?>

<p style="margin-bottom:18px; display:flex; gap:8px; flex-wrap:wrap;">
  <?php if (!$showForm): ?><a href="?new=1" class="btn btn-amber">+ Ajouter une bourse</a><?php endif; ?>
  <a href="/admin/run-fetch.php?type=scholarships&csrf=<?= e(csrfToken()) ?>" class="btn btn-primary"
     onclick="return confirm('Lancer la recherche IA sur le web ? Cela peut prendre jusqu\'à une minute.')">🔍 Lancer la recherche IA</a>
</p>

<?php if ($showForm): ?>
<div class="panel">
  <h2><?= $editing ? 'Modifier : ' . e($editing['title']) : 'Nouvelle bourse' ?></h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="grid-2">
      <div class="form-group"><label>Nom de la bourse *</label>
        <input class="form-control" name="title" required value="<?= e($editing['title'] ?? '') ?>"></div>
      <div class="form-group"><label>Organisme</label>
        <input class="form-control" name="organization" value="<?= e($editing['organization'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Description *</label>
      <textarea class="form-control" name="description" rows="6" required><?= e($editing['description'] ?? '') ?></textarea></div>
    <div class="grid-2">
      <div class="form-group"><label>Niveau d'études</label>
        <select class="form-control" name="study_level">
          <option value="">— Non précisé —</option>
          <?php foreach (['Licence', 'Master', 'Doctorat', 'Tous niveaux'] as $lvl): ?>
            <option value="<?= e($lvl) ?>" <?= ($editing['study_level'] ?? '') === $lvl ? 'selected' : '' ?>><?= e($lvl) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-group"><label>Domaine d'études</label>
        <input class="form-control" name="field" placeholder="Tous domaines, Informatique..." value="<?= e($editing['field'] ?? '') ?>"></div>
      <div class="form-group"><label>Pays d'origine éligibles</label>
        <input class="form-control" name="country" value="<?= e($editing['country'] ?? '') ?>"></div>
      <div class="form-group"><label>Pays de destination</label>
        <input class="form-control" name="destination_country" value="<?= e($editing['destination_country'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Lien officiel de candidature *</label>
      <input class="form-control" type="url" name="source_url" required placeholder="https://..." value="<?= e($editing['source_url'] ?? '') ?>"></div>
    <div class="form-group"><label>Date limite</label>
      <input class="form-control" type="date" name="deadline" value="<?= e($editing['deadline'] ?? '') ?>"></div>
    <div class="grid-2">
      <div class="form-group">
        <label><input type="checkbox" name="is_premium" value="1" <?= ($editing['is_premium'] ?? 0) ? 'checked' : '' ?>> Bourse Premium (100% financée / accès payant)</label></div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Bourse active (visible sur le site)</label></div>
    </div>
    <button class="btn btn-primary">Enregistrer</button>
    <a href="/admin/bourses.php" class="btn btn-danger" style="margin-left:8px;">Annuler</a>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Toutes les bourses (<?= count($bourses) ?>)</h2>
  <?php if (!$bourses): ?>
    <p>Aucune bourse pour le moment.</p>
  <?php else: ?>
  <table>
    <tr><th>Titre</th><th>Organisme</th><th>Niveau</th><th>Premium</th><th>Statut</th><th>Date limite</th><th></th></tr>
    <?php foreach ($bourses as $b): ?>
    <tr>
      <td><strong><?= e($b['title']) ?></strong></td>
      <td><?= e($b['organization'] ?? '—') ?></td>
      <td><?= e($b['study_level'] ?? '—') ?></td>
      <td><?= $b['is_premium'] ? '<span class="badge badge-premium">Premium</span>' : '—' ?></td>
      <td><span class="badge <?= $b['is_active'] ? 'badge-ok' : 'badge-warn' ?>"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td><?= $b['deadline'] ? e(date('d/m/Y', strtotime($b['deadline']))) : '—' ?></td>
      <td style="white-space:nowrap;">
        <a href="?edit=<?= (int)$b['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
        <a href="?action=toggle-premium&id=<?= (int)$b['id'] ?>&csrf=<?= e(csrfToken()) ?>"
           class="btn btn-sm <?= $b['is_premium'] ? 'btn-danger' : 'btn-amber' ?>"><?= $b['is_premium'] ? 'Retirer Premium' : 'Passer Premium' ?></a>
        <a href="?action=delete&id=<?= (int)$b['id'] ?>&csrf=<?= e(csrfToken()) ?>"
           onclick="return confirm('Supprimer définitivement cette bourse ?')"
           class="btn btn-sm btn-danger">Suppr.</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
