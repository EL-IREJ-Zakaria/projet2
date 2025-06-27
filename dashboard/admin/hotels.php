<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Traitement de la suppression d'un hôtel
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $hotel_id = intval($_GET['id']);
    
    // Vérifier si l'hôtel existe
    $hotel = fetchOne("SELECT id FROM hotels WHERE id = ?", "i", [$hotel_id]);
    
    if ($hotel) {
        // Vérifier s'il y a des réservations pour cet hôtel
        $reservations = fetchOne("SELECT COUNT(*) as total FROM reservations r 
                                JOIN chambres c ON r.chambre_id = c.id 
                                WHERE c.hotel_id = ?", "i", [$hotel_id]);
        
        if ($reservations && $reservations['total'] > 0) {
            $message = ["type" => "danger", "text" => "Impossible de supprimer cet hôtel car il possède des réservations actives."];
        } else {
            // Supprimer l'hôtel
            executeQuery("DELETE FROM hotels WHERE id = ?", "i", [$hotel_id]);
            $message = ["type" => "success", "text" => "L'hôtel a été supprimé avec succès."];
        }
    }
}

// Récupération de tous les hôtels
$hotels = fetchAll("SELECT h.*, 
                   (SELECT COUNT(*) FROM chambres WHERE hotel_id = h.id) as nb_chambres,
                   (SELECT AVG(prix_nuit) FROM chambres WHERE hotel_id = h.id) as prix_moyen
                   FROM hotels h 
                   ORDER BY h.nom ASC");

// Titre de la page
$page_title = "Gestion des hôtels";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gestion des hôtels</h1>
                <a href="ajouter_hotel.php" class="btn btn-gold"><i class="fas fa-plus-circle me-2"></i>Ajouter un hôtel</a>
            </div>
            
            <?php if (isset($message)) : ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($hotels)) : ?>
                        <p class="text-center text-muted my-4">Aucun hôtel enregistré.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Ville</th>
                                        <th>Pays</th>
                                        <th>Étoiles</th>
                                        <th>Chambres</th>
                                        <th>Prix moyen</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hotels as $hotel) : ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($hotel['nom']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($hotel['ville']); ?></td>
                                            <td><?php echo htmlspecialchars($hotel['pays']); ?></td>
                                            <td>
                                                <?php for ($i = 0; $i < $hotel['etoiles']; $i++) : ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td><?php echo $hotel['nb_chambres']; ?></td>
                                            <td><?php echo number_format($hotel['prix_moyen'], 2, ',', ' '); ?> MAD</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="../../hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="modifier_hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="hotels.php?action=delete&id=<?php echo $hotel['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet hôtel ?')">
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