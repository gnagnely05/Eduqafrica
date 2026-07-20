<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

const UPLOAD_DIR = __DIR__ . '/../assets/uploads';
const UPLOAD_URL = '/assets/uploads';
const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const MAX_SIZE = 3 * 1024 * 1024; // 3 Mo

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$error = null;
$success = null;

// ---- Upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Aucune image reçue ou erreur d'upload.";
    } else {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ALLOWED_EXT, true)) {
            $error = 'Format non autorisé. Utilise : ' . implode(', ', ALLOWED_EXT) . '.';
        } elseif ($file['size'] > MAX_SIZE) {
            $error = 'Image trop lourde (3 Mo max).';
        } elseif (!@getimagesize($file['tmp_name'])) {
            $error = "Ce fichier n'est pas une image valide.";
        } else {
            $baseName = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
            $filename = $baseName . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $filename)) {
                $success = 'Image ajoutée : ' . UPLOAD_URL . '/' . $filename;
            } else {
                $error = "Impossible d'enregistrer l'image sur le serveur.";
            }
        }
    }
}

// ---- Suppression ----
if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
    $target = basename((string)($_GET['file'] ?? ''));
    $path = UPLOAD_DIR . '/' . $target;
    if ($target !== '' && is_file($path) && dirname(realpath($path)) === realpath(UPLOAD_DIR)) {
        unlink($path);
        $success = 'Image supprimée.';
    }
}

$images = [];
foreach (glob(UPLOAD_DIR . '/*') as $path) {
    if (is_file($path)) {
        $images[] = [
            'name' => basename($path),
            'url'  => UPLOAD_URL . '/' . basename($path),
            'time' => filemtime($path),
        ];
    }
}
usort($images, fn($a, $b) => $b['time'] - $a['time']);

adminHeader('Médiathèque');
?>

<?php if ($success): ?><div class="alert-success">✅ <?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-success" style="background:#FDEDEA; color:#8C2F1B;">❌ <?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <h2>Ajouter une image</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <div class="form-group">
      <label>Fichier (JPG, PNG, WEBP ou GIF — 3 Mo max)</label>
      <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" required>
    </div>
    <button class="btn btn-primary">📤 Envoyer</button>
  </form>
</div>

<div class="panel">
  <h2>Images disponibles (<?= count($images) ?>)</h2>
  <p style="font-size:.82rem; color:#6A7194; margin-bottom:16px;">
    Copie l'URL d'une image et colle-la dans un champ « image » (ex : dans
    <a href="/admin/content.php">Contenu accueil</a>) pour l'utiliser sur le site.
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
