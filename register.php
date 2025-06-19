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
$success = false;

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $nom = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $telephone = cleanInput($_POST['telephone'] ?? '');
        $adresse = cleanInput($_POST['adresse'] ?? '');
        
        // Validation des données
        if (empty($nom)) {
            $errors[] = "Le nom est requis.";
        }
        
        if (empty($prenom)) {
            $errors[] = "Le prénom est requis.";
        }
        
        if (empty($email)) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide.";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
        
        if ($password !== $password_confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        // Si pas d'erreurs, enregistrer l'utilisateur
        if (empty($errors)) {
            $result = registerUser($nom, $prenom, $email, $password, $telephone, $adresse);
            
            if ($result['success']) {
                $success = true;
                // Redirection après 3 secondes
                header("refresh:3;url=login.php");
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

<div class="register-page py-5">
    <div class="container">
        <div class="row g-0 shadow rounded overflow-hidden">
            <div class="col-lg-5 d-none d-lg-block">
                <div class="register-image h-100" style="background: url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=880&q=80') center/cover no-repeat;"></div>
            </div>
            <div class="col-lg-7 bg-white">
                <div class="register-form-container p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-1">Créer un compte</h2>
                        <p class="text-muted">Rejoignez-nous pour découvrir nos hôtels de luxe</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fs-4 me-2"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Inscription réussie!</h5>
                                    <p class="mb-0">Votre compte a été créé avec succès. Vous allez être redirigé vers la page de connexion...</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                                        <label for="nom"><i class="fas fa-user me-2 text-gold"></i>Nom</label>
                                        <div class="invalid-feedback">Veuillez entrer votre nom.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
                                        <label for="prenom"><i class="fas fa-user me-2 text-gold"></i>Prénom</label>
                                        <div class="invalid-feedback">Veuillez entrer votre prénom.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    <label for="email"><i class="fas fa-envelope me-2 text-gold"></i>Email</label>
                                    <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Téléphone" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                                    <label for="telephone"><i class="fas fa-phone me-2 text-gold"></i>Téléphone</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <textarea class="form-control" id="adresse" name="adresse" placeholder="Adresse" style="height: 100px"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                                    <label for="adresse"><i class="fas fa-map-marker-alt me-2 text-gold"></i>Adresse</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required minlength="8">
                                    <label for="password"><i class="fas fa-lock me-2 text-gold"></i>Mot de passe</label>
                                    <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                                    <div class="invalid-feedback">Veuillez entrer un mot de passe d'au moins 8 caractères.</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                                    <label for="password_confirm"><i class="fas fa-lock me-2 text-gold"></i>Confirmer le mot de passe</label>
                                    <div class="invalid-feedback">Les mots de passe ne correspondent pas.</div>
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">J'accepte les <a href="#" class="text-gold">conditions générales</a> et la <a href="#" class="text-gold">politique de confidentialité</a></label>
                                <div class="invalid-feedback">Vous devez accepter les conditions pour continuer.</div>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-gold btn-lg">Créer mon compte</button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Vous avez déjà un compte? <a href="login.php" class="text-gold">Connectez-vous</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>