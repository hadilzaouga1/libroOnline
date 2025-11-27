<?php
require_once '../config.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';

// Marquer comme rendu
if (isset($_GET['return'])) {
    $borrow_id = $_GET['return'];
    $query = "DELETE FROM user_library WHERE id = :id";
    $stmt = $db->prepare($query);
    if ($stmt->execute([':id' => $borrow_id])) {
        $message = '<div class="alert alert-success">Emprunt marqué comme rendu!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors du traitement.</div>';
    }
}

// Récupérer tous les emprunts
$query = "
    SELECT ul.*, u.name, u.email, b.title, b.author, b.cover
    FROM user_library ul
    JOIN users u ON ul.user_email = u.email
    JOIN books b ON ul.book_id = b.id
    WHERE ul.type = 'borrow'
    ORDER BY ul.date DESC
";
$stmt = $db->query($query);
$borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$active_borrows = $db->query("SELECT COUNT(*) FROM user_library WHERE type = 'borrow'")->fetchColumn() ?: 0;

// Calculer les emprunts expirés
$expired_borrows = 0;
$now = time() * 1000; // Timestamp en millisecondes
foreach ($borrows as $borrow) {
    $return_date = $borrow['date'] + (7 * 24 * 60 * 60 * 1000); // +7 jours en millisecondes
    if ($now > $return_date) {
        $expired_borrows++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Gestion des emprunts — Admin Libro Online</title>
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
                    <i class="bi bi-clock-history me-2"></i>Gestion des emprunts
                </h2>
                <?php echo $message; ?>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body text-center">
                        <h3><?php echo $active_borrows; ?></h3>
                        <p class="mb-0">Emprunts actifs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body text-center">
                        <h3><?php echo $expired_borrows; ?></h3>
                        <p class="mb-0">Emprunts expirés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des emprunts -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des emprunts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($borrows)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>Aucun emprunt pour le moment.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Livre</th>
                                            <th>Emprunteur</th>
                                            <th>Email</th>
                                            <th>Date d'emprunt</th>
                                            <th>Date de retour</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($borrows as $borrow): 
                                            $borrow_date = $borrow['date'] / 1000; // Convertir en secondes
                                            $return_date_timestamp = $borrow_date + (7 * 24 * 60 * 60); // +7 jours
                                            $borrow_date_formatted = date('d/m/Y', $borrow_date);
                                            $return_date_formatted = date('d/m/Y', $return_date_timestamp);
                                            $is_expired = time() > $return_date_timestamp;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $borrow['cover']; ?>" alt="Couverture" style="width: 40px; height: 60px; object-fit: cover; margin-right: 10px;" onerror="this.src='../assets/placeholder.png'">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($borrow['title']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($borrow['author']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($borrow['name']); ?></td>
                                            <td><?php echo htmlspecialchars($borrow['email']); ?></td>
                                            <td><?php echo $borrow_date_formatted; ?></td>
                                            <td><?php echo $return_date_formatted; ?></td>
                                            <td>
                                                <?php if ($is_expired): ?>
                                                    <span class="badge bg-danger">Expiré</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">En cours</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?return=<?php echo $borrow['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Marquer cet emprunt comme rendu?')">
                                                    <i class="bi bi-check-circle"></i> Rendu
                                                </a>
                                            </td>
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