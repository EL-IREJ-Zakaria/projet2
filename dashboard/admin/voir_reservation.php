<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Vérifier si l'ID de la réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: reservations.php');
    exit;
}

$reservation_id = intval($_GET['id']);

// Récupérer les détails de la réservation
$sql = "SELECT r.*, 
        c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone, 
        h.nom as hotel_nom, h.ville as hotel_ville, h.adresse as hotel_adresse, h.pays as hotel_pays, h.etoiles as hotel_etoiles, 
        ch.numero as chambre_numero, ch.type as chambre_type, ch.prix_nuit as chambre_prix_nuit, ch.capacite as chambre_capacite, ch.image as chambre_image 
        FROM reservations r 
        JOIN clients c ON r.client_id = c.id 
        JOIN chambres ch ON r.chambre_id = ch.id 
        JOIN hotels h ON ch.hotel_id = h.id 
        WHERE r.id = ?";

$reservation = fetchOne($sql, "i", [$reservation_id]);

if (!$reservation) {
    header('Location: reservations.php');
    exit;
}

// Calculer le nombre de nuits
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$interval = $date_arrivee->diff($date_depart);
$nb_nuits = $interval->days;

// Titre de la page
$page_title = "Détails de la réservation #" . $reservation_id;
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Détails de la réservation #<?php echo $reservation_id; ?></h1>
                <a href="reservations.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
            </div>
            
            <div class="row">
                <!-- Informations générales -->
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Informations générales</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Statut :</strong></p>
                                    <?php if ($reservation['statut'] === 'en_attente') : ?>
                                        <span class="badge bg-warning">En attente</span>
                                    <?php elseif ($reservation['statut'] === 'confirmee') : ?>
                                        <span class="badge bg-success">Confirmée</span>
                                    <?php elseif ($reservation['statut'] === 'annulee') : ?>
                                        <span class="badge bg-danger">Annulée</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Date de réservation :</strong></p>
                                    <p><?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Date d'arrivée :</strong></p>
                                    <p><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Date de départ :</strong></p>
                                    <p><?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Nombre de nuits :</strong></p>
                                    <p><?php echo $nb_nuits; ?> nuit<?php echo $nb_nuits > 1 ? 's' : ''; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Montant total :</strong></p>
                                    <p class="fw-bold"><?php echo number_format($reservation['montant_total'], 2, ',', ' '); ?> MAD</p>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['commentaires'])) : ?>
                                <div class="mb-0">
                                    <p class="mb-1"><strong>Commentaires :</strong></p>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($reservation['commentaires'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between">
                                <?php if ($reservation['statut'] === 'en_attente') : ?>
                                    <a href="reservations.php?action=confirmer&id=<?php echo $reservation_id; ?>" class="btn btn-success" onclick="return confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')"><i class="fas fa-check me-2"></i>Confirmer</a>
                                <?php endif; ?>
                                
                                <?php if ($reservation['statut'] === 'en_attente' || $reservation['statut'] === 'confirmee') : ?>
                                    <a href="reservations.php?action=annuler&id=<?php echo $reservation_id; ?>" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')"><i class="fas fa-ban me-2"></i>Annuler</a>
                                <?php endif; ?>
                                
                                <a href="reservations.php?action=supprimer&id=<?php echo $reservation_id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette réservation ?')"><i class="fas fa-trash me-2"></i>Supprimer</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations client -->
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Informations client</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Nom :</strong></p>
                            <p><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></p>
                            
                            <p class="mb-1"><strong>Email :</strong></p>
                            <p><a href="mailto:<?php echo htmlspecialchars($reservation['client_email']); ?>"><?php echo htmlspecialchars($reservation['client_email']); ?></a></p>
                            
                            <p class="mb-1"><strong>Téléphone :</strong></p>
                            <p><a href="tel:<?php echo htmlspecialchars($reservation['client_telephone']); ?>"><?php echo htmlspecialchars($reservation['client_telephone']); ?></a></p>
                            
                            <div class="mt-3">
                                <a href="clients.php?id=<?php echo $reservation['client_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-user me-2"></i>Voir le profil client</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informations hôtel et chambre -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Informations hôtel et chambre</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($reservation['hotel_nom']); ?> 
                                <?php for ($i = 0; $i < $reservation['hotel_etoiles']; $i++) : ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php endfor; ?>
                            </h6>
                            
                            <p class="mb-1"><strong>Adresse :</strong></p>
                            <p><?php echo htmlspecialchars($reservation['hotel_adresse']); ?><br>
                               <?php echo htmlspecialchars($reservation['hotel_ville'] . ', ' . $reservation['hotel_pays']); ?></p>
                            
                            <p class="mb-1"><strong>Chambre :</strong></p>
                            <p>Chambre <?php echo htmlspecialchars($reservation['chambre_numero']); ?> (<?php echo htmlspecialchars($reservation['chambre_type']); ?>)</p>
                            
                            <p class="mb-1"><strong>Capacité :</strong></p>
                            <p><?php echo $reservation['chambre_capacite']; ?> personne<?php echo $reservation['chambre_capacite'] > 1 ? 's' : ''; ?></p>
                            
                            <p class="mb-1"><strong>Prix par nuit :</strong></p>
                            <p><?php echo number_format($reservation['chambre_prix_nuit'], 2, ',', ' '); ?> MAD</p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($reservation['chambre_image'])) : ?>
                                <img src="../../assets/images/chambres/<?php echo htmlspecialchars($reservation['chambre_image']); ?>" alt="Image de la chambre" class="img-fluid rounded">
                            <?php else : ?>
                                <div class="bg-light rounded p-5 text-center text-muted">
                                    <i class="fas fa-bed fa-3x mb-3"></i>
                                    <p>Aucune image disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Détails du paiement -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Détails du paiement</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Prix par nuit</th>
                                    <td class="text-end"><?php echo number_format($reservation['chambre_prix_nuit'], 2, ',', ' '); ?> MAD</td>
                                </tr>
                                <tr>
                                    <th>Nombre de nuits</th>
                                    <td class="text-end"><?php echo $nb_nuits; ?></td>
                                </tr>
                                <tr>
                                    <th>Sous-total</th>
                                    <td class="text-end"><?php echo number_format($reservation['chambre_prix_nuit'] * $nb_nuits, 2, ',', ' '); ?> MAD</td>
                                </tr>
                                <tr>
                                    <th>Taxes (<?php echo number_format($reservation['taxe_sejour'], 2, ',', ' '); ?> MAD/nuit)</th>
                                    <td class="text-end"><?php echo number_format($reservation['taxe_sejour'] * $nb_nuits, 2, ',', ' '); ?> MAD</td>
                                </tr>
                                <tr class="table-active">
                                    <th>Montant total</th>
                                    <td class="text-end fw-bold"><?php echo number_format($reservation['montant_total'], 2, ',', ' '); ?> MAD</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>