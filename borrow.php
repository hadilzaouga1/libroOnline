<?php
session_start();
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();

// Récupérer les emprunts de l'utilisateur connecté
$borrows = [];
if (isset($_SESSION['user_email'])) {
    $query = "SELECT ul.*, b.title, b.author, b.cover, b.price 
              FROM user_library ul 
              JOIN books b ON ul.book_id = b.id 
              WHERE ul.user_email = :email AND ul.type = 'borrow'
              ORDER BY ul.date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $_SESSION['user_email']);
    $stmt->execute();
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Vérifier ce qui est récupéré
    error_log("📚 Emprunts récupérés pour " . $_SESSION['user_email'] . ": " . count($borrows));
}

// Traitement du retour de livre
if ($_POST && isset($_POST['return_book']) && isset($_SESSION['user_email'])) {
    $borrow_id = $_POST['borrow_id'];
    
    try {
        // Vérifier que l'emprunt appartient bien à l'utilisateur
        $verifyQuery = "SELECT * FROM user_library WHERE id = :id AND user_email = :email";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->execute([
            ':id' => $borrow_id,
            ':email' => $_SESSION['user_email']
        ]);
        
        $borrow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$borrow) {
            $_SESSION['error'] = "Emprunt non trouvé ou vous n'avez pas la permission de le modifier";
            header("Location: borrow.php");
            exit();
        }
        
        // Supprimer l'emprunt
        $deleteQuery = "DELETE FROM user_library WHERE id = :id AND user_email = :email";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([
            ':id' => $borrow_id,
            ':email' => $_SESSION['user_email']
        ]);
        
        if ($deleteStmt->rowCount() > 0) {
            $_SESSION['message'] = "Livre retourné avec succès!";
            error_log("✅ Retour emprunt: ID $borrow_id supprimé");
        } else {
            $_SESSION['error'] = "Erreur lors du retour du livre";
        }
        
        header("Location: borrow.php");
        exit();
        
    } catch(PDOException $e) {
        error_log("❌ Erreur retour: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors du retour: " . $e->getMessage();
        header("Location: borrow.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes Emprunts - Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-soft">

<!-- NAV -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>

        <div class="d-flex align-items-center ms-auto">
            <?php if ($assistant_available): ?>
            <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#assistantModal">
                <i class="bi bi-robot me-1"></i> Assistant
            </button>
            <?php endif; ?>
            
            <a href="cart.php" class="btn btn-sm btn-outline-primary me-2 position-relative">
                <i class="bi bi-cart3"></i>
                <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php
                    if (isset($_SESSION['user_email'])) {
                        $cartQuery = "SELECT COUNT(*) FROM cart WHERE user_email = :email";
                        $cartStmt = $db->prepare($cartQuery);
                        $cartStmt->bindParam(":email", $_SESSION['user_email']);
                        $cartStmt->execute();
                        echo $cartStmt->fetchColumn();
                    } else {
                        echo "0";
                    }
                    ?>
                </span>
            </a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="profile.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Déconnexion</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-primary">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="container py-4 text-center">
    <h1 class="fw-bold text-primary">Mes emprunts (7 jours)</h1>
    <p class="text-muted">Les livres empruntés expirent automatiquement après 7 jours.</p>
</header>

<main class="container mb-5">
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

    <?php if (!isset($_SESSION['user_email'])): ?>
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Vous devez être <a href="login.php" class="alert-link">connecté</a> pour voir vos emprunts.
        </div>
    <?php elseif (empty($borrows)): ?>
        <div class="text-center">
            <div class="card p-5">
                <i class="bi bi-book display-1 text-muted mb-3"></i>
                <h3 class="text-muted">Aucun emprunt en cours</h3>
                <p class="text-muted">Découvrez notre catalogue pour emprunter des livres.</p>
                <a href="index.php" class="btn btn-primary">Voir le catalogue</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4" id="borrowGrid">
            <?php 
            $now = time() * 1000; // Timestamp en millisecondes
            foreach ($borrows as $borrow): 
                $borrow_date = $borrow['date'];
                $expiry_date = $borrow['expiry_date'] ?? ($borrow_date + (7 * 24 * 60 * 60 * 1000));
                $is_expired = $now > $expiry_date;
                $days_remaining = ceil(($expiry_date - $now) / (1000 * 60 * 60 * 24));
            ?>
            <div class="col-md-6">
                <div class="card h-100 <?php echo $is_expired ? 'border-danger' : 'border-success'; ?> shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <img src="<?php echo htmlspecialchars($borrow['cover']); ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?php echo htmlspecialchars($borrow['title']); ?>"
                                     onerror="this.onerror=null;this.src='assets/placeholder.png'">
                            </div>
                            <div class="col-md-9">
                                <h5 class="card-title"><?php echo htmlspecialchars($borrow['title']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($borrow['author']); ?></p>
                                
                                <div class="mb-2">
                                    <strong>Emprunté le:</strong> <?php echo date('d/m/Y à H:i', $borrow_date / 1000); ?>
                                </div>
                                
                                <div class="mb-2 <?php echo $is_expired ? 'text-danger' : 'text-success'; ?>">
                                    <strong>À retourner avant:</strong> <?php echo date('d/m/Y à H:i', $expiry_date / 1000); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <?php if ($is_expired): ?>
                                        <span class="badge bg-danger">EXPIRÉ</span>
                                        <small class="text-danger d-block">Retard de <?php echo abs($days_remaining); ?> jour(s)</small>
                                    <?php else: ?>
                                        <span class="badge bg-success">En cours</span>
                                        <small class="text-success d-block"><?php echo $days_remaining; ?> jour(s) restant(s)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                    <button type="submit" name="return_book" class="btn btn-outline-primary btn-sm" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir marquer ce livre comme rendu?')">
                                        <i class="bi bi-check-circle"></i> Marquer comme rendu
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Statistiques -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistiques des emprunts</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="fw-bold text-primary fs-4"><?php echo count($borrows); ?></div>
                                <div class="text-muted">Emprunts totaux</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-success fs-4">
                                    <?php 
                                    $active = array_filter($borrows, function($b) use ($now) {
                                        $expiry = $b['expiry_date'] ?? ($b['date'] + (7 * 24 * 60 * 60 * 1000));
                                        return $now <= $expiry;
                                    });
                                    echo count($active);
                                    ?>
                                </div>
                                <div class="text-muted">Emprunts actifs</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-danger fs-4">
                                    <?php 
                                    $expired = array_filter($borrows, function($b) use ($now) {
                                        $expiry = $b['expiry_date'] ?? ($b['date'] + (7 * 24 * 60 * 60 * 1000));
                                        return $now > $expiry;
                                    });
                                    echo count($expired);
                                    ?>
                                </div>
                                <div class="text-muted">Emprunts expirés</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-info fs-4">
                                    <?php 
                                    $total_value = array_sum(array_map(function($b) { 
                                        return $b['price'] * 0.3; 
                                    }, $borrows));
                                    echo number_format($total_value, 3);
                                    ?> TND
                                </div>
                                <div class="text-muted">Valeur totale</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- FOOTER -->
<footer class="bg-light border-top pt-5 pb-3">
    <div class="container">
        <div class="row text-start text-md-start gy-4">
            <div class="col-md-3">
                <h5 class="fw-bold mb-3">LibroOnline</h5>
                <p class="mb-0">Libro Online est une bibliothèque numérique moderne permettant de découvrir, consulter, acheter ou emprunter des livres en ligne facilement.</p>
            </div>
            <div class="col-md-3">
                <h5 class="fw-bold mb-3">Liens rapides</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-decoration-none text-dark">Accueil</a></li>
                    <li><a href="profile.php" class="text-decoration-none text-dark">Mon compte</a></li>
                    <li><a href="cart.php" class="text-decoration-none text-dark">Panier</a></li>
                    <li><a href="wishlist.php" class="text-decoration-none text-dark">Wishlist</a></li>
                    <li><a href="borrow.php" class="text-decoration-none text-dark">Emprunts</a></li>
                    <li><a href="login.php" class="text-decoration-none text-dark">Se connecter</a></li>
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
                    <a href="https://www.facebook.com" class="text-dark"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com" class="text-dark"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.x.com" class="text-dark"><i class="fab fa-twitter"></i></a>
                    <a href="mailto:libroonline@gmail.com" class="text-dark"><i class="fas fa-envelope"></i></a>
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