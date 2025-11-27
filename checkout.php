<?php
// Vérifier si l'utilisateur est connecté
session_start();
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

$message = '';
$cart = [];
$total = 0;
$payment_method = 'card'; // Valeur par défaut

// Récupérer le panier depuis la base de données
$query = "SELECT c.*, b.title, b.author, b.cover 
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

// Appliquer code promo
$discount = 0;
$final_total = $total;
if ($_POST && isset($_POST['apply_promo'])) {
    $promo_code = $_POST['promo_code'];
    
    if ($promo_code === 'TEST10') {
        $discount = $total * 0.10;
        $final_total = $total - $discount;
        $message = '<div class="alert alert-success">Code promo appliqué : 10% de réduction!</div>';
    } else {
        $message = '<div class="alert alert-danger">Code promo invalide.</div>';
    }
}

// Traitement du paiement
if ($_POST && isset($_POST['process_payment'])) {
    // Vérifier la méthode de paiement sélectionnée
    if (isset($_POST['payment_method'])) {
        $payment_method = $_POST['payment_method'];
    }
    
    // Vérifier que le panier n'est pas vide
    if (empty($cart)) {
        $message = '<div class="alert alert-danger">Votre panier est vide.</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Créer la transaction avec la méthode de paiement
            $query = "INSERT INTO transactions (user_email, total_amount, promo_code, payment_method, status) 
                      VALUES (:email, :total, :promo, :payment_method, 'completed')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':email' => $_SESSION['user_email'],
                ':total' => $final_total,
                ':promo' => $_POST['promo_code'] ?: null,
                ':payment_method' => $payment_method
            ]);
            
            $transaction_id = $pdo->lastInsertId();
            
            // Transférer les articles vers la bibliothèque
            foreach ($cart as $item) {
                if ($item['type'] === 'buy') {
                    $query = "INSERT INTO user_library (user_email, book_id, type, date) 
                              VALUES (:email, :book_id, 'purchase', :date)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':email' => $_SESSION['user_email'],
                        ':book_id' => $item['book_id'],
                        ':date' => time() * 1000
                    ]);
                } elseif ($item['type'] === 'borrow') {
                    $query = "INSERT INTO user_library (user_email, book_id, type, date, expiry_date) 
                              VALUES (:email, :book_id, 'borrow', :date, :expiry)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':email' => $_SESSION['user_email'],
                        ':book_id' => $item['book_id'],
                        ':date' => time() * 1000,
                        ':expiry' => (time() + (7 * 24 * 60 * 60)) * 1000
                    ]);
                }
            }
            
            // Vider le panier
            $query = "DELETE FROM cart WHERE user_email = :email";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':email' => $_SESSION['user_email']]);
            
            $pdo->commit();
            
            // Envoyer un email de confirmation (optionnel)
            if (file_exists('notification.php')) {
                require_once 'notification.php';
                sendPaymentConfirmation(
                    $_SESSION['user_email'], 
                    $_SESSION['user_name'], 
                    $final_total, 
                    $transaction_id
                );
            }
            
            // Rediriger vers la confirmation
            $_SESSION['payment_success'] = true;
            $_SESSION['transaction_id'] = $transaction_id;
            $_SESSION['payment_method'] = $payment_method;
            header("Location: payment_success.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Erreur lors du traitement du paiement: ' . $e->getMessage() . '</div>';
        }
    }
}

