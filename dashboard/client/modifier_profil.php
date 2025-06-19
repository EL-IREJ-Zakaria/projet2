<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: ../../login.php');
    exit;
}

// Récupération des informations du client
$client = fetchOne("SELECT * FROM clients WHERE id = ?", "i", [$_SESSION['user_id']]);

$errors = [];
$success = false;

// Traitement du formulaire de modification de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $nom = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $telephone = cleanInput($_POST['telephone'] ?? '');
        $adresse = cleanInput($_POST['adresse'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
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
        } elseif ($email !== $client['email']) {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $check_email = fetchOne("SELECT id FROM clients WHERE email = ? AND id != ?", "si", [$email, $_SESSION['user_id']]);
            if ($check_email) {
                $errors[] = "Cet email est déjà utilisé par un autre compte.";
            }
        }
        
        // Validation du mot de passe si l'utilisateur souhaite le changer
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $errors[] = "Le mot de passe actuel est requis pour changer de mot de passe.";
            } elseif (!password_verify($current_password, $client['mot_de_passe'])) {
                $errors[] = "Le mot de passe actuel est incorrect.";
            }
            
            if (empty($new_password)) {
                $errors[] = "Le nouveau mot de passe est requis.";
            } elseif (strlen($new_password) < 8) {
                $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "Les mots de passe ne correspondent pas.";
            }
        }
        
        // Si pas d'erreurs, mettre à jour le profil
        if (empty($errors)) {
            $sql = "UPDATE clients SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?";
            $params = [$nom, $prenom, $email, $telephone, $adresse];
            $types = "sssss";
            
            // Ajouter le mot de passe à la requête si l'utilisateur souhaite le changer
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", mot_de_passe = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $_SESSION['user_id'];
            $types .= "i";
            
            executeQuery($sql, $types, $params);
            $success = true;
            
            // Mettre à jour les informations du client
            $client = fetchOne("SELECT * FROM clients WHERE id = ?", "i", [$_SESSION['user_id']]);
        }
    }
}

// Titre de la page
$page_title = "Modifier mon profil";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Modifier mon profil</h1>
            </div>
            
            <?php if ($success) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Votre profil a été mis à jour avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($client['nom']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer votre nom.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($client['prenom']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer votre prénom.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>">
                                <div class="invalid-feedback">Veuillez entrer un numéro de téléphone valide.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($client['adresse'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Changer de mot de passe</h5>
                        <p class="text-muted small mb-3">Laissez les champs vides si vous ne souhaitez pas changer de mot de passe.</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-gold">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>