<?php
$pageTitle = "Générateur de CV gratuit";
$pageDesc = "Crée ton CV professionnel gratuitement en quelques minutes. Modèles adaptés aux étudiants et jeunes diplômés. Téléchargement PDF.";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$templates = db()->query('SELECT id, name FROM cv_templates WHERE is_active = 1')->fetchAll();
?>

<div class="container section" style="max-width:760px;">
  <span class="eyebrow">Générateur de CV</span>
  <h1>Formulaire classique</h1>
  <p style="margin-top:8px;"><a href="/cv-assistant.php">← Essayer plutôt l'assistant IA (recommandé)</a></p>
  <p style="color:var(--ink-soft); margin:12px 0 32px;">
    Remplis le formulaire, prévisualise gratuitement, télécharge en PDF pour <?= number_format(price('cv_download'), 0, ',', ' ') ?> F CFA.
  </p>

  <form id="cvForm" method="post" action="/api/cv-save.php">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

    <h2>Modèle</h2>
    <div class="form-group">
      <select name="template_id" class="form-control" required>
        <?php if (!$templates): ?><option value="1">Classique</option><?php endif; ?>
        <?php foreach ($templates as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <h2>Informations personnelles</h2>
    <div class="grid-2">
      <div class="form-group"><label>Nom complet *</label><input class="form-control" name="full_name" required></div>
      <div class="form-group"><label>Titre / poste visé</label><input class="form-control" name="headline" placeholder="Ex : Assistant comptable junior"></div>
      <div class="form-group"><label>Email *</label><input class="form-control" type="email" name="email" required></div>
      <div class="form-group"><label>Téléphone</label><input class="form-control" name="phone" placeholder="+225 ..."></div>
      <div class="form-group"><label>Ville, Pays</label><input class="form-control" name="location" placeholder="Abidjan, Côte d'Ivoire"></div>
      <div class="form-group"><label>LinkedIn (optionnel)</label><input class="form-control" name="linkedin"></div>
    </div>

    <div class="form-group"><label>Profil / résumé (2-3 phrases)</label>
      <textarea class="form-control" name="summary" rows="3" placeholder="Jeune diplômé motivé..."></textarea>
    </div>

    <h2>Formation</h2>
    <div id="educationList"></div>
    <button type="button" class="btn btn-ghost" onclick="addEducation()">+ Ajouter une formation</button>

    <h2 style="margin-top:32px;">Expériences (emplois, stages)</h2>
    <div id="experienceList"></div>
    <button type="button" class="btn btn-ghost" onclick="addExperience()">+ Ajouter une expérience</button>

    <h2 style="margin-top:32px;">Compétences</h2>
    <div class="form-group">
      <input class="form-control" name="skills" placeholder="Séparées par des virgules : Excel, Communication, Anglais...">
    </div>

    <h2>Langues</h2>
    <div class="form-group">
      <input class="form-control" name="languages" placeholder="Ex : Français (courant), Anglais (intermédiaire)">
    </div>

    <button type="submit" class="btn btn-primary btn-block" style="margin-top:24px;">Prévisualiser mon CV (gratuit)</button>
  </form>
</div>

<template id="tplEducation">
  <div class="card" style="margin-bottom:14px;">
    <div class="grid-2">
      <div class="form-group"><label>Diplôme *</label><input class="form-control" name="edu_degree[]" required placeholder="Licence en Gestion"></div>
      <div class="form-group"><label>Établissement *</label><input class="form-control" name="edu_school[]" required></div>
      <div class="form-group"><label>Années</label><input class="form-control" name="edu_years[]" placeholder="2021 - 2024"></div>
      <div class="form-group"><label>Ville</label><input class="form-control" name="edu_city[]"></div>
    </div>
    <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Retirer</button>
  </div>
</template>

<template id="tplExperience">
  <div class="card" style="margin-bottom:14px;">
    <div class="grid-2">
      <div class="form-group"><label>Poste *</label><input class="form-control" name="exp_title[]" required></div>
      <div class="form-group"><label>Entreprise *</label><input class="form-control" name="exp_company[]" required></div>
      <div class="form-group"><label>Période</label><input class="form-control" name="exp_period[]" placeholder="Juin 2024 - Août 2024"></div>
      <div class="form-group"><label>Ville</label><input class="form-control" name="exp_city[]"></div>
    </div>
    <div class="form-group"><label>Missions principales</label>
      <textarea class="form-control" name="exp_desc[]" rows="2" placeholder="Une mission par ligne"></textarea>
    </div>
    <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Retirer</button>
  </div>
</template>

<script>
function addEducation() {
  document.getElementById('educationList').appendChild(
    document.getElementById('tplEducation').content.cloneNode(true));
}
function addExperience() {
  document.getElementById('experienceList').appendChild(
    document.getElementById('tplExperience').content.cloneNode(true));
}
addEducation();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
