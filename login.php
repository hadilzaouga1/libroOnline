<?php
session_start();
require_once 'config.php';

$error = '';

if ($_POST && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupérer l'utilisateur avec son rôle
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Définir toutes les informations de session
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_id'] = $user['id'];
        
        // Rediriger vers la page appropriée
        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: index.html");
        }
        exit();
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Connexion — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-soft">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-4" href="index.php">📚 Libro Online</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navBarLibro">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navBarLibro">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="index.php">Accueil</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <h4 class="mb-3">Se connecter</h4>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Connexion</button>
                    </form>

                    <hr class="my-4">

                    <h5>Pas encore de compte ?</h5>
                    <a href="register.php" class="btn btn-outline-primary w-100 mt-2">Créer un compte</a>
                </div>
            </div>
        </div>
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
            <li><a href="account.php" class="text-decoration-none text-dark">Account</a></li>
            <li><a href="cart.php" class="text-decoration-none text-dark">Panier</a></li>
            <li><a href="wishlist.php" class="text-decoration-none text-dark">Wishlist</a></li>
            <li><a href="borrow.php" class="text-decoration-none text-dark">Emprunt</a></li>
            <li><a href="details.php" class="text-decoration-none text-dark">Détails</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>