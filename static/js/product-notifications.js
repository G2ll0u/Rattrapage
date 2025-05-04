document.addEventListener('DOMContentLoaded', function() {
    // Amélioration de l'expérience des boutons d'action
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (window.navigator.vibrate) {
                window.navigator.vibrate(5); 
            }
        });
        
        // Confirmation pour les actions de suppression
        if (button.classList.contains('delete')) {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit? Cette action est irréversible.')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
    
    // Animation au chargement de la table
    const tableRows = document.querySelectorAll('.product-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 50 * index); 
    });
    
    // Mise en évidence des résultats de recherche
    const searchInput = document.getElementById('search');
    if (searchInput && searchInput.value) {
        const searchTerm = searchInput.value.toLowerCase();
        const tableCells = document.querySelectorAll('.product-table td:nth-child(2)'); // Colonne des noms
        
        tableCells.forEach(cell => {
            const text = cell.textContent;
            if (text.toLowerCase().includes(searchTerm)) {
                const highlighted = text.replace(
                    new RegExp(searchTerm, 'gi'),
                    match => `<span style="background-color:rgba(75, 123, 236, 0.2);font-weight:bold;">${match}</span>`
                );
                cell.innerHTML = highlighted;
            }
        });
    }
});