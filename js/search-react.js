// Fichier d'intégration React pour la recherche
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation de la recherche React...');
    const searchRoot = document.getElementById('react-search-root');
    
    if (searchRoot) {
        // Vérifier si React est déjà chargé
        if (window.React && window.ReactDOM) {
            initializeReactSearch();
            return;
        }

        // Charger React et ReactDOM depuis CDN
        const scriptReact = document.createElement('script');
        scriptReact.src = 'https://unpkg.com/react@18/umd/react.development.js';
        scriptReact.crossOrigin = true;
        
        const scriptReactDOM = document.createElement('script');
        scriptReactDOM.src = 'https://unpkg.com/react-dom@18/umd/react-dom.development.js';
        scriptReactDOM.crossOrigin = true;

        scriptReact.onload = () => {
            console.log('✅ React chargé');
            scriptReactDOM.onload = () => {
                console.log('✅ ReactDOM chargé');
                
                // Charger Babel pour JSX
                const scriptBabel = document.createElement('script');
                scriptBabel.src = 'https://unpkg.com/@babel/standalone/babel.min.js';
                scriptBabel.onload = () => {
                    console.log('✅ Babel chargé');
                    loadSearchBarComponent();
                };
                document.head.appendChild(scriptBabel);
            };
            document.head.appendChild(scriptReactDOM);
        };
        document.head.appendChild(scriptReact);
    }
});

