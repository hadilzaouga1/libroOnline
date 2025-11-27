<?php
session_start();
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer tous les livres
$query = "SELECT * FROM books ORDER BY title";
$stmt = $db->query($query);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les infos utilisateur et statistiques
$cart_count = 0;
$user = null;
$stats = null;
$admin_stats = null;

if (isset($_SESSION['user_email'])) {
    // Récupérer les infos utilisateur
    $userQuery = "SELECT * FROM users WHERE email = :email";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(":email", $_SESSION['user_email']);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques utilisateur
    $statsQuery = "SELECT 
        (SELECT COUNT(*) FROM user_library WHERE user_email = :email1) as total_books,
        (SELECT COUNT(*) FROM user_library WHERE user_email = :email2 AND type = 'borrow') as active_borrows,
        (SELECT COUNT(*) FROM transactions WHERE user_email = :email3) as total_orders";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([
        ':email1' => $_SESSION['user_email'],
        ':email2' => $_SESSION['user_email'],
        ':email3' => $_SESSION['user_email']
    ]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Si admin, récupérer les stats admin
    if (isAdmin()) {
        $admin_stats = [
            'total_books' => $db->query("SELECT COUNT(*) FROM books")->fetchColumn() ?: 0,
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0,
            'total_orders' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn() ?: 0,
            'total_revenue' => $db->query("SELECT SUM(total_amount) FROM transactions WHERE status = 'completed'")->fetchColumn() ?: 0,
            'active_borrows' => $db->query("SELECT COUNT(*) FROM user_library WHERE type = 'borrow'")->fetchColumn() ?: 0
        ];
    }
}

// Récupérer les catégories 
$categoriesQuery = "SELECT DISTINCT category FROM books ORDER BY category";
$categoriesStmt = $db->query($categoriesQuery);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les langues 
$languagesQuery = "SELECT DISTINCT language FROM books ORDER BY language";
$languagesStmt = $db->query($languagesQuery);
$languages = $languagesStmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les genres 
$gendersQuery = "SELECT DISTINCT gender FROM books WHERE gender IS NOT NULL AND gender != '' ORDER BY gender";
$gendersStmt = $db->query($gendersQuery);
$genders = $gendersStmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer le compteur du panier si l'utilisateur est connecté
$cart_count = 0;
if (isset($_SESSION['user_email'])) {
    $cartQuery = "SELECT COUNT(*) FROM cart WHERE user_email = :email";
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->bindParam(":email", $_SESSION['user_email']);
    $cartStmt->execute();
    $cart_count = $cartStmt->fetchColumn();
}

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Libro Online — Catalogue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
<body class="bg-soft">

  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>
      
      <div class="d-flex ms-auto me-3">
        <div id="react-search-root">
          <!-- La recherche React sera injectée ici -->
          <div class="input-group">
            <input
                type="search"
                id="react-search-input"
                class="form-control"
                placeholder="🔍 Rechercher un livre, auteur..."
                style="min-width: 280px; border-radius: 8px 0 0 8px;"
            />
            <button class="btn btn-primary" style="border-radius: 0 8px 8px 0;">
                <i class="bi bi-search"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center">
        <?php if ($assistant_available): ?>
        <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#assistantModal">
          <i class="bi bi-robot me-1"></i> Assistant
        </button>
        <?php endif; ?>
        
        <a href="cart.php" class="btn btn-sm btn-outline-primary me-2 position-relative">
          <i class="bi bi-cart3"></i>
          <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?php echo $cart_count; ?>
          </span>
        </a>
        
        <?php if (isset($_SESSION['user_email'])): ?>
          <a href="profile.php" class="btn btn-outline-primary me-2">
            <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
          </a>
          <a href="logout.php" class="btn btn-outline-danger">Déconnexion</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary me-2">Se connecter</a>
          <a href="register.php" class="btn btn-outline-primary">S'inscrire</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

