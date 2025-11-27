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
        
        $book_id = $_POST['book_id'];
        $user_email = $_SESSION['user_email'];
        
        // Vérifier si le livre est déjà dans la wishlist
        $checkQuery = "SELECT * FROM wishlist WHERE user_email = :email AND book_id = :book_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([
            ':email' => $user_email,
            ':book_id' => $book_id
        ]);
        
        $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingItem) {
            $_SESSION['message'] = "Ce livre est déjà dans votre wishlist!";
        } else {
            // Ajouter à la wishlist
            $insertQuery = "INSERT INTO wishlist (user_email, book_id) VALUES (:email, :book_id)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id
            ]);
            $_SESSION['message'] = "Livre ajouté à la wishlist avec succès!";
        }
        
        // Rediriger vers la page précédente
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: wishlist.php");
        }
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout à la wishlist: " . $e->getMessage();
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: wishlist.php");
        }
        exit();
    }
}

header("Location: index.php");
exit();
?>