<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_email'])) {
    $_SESSION['error'] = "Vous devez être connecté pour emprunter un livre";
    header("Location: login.php");
    exit();
}

if ($_POST && isset($_POST['book_id']) && isset($_POST['type'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $book_id = $_POST['book_id'];
        $type = $_POST['type'];
        $user_email = $_SESSION['user_email'];
        
        // Vérifier si le livre existe et est disponible
        $bookQuery = "SELECT * FROM books WHERE id = :book_id";
        $bookStmt = $pdo->prepare($bookQuery);
        $bookStmt->execute([':book_id' => $book_id]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            $_SESSION['error'] = "Livre non trouvé";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit();
        }
        
        if (!$book['available']) {
            $_SESSION['error'] = "Ce livre n'est pas disponible";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit();
        }
        
        if ($type === 'borrow') {
            // VÉRIFICATION : L'utilisateur a-t-il déjà emprunté ce livre (historique complet) ?
            $checkBorrowHistoryQuery = "SELECT * FROM user_library 
                                      WHERE user_email = :email 
                                      AND book_id = :book_id 
                                      AND type = 'borrow'";
            $checkBorrowHistoryStmt = $pdo->prepare($checkBorrowHistoryQuery);
            $checkBorrowHistoryStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id
            ]);
            
            $hasBorrowedBefore = $checkBorrowHistoryStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($hasBorrowedBefore) {
                $_SESSION['error'] = "Vous avez déjà emprunté ce livre auparavant. Vous devez l'acheter pour le consulter à nouveau.";
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
                exit();
            }
            
            // Vérifier aussi s'il a acheté le livre
            $checkPurchaseQuery = "SELECT * FROM user_library 
                                 WHERE user_email = :email 
                                 AND book_id = :book_id 
                                 AND type = 'buy'";
            $checkPurchaseStmt = $pdo->prepare($checkPurchaseQuery);
            $checkPurchaseStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id
            ]);
            
            $hasPurchased = $checkPurchaseStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($hasPurchased) {
                $_SESSION['error'] = "Vous avez déjà acheté ce livre. Vous ne pouvez pas l'emprunter.";
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
                exit();
            }
            
            // AJOUTER L'EMPRUNT DANS LA BASE DE DONNÉES
            $current_time = time() * 1000; // Timestamp en millisecondes
            $expiry_time = $current_time + (7 * 24 * 60 * 60 * 1000); // +7 jours en millisecondes
            
            $insertQuery = "INSERT INTO user_library (user_email, book_id, type, date, expiry_date) 
                           VALUES (:email, :book_id, 'borrow', :date, :expiry_date)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id,
                ':date' => $current_time,
                ':expiry_date' => $expiry_time
            ]);
            
            // Vérifier que l'insertion a réussi
            if ($insertStmt->rowCount() > 0) {
                $_SESSION['message'] = "Livre emprunté avec succès pour 7 jours!";
                error_log("✅ Emprunt enregistré : user_email=$user_email, book_id=$book_id, date=$current_time");
            } else {
                throw new Exception("Échec de l'enregistrement de l'emprunt");
            }
            
        } elseif ($type === 'buy') {
            // Vérifier s'il a déjà acheté le livre
            $checkPurchaseQuery = "SELECT * FROM user_library 
                                 WHERE user_email = :email 
                                 AND book_id = :book_id 
                                 AND type = 'buy'";
            $checkPurchaseStmt = $pdo->prepare($checkPurchaseQuery);
            $checkPurchaseStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id
            ]);
            
            $hasPurchased = $checkPurchaseStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($hasPurchased) {
                $_SESSION['error'] = "Vous avez déjà acheté ce livre.";
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
                exit();
            }
            
            // Ajouter au panier pour achat
            $checkCartQuery = "SELECT * FROM cart WHERE user_email = :email AND book_id = :book_id AND type = 'buy'";
            $checkCartStmt = $pdo->prepare($checkCartQuery);
            $checkCartStmt->execute([
                ':email' => $user_email,
                ':book_id' => $book_id
            ]);
            
            $existingCartItem = $checkCartStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCartItem) {
                $_SESSION['message'] = "Livre déjà dans le panier!";
            } else {
                $insertQuery = "INSERT INTO cart (user_email, book_id, type, quantity, price) 
                               VALUES (:email, :book_id, 'buy', 1, :price)";
                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([
                    ':email' => $user_email,
                    ':book_id' => $book_id,
                    ':price' => $book['price']
                ]);
                $_SESSION['message'] = "Livre ajouté au panier!";
            }
        }
        
        // Rediriger vers la page précédente
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
        
    } catch(PDOException $e) {
        error_log("❌ Erreur emprunt: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'emprunt: " . $e->getMessage();
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    } catch(Exception $e) {
        error_log("❌ Erreur emprunt: " . $e->getMessage());
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
}

header("Location: index.php");
exit();
?>