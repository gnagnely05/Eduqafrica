<?php
/**
 * Recherche IA (via OpenRouter + web search) d'offres d'emploi et de bourses.
 * Utilisé à la fois par les crons (cron/fetch_*.php) et par les boutons de
 * déclenchement manuel dans l'admin (public_html/admin/run-fetch.php).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/openrouter.php';
require_once __DIR__ . '/functions.php';

function fetchValidDate(?string $d): ?string
{
    if (!$d) return null;
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : null;
}

function fetchLog(PDO $pdo, string $type, string $query, int $found, int $inserted, string $status, ?string $err): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO ai_fetch_logs (fetch_type, query_used, items_found, items_inserted, status, error_message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$type, mb_substr($query, 0, 500), $found, $inserted, $status, $err]);
}

/** Recherche et insère des offres d'emploi. Retourne ['found' => int, 'inserted' => int]. */
function fetchJobsFromWeb(): array
{
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
            fetchLog($pdo, 'jobs', $query, 0, 0, 'error', $error);
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
                    fetchValidDate($item['deadline'] ?? null),
                    fetchValidDate($item['posted_at'] ?? null),
                    $hash,
                ]);
                $inserted++;
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    fetchLog($pdo, 'jobs', $query, $found, $inserted, 'error', $e->getMessage());
                }
            }
        }

        $totalFound += $found;
        $totalInserted += $inserted;
        fetchLog($pdo, 'jobs', $query, $found, $inserted, 'success', null);
        sleep(3);
    }

    $pdo->exec("UPDATE jobs SET is_active = 0 WHERE deadline IS NOT NULL AND deadline < CURDATE()");
    $pdo->exec("UPDATE jobs SET is_active = 0 WHERE deadline IS NULL AND fetched_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");

    return ['found' => $totalFound, 'inserted' => $totalInserted];
}

/** Recherche et insère des bourses d'études. Retourne ['found' => int, 'inserted' => int]. */
function fetchScholarshipsFromWeb(): array
{
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
            fetchLog($pdo, 'scholarships', $query, 0, 0, 'error', $error);
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
                    fetchValidDate($item['deadline'] ?? null),
                    !empty($item['is_premium']) ? 1 : 0,
                    $hash,
                ]);
                $inserted++;
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    fetchLog($pdo, 'scholarships', $query, $found, $inserted, 'error', $e->getMessage());
                }
            }
        }

        $totalFound += $found;
        $totalInserted += $inserted;
        fetchLog($pdo, 'scholarships', $query, $found, $inserted, 'success', null);
        sleep(3);
    }

    $pdo->exec("UPDATE scholarships SET is_active = 0 WHERE deadline IS NOT NULL AND deadline < CURDATE()");
    $pdo->exec("UPDATE scholarships SET is_active = 0 WHERE deadline IS NULL AND fetched_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");

    return ['found' => $totalFound, 'inserted' => $totalInserted];
}
