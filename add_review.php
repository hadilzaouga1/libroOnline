<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if ($_POST && isset($_POST['book_id']) && isset($_POST['rating']) && isset($_POST['comment'])) {
    $book_id = $_POST['book_id'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_email = $_SESSION['user_email'];
    $user_name = $_SESSION['user_name'];
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Vérifier si l'utilisateur a déjà donné un avis pour ce livre
            $checkQuery = "SELECT id FROM reviews WHERE book_id = :book_id AND user_email = :user_email";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([
                ':book_id' => $book_id,
                ':user_email' => $user_email
            ]);
            
            if ($checkStmt->fetch()) {
                $_SESSION['error'] = "Vous avez déjà donné votre avis pour ce livre.";
            } else {
                // Ajouter l'avis
                $insertQuery = "INSERT INTO reviews (book_id, user_email, user_name, rating, comment, date) 
                              VALUES (:book_id, :user_email, :user_name, :rating, :comment, :date)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    ':book_id' => $book_id,
                    ':user_email' => $user_email,
                    ':user_name' => $user_name,
                    ':rating' => $rating,
                    ':comment' => $comment,
                    ':date' => time() * 1000
                ]);
                
                $_SESSION['message'] = "Votre avis a été ajouté avec succès!";
            }
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'avis: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
    }
    
    // Rediriger vers la page du livre
    header("Location: details.php?id=" . $book_id);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>