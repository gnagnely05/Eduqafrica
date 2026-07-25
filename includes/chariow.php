<?php
/**
 * Intégration Chariow — paiements CV (1500 F) et abonnement Orientation+Bourses (1000 F/mois).
 * Checkout via widget client (js.chariowcdn.com), confirmation via webhook "Pulse".
 * Doc : https://chariow.dev
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/moneroo.php'; // pour insertSubscription()

/** Retourne le HTML du bouton d'achat Chariow pour un produit donné. */
function chariowWidgetHtml(string $productId): string
{
    $pid = e($productId);
    $storeDomain = e(CHARIOW_STORE_DOMAIN);
    return <<<HTML
<div id="chariow-widget" data-product-id="{$pid}"
    data-store-domain="{$storeDomain}"
    data-style="tap" data-border-style="rounded" data-cta-width="xs"
    data-background-color="#FFFFFF" data-cta-animation="shine"
    data-locale="fr" data-primary-color="#ffcc00"></div>
<script>
(function() {
  var script = document.createElement('script');
  script.src = 'https://js.chariowcdn.com/v1/widget.min.js';
  script.async = true;
  document.head.appendChild(script);
  var link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = 'https://js.chariowcdn.com/v1/widget.min.css';
  document.head.appendChild(link);
})();
</script>
HTML;
}

/** Crée une transaction locale "pending" avant d'afficher le widget de paiement. */
function chariowCreatePendingTx(int $userId, int $amount, string $purpose, ?int $relatedId = null): int
{
    $stmt = db()->prepare(
        "INSERT INTO payment_transactions (user_id, amount, currency, provider, purpose, related_id, status)
         VALUES (?, ?, 'XOF', 'chariow', ?, ?, 'pending')"
    );
    $stmt->execute([$userId, $amount, $purpose, $relatedId]);
    return (int)db()->lastInsertId();
}

/**
 * Traite un événement Pulse (webhook Chariow). Chariow ne signe pas ses webhooks
 * (voir doc "Pulses") : la sécurité repose sur l'idempotence (chariow_sale_id unique)
 * et la vérification que le produit correspond bien à un de nos deux produits connus.
 */
function chariowHandlePulse(array $payload): void
{
    if (($payload['event'] ?? '') !== 'successful.sale') {
        return; // on ignore abandoned.sale / failed.sale / license.* / affiliate.*
    }

    $sale = $payload['sale'] ?? null;
    $product = $payload['product'] ?? null;
    $customer = $payload['customer'] ?? null;
    if (!is_array($sale) || !is_array($product) || !is_array($customer)
        || empty($sale['id']) || empty($product['id'])) {
        return;
    }

    $saleId = (string)$sale['id'];
    $productId = (string)$product['id'];
    $email = trim((string)($customer['email'] ?? ''));
    $amount = (int)round((float)($sale['amount']['value'] ?? 0));

    $purpose = match ($productId) {
        CHARIOW_PRODUCT_CV => 'cv_download',
        CHARIOW_PRODUCT_ORIENTATION => 'orientation_bourses_monthly',
        default => null,
    };
    if ($purpose === null) return; // produit inconnu de notre boutique — on ignore

    $pdo = db();

    // Idempotence : ce paiement a-t-il déjà été traité ? (Chariow peut renvoyer le même Pulse plusieurs fois)
    $stmt = $pdo->prepare('SELECT id FROM payment_transactions WHERE chariow_sale_id = ?');
    $stmt->execute([$saleId]);
    if ($stmt->fetch()) return;

    $userId = null;
    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        $userId = $row ? (int)$row['id'] : null;
    }

    $pdo->beginTransaction();
    try {
        $paymentId = null;
        $relatedId = null;

        if ($purpose === 'cv_download' && $userId) {
            // Rattache au paiement CV en attente le plus récent de cet utilisateur
            $stmt = $pdo->prepare(
                "SELECT id, related_id FROM payment_transactions
                 WHERE user_id = ? AND purpose = 'cv_download' AND provider = 'chariow' AND status = 'pending'
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$userId]);
            $pending = $stmt->fetch();
            if ($pending) {
                $paymentId = (int)$pending['id'];
                $relatedId = $pending['related_id'] !== null ? (int)$pending['related_id'] : null;
                $stmt = $pdo->prepare(
                    "UPDATE payment_transactions SET status = 'success', paid_at = NOW(), chariow_sale_id = ?, amount = ? WHERE id = ?"
                );
                $stmt->execute([$saleId, $amount, $paymentId]);
            }
        }

        if ($paymentId === null) {
            $stmt = $pdo->prepare(
                "INSERT INTO payment_transactions (user_id, amount, currency, provider, purpose, related_id, chariow_sale_id, status, paid_at)
                 VALUES (?, ?, 'XOF', 'chariow', ?, ?, ?, 'success', NOW())"
            );
            $stmt->execute([$userId, $amount, $purpose, $relatedId, $saleId]);
            $paymentId = (int)$pdo->lastInsertId();
        }

        if ($userId) {
            if ($purpose === 'cv_download' && $relatedId) {
                $stmt = $pdo->prepare('UPDATE cv_documents SET is_paid = 1, payment_id = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$paymentId, $relatedId, $userId]);
            } elseif ($purpose === 'orientation_bourses_monthly') {
                insertSubscription($pdo, $userId, 'bundle', $paymentId);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('chariowHandlePulse: ' . $e->getMessage());
    }
}
