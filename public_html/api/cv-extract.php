<?php
/**
 * API — Upload d'un ancien CV (PDF/DOCX/TXT), extraction du texte,
 * restructuration + amélioration par l'IA, création du cv_document.
 *
 * PDF : nécessite smalot/pdfparser →  composer require smalot/pdfparser
 * DOCX : ZipArchive natif. TXT : lecture directe.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/openrouter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['cv_file'])) {
    jsonResponse(['ok' => false, 'error' => 'Aucun fichier reçu'], 400);
}

$file = $_FILES['cv_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'error' => 'Erreur de téléversement (code ' . $file['error'] . ')'], 400);
}
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'error' => 'Fichier trop lourd (5 Mo max)'], 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tmp = $file['tmp_name'];
$text = '';

try {
    if ($ext === 'txt') {
        $text = file_get_contents($tmp);

    } elseif ($ext === 'docx') {
        $zip = new ZipArchive();
        if ($zip->open($tmp) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) throw new Exception('DOCX illisible');
            // Préserver les fins de paragraphes
            $xml = str_replace('</w:p>', "</w:p>\n", $xml);
            $text = trim(strip_tags($xml));
        } else {
            throw new Exception('DOCX illisible');
        }

    } elseif ($ext === 'pdf') {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) throw new Exception('vendor/autoload.php manquant');
        require_once $autoload;
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            jsonResponse(['ok' => false, 'error' =>
                "Lecture PDF non installée sur le serveur. Lance : composer require smalot/pdfparser — ou envoie ton CV en .docx ou .txt."], 500);
        }
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($tmp);
        $text = $pdf->getText();

    } else {
        jsonResponse(['ok' => false, 'error' => 'Format non supporté (PDF, DOCX ou TXT uniquement)'], 400);
    }
} catch (Throwable $ex) {
    error_log('[cv-extract] ' . $ex->getMessage());
    jsonResponse(['ok' => false, 'error' => "Impossible de lire ce fichier. Essaie en .docx ou .txt."], 422);
}

$text = trim(preg_replace('/[ \t]+/', ' ', (string)$text));
if (mb_strlen($text) < 60) {
    jsonResponse(['ok' => false, 'error' => "Le fichier semble vide ou scanné en image. Si c'est un scan, utilise plutôt l'option « Partir de zéro »."], 422);
}
$text = mb_substr($text, 0, 12000);

$targetJob = trim($_POST['target_job'] ?? '');

$systemPrompt = <<<PROMPT
Tu es un expert CV pour jeunes d'Afrique francophone. On te donne le texte brut extrait d'un ancien CV. Ta mission :
1. Extraire toutes les informations réelles (nom, contact, formations, expériences, compétences, langues).
2. AMÉLIORER les formulations : verbes d'action, missions claires, résumé/profil percutant que tu rédiges toi-même.
3. Adapter le CV au poste visé s'il est fourni (réorganiser, mettre en avant le pertinent, ajuster le titre/headline).
4. Ne JAMAIS inventer de diplôme, d'entreprise, de date ou de compétence absents du texte.

Réponds UNIQUEMENT avec ce JSON (aucun texte autour, pas de markdown) :
{"full_name":"...","headline":"titre/poste visé","email":"...","phone":"...","location":"...","linkedin":"","summary":"2-3 phrases rédigées par toi","education":[{"degree":"...","school":"...","years":"...","city":"..."}],"experience":[{"title":"...","company":"...","period":"...","city":"...","desc":"missions améliorées, une par ligne"}],"skills":["..."],"languages":"...","rationale":"2-3 phrases expliquant les points forts de ce CV restructuré et pourquoi ces choix conviennent au poste visé"}
Champs inconnus : "" ou [].
PROMPT;

$userPrompt = ($targetJob !== '' ? "POSTE VISÉ : $targetJob\n\n" : '')
    . "TEXTE DU CV :\n" . $text;

$result = openrouterChat(
    [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt],
    ],
    OPENROUTER_MODEL_CHAT,
    ['max_tokens' => 2000, 'temperature' => 0.3]
);

if (!$result['ok']) {
    error_log('[cv-extract] OpenRouter: ' . $result['error']);
    $msg = 'Service IA momentanément indisponible';
    if (ENV === 'development') $msg .= ' — ' . $result['error'];
    jsonResponse(['ok' => false, 'error' => $msg], 502);
}

// Nettoyage du JSON
$raw = trim($result['content']);
$raw = preg_replace('/^```(json)?\s*/i', '', $raw);
$raw = preg_replace('/\s*```$/', '', $raw);
$start = strcspn($raw, '{');
$raw = substr($raw, $start);
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['full_name'])) {
    jsonResponse(['ok' => false, 'error' => "L'analyse a échoué, réessaie ou utilise l'option « Partir de zéro »."], 422);
}

$data += ['headline'=>'','email'=>'','phone'=>'','location'=>'','linkedin'=>'',
          'summary'=>'','education'=>[],'experience'=>[],'skills'=>[],'languages'=>'','rationale'=>''];

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

jsonResponse(['ok' => true, 'redirect' => '/cv-preview.php?id=' . $cvId]);
