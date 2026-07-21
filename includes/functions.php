<?php
/** Fonctions utilitaires communes. */

function e(?string $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item-' . time();
}

function uniqueSlug(PDO $pdo, string $table, string $baseSlug): string
{
    $slug = $baseSlug;
    $i = 2;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
        $slug = $baseSlug . '-' . $i++;
    }
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function dateFr(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    if (!$ts) return '—';
    $mois = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
             'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/** Lit un réglage depuis la table settings (cache statique). */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = db()->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $e) {
            $cache = []; // table absente → fallback constantes
        }
    }
    return (array_key_exists($key, $cache) && $cache[$key] !== '' && $cache[$key] !== null)
        ? $cache[$key] : $default;
}

/** Tarif dynamique (modifiable en admin), fallback sur les constantes. */
function price(string $name): int
{
    $fallback = [
        'cv_download'     => PRICE_CV_DOWNLOAD,
        'chat_single'     => PRICE_CHAT_SINGLE,
        'chat_monthly'    => PRICE_CHAT_MONTHLY,
        'bourses_single'  => PRICE_BOURSES_SINGLE,
        'bourses_monthly' => PRICE_BOURSES_MONTHLY,
        'bundle_monthly'  => PRICE_BUNDLE_MONTHLY,
    ];
    return (int) setting('price_' . $name, (string)($fallback[$name] ?? 0));
}

/** Formate un montant XOF : 1 500 F */
function xof(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' F';
}

// ---- Médiathèque (images uploadées depuis l'admin) ----

define('UPLOAD_DIR_PATH', __DIR__ . '/../public_html/assets/uploads');
define('UPLOAD_URL_PATH', '/assets/uploads');
define('UPLOAD_MAX_SIZE', 8 * 1024 * 1024); // 8 Mo

/** Valide et enregistre une image uploadée. Retourne ['ok', 'url', 'error']. */
function saveUploadedImage(array $file): array
{
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!is_dir(UPLOAD_DIR_PATH)) {
        mkdir(UPLOAD_DIR_PATH, 0755, true);
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'url' => null, 'error' => "Erreur d'upload (code {$file['error']})."];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'url' => null, 'error' => 'Format non autorisé. Utilise : ' . implode(', ', $allowedExt) . '.'];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'url' => null, 'error' => 'Image trop lourde (8 Mo max).'];
    }
    if (!@getimagesize($file['tmp_name'])) {
        return ['ok' => false, 'url' => null, 'error' => "Ce fichier n'est pas une image valide."];
    }

    $baseName = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = $baseName . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR_PATH . '/' . $filename)) {
        return ['ok' => false, 'url' => null, 'error' => "Impossible d'enregistrer l'image sur le serveur."];
    }

    return ['ok' => true, 'url' => UPLOAD_URL_PATH . '/' . $filename, 'error' => null];
}

/** Liste les images de la médiathèque, plus récentes en premier. */
function listUploadedImages(): array
{
    if (!is_dir(UPLOAD_DIR_PATH)) {
        return [];
    }
    $images = [];
    foreach (glob(UPLOAD_DIR_PATH . '/*') as $path) {
        if (is_file($path)) {
            $images[] = [
                'name' => basename($path),
                'url'  => UPLOAD_URL_PATH . '/' . basename($path),
                'time' => filemtime($path),
            ];
        }
    }
    usort($images, fn($a, $b) => $b['time'] - $a['time']);
    return $images;
}

/** Supprime une image de la médiathèque par nom de fichier (protégé contre le path traversal). */
function deleteUploadedImage(string $filename): bool
{
    $target = basename($filename);
    $path = UPLOAD_DIR_PATH . '/' . $target;
    if ($target === '' || !is_file($path) || dirname(realpath($path)) !== realpath(UPLOAD_DIR_PATH)) {
        return false;
    }
    return unlink($path);
}
