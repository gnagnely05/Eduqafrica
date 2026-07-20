<?php
/** Layout du back-office : adminHeader($title) ... adminFooter() */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

function adminHeader(string $title): void
{
    $current = basename($_SERVER['SCRIPT_NAME']);
    $nav = [
        'index.php'    => '📊 Tableau de bord',
        'content.php'  => '✏️ Contenu accueil',
        'media.php'    => '🖼️ Médiathèque',
        'design.php'   => '🎨 Couleurs & polices',
        'articles.php' => '📝 Articles',
        'settings.php' => '⚙️ Tarifs & codes',
        'messages.php' => '✉️ Messages',
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — <?= e($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root { --ink:#1B2452; --paper:#F5F5F1; --amber:#FFB703; --coral:#E76F51; --line:#E0DFD6; }
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Inter',sans-serif; background:var(--paper); color:var(--ink); font-size:15px; line-height:1.6; }
h1,h2,h3 { font-family:'Space Grotesk',sans-serif; }
a { color:var(--ink); }
.admin-shell { display:flex; min-height:100vh; }
.admin-side { width:230px; background:var(--ink); color:#fff; padding:24px 0; flex-shrink:0; }
.admin-side .logo { font-family:'Space Grotesk',sans-serif; font-weight:700; padding:0 22px 22px; font-size:1.1rem; }
.admin-side a { display:block; color:#C6CAE0; text-decoration:none; padding:11px 22px; font-size:.93rem; }
.admin-side a:hover { background:rgba(255,255,255,.07); color:#fff; }
.admin-side a.active { background:rgba(255,183,3,.14); color:var(--amber); border-left:3px solid var(--amber); }
.admin-side .back { margin-top:26px; border-top:1px solid rgba(255,255,255,.12); padding-top:14px; }
.admin-main { flex:1; padding:32px 36px; max-width:1100px; }
.admin-main h1 { margin-bottom:22px; font-size:1.6rem; }
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.kpi { background:#fff; border:1px solid var(--line); border-radius:12px; padding:18px 20px; }
.kpi .val { font-family:'Space Grotesk',sans-serif; font-size:1.7rem; font-weight:700; }
.kpi .lbl { font-size:.82rem; color:#6A7194; }
.kpi .sub { font-size:.78rem; color:#2A9D8F; font-weight:600; }
.panel { background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px 24px; margin-bottom:24px; overflow-x:auto; }
.panel h2 { font-size:1.1rem; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; font-size:.9rem; min-width:520px; }
th { text-align:left; padding:8px 10px; border-bottom:2px solid var(--line); font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#6A7194; }
td { padding:9px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
tr:hover td { background:#FAFAF6; }
.btn { display:inline-block; padding:9px 18px; border-radius:999px; text-decoration:none; font-weight:600; font-size:.88rem; border:none; cursor:pointer; font-family:'Inter',sans-serif; }
.btn-primary { background:var(--ink); color:#fff; }
.btn-amber { background:var(--amber); color:var(--ink); }
.btn-danger { background:#fff; color:var(--coral); border:1.5px solid var(--coral); }
.btn-sm { padding:5px 13px; font-size:.8rem; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-weight:600; font-size:.87rem; margin-bottom:5px; }
.form-control { width:100%; padding:10px 13px; border:1.5px solid var(--line); border-radius:9px; font-family:'Inter',sans-serif; font-size:.95rem; background:#fff; }
.form-control:focus { outline:2px solid var(--amber); border-color:transparent; }
textarea.form-control { font-family:monospace; font-size:.86rem; }
.badge { display:inline-block; font-size:.72rem; font-weight:700; padding:2px 10px; border-radius:99px; background:#EEE; }
.badge-ok { background:#DFF3E3; color:#1F5E2C; }
.badge-warn { background:#FFF3D1; color:#7A5C00; }
.badge-err { background:#FDEDEA; color:#8C2F1B; }
.alert-success { background:#EDF7EE; color:#1F5E2C; padding:12px 16px; border-radius:9px; margin-bottom:18px; }
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media (max-width:900px) { .admin-shell{flex-direction:column} .admin-side{width:100%} .kpi-grid{grid-template-columns:repeat(2,1fr)} .grid-2{grid-template-columns:1fr} .admin-main{padding:20px} }
@media (max-width:600px) {
  .admin-side { display:flex; overflow-x:auto; padding:12px 0; white-space:nowrap; }
  .admin-side .logo { padding:0 14px; flex-shrink:0; }
  .admin-side a { padding:10px 14px; flex-shrink:0; border-left:none; }
  .admin-side a.active { border-left:none; border-bottom:3px solid var(--amber); }
  .admin-side .back { margin-top:0; border-top:none; padding-top:0; display:flex; flex-shrink:0; }
  .kpi-grid { grid-template-columns:1fr 1fr; }
  .admin-main { padding:14px; }
}
</style>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-side">
    <div class="logo">🧭 Admin</div>
    <?php foreach ($nav as $file => $label): ?>
      <a href="/admin/<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <div class="back">
      <a href="/">← Voir le site</a>
      <a href="/logout.php">Se déconnecter</a>
    </div>
  </aside>
  <main class="admin-main">
    <h1><?= e($title) ?></h1>
<?php
}

function adminFooter(): void
{
    echo '</main></div></body></html>';
}
