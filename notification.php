<?php
function sendPaymentConfirmation($email, $name, $amount, $transaction_id) {
    $subject = "Confirmation de paiement - Libro Online";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .amount { font-size: 24px; color: #198754; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📚 Libro Online</h1>
                <p>Confirmation de votre paiement</p>
            </div>
            <div class='content'>
                <h2>Bonjour $name,</h2>
                <p>Votre paiement a été traité avec succès.</p>
                
                <div style='background: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3>Détails de la transaction</h3>
                    <p><strong>Référence:</strong> #$transaction_id</p>
                    <p><strong>Montant:</strong> <span class='amount'>" . number_format($amount, 3) . " TND</span></p>
                    <p><strong>Date:</strong> " . date('d/m/Y à H:i') . "</p>
                </div>
                
                <p>Vous pouvez maintenant accéder à vos livres depuis votre <a href='http://localhost/mon_projet/libro-online/account.php'>bibliothèque personnelle</a>.</p>
                
                <p>Merci pour votre confiance,<br>L'équipe Libro Online</p>
            </div>
            <div class='footer'>
                <p>© 2025 Libro Online. Tous droits réservés.</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

function sendBorrowReminder($email, $name, $book_title, $return_date) {
    $subject = "Rappel - Date de retour approche - Libro Online";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #ffc107; color: #000; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .important { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📚 Libro Online</h1>
                <p>Rappel de date de retour</p>
            </div>
            <div class='content'>
                <h2>Bonjour $name,</h2>
                
                <div class='important'>
                    <h3>📖 Livre à retourner</h3>
                    <p><strong>Titre:</strong> $book_title</p>
                    <p><strong>Date de retour:</strong> $return_date</p>
                </div>
                
                <p>N'oubliez pas de retourner votre livre emprunté avant la date indiquée.</p>
                
                <p>Cordialement,<br>L'équipe Libro Online</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Libro Online <noreply@libroonline.com>" . "\r\n";
    $headers .= "Reply-To: libroonline@gmail.com" . "\r\n";
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        error_log("EMAIL SIMULÉ - To: $to, Subject: $subject");
        return true;
    } else {
        // En production, envoyer l'email réel
        return mail($to, $subject, $message, $headers);
    }
}
?>