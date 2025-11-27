<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}

if ($_POST && isset($_POST['cart_id'])) {
    try {
        require_once 'config.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "DELETE FROM cart WHERE id = :id AND user_email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id' => $_POST['cart_id'],
            ':email' => $_SESSION['user_email']
        ]);
        
        $_SESSION['message'] = "Article retiré du panier avec succès!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

header("Location: cart.php");
exit();
?>