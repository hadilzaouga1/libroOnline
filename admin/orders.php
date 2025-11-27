<?php
require_once '../config.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Récupérer toutes les transactions
$query = "
    SELECT t.*, u.name, u.email 
    FROM transactions t 
    JOIN users u ON t.user_email = u.email 
    ORDER BY t.transaction_date DESC
";
$stmt = $db->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques avec gestion des valeurs NULL
$total_revenue_result = $db->query("SELECT SUM(total_amount) FROM transactions WHERE status = 'completed'")->fetchColumn();
$total_revenue = $total_revenue_result ?: 0; // Si NULL, mettre 0

$total_orders = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn() ?: 0;
$pending_orders = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Gestion des commandes — Admin Libro Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-soft">
    <?php include 'admin_nav.php'; ?>

    <main class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-primary">
                    <i class="bi bi-cart me-2"></i>Gestion des commandes
                </h2>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body text-center">
                        <h3><?php echo $total_orders; ?></h3>
                        <p class="mb-0">Commandes totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($total_revenue, 3); ?> TND</h3>
                        <p class="mb-0">Chiffre d'affaires</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body text-center">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p class="mb-0">Commandes en attente</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des commandes -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Historique des commandes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>Aucune commande pour le moment.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Email</th>
                                            <th>Montant</th>
                                            <th>Code promo</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td>#<?php echo $transaction['id']; ?></td>
                                            <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['email']); ?></td>
                                            <td><strong><?php echo number_format($transaction['total_amount'], 3); ?> TND</strong></td>
                                            <td>
                                                <?php if ($transaction['promo_code']): ?>
                                                    <span class="badge bg-info"><?php echo $transaction['promo_code']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Aucun</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_badge = [
                                                    'completed' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'failed' => 'bg-danger'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $status_badge[$transaction['status']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $transaction['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>