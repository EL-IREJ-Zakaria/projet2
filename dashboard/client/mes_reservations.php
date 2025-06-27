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

// Traitement de l'annulation d'une réservation
if (isset($_POST['annuler_reservation']) && isset($_POST['reservation_id']) && isset($_POST['csrf_token'])) {
    // Vérification du jeton CSRF
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $reservation_id = (int)$_POST['reservation_id'];
        
        // Vérifier que la réservation appartient bien au client
        $reservation = fetchOne("SELECT * FROM reservations WHERE id = ? AND client_id = ?", "ii", [$reservation_id, $_SESSION['user_id']]);
        
        if ($reservation) {
            // Vérifier que la date d'arrivée est à plus de 48h
            $date_arrivee = new DateTime($reservation['date_arrivee']);
            $now = new DateTime();
            $diff = $now->diff($date_arrivee);
            
            if ($diff->days > 2) {
                // Annuler la réservation
                executeQuery("UPDATE reservations SET statut = 'annulée' WHERE id = ?", "i", [$reservation_id]);
                $success_message = "Votre réservation a été annulée avec succès.";
            } else {
                $error_message = "Vous ne pouvez pas annuler une réservation moins de 48h avant la date d'arrivée.";
            }
        } else {
            $error_message = "Réservation introuvable.";
        }
    } else {
        $error_message = "Erreur de sécurité, veuillez réessayer.";
    }
}

// Récupération des réservations du client
$sql = "SELECT r.*, c.type as chambre_type, c.numero as chambre_numero, h.nom as hotel_nom, h.ville as hotel_ville, h.image_principale as hotel_image 
       FROM reservations r 
       JOIN chambres c ON r.chambre_id = c.id 
       JOIN hotels h ON c.hotel_id = h.id 
       WHERE r.client_id = ? 
       ORDER BY r.date_arrivee DESC";

$reservations = [];
$result = executeQuery($sql, "i", [$_SESSION['user_id']]);
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

// Titre de la page
$page_title = "Mes réservations";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Mes réservations</h1>
            </div>
            
            <?php if (isset($success_message)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($reservations)) : ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times text-muted fa-4x mb-3"></i>
                        <h4>Aucune réservation</h4>
                        <p class="text-muted">Vous n'avez pas encore effectué de réservation.</p>
                        <a href="/projet2/index.php#search" class="btn btn-gold">Réserver maintenant</a>
                    </div>
                </div>
            <?php else : ?>
                <!-- Réservations à venir -->
                <h4 class="mb-3">Réservations à venir</h4>
                <div class="row mb-4">
                    <?php 
                    $has_upcoming = false;
                    foreach ($reservations as $reservation) : 
                        if ($reservation['date_arrivee'] >= date('Y-m-d') && $reservation['statut'] !== 'annulée') :
                            $has_upcoming = true;
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="/projet2/assets/images/hotels/<?php echo htmlspecialchars($reservation['hotel_image']); ?>" 
                                         class="img-fluid rounded-start h-100" 
                                         alt="<?php echo htmlspecialchars($reservation['hotel_nom']); ?>" 
                                         style="object-fit: cover;"
                                         onerror="this.src='/projet2/assets/images/hotel_default.jpg'">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($reservation['hotel_nom']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($reservation['hotel_ville']); ?></p>
                                        <p class="card-text">
                                            <small><i class="fas fa-bed me-2 text-gold"></i><?php echo htmlspecialchars($reservation['chambre_type']); ?> (N°<?php echo htmlspecialchars($reservation['chambre_numero']); ?>)</small><br>
                                            <small><i class="fas fa-calendar me-2 text-gold"></i><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?> - <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></small><br>
                                            <small class="text-gold fw-bold"><i class="fas fa-euro-sign me-2"></i><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> MAD</small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success">Confirmée</span>
                                            <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" name="annuler_reservation" class="btn btn-sm btn-outline-danger">Annuler</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    
                    if (!$has_upcoming) :
                    ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Vous n'avez pas de réservation à venir.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Historique des réservations -->
                <h4 class="mb-3">Historique des réservations</h4>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Hôtel</th>
                                    <th>Chambre</th>
                                    <th>Dates</th>
                                    <th>Statut</th>
                                    <th>Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $has_history = false;
                                foreach ($reservations as $reservation) : 
                                    if ($reservation['date_depart'] < date('Y-m-d') || $reservation['statut'] === 'annulée') :
                                        $has_history = true;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['hotel_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['chambre_type']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?> - <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></td>
                                    <td>
                                        <?php if ($reservation['statut'] === 'confirmée') : ?>
                                            <span class="badge bg-success">Confirmée</span>
                                        <?php elseif ($reservation['statut'] === 'annulée') : ?>
                                            <span class="badge bg-danger">Annulée</span>
                                        <?php else : ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> MAD</td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                
                                if (!$has_history) :
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">Aucun historique de réservation.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>