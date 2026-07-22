<?php
/** Test de personnalité professionnelle RIASEC (Holland) — données statiques. */

function riasecQuestions(): array
{
    return [
        'r' => [
            "J'aime réparer ou construire des objets avec mes mains.",
            "Je préfère les activités physiques ou en plein air aux tâches de bureau.",
            "Utiliser des outils, des machines ou de l'équipement technique m'intéresse.",
            "Je suis à l'aise pour résoudre des problèmes concrets et pratiques.",
        ],
        'i' => [
            "J'aime comprendre comment les choses fonctionnent en profondeur.",
            "Résoudre des problèmes complexes ou des énigmes me plaît.",
            "Je suis curieux(se) des sciences, des chiffres ou de la recherche.",
            "J'aime analyser des données avant de prendre une décision.",
        ],
        'a' => [
            "Créer, dessiner, écrire ou composer me procure du plaisir.",
            "J'aime exprimer mes idées de façon originale, sortir des sentiers battus.",
            "Les activités artistiques (musique, design, écriture) m'attirent.",
            "Je préfère un cadre où je peux improviser plutôt que suivre des règles strictes.",
        ],
        's' => [
            "Aider, conseiller ou enseigner à d'autres personnes me motive.",
            "J'aime travailler en équipe et échanger avec les autres.",
            "Je me sens utile quand je soutiens quelqu'un en difficulté.",
            "Les métiers de la santé, de l'éducation ou du social m'intéressent.",
        ],
        'e' => [
            "Prendre des initiatives et convaincre les autres me plaît.",
            "J'aimerais diriger un projet ou une équipe.",
            "L'idée de créer ma propre entreprise m'attire.",
            "Je suis à l'aise pour négocier ou défendre une idée.",
        ],
        'c' => [
            "J'aime organiser, classer et structurer l'information.",
            "Suivre des procédures claires et précises me rassure.",
            "Je suis rigoureux(se) dans les tâches administratives ou comptables.",
            "Je préfère un cadre de travail stable avec des règles bien définies.",
        ],
    ];
}

function riasecLabels(): array
{
    return [
        'r' => 'Réaliste',
        'i' => 'Investigateur',
        'a' => 'Artistique',
        's' => 'Social',
        'e' => 'Entreprenant',
        'c' => 'Conventionnel',
    ];
}

function riasecDescriptions(): array
{
    return [
        'r' => "Tu aimes le concret, le pratique, le manuel ou la technique — construire, réparer, manipuler.",
        'i' => "Tu aimes comprendre, analyser, chercher et résoudre des problèmes complexes.",
        'a' => "Tu aimes créer, imaginer et t'exprimer sans être enfermé(e) dans des règles strictes.",
        's' => "Tu aimes accompagner, enseigner, soutenir et échanger avec les autres.",
        'e' => "Tu aimes convaincre, entreprendre, diriger et prendre des initiatives.",
        'c' => "Tu aimes structurer, organiser et suivre des procédures précises.",
    ];
}

function riasecCareers(): array
{
    return [
        'r' => ['Technicien(ne)', 'Mécanicien(ne)', 'Électricien(ne)', 'Agronome', 'Ingénieur(e) travaux', 'Pilote', 'Chef de chantier'],
        'i' => ['Chercheur(se)', 'Data analyst', 'Médecin', 'Ingénieur(e)', 'Statisticien(ne)', 'Biologiste', 'Développeur(se)'],
        'a' => ['Designer graphique', 'Architecte', 'Musicien(ne)', 'Rédacteur(rice)', 'Réalisateur(rice)', 'Styliste', 'Publicitaire'],
        's' => ['Enseignant(e)', 'Infirmier(ère)', 'Travailleur(se) social(e)', 'Conseiller(ère) RH', 'Psychologue', 'Coach', 'Éducateur(rice)'],
        'e' => ['Entrepreneur(e)', 'Commercial(e)', 'Manager', 'Avocat(e)', 'Chef de projet', 'Responsable marketing', 'Élu(e) / politique'],
        'c' => ['Comptable', 'Gestionnaire', 'Assistant(e) administratif(ve)', 'Auditeur(rice)', 'Analyste financier(ère)', 'Archiviste', 'Notaire'],
    ];
}

/** Calcule le code RIASEC (3 lettres dominantes) à partir des scores. */
function riasecCode(array $scores): string
{
    arsort($scores);
    return implode('', array_map('strtoupper', array_slice(array_keys($scores), 0, 3)));
}
