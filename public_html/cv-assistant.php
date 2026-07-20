<?php
$pageTitle = "Assistant CV — crée ton CV en discutant";
$pageDesc = "Envoie ton ancien CV ou discute avec notre IA : elle construit ton CV professionnel avec toi en quelques minutes. Aperçu gratuit.";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="chat-wrap">
  <span class="eyebrow">Assistant CV</span>
  <h1>Ton CV, en discutant 💬</h1>
  <p style="color:var(--ink-soft); margin-top:8px;">
    Deux façons de faire : <strong>envoie ton ancien CV</strong> (PDF, Word ou texte) et l'IA le modernise,
    ou <strong>pars de zéro</strong> — elle te pose les bonnes questions et rédige pour toi.
    Aperçu gratuit, PDF final à <?= xof(price('cv_download')) ?> CFA.
    <a href="/cv-generator.php" style="font-size:.88rem;">Préférer le formulaire classique ?</a>
  </p>

  <div class="chat-messages" id="chatMessages">
    <div class="msg msg-ai">Salut ! 👋 Je suis ton assistant CV. On commence comment ?</div>
  </div>

  <!-- Choix initial -->
  <div class="quick-replies" id="startChoices">
    <button class="quick-reply" onclick="chooseUpload()">📤 J'ai déjà un CV à améliorer</button>
    <button class="quick-reply" onclick="chooseScratch()">✨ Partir de zéro avec l'IA</button>
  </div>

  <!-- Zone d'upload (cachée par défaut) -->
  <div id="uploadBox" style="display:none; margin-top:14px;">
    <label class="upload-zone" id="uploadZone">
      <div class="up-icon">📄</div>
      <strong>Clique ou dépose ton CV ici</strong>
      <p class="meta" style="margin-top:4px;">PDF, Word (.docx) ou texte — 5 Mo max</p>
      <input type="file" id="cvFile" accept=".pdf,.docx,.txt" style="display:none;">
    </label>
    <div class="form-group" style="margin-top:14px;">
      <label>Quel poste ou domaine vises-tu ? (optionnel mais recommandé)</label>
      <input class="form-control" id="targetJobUpload" placeholder="Ex : Stage en comptabilité, Développeur junior, Commercial…">
    </div>
    <button class="btn btn-coral btn-block" id="uploadBtn" disabled>Analyser mon CV</button>
    <p class="meta" id="uploadStatus" style="margin-top:8px;"></p>
  </div>

  <!-- Formulaire de chat (caché tant qu'on n'a pas choisi) -->
  <form class="chat-form" id="chatForm" style="display:none;">
    <textarea id="chatInput" placeholder="Réponds ici…" required></textarea>
    <button type="submit" class="btn btn-primary" id="chatSend">Envoyer</button>
  </form>
</div>

<script>
const messagesEl = document.getElementById('chatMessages');
const startChoices = document.getElementById('startChoices');
const uploadBox = document.getElementById('uploadBox');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const chatSend = document.getElementById('chatSend');
const cvFile = document.getElementById('cvFile');
const uploadZone = document.getElementById('uploadZone');
const uploadBtn = document.getElementById('uploadBtn');
const uploadStatus = document.getElementById('uploadStatus');

let history = []; // [{role, content}]

function addMsg(text, cls) {
  const div = document.createElement('div');
  div.className = 'msg ' + cls;
  div.textContent = text;
  messagesEl.appendChild(div);
  div.scrollIntoView({ behavior: 'smooth', block: 'end' });
  return div;
}

/* ---------- Option 1 : upload ---------- */
function chooseUpload() {
  startChoices.style.display = 'none';
  addMsg("J'ai déjà un CV à améliorer", 'msg-user');
  addMsg("Parfait ! Envoie-moi ton CV et dis-moi le poste que tu vises : je vais le restructurer, moderniser la présentation et renforcer les formulations. 💪", 'msg-ai');
  uploadBox.style.display = 'block';
}

uploadZone.addEventListener('click', () => cvFile.click());
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
  e.preventDefault();
  uploadZone.classList.remove('dragover');
  if (e.dataTransfer.files.length) { cvFile.files = e.dataTransfer.files; onFileChosen(); }
});
cvFile.addEventListener('change', onFileChosen);

function onFileChosen() {
  if (cvFile.files.length) {
    uploadZone.querySelector('strong').textContent = '✅ ' + cvFile.files[0].name;
    uploadBtn.disabled = false;
  }
}

uploadBtn.addEventListener('click', async () => {
  uploadBtn.disabled = true;
  uploadStatus.textContent = '⏳ Analyse en cours (10-30 secondes)…';
  const fd = new FormData();
  fd.append('cv_file', cvFile.files[0]);
  fd.append('target_job', document.getElementById('targetJobUpload').value);
  try {
    const res = await fetch('/api/cv-extract.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      uploadStatus.textContent = '✅ CV reconstruit ! Redirection vers l\'aperçu…';
      window.location.href = data.redirect;
    } else {
      uploadStatus.textContent = '❌ ' + (data.error || 'Erreur, réessaie.');
      uploadBtn.disabled = false;
    }
  } catch (e) {
    uploadStatus.textContent = '❌ Connexion impossible, réessaie.';
    uploadBtn.disabled = false;
  }
});

/* ---------- Option 2 : chat de zéro ---------- */
function chooseScratch() {
  startChoices.style.display = 'none';
  chatForm.style.display = 'flex';
  addMsg("Partir de zéro avec l'IA", 'msg-user');
  const first = "Super, on va construire ton CV ensemble ! 🚀 D'abord : quel poste, stage ou domaine vises-tu ? Et dans quelle ville/pays cherches-tu ?";
  addMsg(first, 'msg-ai');
  history = [
    { role: 'user', content: "Je veux créer mon CV de zéro." },
    { role: 'assistant', content: first }
  ];
  chatInput.focus();
}

chatForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = chatInput.value.trim();
  if (!text) return;
  addMsg(text, 'msg-user');
  history.push({ role: 'user', content: text });
  chatInput.value = '';
  chatSend.disabled = true;
  const loading = addMsg('…', 'msg-ai');

  try {
    const res = await fetch('/api/cv-chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ history: history })
    });
    const data = await res.json();
    loading.remove();
    if (!data.ok) {
      addMsg(data.error || "Erreur, réessaie dans un instant.", 'msg-ai');
    } else if (data.cv_ready) {
      addMsg("🎉 Ton CV est prêt ! Je t'emmène voir l'aperçu…", 'msg-ai');
      setTimeout(() => window.location.href = data.redirect, 900);
    } else {
      addMsg(data.reply, 'msg-ai');
      history.push({ role: 'assistant', content: data.reply });
    }
  } catch (err) {
    loading.remove();
    addMsg("Connexion impossible. Vérifie ta connexion internet.", 'msg-ai');
  }
  chatSend.disabled = false;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
