<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare(
    "SELECT a.*, c.name AS cat_name FROM articles a
     LEFT JOIN categories c ON c.id = a.category_id
     WHERE a.slug = ? AND a.status = 'published'"
);
$stmt->execute([$slug]);
$a = $stmt->fetch();
if (!$a) { http_response_code(404); $pageTitle = 'Article introuvable'; require_once __DIR__ . '/../includes/header.php'; echo '<div class="container section"><p>Article introuvable. <a href="/blog.php">Retour au blog</a>.</p></div>'; require_once __DIR__ . '/../includes/footer.php'; exit; }

// Compteur de vues (simple)
db()->prepare('UPDATE articles SET views = views + 1 WHERE id = ?')->execute([$a['id']]);

$pageTitle = $a['title'];
$pageDesc = $a['excerpt'] ?: mb_substr(strip_tags($a['content']), 0, 155);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
  <article class="article-body">
    <p><a href="/blog.php">← Blog</a></p>
    <?php if ($a['cat_name']): ?><span class="badge"><?= e($a['cat_name']) ?></span><?php endif; ?>
    <h1 style="margin:10px 0 6px;"><?= e($a['title']) ?></h1>
    <p class="meta" style="margin-bottom:28px;">Publié le <?= dateFr($a['published_at']) ?></p>

    <?php /* Emplacement publicité AdSense (in-article) — à activer après approbation */ ?>

    <div><?= $a['content'] /* HTML rédigé via l'admin — auteur de confiance */ ?></div>

    <div class="card" style="margin-top:36px; background:var(--paper-dim);">
      <p><strong>Besoin d'un conseil personnalisé ?</strong> Pose ta question à notre conseiller d'orientation IA, c'est gratuit.</p>
      <a href="/chat.php" class="btn btn-primary" style="margin-top:10px;">Poser ma question</a>
    </div>
  </article>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
