<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();

// Récupérer le panier depuis la base de données
$cart = [];
$total = 0;

$query = "SELECT c.*, b.title, b.author, b.cover, b.available 
          FROM cart c 
          JOIN books b ON c.book_id = b.id 
          WHERE c.user_email = :email";
$stmt = $pdo->prepare($query);
$stmt->bindParam(":email", $_SESSION['user_email']);
$stmt->execute();
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer le total
foreach ($cart as $item) {
    if ($item['type'] === 'buy') {
        $total += $item['price'] * $item['quantity'];
    } elseif ($item['type'] === 'borrow') {
        $total += $item['price'];
    }
}

// Vérifier s'il y a un message de session
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
  <title>Panier — Libro Online</title>
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
        <span class="me-3">Bonjour, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger">Déconnexion</a>
      </div>
    </div>
  </nav>

  <main class="container py-5">
    <h2>Mon panier</h2>
    
    <?php echo $message; ?>
    
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0">Articles dans votre panier</h5>
      </div>
      <div class="card-body">
        <?php if (empty($cart)): ?>
          <div class="text-center py-4">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h4 class="text-muted mt-3">Votre panier est vide</h4>
            <p class="text-muted">Ajoutez des livres à votre panier pour les voir apparaître ici.</p>
            <a href="index.php" class="btn btn-primary">Découvrir des livres</a>
          </div>
        <?php else: ?>
          <?php foreach ($cart as $item): ?>
          <div class="row align-items-center mb-3 pb-3 border-bottom">
            <div class="col-md-2">
              <?php if (!empty($item['cover'])): ?>
                <img src="<?php echo htmlspecialchars($item['cover']); ?>" alt="Couverture" class="img-fluid rounded" style="max-height: 80px; object-fit: cover;">
              <?php else: ?>
                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 80px;">
                  <i class="bi bi-book text-white"></i>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-4">
              <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
              <p class="text-muted mb-0 small"><?php echo htmlspecialchars($item['author']); ?></p>
              <span class="badge <?php echo $item['type'] === 'buy' ? 'bg-success' : 'bg-info'; ?>">
                <?php echo $item['type'] === 'buy' ? 'Achat' : 'Emprunt'; ?>
              </span>
              <?php if (!$item['available']): ?>
                <span class="badge bg-warning ms-1">Indisponible</span>
              <?php endif; ?>
            </div>
            <div class="col-md-2">
              <span class="fw-bold"><?php echo number_format($item['price'], 3); ?> TND</span>
            </div>
            <div class="col-md-2">
              <?php if ($item['type'] === 'buy'): ?>
                <span>Quantité: <?php echo $item['quantity']; ?></span>
              <?php else: ?>
                <span class="text-muted">7 jours</span>
              <?php endif; ?>
            </div>
            <div class="col-md-2 text-end">
              <form method="POST" action="remove_from_cart.php" style="display: inline;">
                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i> Supprimer
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($cart)): ?>
    <div class="card p-3 shadow-sm">
      <h5>Récapitulatif</h5>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-2">
          <span>Sous-total:</span>
          <span><?php echo number_format($total, 3); ?> TND</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Frais de service:</span>
          <span>0.000 TND</span>
        </div>
        <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
          <span>Total:</span>
          <span class="text-primary"><?php echo number_format($total, 3); ?> TND</span>
        </div>
      </div>
      
      <div class="mb-3">
        <label for="promo" class="form-label">Code promo</label>
        <input id="promo" class="form-control form-control-sm" placeholder="Saisir un code promo (TEST10)">
      </div>
      
      <div class="d-grid gap-2">
        <a href="checkout.php" class="btn btn-success btn-lg">
          <i class="bi bi-credit-card me-2"></i>Procéder au paiement
        </a>
        <a href="index.php" class="btn btn-outline-primary">
          <i class="bi bi-arrow-left me-2"></i>Continuer mes achats
        </a>
      </div>
      
      <small class="d-block text-muted mt-2">
        Le paiement est simulé dans cette démo. Pour un projet réel, intégrer un service de paiement sécurisé.
      </small>
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