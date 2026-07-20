<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();

if (($_GET['action'] ?? '') === 'read' && csrfCheck($_GET['csrf'] ?? null)) {
    $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    redirect('/admin/messages.php');
}
if (($_GET['action'] ?? '') === 'delete' && csrfCheck($_GET['csrf'] ?? null)) {
    $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    redirect('/admin/messages.php');
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 100')->fetchAll();

adminHeader('Messages de contact');
?>

<div class="panel">
  <?php if (!$messages): ?>
    <p>Aucun message pour le moment.</p>
  <?php else: ?>
  <table>
    <tr><th>De</th><th>Message</th><th>Reçu le</th><th></th></tr>
    <?php foreach ($messages as $m): ?>
    <tr style="<?= $m['is_read'] ? '' : 'background:#FFFBEE;' ?>">
      <td><strong><?= e($m['name']) ?></strong><br>
        <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
        <?php if (!$m['is_read']): ?><br><span class="badge badge-warn">Non lu</span><?php endif; ?></td>
      <td style="max-width:420px;"><?= nl2br(e($m['message'])) ?></td>
      <td style="white-space:nowrap;"><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
      <td style="white-space:nowrap;">
        <?php if (!$m['is_read']): ?>
          <a href="?action=read&id=<?= (int)$m['id'] ?>&csrf=<?= e(csrfToken()) ?>" class="btn btn-sm btn-primary">Lu ✓</a>
        <?php endif; ?>
        <a href="?action=delete&id=<?= (int)$m['id'] ?>&csrf=<?= e(csrfToken()) ?>"
           onclick="return confirm('Supprimer ce message ?')" class="btn btn-sm btn-danger">Suppr.</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
