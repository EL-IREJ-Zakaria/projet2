<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Vérifier si l'ID du client est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: clients.php');
    exit;
}

$client_id = intval($_GET['id']);

// Récupérer les détails du client
$client = fetchOne("SELECT * FROM clients WHERE id = ?", "i", [$client_id]);

if (!$client) {
    header('Location: clients.php');
    exit;
}

// Récupérer les statistiques du client
$stats = fetchOne("SELECT 
                    COUNT(id) as nb_reservations, 
                    SUM(montant_total) as montant_total,
                    MIN(date_reservation) as premiere_reservation,
                    MAX(date_arrivee) as derniere_arrivee
                FROM reservations 
                WHERE client_id = ?", "i", [$client_id]);

// Récupérer les réservations du client
$reservations = fetchAll("SELECT r.*, 
                        h.nom as hotel_nom, h.ville as hotel_ville, 
                        ch.numero as chambre_numero, ch.type as chambre_type 
                        FROM reservations r 
                        JOIN chambres ch ON r.chambre_id = ch.id 
                        JOIN hotels h ON ch.hotel_id = h.id 
                        WHERE r.client_id = ? 
                        ORDER BY r.date_arrivee DESC", "i", [$client_id]);

// Titre de la page
$page_title = "Profil client - " . $client['prenom'] . " " . $client['nom'];
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Profil client</h1>
                <a href="clients.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
            </div>
            
            <div class="row">
                <!-- Informations client -->
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="avatar-placeholder rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    <?php echo strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1)); ?>
                                </div>
                                <h4 class="mt-3"><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></h4>
                                <p class="text-muted">Client depuis <?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1"><strong>Email :</strong></p>
                                <p><a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a></p>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1"><strong>Téléphone :</strong></p>
                                <p><a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>"><?php echo htmlspecialchars($client['telephone']); ?></a></p>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1"><strong>Adresse :</strong></p>
                                <p><?php echo htmlspecialchars($client['adresse']); ?><br>
                                   <?php echo htmlspecialchars($client['code_postal'] . ' ' . $client['ville']); ?><br>
                                   <?php echo htmlspecialchars($client['pays']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques client -->
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Statistiques</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Nombre de réservations</h6>
                                            <h2 class="mb-0"><?php echo $stats['nb_reservations']; ?></h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Montant total dépensé</h6>
                                            <h2 class="mb-0"><?php echo number_format($stats['montant_total'] ?? 0, 2, ',', ' '); ?> €</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Première réservation</h6>
                                            <h5 class="mb-0"><?php echo $stats['premiere_reservation'] ? date('d/m/Y', strtotime($stats['premiere_reservation'])) : 'Aucune'; ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Dernière arrivée prévue</h6>
                                            <h5 class="mb-0"><?php echo $stats['derniere_arrivee'] ? date('d/m/Y', strtotime($stats['derniere_arrivee'])) : 'Aucune'; ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historique des réservations -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Historique des réservations</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)) : ?>
                        <p class="text-center text-muted my-4">Aucune réservation trouvée pour ce client.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Réf.</th>
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
                                                <a href="voir_reservation.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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