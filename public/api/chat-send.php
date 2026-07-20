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

// ---- Prompt système selon le niveau d'accès ----
$countryHint = $user['country'] ?? null;

$systemBase = "Tu es un conseiller d'orientation scolaire et professionnelle pour les élèves et étudiants d'Afrique francophone (Côte d'Ivoire, Sénégal, Burkina Faso, Cameroun, Bénin, Mali, etc.). "
    . "Tu connais les systèmes éducatifs locaux : BAC séries A/C/D, BTS, licences LMD, grandes écoles (INP-HB, ENSEA...), concours de la fonction publique, formations professionnelles. "
    . "Tu réponds en français, avec un ton chaleureux et encourageant, adapté à un jeune de 15-25 ans. "
    . ($countryHint ? "L'utilisateur est basé en/au $countryHint. " : "");

if ($isPremium) {
    $systemPrompt = $systemBase
        . "MODE APPROFONDI : donne une réponse complète et personnalisée. Structure ta réponse avec : "
        . "1) une analyse de la situation, 2) des pistes concrètes (filières, écoles nommées, concours avec périodes d'inscription si tu les connais), "
        . "3) les débouchés et réalités du marché, 4) un plan d'action en étapes, 5) des alternatives ou plans B. "
        . "Sois précis, nuancé, et n'hésite pas à poser une question de suivi si le profil manque de détails.";
    $maxTokens = 1500;
} else {
    $systemPrompt = $systemBase
        . "MODE SIMPLE : donne une réponse COURTE (4-6 phrases maximum) qui répond à la question de façon utile mais générale. "
        . "Ne donne PAS de plan d'action détaillé, ne cite PAS plus de 2-3 pistes, ne détaille PAS les démarches. "
        . "Termine ta réponse naturellement, sans mentionner qu'une version plus complète existe (l'interface s'en charge). "
        . "Reste utile et honnête : la réponse courte doit avoir de la vraie valeur.";
    $maxTokens = 350;
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

$stmt = $pdo->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, "assistant", ?)');
$stmt->execute([$conversationId, $reply]);
$messageId = (int)$pdo->lastInsertId();

jsonResponse([
    'ok'              => true,
    'reply'           => $reply,
    'conversation_id' => $conversationId,
    'message_id'      => $messageId,
    'is_limited'      => !$isPremium,
]);
