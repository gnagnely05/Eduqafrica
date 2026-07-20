<?php
/** Sauvegarde le CV en base (gratuit) puis redirige vers l'aperçu. */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfCheck($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide.');
}

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$templateId = (int)($_POST['template_id'] ?? 0);

if ($fullName === '' || $email === '' || !$templateId) {
    http_response_code(400);
    exit('Champs obligatoires manquants.');
}

$education = [];
foreach (($_POST['edu_degree'] ?? []) as $i => $degree) {
    if (trim($degree) === '') continue;
    $education[] = [
        'degree' => trim($degree),
        'school' => trim($_POST['edu_school'][$i] ?? ''),
        'years'  => trim($_POST['edu_years'][$i] ?? ''),
        'city'   => trim($_POST['edu_city'][$i] ?? ''),
    ];
}

$experience = [];
foreach (($_POST['exp_title'] ?? []) as $i => $title) {
    if (trim($title) === '') continue;
    $experience[] = [
        'title'   => trim($title),
        'company' => trim($_POST['exp_company'][$i] ?? ''),
        'period'  => trim($_POST['exp_period'][$i] ?? ''),
        'city'    => trim($_POST['exp_city'][$i] ?? ''),
        'desc'    => trim($_POST['exp_desc'][$i] ?? ''),
    ];
}

$data = [
    'full_name' => $fullName,
    'headline'  => trim($_POST['headline'] ?? ''),
    'email'     => $email,
    'phone'     => trim($_POST['phone'] ?? ''),
    'location'  => trim($_POST['location'] ?? ''),
    'linkedin'  => trim($_POST['linkedin'] ?? ''),
    'summary'   => trim($_POST['summary'] ?? ''),
    'education' => $education,
    'experience'=> $experience,
    'skills'    => array_values(array_filter(array_map('trim', explode(',', $_POST['skills'] ?? '')))),
    'languages' => trim($_POST['languages'] ?? ''),
];

$user = currentUser();
$stmt = db()->prepare(
    'INSERT INTO cv_documents (user_id, template_id, full_name, data_json) VALUES (?, ?, ?, ?)'
);
$stmt->execute([
    $user ? (int)$user['id'] : null,
    $templateId,
    $fullName,
    json_encode($data, JSON_UNESCAPED_UNICODE),
]);
$cvId = (int)db()->lastInsertId();

// Sécurise l'accès à l'aperçu pour les visiteurs anonymes
$_SESSION['own_cvs'][] = $cvId;

redirect('/cv-preview.php?id=' . $cvId);
