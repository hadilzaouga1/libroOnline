<?php
session_start();
if (!isset($_SESSION['payment_success'])) {
    header("Location: cart.php");
    exit();
}

require_once 'config.php';

$transaction_id = $_SESSION['transaction_id'] ?? 'N/A';
$payment_method = $_SESSION['payment_method'] ?? 'Carte bancaire';
$amount = $_SESSION['payment_amount'] ?? 0;

// Récupérer les informations de l'utilisateur
$database = new Database();
$db = $database->getConnection();

$userQuery = "SELECT name, email FROM users WHERE email = :email";
$userStmt = $db->prepare($userQuery);
$userStmt->bindParam(":email", $_SESSION['user_email']);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Envoyer l'email de confirmation
$email_sent = false;
if ($user) {
    require_once 'notification.php'; // CORRECTION ICI : notification.php au lieu de notification_functions.php
    $email_sent = sendPaymentConfirmation($user['email'], $user['name'], $amount, $transaction_id);
}

// Réinitialiser les variables de session
unset($_SESSION['payment_success']);
unset($_SESSION['transaction_id']);
unset($_SESSION['payment_method']);
unset($_SESSION['payment_amount']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Paiement Réussi — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-soft">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card shadow-sm border-0">
                    <div class="card-body py-5">
                        <i class="bi bi-check-circle-fill display-1 text-success mb-4"></i>
                        <h1 class="text-success mb-3">Paiement Réussi !</h1>
                        <p class="lead mb-4">Merci pour votre achat. Votre commande a été traitée avec succès.</p>
                        
                        <!-- Notification d'envoi d'email -->
                        <?php if ($email_sent): ?>
                        <div class="alert alert-success mb-4">
                            <i class="bi bi-envelope-check me-2"></i>
                            <strong>Email envoyé !</strong> Un email de confirmation a été envoyé à <strong><?php echo htmlspecialchars($user['email']); ?></strong> avec tous les détails de votre commande.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mb-4">
                            <i class="bi bi-envelope-exclamation me-2"></i>
                            <strong>Notification :</strong> L'email de confirmation n'a pas pu être envoyé, mais votre paiement a bien été traité.
                        </div>
                        <?php endif; ?>
                        
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5>Détails de la commande</h5>
                                <p class="mb-1"><strong>Référence:</strong> #<?php echo $transaction_id; ?></p>
                                <p class="mb-1"><strong>Montant:</strong> <?php echo number_format($amount, 3); ?> TND</p>
                                <p class="mb-1"><strong>Méthode de paiement:</strong> 
                                    <?php 
                                    switch($payment_method) {
                                        case 'card': echo 'Carte bancaire'; break;
                                        case 'paypal': echo 'PayPal'; break;
                                        case 'mobile': echo 'Paiement mobile'; break;
                                        default: echo $payment_method;
                                    }
                                    ?>
                                </p>
                                <p class="mb-0"><strong>Date:</strong> <?php echo date('d/m/Y à H:i'); ?></p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Important :</strong> Vous recevrez un email de confirmation sous peu. Vos livres sont maintenant disponibles dans votre bibliothèque.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="account.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-collection-play me-1"></i>Accéder à ma bibliothèque
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="bi bi-house me-1"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>