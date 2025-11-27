import { useState, useEffect, useRef } from "react";

export default function SearchBar({ books, onSearch }) {
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
        if (onSearch) {
            onSearch(query);
        }
    }, [query, onSearch]);

    const handleSuggestionClick = (book) => {
        if (book.link) {
            window.location.href = book.link;
        }
    };

    return (
        <div className="search-container" style={{ position: "relative" }}>
            <div className="input-group">
                <input
                    type="search"
                    id="react-search-input"
                    className="form-control"
                    placeholder="🔍 Rechercher un livre, auteur..."
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={() => setShowSuggestions(true)}
                    style={{ minWidth: "280px", borderRadius: "8px" }}
                />

                <button
                    className="btn btn-primary"
                    style={{ borderRadius: "0 8px 8px 0" }}
                    onClick={() => onSearch && onSearch(query)}
                >
                    <i className="bi bi-search"></i>
                </button>
            </div>

            {/* Suggestions */}
            {showSuggestions && query.length > 1 && (
                <div
                    ref={suggestionsRef}
                    className="search-suggestions"
                    style={{
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
                    }}
                >
                    {filteredSuggestions.length === 0 ? (
                        <div style={{ padding: "15px", textAlign: "center", color: "#6c757d" }}>
                            <i className="bi bi-search"></i> Aucun résultat
                        </div>
                    ) : (
                        filteredSuggestions.map((book, index) => (
                            <div
                                key={index}
                                className="suggestion-item"
                                style={{
                                    padding: "12px",
                                    borderBottom: "1px solid #f8f9fa",
                                    cursor: "pointer",
                                    display: "flex",
                                    alignItems: "center"
                                }}
                                onMouseOver={(e) => e.currentTarget.style.backgroundColor = "#f8f9fa"}
                                onMouseOut={(e) => e.currentTarget.style.backgroundColor = "white"}
                                onClick={() => handleSuggestionClick(book)}
                            >
                                <img
                                    src={book.cover}
                                    alt={book.title}
                                    style={{
                                        width: "40px",
                                        height: "55px",
                                        objectFit: "cover",
                                        borderRadius: "4px",
                                        marginRight: "12px"
                                    }}
                                    onError={(e) => {
                                        e.target.src = "assets/placeholder.png";
                                    }}
                                />

                                <div style={{ flex: 1 }}>
                                    <div style={{ fontWeight: 600, fontSize: "14px", color: "#0a66c2" }}>
                                        {book.title}
                                    </div>
                                    <div style={{ fontSize: "12px", color: "#6c757d" }}>
                                        {book.author}
                                    </div>
                                    <div style={{ fontSize: "12px", color: "#28a745", fontWeight: "bold" }}>
                                        {book.price}
                                    </div>
                                </div>

                                <span
                                    className={`badge ${book.available ? "bg-success" : "bg-secondary"}`}
                                    style={{ fontSize: "0.7rem" }}
                                >
                                    {book.available ? "Dispo" : "Indispo"}
                                </span>
                            </div>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}