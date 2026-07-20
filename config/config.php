<?php
/**
 * Configuration générale — Plateforme d'orientation
 */

// ---- Site ----
define('SITE_NAME', 'eduqAfrica');
define('SITE_URL', 'https://tondomaine.ci');       // sans slash final
define('SITE_EMAIL', 'contact@tondomaine.ci');

// ---- Environnement ----
define('ENV', 'production'); // 'development' ou 'production'
if (ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ---- Base de données (Hostinger hPanel > Bases de données) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXXXXX_orientation');
define('DB_USER', 'uXXXXXXXX_admin');
define('DB_PASS', 'CHANGE_ME');

// ---- OpenRouter ----
define('OPENROUTER_API_KEY', 'CHANGE_ME');         // https://openrouter.ai/keys
define('OPENROUTER_MODEL_CHAT', 'anthropic/claude-3.5-haiku');
define('OPENROUTER_MODEL_SEARCH', 'perplexity/sonar');
// Astuce : suffixe ":online" pour activer la recherche web sur n'importe quel modèle.

// ---- Moneroo ----
define('MONEROO_SECRET_KEY', 'CHANGE_ME');
define('MONEROO_API_URL', 'https://api.moneroo.io/v1');

// ---- Tarifs (XOF) ----
define('PRICE_CV_DOWNLOAD', 1500);
define('PRICE_CHAT_SINGLE', 500);
define('PRICE_CHAT_MONTHLY', 500);
define('PRICE_BOURSES_SINGLE', 500);
define('PRICE_BOURSES_MONTHLY', 500);
define('PRICE_BUNDLE_MONTHLY', 1000);

// ---- Divers ----
define('SUBSCRIPTION_DAYS', 30);
date_default_timezone_set('Africa/Abidjan');
