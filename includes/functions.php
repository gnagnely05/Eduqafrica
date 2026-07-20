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
