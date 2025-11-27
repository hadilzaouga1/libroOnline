<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$book_id = $_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupérer le livre
    $query = "SELECT * FROM books WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        header("Location: index.php");
        exit();
    }
    
    // Récupérer les recommandations (même catégorie, limit 4)
    $recQuery = "SELECT * FROM books WHERE category = :category AND id != :id AND available = 1 LIMIT 4";
    $recStmt = $db->prepare($recQuery);
    $recStmt->execute([
        ':category' => $book['category'],
        ':id' => $book_id
    ]);
    $recommendations = $recStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les avis pour ce livre
    $reviewsQuery = "SELECT r.*, u.name FROM reviews r 
                    LEFT JOIN users u ON r.user_email = u.email 
                    WHERE r.book_id = :book_id 
                    ORDER BY r.created_at DESC 
                    LIMIT 10";
    $reviewsStmt = $db->prepare($reviewsQuery);
    $reviewsStmt->execute([':book_id' => $book_id]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer la note moyenne
    $avgRatingQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                      FROM reviews WHERE book_id = :book_id";
    $avgStmt = $db->prepare($avgRatingQuery);
    $avgStmt->execute([':book_id' => $book_id]);
    $ratingStats = $avgStmt->fetch(PDO::FETCH_ASSOC);
    
    $avg_rating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : 0;
    $review_count = $ratingStats['review_count'] ?: 0;
    
    // Vérifier les droits d'emprunt/achat
    $canBorrow = false;
    $canBuy = false;
    $hasBorrowedBefore = false;
    $hasPurchased = false;

    if (isset($_SESSION['user_email'])) {
        // Vérifier historique d'emprunt
        $borrowHistoryQuery = "SELECT * FROM user_library 
                              WHERE user_email = :email 
                              AND book_id = :book_id 
                              AND type = 'borrow'";
        $borrowHistoryStmt = $db->prepare($borrowHistoryQuery);
        $borrowHistoryStmt->execute([
            ':email' => $_SESSION['user_email'],
            ':book_id' => $book_id
        ]);
        $hasBorrowedBefore = $borrowHistoryStmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier achat
        $purchaseQuery = "SELECT * FROM user_library 
                         WHERE user_email = :email 
                         AND book_id = :book_id 
                         AND type = 'buy'";
        $purchaseStmt = $db->prepare($purchaseQuery);
        $purchaseStmt->execute([
            ':email' => $_SESSION['user_email'],
            ':book_id' => $book_id
        ]);
        $hasPurchased = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
    }

    $canBorrow = !$hasBorrowedBefore && !$hasPurchased && $book['available'];
    $canBuy = !$hasPurchased && $book['available'];
    
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Traitement de l'ajout d'avis
if ($_POST && isset($_POST['add_review']) && isset($_SESSION['user_email'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_email = $_SESSION['user_email'];
    $user_name = $_SESSION['user_name'];
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $insertReviewQuery = "INSERT INTO reviews (book_id, user_email, user_name, rating, comment, date) 
                                VALUES (:book_id, :user_email, :user_name, :rating, :comment, :date)";
            $insertStmt = $db->prepare($insertReviewQuery);
            $insertStmt->execute([
                ':book_id' => $book_id,
                ':user_email' => $user_email,
                ':user_name' => $user_name,
                ':rating' => $rating,
                ':comment' => $comment,
                ':date' => time() * 1000
            ]);
            
            $_SESSION['message'] = "Votre avis a été ajouté avec succès!";
            header("Location: details.php?id=" . $book_id);
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'avis: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement";
    }
}

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo htmlspecialchars($book['title']); ?> — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .review-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .star-rating {
            cursor: pointer;
        }
        .star-rating .bi-star:hover,
        .star-rating .bi-star-fill:hover {
            transform: scale(1.2);
            transition: transform 0.2s;
        }
        /* Styles pour l'assistant */
        .assistant-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .assistant-modal .modal-dialog {
            max-width: 400px;
            margin: 0;
            position: fixed;
            bottom: 90px;
            right: 20px;
            height: 600px;
        }
        
        .assistant-chat {
            height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
            max-width: 80%;
        }
        
        .user-message {
            background: #007bff;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 2px;
        }
        
        .assistant-message {
            background: white;
            border: 1px solid #dee2e6;
            margin-right: auto;
            border-bottom-left-radius: 2px;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-soft">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>
            <div>
                <?php if ($assistant_available): ?>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#assistantModal">
                    <i class="bi bi-robot me-1"></i> Assistant
                </button>
                <?php endif; ?>
                
                <a href="cart.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-cart3"></i> Panier
                </a>
                <?php if (isset($_SESSION['user_email'])): ?>
                    <span class="me-3">Bonjour, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-primary">Se connecter</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="row align-items-start">
            <div class="col-md-4">
                <img src="<?php echo htmlspecialchars($book['cover']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($book['title']); ?>" onerror="this.onerror=null;this.src='assets/placeholder.png'">
            </div>
            <div class="col-md-8">
                <h2 class="fw-bold text-primary"><?php echo htmlspecialchars($book['title']); ?></h2>
                <p class="small-muted">Auteur · <?php echo htmlspecialchars($book['author']); ?> — Catégorie : <?php echo htmlspecialchars($book['category']); ?></p>
                
                <!-- Note moyenne -->
                <div class="mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rating-stars me-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= floor($avg_rating) ? '-fill' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="fw-bold"><?php echo $avg_rating; ?>/5</span>
                        <span class="text-muted ms-2">(<?php echo $review_count; ?> avis)</span>
                    </div>
                </div>
                
                <p class="mt-3"><?php echo htmlspecialchars($book['description']); ?></p>
                
                <div class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="text-success"><?php echo number_format($book['price'], 3); ?> TND</h4>
                            <small class="text-muted">Prix d'achat</small>
                        </div>
                        <div class="col-md-6">
                            <h4 class="text-info"><?php echo number_format($book['price'] * 0.3, 3); ?> TND</h4>
                            <small class="text-muted">Emprunt (7 jours)</small>
                        </div>
                    </div>
                </div>
                
                <!-- Section des boutons d'action -->
                <div class="d-flex gap-2 flex-wrap mb-4">
                    <!-- Formulaire pour ACHETER -->
                    <form method="POST" action="add_to_cart.php" class="d-inline">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="type" value="buy">
                        <input type="hidden" name="price" value="<?php echo $book['price']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-success btn-lg" 
                                <?php echo !$canBuy ? 'disabled' : ''; ?>
                                title="<?php echo $hasPurchased ? 'Déjà acheté' : (!$book['available'] ? 'Indisponible' : 'Acheter maintenant'); ?>">
                            <i class="bi bi-cart-plus"></i> 
                            <?php if ($hasPurchased): ?>
                                Déjà acheté
                            <?php else: ?>
                                <?php echo $book['available'] ? 'Acheter maintenant' : 'Indisponible'; ?>
                            <?php endif; ?>
                        </button>
                    </form>
                    
                    <!-- Formulaire pour EMPRUNTER -->
                    <form method="POST" action="add_to_cart.php" class="d-inline">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="type" value="borrow">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-info btn-lg" 
                                <?php echo !$canBorrow ? 'disabled' : ''; ?>
                                title="<?php echo $hasBorrowedBefore ? 'Déjà emprunté - Achetez-le' : ($hasPurchased ? 'Déjà acheté' : (!$book['available'] ? 'Indisponible' : 'Emprunter')); ?>">
                            <i class="bi bi-clock"></i> 
                            <?php if ($hasBorrowedBefore): ?>
                                Acheter (déjà emprunté)
                            <?php else: ?>
                                Emprunter (30%)
                            <?php endif; ?>
                        </button>
                    </form>
                    
                    <!-- Formulaire pour WISHLIST -->
                    <form method="POST" action="add_to_wishlist.php" class="d-inline">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger btn-lg">
                            <i class="bi bi-heart"></i> Wishlist
                        </button>
                    </form>
                    
                    <!-- Message d'information -->
                    <?php if ($hasBorrowedBefore && isset($_SESSION['user_email'])): ?>
                    <div class="w-100">
                        <div class="alert alert-warning mt-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Information :</strong> Vous avez déjà emprunté ce livre. Pour y accéder à nouveau, vous devez l'acheter.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <?php if ($book['available']): ?>
                        <span class="badge bg-success fs-6 p-2"><i class="bi bi-check-circle me-1"></i>Disponible</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6 p-2"><i class="bi bi-x-circle me-1"></i>Indisponible</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <!-- Section des avis -->
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-4">Avis des lecteurs (<?php echo $review_count; ?>)</h4>
                
                <?php if (empty($reviews)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Aucun avis pour ce livre pour le moment. Soyez le premier à donner votre avis !
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="card review-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($review['name']); ?></h6>
                                    <div class="rating-stars small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y', $review['date'] / 1000); ?>
                                </small>
                            </div>
                            <p class="card-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Formulaire d'ajout d'avis -->
                <?php if (isset($_SESSION['user_email'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Donner votre avis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Note</label>
                                <div class="star-rating" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star" data-rating="<?php echo $i; ?>" style="font-size: 1.5rem; cursor: pointer;"></i>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="selectedRating" value="5" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Votre commentaire</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="Partagez votre expérience avec ce livre..." required></textarea>
                            </div>
                            <button type="submit" name="add_review" class="btn btn-primary">Publier mon avis</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Vous devez être <a href="login.php" class="alert-link">connecté</a> pour donner votre avis.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-5">

        <!-- Section recommandations -->
        <h5>Livres similaires</h5>
        <div class="row g-3 mt-2">
            <?php if (empty($recommendations)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Aucune recommandation disponible pour le moment.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recommendations as $rec): ?>
                <div class="col-md-3">
                    <div class="card p-2 h-100">
                        <img src="<?php echo htmlspecialchars($rec['cover']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($rec['title']); ?>" 
                             style="height: 200px; object-fit: cover;"
                             onerror="this.onerror=null;this.src='assets/placeholder.png'">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo htmlspecialchars($rec['title']); ?></h6>
                            <p class="text-muted small"><?php echo htmlspecialchars($rec['author']); ?></p>
                            <div class="mt-auto">
                                <div class="fw-bold text-primary mb-2"><?php echo number_format($rec['price'], 3); ?> TND</div>
                                <a href="details.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Voir détails</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Assistant Chat Button -->
    <?php if ($assistant_available): ?>
    <button class="btn btn-primary assistant-btn" data-bs-toggle="modal" data-bs-target="#assistantModal">
        <i class="bi bi-robot"></i>
    </button>

    <!-- Assistant Modal -->
    <div class="modal fade assistant-modal" id="assistantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-robot me-2"></i>Assistant Libro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="assistant-chat" id="assistantChat">
                        <div class="message assistant-message">
                            👋 Bonjour ! Je suis l'assistant virtuel de Libro Online. Comment puis-je vous aider aujourd'hui ?
                        </div>
                    </div>
                    <div class="typing-indicator" id="typingIndicator">
                        <i class="bi bi-three-dots"></i> L'assistant écrit...
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="assistantInput" placeholder="Posez votre question...">
                        <button class="btn btn-primary" id="sendMessage">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <footer class="bg-light border-top pt-5 pb-3">
        <div class="container">
            <div class="row text-start text-md-start gy-4">
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3 text-dark">LibroOnline</h5>
                    <p class="mb-0 text-dark">Bibliothèque numérique moderne permettant de découvrir, consulter, acheter ou emprunter des livres en ligne facilement.</p>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3 text-dark">Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none text-dark">Accueil</a></li>
                        <li><a href="account.php" class="text-decoration-none text-dark">Mon compte</a></li>
                        <li><a href="cart.php" class="text-decoration-none text-dark">Panier</a></li>
                        <li><a href="wishlist.php" class="text-decoration-none text-dark">Wishlist</a></li>
                        <li><a href="borrow.php" class="text-decoration-none text-dark">Emprunts</a></li>
                        <li><a href="login.php" class="text-decoration-none text-dark">Se connecter</a></li>
                        <li><a href="register.php" class="text-decoration-none text-dark">Inscription</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3 text-dark">Contact</h5>
                    <ul class="list-unstyled text-dark">
                        <li><i class="fas fa-phone me-2"></i>55 14 13 55</li>
                        <li><i class="fas fa-envelope me-2"></i>libroonline@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Tunis</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3 text-dark">Réseaux sociaux</h5>
                    <div class="d-flex gap-3 fs-5">
                        <a href="https://www.facebook.com" class="text-dark"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com" class="text-dark"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.x.com" class="text-dark"><i class="fab fa-twitter"></i></a>
                        <a href="mailto:libroonline@gmail.com" class="text-dark"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 text-dark">© 2025 Libro Online - Tous droits réservés</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Système de notation par étoiles
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating .bi-star');
            const selectedRating = document.getElementById('selectedRating');
            
            if (stars.length > 0 && selectedRating) {
                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = this.getAttribute('data-rating');
                        selectedRating.value = rating;
                        
                        // Mettre à jour l'affichage des étoiles
                        stars.forEach(s => {
                            const starRating = s.getAttribute('data-rating');
                            if (starRating <= rating) {
                                s.classList.remove('bi-star');
                                s.classList.add('bi-star-fill');
                            } else {
                                s.classList.remove('bi-star-fill');
                                s.classList.add('bi-star');
                            }
                        });
                    });
                    
                    star.addEventListener('mouseover', function() {
                        const rating = this.getAttribute('data-rating');
                        stars.forEach(s => {
                            const starRating = s.getAttribute('data-rating');
                            if (starRating <= rating) {
                                s.classList.add('text-warning');
                            } else {
                                s.classList.remove('text-warning');
                            }
                        });
                    });
                });
                
                // Réinitialiser au survol de la zone
                document.getElementById('starRating').addEventListener('mouseleave', function() {
                    const currentRating = selectedRating.value;
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= currentRating) {
                            s.classList.add('text-warning');
                        } else {
                            s.classList.remove('text-warning');
                        }
                    });
                });
            }
        });

        <?php if ($assistant_available): ?>
        // Script pour l'assistant
        document.addEventListener('DOMContentLoaded', function() {
            const chat = document.getElementById('assistantChat');
            const input = document.getElementById('assistantInput');
            const sendBtn = document.getElementById('sendMessage');
            const typingIndicator = document.getElementById('typingIndicator');
            
            function addMessage(text, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
                messageDiv.textContent = text;
                chat.appendChild(messageDiv);
                chat.scrollTop = chat.scrollHeight;
            }
            
            function sendMessage() {
                const message = input.value.trim();
                if (!message) return;
                
                addMessage(message, true);
                input.value = '';
                
                // Afficher l'indicateur de frappe
                typingIndicator.style.display = 'block';
                chat.scrollTop = chat.scrollHeight;
                
                // Envoyer la requête à l'assistant
                fetch('assistant_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(response => response.text())
                .then(response => {
                    typingIndicator.style.display = 'none';
                    addMessage(response);
                })
                .catch(error => {
                    typingIndicator.style.display = 'none';
                    addMessage('Désolé, une erreur s\'est produite. Veuillez réessayer.');
                    console.error('Error:', error);
                });
            }
            
            sendBtn.addEventListener('click', sendMessage);
            
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Focus sur l'input quand le modal s'ouvre
            const modal = document.getElementById('assistantModal');
            modal.addEventListener('shown.bs.modal', function() {
                input.focus();
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>