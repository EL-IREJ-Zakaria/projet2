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

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation des données
        if (empty($email)) {
            $errors[] = "L'email est requis.";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        }
        
        // Si pas d'erreurs, connecter l'utilisateur
        if (empty($errors)) {
            $result = loginUser($email, $password);
            
            if ($result['success']) {
                // Si option "se souvenir de moi" est cochée
                if ($remember) {
                    // Créer un cookie qui expire dans 30 jours
                    setcookie('remember_user', $_SESSION['user_id'], time() + (86400 * 30), '/', '', false, true);
                }
                
                // Redirection selon le type d'utilisateur
                if ($_SESSION['user_type'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
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
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-1">Bienvenue</h2>
                        <p class="text-muted">Connectez-vous à votre compte</p>
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
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <label for="email"><i class="fas fa-envelope me-2 text-gold"></i>Email</label>
                            </div>
                            <div class="invalid-feedback">Veuillez entrer votre adresse email.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                                <label for="password"><i class="fas fa-lock me-2 text-gold"></i>Mot de passe</label>
                            </div>
                            <div class="invalid-feedback">Veuillez entrer votre mot de passe.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            <a href="mot_de_passe_oublie.php" class="text-gold text-decoration-none">Mot de passe oublié?</a>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-gold btn-lg">Connexion</button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Vous n'avez pas de compte? <a href="register.php" class="text-gold">Inscrivez-vous</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>