// Récupérer la méthode de paiement sélectionnée pour l'affichage
if ($_POST && isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Paiement — Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .payment-method-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method-card:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-method-card.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .form-check-input {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-soft">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">📚 Libro Online</a>
            <div>
                <a href="cart.php" class="btn btn-sm btn-outline-primary me-2">Retour au panier</a>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Finaliser ma commande</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" id="paymentForm">
                            <h5 class="mb-3">Informations personnelles</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nom complet</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly>
                                </div>
                            </div>

                            <h5 class="mb-3">Méthode de paiement</h5>
                            <div class="mb-4">
                                <!-- Carte bancaire -->
                                <div class="payment-method-card <?php echo $payment_method === 'card' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('card')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="card" value="card" <?php echo $payment_method === 'card' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="card">
                                            <i class="bi bi-credit-card-2-front payment-icon text-primary"></i>Carte bancaire
                                        </label>
                                    </div>
                                    <div class="mt-2 <?php echo $payment_method === 'card' ? '' : 'd-none'; ?>" id="cardDetails">
                                        <div class="row">
                                            <div class="col-12 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Numéro de carte (ex: 1234 5678 9012 3456)" name="card_number">
                                            </div>
                                            <div class="col-6 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="MM/AA (ex: 12/25)" name="card_expiry">
                                            </div>
                                            <div class="col-6 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="CVV (ex: 123)" name="card_cvv">
                                            </div>
                                            <div class="col-12 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Nom sur la carte" name="card_name">
                                            </div>
                                        </div>
                                        <small class="text-muted">Paiement sécurisé - Cette démo n'enregistre pas vos informations bancaires</small>
                                    </div>
                                </div>
                                
                                <!-- PayPal -->
                                <div class="payment-method-card <?php echo $payment_method === 'paypal' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('paypal')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal" <?php echo $payment_method === 'paypal' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="paypal">
                                            <i class="bi bi-paypal payment-icon text-primary"></i>PayPal
                                        </label>
                                    </div>
                                    <div class="mt-2 <?php echo $payment_method === 'paypal' ? '' : 'd-none'; ?>" id="paypalDetails">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Vous serez redirigé vers PayPal pour finaliser votre paiement.
                                        </div>
                                        <div class="mb-2">
                                            <input type="email" class="form-control form-control-sm" placeholder="Email PayPal" name="paypal_email">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Paiement mobile -->
                                <div class="payment-method-card <?php echo $payment_method === 'mobile' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('mobile')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="mobile" value="mobile" <?php echo $payment_method === 'mobile' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mobile">
                                            <i class="bi bi-phone payment-icon text-success"></i>Paiement mobile
                                        </label>
                                    </div>
                                    <div class="mt-2 <?php echo $payment_method === 'mobile' ? '' : 'd-none'; ?>" id="mobileDetails">
                                        <div class="row">
                                            <div class="col-12 mb-2">
                                                <select class="form-select form-select-sm" name="mobile_operator">
                                                    <option value="">Sélectionnez votre opérateur</option>
                                                    <option value="flouci">Flouci</option>
                                                    <option value="paymee">Paymee</option>
                                                    <option value="mytmoney">MyT Money</option>
                                                    <option value="orange">Orange Money</option>
                                                    <option value="ooredoo">Ooredoo Money</option>
                                                </select>
                                            </div>
                                            <div class="col-12 mb-2">
                                                <input type="tel" class="form-control form-control-sm" placeholder="Numéro de téléphone" name="mobile_number">
                                            </div>
                                        </div>
                                        <small class="text-muted">Sélectionnez votre opérateur mobile</small>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3">Code promo</h5>
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <input type="text" name="promo_code" class="form-control" placeholder="TEST10 pour 10% de réduction" value="<?php echo isset($_POST['promo_code']) ? htmlspecialchars($_POST['promo_code']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="apply_promo" class="btn btn-outline-primary w-100">
                                        Appliquer
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="process_payment" class="btn btn-success btn-lg" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                                    <i class="bi bi-lock-fill me-2"></i>Payer <?php echo number_format($final_total, 3); ?> TND
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Récapitulatif</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cart)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Votre panier est vide.
                                <a href="index.php" class="alert-link">Retour au catalogue</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cart as $item): ?>
                            <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
                                <div class="flex-grow-1">
                                    <strong class="d-block"><?php echo htmlspecialchars($item['title']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($item['author']); ?>
                                        <?php if ($item['type'] === 'borrow'): ?>
                                            <span class="badge bg-info ms-1">Emprunt</span>
                                        <?php else: ?>
                                            <span class="badge bg-success ms-1">Achat</span>
                                        <?php endif; ?>
                                        <?php if ($item['type'] === 'buy' && $item['quantity'] > 1): ?>
                                            <span class="badge bg-secondary ms-1">x<?php echo $item['quantity']; ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <strong>
                                        <?php 
                                        if ($item['type'] === 'buy') {
                                            echo number_format($item['price'] * $item['quantity'], 3) . ' TND';
                                        } else {
                                            echo number_format($item['price'], 3) . ' TND';
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Sous-total:</span>
                                    <span><?php echo number_format($total, 3); ?> TND</span>
                                </div>
                                <?php if ($discount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Réduction:</span>
                                    <span>-<?php echo number_format($discount, 3); ?> TND</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                                    <span>Total:</span>
                                    <span class="text-primary"><?php echo number_format($final_total, 3); ?> TND</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(method) {
            // Mettre à jour le radio button
            document.getElementById(method).checked = true;
            
            // Mettre à jour l'apparence des cartes
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Afficher/masquer les détails
            document.querySelectorAll('[id$="Details"]').forEach(detail => {
                detail.classList.add('d-none');
            });
            const detailsElement = document.getElementById(method + 'Details');
            if (detailsElement) {
                detailsElement.classList.remove('d-none');
            }
        }
        
        // Initialiser l'affichage au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (selectedMethod) {
                selectPaymentMethod(selectedMethod.value);
            }
        });

        // Empêcher la soumission du formulaire si des champs requis ne sont pas remplis
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                alert('Veuillez sélectionner une méthode de paiement');
                e.preventDefault();
                return;
            }
            
            const selectedMethodValue = selectedMethod.value;
            let isValid = true;
            
            if (selectedMethodValue === 'card') {
                const cardNumber = document.querySelector('input[name="card_number"]').value;
                const cardExpiry = document.querySelector('input[name="card_expiry"]').value;
                const cardCvv = document.querySelector('input[name="card_cvv"]').value;
                const cardName = document.querySelector('input[name="card_name"]').value;
                
                if (!cardNumber || !cardExpiry || !cardCvv || !cardName) {
                    alert('Veuillez remplir tous les champs de la carte bancaire');
                    isValid = false;
                }
            } else if (selectedMethodValue === 'paypal') {
                const paypalEmail = document.querySelector('input[name="paypal_email"]').value;
                if (!paypalEmail) {
                    alert('Veuillez saisir votre email PayPal');
                    isValid = false;
                }
            } else if (selectedMethodValue === 'mobile') {
                const mobileOperator = document.querySelector('select[name="mobile_operator"]').value;
                const mobileNumber = document.querySelector('input[name="mobile_number"]').value;
                
                if (!mobileOperator || !mobileNumber) {
                    alert('Veuillez sélectionner un opérateur et saisir votre numéro');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>