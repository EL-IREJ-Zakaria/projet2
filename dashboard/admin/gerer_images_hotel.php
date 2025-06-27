<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;

if (!$hotel_id) {
    header('Location: hotels.php');
    exit;
}

// Récupérer les informations de l'hôtel
$hotel_sql = "SELECT * FROM hotels WHERE id = ?";
$hotel_result = executeQuery($hotel_sql, "i", [$hotel_id]);
$hotel = $hotel_result->fetch_assoc();

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

// Traitement de l'ajout d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_image'])) {
    $description = cleanInput($_POST['description']);
    $type_image = cleanInput($_POST['type_image']);
    $ordre = (int)$_POST['ordre_affichage'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/hotels/';
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'hotel_' . $hotel_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $insert_sql = "INSERT INTO hotel_images (hotel_id, nom_image, description, type_image, ordre_affichage) VALUES (?, ?, ?, ?, ?)";
                executeQuery($insert_sql, "isssi", [$hotel_id, $filename, $description, $type_image, $ordre]);
                $success = "Image ajoutée avec succès !";
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error = "Format d'image non autorisé. Utilisez JPG, PNG ou WebP.";
        }
    }
}

// Récupérer les images existantes
$images_sql = "SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY ordre_affichage ASC";
$images_result = executeQuery($images_sql, "i", [$hotel_id]);
$images = [];
while ($img = $images_result->fetch_assoc()) {
    $images[] = $img;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gérer les images - <?php echo htmlspecialchars($hotel['nom']); ?></h1>
                <a href="hotels.php" class="btn btn-outline-secondary">Retour aux hôtels</a>
            </div>
            
            <?php if (isset($success)) : ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)) : ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Formulaire d'ajout d'image -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Ajouter une nouvelle image</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type_image" class="form-label">Type d'image</label>
                                    <select class="form-select" id="type_image" name="type_image" required>
                                        <option value="principale">Principale</option>
                                        <option value="exterieur">Extérieur</option>
                                        <option value="interieur">Intérieur</option>
                                        <option value="restaurant">Restaurant</option>
                                        <option value="spa">Spa</option>
                                        <option value="piscine">Piscine</option>
                                        <option value="chambre">Chambre</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ordre_affichage" class="form-label">Ordre d'affichage</label>
                                    <input type="number" class="form-control" id="ordre_affichage" name="ordre_affichage" value="<?php echo count($images) + 1; ?>" min="1">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="ajouter_image" class="btn btn-primary">Ajouter l'image</button>
                    </form>
                </div>
            </div>
            
            <!-- Liste des images existantes -->
            <div class="card">
                <div class="card-header">
                    <h5>Images existantes (<?php echo count($images); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($images)) : ?>
                        <p class="text-muted">Aucune image pour cet hôtel.</p>
                    <?php else : ?>
                        <div class="row">
                            <?php foreach ($images as $image) : ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <img src="../../assets/images/hotels/<?php echo htmlspecialchars($image['nom_image']); ?>" 
                                             class="card-img-top" 
                                             style="height: 200px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($image['description']); ?>">
                                        <div class="card-body p-2">
                                            <h6 class="card-title"><?php echo htmlspecialchars($image['description']); ?></h6>
                                            <p class="card-text small">
                                                <span class="badge bg-secondary"><?php echo ucfirst($image['type_image']); ?></span>
                                                <span class="badge bg-info">Ordre: <?php echo $image['ordre_affichage']; ?></span>
                                            </p>
                                            <div class="d-flex gap-1">
                                                <a href="modifier_image_hotel.php?id=<?php echo $image['id']; ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                                <a href="supprimer_image_hotel.php?id=<?php echo $image['id']; ?>&hotel_id=<?php echo $hotel_id; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image ?')">Supprimer</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>