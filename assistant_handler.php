<?php
session_start();
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['message'])) {
            $user_message = trim($_POST['message']);
            
            if (!empty($user_message)) {
                // Vérifier si l'assistant est disponible
                if (!isAssistantAvailable()) {
                    echo "❌ L'assistant est temporairement indisponible. Veuillez réessayer plus tard.";
                    exit;
                }
                
                // Récupérer l'email de l'utilisateur - CORRECTION ICI
                $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'anonymous';
                $session_id = session_id();
                
                // DEBUG: Vérifier les valeurs
                error_log("Assistant Handler - Email: " . $user_email);
                error_log("Assistant Handler - Session ID: " . $session_id);
                error_log("Assistant Handler - Message: " . $user_message);
                
                // Préparer la commande Python avec les paramètres supplémentaires
                $escaped_message = escapeshellarg($user_message);
                $escaped_email = escapeshellarg($user_email);
                $escaped_session = escapeshellarg($session_id);
                
                $command = PYTHON_PATH . ' ' . ASSISTANT_SCRIPT_PATH . ' ' . $escaped_message . ' ' . $escaped_email . ' ' . $escaped_session . ' 2>&1';
                
                // DEBUG: Log la commande
                error_log("Assistant Command: " . $command);
                
                // Exécuter la commande
                $output = shell_exec($command);
                
                // DEBUG: Log la sortie
                error_log("Assistant Output: " . $output);
                
                if ($output === null) {
                    echo "❌ Erreur lors de l'exécution de l'assistant.";
                } else {
                    $response = trim($output);
                    echo $response ?: "❌ Je n'ai pas pu traiter votre demande. Veuillez réessayer.";
                }
            } else {
                echo "❌ Veuillez saisir un message.";
            }
        } else {
            echo "❌ Aucun message reçu.";
        }
    } else {
        echo "❌ Méthode non autorisée.";
    }
} catch (Exception $e) {
    error_log("Erreur assistant_handler: " . $e->getMessage());
    echo "❌ Une erreur s'est produite. Veuillez réessayer.";
}
?>