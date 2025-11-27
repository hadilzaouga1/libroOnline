<?php
// Démarrer la session seulement si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private $host = "localhost";
    private $db_name = "gestion_biblio";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            die("Erreur de connexion à la base de données: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Configuration de l'assistant Libro
define('ASSISTANT_ENABLED', true);
define('PYTHON_PATH', 'C:\Users\hadil\AppData\Local\Programs\Thonny\python.exe'); // Ou le chemin complet vers python.exe si nécessaire
define('ASSISTANT_SCRIPT_PATH', __DIR__ . '/libro_assistant.py');

function isAdmin() {
    if (!isset($_SESSION['user_email'])) {
        return false;
    }
    
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // Vérifier dans la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT role FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $_SESSION['user_email']);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['role'] === 'admin') {
        $_SESSION['user_role'] = 'admin';
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_email']);
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Fonction utilitaire pour formater les prix
function formatPrice($price) {
    return number_format(floatval($price), 3, '.', ' ') . ' TND';
}

// Fonction pour interagir avec l'assistant
function askAssistant($message) {
    if (!ASSISTANT_ENABLED) {
        return "L'assistant est temporairement indisponible.";
    }

    try {
        $command = escapeshellcmd(PYTHON_PATH . ' ' . ASSISTANT_SCRIPT_PATH . ' "' . addslashes($message) . '"');
        $output = shell_exec($command . ' 2>&1'); // Capturer stderr aussi

        error_log("Command: " . $command);
        error_log("Output: " . $output);

        $response = trim($output);
        return $response ?: "Je n'ai pas pu traiter votre demande. Veuillez réessayer.";

    } catch (Exception $e) {
        error_log("Erreur assistant: " . $e->getMessage());
        return "Désolé, une erreur s'est produite. Veuillez réessayer.";
    }
}

// Vérifier si l'assistant est disponible
function isAssistantAvailable() {
    if (!ASSISTANT_ENABLED) return false;
    
    // Pour les tests, retournez toujours true
    return true;
    
    /*
    try {
        $test_command = escapeshellcmd(PYTHON_PATH . ' --version');
        $output = shell_exec($test_command);
        return !empty($output);
    } catch (Exception $e) {
        return false;
    }
    */
}
function getAssistantConversations($user_email = null, $limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        if ($user_email) {
            $query = "SELECT user_message, assistant_response, created_at 
                      FROM assistant_conversations 
                      WHERE user_email = :email 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $user_email);
        } else {
            $query = "SELECT user_message, assistant_response, created_at 
                      FROM assistant_conversations 
                      WHERE session_id = :session_id 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":session_id", session_id());
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getAssistantConversations: " . $e->getMessage());
        return [];
    }
}
class DatabaseBackup {
    private $db;
    private $backupDir;
    
    public function __construct($database) {
        $this->db = $database;
        $this->backupDir = __DIR__ . '/backups/';
        $this->ensureBackupDir();
    }
    
    private function ensureBackupDir() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function createBackup($includeData = true) {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_gestion_biblio_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            $backupContent = $this->generateBackupSQL($includeData);
            
            if (file_put_contents($filepath, $backupContent)) {
                return [
                    'success' => true,
                    'message' => 'Sauvegarde créée avec succès',
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath)
                ];
            } else {
                throw new Exception('Impossible d\'écrire le fichier de sauvegarde');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ];
        }
    }
    
    private function generateBackupSQL($includeData) {
        $sql = "-- Sauvegarde Base de Données: gestion_biblio\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- PHP Version: " . PHP_VERSION . "\n";
        $sql .= "-- MySQL Version: " . $this->getMySQLVersion() . "\n\n";
        
        // Structure des tables
        $sql .= $this->getTablesStructure();
        
        if ($includeData) {
            $sql .= $this->getTablesData();
        }
        
        return $sql;
    }
    
    private function getMySQLVersion() {
        $stmt = $this->db->query("SELECT VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['version'] ?? 'Inconnue';
    }
    
    private function getTablesStructure() {
        $sql = "";
        $tables = ['books', 'users', 'cart', 'reviews', 'transactions', 'user_library', 'wishlist'];
        
        foreach ($tables as $table) {
            $stmt = $this->db->query("SHOW CREATE TABLE `$table`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $sql .= "--\n-- Structure de la table `$table`\n--\n\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $result['Create Table'] . ";\n\n";
            }
        }
        
        return $sql;
    }
    
    private function getTablesData() {
        $sql = "";
        $tables = ['books', 'users', 'cart', 'reviews', 'transactions', 'user_library', 'wishlist'];
        
        foreach ($tables as $table) {
            $sql .= "--\n-- Données de la table `$table`\n--\n\n";
            
            $stmt = $this->db->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $this->db->quote($value);
                        }
                    }
                    
                    $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        return $sql;
    }
    
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . 'backup_*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $backups;
    }
}

?>