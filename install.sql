-- ============================================================
-- Plateforme d'orientation — Schéma MySQL complet
-- Import : hPanel > phpMyAdmin > Importer ce fichier
-- ============================================================
SET NAMES utf8mb4;

-- ---------- Utilisateurs ----------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  country VARCHAR(80) NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Chat ----------
CREATE TABLE IF NOT EXISTS chat_conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(128) NOT NULL,
  title VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_session (session_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conv (conversation_id),
  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Blog ----------
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(280) NOT NULL UNIQUE,
  excerpt VARCHAR(500) NULL,
  featured_image VARCHAR(500) NULL,
  content MEDIUMTEXT NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  views INT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_date (status, published_at),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CV ----------
CREATE TABLE IF NOT EXISTS cv_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cv_templates (name) VALUES ('Classique'), ('Moderne');

CREATE TABLE IF NOT EXISTS cv_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  template_id INT UNSIGNED NOT NULL DEFAULT 1,
  full_name VARCHAR(120) NOT NULL,
  data_json MEDIUMTEXT NOT NULL,
  is_paid TINYINT(1) NOT NULL DEFAULT 0,
  payment_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Emplois ----------
CREATE TABLE IF NOT EXISTS jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(280) NOT NULL UNIQUE,
  company VARCHAR(160) NULL,
  description TEXT NOT NULL,
  location VARCHAR(120) NULL,
  country VARCHAR(80) NULL,
  contract_type VARCHAR(40) NULL,
  category VARCHAR(80) NULL,
  source_url VARCHAR(500) NOT NULL,
  source_domain VARCHAR(190) NULL,
  deadline DATE NULL,
  posted_at DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  content_hash CHAR(64) NOT NULL UNIQUE,
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active, posted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Bourses ----------
CREATE TABLE IF NOT EXISTS scholarships (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(280) NOT NULL UNIQUE,
  organization VARCHAR(160) NULL,
  description TEXT NOT NULL,
  study_level VARCHAR(40) NULL,
  field VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  destination_country VARCHAR(120) NULL,
  source_url VARCHAR(500) NOT NULL,
  source_domain VARCHAR(190) NULL,
  deadline DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_premium TINYINT(1) NOT NULL DEFAULT 0,
  content_hash CHAR(64) NOT NULL UNIQUE,
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active, deadline),
  INDEX idx_premium (is_premium)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Logs des crons IA ----------
CREATE TABLE IF NOT EXISTS ai_fetch_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fetch_type ENUM('jobs','scholarships') NOT NULL,
  query_used VARCHAR(500) NOT NULL,
  items_found INT UNSIGNED NOT NULL DEFAULT 0,
  items_inserted INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('success','error') NOT NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Contact ----------
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Paiements ----------
CREATE TABLE IF NOT EXISTS payment_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'XOF',
  purpose ENUM('cv_download','chat_single_unlock','chat_subscription_monthly',
               'bourses_single_unlock','bourses_subscription_monthly',
               'bundle_subscription_monthly') NOT NULL,
  related_id INT UNSIGNED NULL,
  moneroo_reference VARCHAR(120) NULL,
  checkout_url VARCHAR(500) NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_moneroo (moneroo_reference),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_type ENUM('chat','bourses','bundle') NOT NULL,
  payment_id INT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  INDEX idx_user_active (user_id, status, expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (payment_id) REFERENCES payment_transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS single_unlocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  unlock_type ENUM('chat_message','bourses_view') NOT NULL,
  related_id INT UNSIGNED NULL,
  payment_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_type (user_id, unlock_type, related_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (payment_id) REFERENCES payment_transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catégories de blog de départ
INSERT INTO categories (name, slug) VALUES
('Orientation', 'orientation'),
('Bourses', 'bourses'),
('Emploi & carrière', 'emploi-carriere'),
('Méthodes & réussite', 'methodes-reussite');

-- ---------- Réglages dynamiques (admin) ----------
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
