<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = setting('hero_title', "L'orientation et l'emploi gratuits");
require_once __DIR__ . '/../includes/header.php';

$bourses = db()->query(
    "SELECT title, slug, organization, deadline FROM scholarships
     WHERE is_active = 1 AND is_premium = 0 ORDER BY fetched_at DESC LIMIT 3"
)->fetchAll();

$articles = db()->query(
    "SELECT title, slug, excerpt, published_at FROM articles
     WHERE status = 'published' ORDER BY published_at DESC LIMIT 3"
)->fetchAll();

$nbBourses = (int)db()->query("SELECT COUNT(*) FROM scholarships WHERE is_active = 1")->fetchColumn();
$nbJobs = (int)db()->query("SELECT COUNT(*) FROM jobs WHERE is_active = 1")->fetchColumn();
$nbUsers = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$nbCV = (int)db()->query("SELECT COUNT(*) FROM cv_documents")->fetchColumn();

$ebImage = setting('eb_image_url', '');
?>

<!-- ================= HERO ================= -->
<section class="hero">
  <div class="container hero-inner">
    <p class="hero-label"><?= e(setting('hero_label', 'Ton avenir, simplifié.')) ?></p>
    <h1><?= e(setting('hero_title', "L'orientation et l'emploi gratuits.")) ?></h1>
    <p class="lead"><?= e(setting('hero_lead', '')) ?></p>
    <div class="hero-cta">
      <a href="/chat.php" class="btn btn-primary"><?= e(setting('hero_btn1_text', "Lancer l'orientation IA")) ?></a>
      <a href="#solutions" class="btn btn-outline"><?= e(setting('hero_btn2_text', 'Découvrir nos outils')) ?></a>
    </div>
  </div>
</section>

<!-- ================= NOS OUTILS ================= -->
<section class="section section-grey" id="solutions">
  <div class="container">
    <div class="section-head">
      <h2><?= e(setting('solutions_title', 'Tes outils pour réussir')) ?></h2>
      <p><?= e(setting('solutions_subtitle', '')) ?></p>
    </div>
    <div class="solutions-grid">

      <div class="solution-card">
        <div class="icon">⚡</div>
        <h3><?= e(setting('sol1_title', 'Orientation sur-mesure')) ?></h3>
        <p><?= e(setting('sol1_text', '')) ?></p>
        <a href="/chat.php" class="btn btn-outline btn-block">Plus</a>
      </div>

      <div class="solution-card">
        <div class="icon">📝</div>
        <h3><?= e(setting('sol2_title', 'Générateur de CV Pro')) ?></h3>
        <p><?= e(setting('sol2_text', '')) ?></p>
        <a href="/cv-assistant.php" class="btn btn-primary btn-block">Créer mon CV</a>
      </div>

      <div class="solution-card">
        <div class="icon">🎓</div>
        <h3><?= e(setting('sol3_title', "Bourses d'études vérifiées")) ?></h3>
        <p><?= e(setting('sol3_text', '')) ?></p>
        <a href="/bourses.php" class="btn btn-outline btn-block">Voir les bourses</a>
      </div>

    </div>
  </div>
</section>

<!-- ================= EMPLOIS & BOURSES ================= -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2><?= setting('eb_title', "Emplois &amp; Bourses : tes portes ouvertes") ?></h2>
    </div>
    <div class="eb-grid">

      <div class="eb-photo-card">
        <div class="photo">
          <?php if ($ebImage): ?>
            <img src="<?= e($ebImage) ?>" alt="<?= e(setting('eb_photo_title', '')) ?>" loading="lazy">
          <?php else: ?>
            💼
          <?php endif; ?>
        </div>
        <h3><?= e(setting('eb_photo_title', "Offres d'emploi locales")) ?></h3>
        <p><?= e(setting('eb_photo_text', '')) ?></p>
        <a href="/emplois.php" class="btn btn-primary">Voir les offres</a>
      </div>

      <div class="eb-text-card">
        <h3><?= e(setting('eb_text_title', "Bourses d'études récentes")) ?></h3>
        <p><?= e(setting('eb_text_text', '')) ?></p>
        <?php if ($bourses): ?>
        <div class="listing" style="margin-top:18px;">
          <?php foreach ($bourses as $b): ?>
          <div class="listing-item" style="padding:14px 16px;">
            <div>
              <h3 style="font-size:.98rem;"><a href="/bourse.php?slug=<?= e($b['slug']) ?>"><?= e($b['title']) ?></a></h3>
              <p><?= e($b['organization'] ?? '') ?></p>
            </div>
            <?php if ($b['deadline']): ?><span class="deadline"><?= dateFr($b['deadline']) ?></span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="/bourses.php" class="btn btn-outline" style="margin-top:16px;">Voir toutes les bourses</a>
      </div>

    </div>
  </div>
</section>

<!-- ================= STATS ================= -->
<section class="section section-grey stats-block">
  <div class="container">
    <p class="section-eyebrow"><?= e(setting('stats_eyebrow', 'Notre impact en chiffres')) ?></p>
    <h2><?= e(setting('stats_title', "Des milliers d'avenirs éclairés")) ?></h2>
    <div class="stats">
      <div class="stat">
        <div class="stat-number"><?= number_format(max($nbUsers, 0), 0, ',', ' ') ?>+</div>
        <div class="stat-label"><?= e(setting('stat1_label', 'Étudiants orientés')) ?></div>
      </div>
      <div class="stat">
        <div class="stat-number"><?= number_format(max($nbCV, 0), 0, ',', ' ') ?>+</div>
        <div class="stat-label"><?= e(setting('stat2_label', 'CV générés')) ?></div>
      </div>
      <div class="stat">
        <div class="stat-number"><?= $nbBourses ?>+</div>
        <div class="stat-label"><?= e(setting('stat3_label', 'Bourses listées')) ?></div>
      </div>
    </div>
  </div>
</section>

<?php if ($articles): ?>
<!-- ================= BLOG ================= -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Guides &amp; conseils</h2>
    </div>
    <div class="articles-grid">
      <?php foreach ($articles as $a): ?>
      <div class="article-card">
        <p class="meta"><?= dateFr($a['published_at']) ?></p>
        <h3><a href="/article.php?slug=<?= e($a['slug']) ?>"><?= e($a['title']) ?></a></h3>
        <p><?= e(mb_substr($a['excerpt'] ?? $a['title'], 0, 110)) ?>…</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= CTA FINAL ================= -->
<section class="section section-dark cta-final">
  <div class="container">
    <h2><?= e(setting('cta_title', 'Prêt à construire ton avenir ?')) ?></h2>
    <p><?= e(setting('cta_text', '')) ?></p>
    <a href="/chat.php" class="btn btn-accent"><?= e(setting('cta_btn_text', "Lancer l'orientation IA")) ?></a>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
