<?php
/**
 * API — Assistant CV conversationnel.
 * Le client envoie tout l'historique { history: [{role, content}] }.
 * L'IA pose des questions ; quand elle a tout, elle répond avec
 * <CV_JSON>{...}</CV_JSON> que ce script détecte pour créer le CV.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/openrouter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Méthode non autorisée'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$history = $body['history'] ?? [];

if (!is_array($history) || !$history) {
    jsonResponse(['ok' => false, 'error' => 'Historique manquant'], 400);
}

// Nettoyer et limiter l'historique (anti-abus)
$history = array_slice($history, -24);
$clean = [];
foreach ($history as $m) {
    $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $content = trim((string)($m['content'] ?? ''));
    if ($content === '' || mb_strlen($content) > 3000) continue;
    $clean[] = ['role' => $role, 'content' => $content];
}
if (!$clean) jsonResponse(['ok' => false, 'error' => 'Historique vide'], 400);

// Rate-limit simple par session : 40 échanges / heure
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['cv_chat_count'] = ($_SESSION['cv_chat_count'] ?? 0) + 1;
if ($_SESSION['cv_chat_count'] > 40) {
    jsonResponse(['ok' => false, 'error' => 'Limite atteinte, réessaie plus tard.'], 429);
}

$systemPrompt = <<<PROMPT
Tu es un assistant de création de CV pour jeunes d'Afrique francophone (étudiants, jeunes diplômés). Ton objectif : collecter les informations nécessaires par une conversation naturelle et courte, puis générer le CV.

DÉROULÉ :
1. Tu poses UNE question à la fois, courte et claire. Tutoie, sois chaleureux.
2. Informations à collecter dans cet ordre : poste/domaine visé + ville → nom complet → contact (email, téléphone) → formation (diplômes, écoles, années) → expériences/stages (même petits jobs, bénévolat, projets) → compétences → langues.
3. Si l'utilisateur n'a pas d'expérience, aide-le à valoriser projets scolaires, associatif, petits boulots.
4. AMÉLIORE ses formulations : transforme "j'ai vendu au marché" en "Vente directe et gestion de caisse". Le résumé/profil, tu le RÉDIGES toi-même à partir de ce qu'il dit.
5. Quand tu as l'essentiel (nom, contact, poste visé, au moins une formation), propose : "Je peux générer ton CV maintenant, ou tu veux ajouter autre chose ?"
6. Quand l'utilisateur confirme la génération (ou dit "génère", "c'est bon", "vas-y"), réponds UNIQUEMENT avec le bloc suivant, sans AUCUN texte autour :

<CV_JSON>{"full_name":"...","headline":"poste visé","email":"...","phone":"...","location":"Ville, Pays","linkedin":"","summary":"2-3 phrases percutantes que TU rédiges","education":[{"degree":"...","school":"...","years":"...","city":"..."}],"experience":[{"title":"...","company":"...","period":"...","city":"...","desc":"missions reformulées, une par ligne"}],"skills":["...","..."],"languages":"Français (courant), ..."}</CV_JSON>

RÈGLES : n'invente JAMAIS de diplôme, d'entreprise ou de date que l'utilisateur n'a pas donnés. Les champs inconnus restent vides ("" ou []).
PROMPT;

$messages = array_merge([['role' => 'system', 'content' => $systemPrompt]], $clean);

$result = openrouterChat($messages, OPENROUTER_MODEL_CHAT, ['max_tokens' => 1600, 'temperature' => 0.5]);

if (!$result['ok']) {
    error_log('[cv-chat] OpenRouter error: ' . $result['error']);
    $msg = 'Service IA momentanément indisponible';
    if (ENV === 'development') $msg .= ' — ' . $result['error'];
    jsonResponse(['ok' => false, 'error' => $msg], 502);
}

$reply = $result['content'];

// Détection du CV généré
if (preg_match('/<CV_JSON>(.*?)<\/CV_JSON>/s', $reply, $m)) {
    $data = json_decode(trim($m[1]), true);
    if (is_array($data) && !empty($data['full_name'])) {
        // Normaliser les champs attendus
        $data += ['headline'=>'','email'=>'','phone'=>'','location'=>'','linkedin'=>'',
                  'summary'=>'','education'=>[],'experience'=>[],'skills'=>[],'languages'=>''];

        $user = currentUser();
        $stmt = db()->prepare(
            'INSERT INTO cv_documents (user_id, template_id, full_name, data_json) VALUES (?, 1, ?, ?)'
        );
        $stmt->execute([
            $user ? (int)$user['id'] : null,
            mb_substr($data['full_name'], 0, 120),
            json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
        $cvId = (int)db()->lastInsertId();
        $_SESSION['own_cvs'][] = $cvId;

        jsonResponse(['ok' => true, 'cv_ready' => true, 'redirect' => '/cv-preview.php?id=' . $cvId]);
    }
    // JSON invalide → demander à l'IA de réessayer côté client
    jsonResponse(['ok' => true, 'cv_ready' => false,
        'reply' => "J'ai eu un souci en générant le CV. Dis-moi « génère » et je réessaie !"]);
}

jsonResponse(['ok' => true, 'cv_ready' => false, 'reply' => $reply]);
