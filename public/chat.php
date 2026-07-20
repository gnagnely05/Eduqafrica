<?php
$pageTitle = "Conseiller d'orientation IA";
$pageDesc = "Pose tes questions d'orientation à notre conseiller IA : filières, écoles, débouchés, réorientation. Réponse immédiate et gratuite.";
require_once __DIR__ . '/../includes/header.php';
$hasChatPremium = $user ? hasActiveSubscription((int)$user['id'], 'chat') : false;
?>

<div class="chat-wrap">
  <span class="eyebrow">Orientation IA</span>
  <h1>Quelle est ta question&nbsp;?</h1>
  <p style="color:var(--ink-soft); margin-top:8px;">
    Filières, concours, écoles, réorientation, débouchés… Réponse rapide gratuite.
    <?php if ($hasChatPremium): ?>
      <span class="badge badge-premium">Abonnement actif — réponses approfondies</span>
    <?php else: ?>
      Pour une analyse approfondie : <?= price('chat_single') ?> F la question ou <?= price('chat_monthly') ?> F/mois.
    <?php endif; ?>
  </p>

  <div class="chat-messages" id="chatMessages">
    <div class="msg msg-ai">Salut ! 👋 Je suis ton conseiller d'orientation. Dis-moi où tu en es (ton niveau, ton pays) et ce que tu cherches, je t'aide à y voir clair.</div>
  </div>

  <form class="chat-form" id="chatForm">
    <textarea id="chatInput" placeholder="Ex : Je suis en Terminale D en Côte d'Ivoire, quelles filières après le bac ?" required></textarea>
    <button type="submit" class="btn btn-primary" id="chatSend">Envoyer</button>
  </form>
</div>

<script>
const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const PRICE_SINGLE = <?= price('chat_single') ?>;
const PRICE_MONTHLY = <?= price('chat_monthly') ?>;

const messagesEl = document.getElementById('chatMessages');
const form = document.getElementById('chatForm');
const input = document.getElementById('chatInput');
const sendBtn = document.getElementById('chatSend');
let conversationId = null;

function addMsg(text, cls) {
  const div = document.createElement('div');
  div.className = 'msg ' + cls;
  div.textContent = text;
  messagesEl.appendChild(div);
  div.scrollIntoView({ behavior: 'smooth', block: 'end' });
  return div;
}

function addPaywall(messageId) {
  const box = document.createElement('div');
  box.className = 'paywall-box';
  box.innerHTML = `
    <p><strong>🔓 Tu veux aller plus loin ?</strong> Débloquer l'analyse approfondie : plan d'action détaillé, écoles précises, coûts, alternatives.</p>
    <div class="paywall-actions">
      <a href="/payer.php?type=chat_single&mid=${messageId}" class="btn btn-coral">Cette question — ${PRICE_SINGLE} F</a>
      <a href="/payer.php?type=chat_monthly" class="btn btn-amber">Abonnement 1 mois — ${PRICE_MONTHLY} F</a>
    </div>`;
  messagesEl.appendChild(box);
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = input.value.trim();
  if (!text) return;

  addMsg(text, 'msg-user');
  input.value = '';
  sendBtn.disabled = true;
  const loading = addMsg('…', 'msg-ai');

  try {
    const res = await fetch('/api/chat-send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, conversation_id: conversationId })
    });
    const data = await res.json();
    loading.remove();

    if (!data.ok) {
      addMsg(data.error || "Désolé, une erreur est survenue. Réessaie dans un instant.", 'msg-ai');
    } else {
      conversationId = data.conversation_id;
      addMsg(data.reply, 'msg-ai' + (data.is_limited ? ' locked' : ''));
      if (data.is_limited && IS_LOGGED_IN) {
        addPaywall(data.message_id);
      } else if (data.is_limited && !IS_LOGGED_IN) {
        const box = document.createElement('div');
        box.className = 'paywall-box';
        box.innerHTML = `<p><strong>🔓 Réponse approfondie disponible.</strong> <a href="/register.php?back=/chat.php">Crée un compte gratuit</a> pour débloquer les analyses détaillées (${PRICE_SINGLE} F la question ou ${PRICE_MONTHLY} F/mois).</p>`;
        messagesEl.appendChild(box);
      }
    }
  } catch (err) {
    loading.remove();
    addMsg("Connexion impossible. Vérifie ta connexion internet.", 'msg-ai');
  }
  sendBtn.disabled = false;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
