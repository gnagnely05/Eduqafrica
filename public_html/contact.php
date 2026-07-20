<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, mb_substr($message, 0, 5000)]);
        // Notification email (fonctionne nativement sur Hostinger)
        @mail(SITE_EMAIL, '[' . SITE_NAME . '] Nouveau message de ' . $name,
              "De : $name <$email>\n\n$message",
              'From: ' . SITE_EMAIL . "\r\nReply-To: $email");
        $sent = true;
    }
}

$pageTitle = "Contact";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
  <h1>Nous contacter</h1>
  <p style="color:var(--ink-soft); margin:8px 0 24px;">Une question, une suggestion, un partenariat ? Écris-nous.</p>
  <?php if ($sent): ?>
    <div class="alert alert-success">✅ Message envoyé ! Nous te répondons sous 48h.</div>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <div class="form-group"><label>Nom</label><input class="form-control" name="name" required></div>
    <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="6" required></textarea></div>
    <button class="btn btn-primary btn-block">Envoyer</button>
  </form>
  <?php endif; ?>
  <p style="margin-top:20px; font-size:.92rem;">Ou par email : <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
