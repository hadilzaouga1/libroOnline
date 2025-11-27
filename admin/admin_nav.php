<?php
// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);

// Vérifier la disponibilité de l'assistant
require_once '../config.php';
$assistant_available = isAssistantAvailable();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-shield-lock me-2"></i>Admin Libro Online
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNav">
            <div class="navbar-nav ms-auto">
                <?php if ($assistant_available): ?>
                <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#assistantModal">
                    <i class="bi bi-robot me-1"></i> Assistant
                </button>
                <?php endif; ?>
                
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-1"></i>Tableau de bord
                </a>
                <a class="nav-link <?php echo $current_page == 'books.php' ? 'active' : ''; ?>" href="books.php">
                    <i class="bi bi-book me-1"></i>Livres
                </a>
                <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people me-1"></i>Utilisateurs
                </a>
                <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                    <i class="bi bi-cart me-1"></i>Commandes
                </a>
                <a class="nav-link <?php echo $current_page == 'borrows.php' ? 'active' : ''; ?>" href="borrows.php">
                    <i class="bi bi-clock-history me-1"></i>Emprunts
                </a>
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-eye me-1"></i>Voir le site
                </a>
                <a class="nav-link text-warning" href="?logout=1">
                    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Assistant Chat Button -->
<?php if ($assistant_available): ?>
<button class="btn btn-primary assistant-btn" data-bs-toggle="modal" data-bs-target="#assistantModal">
    <i class="bi bi-robot"></i>
</button>

<!-- Assistant Modal -->
<div class="modal fade assistant-modal" id="assistantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-robot me-2"></i>Assistant Libro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="assistant-chat" id="assistantChat">
                    <div class="message assistant-message">
                        👋 Bonjour Admin ! Je suis l'assistant virtuel de Libro Online. Comment puis-je vous aider dans la gestion du site ?
                    </div>
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    <i class="bi bi-three-dots"></i> L'assistant écrit...
                </div>
                <div class="input-group">
                    <input type="text" class="form-control" id="assistantInput" placeholder="Posez votre question...">
                    <button class="btn btn-primary" id="sendMessage">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chat = document.getElementById('assistantChat');
    const input = document.getElementById('assistantInput');
    const sendBtn = document.getElementById('sendMessage');
    const typingIndicator = document.getElementById('typingIndicator');
    
    function addMessage(text, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
        messageDiv.textContent = text;
        chat.appendChild(messageDiv);
        chat.scrollTop = chat.scrollHeight;
    }
    
    function sendMessage() {
        const message = input.value.trim();
        if (!message) return;
        
        addMessage(message, true);
        input.value = '';
        
        // Afficher l'indicateur de frappe
        typingIndicator.style.display = 'block';
        chat.scrollTop = chat.scrollHeight;
        
        // Envoyer la requête à l'assistant
        fetch('../assistant_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => response.text())
        .then(response => {
            typingIndicator.style.display = 'none';
            addMessage(response);
        })
        .catch(error => {
            typingIndicator.style.display = 'none';
            addMessage('Désolé, une erreur s\'est produite. Veuillez réessayer.');
            console.error('Error:', error);
        });
    }
    
    sendBtn.addEventListener('click', sendMessage);
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Focus sur l'input quand le modal s'ouvre
    const modal = document.getElementById('assistantModal');
    modal.addEventListener('shown.bs.modal', function() {
        input.focus();
    });
});
</script>
<?php endif; ?>