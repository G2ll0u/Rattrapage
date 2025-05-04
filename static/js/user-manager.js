// Script pour la gestion des utilisateurs
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour les liens de suppression
    const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');
    
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });
    
    // Affichage des notifications
    const serverNotification = document.getElementById('server-notification');
    if (serverNotification) {
        try {
            const notification = JSON.parse(serverNotification.textContent);
            if (notification && notification.message) {
                // Vous pouvez utiliser une bibliothèque comme Toastify ici
                alert(notification.message);
            }
        } catch (e) {
            console.error('Erreur lors du parsing de la notification', e);
        }
    }
});