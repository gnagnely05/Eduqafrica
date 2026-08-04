<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDesc  = $pageDesc ?? "Orientation scolaire et professionnelle pour élèves et étudiants francophones : conseils IA, bourses d'études, offres d'emploi, générateur de CV.";
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light only">
<meta name="google-adsense-account" content="ca-pub-5953146496728381">
<title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="canonical" href="<?= e(SITE_URL . strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<?php
$fontDisplay = setting('font_display', 'Sora');
$fontNav     = setting('font_nav', 'DM Sans');
$fontBody    = setting('font_body', 'IBM Plex Sans');
$fontsParam  = implode('&family=', array_map(
    fn($f) => str_replace(' ', '+', $f) . ':wght@400;500;600;700',
    array_unique([$fontDisplay, $fontNav, $fontBody])
));
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=<?= e($fontsParam) ?>&display=swap" rel="stylesheet">
<?php $styleVersion = @filemtime(__DIR__ . '/../public_html/assets/css/style.css') ?: time(); ?>
<link rel="stylesheet" href="/assets/css/style.css?v=<?= $styleVersion ?>">
<style>
:root {
  --dark-teal: <?= e(setting('color_dark_teal', '#0B2524')) ?>;
  --coral: <?= e(setting('color_coral', '#E8935F')) ?>;
  --light-sage: <?= e(setting('color_light_sage', '#C9DEDB')) ?>;
  --light-grey: <?= e(setting('color_light_grey', '#E7E9E7')) ?>;
  --font-display: '<?= e($fontDisplay) ?>', sans-serif;
  --font-nav: '<?= e($fontNav) ?>', sans-serif;
  --font-body: '<?= e($fontBody) ?>', sans-serif;
}
</style>
<?= setting('code_head', '') /* Codes AdSense / Analytics / vérification — gérés depuis l'admin */ ?>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="brand" aria-label="Accueil <?= e(SITE_NAME) ?>">
      <svg class="brand-icon" viewBox="0 0 32 32" aria-hidden="true">
        <circle cx="16" cy="16" r="15" fill="#0B2524"/>
        <path d="M20 11 L17 17 L11 20 L14 14 Z" fill="#E8935F"/>
      </svg>
      <span><?= e(SITE_NAME) ?></span>
    </a>
    <?php
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
    $navActive = fn(string $page) => $currentPage === $page ? 'active' : '';
    ?>
    <nav class="main-nav" id="mainNav">
      <a href="/" class="<?= $navActive('index.php') ?>">Accueil</a>
      <a href="/chat.php" class="<?= $navActive('chat.php') ?>">Orientation IA</a>
      <a href="/cv-assistant.php" class="<?= $navActive('cv-assistant.php') . ' ' . $navActive('cv-generator.php') . ' ' . $navActive('cv-preview.php') ?>">Générateur de CV</a>
      <a href="/bourses.php" class="<?= $navActive('bourses.php') . ' ' . $navActive('bourse.php') ?>">Bourses d'Études</a>
      <a href="/emplois.php" class="<?= $navActive('emplois.php') . ' ' . $navActive('emploi.php') ?>">Offres d'emploi</a>
      <a href="/blog.php" class="<?= $navActive('blog.php') . ' ' . $navActive('article.php') ?>">Guides et conseils</a>
    </nav>
    <div class="header-actions">
      <?php if ($user): ?>
        <?php if (isAdmin()): ?><a href="/admin/" class="btn btn-primary btn-sm">Admin</a><?php endif; ?>
        <a href="/compte.php" class="btn btn-outline btn-sm"><?= e($user['name']) ?></a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-outline btn-sm">Connexion</a>
        <a href="/register.php" class="btn btn-primary btn-sm">S'inscrire</a>
      <?php endif; ?>
      <button class="nav-toggle" aria-label="Menu" onclick="document.getElementById('mainNav').classList.toggle('open')">☰</button>
    </div>
  </div>
</header>
<main>
