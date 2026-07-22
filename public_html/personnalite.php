<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/riasec.php';

$pdo = db();

// ---- Soumission du test ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? null)) {
    $scores = [];
    foreach (array_keys(riasecQuestions()) as $dim) {
        $total = 0;
        foreach ($_POST['q'][$dim] ?? [] as $val) {
            $total += max(1, min(5, (int)$val));
        }
        $scores[$dim] = $total;
    }
    $code = riasecCode($scores);

    $user = currentUser();
    $stmt = $pdo->prepare(
        'INSERT INTO personality_tests (user_id, session_id, score_r, score_i, score_a, score_s, score_e, score_c, code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user ? (int)$user['id'] : null,
        session_id(),
        $scores['r'], $scores['i'], $scores['a'], $scores['s'], $scores['e'], $scores['c'],
        $code,
    ]);
    $testId = (int)$pdo->lastInsertId();
    $_SESSION['own_tests'][] = $testId;

    redirect('/personnalite.php?id=' . $testId);
}

// ---- Affichage des résultats ----
$resultId = (int)($_GET['id'] ?? 0);
$result = null;
if ($resultId) {
    $stmt = $pdo->prepare('SELECT * FROM personality_tests WHERE id = ?');
    $stmt->execute([$resultId]);
    $result = $stmt->fetch();

    $user = currentUser();
    $isOwner = ($result && $user && (int)$result['user_id'] === (int)$user['id'])
        || in_array($resultId, $_SESSION['own_tests'] ?? [], true);
    if ($result && !$isOwner && !(isLoggedIn() && isAdmin())) {
        http_response_code(403);
        exit('Accès refusé.');
    }
}

$user = currentUser();
$hasPremium = $user ? hasActiveSubscription((int)$user['id'], 'chat') : false;

$pageTitle = $result ? "Mon profil RIASEC" : "Test de personnalité professionnelle";
$pageDesc = "Découvre ton profil RIASEC gratuitement : intérêts, aptitudes et métiers qui te correspondent, basé sur le modèle de John Holland.";
require_once __DIR__ . '/../includes/header.php';

$labels = riasecLabels();
$descriptions = riasecDescriptions();
$careers = riasecCareers();
?>

<div class="container section" style="max-width:760px;">
<?php if ($resultId && !$result): ?>
  <p>Résultat introuvable. <a href="/personnalite.php">Refaire le test</a>.</p>
<?php elseif (!$result): ?>

  <span class="eyebrow">Test de personnalité</span>
  <h1>Découvre ton profil RIASEC</h1>
  <p style="color:var(--ink-soft); margin:12px 0 28px;">
    24 affirmations courtes, basées sur le modèle scientifique de John Holland (RIASEC), utilisé
    par les conseillers d'orientation dans le monde entier. Réponds honnêtement — il n'y a pas de
    bonne ou de mauvaise réponse. Gratuit, résultat immédiat.
  </p>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach (riasecQuestions() as $dim => $questions): ?>
      <?php foreach ($questions as $i => $question): ?>
      <div class="card" style="margin-bottom:14px;">
        <p style="margin-bottom:10px; font-weight:600;"><?= e($question) ?></p>
        <div style="display:flex; justify-content:space-between; gap:6px; flex-wrap:wrap;">
          <?php foreach ([1 => 'Pas du tout', 2 => 'Un peu', 3 => 'Moyennement', 4 => 'Beaucoup', 5 => 'Tout à fait'] as $val => $label): ?>
            <label style="flex:1; text-align:center; font-size:.8rem; cursor:pointer;">
              <input type="radio" name="q[<?= e($dim) ?>][<?= $i ?>]" value="<?= $val ?>" required style="display:block; margin:0 auto 4px;">
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary btn-block">Voir mon profil</button>
  </form>

<?php else:
  $scores = [
      'r' => (int)$result['score_r'], 'i' => (int)$result['score_i'], 'a' => (int)$result['score_a'],
      's' => (int)$result['score_s'], 'e' => (int)$result['score_e'], 'c' => (int)$result['score_c'],
  ];
  $maxScore = max(20, max($scores));
  arsort($scores);
  $topDims = array_slice(array_keys($scores), 0, 3);
?>

  <span class="eyebrow">Ton résultat</span>
  <h1>Ton code RIASEC : <?= e($result['code']) ?></h1>
  <p style="color:var(--ink-soft); margin:12px 0 28px;">
    Basé sur tes réponses, voici tes intérêts professionnels dominants selon le modèle de John Holland.
  </p>

  <div class="card" style="margin-bottom:24px;">
    <?php foreach ($scores as $dim => $score): ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:.92rem;">
          <strong><?= e($labels[$dim]) ?></strong><span class="meta"><?= $score ?>/20</span>
        </div>
        <div style="background:var(--light-grey); border-radius:99px; height:10px; overflow:hidden;">
          <div style="background:var(--coral); height:100%; width:<?= round($score / $maxScore * 100) ?>%;"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($topDims as $rank => $dim): ?>
    <?php if ($rank === 0 || $hasPremium): ?>
    <div class="card" style="margin-bottom:16px;">
      <h3><?= e($labels[$dim]) ?><?= $rank === 0 ? ' — profil dominant' : '' ?></h3>
      <p><?= e($descriptions[$dim]) ?></p>
      <p class="meta" style="margin-top:8px;"><strong>Métiers souvent liés :</strong> <?= e(implode(', ', array_slice($careers[$dim], 0, $hasPremium ? 7 : 3))) ?><?= !$hasPremium ? '…' : '' ?></p>
    </div>
    <?php else: ?>
    <div class="card" style="margin-bottom:16px; position:relative;">
      <h3 style="filter:blur(4px); user-select:none;"><?= e($labels[$dim]) ?></h3>
      <p style="filter:blur(4px); user-select:none;"><?= e($descriptions[$dim]) ?></p>
    </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if (!$hasPremium): ?>
  <div class="paywall-box" style="margin-top:8px;">
    <p><strong>🔓 Débloque ton analyse complète</strong> — les 3 profils dominants détaillés, la liste complète des métiers correspondants, et intègre ce profil dans une conversation avec notre conseiller IA pour une orientation vraiment personnalisée.</p>
    <div class="paywall-actions">
      <a href="/payer.php?type=chat_monthly" class="btn btn-amber">Abonnement 1 mois — <?= price('chat_monthly') ?> F</a>
      <a href="/payer.php?type=bundle" class="btn btn-coral">Bundle + bourses — <?= price('bundle_monthly') ?> F/mois</a>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-top:24px; display:flex; gap:10px; flex-wrap:wrap;">
    <a href="/chat.php" class="btn btn-primary">Discuter avec le conseiller IA</a>
    <a href="/personnalite.php" class="btn btn-ghost">Refaire le test</a>
  </div>

<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
