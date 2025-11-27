<?php
require_once '../config.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';

// Ajouter un livre
if ($_POST && isset($_POST['add_book'])) {
    $id = 'b' . (time() % 1000);
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $available = isset($_POST['available']) ? 1 : 0;
    $cover = $_POST['cover'];
    $description = $_POST['description'];
    $language = $_POST['language'];

    $query = "INSERT INTO books (id, title, author, category, price, available, cover, description, language) 
              VALUES (:id, :title, :author, :category, :price, :available, :cover, :description, :language)";
    
    $stmt = $db->prepare($query);
    if ($stmt->execute([
        ':id' => $id,
        ':title' => $title,
        ':author' => $author,
        ':category' => $category,
        ':price' => $price,
        ':available' => $available,
        ':cover' => $cover,
        ':description' => $description,
        ':language' => $language
    ])) {
        $message = '<div class="alert alert-success">Livre ajouté avec succès!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de l\'ajout du livre.</div>';
    }
}

// Supprimer un livre
if (isset($_GET['delete'])) {
    $book_id = $_GET['delete'];
    $query = "DELETE FROM books WHERE id = :id";
    $stmt = $db->prepare($query);
    if ($stmt->execute([':id' => $book_id])) {
        $message = '<div class="alert alert-success">Livre supprimé avec succès!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
    }
}

// Récupérer tous les livres
$query = "SELECT * FROM books ORDER BY created_at DESC";
$stmt = $db->query($query);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Gestion des livres — Admin Libro Online</title>
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
                    <i class="bi bi-book me-2"></i>Gestion des livres
                </h2>
                <?php echo $message; ?>
            </div>
        </div>

        <!-- Formulaire d'ajout -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Ajouter un nouveau livre</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Titre</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Auteur</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <input type="text" name="category" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Prix (TND)</label>
                                    <input type="number" step="0.001" name="price" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Langue</label>
                                    <select name="language" class="form-select">
                                        <option value="FR">Français</option>
                                        <option value="EN">Anglais</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">URL de la couverture</label>
                                    <input type="url" name="cover" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Disponibilité</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="available" id="available" checked>
                                        <label class="form-check-label" for="available">Disponible</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_book" class="btn btn-success">
                                <i class="bi bi-plus-circle me-1"></i>Ajouter le livre
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des livres -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des livres (<?php echo count($books); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Couverture</th>
                                        <th>Titre</th>
                                        <th>Auteur</th>
                                        <th>Catégorie</th>
                                        <th>Prix</th>
                                        <th>Disponible</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['id']; ?></td>
                                        <td>
                                            <img src="<?php echo $book['cover']; ?>" alt="Couverture" style="width: 50px; height: 70px; object-fit: cover;" onerror="this.src='../assets/placeholder.png'">
                                        </td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                                        <td><?php echo number_format($book['price'], 3); ?> TND</td>
                                        <td>
                                            <?php if ($book['available']): ?>
                                                <span class="badge bg-success">Oui</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?delete=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce livre?')">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </a>
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