<?php
/**
 * CRON HEBDO — Offres d'emploi via IA + web search.
 * hPanel Hostinger > Cron Jobs :  0 6 * * 1  php /home/USER/chemin/cron/fetch_jobs.php
 */
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI uniquement.'); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/openrouter.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

$searchQueries = [
    "offres d'emploi et stages récents pour jeunes diplômés en Côte d'Ivoire (Abidjan et autres villes), publiées cette semaine ou ce mois",
    "offres d'emploi récentes pour jeunes diplômés au Sénégal, Burkina Faso, Bénin, Mali, publiées récemment",
    "offres de stage et premier emploi en Afrique francophone pour étudiants, publiées récemment",
];

$systemPrompt = <<<PROMPT
Tu es un agent de collecte d'offres d'emploi. Tu cherches sur le web des offres d'emploi et de stage RÉELLES et RÉCENTES (moins de 30 jours) destinées aux jeunes diplômés et étudiants d'Afrique francophone.

RÈGLES STRICTES :
- Uniquement des offres réelles trouvées sur le web, avec leur URL source exacte. N'invente JAMAIS d'offre ni d'URL.
- Si tu n'es pas sûr qu'une offre soit réelle et actuelle, ne l'inclus pas.
- Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ou après, sans balises markdown.

Format de chaque élément :
{
  "title": "Intitulé du poste",
  "company": "Nom de l'entreprise ou null",
  "description": "Résumé de l'offre en 2-3 phrases (missions, profil recherché)",
  "location": "Ville ou null",
  "country": "Pays",
  "contract_type": "CDI | CDD | Stage | Freelance | Alternance | null",
  "category": "Domaine (Informatique, Finance, Commercial, ...) ou null",
  "source_url": "URL exacte de l'offre",
  "deadline": "YYYY-MM-DD ou null",
  "posted_at": "YYYY-MM-DD ou null"
}
PROMPT;

$totalFound = 0;
$totalInserted = 0;

foreach ($searchQueries as $query) {
    $error = null;
    $items = openrouterSearchJson($systemPrompt, $query, $error);

    if ($items === null) {
        logFetch($pdo, 'jobs', $query, 0, 0, 'error', $error);
        continue;
    }

    $found = count($items);
    $inserted = 0;

    foreach ($items as $item) {
        if (empty($item['title']) || empty($item['source_url']) || empty($item['description'])) continue;
        if (!filter_var($item['source_url'], FILTER_VALIDATE_URL)) continue;

        $hash = hash('sha256', mb_strtolower(
            ($item['title'] ?? '') . '|' . ($item['company'] ?? '') . '|' . $item['source_url']
        ));

        try {
            $slug = uniqueSlug($pdo, 'jobs', slugify($item['title'] . '-' . ($item['company'] ?? '')));
            $stmt = $pdo->prepare(
                'INSERT INTO jobs (title, slug, company, description, location, country, contract_type,
                                   category, source_url, source_domain, deadline, posted_at, content_hash)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                mb_substr($item['title'], 0, 255),
                $slug,
                $item['company'] ?? null,
                $item['description'],
                $item['location'] ?? null,
                $item['country'] ?? null,
                $item['contract_type'] ?? null,
                $item['category'] ?? null,
                mb_substr($item['source_url'], 0, 500),
                parse_url($item['source_url'], PHP_URL_HOST),
                validDate($item['deadline'] ?? null),
                validDate($item['posted_at'] ?? null),
                $hash,
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                logFetch($pdo, 'jobs', $query, $found, $inserted, 'error', $e->getMessage());
            }
        }
    }

    $totalFound += $found;
    $totalInserted += $inserted;
    logFetch($pdo, 'jobs', $query, $found, $inserted, 'success', null);
    sleep(3);
}

$pdo->exec("UPDATE jobs SET is_active = 0 WHERE deadline IS NOT NULL AND deadline < CURDATE()");
$pdo->exec("UPDATE jobs SET is_active = 0 WHERE deadline IS NULL AND fetched_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");

echo "Terminé : $totalFound trouvées, $totalInserted insérées.\n";

function validDate(?string $d): ?string
{
    if (!$d) return null;
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : null;
}

function logFetch(PDO $pdo, string $type, string $query, int $found, int $inserted, string $status, ?string $err): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO ai_fetch_logs (fetch_type, query_used, items_found, items_inserted, status, error_message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$type, mb_substr($query, 0, 500), $found, $inserted, $status, $err]);
}
