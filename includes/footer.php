<?php
require_once __DIR__ . '/../config/config.php';
?>
</main>

<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <div class="footer-brand"><?= e(SITE_NAME) ?></div>
      <p class="footer-tagline">Ton orientation, tes bourses et ton CV gratuits en Côte d'Ivoire.</p>
    </div>

    <nav>
      <div class="footer-heading">Outils</div>
      <a href="/">Accueil</a>
      <a href="/chat.php">Orientation IA</a>
      <a href="/cv-assistant.php">Générateur de CV</a>
      <a href="/bourses.php">Bourses</a>
      <a href="/emplois.php">Emplois</a>
      <a href="/blog.php">Guides</a>
    </nav>

    <div class="footer-contact">
      <div class="footer-heading">Contact</div>
      <p><?= e(setting('contact_email', 'contact@' . strtolower(str_replace(' ', '', SITE_NAME)) . '.ci')) ?></p>
      <p>Abidjan, Côte d'Ivoire</p>
      <p>Accès gratuit sans inscription</p>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> — Plateforme 100% gratuite financée par la publicité.</p>
    <span class="region">Orientation &amp; carrière en Afrique de l'Ouest</span>
  </div>
</footer>

<script src="/assets/js/main.js" defer></script>
<?= setting('code_body_end', '') ?>
</body>
</html>
