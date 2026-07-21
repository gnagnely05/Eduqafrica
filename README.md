# 🧭 EduqAfrica — Guide de déploiement Hostinger

## Structure du projet

```
config/                  ← HORS document root (protégé)
├── config.php           ← déjà configuré sur le serveur, ne pas écraser
└── database.php
includes/                ← HORS document root
├── functions.php, auth.php, openrouter.php, moneroo.php
├── header.php, footer.php, cv-pdf.php, admin-layout.php
cron/                    ← HORS document root (CLI uniquement)
├── fetch_jobs.php
└── fetch_scholarships.php
public_html/             ← DOCUMENT ROOT
├── index.php, chat.php, bourses.php, emplois.php...
├── admin/ (tableau de bord, contenu, design, articles, tarifs, messages)
├── api/ (chat-send.php, cv-chat.php, cv-extract.php, cv-save.php)
├── assets/ (css, js)
├── composer.json
└── vendor/ (généré par composer install, voir ci-dessous)
install.sql              ← Schéma de la base
upgrade.sql               ← Mise à jour incrémentale (table settings)
```

`config/`, `cron/`, `includes/` et `public_html/` sont tous au même niveau
(frères), à la racine du site sur Hostinger — c'est cette disposition qui
permet aux chemins relatifs du code (`../config/...`, `../includes/...`) de
fonctionner correctement.

## Étapes de déploiement

### 1. Base de données
1. hPanel > **Bases de données MySQL** > créer une base + un utilisateur
2. Ouvrir **phpMyAdmin** > sélectionner la base > **Importer** > `install.sql`

### 2. Configuration
`config/config.php` **n'est pas suivi par Git** (voir `.gitignore`) : un
déploiement ne le touche jamais, ni ne le supprime. Sur un nouveau serveur,
copie `config/config.example.php` en `config/config.php` et renseigne les
vraies valeurs (BDD, etc.). Sur ce site, il est déjà configuré sur le serveur
— pour changer un réglage (ex. le modèle IA), édite-le directement via le
Gestionnaire de fichiers Hostinger, jamais via Git. Les clés
`OPENROUTER_API_KEY` et `MONEROO_SECRET_KEY` peuvent être ajoutées plus tard
sans bloquer l'affichage du site.

### 3. DomPDF (génération des CV en PDF)
`vendor/` doit se trouver **à l'intérieur de `public_html/`** (au même niveau
que `admin/`, `api/`, `assets/`), car `composer.json` s'y trouve. En SSH
(hPanel > Avancé > SSH) :
```bash
cd /home/USER/chemin-du-site/public_html
composer install
```
Sans SSH : installer en local (`composer install` dans `public_html/`) puis
uploader le dossier `vendor/` généré par FTP.

Lecture des PDF uploadés (assistant CV) : `composer.json` inclut déjà
`smalot/pdfparser`, `composer install` l'installe automatiquement. Sans ça,
l'upload PDF affichera un message invitant à envoyer en .docx/.txt (le reste
fonctionne).

### 4. Crons (hPanel > Avancé > Tâches Cron)
```
0 6 * * 1    php /home/USER/chemin/cron/fetch_jobs.php
30 6 * * 1   php /home/USER/chemin/cron/fetch_scholarships.php
```
(Tous les lundis à 6h00 et 6h30. Tester d'abord manuellement en SSH :
`php cron/fetch_jobs.php`)

### 5. Compte admin
S'inscrire normalement sur le site, puis en phpMyAdmin :
```sql
UPDATE users SET role = 'admin' WHERE email = 'ton@email.ci';
```
Puis va sur `https://ton-site/admin/`.

### 6. Vérifications avant AdSense
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
