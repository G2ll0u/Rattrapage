// Script pour le bouton "Retour en haut"
document.addEventListener('DOMContentLoaded', function() {
    // Créer le bouton dynamiquement
    const backToTopButton = document.createElement('button');
    backToTopButton.id = 'back-to-top';
    backToTopButton.innerHTML = '<span>&uarr;</span>';
    backToTopButton.setAttribute('aria-label', 'Retour en haut de la page');
    backToTopButton.setAttribute('title', 'Retour en haut de la page');
    document.body.appendChild(backToTopButton);
    
    // Fonction pour vérifier la position de défilement
    function toggleBackToTopButton() {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    }
    
    // Événement de défilement
    window.addEventListener('scroll', toggleBackToTopButton);
    
    // Action au clic sur le bouton
    backToTopButton.addEventListener('click', function() {
        // Défilement fluide pour les navigateurs modernes
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Appel initial pour déterminer si le bouton doit être visible
    toggleBackToTopButton();
});