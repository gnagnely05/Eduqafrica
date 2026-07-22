<?php
/** Déclenche manuellement une recherche IA (emplois ou bourses) depuis l'admin. */
require_once __DIR__ . '/../../includes/admin-layout.php';
require_once __DIR__ . '/../../includes/fetchers.php';

set_time_limit(180);

$type = $_GET['type'] ?? '';
if (!csrfCheck($_GET['csrf'] ?? null) || !in_array($type, ['jobs', 'scholarships'], true)) {
    redirect('/admin/index.php');
}

$result = $type === 'jobs' ? fetchJobsFromWeb() : fetchScholarshipsFromWeb();
$target = $type === 'jobs' ? '/admin/jobs.php' : '/admin/bourses.php';

redirect($target . '?fetched=1&found=' . $result['found'] . '&inserted=' . $result['inserted']);
