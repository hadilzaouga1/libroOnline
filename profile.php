<?php
session_start();
require_once 'config.php';

// Vérifier la disponibilité de l'assistant
$assistant_available = isAssistantAvailable();

// Rediriger si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$user = [];

// Récupérer les informations de l'utilisateur
if (isset($_SESSION['user_email'])) {
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $_SESSION['user_email']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Mettre à jour le profil
if ($_POST && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    $query = "UPDATE users SET name = :name, phone = :phone, address = :address WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":address", $address);
    $stmt->bindParam(":email", $_SESSION['user_email']);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Profil mis à jour avec succès!</div>';
        $_SESSION['user_name'] = $name;
        // Recharger les données utilisateur
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $_SESSION['user_email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de la mise à jour.</div>';
    }
}

// Changer le mot de passe
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Les mots de passe ne correspondent pas.</div>';
    } elseif ($user && password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":email", $_SESSION['user_email']);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Mot de passe changé avec succès!</div>';
        } else {
            $message = '<div class="alert alert-danger">Erreur lors du changement de mot de passe.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Mot de passe actuel incorrect.</div>';
    }
}

// Ajouter des informations supplémentaires
if ($_POST && isset($_POST['add_info'])) {
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $interests = $_POST['interests'] ?? '';

    $query = "UPDATE users SET birth_date = :birth_date, gender = :gender, interests = :interests WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":birth_date", $birth_date);
    $stmt->bindParam(":gender", $gender);
    $stmt->bindParam(":interests", $interests);
    $stmt->bindParam(":email", $_SESSION['user_email']);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Informations supplémentaires ajoutées avec succès!</div>';
        // Recharger les données utilisateur
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $_SESSION['user_email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de l\'ajout des informations.</div>';
    }
}

