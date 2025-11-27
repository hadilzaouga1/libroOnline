<?php
session_start();
require_once 'config.php';
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Récupérer les informations utilisateur
$userQuery = "SELECT * FROM users WHERE email = :email";
$userStmt = $db->prepare($userQuery);
$userStmt->bindParam(":email", $_SESSION['user_email']);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur existe
if (!$user) {
    // Rediriger vers la déconnexion si l'utilisateur n'existe pas
    header("Location: logout.php");
    exit();
}

// Récupérer la bibliothèque de l'utilisateur
$libraryQuery = "SELECT ul.*, b.title, b.author, b.cover 
                 FROM user_library ul 
                 JOIN books b ON ul.book_id = b.id 
                 WHERE ul.user_email = :email 
                 ORDER BY ul.created_at DESC";
$libraryStmt = $db->prepare($libraryQuery);
$libraryStmt->bindParam(":email", $_SESSION['user_email']);
$libraryStmt->execute();
$library = $libraryStmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Mon compte — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
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
                
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Se déconnecter</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <h2>Mon compte</h2>
        <p class="text-muted">Bonjour, <?php echo htmlspecialchars($user['name'] ?? 'Utilisateur'); ?></p>

        <div class="row">
            <div class="col-md-8">
                <h5>Mes livres</h5>
                <div class="list-group mb-4">
                    <?php if (empty($library)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Aucun livre dans votre bibliothèque pour le moment.
                            <a href="index.php" class="alert-link">Découvrez notre catalogue</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($library as $item): 
                            $borrowEnd = $item['expiry_date'] ? date('d/m/Y', $item['expiry_date'] / 1000) : null;
                            $isExpired = $item['expiry_date'] && (time() * 1000 > $item['expiry_date']);
                        ?>
                            <div class="list-group-item d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($item['cover'] ?? 'assets/placeholder.png'); ?>" 
                                     style="width:60px;height:90px;object-fit:cover" 
                                     class="me-3 rounded" 
                                     onerror="this.onerror=null;this.src='assets/placeholder.png'">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($item['title'] ?? 'Titre inconnu'); ?>
                                        <span class="badge <?php echo $item['type'] === 'borrow' ? 'bg-info' : 'bg-success'; ?>">
                                            <?php echo $item['type'] === 'borrow' ? 'Emprunt' : 'Achat'; ?>
                                        </span>
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger">Expiré</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small-muted"><?php echo htmlspecialchars($item['author'] ?? 'Auteur inconnu'); ?></div>
                                    <div class="small-muted mt-1">
                                        <?php if ($item['type'] === 'borrow'): ?>
                                            Emprunté le <?php echo date('d/m/Y', $item['date'] / 1000); ?>
                                            <?php if ($borrowEnd): ?>
                                                — Retour avant le <?php echo $borrowEnd; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Acheté le <?php echo date('d/m/Y', $item['date'] / 1000); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <h5>Actions rapides</h5>
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-gear me-2"></i>Gérer mon profil
                    </a>
                    <a href="wishlist.php" class="btn btn-outline-info">
                        <i class="bi bi-heart me-2"></i>Ma wishlist
                    </a>
                    <a href="cart.php" class="btn btn-outline-success">
                        <i class="bi bi-cart me-2"></i>Mon panier
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="btn btn-outline-danger">
                            <i class="bi bi-shield-lock me-2"></i>Administration
                        </a>
                    <?php endif; ?>
                </div>
            </div>
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