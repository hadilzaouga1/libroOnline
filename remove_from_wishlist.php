<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if ($_POST && isset($_POST['book_id'])) {
    try {
        require_once 'config.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "DELETE FROM wishlist WHERE user_email = :email AND book_id = :book_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':email' => $_SESSION['user_email'],
            ':book_id' => $_POST['book_id']
        ]);
        
        $_SESSION['message'] = "Livre retiré de la wishlist avec succès!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Rediriger vers la page précédente ou la wishlist
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: wishlist.php");
}
exit();
?>