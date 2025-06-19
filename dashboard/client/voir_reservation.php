<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: ../../login.php');
    exit;
}

// Récupération de l'ID de la réservation
$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservation_id <= 0) {
    header('Location: mes_reservations.php');
    exit;
}

// Récupération des informations de la réservation
$sql = "SELECT r.*, 
       c.numero as chambre_numero, c.type as chambre_type, c.capacite as chambre_capacite, c.prix_nuit, c.image as chambre_image,
       h.nom as hotel_nom, h.ville as hotel_ville, h.pays as hotel_pays, h.adresse as hotel_adresse, h.etoiles as hotel_etoiles
       FROM reservations r
       JOIN chambres c ON r.chambre_id = c.id
       JOIN hotels h ON c.hotel_id = h.id
       WHERE r.id = ? AND r.client_id = ?";

$reservation = fetchOne($sql, "ii", [$reservation_id, $_SESSION['user_id']]);

if (!$reservation) {
    header('Location: mes_reservations.php');
    exit;
}

// Calcul du nombre de nuits
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$nb_nuits = $date_depart->diff($date_arrivee)->days;

// Calcul des détails du prix
$prix_nuit = $reservation['prix_nuit'];
$sous_total = $prix_nuit * $nb_nuits;
$taxe = $sous_total * 0.10; // 10% de taxe
$total = $sous_total + $taxe;

// Vérifier si la réservation peut être annulée (48h avant l'arrivée)
$can_cancel = false;
$now = new DateTime();
$diff_hours = $date_arrivee->getTimestamp() - $now->getTimestamp();
$diff_hours = $diff_hours / 3600; // Conversion en heures

if ($diff_hours >= 48 && $reservation['statut'] !== 'annulée') {
    $can_cancel = true;
}

// Traitement de l'annulation
$success_message = '';
$error_message = '';

if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Erreur de sécurité, veuillez réessayer.";
    } elseif (!$can_cancel) {
        $error_message = "Cette réservation ne peut plus être annulée.";
    } else {
        // Mise à jour du statut de la réservation
        $update = executeQuery("UPDATE reservations SET statut = 'annulée' WHERE id = ? AND client_id = ?", "ii", [$reservation_id, $_SESSION['user_id']]);
        
        if ($update) {
            $success_message = "Votre réservation a été annulée avec succès.";
            $reservation['statut'] = 'annulée';
            $can_cancel = false;
        } else {
            $error_message = "Une erreur est survenue lors de l'annulation de la réservation.";
        }
    }
}

// Titre de la page
$page_title = "Détails de la réservation";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Détails de la réservation</h1>
                <a href="mes_reservations.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
            </div>
            
            <?php if (!empty($success_message)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Informations générales -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations générales</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Référence</p>
                                    <p class="fw-bold">#<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="mb-1 text-muted">Statut</p>
                                    <?php if ($reservation['statut'] === 'confirmée') : ?>
                                        <span class="badge bg-success">Confirmée</span>
                                    <?php elseif ($reservation['statut'] === 'annulée') : ?>
                                        <span class="badge bg-danger">Annulée</span>
                                    <?php else : ?>
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Date d'arrivée</p>
                                    <p class="fw-bold"><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Date de départ</p>
                                    <p class="fw-bold"><?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Durée du séjour</p>
                                    <p class="fw-bold"><?php echo $nb_nuits; ?> nuit<?php echo $nb_nuits > 1 ? 's' : ''; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Montant total</p>
                                    <p class="fw-bold text-gold"><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> €</p>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['commentaire'])) : ?>
                                <div class="mt-3">
                                    <p class="mb-1 text-muted">Commentaires</p>
                                    <p><?php echo nl2br(htmlspecialchars($reservation['commentaire'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($can_cancel) : ?>
                                <div class="mt-4">
                                    <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-danger"><i class="fas fa-times-circle me-2"></i>Annuler la réservation</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Informations sur l'hôtel et la chambre -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Hôtel et chambre</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Hôtel</h6>
                                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($reservation['hotel_nom']); ?></p>
                                    <div class="mb-2">
                                        <?php for ($i = 0; $i < $reservation['hotel_etoiles']; $i++) : ?>
                                            <i class="fas fa-star text-gold"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt me-2 text-gold"></i>
                                        <?php echo htmlspecialchars($reservation['hotel_adresse']); ?>
                                    </p>
                                    <p>
                                        <?php echo htmlspecialchars($reservation['hotel_ville']); ?>, 
                                        <?php echo htmlspecialchars($reservation['hotel_pays']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Chambre</h6>
                                    <?php if (!empty($reservation['chambre_image'])) : ?>
                                        <img src="../../assets/images/chambres/<?php echo htmlspecialchars($reservation['chambre_image']); ?>" 
                                             class="img-fluid rounded mb-3" 
                                             alt="<?php echo htmlspecialchars($reservation['chambre_type']); ?>" 
                                             onerror="this.src='../../assets/images/chambre_default.jpg'">
                                    <?php endif; ?>
                                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($reservation['chambre_type']); ?></p>
                                    <p class="mb-1">Chambre n°<?php echo htmlspecialchars($reservation['chambre_numero']); ?></p>
                                    <p class="mb-1">Capacité: <?php echo $reservation['chambre_capacite']; ?> personnes</p>
                                    <p>Prix par nuit: <?php echo number_format($reservation['prix_nuit'], 2, ',', ' '); ?> €</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Détails du paiement -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Détails du paiement</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td>Prix par nuit</td>
                                        <td class="text-end"><?php echo number_format($prix_nuit, 2, ',', ' '); ?> €</td>
                                    </tr>
                                    <tr>
                                        <td>Nombre de nuits</td>
                                        <td class="text-end"><?php echo $nb_nuits; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Sous-total</td>
                                        <td class="text-end"><?php echo number_format($sous_total, 2, ',', ' '); ?> €</td>
                                    </tr>
                                    <tr>
                                        <td>Taxes (10%)</td>
                                        <td class="text-end"><?php echo number_format($taxe, 2, ',', ' '); ?> €</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end text-gold"><?php echo number_format($total, 2, ',', ' '); ?> €</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Politique d'annulation -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Politique d'annulation</h5>
                            <p class="card-text">Annulation gratuite jusqu'à 48 heures avant l'arrivée. Après cette période, le montant total de la réservation sera facturé.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>