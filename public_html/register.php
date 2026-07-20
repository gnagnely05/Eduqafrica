<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$back = $_GET['back'] ?? $_POST['back'] ?? '/';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = trim($_POST['country'] ?? '') ?: null;

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nom et email valide requis.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } else {
        $id = registerUser($name, $email, $password, $country);
        if ($id === null) {
            $error = 'Un compte existe déjà avec cet email. <a href="/login.php">Se connecter</a>';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            // Rattacher les CV créés en anonyme dans cette session
            if (!empty($_SESSION['own_cvs'])) {
                $in = implode(',', array_map('intval', $_SESSION['own_cvs']));
                db()->exec("UPDATE cv_documents SET user_id = $id WHERE id IN ($in) AND user_id IS NULL");
            }
            redirect($back);
        }
    }
}

$pageTitle = "Créer un compte";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
  <h1>Créer un compte</h1>
  <p style="color:var(--ink-soft); margin:8px 0 24px;">Gratuit. Nécessaire pour les paiements et retrouver tes CV.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="form-group"><label>Nom complet</label><input class="form-control" name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
    <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
    <div class="form-group"><label>Mot de passe (8+ caractères)</label><input class="form-control" type="password" name="password" required minlength="8"></div>
    <div class="form-group"><label>Pays</label>
      <select name="country" class="form-control">
        <option value="">— Choisir —</option>
        <?php foreach (['Côte d\'Ivoire','Sénégal','Burkina Faso','Mali','Bénin','Togo','Cameroun','Guinée','Niger','Gabon','RD Congo','Autre'] as $c): ?>
          <option value="<?= e($c) ?>" <?= ($_POST['country'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary btn-block">Créer mon compte</button>
  </form>
  <p style="margin-top:16px; font-size:.92rem;">Déjà un compte ? <a href="/login.php?back=<?= e(urlencode($back)) ?>">Se connecter</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
