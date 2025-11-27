<?php
require_once '../config.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Statistiques avec gestion des valeurs NULL
$stats = [
    'total_books' => $db->query("SELECT COUNT(*) FROM books")->fetchColumn() ?: 0,
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0,
    'total_orders' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn() ?: 0,
    'active_borrows' => $db->query("SELECT COUNT(*) FROM user_library WHERE type = 'borrow'")->fetchColumn() ?: 0
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Tableau de bord Admin — Libro Online</title>
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
                    <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                </h2>
                <p class="text-muted">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3><?php echo $stats['total_books']; ?></h3>
                                <p class="mb-0">Livres</p>
                            </div>
                            <i class="bi bi-book display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <p class="mb-0">Utilisateurs</p>
                            </div>
                            <i class="bi bi-people display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3><?php echo $stats['total_orders']; ?></h3>
                                <p class="mb-0">Commandes</p>
                            </div>
                            <i class="bi bi-cart display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3><?php echo $stats['active_borrows']; ?></h3>
                                <p class="mb-0">Emprunts actifs</p>
                            </div>
                            <i class="bi bi-clock-history display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="books.php?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Ajouter un livre
                            </a>
                            <a href="users.php" class="btn btn-success">
                                <i class="bi bi-person-plus me-2"></i>Gérer les utilisateurs
                            </a>
                            <a href="orders.php" class="btn btn-info">
                                <i class="bi bi-list-check me-2"></i>Voir les commandes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Activité récente</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT t.*, u.name FROM transactions t 
                                 JOIN users u ON t.user_email = u.email 
                                 ORDER BY t.transaction_date DESC LIMIT 5";
                        $stmt = $db->query($query);
                        $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($recent_activity):
                            foreach ($recent_activity as $activity):
                        ?>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                                    <small class="d-block text-muted">Commande #<?php echo $activity['id']; ?></small>
                                </div>
                                <div class="text-end">
                                    <strong><?php echo number_format($activity['total_amount'], 3); ?> TND</strong>
                                    <small class="d-block text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['transaction_date'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Aucune activité récente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>