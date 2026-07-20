<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();
$saved = false;

// ---- Suppression ----
if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    redirect('/admin/articles.php');
}

// ---- Sauvegarde (création ou édition) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $catId    = (int)($_POST['category_id'] ?? 0) ?: null;
    $excerpt  = trim($_POST['excerpt'] ?? '');
    $content  = $_POST['content'] ?? '';
    $status   = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

    if ($title !== '' && $content !== '') {
        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE articles SET title=?, category_id=?, excerpt=?, content=?, status=?,
                 published_at = IF(? = 'published' AND published_at IS NULL, NOW(), published_at)
                 WHERE id=?"
            );
            $stmt->execute([$title, $catId, $excerpt, $content, $status, $status, $id]);
        } else {
            $slug = uniqueSlug($pdo, 'articles', slugify($title));
            $stmt = $pdo->prepare(
                "INSERT INTO articles (category_id, title, slug, excerpt, content, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, IF(? = 'published', NOW(), NULL))"
            );
            $stmt->execute([$catId, $title, $slug, $excerpt, $content, $status, $status]);
            $id = (int)$pdo->lastInsertId();
        }
        redirect('/admin/articles.php?saved=1&edit=' . $id);
    }
}

$saved = isset($_GET['saved']);
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}
$showForm = $editing || isset($_GET['new']);

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$articles = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.status, a.views, a.published_at, c.name AS cat
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY a.id DESC"
)->fetchAll();

adminHeader('Articles du blog');
?>

<?php if ($saved): ?><div class="alert-success">✅ Article enregistré.</div><?php endif; ?>

<?php if ($showForm): ?>
<div class="panel">
  <h2><?= $editing ? 'Modifier : ' . e($editing['title']) : 'Nouvel article' ?></h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="form-group"><label>Titre *</label>
      <input class="form-control" name="title" required value="<?= e($editing['title'] ?? '') ?>"></div>
    <div class="grid-2">
      <div class="form-group"><label>Catégorie</label>
        <select class="form-control" name="category_id">
          <option value="">— Aucune —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($editing['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-group"><label>Statut</label>
        <select class="form-control" name="status">
          <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
          <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
        </select></div>
    </div>
    <div class="form-group"><label>Extrait (méta description, ~150 caractères)</label>
      <input class="form-control" name="excerpt" maxlength="500" value="<?= e($editing['excerpt'] ?? '') ?>"></div>
    <div class="form-group">
      <label>Contenu</label>
      <div id="articleEditor" style="background:#fff; min-height:340px; border-radius:0 0 9px 9px;"><?= $editing['content'] ?? '' ?></div>
      <textarea name="content" id="contentField" required style="display:none;"><?= e($editing['content'] ?? '') ?></textarea>
    </div>
    <button class="btn btn-primary">Enregistrer</button>
    <a href="/admin/articles.php" class="btn btn-danger" style="margin-left:8px;">Annuler</a>
    <?php if ($editing && $editing['status'] === 'published'): ?>
      <a href="/article.php?slug=<?= e($editing['slug']) ?>" target="_blank" class="btn btn-sm" style="margin-left:8px;">Voir sur le site ↗</a>
    <?php endif; ?>
  </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const quill = new Quill('#articleEditor', {
  theme: 'snow',
  placeholder: 'Rédige ton article ici…',
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ size: ['small', false, 'large', 'huge'] }],
      [{ color: [] }, { background: [] }],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ align: [] }],
      ['blockquote', 'link', 'image'],
      ['clean'],
    ],
  },
});

quill.getModule('toolbar').addHandler('image', function () {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.onchange = async () => {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('image', file);
    try {
      const res = await fetch('/admin/upload-image.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        const range = quill.getSelection(true) || { index: quill.getLength() };
        quill.insertEmbed(range.index, 'image', data.url, 'user');
        quill.setSelection(range.index + 1);
      } else {
        alert(data.error || "Erreur lors de l'envoi de l'image.");
      }
    } catch (e) {
      alert('Connexion impossible, réessaie.');
    }
  };
});

document.querySelector('#contentField').closest('form').addEventListener('submit', () => {
  document.querySelector('#contentField').value = quill.root.innerHTML;
});
</script>
<?php else: ?>
<p style="margin-bottom:18px;"><a href="?new=1" class="btn btn-amber">+ Nouvel article</a></p>
<?php endif; ?>

<div class="panel">
  <h2>Tous les articles (<?= count($articles) ?>)</h2>
  <?php if (!$articles): ?>
    <p>Aucun article. Objectif AdSense : 15-20 articles originaux de 800+ mots. 📝</p>
  <?php else: ?>
  <table>
    <tr><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Vues</th><th>Publié le</th><th></th></tr>
    <?php foreach ($articles as $a): ?>
    <tr>
      <td><strong><?= e($a['title']) ?></strong></td>
      <td><?= e($a['cat'] ?? '—') ?></td>
      <td><span class="badge <?= $a['status'] === 'published' ? 'badge-ok' : 'badge-warn' ?>"><?= $a['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span></td>
      <td><?= (int)$a['views'] ?></td>
      <td><?= $a['published_at'] ? e(date('d/m/Y', strtotime($a['published_at']))) : '—' ?></td>
      <td style="white-space:nowrap;">
        <a href="?edit=<?= (int)$a['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
        <a href="?action=delete&id=<?= (int)$a['id'] ?>&csrf=<?= e(csrfToken()) ?>"
           onclick="return confirm('Supprimer définitivement cet article ?')"
           class="btn btn-sm btn-danger">Suppr.</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
