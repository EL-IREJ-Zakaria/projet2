<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reservation_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Vérifier si la réservation existe
    $reservation = fetchOne("SELECT id, statut FROM reservations WHERE id = ?", "i", [$reservation_id]);
    
    if ($reservation) {
        if ($action === 'confirmer' && $reservation['statut'] === 'en_attente') {
            executeQuery("UPDATE reservations SET statut = 'confirmee' WHERE id = ?", "i", [$reservation_id]);
            $message = ["type" => "success", "text" => "La réservation a été confirmée avec succès."];
        } elseif ($action === 'annuler' && ($reservation['statut'] === 'en_attente' || $reservation['statut'] === 'confirmee')) {
            executeQuery("UPDATE reservations SET statut = 'annulee' WHERE id = ?", "i", [$reservation_id]);
            $message = ["type" => "success", "text" => "La réservation a été annulée avec succès."];
        } elseif ($action === 'supprimer') {
            executeQuery("DELETE FROM reservations WHERE id = ?", "i", [$reservation_id]);
            $message = ["type" => "success", "text" => "La réservation a été supprimée avec succès."];
        }
    }
}

// Filtres
$statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
$date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';

// Construction de la requête avec filtres
$sql = "SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, 
        h.nom as hotel_nom, h.ville as hotel_ville, ch.numero as chambre_numero, ch.type as chambre_type 
        FROM reservations r 
        JOIN clients c ON r.client_id = c.id 
        JOIN chambres ch ON r.chambre_id = ch.id 
        JOIN hotels h ON ch.hotel_id = h.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($statut)) {
    $sql .= " AND r.statut = ?";
    $params[] = $statut;
    $types .= "s";
}

if ($hotel_id > 0) {
    $sql .= " AND h.id = ?";
    $params[] = $hotel_id;
    $types .= "i";
}

if (!empty($date_debut)) {
    $sql .= " AND r.date_arrivee >= ?";
    $params[] = $date_debut;
    $types .= "s";
}

if (!empty($date_fin)) {
    $sql .= " AND r.date_depart <= ?";
    $params[] = $date_fin;
    $types .= "s";
}

$sql .= " ORDER BY r.date_arrivee DESC";

// Récupération des réservations
$reservations = fetchAll($sql, $types, $params);

// Récupération des hôtels pour le filtre
$hotels = fetchAll("SELECT id, nom, ville FROM hotels ORDER BY nom ASC");

// Titre de la page
$page_title = "Gestion des réservations";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gestion des réservations</h1>
                <a href="ajouter_reservation.php" class="btn btn-gold"><i class="fas fa-plus-circle me-2"></i>Nouvelle réservation</a>
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
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmee" <?php echo $statut === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="annulee" <?php echo $statut === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="hotel_id" class="form-label">Hôtel</label>
                            <select class="form-select" id="hotel_id" name="hotel_id">
                                <option value="0">Tous les hôtels</option>
                                <?php foreach ($hotels as $hotel) : ?>
                                    <option value="<?php echo $hotel['id']; ?>" <?php echo ($hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date d'arrivée (min)</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date de départ (max)</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-12 d-flex">
                            <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
                            <?php if (!empty($statut) || $hotel_id > 0 || !empty($date_debut) || !empty($date_fin)) : ?>
                                <a href="reservations.php" class="btn btn-outline-danger ms-2">Réinitialiser</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($reservations)) : ?>
                        <p class="text-center text-muted my-4">Aucune réservation trouvée.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Réf.</th>
                                        <th>Client</th>
                                        <th>Hôtel / Chambre</th>
                                        <th>Dates</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation) : ?>
                                        <tr>
                                            <td>#<?php echo $reservation['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($reservation['client_email']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($reservation['hotel_nom']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($reservation['hotel_ville'] . ' - Chambre ' . $reservation['chambre_numero'] . ' (' . $reservation['chambre_type'] . ')'); ?></div>
                                            </td>
                                            <td>
                                                <div>Du <?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></div>
                                                <div>Au <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></div>
                                                <div class="small text-muted">
                                                    <?php 
                                                    $interval = date_diff(date_create($reservation['date_arrivee']), date_create($reservation['date_depart']));
                                                    echo $interval->days . ' nuit' . ($interval->days > 1 ? 's' : '');
                                                    ?>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($reservation['montant_total'], 2, ',', ' '); ?> €</td>
                                            <td>
                                                <?php if ($reservation['statut'] === 'en_attente') : ?>
                                                    <span class="badge bg-warning">En attente</span>
                                                <?php elseif ($reservation['statut'] === 'confirmee') : ?>
                                                    <span class="badge bg-success">Confirmée</span>
                                                <?php elseif ($reservation['statut'] === 'annulee') : ?>
                                                    <span class="badge bg-danger">Annulée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $reservation['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $reservation['id']; ?>">
                                                        <li><a class="dropdown-item" href="voir_reservation.php?id=<?php echo $reservation['id']; ?>"><i class="fas fa-eye me-2"></i>Voir détails</a></li>
                                                        <?php if ($reservation['statut'] === 'en_attente') : ?>
                                                            <li><a class="dropdown-item" href="reservations.php?action=confirmer&id=<?php echo $reservation['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')"><i class="fas fa-check me-2"></i>Confirmer</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($reservation['statut'] === 'en_attente' || $reservation['statut'] === 'confirmee') : ?>
                                                            <li><a class="dropdown-item" href="reservations.php?action=annuler&id=<?php echo $reservation['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')"><i class="fas fa-ban me-2"></i>Annuler</a></li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="reservations.php?action=supprimer&id=<?php echo $reservation['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette réservation ?')"><i class="fas fa-trash me-2"></i>Supprimer</a></li>
                                                    </ul>
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