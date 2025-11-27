const app = {
    /* --- Catalog page --- */
    initCatalog() {
        console.log('📚 Initialisation du catalogue');
        this.setupEventListeners();
        console.log('✅ Catalogue initialisé avec filtres');
    },

    setupEventListeners() {
        document.getElementById('filterCategory')?.addEventListener('change', () => this.filterBooks());
        document.getElementById('filterAvailability')?.addEventListener('change', () => this.filterBooks());
        document.getElementById('filterGender')?.addEventListener('change', () => this.filterBooks());
        document.getElementById('filterLang')?.addEventListener('change', () => this.filterBooks());
        document.getElementById('sortBy')?.addEventListener('change', () => this.filterBooks());
        
        // Écouter les changements sur l'input de recherche React
        this.setupSearchListener();
        
        console.log('✅ Événements des filtres configurés');
    },

    setupSearchListener() {
        // Surveiller les changements sur l'input de recherche React
        const searchInput = document.getElementById('react-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.filterBooks());
            console.log('✅ Écouteur de recherche configuré');
        } else {
            // Réessayer après un délai si l'input n'est pas encore disponible
            setTimeout(() => this.setupSearchListener(), 100);
        }
    },

    filterBooks() {
        console.log('🎯 filterBooks appelé');
        
        let searchInput = document.getElementById('react-search-input');
        let q = '';
        
        if (searchInput) {
            q = searchInput.value.toLowerCase().trim();
            console.log('🔍 Terme de recherche:', q);
        } else {
            console.log('⚠️ Input de recherche non trouvé');
            // Réessayer après un court délai
            setTimeout(() => {
                this.filterBooks();
            }, 100);
            return;
        }
        
        const cat = document.getElementById('filterCategory')?.value || '';
        const avail = document.getElementById('filterAvailability')?.value || '';
        const gender = document.getElementById('filterGender')?.value || '';
        const lang = document.getElementById('filterLang')?.value || '';
        const sort = document.getElementById('sortBy')?.value || 'title';

        console.log('🔍 Filtrage avec:', { q, cat, avail, gender, lang, sort });

        const bookElements = document.querySelectorAll('#booksGrid .col-6');
        let visibleCount = 0;
        
        bookElements.forEach(element => {
            const title = element.querySelector('.book-title')?.textContent?.toLowerCase() || '';
            const author = element.querySelector('.book-author')?.textContent?.toLowerCase() || '';
            const category = element.getAttribute('data-category') || '';
            const language = element.getAttribute('data-language') || '';
            const bookGender = element.getAttribute('data-gender') || '';
            const available = element.getAttribute('data-available') === 'available';

            let show = true;

            // Filtre recherche
            if (q && !title.includes(q) && !author.includes(q)) {
                show = false;
            }

            // Filtre catégorie
            if (cat && category !== cat) {
                show = false;
            }

            // Filtre disponibilité
            if (avail === 'available' && !available) {
                show = false;
            }
            if (avail === 'unavailable' && available) {
                show = false;
            }

            // Filtre genre
            if (gender && bookGender !== gender) {
                show = false;
            }

            // Filtre langue
            if (lang && language !== lang) {
                show = false;
            }

            element.style.display = show ? 'block' : 'none';
            if (show) visibleCount++;
        });

        console.log(`✅ Filtrage terminé: ${visibleCount} livres visibles`);
        this.sortBooks(sort);
    },

    sortBooks(sortBy) {
        const container = document.getElementById('booksGrid');
        if (!container) return;

        const items = Array.from(container.querySelectorAll('.col-6')).filter(item => 
            item.style.display !== 'none'
        );

        console.log(`📚 Tri de ${items.length} livres par: ${sortBy}`);

        items.sort((a, b) => {
            let aValue, bValue;

            switch(sortBy) {
                case 'price':
                    const aPriceText = a.querySelector('.small-muted')?.textContent || '0';
                    const bPriceText = b.querySelector('.small-muted')?.textContent || '0';
                    aValue = parseFloat(aPriceText.replace(/[^\d.,]/g, '').replace(',', '.'));
                    bValue = parseFloat(bPriceText.replace(/[^\d.,]/g, '').replace(',', '.'));
                    return aValue - bValue;
                    
                case 'author':
                    aValue = a.querySelector('.book-author')?.textContent || '';
                    bValue = b.querySelector('.book-author')?.textContent || '';
                    break;
                    
                case 'category':
                    aValue = a.getAttribute('data-category') || '';
                    bValue = b.getAttribute('data-category') || '';
                    break;
                    
                default: // title
                    aValue = a.querySelector('.book-title')?.textContent || '';
                    bValue = b.querySelector('.book-title')?.textContent || '';
            }

            return aValue.localeCompare(bValue, 'fr', { sensitivity: 'base' });
        });

        // Réorganiser les éléments dans le DOM
        items.forEach(item => container.appendChild(item));
        
        console.log('✅ Tri terminé');
    },

    /* --- Autres fonctions utilitaires --- */
    formatPrice(price) {
        return parseFloat(price).toFixed(3) + ' TND';
    },

    escape(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    console.log('🏠 Page catalogue - Initialisation app.js');
    const path = location.pathname.split('/').pop();
    if (path === 'index.php' || path === '' || path === 'catalogue.php') {
        app.initCatalog();
    }
});

// Exposer app globalement pour la recherche React
window.app = app;
console.log('app.js chargé et prêt');