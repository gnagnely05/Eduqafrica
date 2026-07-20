<?php
/** Wrapper Moneroo — paiements mobile money / carte. Doc : https://docs.moneroo.io */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function monerooRequest(string $method, string $path, ?array $body = null): array
{
    $ch = curl_init(MONEROO_API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . MONEROO_SECRET_KEY,
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode((string)$response, true) ?? [];
    return ['http' => $httpCode, 'data' => $data];
}

function monerooInitPayment(int $userId, int $amount, string $purpose, string $description, ?int $relatedId = null): array
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return ['ok' => false, 'error' => 'Utilisateur introuvable'];

    $parts = explode(' ', trim($user['name']), 2);
    $firstName = $parts[0] ?: 'Client';
    $lastName  = $parts[1] ?? '-';

    // 1. Transaction locale en pending
    $stmt = $pdo->prepare(
        'INSERT INTO payment_transactions (user_id, amount, currency, purpose, related_id, status)
         VALUES (?, ?, "XOF", ?, ?, "pending")'
    );
    $stmt->execute([$userId, $amount, $purpose, $relatedId]);
    $paymentId = (int)$pdo->lastInsertId();

    // 2. Initialisation côté Moneroo
    $res = monerooRequest('POST', '/payments/initialize', [
        'amount'      => $amount,
        'currency'    => 'XOF',
        'description' => $description,
        'customer'    => [
            'email'      => $user['email'],
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ],
        'return_url'  => SITE_URL . '/payment-callback.php?pid=' . $paymentId,
        'metadata'    => [
            'payment_id' => (string)$paymentId,
            'user_id'    => (string)$userId,
            'purpose'    => $purpose,
        ],
    ]);

    // Moneroo peut renvoyer 200 ou 201 en cas de succès — on regarde surtout le checkout_url
    if (empty($res['data']['data']['checkout_url'])) {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'failed' WHERE id = ?");
        $stmt->execute([$paymentId]);
        $msg = $res['data']['message']
             ?? (isset($res['data']['errors']) ? json_encode($res['data']['errors']) : ('HTTP ' . $res['http']));
        return ['ok' => false, 'error' => 'Moneroo: ' . $msg];
    }

    $monerooId   = $res['data']['data']['id'];
    $checkoutUrl = $res['data']['data']['checkout_url'];

    $stmt = $pdo->prepare(
        'UPDATE payment_transactions SET moneroo_reference = ?, checkout_url = ? WHERE id = ?'
    );
    $stmt->execute([$monerooId, $checkoutUrl, $paymentId]);

    return ['ok' => true, 'checkout_url' => $checkoutUrl, 'payment_id' => $paymentId, 'error' => null];
}

/** Vérifie le statut réel côté Moneroo et active les droits si payé. */
function monerooVerifyAndActivate(int $paymentId): string
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = ?');
    $stmt->execute([$paymentId]);
    $tx = $stmt->fetch();
    if (!$tx) return 'failed';
    if ($tx['status'] === 'success') return 'success'; // idempotence
    if (empty($tx['moneroo_reference'])) return 'failed';

    $res = monerooRequest('GET', '/payments/' . $tx['moneroo_reference']);
    $status = $res['data']['data']['status'] ?? null;

    if (in_array($status, ['success', 'paid'], true)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE payment_transactions SET status = 'success', paid_at = NOW() WHERE id = ? AND status != 'success'"
            );
            $stmt->execute([$paymentId]);
            if ($stmt->rowCount() > 0) {
                activateEntitlement($pdo, $tx);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            return 'pending';
        }
        return 'success';
    }

    if (in_array($status, ['failed', 'cancelled'], true)) {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'failed' WHERE id = ?");
        $stmt->execute([$paymentId]);
        return 'failed';
    }

    return 'pending';
}

function activateEntitlement(PDO $pdo, array $tx): void
{
    $userId    = (int)$tx['user_id'];
    $paymentId = (int)$tx['id'];
    $relatedId = $tx['related_id'] !== null ? (int)$tx['related_id'] : null;

    switch ($tx['purpose']) {
        case 'cv_download':
            $stmt = $pdo->prepare('UPDATE cv_documents SET is_paid = 1, payment_id = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$paymentId, $relatedId, $userId]);
            break;
        case 'chat_subscription_monthly':
            insertSubscription($pdo, $userId, 'chat', $paymentId);
            break;
        case 'bourses_subscription_monthly':
            insertSubscription($pdo, $userId, 'bourses', $paymentId);
            break;
        case 'bundle_subscription_monthly':
            insertSubscription($pdo, $userId, 'bundle', $paymentId);
            break;
        case 'chat_single_unlock':
            $stmt = $pdo->prepare(
                'INSERT INTO single_unlocks (user_id, unlock_type, related_id, payment_id) VALUES (?, "chat_message", ?, ?)'
            );
            $stmt->execute([$userId, $relatedId, $paymentId]);
            break;
        case 'bourses_single_unlock':
            $stmt = $pdo->prepare(
                'INSERT INTO single_unlocks (user_id, unlock_type, related_id, payment_id) VALUES (?, "bourses_view", ?, ?)'
            );
            $stmt->execute([$userId, $relatedId, $paymentId]);
            break;
    }
}

function insertSubscription(PDO $pdo, int $userId, string $planType, int $paymentId): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO subscriptions (user_id, plan_type, payment_id, started_at, expires_at, status)
         VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ' . (int)SUBSCRIPTION_DAYS . ' DAY), "active")'
    );
    $stmt->execute([$userId, $planType, $paymentId]);
}
