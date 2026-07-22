<?php
/**
 * CRON HEBDO — Offres d'emploi via IA + web search.
 * hPanel Hostinger > Cron Jobs :  0 6 * * 1  php /home/USER/chemin/cron/fetch_jobs.php
 */
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI uniquement.'); }

require_once __DIR__ . '/../includes/fetchers.php';

$result = fetchJobsFromWeb();

echo "Terminé : {$result['found']} trouvées, {$result['inserted']} insérées.\n";
