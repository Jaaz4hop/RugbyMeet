
//Menu burger pour la version mobile
function initBurger() {
    const burger = document.getElementById('burger');
    const nav = document.getElementById('nav1');

    //Si la page n'a pas de burger (ou si on est en desktop), on ne fait rien
    if (!burger || !nav) return;
    //Au click sur le menu burger on l'ouvre.
    burger.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('open');
        burger.classList.toggle('open', isOpen);
        burger.setAttribute('aria-expanded', isOpen);
    });

    //Ferme le menu quand on clique sur un lien
    nav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            nav.classList.remove('open');
            burger.classList.remove('open');
            burger.setAttribute('aria-expanded', 'false');
        });
    });

    //Ferme le menu si on repasse en desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 860) {
            nav.classList.remove('open');
            burger.classList.remove('open');
            burger.setAttribute('aria-expanded', 'false');
        }
    });
}
//Lancement de initBurger() au chargement de la page.
document.addEventListener('DOMContentLoaded', () => {
      initBurger();
});