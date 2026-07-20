<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();

// Champs texte éditables (groupés par section pour l'affichage)
$textFields = [
    'Hero (bandeau principal)' => [
        'hero_label' => ['Petit texte au-dessus du titre', 'text'],
        'hero_title' => ['Titre principal', 'text'],
        'hero_lead' => ['Paragraphe descriptif', 'textarea'],
        'hero_btn1_text' => ['Texte du 1er bouton', 'text'],
        'hero_btn2_text' => ['Texte du 2e bouton', 'text'],
    ],
    'Section "Nos outils"' => [
        'solutions_title' => ['Titre de section', 'text'],
        'solutions_subtitle' => ['Sous-titre', 'textarea'],
        'sol1_image' => ['Carte 1 — image', 'image'],
        'sol1_title' => ['Carte 1 — titre', 'text'],
        'sol1_text' => ['Carte 1 — texte', 'textarea'],
        'sol2_image' => ['Carte 2 — image', 'image'],
        'sol2_title' => ['Carte 2 — titre', 'text'],
        'sol2_text' => ['Carte 2 — texte', 'textarea'],
        'sol3_image' => ['Carte 3 — image', 'image'],
        'sol3_title' => ['Carte 3 — titre', 'text'],
        'sol3_text' => ['Carte 3 — texte', 'textarea'],
    ],
    'Section "Emplois & Bourses"' => [
        'eb_title' => ['Titre de section', 'text'],
        'eb_image_url' => ['Image (bloc emploi)', 'image'],
        'eb_photo_title' => ['Bloc emploi — titre', 'text'],
        'eb_photo_text' => ['Bloc emploi — texte', 'textarea'],
        'eb_text_title' => ['Bloc bourses — titre', 'text'],
        'eb_text_text' => ['Bloc bourses — texte', 'textarea'],
    ],
    'Section statistiques' => [
        'stats_eyebrow' => ['Petit texte au-dessus', 'text'],
        'stats_title' => ['Titre de section', 'text'],
        'stat1_label' => ['Libellé chiffre 1', 'text'],
        'stat2_label' => ['Libellé chiffre 2', 'text'],
        'stat3_label' => ['Libellé chiffre 3', 'text'],
    ],
    'Bandeau final (CTA)' => [
        'cta_title' => ['Titre', 'text'],
        'cta_text' => ['Texte', 'textarea'],
        'cta_btn_text' => ['Texte du bouton', 'text'],
    ],
];

$uploadErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $stmt = $pdo->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
    );
    foreach ($textFields as $group) {
        foreach ($group as $key => [$label, $type]) {
            if ($type === 'image') {
                // Un fichier envoyé remplace la valeur sélectionnée dans la médiathèque.
                if (!empty($_FILES[$key . '_file']) && $_FILES[$key . '_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $res = saveUploadedImage($_FILES[$key . '_file']);
                    if ($res['ok']) {
                        $stmt->execute([$key, $res['url']]);
                        continue;
                    }
                    $uploadErrors[] = e($label) . ' : ' . e($res['error']);
                }
                if (isset($_POST[$key])) {
                    $stmt->execute([$key, trim($_POST[$key])]);
                }
            } elseif (isset($_POST[$key])) {
                $stmt->execute([$key, trim($_POST[$key])]);
            }
        }
    }
    if (!$uploadErrors) {
        redirect('/admin/content.php?saved=1');
    }
}

$saved = isset($_GET['saved']);
$current = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$mediaImages = listUploadedImages();

adminHeader('Contenu de la page d\'accueil');
?>

<?php if ($saved): ?><div class="alert-success">✅ Contenu mis à jour — visible immédiatement sur le site.</div><?php endif; ?>
<?php foreach ($uploadErrors as $err): ?><div class="alert-success" style="background:#FDEDEA; color:#8C2F1B;">❌ <?= $err ?></div><?php endforeach; ?>

<p style="margin-bottom:20px;"><a href="/" target="_blank" class="btn btn-sm">Voir la page d'accueil ↗</a>
<a href="/admin/media.php" class="btn btn-sm" style="margin-left:8px;">🖼️ Médiathèque →</a>
<a href="/admin/design.php" class="btn btn-sm btn-amber" style="margin-left:8px;">🎨 Couleurs &amp; polices →</a></p>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

<?php foreach ($textFields as $groupTitle => $fields): ?>
<div class="panel">
  <h2><?= e($groupTitle) ?></h2>
  <div class="grid-2">
    <?php foreach ($fields as $key => [$label, $type]): ?>
      <div class="form-group" style="<?= $type !== 'text' ? 'grid-column: 1 / -1;' : '' ?>">
        <label><?= e($label) ?></label>
        <?php if ($type === 'textarea'): ?>
          <textarea class="form-control" name="<?= e($key) ?>" rows="2" style="font-family:'Inter',sans-serif;"><?= e($current[$key] ?? '') ?></textarea>
        <?php elseif ($type === 'image'): ?>
          <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <?php if ($current[$key] ?? ''): ?>
              <img src="<?= e($current[$key]) ?>" alt="" style="width:80px; height:80px; object-fit:cover; border-radius:10px; border:1px solid var(--line);">
            <?php endif; ?>
            <div style="flex:1; min-width:220px;">
              <select class="form-control" name="<?= e($key) ?>" style="margin-bottom:8px;">
                <option value="">— Aucune image —</option>
                <?php foreach ($mediaImages as $img): ?>
                  <option value="<?= e($img['url']) ?>" <?= ($current[$key] ?? '') === $img['url'] ? 'selected' : '' ?>><?= e($img['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <input class="form-control" type="file" name="<?= e($key) ?>_file" accept=".jpg,.jpeg,.png,.webp,.gif">
              <p style="font-size:.78rem; color:#6A7194; margin-top:4px;">Choisis une image déjà envoyée, ou envoies-en une nouvelle directement ici (elle sera ajoutée à la médiathèque et utilisée après enregistrement).</p>
            </div>
          </div>
        <?php else: ?>
          <input class="form-control" type="text" name="<?= e($key) ?>" value="<?= e($current[$key] ?? '') ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<button class="btn btn-primary">💾 Enregistrer le contenu</button>
</form>

<?php adminFooter(); ?>
