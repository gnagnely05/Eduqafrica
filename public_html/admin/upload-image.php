<?php
/** Endpoint JSON — upload d'une image depuis l'éditeur d'article (réutilise la médiathèque). */
require_once __DIR__ . '/../../includes/admin-layout.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Aucune image reçue.']);
    exit;
}

echo json_encode(saveUploadedImage($_FILES['image']));
