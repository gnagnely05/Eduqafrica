<?php
/**
 * Point d'entrée unique des paiements.
 * /payer.php?type=cv&cvid=12 | chat_single&mid=345 | chat_monthly
 * bourses_single&sid=67 | bourses_monthly | bundle
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/moneroo.php';

requireLogin();
$userId = (int)currentUser()['id'];
$type = $_GET['type'] ?? '';

switch ($type) {
    case 'cv':
        $cvId = (int)($_GET['cvid'] ?? 0);
        $stmt = db()->prepare('SELECT id, is_paid FROM cv_documents WHERE id = ? AND user_id = ?');
        $stmt->execute([$cvId, $userId]);
        $cv = $stmt->fetch();
        if (!$cv) { http_response_code(404); exit('CV introuvable.'); }
        if ($cv['is_paid']) { redirect('/cv-preview.php?id=' . $cvId); }
        $init = monerooInitPayment($userId, price('cv_download'), 'cv_download',
            'Téléchargement CV PDF — ' . SITE_NAME, $cvId);
        break;

    case 'chat_single':
        $mid = (int)($_GET['mid'] ?? 0) ?: null;
        $init = monerooInitPayment($userId, price('chat_single'), 'chat_single_unlock',
            'Réponse approfondie orientation — ' . SITE_NAME, $mid);
        break;

    case 'chat_monthly':
        $init = monerooInitPayment($userId, price('chat_monthly'), 'chat_subscription_monthly',
            'Abonnement orientation IA 1 mois — ' . SITE_NAME);
        break;

    case 'bourses_single':
        $sid = (int)($_GET['sid'] ?? 0) ?: null;
        $init = monerooInitPayment($userId, price('bourses_single'), 'bourses_single_unlock',
            'Accès bourse premium — ' . SITE_NAME, $sid);
        break;

    case 'bourses_monthly':
        $init = monerooInitPayment($userId, price('bourses_monthly'), 'bourses_subscription_monthly',
            'Abonnement bourses premium 1 mois — ' . SITE_NAME);
        break;

    case 'bundle':
        $init = monerooInitPayment($userId, price('bundle_monthly'), 'bundle_subscription_monthly',
            'Abonnement complet (orientation + bourses) 1 mois — ' . SITE_NAME);
        break;

    default:
        http_response_code(400);
        exit('Type de paiement inconnu.');
}

if (!$init['ok']) {
    $pageTitle = 'Paiement indisponible';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container section"><div class="alert alert-error">Le paiement est momentanément indisponible. Réessaie dans quelques minutes ou <a href="/contact.php">contacte-nous</a>.</div>';
    if (ENV === 'development') {
        echo '<pre style="background:#fee; padding:12px; border-radius:6px; margin-top:12px; overflow:auto;">Détails technique (dev) : ' . e($init['error']) . '</pre>';
    }
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

redirect($init['checkout_url']);
