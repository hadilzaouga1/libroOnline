<?php
session_start();
require_once 'config.php';
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();

// Récupérer la wishlist avec les informations des livres et les notes moyennes
$query = "SELECT w.*, b.title, b.author, b.cover, b.price, b.available, 
                 COALESCE(AVG(r.rating), 0) as avg_rating,
                 COUNT(r.id) as review_count
          FROM wishlist w 
          JOIN books b ON w.book_id = b.id 
          LEFT JOIN reviews r ON b.id = r.book_id
          WHERE w.user_email = :email
          GROUP BY w.id, b.id
          ORDER BY w.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":email", $_SESSION['user_email']);
$stmt->execute();
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_SESSION['message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $message = '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Ma Wishlist — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
        }
        .wishlist-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .book-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
    </style>
</head>
<body class="bg-soft">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>
            <div>
                <span class="me-3">Bonjour, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Déconnexion</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Ma Wishlist</h2>
            <span class="badge bg-primary fs-6"><?php echo count($wishlist); ?> livre(s)</span>
        </div>
        
        <?php echo $message; ?>
        
        <div class="row">
            <?php if (empty($wishlist)): ?>
                <div class="col-12">
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-heart display-1 text-muted mb-3"></i>
                            <h3 class="text-muted">Votre wishlist est vide</h3>
                            <p class="text-muted mb-4">Ajoutez des livres à votre wishlist pour les retrouver facilement plus tard.</p>
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-book me-2"></i>Découvrir le catalogue
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($wishlist as $item): 
                    $avg_rating = round($item['avg_rating'], 1);
                    $review_count = $item['review_count'];
                    $borrow_price = $item['price'] * 0.3;
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 wishlist-card shadow-sm">
                        <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                             class="card-img-top" 
                             style="height: 300px; object-fit: cover;" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             onerror="this.onerror=null;this.src='assets/placeholder.png'">
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($item['author']); ?></p>
                            
                            <!-- Avis et notation -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="rating-stars me-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= floor($avg_rating) ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($review_count > 0): ?>
                                            <?php echo $avg_rating; ?>/5 (<?php echo $review_count; ?> avis)
                                        <?php else: ?>
                                            <span class="text-muted">Aucun avis</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="fw-bold text-success"><?php echo number_format($item['price'], 3); ?> TND</div>
                                    <small class="text-info">Emprunt: <?php echo number_format($borrow_price, 3); ?> TND</small>
                                </div>
                                <?php if ($item['available']): ?>
                                    <span class="badge bg-success">Disponible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Indisponible</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div class="book-actions mb-3">
                                <!-- Formulaire ACHETER -->
                                <form method="POST" action="add_to_cart.php">
                                    <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                    <input type="hidden" name="type" value="buy">
                                    <input type="hidden" name="price" value="<?php echo $item['price']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-success btn-sm w-100" <?php echo !$item['available'] ? 'disabled' : ''; ?>>
                                        <i class="bi bi-cart-plus"></i> Acheter
                                    </button>
                                </form>
                                
                                <!-- Formulaire EMPRUNTER -->
                                <form method="POST" action="add_to_cart.php">
                                    <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                    <input type="hidden" name="type" value="borrow">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-info btn-sm w-100" <?php echo !$item['available'] ? 'disabled' : ''; ?>>
                                        <i class="bi bi-clock"></i> Emprunter
                                    </button>
                                </form>
                            </div>
                            
                            <div class="mt-auto">
                                <div class="d-grid gap-2">
                                    <a href="details.php?id=<?php echo $item['book_id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> Voir détails
                                    </a>
                                    
                                    <!-- Formulaire pour retirer de la wishlist -->
                                    <form method="POST" action="remove_from_wishlist.php">
                                        <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash me-1"></i> Retirer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent border-top-0">
                            <small class="text-muted">
                                Ajouté le <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Statistiques de la wishlist -->
        <?php if (!empty($wishlist)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Statistiques de votre wishlist</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="fw-bold text-primary fs-4"><?php echo count($wishlist); ?></div>
                                <div class="text-muted">Livres au total</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-success fs-4">
                                    <?php echo array_reduce($wishlist, function($carry, $item) {
                                        return $carry + ($item['available'] ? 1 : 0);
                                    }, 0); ?>
                                </div>
                                <div class="text-muted">Disponibles</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-info fs-4">
                                    <?php echo number_format(array_reduce($wishlist, function($carry, $item) {
                                        return $carry + $item['price'];
                                    }, 0), 3); ?> TND
                                </div>
                                <div class="text-muted">Valeur totale</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-warning fs-4">
                                    <?php 
                                    $total_reviews = array_reduce($wishlist, function($carry, $item) {
                                        return $carry + $item['review_count'];
                                    }, 0);
                                    echo $total_reviews;
                                    ?>
                                </div>
                                <div class="text-muted">Avis totaux</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="bg-light border-top pt-5 pb-3">
        <div class="container">
            <div class="row text-start text-md-start gy-4">
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3">LibroOnline</h5>
                    <p class="mb-0">Bibliothèque numérique moderne permettant de découvrir, consulter, acheter ou emprunter des livres en ligne facilement.</p>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3">Liens rapides</h5>
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
                    <h5 class="fw-bold mb-3">Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i>55 14 13 55</li>
                        <li><i class="fas fa-envelope me-2"></i>libroonline@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Tunis</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold mb-3">Réseaux sociaux</h5>
                    <div class="d-flex gap-3 fs-5">
                        <a href="https://www.facebook.com" class="text-white text-decoration-none">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com" class="text-white text-decoration-none">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.x.com" class="text-white text-decoration-none">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="mailto:libroonline@gmail.com" class="text-white text-decoration-none">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0">© 2025 Libro Online - Tous droits réservés</p>
            </div>
        </div>
    </footer>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($assistant_available): ?>
    <script>
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
    </script>
    <?php endif; ?>
</body>
</html>