<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Récupération des informations de l'administrateur
$admin = fetchOne("SELECT * FROM administrateurs WHERE id = ?", "i", [$_SESSION['admin_id']]);

// Statistiques générales
$stats = [
    'total_hotels' => 0,
    'total_chambres' => 0,
    'total_reservations' => 0,
    'total_clients' => 0,
    'reservations_recentes' => [],
    'taux_occupation' => 0
];

// Nombre total d'hôtels
$result = fetchOne("SELECT COUNT(*) as total FROM hotels");
$stats['total_hotels'] = $result['total'];

// Nombre total de chambres
$result = fetchOne("SELECT COUNT(*) as total FROM chambres");
$stats['total_chambres'] = $result['total'];

// Nombre total de réservations
$result = fetchOne("SELECT COUNT(*) as total FROM reservations");
$stats['total_reservations'] = $result['total'];

// Nombre total de clients
$result = fetchOne("SELECT COUNT(*) as total FROM clients");
$stats['total_clients'] = $result['total'];

// Taux d'occupation (réservations confirmées / total des chambres)
$result = fetchOne("SELECT 
    (SELECT COUNT(*) FROM reservations WHERE statut = 'confirmée' AND date_depart >= CURDATE()) as reservations_actives,
    (SELECT COUNT(*) FROM chambres) as total_chambres");

if ($result && $result['total_chambres'] > 0) {
    $stats['taux_occupation'] = round(($result['reservations_actives'] / $result['total_chambres']) * 100);
}

// Récupération des réservations récentes
$sql = "SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, 
       ch.type as chambre_type, ch.numero as chambre_numero, 
       h.nom as hotel_nom, h.ville as hotel_ville 
       FROM reservations r 
       JOIN clients c ON r.client_id = c.id 
       JOIN chambres ch ON r.chambre_id = ch.id 
       JOIN hotels h ON ch.hotel_id = h.id 
       ORDER BY r.date_reservation DESC 
       LIMIT 5";

$result = executeQuery($sql);
while ($row = $result->fetch_assoc()) {
    $stats['reservations_recentes'][] = $row;
}

// Titre de la page
$page_title = "Tableau de bord administrateur";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Tableau de bord administrateur</h1>
                <span class="badge bg-gold">Connecté en tant que <?php echo htmlspecialchars($admin['role']); ?></span>
            </div>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Hôtels</h5>
                            <p class="display-6 mb-0"><?php echo $stats['total_hotels']; ?></p>
                            <a href="hotels.php" class="btn btn-sm btn-outline-secondary mt-3">Gérer les hôtels</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Chambres</h5>
                            <p class="display-6 mb-0"><?php echo $stats['total_chambres']; ?></p>
                            <a href="chambres.php" class="btn btn-sm btn-outline-secondary mt-3">Gérer les chambres</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Réservations</h5>
                            <p class="display-6 mb-0"><?php echo $stats['total_reservations']; ?></p>
                            <a href="reservations.php" class="btn btn-sm btn-outline-secondary mt-3">Gérer les réservations</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Clients</h5>
                            <p class="display-6 mb-0"><?php echo $stats['total_clients']; ?></p>
                            <a href="clients.php" class="btn btn-sm btn-outline-secondary mt-3">Gérer les clients</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Taux d'occupation</h5>
                            <p class="display-6 mb-0"><?php echo $stats['taux_occupation']; ?>%</p>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar bg-gold" role="progressbar" style="width: <?php echo $stats['taux_occupation']; ?>%" aria-valuenow="<?php echo $stats['taux_occupation']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-0">Revenus du mois</h5>
                            <?php 
                            $revenus = fetchOne("SELECT SUM(prix_total) as total FROM reservations WHERE MONTH(date_reservation) = MONTH(CURRENT_DATE()) AND YEAR(date_reservation) = YEAR(CURRENT_DATE()) AND statut = 'confirmée'");
                            $montant = $revenus['total'] ?: 0;
                            ?>
                            <p class="display-6 mb-0"><?php echo number_format($montant, 2, ',', ' '); ?> €</p>
                            <a href="rapports.php" class="btn btn-sm btn-outline-secondary mt-3">Voir les rapports</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dernières réservations -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Dernières réservations</h5>
                    <a href="reservations.php" class="btn btn-sm btn-outline-secondary">Voir toutes</a>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['reservations_recentes'])) : ?>
                        <p class="text-center text-muted my-4">Aucune réservation récente.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Hôtel</th>
                                        <th>Chambre</th>
                                        <th>Dates</th>
                                        <th>Statut</th>
                                        <th>Prix</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['reservations_recentes'] as $reservation) : ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($reservation['hotel_nom']); ?></div>
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
                                            <td><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> €</td>
                                            <td>
                                                <a href="reservation_details.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-secondary">Détails</a>
                                                <div class="btn-group ms-1">
                                                    <button type="button" class="btn btn-sm btn-outline-gold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="modifier_reservation.php?id=<?php echo $reservation['id']; ?>">Modifier</a></li>
                                                        <?php if ($reservation['statut'] === 'en attente') : ?>
                                                            <li><a class="dropdown-item text-success" href="confirmer_reservation.php?id=<?php echo $reservation['id']; ?>">Confirmer</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($reservation['statut'] !== 'annulée') : ?>
                                                            <li><a class="dropdown-item text-danger" href="annuler_reservation.php?id=<?php echo $reservation['id']; ?>">Annuler</a></li>
                                                        <?php endif; ?>
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