// Supprimer le compte
if ($_POST && isset($_POST['delete_account'])) {
    $confirm_email = $_POST['confirm_email'];
    $confirm_password = $_POST['confirm_password'];

    if ($confirm_email !== $_SESSION['user_email']) {
        $message = '<div class="alert alert-danger">L\'email de confirmation ne correspond pas.</div>';
    } elseif ($user && password_verify($confirm_password, $user['password'])) {
        // Commencer une transaction pour supprimer toutes les données utilisateur
        try {
            $db->beginTransaction();

            // Supprimer les données associées dans toutes les tables
            $tables = ['cart', 'wishlist', 'user_library', 'reviews', 'transactions'];
            foreach ($tables as $table) {
                $query = "DELETE FROM $table WHERE user_email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":email", $_SESSION['user_email']);
                $stmt->execute();
            }

            // Supprimer l'utilisateur (sauf s'il est admin)
            if ($user['role'] !== 'admin') {
                $query = "DELETE FROM users WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":email", $_SESSION['user_email']);
                $stmt->execute();
            }

            $db->commit();

            if ($user['role'] !== 'admin') {
                // Déconnecter et rediriger
                session_destroy();
                header("Location: index.php");
                exit();
            } else {
                $message = '<div class="alert alert-warning">Les comptes administrateurs ne peuvent pas être supprimés.</div>';
            }

        } catch (Exception $e) {
            $db->rollBack();
            $message = '<div class="alert alert-danger">Erreur lors de la suppression du compte: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Mot de passe incorrect.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Gérer mon profil — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .info-card {
            border-left: 4px solid #0d6efd;
        }
        .danger-card {
            border-left: 4px solid #dc3545;
        }
        .success-card {
            border-left: 4px solid #198754;
        }
        .warning-card {
            border-left: 4px solid #ffc107;
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
                <a href="account.php" class="btn btn-sm btn-outline-primary me-2">Mon compte</a>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Retour au catalogue</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-gear me-2"></i>Gérer mon profil</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <!-- Section 1: Informations personnelles (Modification) -->
                        <div class="card info-card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Modifier mes informations personnelles</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Nom complet</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                                            <small class="text-muted">L'email ne peut pas être modifié</small>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Téléphone</label>
                                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Ex: +216 12 345 678">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Adresse</label>
                                            <textarea name="address" class="form-control" rows="2" placeholder="Votre adresse complète"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>Mettre à jour mes informations
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Section 2: Informations supplémentaires (Ajout) -->
                        <div class="card success-card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Ajouter des informations supplémentaires</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Date de naissance</label>
                                            <input type="date" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Genre</label>
                                            <select name="gender" class="form-select">
                                                <option value="">Sélectionner</option>
                                                <option value="Homme" <?php echo (isset($user['gender']) && $user['gender'] == 'Homme') ? 'selected' : ''; ?>>Homme</option>
                                                <option value="Femme" <?php echo (isset($user['gender']) && $user['gender'] == 'Femme') ? 'selected' : ''; ?>>Femme</option>
                                                <option value="Autre" <?php echo (isset($user['gender']) && $user['gender'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Centres d'intérêt</label>
                                            <input type="text" name="interests" class="form-control" value="<?php echo htmlspecialchars($user['interests'] ?? ''); ?>" placeholder="Ex: Romans, Science-fiction, Histoire...">
                                        </div>
                                    </div>
                                    <button type="submit" name="add_info" class="btn btn-success">
                                        <i class="bi bi-plus-lg me-1"></i>Ajouter ces informations
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Section 3: Voir les informations -->
                        <div class="card info-card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-eye me-2 text-info"></i>Mes informations complètes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th class="bg-light">Nom complet</th>
                                                    <td><?php echo htmlspecialchars($user['name'] ?? 'Non renseigné'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Email</th>
                                                    <td><?php echo htmlspecialchars($user['email'] ?? 'Non renseigné'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Téléphone</th>
                                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Adresse</th>
                                                    <td><?php echo htmlspecialchars($user['address'] ?? 'Non renseigné'); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th class="bg-light">Date de naissance</th>
                                                    <td><?php echo htmlspecialchars($user['birth_date'] ?? 'Non renseignée'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Genre</th>
                                                    <td><?php echo htmlspecialchars($user['gender'] ?? 'Non renseigné'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Centres d'intérêt</th>
                                                    <td><?php echo htmlspecialchars($user['interests'] ?? 'Non renseignés'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Membre depuis</th>
                                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Changer le mot de passe -->
                        <div class="card warning-card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-key me-2 text-warning"></i>Changer mon mot de passe</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Mot de passe actuel</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Nouveau mot de passe</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Confirmer le mot de passe</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="bi bi-key me-1"></i>Changer le mot de passe
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Section 5: Bouton d'administration (seulement pour les admins) -->
                        <?php if (isAdmin()): ?>
                        <div class="card danger-card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Accès Administrateur</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Zone réservée aux administrateurs</strong>
                                    <p class="mb-2 mt-2">Accédez au panneau d'administration pour gérer les livres, utilisateurs, commandes et emprunts.</p>
                                    <a href="admin/dashboard.php" class="btn btn-danger btn-lg w-100">
                                        <i class="bi bi-shield-check me-2"></i>Accéder à l'administration
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Section 6: Supprimer le compte -->
                        <div class="card danger-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-trash me-2 text-danger"></i>Supprimer mon compte</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Attention : Cette action est irréversible !</strong>
                                    <p class="mb-2">La suppression de votre compte entraînera :</p>
                                    <ul>
                                        <li>La perte de toutes vos données personnelles</li>
                                        <li>La suppression de votre historique d'achats et d'emprunts</li>
                                        <li>La suppression de votre wishlist et panier</li>
                                        <li>L'impossibilité de récupérer votre compte</li>
                                    </ul>
                                    
                                    <form method="POST" onsubmit="return confirm('Êtes-vous ABSOLUMENT SÛR de vouloir supprimer votre compte ? Cette action est irréversible !');">
                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Confirmez votre email</label>
                                                <input type="email" name="confirm_email" class="form-control" placeholder="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Confirmez votre mot de passe</label>
                                                <input type="password" name="confirm_password" class="form-control" required>
                                            </div>
                                        </div>
                                        <button type="submit" name="delete_account" class="btn btn-danger w-100">
                                            <i class="bi bi-trash me-1"></i>Supprimer définitivement mon compte
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer standard -->
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
                        <li><a href="profile.php" class="text-decoration-none text-dark">Mon Profil</a></li>
                        <li><a href="cart.php" class="text-decoration-none text-dark">Panier</a></li>
                        <li><a href="wishlist.php" class="text-decoration-none text-dark">Wishlist</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a href="admin/dashboard.php" class="text-decoration-none text-danger fw-bold">Administration</a></li>
                        <?php endif; ?>
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