<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Traitement de la suppression d'une chambre
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $chambre_id = intval($_GET['id']);
    
    // Vérifier si la chambre existe
    $chambre = fetchOne("SELECT id FROM chambres WHERE id = ?", "i", [$chambre_id]);
    
    if ($chambre) {
        // Vérifier s'il y a des réservations pour cette chambre
        $reservations = fetchOne("SELECT COUNT(*) as total FROM reservations WHERE chambre_id = ?", "i", [$chambre_id]);
        
        if ($reservations && $reservations['total'] > 0) {
            $message = ["type" => "danger", "text" => "Impossible de supprimer cette chambre car elle possède des réservations."];
        } else {
            // Supprimer la chambre
            executeQuery("DELETE FROM chambres WHERE id = ?", "i", [$chambre_id]);
            $message = ["type" => "success", "text" => "La chambre a été supprimée avec succès."];
        }
    }
}

// Filtrage par hôtel
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

// Récupération de tous les hôtels pour le filtre
$hotels = fetchAll("SELECT id, nom, ville FROM hotels ORDER BY nom ASC");

// Construction de la requête de chambres avec filtrage
$sql = "SELECT c.*, h.nom as hotel_nom, h.ville as hotel_ville 
       FROM chambres c 
       JOIN hotels h ON c.hotel_id = h.id";
$params = [];
$types = "";

if ($hotel_id > 0) {
    $sql .= " WHERE c.hotel_id = ?";
    $params[] = $hotel_id;
    $types = "i";
}

$sql .= " ORDER BY h.nom ASC, c.numero ASC";

// Récupération des chambres
$chambres = fetchAll($sql, $types, $params);

// Titre de la page
$page_title = "Gestion des chambres";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gestion des chambres</h1>
                <a href="ajouter_chambre.php" class="btn btn-gold"><i class="fas fa-plus-circle me-2"></i>Ajouter une chambre</a>
            </div>
            
            <?php if (isset($message)) : ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filtres -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-6">
                            <label for="hotel_id" class="form-label">Filtrer par hôtel</label>
                            <select class="form-select" id="hotel_id" name="hotel_id">
                                <option value="0">Tous les hôtels</option>
                                <?php foreach ($hotels as $hotel) : ?>
                                    <option value="<?php echo $hotel['id']; ?>" <?php echo ($hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
                            <?php if ($hotel_id > 0) : ?>
                                <a href="chambres.php" class="btn btn-outline-danger ms-2">Réinitialiser</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($chambres)) : ?>
                        <p class="text-center text-muted my-4">Aucune chambre trouvée.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Hôtel</th>
                                        <th>Numéro</th>
                                        <th>Type</th>
                                        <th>Capacité</th>
                                        <th>Prix/nuit</th>
                                        <th>Disponibilité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chambres as $chambre) : ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($chambre['hotel_nom']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($chambre['hotel_ville']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($chambre['numero']); ?></td>
                                            <td><?php echo htmlspecialchars($chambre['type']); ?></td>
                                            <td><?php echo $chambre['capacite']; ?> personne<?php echo $chambre['capacite'] > 1 ? 's' : ''; ?></td>
                                            <td><?php echo number_format($chambre['prix_nuit'], 2, ',', ' '); ?> MAD</td>
                                            <td>
                                                <?php if ($chambre['disponible']) : ?>
                                                    <span class="badge bg-success">Disponible</span>
                                                <?php else : ?>
                                                    <span class="badge bg-danger">Indisponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="modifier_chambre.php?id=<?php echo $chambre['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="chambres.php?action=delete&id=<?php echo $chambre['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette chambre ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>