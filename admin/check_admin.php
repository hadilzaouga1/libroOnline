<?php
// check_admin.php - Vérifier le statut admin
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Vérification du statut administrateur</h2>";

// Vérifier le compte admin
$query = "SELECT * FROM users WHERE email = 'admin@libroonline.com'";
$stmt = $db->query($query);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "<h3 style='color: green;'>✅ Compte admin trouvé</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // Vérifier le mot de passe
    if (password_verify('password', $admin['password'])) {
        echo "<p style='color: green;'>✅ Mot de passe correct</p>";
    } else {
        echo "<p style='color: red;'>❌ Mot de passe incorrect</p>";
    }
    
    // Vérifier le rôle
    if ($admin['role'] === 'admin') {
        echo "<p style='color: green;'>✅ Rôle administrateur correct</p>";
    } else {
        echo "<p style='color: red;'>❌ Rôle incorrect: " . $admin['role'] . "</p>";
        
        // Corriger le rôle si nécessaire
        $db->exec("UPDATE users SET role = 'admin' WHERE email = 'admin@libroonline.com'");
        echo "<p style='color: orange;'>⚠️ Rôle corrigé en 'admin'</p>";
    }
} else {
    echo "<h3 style='color: red;'>❌ Compte admin non trouvé</h3>";
}

echo "<hr>";
echo "<h3>Session actuelle:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='profile.php'>Retour au profil</a></p>";
?>