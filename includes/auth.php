<?php
/** Authentification + vérification des droits d'accès premium. */
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, email, country, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function isAdmin(): bool
{
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $back = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?back=' . $back);
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('Accès refusé.');
    }
}

function loginUser(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        return true;
    }
    return false;
}

function registerUser(string $name, string $email, string $password, ?string $country = null): ?int
{
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) return null;

    $stmt = db()->prepare(
        'INSERT INTO users (name, email, password_hash, country) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $country]);
    return (int)db()->lastInsertId();
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}

// ---- Accès premium ----

function hasActiveSubscription(int $userId, string $feature): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM subscriptions
         WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
         AND plan_type IN (?, 'bundle')"
    );
    $stmt->execute([$userId, $feature]);
    return (int)$stmt->fetchColumn() > 0;
}

function hasSingleUnlock(int $userId, string $unlockType, ?int $relatedId = null): bool
{
    if ($relatedId !== null) {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM single_unlocks
             WHERE user_id = ? AND unlock_type = ? AND related_id = ?'
        );
        $stmt->execute([$userId, $unlockType, $relatedId]);
    } else {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM single_unlocks
             WHERE user_id = ? AND unlock_type = ? AND related_id IS NULL'
        );
        $stmt->execute([$userId, $unlockType]);
    }
    return (int)$stmt->fetchColumn() > 0;
}

function hasPremiumAccess(int $userId, string $feature, ?int $relatedId = null): bool
{
    $unlockType = $feature === 'chat' ? 'chat_message' : 'bourses_view';
    return hasActiveSubscription($userId, $feature)
        || hasSingleUnlock($userId, $unlockType, $relatedId);
}
