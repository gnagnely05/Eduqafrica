<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();

// Polices Google Fonts autorisées (sûres, chargées de manière fiable)
$fontChoices = [
    'Sora', 'Poppins', 'Space Grotesk', 'Manrope', 'Outfit', 'Plus Jakarta Sans',
];
$bodyFontChoices = [
    'IBM Plex Sans', 'Inter', 'DM Sans', 'Work Sans', 'Nunito Sans', 'Source Sans 3',
];

$colorFields = [
    'color_dark_teal'  => 'Couleur principale (texte, boutons, footer)',
    'color_coral'      => 'Couleur d\'accent (icônes, hover, CTA sur fond sombre)',
    'color_light_sage' => 'Fond dégradé du hero (haut)',
    'color_light_grey' => 'Fond des sections claires',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $stmt = $pdo->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
    );
    foreach (array_keys($colorFields) as $key) {
        if (!empty($_POST[$key]) && preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST[$key])) {
            $stmt->execute([$key, $_POST[$key]]);
        }
    }
    foreach (['font_display' => $fontChoices, 'font_nav' => $bodyFontChoices, 'font_body' => $bodyFontChoices] as $key => $choices) {
        if (!empty($_POST[$key]) && in_array($_POST[$key], $choices, true)) {
            $stmt->execute([$key, $_POST[$key]]);
        }
    }
    redirect('/admin/design.php?saved=1');
}

$saved = isset($_GET['saved']);
$current = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$get = fn($k, $d) => $current[$k] ?? $d;

adminHeader('Couleurs & polices');
?>

<?php if ($saved): ?><div class="alert-success">✅ Design mis à jour — visible immédiatement sur tout le site.</div><?php endif; ?>

<p style="margin-bottom:20px;"><a href="/" target="_blank" class="btn btn-sm">Voir le site ↗</a>
<a href="/admin/content.php" class="btn btn-sm" style="margin-left:8px;">← Retour au contenu</a></p>

<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

<div class="panel">
  <h2>🎨 Couleurs</h2>
  <div class="grid-2">
    <?php foreach ($colorFields as $key => $label): ?>
    <div class="form-group">
      <label><?= e($label) ?></label>
      <div style="display:flex; gap:10px; align-items:center;">
        <input type="color" name="<?= e($key) ?>" value="<?= e($get($key, '#0B2524')) ?>"
               style="width:52px; height:40px; border:1.5px solid var(--line); border-radius:8px; cursor:pointer; padding:2px;">
        <input class="form-control" type="text" value="<?= e($get($key, '')) ?>"
               style="font-family:monospace;" readonly
               oninput="this.previousElementSibling.value=this.value">
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p style="font-size:.82rem; color:#6A7194; margin-top:8px;">Clique sur le carré pour choisir une couleur. Elle s'applique partout : boutons, titres, fonds de section.</p>
</div>

<div class="panel">
  <h2>🔤 Typographies</h2>
  <div class="grid-2">
    <div class="form-group">
      <label>Police des titres</label>
      <select class="form-control" name="font_display">
        <?php foreach ($fontChoices as $f): ?>
          <option value="<?= e($f) ?>" <?= $get('font_display', 'Sora') === $f ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Police navigation &amp; boutons</label>
      <select class="form-control" name="font_nav">
        <?php foreach ($bodyFontChoices as $f): ?>
          <option value="<?= e($f) ?>" <?= $get('font_nav', 'DM Sans') === $f ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Police du texte courant</label>
      <select class="form-control" name="font_body">
        <?php foreach ($bodyFontChoices as $f): ?>
          <option value="<?= e($f) ?>" <?= $get('font_body', 'IBM Plex Sans') === $f ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <p style="font-size:.82rem; color:#6A7194; margin-top:8px;">Liste volontairement limitée à des polices Google Fonts fiables et lisibles pour garder un rendu professionnel.</p>
</div>

<button class="btn btn-primary">💾 Enregistrer le design</button>
</form>

<?php adminFooter(); ?>
