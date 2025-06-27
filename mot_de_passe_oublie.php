<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success_message = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'request';

// Étape 1: Demande de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        $email = cleanInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide.";
        } else {
            // Vérifier si l'email existe
            $user = fetchOne("SELECT id, nom, prenom FROM clients WHERE email = ?", "s", [$email]);
            
            if ($user) {
                // Générer un token de réinitialisation
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Stocker le token en base (vous devrez créer cette table)
                $sql = "INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
                executeQuery($sql, "isss", [$user['id'], $email, $reset_token, $expires_at]);
                
                // Simuler l'envoi d'email (en production, utilisez PHPMailer ou similar)
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/projet2/mot_de_passe_oublie.php?step=reset&token=" . $reset_token;
                
                // Pour la démo, on affiche le lien
                $success_message = "Un email de réinitialisation a été envoyé à votre adresse.<br><br>
                                  <strong>Lien de réinitialisation (pour la démo):</strong><br>
                                  <a href='$reset_link' class='btn btn-gold btn-sm'>Réinitialiser le mot de passe</a>";
            } else {
                // Ne pas révéler si l'email existe ou non pour la sécurité
                $success_message = "Si cette adresse email est associée à un compte, vous recevrez un email de réinitialisation.";
            }
        }
    }
}

// Étape 2: Réinitialisation avec token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        $token = cleanInput($_POST['token'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password)) {
            $errors[] = "Le nouveau mot de passe est requis.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        } else {
            // Vérifier le token
            $reset_data = fetchOne(
                "SELECT user_id, email FROM password_resets WHERE token = ? AND expires_at > NOW()", 
                "s", 
                [$token]
            );
            
            if ($reset_data) {
                // Mettre à jour le mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $result = executeQuery(
                    "UPDATE clients SET mot_de_passe = ? WHERE id = ?", 
                    "si", 
                    [$hashed_password, $reset_data['user_id']]
                );
                
                if ($result) {
                    // Supprimer le token utilisé
                    executeQuery("DELETE FROM password_resets WHERE token = ?", "s", [$token]);
                    
                    $success_message = "Votre mot de passe a été réinitialisé avec succès. <a href='login.php'>Connectez-vous maintenant</a>.";
                    $step = 'success';
                } else {
                    $errors[] = "Erreur lors de la mise à jour du mot de passe.";
                }
            } else {
                $errors[] = "Token invalide ou expiré.";
            }
        }
    }
}

// Vérification du token pour l'affichage du formulaire de réinitialisation
if ($step === 'reset' && isset($_GET['token'])) {
    $token = cleanInput($_GET['token']);
    $reset_data = fetchOne(
        "SELECT user_id, email FROM password_resets WHERE token = ? AND expires_at > NOW()", 
        "s", 
        [$token]
    );
    
    if (!$reset_data) {
        $errors[] = "Token invalide ou expiré.";
        $step = 'request';
    }
}

// Générer un nouveau jeton CSRF
$csrf_token = generateCSRFToken();

include 'includes/header.php';
?>

<div class="login-page py-5">
    <div class="container">
        <div class="row g-0 shadow rounded overflow-hidden">
            <div class="col-md-6 d-none d-md-block">
                <div class="login-image h-100" style="background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1080&q=80') center/cover no-repeat;"></div>
            </div>
            <div class="col-md-6 bg-white p-0">
                <div class="login-form-container p-4 p-md-5">
                    <?php if ($step === 'request'): ?>
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-1">Mot de passe oublié</h2>
                            <p class="text-muted">Entrez votre email pour recevoir un lien de réinitialisation</p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="?step=request" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    <label for="email"><i class="fas fa-envelope me-2 text-gold"></i>Email</label>
                                </div>
                                <div class="invalid-feedback">Veuillez entrer votre adresse email.</div>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-gold btn-lg">Envoyer le lien de réinitialisation</button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Vous vous souvenez de votre mot de passe? <a href="login.php" class="text-gold">Connectez-vous</a></p>
                            </div>
                        </form>
                        
                    <?php elseif ($step === 'reset'): ?>
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-1">Nouveau mot de passe</h2>
                            <p class="text-muted">Choisissez un nouveau mot de passe sécurisé</p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="?step=reset" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nouveau mot de passe" required minlength="6">
                                    <label for="new_password"><i class="fas fa-lock me-2 text-gold"></i>Nouveau mot de passe</label>
                                </div>
                                <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères.</div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required minlength="6">
                                    <label for="confirm_password"><i class="fas fa-lock me-2 text-gold"></i>Confirmer le mot de passe</label>
                                </div>
                                <div class="invalid-feedback">Veuillez confirmer votre mot de passe.</div>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-gold btn-lg">Réinitialiser le mot de passe</button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0"><a href="login.php" class="text-gold">Retour à la connexion</a></p>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="fw-bold mb-3 text-success">Mot de passe réinitialisé</h2>
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            <a href="login.php" class="btn btn-gold btn-lg">Se connecter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation des mots de passe identiques
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }
});
</script>

<?php include 'includes/footer.php'; ?>