<?php
/** Wrapper OpenRouter — appels IA centralisés. */
require_once __DIR__ . '/../config/config.php';

function openrouterChat(array $messages, string $model = OPENROUTER_MODEL_CHAT, array $options = []): array
{
    $payload = array_merge([
        'model'    => $model,
        'messages' => $messages,
        'max_tokens' => $options['max_tokens'] ?? 1024,
    ], $options);

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENROUTER_API_KEY,
            'HTTP-Referer: ' . SITE_URL,
            'X-Title: ' . SITE_NAME,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'content' => null, 'error' => 'cURL: ' . $curlErr, 'usage' => null];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $err = $data['error']['message'] ?? ('HTTP ' . $httpCode);
        return ['ok' => false, 'content' => null, 'error' => $err, 'usage' => null];
    }

    return [
        'ok'      => true,
        'content' => $data['choices'][0]['message']['content'],
        'error'   => null,
        'usage'   => $data['usage'] ?? null,
    ];
}

function openrouterSearchJson(string $systemPrompt, string $userPrompt, ?string &$error = null): ?array
{
    $result = openrouterChat(
        [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        OPENROUTER_MODEL_SEARCH,
        ['max_tokens' => 4096, 'temperature' => 0.2]
    );

    if (!$result['ok']) {
        $error = $result['error'];
        return null;
    }

    // Nettoyage : certains modèles entourent le JSON de ```json ... ```
    $raw = trim($result['content']);
    $raw = preg_replace('/^```(json)?\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);

    // Extraire le premier bloc JSON (objet ou tableau)
    $start = strcspn($raw, '[{');
    $raw = substr($raw, $start);

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $error = 'JSON invalide retourné par le modèle';
        return null;
    }
    return $decoded;
}
