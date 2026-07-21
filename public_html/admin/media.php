<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$error = null;
$success = null;

// ---- Upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    if (empty($_FILES['image'])) {
        $error = "Aucune image reçue.";
    } else {
        $res = saveUploadedImage($_FILES['image']);
        if ($res['ok']) {
            $success = 'Image ajoutée : ' . $res['url'];
        } else {
            $error = $res['error'];
        }
    }
}

// ---- Suppression ----
if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
    if (deleteUploadedImage((string)($_GET['file'] ?? ''))) {
        $success = 'Image supprimée.';
    }
}

$images = listUploadedImages();

adminHeader('Médiathèque');
?>

<?php if ($success): ?><div class="alert-success">✅ <?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-success" style="background:#FDEDEA; color:#8C2F1B;">❌ <?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <h2>Ajouter une image</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <div class="form-group">
      <label>Fichier (JPG, PNG, WEBP ou GIF — 8 Mo max)</label>
      <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" required>
    </div>
    <button class="btn btn-primary">📤 Envoyer</button>
  </form>
</div>

<div class="panel">
  <h2>Images disponibles (<?= count($images) ?>)</h2>
  <p style="font-size:.82rem; color:#6A7194; margin-bottom:16px;">
    Ces images sont aussi sélectionnables directement depuis
    <a href="/admin/content.php">Contenu accueil</a>, sans repasser par ici.
  </p>
  <?php if (!$images): ?>
    <p>Aucune image envoyée pour le moment.</p>
  <?php else: ?>
  <div class="grid-2" style="grid-template-columns: repeat(4, 1fr);">
    <?php foreach ($images as $img): ?>
    <div class="card" style="padding:12px;">
      <img src="<?= e($img['url']) ?>" alt="" style="width:100%; height:110px; object-fit:cover; border-radius:8px; margin-bottom:8px;">
      <input class="form-control" type="text" readonly value="<?= e($img['url']) ?>" onclick="this.select()" style="font-size:.75rem; margin-bottom:8px;">
      <a href="?action=delete&file=<?= e(urlencode($img['name'])) ?>&csrf=<?= e(csrfToken()) ?>"
         onclick="return confirm('Supprimer cette image ?')"
         class="btn btn-sm btn-danger" style="width:100%; text-align:center;">Supprimer</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
