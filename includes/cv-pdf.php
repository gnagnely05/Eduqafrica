<?php
/**
 * Génération PDF du CV via DomPDF.
 * Installation : composer require dompdf/dompdf
 */
require_once __DIR__ . '/../public_html/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateCvPdf(array $cv, array $data): void
{
    $html = renderCvHtml($data);

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'CV-' . preg_replace('/[^a-zA-Z0-9]+/', '-', $data['full_name']) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

function renderCvHtml(array $d): string
{
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    $expHtml = '';
    foreach ($d['experience'] ?? [] as $x) {
        $desc = $x['desc'] ? '<p class="desc">' . nl2br($e($x['desc'])) . '</p>' : '';
        $expHtml .= '<div class="item"><p><strong>' . $e($x['title']) . '</strong> — ' . $e($x['company'])
            . ' <span class="meta">' . $e($x['period']) . ($x['city'] ? ' · ' . $e($x['city']) : '') . '</span></p>'
            . $desc . '</div>';
    }

    $eduHtml = '';
    foreach ($d['education'] ?? [] as $x) {
        $eduHtml .= '<div class="item"><p><strong>' . $e($x['degree']) . '</strong> — ' . $e($x['school'])
            . ' <span class="meta">' . $e($x['years']) . ($x['city'] ? ' · ' . $e($x['city']) : '') . '</span></p></div>';
    }

    $skills = !empty($d['skills']) ? $e(implode(' · ', $d['skills'])) : '';

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; color: #1B2452; margin: 0; }
        .page { padding: 34px 40px; }
        h1 { font-size: 21pt; margin: 0 0 2px; }
        .headline { color: #E76F51; font-weight: bold; margin: 0 0 4px; }
        .contact { color: #555; font-size: 9pt; margin: 0 0 18px; }
        h2 { font-size: 12pt; border-bottom: 2px solid #FFB703; padding-bottom: 3px; margin: 16px 0 8px; }
        .item { margin-bottom: 8px; }
        .item p { margin: 0 0 2px; }
        .meta { color: #777; font-size: 9pt; }
        .desc { font-size: 9.5pt; margin: 2px 0 0; }
    </style></head><body><div class="page">'
        . '<h1>' . $e($d['full_name']) . '</h1>'
        . (!empty($d['headline']) ? '<p class="headline">' . $e($d['headline']) . '</p>' : '')
        . '<p class="contact">' . $e($d['email'])
        . ($d['phone'] ? ' · ' . $e($d['phone']) : '')
        . ($d['location'] ? ' · ' . $e($d['location']) : '')
        . ($d['linkedin'] ? ' · ' . $e($d['linkedin']) : '') . '</p>'
        . (!empty($d['summary']) ? '<h2>Profil</h2><p>' . nl2br($e($d['summary'])) . '</p>' : '')
        . ($expHtml ? '<h2>Expériences</h2>' . $expHtml : '')
        . ($eduHtml ? '<h2>Formation</h2>' . $eduHtml : '')
        . ($skills ? '<h2>Compétences</h2><p>' . $skills . '</p>' : '')
        . (!empty($d['languages']) ? '<h2>Langues</h2><p>' . $e($d['languages']) . '</p>' : '')
        . '</div></body></html>';
}