<!-- Tableau de bord utilisateur (si connecté) -->
<?php if (isset($_SESSION['user_email']) && $user): ?>
<div class="container mt-4">
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">👋 Bienvenue, <?php echo htmlspecialchars($user['name']); ?> !</h4>
          <?php if (isAdmin()): ?>
          <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="bi bi-shield-lock me-1"></i>Administrateur
          </span>
          <?php endif; ?>
        </div>
        <a href="profile.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-gear me-1"></i>Gérer mon profil
        </a>
      </div>

      <!-- Statistiques personnelles -->
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="mb-0"><?php echo $stats['total_books']; ?></h3>
                <small>Mes livres</small>
              </div>
              <i class="bi bi-book display-5 opacity-50"></i>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="mb-0"><?php echo $stats['total_orders']; ?></h3>
                <small>Commandes</small>
              </div>
              <i class="bi bi-cart display-5 opacity-50"></i>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="mb-0"><?php echo $stats['active_borrows']; ?></h3>
                <small>Emprunts actifs</small>
              </div>
              <i class="bi bi-clock-history display-5 opacity-50"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="row g-2">
        <div class="col-6 col-md-3">
          <a href="account.php" class="btn btn-primary w-100 action-btn">
            <i class="bi bi-person-circle"></i><br><small>Mon compte</small>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="wishlist.php" class="btn btn-success w-100 action-btn">
            <i class="bi bi-heart"></i><br><small>Wishlist</small>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="cart.php" class="btn btn-info w-100 action-btn">
            <i class="bi bi-cart"></i><br><small>Panier</small>
          </a>
        </div>
        <div class="col-6 col-md-3">
          <a href="borrow.php" class="btn btn-warning w-100 action-btn">
            <i class="bi bi-clock"></i><br><small>Emprunts</small>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Section Admin (si administrateur) -->
  <?php if (isAdmin()): ?>
  <div class="admin-section shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Tableau de bord Administrateur</h4>
      <a href="admin/dashboard.php" class="btn btn-light">
        <i class="bi bi-arrow-right-circle me-1"></i>Accéder au panel admin
      </a>
    </div>

    <!-- Stats Admin -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card bg-white text-dark">
          <div class="card-body text-center p-3">
            <h4 class="mb-1"><?php echo $admin_stats['total_books']; ?></h4>
            <small>Livres totaux</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-white text-dark">
          <div class="card-body text-center p-3">
            <h4 class="mb-1"><?php echo $admin_stats['total_users']; ?></h4>
            <small>Utilisateurs</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-white text-dark">
          <div class="card-body text-center p-3">
            <h4 class="mb-1"><?php echo $admin_stats['total_orders']; ?></h4>
            <small>Commandes</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-white text-dark">
          <div class="card-body text-center p-3">
            <h4 class="mb-1"><?php echo number_format($admin_stats['total_revenue'], 0); ?> TND</h4>
            <small>Chiffre d'affaires</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions Admin rapides -->
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <a href="admin/books.php" class="btn btn-light w-100">
          <i class="bi bi-book"></i> Livres
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="admin/users.php" class="btn btn-light w-100">
          <i class="bi bi-people"></i> Utilisateurs
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="admin/orders.php" class="btn btn-light w-100">
          <i class="bi bi-cart"></i> Commandes
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="admin/borrows.php" class="btn btn-light w-100">
          <i class="bi bi-clock-history"></i> Emprunts
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

  <header class="container py-4 text-center">
    <h1 class="fw-bold text-primary">Catalogue — Découvre des milliers de livres</h1>
    <p class="text-muted">Acheter, emprunter, ajouter à la wishlist et lire en ligne.</p>
  </header>

  <div class="container mb-4 d-flex justify-content-center">
    <div class="d-flex gap-3 align-items-center flex-wrap justify-content-center">
      <select id="filterCategory" class="form-select form-select-sm w-auto">
        <option value="">Toutes catégories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
        <?php endforeach; ?>
      </select>
      
      <select id="filterAvailability" class="form-select form-select-sm w-auto">
        <option value="">Toute disponibilité</option>
        <option value="available">Disponible</option>
        <option value="unavailable">Indisponible</option>
      </select>

      <select id="filterGender" class="form-select form-select-sm w-auto">
        <option value="">Tous les genres</option>
        <?php foreach ($genders as $gender): ?>
          <option value="<?php echo htmlspecialchars($gender); ?>"><?php echo htmlspecialchars($gender); ?></option>
        <?php endforeach; ?>
      </select>

      <select id="filterLang" class="form-select form-select-sm w-auto">
        <option value="">Toutes langues</option>
        <?php foreach ($languages as $language): ?>
          <option value="<?php echo htmlspecialchars($language); ?>"><?php echo htmlspecialchars($language); ?></option>
        <?php endforeach; ?>
      </select>

      <div class="d-flex align-items-center">
        <small class="text-muted me-2 text-nowrap">Trier par :</small>
        <select id="sortBy" class="form-select form-select-sm w-auto">
          <option value="title">Titre</option>
          <option value="author">Auteur</option>
          <option value="price">Prix</option>
          <option value="category">Catégorie</option> 
        </select>
      </div>
    </div>
  </div>

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

    <div id="booksGrid" class="row g-4">
      <?php if (empty($books)): ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucun livre disponible pour le moment.
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($books as $book): 
          $borrow_price = $book['price'] * 0.3;
          
          // Vérifier si l'utilisateur a déjà emprunté ce livre
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
                  ':book_id' => $book['id']
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
                  ':book_id' => $book['id']
              ]);
              $hasPurchased = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
          }
          
          $canBorrow = !$hasBorrowedBefore && !$hasPurchased && $book['available'];
          $canBuy = !$hasPurchased && $book['available'];
        ?>
        <div class="col-6 col-md-3" 
             data-category="<?php echo htmlspecialchars($book['category']); ?>" 
             data-language="<?php echo htmlspecialchars($book['language']); ?>"
             data-gender="<?php echo htmlspecialchars($book['gender'] ?? ''); ?>"
             data-available="<?php echo $book['available'] ? 'available' : 'unavailable'; ?>">
          <div class="book-card">
            <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                 class="book-cover" 
                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                 onerror="this.onerror=null;this.src='assets/placeholder.png'">
            
            <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
            <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
            
            <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
              <span class="small-muted"><?php echo number_format($book['price'], 3); ?> TND</span>
              <?php if ($book['available']): ?>
                <span class="badge-status badge-available">Disponible</span>
              <?php else: ?>
                <span class="badge-status badge-unavailable">Indisponible</span>
              <?php endif; ?>
            </div>

            <div class="book-actions">
              <!-- Formulaire ACHETER -->
              <form method="POST" action="add_to_cart.php">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <input type="hidden" name="type" value="buy">
                <input type="hidden" name="price" value="<?php echo $book['price']; ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-success btn-sm w-100" 
                        <?php echo !$canBuy ? 'disabled' : ''; ?>
                        title="<?php echo $hasPurchased ? 'Déjà acheté' : (!$book['available'] ? 'Indisponible' : 'Acheter'); ?>">
                  <i class="bi bi-cart-plus"></i> 
                  <?php if ($hasPurchased): ?>
                    Déjà acheté
                  <?php else: ?>
                    Acheter
                  <?php endif; ?>
                </button>
              </form>
              
              <!-- Formulaire EMPRUNTER -->
              <form method="POST" action="add_to_cart.php">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <input type="hidden" name="type" value="borrow">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-info btn-sm w-100" 
                        <?php echo !$canBorrow ? 'disabled' : ''; ?>
                        title="<?php echo $hasBorrowedBefore ? 'Déjà emprunté - Achetez-le' : ($hasPurchased ? 'Déjà acheté' : (!$book['available'] ? 'Indisponible' : 'Emprunter')); ?>">
                  <i class="bi bi-clock"></i> 
                  <?php if ($hasBorrowedBefore): ?>
                    Acheter
                  <?php else: ?>
                    Emprunter
                  <?php endif; ?>
                </button>
              </form>
              
              <!-- Formulaire WISHLIST -->
              <form method="POST" action="add_to_wishlist.php">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                  <i class="bi bi-heart"></i> Wishlist
                </button>
              </form>
              
              <!-- Lien DÉTAILS -->
              <a href="details.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-eye"></i> Détails
              </a>
            </div>
            
            <!-- Message d'information -->
            <?php if ($hasBorrowedBefore && isset($_SESSION['user_email'])): ?>
              <div class="mt-2">
                <small class="text-warning">
                  <i class="bi bi-exclamation-triangle"></i> Déjà emprunté
                </small>
              </div>
            <?php endif; ?>
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
  <script src="js/search-react.js"></script> 
  <script src="js/app.js"></script>
  
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