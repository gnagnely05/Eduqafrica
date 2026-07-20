<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$back = $_GET['back'] ?? $_POST['back'] ?? '/';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    if (loginUser(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
        // Rattacher les CV anonymes de la session
        $uid = (int)$_SESSION['user_id'];
        if (!empty($_SESSION['own_cvs'])) {
            $in = implode(',', array_map('intval', $_SESSION['own_cvs']));
            db()->exec("UPDATE cv_documents SET user_id = $uid WHERE id IN ($in) AND user_id IS NULL");
        }
        redirect($back);
    }
    $error = 'Email ou mot de passe incorrect.';
}

$pageTitle = "Connexion";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
  <h1>Connexion</h1>
  <?php if ($error): ?><div class="alert alert-error" style="margin-top:16px;"><?= e($error) ?></div><?php endif; ?>
  <form method="post" style="margin-top:20px;">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="form-group"><label>Mot de passe</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn btn-primary btn-block">Se connecter</button>
  </form>
  <p style="margin-top:16px; font-size:.92rem;">Pas encore de compte ? <a href="/register.php?back=<?= e(urlencode($back)) ?>">S'inscrire gratuitement</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
