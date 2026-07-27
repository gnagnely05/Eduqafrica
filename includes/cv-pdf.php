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
            . '<br><span class="meta">' . $e($x['period']) . ($x['city'] ? ' · ' . $e($x['city']) : '') . '</span></p>'
            . $desc . '</div>';
    }

    $eduHtml = '';
    foreach ($d['education'] ?? [] as $x) {
        $eduHtml .= '<div class="item"><p><strong>' . $e($x['degree']) . '</strong><br>' . $e($x['school'])
            . '<br><span class="meta">' . $e($x['years']) . ($x['city'] ? ' · ' . $e($x['city']) : '') . '</span></p></div>';
    }

    $skillsHtml = '';
    foreach ($d['skills'] ?? [] as $s) {
        if (trim((string)$s) === '') continue;
        $skillsHtml .= '<span class="tag">' . $e($s) . '</span>';
    }

    $contactHtml = '';
    foreach ([
        ['✉', $d['email'] ?? ''],
        ['☎', $d['phone'] ?? ''],
        ['📍', $d['location'] ?? ''],
        ['in', $d['linkedin'] ?? ''],
    ] as [$icon, $val]) {
        if (trim((string)$val) === '') continue;
        $contactHtml .= '<p class="contact-line"><span class="ic">' . $e($icon) . '</span> ' . $e($val) . '</p>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #23264a; margin: 0; }
        .header { background: #0B2524; color: #fff; padding: 28px 36px; }
        .header h1 { font-size: 22pt; margin: 0 0 3px; }
        .header .headline { color: #FFB703; font-weight: bold; font-size: 11.5pt; margin: 0; }
        table.layout { width: 100%; border-collapse: collapse; }
        td.sidebar { width: 32%; background: #EFF4F3; padding: 24px 20px; vertical-align: top; }
        td.main { width: 68%; padding: 24px 30px; vertical-align: top; }
        .contact-line { font-size: 9pt; margin: 0 0 8px; }
        .ic { display: inline-block; width: 14px; color: #E8935F; font-weight: bold; }
        .sidebar h2, .main h2 { font-size: 11pt; color: #0B2524; text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 2px solid #FFB703; padding-bottom: 4px; margin: 18px 0 8px; }
        .sidebar h2:first-child, .main h2:first-child { margin-top: 0; }
        .tag { display: inline-block; background: #fff; border: 1px solid #C9DEDB; border-radius: 10px;
            padding: 3px 9px; font-size: 8.5pt; margin: 0 4px 6px 0; }
        .item { margin-bottom: 10px; }
        .item p { margin: 0; }
        .meta { color: #6A7194; font-size: 8.5pt; }
        .desc { font-size: 9pt; margin: 3px 0 0; }
        .summary { font-size: 9.5pt; line-height: 1.5; margin: 0; }
    </style></head><body>'
        . '<div class="header"><h1>' . $e($d['full_name']) . '</h1>'
        . (!empty($d['headline']) ? '<p class="headline">' . $e($d['headline']) . '</p>' : '')
        . '</div>'
        . '<table class="layout"><tr>'
        . '<td class="sidebar">'
        . '<h2>Contact</h2>' . $contactHtml
        . ($skillsHtml ? '<h2>Compétences</h2>' . $skillsHtml : '')
        . (!empty($d['languages']) ? '<h2>Langues</h2><p class="meta">' . nl2br($e($d['languages'])) . '</p>' : '')
        . '</td>'
        . '<td class="main">'
        . (!empty($d['summary']) ? '<h2>Profil</h2><p class="summary">' . nl2br($e($d['summary'])) . '</p>' : '')
        . ($expHtml ? '<h2>Expériences</h2>' . $expHtml : '')
        . ($eduHtml ? '<h2>Formation</h2>' . $eduHtml : '')
        . '</td>'
        . '</tr></table>'
        . '</body></html>';
}
