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

// Récupération des réservations du client
$sql = "SELECT r.*, c.type as chambre_type, c.numero as chambre_numero, h.nom as hotel_nom, h.ville as hotel_ville 
       FROM reservations r 
       JOIN chambres c ON r.chambre_id = c.id 
       JOIN hotels h ON c.hotel_id = h.id 
       WHERE r.client_id = ? 
       ORDER BY r.date_arrivee DESC 
       LIMIT 5";

$reservations = [];
$result = executeQuery($sql, "i", [$_SESSION['user_id']]);
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

// Calcul des statistiques
$stats = [
    'total_reservations' => 0,
    'nuits_reservees' => 0,
    'montant_total' => 0,
    'prochaine_reservation' => null
];

$sql = "SELECT COUNT(*) as total, 
       SUM(DATEDIFF(date_depart, date_arrivee)) as nuits, 
       SUM(prix_total) as montant 
       FROM reservations 
       WHERE client_id = ? 
       AND statut != 'annulée'";

$result = fetchOne($sql, "i", [$_SESSION['user_id']]);
if ($result) {
    $stats['total_reservations'] = $result['total'];
    $stats['nuits_reservees'] = $result['nuits'] ?: 0;
    $stats['montant_total'] = $result['montant'] ?: 0;
}

// Prochaine réservation
$sql = "SELECT date_arrivee, hotel_nom 
       FROM (
           SELECT r.date_arrivee, h.nom as hotel_nom 
           FROM reservations r 
           JOIN chambres c ON r.chambre_id = c.id 
           JOIN hotels h ON c.hotel_id = h.id 
           WHERE r.client_id = ? 
           AND r.date_arrivee >= CURDATE() 
           AND r.statut = 'confirmée' 
           ORDER BY r.date_arrivee ASC 
           LIMIT 1
       ) as prochaine";

$prochaine = fetchOne($sql, "i", [$_SESSION['user_id']]);
if ($prochaine) {
    $stats['prochaine_reservation'] = $prochaine;
}

// Titre de la page
$page_title = "Tableau de bord";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Bienvenue, <?php echo htmlspecialchars($client['prenom']); ?> !</h1>
                <a href="modifier_profil.php" class="btn btn-outline-gold"><i class="fas fa-user-edit me-2"></i>Modifier mon profil</a>
            </div>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Réservations</h5>
                            <p class="display-6 mb-0"><?php echo $stats['total_reservations']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Nuits réservées</h5>
                            <p class="display-6 mb-0"><?php echo $stats['nuits_reservees']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Montant total</h5>
                            <p class="display-6 mb-0"><?php echo number_format($stats['montant_total'], 2, ',', ' '); ?> MAD</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Prochaine réservation</h5>
                            <?php if ($stats['prochaine_reservation']) : ?>
                                <p class="mb-0 fw-bold"><?php echo is_array($stats['prochaine_reservation']) && isset($stats['prochaine_reservation']['date_arrivee']) ? date('d/m/Y', strtotime($stats['prochaine_reservation']['date_arrivee'])) : ''; ?></p>
                                <p class="small text-muted"><?php echo $stats['prochaine_reservation'] && is_array($stats['prochaine_reservation']) && isset($stats['prochaine_reservation']['hotel_nom']) ? htmlspecialchars($stats['prochaine_reservation']['hotel_nom']) : ''; ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0">Aucune</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Dernières réservations -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Dernières réservations</h5>
                    <a href="mes_reservations.php" class="btn btn-sm btn-outline-secondary">Voir toutes</a>
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)) : ?>
                        <p class="text-center text-muted my-4">Vous n'avez pas encore de réservation.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Hôtel</th>
                                        <th>Chambre</th>
                                        <th>Dates</th>
                                        <th>Statut</th>
                                        <th>Prix</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation) : ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($reservation['hotel_nom']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($reservation['hotel_ville']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($reservation['chambre_type']); ?></div>
                                                <div class="small text-muted">N°<?php echo htmlspecialchars($reservation['chambre_numero']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></div>
                                                <div class="small text-muted">au <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($reservation['statut'] === 'confirmée') : ?>
                                                    <span class="badge bg-success">Confirmée</span>
                                                <?php elseif ($reservation['statut'] === 'en attente') : ?>
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                <?php else : ?>
                                                    <span class="badge bg-danger">Annulée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> MAD</td>
                                            <td>
                                                <a href="voir_reservation.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-secondary">Détails</a>
                                                <?php if ($reservation['statut'] !== 'annulée' && strtotime($reservation['date_arrivee']) > time()) : ?>
                                                    <a href="mes_reservations.php?action=cancel&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">Annuler</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Suggestions d'hôtels -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Suggestions pour vous</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card h-100">
                                <img src="../../assets/images/hotels/hotel_marrakech.jpg" class="card-img-top" alt="Hôtel à Marrakech" onerror="this.src='../../assets/images/hotel_default.jpg'">
                                <div class="card-body">
                                    <h5 class="card-title">Le Grand Palais</h5>
                                    <p class="card-text">Marrakech, Maroc</p>
                                    <a href="../../hotel.php?id=1" class="btn btn-sm btn-outline-gold">Voir l'hôtel</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card h-100">
                                <img src="../../assets/images/hotels/hotel_fes.jpg" class="card-img-top" alt="Hôtel à Fès" onerror="this.src='../../assets/images/hotel_default.jpg'">
                                <div class="card-body">
                                    <h5 class="card-title">Château Royal</h5>
                                    <p class="card-text">Cannes, France</p>
                                    <a href