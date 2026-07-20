<?php
$pageTitle = "Blog orientation & réussite";
$pageDesc = "Conseils d'orientation, guides des filières, astuces bourses et emploi pour les élèves et étudiants francophones.";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$catSlug = $_GET['categorie'] ?? '';
$where = "a.status = 'published'";
$params = [];
if ($catSlug !== '') {
    $where .= ' AND c.slug = ?';
    $params[] = $catSlug;
}

$stmt = db()->prepare(
    "SELECT a.title, a.slug, a.excerpt, a.featured_image, a.published_at, c.name AS cat_name, c.slug AS cat_slug
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     WHERE $where ORDER BY a.published_at DESC LIMIT 30"
);
$stmt->execute($params);
$articles = $stmt->fetchAll();

$cats = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
?>
<div class="container section">
  <span class="eyebrow" style="color:var(--coral); font-size:.8rem; letter-spacing:.12em; text-transform:uppercase; font-weight:600;">Blog</span>
  <h1>Guides &amp; conseils</h1>

  <div style="display:flex; gap:10px; margin:20px 0 28px; flex-wrap:wrap;">
    <a href="/blog.php" class="btn <?= $catSlug === '' ? 'btn-primary' : 'btn-ghost' ?>">Tous</a>
    <?php foreach ($cats as $c): ?>
      <a href="?categorie=<?= e($c['slug']) ?>" class="btn <?= $catSlug === $c['slug'] ? 'btn-primary' : 'btn-ghost' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$articles): ?>
    <div class="card" style="text-align:center; padding:48px;"><p>Les premiers articles arrivent très bientôt.</p></div>
  <?php else: ?>
  <div class="articles-grid">
    <?php foreach ($articles as $a): ?>
    <a href="/article.php?slug=<?= e($a['slug']) ?>" class="article-card" style="display:block;">
      <?php if ($a['featured_image']): ?>
        <img src="<?= e($a['featured_image']) ?>" alt="" class="article-card-img">
      <?php endif; ?>
      <?php if ($a['cat_name']): ?><span class="badge"><?= e($a['cat_name']) ?></span><?php endif; ?>
      <h3 style="margin-top:8px;"><?= e($a['title']) ?></h3>
      <p class="meta"><?= dateFr($a['published_at']) ?></p>
      <p><?= e($a['excerpt'] ?? '') ?></p>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
