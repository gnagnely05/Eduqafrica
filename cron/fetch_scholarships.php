<?php
/**
 * CRON HEBDO — Bourses via IA + web search.
 * hPanel :  30 6 * * 1  php /home/USER/chemin/cron/fetch_scholarships.php
 * is_premium=1 → bourses 100% financées/prestigieuses (abonnés) ; 0 → visibles par tous.
 */
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI uniquement.'); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/openrouter.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

$searchQueries = [
    "bourses d'études actuellement ouvertes pour étudiants africains francophones (licence, master, doctorat), avec dates limites à venir",
    "bourses d'études 100% financées (fully funded) ouvertes aux étudiants de Côte d'Ivoire et d'Afrique de l'Ouest, candidatures ouvertes",
    "bourses gouvernementales et universitaires pour étudiants africains : France, Canada, Maroc, Turquie, Chine, avec deadlines à venir",
];

$systemPrompt = <<<PROMPT
Tu es un agent de collecte de bourses d'études. Tu cherches sur le web des bourses RÉELLES dont les candidatures sont OUVERTES ou à venir, destinées aux étudiants d'Afrique francophone.

RÈGLES STRICTES :
- Uniquement des bourses réelles trouvées sur le web, avec leur URL source officielle. N'invente JAMAIS de bourse ni d'URL.
- Si tu n'es pas sûr qu'une bourse soit réelle et ouverte, ne l'inclus pas.
- Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ou après, sans balises markdown.

Classification "is_premium" :
- true : bourse 100% financée (scolarité + logement + billet + allocation), ou bourse prestigieuse (Chevening, Eiffel, DAAD, Erasmus Mundus, Fulbright, bourses gouvernementales complètes...)
- false : bourse partielle, réduction de frais, bourse courante

Format de chaque élément :
{
  "title": "Nom de la bourse",
  "organization": "Organisme ou null",
  "description": "Résumé en 2-3 phrases (ce qui est couvert, qui peut candidater)",
  "study_level": "Licence | Master | Doctorat | Tous niveaux | null",
  "field": "Domaine d'études ou 'Tous domaines'",
  "country": "Pays d'origine éligibles",
  "destination_country": "Pays d'accueil",
  "source_url": "URL officielle",
  "deadline": "YYYY-MM-DD ou null",
  "is_premium": true/false
}
PROMPT;

$totalFound = 0;
$totalInserted = 0;

foreach ($searchQueries as $query) {
    $error = null;
    $items = openrouterSearchJson($systemPrompt, $query, $error);

    if ($items === null) {
        logFetchS($pdo, $query, 0, 0, 'error', $error);
        continue;
    }

    $found = count($items);
    $inserted = 0;

    foreach ($items as $item) {
        if (empty($item['title']) || empty($item['source_url']) || empty($item['description'])) continue;
        if (!filter_var($item['source_url'], FILTER_VALIDATE_URL)) continue;

        $hash = hash('sha256', mb_strtolower(
            ($item['title'] ?? '') . '|' . ($item['organization'] ?? '') . '|' . $item['source_url']
        ));

        try {
            $slug = uniqueSlug($pdo, 'scholarships', slugify($item['title']));
            $stmt = $pdo->prepare(
                'INSERT INTO scholarships (title, slug, organization, description, study_level, field,
                                           country, destination_country, source_url, source_domain,
                                           deadline, is_premium, content_hash)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                mb_substr($item['title'], 0, 255),
                $slug,
                $item['organization'] ?? null,
                $item['description'],
                $item['study_level'] ?? null,
                $item['field'] ?? null,
                $item['country'] ?? null,
                $item['destination_country'] ?? null,
                mb_substr($item['source_url'], 0, 500),
                parse_url($item['source_url'], PHP_URL_HOST),
                validDateS($item['deadline'] ?? null),
                !empty($item['is_premium']) ? 1 : 0,
                $hash,
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                logFetchS($pdo, $query, $found, $inserted, 'error', $e->getMessage());
            }
        }
    }

    $totalFound += $found;
    $totalInserted += $inserted;
    logFetchS($pdo, $query, $found, $inserted, 'success', null);
    sleep(3);
}

$pdo->exec("UPDATE scholarships SET is_active = 0 WHERE deadline IS NOT NULL AND deadline < CURDATE()");
$pdo->exec("UPDATE scholarships SET is_active = 0 WHERE deadline IS NULL AND fetched_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");

echo "Terminé : $totalFound trouvées, $totalInserted insérées.\n";

function validDateS(?string $d): ?string
{
    if (!$d) return null;
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : null;
}

function logFetchS(PDO $pdo, string $query, int $found, int $inserted, string $status, ?string $err): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO ai_fetch_logs (fetch_type, query_used, items_found, items_inserted, status, error_message)
         VALUES ("scholarships", ?, ?, ?, ?, ?)'
    );
    $stmt->execute([mb_substr($query, 0, 500), $found, $inserted, $status, $err]);
}
