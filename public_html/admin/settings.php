<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();
$saved = false;

$priceKeys = [
    'price_cv_download'                 => 'Téléchargement CV (PDF)',
    'price_orientation_bourses_monthly' => 'Orientation + Bourses — abonnement mensuel',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $stmt = $pdo->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
    );
    // Tarifs (entiers uniquement)
    foreach ($priceKeys as $key => $label) {
        if (isset($_POST[$key])) {
            $val = max(0, (int)$_POST[$key]);
            $stmt->execute([$key, (string)$val]);
        }
    }
    // Codes (HTML brut — admin de confiance)
    foreach (['code_head', 'code_body_end'] as $key) {
        if (isset($_POST[$key])) {
            $stmt->execute([$key, trim($_POST[$key])]);
        }
    }
    redirect('/admin/settings.php?saved=1');
}

$saved = isset($_GET['saved']);
$current = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);

adminHeader('Réglages');
?>

<?php if ($saved): ?><div class="alert-success">✅ Réglages enregistrés — effet immédiat sur le site.</div><?php endif; ?>

<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

<div class="panel">
  <h2>💰 Tarifs (en francs CFA)</h2>
  <p style="font-size:.88rem; color:#6A7194; margin-bottom:16px;">Modifie les montants et enregistre : les nouveaux prix s'appliquent immédiatement sur tout le site et les paiements.</p>
  <div class="grid-2">
    <?php foreach ($priceKeys as $key => $label): ?>
    <div class="form-group">
      <label><?= e($label) ?></label>
      <input class="form-control" type="number" min="0" step="50" name="<?= e($key) ?>"
             value="<?= e($current[$key] ?? '') ?>">
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel">
  <h2>📢 Codes AdSense, Analytics &amp; vérifications</h2>
  <div class="form-group">
    <label>Code dans &lt;head&gt; — script AdSense, balise de vérification Google, Google Analytics…</label>
    <textarea class="form-control" name="code_head" rows="6"
      placeholder='<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXX" crossorigin="anonymous"></script>'><?= e($current['code_head'] ?? '') ?></textarea>
    <p style="font-size:.82rem; color:#6A7194; margin-top:6px;">Colle ici le code exactement comme fourni par Google. Il sera inséré sur toutes les pages du site.</p>
  </div>
  <div class="form-group">
    <label>Code avant &lt;/body&gt; (pixels, chats externes, etc.)</label>
    <textarea class="form-control" name="code_body_end" rows="4"><?= e($current['code_body_end'] ?? '') ?></textarea>
  </div>
</div>

<button class="btn btn-primary">💾 Enregistrer les réglages</button>
</form>

<?php adminFooter(); ?>
