<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$errors = [];
$success = false;

// Traitement du formulaire d'ajout d'hôtel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $nom = cleanInput($_POST['nom'] ?? '');
        $adresse = cleanInput($_POST['adresse'] ?? '');
        $ville = cleanInput($_POST['ville'] ?? '');
        $pays = cleanInput($_POST['pays'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $etoiles = intval($_POST['etoiles'] ?? 0);
        $coordonnees_gps = cleanInput($_POST['coordonnees_gps'] ?? '');
        
        // Validation des données
        if (empty($nom)) {
            $errors[] = "Le nom de l'hôtel est requis.";
        }
        
        if (empty($adresse)) {
            $errors[] = "L'adresse est requise.";
        }
        
        if (empty($ville)) {
            $errors[] = "La ville est requise.";
        }
        
        if (empty($pays)) {
            $errors[] = "Le pays est requis.";
        }
        
        if ($etoiles < 1 || $etoiles > 5) {
            $errors[] = "Le nombre d'étoiles doit être compris entre 1 et 5.";
        }
        
        // Traitement de l'image
        $image_principale = '';
        if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/hotels/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['image_principale']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Vérifier l'extension du fichier
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
            } else {
                // Générer un nom de fichier unique
                $new_file_name = 'hotel_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($_FILES['image_principale']['tmp_name'], $upload_path)) {
                    $image_principale = $new_file_name;
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image.";
                }
            }
        }
        
        // Si pas d'erreurs, ajouter l'hôtel
        if (empty($errors)) {
            $sql = "INSERT INTO hotels (nom, adresse, ville, pays, description, etoiles, image_principale, coordonnees_gps) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $id = insertAndGetId($sql, "ssssssss", [$nom, $adresse, $ville, $pays, $description, $etoiles, $image_principale, $coordonnees_gps]);
            
            if ($id) {
                $success = true;
            } else {
                $errors[] = "Erreur lors de l'ajout de l'hôtel.";
            }
        }
    }
}

// Générer un nouveau jeton CSRF
$csrf_token = generateCSRFToken();

// Titre de la page
$page_title = "Ajouter un hôtel";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Ajouter un hôtel</h1>
                <a href="hotels.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
            </div>
            
            <?php if ($success) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    L'hôtel a été ajouté avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="text-center mb-4">
                    <a href="hotels.php" class="btn btn-outline-secondary me-2">Retour à la liste des hôtels</a>
                    <a href="ajouter_hotel.php" class="btn btn-gold">Ajouter un autre hôtel</a>
                </div>
            <?php endif; ?>
            
            <?php if (!$success) : ?>
                <?php if (!empty($errors)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom de l'hôtel <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                                    <div class="invalid-feedback">Veuillez entrer le nom de l'hôtel.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="etoiles" class="form-label">Nombre d'étoiles <span class="text-danger">*</span></label>
                                    <select class="form-select" id="etoiles" name="etoiles" required>
                                        <option value="">Sélectionnez</option>
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['etoiles']) && $_POST['etoiles'] == $i) ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> étoile<?php echo $i > 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="invalid-feedback">Veuillez sélectionner le nombre d'étoiles.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adresse" name="adresse" value="<?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?>" required>
                                <div class="invalid-feedback">Veuillez entrer l'adresse de l'hôtel.</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?php echo isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : ''; ?>" required>
                                    <div class="invalid-feedback">Veuillez entrer la ville.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="pays" class="form-label">Pays <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="pays" name="pays" value="<?php echo isset($_POST['pays']) ? htmlspecialchars($_POST['pays']) : ''; ?>" required>
                                    <div class="invalid-feedback">Veuillez entrer le pays.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="coordonnees_gps" class="form-label">Coordonnées GPS</label>
                                    <input type="text" class="form-control" id="coordonnees_gps" name="coordonnees_gps" value="<?php echo isset($_POST['coordonnees_gps']) ? htmlspecialchars($_POST['coordonnees_gps']) : ''; ?>" placeholder="ex: 48.8566,2.3522">
                                    <div class="form-text">Format: latitude,longitude</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="image_principale" class="form-label">Image principale</label>
                                    <input type="file" class="form-control" id="image_principale" name="image_principale">
                                    <div class="form-text">Formats acceptés: JPG, JPEG, PNG, GIF</div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-gold"><i class="fas fa-plus-circle me-2"></i>Ajouter l'hôtel</button>
                                <a href="hotels.php" class="btn btn-outline-secondary ms-2">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>