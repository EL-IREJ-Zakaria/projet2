<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Vérifier si l'ID de la chambre est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: chambres.php');
    exit;
}

$chambre_id = intval($_GET['id']);

// Récupérer les informations de la chambre
$chambre = fetchOne("SELECT * FROM chambres WHERE id = ?", "i", [$chambre_id]);

if (!$chambre) {
    header('Location: chambres.php');
    exit;
}

// Récupération de tous les hôtels
$hotels = fetchAll("SELECT id, nom, ville FROM hotels ORDER BY nom ASC");

$errors = [];
$success = false;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $hotel_id = intval($_POST['hotel_id'] ?? 0);
        $numero = cleanInput($_POST['numero'] ?? '');
        $type = cleanInput($_POST['type'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $prix_nuit = floatval(str_replace(',', '.', $_POST['prix_nuit'] ?? 0));
        $capacite = intval($_POST['capacite'] ?? 0);
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        
        // Validation des données
        if ($hotel_id <= 0) {
            $errors[] = "Veuillez sélectionner un hôtel.";
        }
        
        if (empty($numero)) {
            $errors[] = "Le numéro de chambre est requis.";
        }
        
        if (empty($type)) {
            $errors[] = "Le type de chambre est requis.";
        }
        
        if ($prix_nuit <= 0) {
            $errors[] = "Le prix par nuit doit être supérieur à 0.";
        }
        
        if ($capacite <= 0) {
            $errors[] = "La capacité doit être supérieure à 0.";
        }
        
        // Vérifier si le numéro de chambre existe déjà dans cet hôtel (sauf pour cette chambre)
        $chambre_existante = fetchOne("SELECT id FROM chambres WHERE hotel_id = ? AND numero = ? AND id != ?", "isi", [$hotel_id, $numero, $chambre_id]);
        if ($chambre_existante) {
            $errors[] = "Ce numéro de chambre existe déjà dans cet hôtel.";
        }
        
        // Traitement de l'image
        $image = $chambre['image']; // Conserver l'image existante par défaut
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/chambres/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Vérifier l'extension du fichier
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
            } else {
                // Générer un nom de fichier unique
                $new_file_name = 'chambre_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne image si elle existe
                    if (!empty($chambre['image'])) {
                        $old_image_path = $upload_dir . $chambre['image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    $image = $new_file_name;
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image.";
                }
            }
        }
        
        // Si pas d'erreurs, modifier la chambre
        if (empty($errors)) {
            $sql = "UPDATE chambres SET hotel_id = ?, numero = ?, type = ?, description = ?, 
                   prix_nuit = ?, capacite = ?, disponible = ?, image = ? WHERE id = ?";
            $result = executeQuery($sql, "isssdiisi", [
                $hotel_id, $numero, $type, $description, $prix_nuit, 
                $capacite, $disponible, $image, $chambre_id
            ]);
            
            if ($result) {
                $success = true;
                // Mettre à jour les données de la chambre pour l'affichage
                $chambre = fetchOne("SELECT * FROM chambres WHERE id = ?", "i", [$chambre_id]);
            } else {
                $errors[] = "Erreur lors de la modification de la chambre.";
            }
        }
    }
}

// Générer un nouveau jeton CSRF
$csrf_token = generateCSRFToken();

// Titre de la page
$page_title = "Modifier une chambre";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Modifier une chambre</h1>
                <a href="chambres.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
            </div>
            
            <?php if ($success) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    La chambre a été modifiée avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
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
                        
                        <div class="mb-3">
                            <label for="hotel_id" class="form-label">Hôtel <span class="text-danger">*</span></label>
                            <select class="form-select" id="hotel_id" name="hotel_id" required>
                                <option value="">Sélectionnez un hôtel</option>
                                <?php foreach ($hotels as $hotel) : ?>
                                    <option value="<?php echo $hotel['id']; ?>" <?php echo ($chambre['hotel_id'] == $hotel['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Veuillez sélectionner un hôtel.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="numero" class="form-label">Numéro de chambre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($chambre['numero']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer le numéro de chambre.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type de chambre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="type" name="type" value="<?php echo htmlspecialchars($chambre['type']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer le type de chambre.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($chambre['description']); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prix_nuit" class="form-label">Prix par nuit (€) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="prix_nuit" name="prix_nuit" step="0.01" min="0" value="<?php echo htmlspecialchars($chambre['prix_nuit']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer un prix valide.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="capacite" class="form-label">Capacité (personnes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="capacite" name="capacite" min="1" value="<?php echo htmlspecialchars($chambre['capacite']); ?>" required>
                                <div class="invalid-feedback">Veuillez entrer la capacité de la chambre.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image de la chambre</label>
                            <?php if (!empty($chambre['image'])) : ?>
                                <div class="mb-2">
                                    <img src="../../assets/images/chambres/<?php echo htmlspecialchars($chambre['image']); ?>" alt="Image de la chambre" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image">
                            <div class="form-text">Formats acceptés: JPG, JPEG, PNG, GIF. Laissez vide pour conserver l'image actuelle.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="disponible" name="disponible" <?php echo $chambre['disponible'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="disponible">Chambre disponible</label>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-gold"><i class="fas fa-save me-2"></i>Enregistrer les modifications</button>
                            <a href="chambres.php" class="btn btn-outline-secondary ms-2">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>