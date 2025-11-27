<?php
require_once '../config.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';

// Supprimer un utilisateur
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $query = "DELETE FROM users WHERE id = :id AND role != 'admin'";
    $stmt = $db->prepare($query);
    if ($stmt->execute([':id' => $user_id])) {
        $message = '<div class="alert alert-success">Utilisateur supprimé avec succès!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
    }
}

// Changer le rôle
if (isset($_GET['change_role'])) {
    $user_id = $_GET['change_role'];
    $new_role = $_GET['role'];
    
    $query = "UPDATE users SET role = :role WHERE id = :id";
    $stmt = $db->prepare($query);
    if ($stmt->execute([':role' => $new_role, ':id' => $user_id])) {
        $message = '<div class="alert alert-success">Rôle modifié avec succès!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors du changement de rôle.</div>';
    }
}

// Récupérer tous les utilisateurs
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Gestion des utilisateurs — Admin Libro Online</title>
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
                    <i class="bi bi-people me-2"></i>Gestion des utilisateurs
                </h2>
                <?php echo $message; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des utilisateurs (<?php echo count($users); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Rôle</th>
                                        <th>Inscrit le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Administrateur</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Utilisateur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <a href="?change_role=<?php echo $user['id']; ?>&role=admin" class="btn btn-sm btn-warning" onclick="return confirm('Donner les droits admin à cet utilisateur?')">
                                                        <i class="bi bi-shield-check"></i> Admin
                                                    </a>
                                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet utilisateur?')">
                                                        <i class="bi bi-trash"></i> Supprimer
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Actions limitées</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>