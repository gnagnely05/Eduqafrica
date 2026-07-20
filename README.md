# 🧭 EduqAfrica — Guide de déploiement Hostinger

## Structure du projet

```
site/
├── config/              ← HORS document root (protégé)
│   ├── config.php       ← ⚙️ À CONFIGURER (clés API, BDD)
│   └── database.php
├── includes/            ← HORS document root
│   ├── functions.php, auth.php, openrouter.php, moneroo.php
│   ├── header.php, footer.php, cv-pdf.php
├── cron/                ← HORS document root (CLI uniquement)
│   ├── fetch_jobs.php
│   └── fetch_scholarships.php
├── public/              ← DOCUMENT ROOT (public_html)
│   ├── index.php, chat.php, bourses.php, emplois.php...
│   ├── admin/ (tableau de bord, contenu, design, articles, tarifs, messages)
│   ├── api/ (chat-send.php, cv-chat.php, cv-extract.php, cv-save.php)
│   └── assets/ (css, js)
├── install.sql          ← Schéma de la base
└── upgrade.sql          ← Mise à jour incrémentale (table settings)
```

## Étapes de déploiement

### 1. Base de données
1. hPanel > **Bases de données MySQL** > créer une base + un utilisateur
2. Ouvrir **phpMyAdmin** > sélectionner la base > **Importer** > `install.sql`

### 2. Fichiers
1. Uploader tout le dossier via le **Gestionnaire de fichiers** ou FTP :
   - `config/`, `includes/`, `cron/` → dans `/home/USER/` (au-dessus de public_html)
   - contenu de `public/` → dans `public_html/`
2. **OU** garder la structure telle quelle et pointer le document root du domaine
   vers le dossier `public/` (hPanel > Domaines > document root) — recommandé.

### 3. Configuration
Éditer `config/config.php` :
- `SITE_NAME`, `SITE_URL`, `SITE_EMAIL`
- Identifiants MySQL (`DB_NAME`, `DB_USER`, `DB_PASS`)
- `OPENROUTER_API_KEY` → créer sur https://openrouter.ai/keys et créditer ~5-10 $
- `MONEROO_SECRET_KEY` → dashboard https://moneroo.io après validation du compte

### 4. DomPDF (génération des CV en PDF)
En SSH (hPanel > Avancé > SSH) :
```bash
cd /home/USER/chemin-du-site
composer require dompdf/dompdf
```
Le dossier `vendor/` doit être au même niveau que `includes/` (le code fait
`require __DIR__ . '/../vendor/autoload.php'`). Sans SSH : installer en local
puis uploader `vendor/` par FTP.

Lecture des PDF uploadés (assistant CV) : en SSH →
```bash
composer require smalot/pdfparser
```
Sans ça, l'upload PDF affichera un message invitant à envoyer en .docx/.txt
(le reste fonctionne).

### 5. Crons (hPanel > Avancé > Tâches Cron)
```
0 6 * * 1    php /home/USER/chemin/cron/fetch_jobs.php
30 6 * * 1   php /home/USER/chemin/cron/fetch_scholarships.php
```
(Tous les lundis à 6h00 et 6h30. Tester d'abord manuellement en SSH :
`php cron/fetch_jobs.php`)

### 6. Compte admin
S'inscrire normalement sur le site, puis en phpMyAdmin :
```sql
UPDATE users SET role = 'admin' WHERE email = 'ton@email.ci';
```
Puis va sur `https://ton-site/admin/`.

### 7. Vérifications avant AdSense
- [ ] 15-20 articles de blog originaux publiés
- [ ] Pages À propos / Contact / Confidentialité complétées (remplacer les [À COMPLÉTER])
- [ ] Domaine avec HTTPS actif
- [ ] Puis : https://adsense.google.com > ajouter le site > Admin > Réglages > coller le script dans « Code head »

## ⚠️ Points d'attention
- **Moneroo** : vérifier la structure exacte des réponses API dans la doc officielle
  (https://docs.moneroo.io) avant la mise en production — le code suppose
  `data.data.checkout_url` et `data.data.status`. Faire un paiement test de 100 F.
- **OpenRouter** : surveiller la consommation (dashboard > Usage). Le chat en
  claude-3.5-haiku coûte ~0,5-1 F CFA par réponse simple.
- Le fichier `config.php` contient des secrets : il est hors de public_html, ne
  jamais le déplacer dans le document root.
