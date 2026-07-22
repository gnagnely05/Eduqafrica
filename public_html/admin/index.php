<?php
require_once __DIR__ . '/../../includes/admin-layout.php';

$pdo = db();
$q = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();

// KPIs
$usersTotal   = $q("SELECT COUNT(*) FROM users");
$usersMonth   = $q("SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$revTotal     = $q("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE status='success'");
$revMonth     = $q("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE status='success' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$subsActive   = $q("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expires_at > NOW()");
$cvTotal      = $q("SELECT COUNT(*) FROM cv_documents");
$cvPaid       = $q("SELECT COUNT(*) FROM cv_documents WHERE is_paid=1");
$msgWeek      = $q("SELECT COUNT(*) FROM chat_messages WHERE role='user' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$boursesN     = $q("SELECT COUNT(*) FROM scholarships WHERE is_active=1");
$jobsN        = $q("SELECT COUNT(*) FROM jobs WHERE is_active=1");
$unreadMsg    = $q("SELECT COUNT(*) FROM contact_messages WHERE is_read=0");
$artPub       = $q("SELECT COUNT(*) FROM articles WHERE status='published'");

// Revenus par produit (30 derniers jours)
$revByPurpose = $pdo->query(
    "SELECT purpose, COUNT(*) n, SUM(amount) total
     FROM payment_transactions
     WHERE status='success' AND paid_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY purpose ORDER BY total DESC"
)->fetchAll();

// Derniers paiements
$lastPayments = $pdo->query(
    "SELECT p.id, p.amount, p.purpose, p.status, p.created_at, u.name, u.email
     FROM payment_transactions p JOIN users u ON u.id = p.user_id
     ORDER BY p.id DESC LIMIT 10"
)->fetchAll();

// Derniers logs crons
$cronLogs = $pdo->query(
    "SELECT fetch_type, items_found, items_inserted, status, error_message, created_at
     FROM ai_fetch_logs ORDER BY id DESC LIMIT 8"
)->fetchAll();

$purposeLabels = [
    'cv_download' => 'CV PDF',
    'chat_single_unlock' => 'Chat (unité)',
    'chat_subscription_monthly' => 'Chat (mois)',
    'bourses_single_unlock' => 'Bourse (unité)',
    'bourses_subscription_monthly' => 'Bourses (mois)',
    'bundle_subscription_monthly' => 'Bundle',
];

adminHeader('Tableau de bord');
?>

<p style="margin-bottom:20px; display:flex; gap:8px; flex-wrap:wrap;">
  <a href="/admin/run-fetch.php?type=jobs&csrf=<?= e(csrfToken()) ?>" class="btn btn-primary"
     onclick="return confirm('Lancer la recherche IA d\'emplois sur le web ? Cela peut prendre jusqu\'à une minute.')">🔍 Rechercher des emplois (IA)</a>
  <a href="/admin/run-fetch.php?type=scholarships&csrf=<?= e(csrfToken()) ?>" class="btn btn-primary"
     onclick="return confirm('Lancer la recherche IA de bourses sur le web ? Cela peut prendre jusqu\'à une minute.')">🔍 Rechercher des bourses (IA)</a>
</p>

<div class="kpi-grid">
  <div class="kpi"><div class="val"><?= xof($revTotal) ?></div><div class="lbl">Revenus totaux</div>
    <div class="sub">+<?= xof($revMonth) ?> ce mois</div></div>
  <div class="kpi"><div class="val"><?= $usersTotal ?></div><div class="lbl">Inscrits</div>
    <div class="sub">+<?= $usersMonth ?> ce mois</div></div>
  <div class="kpi"><div class="val"><?= $subsActive ?></div><div class="lbl">Abonnements actifs</div></div>
  <div class="kpi"><div class="val"><?= $msgWeek ?></div><div class="lbl">Questions IA / 7 jours</div></div>
  <div class="kpi"><div class="val"><?= $cvTotal ?></div><div class="lbl">CV créés</div>
    <div class="sub"><?= $cvPaid ?> payés (<?= $cvTotal ? round($cvPaid * 100 / $cvTotal) : 0 ?>%)</div></div>
  <div class="kpi"><div class="val"><?= $boursesN ?></div><div class="lbl">Bourses actives</div></div>
  <div class="kpi"><div class="val"><?= $jobsN ?></div><div class="lbl">Emplois actifs</div></div>
  <div class="kpi"><div class="val"><?= $artPub ?></div><div class="lbl">Articles publiés</div>
    <?php if ($unreadMsg): ?><div class="sub" style="color:var(--coral);"><?= $unreadMsg ?> message(s) non lu(s)</div><?php endif; ?></div>
</div>

<div class="grid-2">
  <div class="panel">
    <h2>💰 Revenus par produit (30 jours)</h2>
    <?php if (!$revByPurpose): ?><p class="meta">Aucun paiement sur la période.</p><?php else: ?>
    <table>
      <tr><th>Produit</th><th>Ventes</th><th>Total</th></tr>
      <?php foreach ($revByPurpose as $r): ?>
      <tr>
        <td><?= e($purposeLabels[$r['purpose']] ?? $r['purpose']) ?></td>
        <td><?= (int)$r['n'] ?></td>
        <td><strong><?= xof((int)$r['total']) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h2>🤖 Derniers crons IA</h2>
    <?php if (!$cronLogs): ?><p class="meta">Aucun log — les crons n'ont pas encore tourné.</p><?php else: ?>
    <table>
      <tr><th>Type</th><th>Trouvé / inséré</th><th>Statut</th><th>Date</th></tr>
      <?php foreach ($cronLogs as $l): ?>
      <tr>
        <td><?= e($l['fetch_type']) ?></td>
        <td><?= (int)$l['items_found'] ?> / <?= (int)$l['items_inserted'] ?></td>
        <td><span class="badge <?= $l['status'] === 'success' ? 'badge-ok' : 'badge-err' ?>"><?= e($l['status']) ?></span>
          <?php if ($l['error_message']): ?><br><small><?= e(mb_substr($l['error_message'], 0, 60)) ?></small><?php endif; ?></td>
        <td><?= e(date('d/m H:i', strtotime($l['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <h2>💳 Dernières transactions</h2>
  <?php if (!$lastPayments): ?><p class="meta">Aucune transaction.</p><?php else: ?>
  <table>
    <tr><th>#</th><th>Client</th><th>Produit</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
    <?php foreach ($lastPayments as $p): ?>
    <tr>
      <td><?= (int)$p['id'] ?></td>
      <td><?= e($p['name']) ?><br><small><?= e($p['email']) ?></small></td>
      <td><?= e($purposeLabels[$p['purpose']] ?? $p['purpose']) ?></td>
      <td><?= xof((int)$p['amount']) ?></td>
      <td><span class="badge <?= $p['status'] === 'success' ? 'badge-ok' : ($p['status'] === 'pending' ? 'badge-warn' : 'badge-err') ?>"><?= e($p['status']) ?></span></td>
      <td><?= e(date('d/m H:i', strtotime($p['created_at']))) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php adminFooter(); ?>