function loadSearchBarComponent() {
    console.log('Chargement du composant SearchBar...');
    
    // Créer un script pour le composant SearchBar avec JSX
    const script = document.createElement('script');
    script.type = 'text/babel';
    script.innerHTML = `
        const { useState, useEffect, useRef } = React;

        function SearchBar({ books, onSearch }) {
            const [query, setQuery] = useState("");
            const [showSuggestions, setShowSuggestions] = useState(false);
            const suggestionsRef = useRef();

            // Filtrage suggestions
            const filteredSuggestions = books
                .filter(book =>
                    book.title.toLowerCase().includes(query.toLowerCase()) ||
                    book.author.toLowerCase().includes(query.toLowerCase())
                )
                .slice(0, 5);

            // Cacher suggestions si on clique ailleurs
            useEffect(() => {
                function handleClick(e) {
                    if (suggestionsRef.current && !suggestionsRef.current.contains(e.target)) {
                        setShowSuggestions(false);
                    }
                }
                document.addEventListener("click", handleClick);
                return () => document.removeEventListener("click", handleClick);
            }, []);

            // Recherche en temps réel
            useEffect(() => {
                console.log('🔍 Recherche:', query);
                if (onSearch) {
                    onSearch(query);
                }
            }, [query]);

            const handleSuggestionClick = (book) => {
                if (book.link) {
                    window.location.href = book.link;
                }
            };

            return React.createElement('div', { 
                className: "search-container", 
                style: { position: "relative" } 
            },
                React.createElement('div', { className: "input-group" },
                    React.createElement('input', {
                        type: "search",
                        id: "react-search-input",
                        className: "form-control",
                        placeholder: "🔍 Rechercher un livre, auteur...",
                        value: query,
                        onChange: (e) => setQuery(e.target.value),
                        onFocus: () => setShowSuggestions(true),
                        style: { minWidth: "280px", borderRadius: "8px" }
                    }),
                    React.createElement('button', {
                        className: "btn btn-primary",
                        style: { borderRadius: "0 8px 8px 0" },
                        onClick: () => onSearch && onSearch(query)
                    },
                        React.createElement('i', { className: "bi bi-search" })
                    )
                ),

                // Suggestions
                showSuggestions && query.length > 1 && React.createElement('div', {
                    ref: suggestionsRef,
                    className: "search-suggestions",
                    style: {
                        position: "absolute",
                        top: "100%",
                        left: 0,
                        right: 0,
                        background: "white",
                        border: "1px solid #dee2e6",
                        borderRadius: "8px",
                        marginTop: "5px",
                        zIndex: 1000,
                        maxHeight: "300px",
                        overflowY: "auto",
                        boxShadow: "0 4px 12px rgba(0,0,0,0.1)"
                    }
                },
                    filteredSuggestions.length === 0 
                        ? React.createElement('div', { 
                            style: { padding: "15px", textAlign: "center", color: "#6c757d" } 
                          },
                            React.createElement('i', { className: "bi bi-search" }),
                            " Aucun résultat"
                          )
                        : filteredSuggestions.map((book, index) =>
                            React.createElement('div', {
                                key: index,
                                className: "suggestion-item",
                                style: {
                                    padding: "12px",
                                    borderBottom: "1px solid #f8f9fa",
                                    cursor: "pointer",
                                    display: "flex",
                                    alignItems: "center"
                                },
                                onMouseOver: (e) => e.currentTarget.style.backgroundColor = "#f8f9fa",
                                onMouseOut: (e) => e.currentTarget.style.backgroundColor = "white",
                                onClick: () => handleSuggestionClick(book)
                            },
                                React.createElement('img', {
                                    src: book.cover,
                                    alt: book.title,
                                    style: {
                                        width: "40px",
                                        height: "55px",
                                        objectFit: "cover",
                                        borderRadius: "4px",
                                        marginRight: "12px"
                                    },
                                    onError: (e) => {
                                        e.target.src = "assets/placeholder.png";
                                    }
                                }),
                                React.createElement('div', { style: { flex: 1 } },
                                    React.createElement('div', { 
                                        style: { fontWeight: 600, fontSize: "14px", color: "#0a66c2" } 
                                    }, book.title),
                                    React.createElement('div', { 
                                        style: { fontSize: "12px", color: "#6c757d" } 
                                    }, book.author),
                                    React.createElement('div', { 
                                        style: { fontSize: "12px", color: "#28a745", fontWeight: "bold" } 
                                    }, book.price)
                                ),
                                React.createElement('span', {
                                    className: \`badge \${book.available ? "bg-success" : "bg-secondary"}\`,
                                    style: { fontSize: "0.7rem" }
                                }, book.available ? "Dispo" : "Indispo")
                            )
                        )
                )
            );
        }

        // Initialiser la recherche
        setTimeout(() => {
            const searchRoot = document.getElementById('react-search-root');
            if (searchRoot) {
                // Récupérer les données des livres depuis le DOM
                function getBooksData() {
                    const bookElements = document.querySelectorAll('#booksGrid .col-6');
                    const books = [];
                    
                    bookElements.forEach(element => {
                        const title = element.querySelector('.book-title')?.textContent || '';
                        const author = element.querySelector('.book-author')?.textContent || '';
                        const price = element.querySelector('.small-muted')?.textContent || '';
                        const cover = element.querySelector('.book-cover')?.src || '';
                        const available = element.getAttribute('data-available') === 'available';
                        const link = element.querySelector('a[href*="details.php"]')?.href || '';
                        
                        books.push({
                            title,
                            author,
                            price,
                            cover,
                            available,
                            link
                        });
                    });
                    
                    return books;
                }

                // Fonction de recherche pour filtrer les livres
                function handleSearch(query) {
                    console.log('🔍 Recherche déclenchée:', query);
                    
                    // Mettre à jour la valeur de l'input de recherche
                    const searchInput = document.getElementById('react-search-input');
                    if (searchInput) {
                        searchInput.value = query;
                    }
                    
                    // Déclencher le filtrage via app.js
                    if (window.app && window.app.filterBooks) {
                        console.log('✅ Déclenchement de filterBooks');
                        window.app.filterBooks();
                    } else {
                        console.log('❌ app.filterBooks non disponible');
                    }
                }

                // Rendre le composant
                const root = ReactDOM.createRoot(searchRoot);
                root.render(React.createElement(SearchBar, {
                    books: getBooksData(),
                    onSearch: handleSearch
                }));
                
                console.log('✅ Recherche React initialisée avec succès');
            }
        }, 100);
    `;
    
    document.head.appendChild(script);
}