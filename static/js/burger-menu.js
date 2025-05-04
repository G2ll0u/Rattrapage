/**
 * StockFlow - Script principal
 * Gère la navigation, le menu burger et l'interface utilisateur
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('StockFlow - Initialisation...');
    
    // ===== GESTION DU MENU BURGER =====
    const burgerMenu = document.querySelector('.burger-menu');
    const navMenu = document.querySelector('nav');
    
    if (burgerMenu && navMenu) {
        // Gestion du clic sur le burger menu
        burgerMenu.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Menu burger activé');
            
            // Basculer les classes active
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Mettre à jour l'attribut aria-expanded pour l'accessibilité
            const expanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !expanded);
        });
        
        // Fermer le menu quand on clique sur un lien de navigation
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                burgerMenu.setAttribute('aria-expanded', 'false');
            });
        });
        
        // Fermer le menu quand on clique en dehors
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navMenu.contains(event.target);
            const isClickInsideBurger = burgerMenu.contains(event.target);
            
            if (!isClickInsideNav && !isClickInsideBurger && navMenu.classList.contains('active')) {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                burgerMenu.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Fermer le menu au redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                burgerMenu.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // ===== GESTION DU MENU UTILISATEUR DROPDOWN =====
    const userIcon = document.getElementById('user-icon');
    if (userIcon) {
        // Gestion du clic sur l'icône utilisateur
        userIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdownMenu = this.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                // Afficher/masquer le menu dropdown
                const isVisible = dropdownMenu.style.display === 'block';
                dropdownMenu.style.display = isVisible ? 'none' : 'block';
            }
        });
        
        // Empêcher la fermeture au clic sur le menu déroulant
        const dropdownMenu = userIcon.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Fermer le menu au clic en dehors
        document.addEventListener('click', function() {
            const dropdownMenu = userIcon.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.style.display = 'none';
            }
        });
    }
    
    // ===== AJOUT DE LA CLASSE ACTIVE SUR LES LIENS DE NAVIGATION =====
    const setActiveNavLink = () => {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('nav a');
        let activeSet = false;
        
        navLinks.forEach(link => {
            // Enlever la classe active de tous les liens
            link.classList.remove('active');
            
            try {
                // Obtenir le chemin du lien
                const linkPath = new URL(link.href).pathname;
                
                // Si le chemin de la page correspond au chemin du lien
                if (currentPath === linkPath || 
                    (currentPath.includes(linkPath) && linkPath !== '/' && linkPath.length > 1)) {
                    link.classList.add('active');
                    activeSet = true;
                }
            } catch (e) {
                console.warn('Erreur lors de la vérification du lien actif:', e);
            }
        });
        
        // Si aucun lien n'est actif et qu'on est sur la page d'accueil
        if (!activeSet && (currentPath === '/' || currentPath.endsWith('index.php') || currentPath === '')) {
            const homeLink = document.querySelector('nav a[href$="home/index"]') || 
                             document.querySelector('nav a[href$="home"]') ||
                             document.querySelector('nav a[href="/"]');
            if (homeLink) homeLink.classList.add('active');
        }
    };
    
    // Exécuter au chargement de la page
    setActiveNavLink();
    
    // ===== GESTION DES FORMULAIRES =====
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Validation des formulaires avant soumission
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Créer ou mettre à jour le message d'erreur
                    let errorMessage = field.nextElementSibling;
                    if (!errorMessage || !errorMessage.classList.contains('error-message')) {
                        errorMessage = document.createElement('div');
                        errorMessage.classList.add('error-message');
                        field.parentNode.insertBefore(errorMessage, field.nextSibling);
                    }
                    errorMessage.textContent = 'Ce champ est requis';
                } else {
                    field.classList.remove('error');
                    const errorMessage = field.nextElementSibling;
                    if (errorMessage && errorMessage.classList.contains('error-message')) {
                        errorMessage.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // ===== GESTION DES ALERTES ET NOTIFICATIONS =====
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        // Faire disparaître les alertes après 5 secondes
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
            
            // Ajouter un bouton de fermeture
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.className = 'alert-close';
            closeBtn.addEventListener('click', () => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
            alert.appendChild(closeBtn);
        });
    }
    
    console.log('StockFlow - Initialisation terminée');
});