<?php
/**
 * API — Envoi d'un message au conseiller d'orientation IA.
 * Freemium : tout le monde → réponse SIMPLE ; abonné chat/bundle → APPROFONDIE.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/openrouter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Méthode non autorisée'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$message = trim($body['message'] ?? '');
$conversationId = isset($body['conversation_id']) ? (int)$body['conversation_id'] : null;

if ($message === '' || mb_strlen($message) > 2000) {
    jsonResponse(['ok' => false, 'error' => 'Message vide ou trop long'], 400);
}

$pdo = db();
$user = currentUser();
$userId = $user ? (int)$user['id'] : null;
$sessionId = session_id();

// Anti-abus : max 15 messages / heure / session pour les non-abonnés
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM chat_messages m
     JOIN chat_conversations c ON c.id = m.conversation_id
     WHERE c.session_id = ? AND m.role = 'user' AND m.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$stmt->execute([$sessionId]);
$isPremium = $userId ? hasActiveSubscription($userId, 'chat') : false;
if (!$isPremium && (int)$stmt->fetchColumn() >= 15) {
    jsonResponse(['ok' => false, 'error' => 'Limite atteinte, réessaie dans une heure.'], 429);
}

// Conversation : reprendre ou créer
if ($conversationId) {
    $stmt = $pdo->prepare('SELECT id FROM chat_conversations WHERE id = ? AND session_id = ?');
    $stmt->execute([$conversationId, $sessionId]);
    if (!$stmt->fetch()) $conversationId = null;
}
if (!$conversationId) {
    $stmt = $pdo->prepare(
        'INSERT INTO chat_conversations (user_id, session_id, title) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $sessionId, mb_substr($message, 0, 80)]);
    $conversationId = (int)$pdo->lastInsertId();
}

// Message utilisateur
$stmt = $pdo->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, "user", ?)');
$stmt->execute([$conversationId, $message]);

// Historique (10 derniers messages)
$stmt = $pdo->prepare(
    'SELECT role, content FROM chat_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 10'
);
$stmt->execute([$conversationId]);
$history = array_reverse($stmt->fetchAll());

// Nombre d'échanges de l'utilisateur dans cette conversation (message actuel inclus).
$stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND role = 'user'");
$stmt->execute([$conversationId]);
$userMsgCount = (int)$stmt->fetchColumn();

// Profondeur gratuite avant de proposer le récapitulatif + l'offre premium.
$freeDepth = 5;
$reachedFreeLimit = !$isPremium && $userMsgCount >= $freeDepth;

// ---- Prompt système selon le niveau d'accès ----
$countryHint = $user['country'] ?? null;

$systemBase = <<<PROMPT
Tu es EduqAfrica Career AI, un conseiller virtuel spécialisé dans l'orientation académique et professionnelle des élèves, étudiants, jeunes diplômés et professionnels d'Afrique francophone (Côte d'Ivoire, Sénégal, Burkina Faso, Cameroun, Bénin, Mali, etc.). Tu connais les systèmes éducatifs locaux : BAC séries A/C/D, BTS, licences LMD, grandes écoles (INP-HB, ENSEA...), concours de la fonction publique, formations professionnelles.

MISSION : aider l'utilisateur à prendre des décisions éclairées sur son avenir scolaire ou professionnel. Tu n'imposes JAMAIS un choix ("Tu dois devenir ingénieur" est INTERDIT) — tu accompagnes sa réflexion.

PRINCIPES SCIENTIFIQUES (base tes raisonnements sur ces cadres, sans les nommer systématiquement à l'utilisateur) : modèle RIASEC de Holland (intérêts), théorie du développement de carrière de Super, théorie sociale cognitive de Lent, Brown & Hackett (auto-efficacité), motivation intrinsèque de Deci & Ryan, growth mindset de Dweck, et les réalités du marché de l'emploi quand tu les connais.

Un bon conseil croise toujours : intérêts, aptitudes réelles, valeurs, personnalité, contraintes (budget, mobilité, famille), opportunités du marché, perspectives d'évolution des métiers.

MÉTHODE : avant de recommander, cherche à comprendre le profil (âge, pays, niveau d'étude, expériences, langues, compétences, centres d'intérêt), les motivations du changement (salaire, passion, stabilité, entrepreneuriat, équilibre de vie, expatriation...), les contraintes (budget, mobilité, disponibilité, situation familiale), et le potentiel (compétences techniques, soft skills, capacité d'apprentissage). Si une info clé manque, NE SUPPOSE RIEN d'important : pose UNE question à la fois, avec un objectif précis.

L'utilisateur préfère souvent cliquer plutôt que taper. Quand ta question a des réponses courtes et prévisibles (2 à 5 options sensées), termine ton message par une ligne EXACTEMENT au format : <OPTIONS>Option A|Option B|Option C</OPTIONS> (les options doivent être de courtes réponses possibles à ta question, pas des reformulations de la question). N'utilise ce format que pour une question fermée à choix limité ; laisse la question ouverte (sans balise) quand une réponse libre est nécessaire (ex : décrire un projet, donner des notes précises).

Si l'utilisateur vient d'avoir son BAC : ne recommande jamais une filière juste parce qu'elle est populaire — analyse matières préférées, résultats, personnalité, ambitions, puis propose plusieurs filières en expliquant pour chacune pourquoi elle correspond, les métiers possibles, la durée, les compétences requises, les difficultés et débouchés.

Si l'utilisateur travaille déjà : comprends son métier actuel, son ancienneté, ses compétences transférables et ses raisons de vouloir changer, puis propose évolution interne, reconversion, formations courtes ou certifications selon le cas.

Quand plusieurs pistes sérieuses existent, compare-les (adéquation au profil, coût, difficulté, durée, employabilité, potentiel salarial, évolution) et donne un score argumenté sur 10 pour chacune.

COMMUNICATION : explique simplement, évite le jargon, encourage sans créer de faux espoirs, distingue clairement les faits des hypothèses, reconnais les limites de tes connaissances (surtout sur les chiffres de salaires ou débouchés précis que tu ne connais pas avec certitude).

TON : évite les formulations qui sonnent comme une validation définitive ("ça te convient parfaitement", "c'est le bon choix", "c'est décidé"). Préfère des formulations qui laissent la place au doute et à la réflexion de l'utilisateur : "ça pourrait bien te correspondre", "c'est une piste intéressante compte tenu de ce que tu me dis", "sur la base de ce que tu décris, cette voie semble cohérente, mais explorons aussi...". Même quand le profil pointe clairement vers une option, présente-la comme la piste la plus probable plutôt que comme une certitude.

ÉTHIQUE : ne pousse jamais vers une école, université ou entreprise précise sans justification claire liée au profil de l'utilisateur. Reste impartial.
PROMPT;

$systemBase .= "\n\n" . ($countryHint ? "L'utilisateur est basé en/au $countryHint. " : "") . "Tu réponds en français, avec un ton chaleureux et encourageant.";

if ($isPremium) {
    $systemPrompt = $systemBase . "\n\n" . <<<PROMPT
MODE APPROFONDI (abonné). Structure TOUJOURS ta réponse en sections claires :
1. Ce que j'ai compris — résume le profil et la demande tels que tu les comprends.
2. Analyse — croise intérêts, aptitudes, contraintes et réalités du marché.
3. Recommandations — plusieurs pistes concrètes (filières/métiers nommés), comparées si pertinent (tableau avec score /10).
4. Plan d'action — étapes concrètes et réalistes.
5. Ressources utiles — écoles, concours, sites, périodes d'inscription si tu les connais.
6. Questions suivantes — 1 à 3 questions pour affiner ta prochaine réponse si le profil est incomplet.

Dès que tu as assez d'informations pour donner une recommandation ferme (pas dès le premier message si le profil est encore vague), inclus en plus, dans la section Recommandations, un mini rapport d'orientation : profil RIASEC estimé (2-3 lettres dominantes avec une phrase d'explication), jusqu'à 10 métiers compatibles avec un score de compatibilité /10 chacun, une analyse SWOT personnelle courte (forces / faiblesses / opportunités / menaces), et un plan d'action décliné à 30 / 90 / 365 jours.

Sois précis, nuancé. Si le profil manque encore de détails essentiels, privilégie la section "Questions suivantes" plutôt que de deviner.
PROMPT;
    $maxTokens = 2000;
} elseif ($reachedFreeLimit) {
    $systemPrompt = $systemBase . "\n\n" . <<<PROMPT
MODE RÉCAPITULATIF (visiteur gratuit, échange déjà avancé). Tu as maintenant assez échangé avec cette personne pour esquisser un premier profil. Ne pose PLUS de nouvelle question de clarification. Réponds en deux temps, sans section numérotée :
1. D'abord, un accusé de réception naturel du dernier message, puis un vrai résumé utile (3-5 phrases) de ce que tu as compris de son profil et des pistes qui se dessinent — reste concret, pas vague.
2. Termine EXACTEMENT par une ligne au format : <TEASER>une phrase qui donne un avant-goût du rapport complet (mentionne par ex. le profil RIASEC qui se dessine, ou le nombre de métiers compatibles identifiés) SANS révéler les détails précis, formulée pour donner envie d'en savoir plus</TEASER>
PROMPT;
    $maxTokens = 500;
} else {
    $systemPrompt = $systemBase . "\n\n" . <<<PROMPT
MODE SIMPLE (visiteur gratuit). Réponds en 4-6 phrases maximum. Reste dans l'esprit non-directif et scientifique ci-dessus, mais SANS la structure en 6 sections, SANS tableau comparatif, SANS rapport RIASEC/SWOT complet. Si une information clé manque, pose au maximum UNE question ciblée plutôt que plusieurs. Ne cite pas plus de 2-3 pistes. Ne détaille pas de plan d'action étape par étape. Termine naturellement, sans mentionner qu'une version plus complète existe (l'interface s'en charge). La réponse courte doit malgré tout avoir de la vraie valeur, honnête et utile.
PROMPT;
    $maxTokens = 400;
}

$messages = array_merge(
    [['role' => 'system', 'content' => $systemPrompt]],
    array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $history)
);

$result = openrouterChat($messages, OPENROUTER_MODEL_CHAT, ['max_tokens' => $maxTokens, 'temperature' => 0.6]);

if (!$result['ok']) {
    $userMsg = 'Service IA momentanément indisponible';
    if (ENV === 'development') $userMsg .= ' — ' . $result['error'];
    error_log('[chat-send] OpenRouter error: ' . $result['error']);
    jsonResponse(['ok' => false, 'error' => $userMsg], 502);
}

$reply = $result['content'];

// Options à choix rapide proposées par l'IA (question fermée)
$options = [];
if (preg_match('/<OPTIONS>(.*?)<\/OPTIONS>/s', $reply, $m)) {
    $options = array_values(array_filter(array_map('trim', explode('|', $m[1])), fn($o) => $o !== ''));
    $reply = trim(str_replace($m[0], '', $reply));
}

// Avant-goût du rapport premium (mode récapitulatif)
$teaser = null;
if (preg_match('/<TEASER>(.*?)<\/TEASER>/s', $reply, $m)) {
    $teaser = trim($m[1]);
    $reply = trim(str_replace($m[0], '', $reply));
}

$stmt = $pdo->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, "assistant", ?)');
$stmt->execute([$conversationId, $reply]);
$messageId = (int)$pdo->lastInsertId();

jsonResponse([
    'ok'              => true,
    'reply'           => $reply,
    'conversation_id' => $conversationId,
    'message_id'      => $messageId,
    'is_limited'      => $reachedFreeLimit,
    'options'         => $options,
    'teaser'          => $teaser,
]);
