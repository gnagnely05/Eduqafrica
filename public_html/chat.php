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
    Filières, concours, écoles, réorientation, débouchés… Discute librement, gratuitement.
    <?php if ($hasChatPremium): ?>
      <span class="badge badge-premium">Abonnement actif — rapport d'orientation complet</span>
    <?php else: ?>
      Ton rapport d'orientation complet (profil RIASEC, métiers compatibles, plan d'action) : abonnement à <?= price('orientation_bourses_monthly') ?> F/mois (accès aussi aux bourses premium).
    <?php endif; ?>
  </p>
  <p style="margin-top:10px;">💡 Envie d'un premier aperçu ? <a href="/personnalite.php">Fais le test de personnalité RIASEC</a> (gratuit, 2 minutes) avant de discuter.</p>

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
const PRICE_MONTHLY = <?= price('orientation_bourses_monthly') ?>;

const messagesEl = document.getElementById('chatMessages');
const form = document.getElementById('chatForm');
const input = document.getElementById('chatInput');
const sendBtn = document.getElementById('chatSend');
let conversationId = null;

function formatMsg(text) {
  return text
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>');
}

function addMsg(text, cls) {
  const div = document.createElement('div');
  div.className = 'msg ' + cls;
  div.innerHTML = formatMsg(text);
  messagesEl.appendChild(div);
  div.scrollIntoView({ behavior: 'smooth', block: 'end' });
  return div;
}

function sendMessage(text) {
  document.querySelectorAll('.quick-replies').forEach(el => el.remove());
  input.value = text;
  if (form.requestSubmit) {
    form.requestSubmit();
  } else {
    form.dispatchEvent(new Event('submit', { cancelable: true }));
  }
}

function addOptions(options) {
  const wrap = document.createElement('div');
  wrap.className = 'quick-replies';
  options.forEach(opt => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'quick-reply';
    btn.textContent = opt;
    btn.addEventListener('click', () => sendMessage(opt));
    wrap.appendChild(btn);
  });
  messagesEl.appendChild(wrap);
  wrap.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function addTeaser(teaser) {
  const div = document.createElement('div');
  div.className = 'msg msg-ai';
  div.style.filter = 'blur(4px)';
  div.style.userSelect = 'none';
  div.textContent = teaser;
  messagesEl.appendChild(div);
}

function addPaywall(messageId) {
  const box = document.createElement('div');
  box.className = 'paywall-box';
  box.innerHTML = `
    <p><strong>🔓 Ton profil se précise !</strong> Débloque ton rapport d'orientation complet : profil RIASEC, 10 métiers compatibles avec score, plan d'action 30/90/365 jours, SWOT personnelle — et accès à un test de personnalité approfondi et aux bourses premium.</p>
    <div class="paywall-actions">
      <a href="/payer.php?type=orientation_bourses" class="btn btn-amber">Abonnement Orientation + Bourses — ${PRICE_MONTHLY} F/mois</a>
    </div>`;
  messagesEl.appendChild(box);
  box.scrollIntoView({ behavior: 'smooth', block: 'end' });
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

      if (data.teaser) addTeaser(data.teaser);

      if (data.is_limited && IS_LOGGED_IN) {
        addPaywall(data.message_id);
      } else if (data.is_limited && !IS_LOGGED_IN) {
        const box = document.createElement('div');
        box.className = 'paywall-box';
        box.innerHTML = `<p><strong>🔓 Ton profil se précise !</strong> <a href="/register.php?back=/chat.php">Crée un compte gratuit</a> pour débloquer ton rapport d'orientation complet (${PRICE_MONTHLY} F/mois en abonnement, avec test de personnalité approfondi et bourses premium).</p>`;
        messagesEl.appendChild(box);
        box.scrollIntoView({ behavior: 'smooth', block: 'end' });
      } else if (data.options && data.options.length) {
        addOptions(data.options);
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
