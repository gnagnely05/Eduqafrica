<?php
/** Webhook Chariow ("Pulse") — confirmation de paiement. À enregistrer dans
 * app.chariow.com > Automatisation > Pulses > Add Pulse, sur l'événement "Successful Sale". */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/chariow.php';

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);

// Chariow attend une réponse 2xx rapide, sous peine de nouvelles tentatives (voir doc "Retry Policy").
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

if (is_array($payload)) {
    chariowHandlePulse($payload);
}
