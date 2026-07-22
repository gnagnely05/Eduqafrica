-- ============================================================
-- MISE À JOUR v2 — à importer sur la base EXISTANTE (phpMyAdmin)
-- N'écrase rien : ajoute seulement la table settings.
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(60) PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (skey, svalue) VALUES
('price_cv_download', '1500'),
('price_chat_single', '500'),
('price_chat_monthly', '500'),
('price_bourses_single', '500'),
('price_bourses_monthly', '500'),
('price_bundle_monthly', '1000'),
('code_head', ''),
('code_body_end', '');

-- ---------- v4 : contenu éditable de l'accueil + design ----------
INSERT IGNORE INTO settings (skey, svalue) VALUES
('hero_label', 'Ton avenir, simplifié.'),
('hero_title', "L'orientation et l'emploi gratuits."),
('hero_lead', "Trouve ta filière, rédige ton CV pro, découvre les bourses et offres d'emploi locales. Tout ce dont tu as besoin, 100% gratuit, propulsé par l'IA."),
('hero_btn1_text', "Lancer l'orientation IA"),
('hero_btn2_text', 'Découvrir nos outils'),
('solutions_title', 'Tes outils pour réussir'),
('solutions_subtitle', 'Accède gratuitement à des ressources innovantes conçues pour les réalités académiques et professionnelles de la Côte d\'Ivoire.'),
('sol1_title', 'Orientation sur-mesure'),
('sol1_text', 'Notre assistant conversationnel analyse ton profil pour te guider vers les meilleures filières et réorientations.'),
('sol2_title', 'Générateur de CV Pro'),
('sol2_text', "Crée un CV percutant en quelques minutes, prêt à l'emploi avec des modèles adaptés au marché local."),
('sol3_title', "Bourses d'études vérifiées"),
('sol3_text', 'Explore notre base de données IA des bourses actives, mises à jour régulièrement pour toi.'),
('eb_title', "Emplois &amp; Bourses : tes portes ouvertes"),
('eb_image_url', ''),
('eb_photo_title', "Offres d'emploi locales"),
('eb_photo_text', "Trouve des stages et emplois adaptés à ton profil en Côte d'Ivoire et dans la sous-région, mis à jour quotidiennement."),
('eb_text_title', "Bourses d'études récentes"),
('eb_text_text', "L'IA scanne et vérifie les dernières opportunités de financement pour tes études, ici et à l'étranger."),
('stats_eyebrow', 'Notre impact en chiffres'),
('stats_title', "Des milliers d'avenirs éclairés"),
('stat1_label', 'Étudiants orientés'),
('stat2_label', 'CV générés'),
('stat3_label', 'Bourses listées'),
('cta_title', 'Prêt à construire ton avenir ?'),
('cta_text', 'Rejoins des milliers d\'étudiants qui ont déjà trouvé leur voie. C\'est gratuit, rapide et efficace.'),
('cta_btn_text', "Lancer l'orientation IA"),
('color_dark_teal', '#0B2524'),
('color_coral', '#E8935F'),
('color_light_sage', '#C9DEDB'),
('color_light_grey', '#E7E9E7'),
('font_display', 'Sora'),
('font_nav', 'DM Sans'),
('font_body', 'IBM Plex Sans');
INSERT IGNORE INTO settings (skey, svalue) VALUES ('contact_email', '');

-- ---------- v6 : image mise en avant des articles ----------
-- Si la colonne existe déjà, cette ligne renverra une erreur "Duplicate column
-- name" que tu peux ignorer sans risque.
ALTER TABLE articles ADD COLUMN featured_image VARCHAR(500) NULL AFTER excerpt;

-- ---------- v7 : test de personnalité RIASEC ----------
CREATE TABLE IF NOT EXISTS personality_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(128) NOT NULL,
  score_r INT UNSIGNED NOT NULL DEFAULT 0,
  score_i INT UNSIGNED NOT NULL DEFAULT 0,
  score_a INT UNSIGNED NOT NULL DEFAULT 0,
  score_s INT UNSIGNED NOT NULL DEFAULT 0,
  score_e INT UNSIGNED NOT NULL DEFAULT 0,
  score_c INT UNSIGNED NOT NULL DEFAULT 0,
  code VARCHAR(6) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
