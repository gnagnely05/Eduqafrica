<?php
/**
 * CRON HEBDO — Bourses via IA + web search.
 * hPanel :  30 6 * * 1  php /home/USER/chemin/cron/fetch_scholarships.php
 * is_premium=1 → bourses 100% financées/prestigieuses (abonnés) ; 0 → visibles par tous.
 */
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI uniquement.'); }

require_once __DIR__ . '/../includes/fetchers.php';

$result = fetchScholarshipsFromWeb();

echo "Terminé : {$result['found']} trouvées, {$result['inserted']} insérées.\n";
