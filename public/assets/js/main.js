// Fermer le menu mobile quand on clique sur un lien
document.querySelectorAll('.main-nav a').forEach(a => {
  a.addEventListener('click', () => {
    document.getElementById('mainNav')?.classList.remove('open');
  });
});
