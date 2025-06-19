<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Récupération des paramètres
$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date_arrivee = isset($_GET['date_arrivee']) ? cleanInput($_GET['date_arrivee']) : '';
$date_depart = isset($_GET['date_depart']) ? cleanInput($_GET['date_depart']) : '';
$nb_personnes = isset($_GET['nb_personnes']) ? (int)$_GET['nb_personnes'] : 2;

// Vérification de l'existence de l'hôtel
if ($hotel_id <= 0) {
    header('Location: index.php');
    exit;
}

// Récupération des informations de l'hôtel
$hotel = fetchOne("SELECT * FROM hotels WHERE id = ?", "i", [$hotel_id]);

if (!$hotel) {
    header('Location: index.php');
    exit;
}

// Récupération des chambres disponibles
$chambres = [];
if (!empty($date_arrivee) && !empty($date_depart)) {
    $sql = "SELECT c.* FROM chambres c 
           WHERE c.hotel_id = ? 
           AND c.capacite >= ? 
           AND c.disponible = 1
           AND NOT EXISTS (
               SELECT 1 FROM reservations r 
               WHERE r.chambre_id = c.id 
               AND r.statut != 'annulée'
               AND (
                   (r.date_arrivee <= ? AND r.date_depart > ?) OR
                   (r.date_arrivee < ? AND r.date_depart >= ?) OR
                   (r.date_arrivee >= ? AND r.date_depart <= ?)
               )
           )
           ORDER BY c.prix_nuit ASC";
    
    $params = [$hotel_id, $nb_personnes, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
    $result = executeQuery($sql, "iissssss", $params);
    
    while ($row = $result->fetch_assoc()) {
        $chambres[] = $row;
    }
}

// Calcul du nombre de nuits
$nb_nuits = 0;
if (!empty($date_arrivee) && !empty($date_depart)) {
    $date1 = new DateTime($date_arrivee);
    $date2 = new DateTime($date_depart);
    $nb_nuits = $date2->diff($date1)->days;
}

include 'includes/header.php';
?>

<!-- Bannière de l'hôtel -->
<section class="hotel-banner" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/hotels/<?php echo htmlspecialchars($hotel['image_principale']); ?>')">
    <div class="container py-5">
        <div class="row py-5">
            <div class="col-lg-8 text-white">
                <h1 class="display-4"><?php echo htmlspecialchars($hotel['nom']); ?></h1>
                <div class="mb-3">
                    <?php for ($i = 0; $i < $hotel['etoiles']; $i++) : ?>
                        <i class="fas fa-star text-gold"></i>
                    <?php endfor; ?>
                </div>
                <p class="lead">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?php echo htmlspecialchars($hotel['adresse']); ?>, <?php echo htmlspecialchars($hotel['ville']); ?>, <?php echo htmlspecialchars($hotel['pays']); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Détails de l'hôtel -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="mb-4">À propos de l'hôtel</h2>
                <p class="mb-4"><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
                
                <!-- Formulaire de recherche de disponibilité -->
                <div class="availability-form bg-light p-4 rounded mb-5">
                    <h3 class="mb-3">Vérifier la disponibilité</h3>
                    <form action="hotel.php" method="GET" class="row g-3 needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?php echo $hotel_id; ?>">
                        <div class="col-md-3">
                            <label for="date_arrivee" class="form-label">Arrivée</label>
                            <input type="date" class="form-control" id="date_arrivee" name="date_arrivee" value="<?php echo $date_arrivee; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="date_depart" class="form-label">Départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?php echo $date_depart; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="nb_personnes" class="form-label">Personnes</label>
                            <select class="form-select" id="nb_personnes" name="nb_personnes">
                                <?php for ($i = 1; $i <= 6; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php echo $nb_personnes === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-gold w-100">Vérifier</button>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($date_arrivee) && !empty($date_depart)) : ?>
                    <!-- Liste des chambres disponibles -->
                    <h3 class="mb-4">Chambres disponibles</h3>
                    
                    <?php if (empty($chambres)) : ?>
                        <div class="alert alert-info">
                            Aucune chambre disponible pour ces dates. Veuillez essayer d'autres dates.
                        </div>
                    <?php else : ?>
                        <?php foreach ($chambres as $chambre) : ?>
                            <div class="card mb-4 shadow-sm">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="assets/images/chambres/<?php echo htmlspecialchars($chambre['image']); ?>" 
                                             class="img-fluid rounded-start h-100" 
                                             alt="<?php echo htmlspecialchars($chambre['type']); ?>" 
                                             style="object-fit: cover;"
                                             onerror="this.src='assets/images/chambre_default.jpg'">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h4 class="card-title"><?php echo htmlspecialchars($chambre['type']); ?></h4>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($chambre['description'])); ?></p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-user-friends me-2"></i> Capacité: <?php echo $chambre['capacite']; ?> personnes</li>
                                                <li><i class="fas fa-bed me-2"></i> Chambre <?php echo htmlspecialchars($chambre['numero']); ?></li>
                                            </ul>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <p class="text-gold fw-bold mb-0"><?php echo number_format($chambre['prix_nuit'], 2, ',', ' '); ?> € / nuit</p>
                                                    <p class="text-muted small">Total pour <?php echo $nb_nuits; ?> nuits: <?php echo number_format($chambre['prix_nuit'] * $nb_nuits, 2, ',', ' '); ?> €</p>
                                                </div>
                                                <a href="reserver.php?chambre_id=<?php echo $chambre['id']; ?>&date_arrivee=<?php echo urlencode($date_arrivee); ?>&date_depart=<?php echo urlencode($date_depart); ?>" class="btn btn-gold">Réserver</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <!-- Carte et informations complémentaires -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Emplacement</h4>
                        <div class="ratio ratio-4x3 mb-3">
                            <iframe src="https://maps.google.com/maps?q=<?php echo urlencode($hotel['adresse'] . ', ' . $hotel['ville'] . ', ' . $hotel['pays']); ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>
                        </div>
                        <p class="card-text">
                            <i class="fas fa-map-marker-alt me-2 text-gold"></i>
                            <?php echo htmlspecialchars($hotel['adresse']); ?><br>
                            <?php echo htmlspecialchars($hotel['ville']); ?>, <?php echo htmlspecialchars($hotel['pays']); ?>
                        </p>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Services de l'hôtel</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-wifi me-2 text-gold"></i> Wi-Fi gratuit</li>
                            <li class="mb-2"><i class="fas fa-parking me-2 text-gold"></i> Parking</li>
                            <li class="mb-2"><i class="fas fa-concierge-bell me-2 text-gold"></i> Service de conciergerie</li>
                            <li class="mb-2"><i class="fas fa-utensils me-2 text-gold"></i> Restaurant gastronomique</li>
                            <li class="mb-2"><i class="fas fa-spa me-2 text-gold"></i> Spa & bien-être</li>
                            <li class="mb-2"><i class="fas fa-dumbbell me-2 text-gold"></i> Salle de fitness</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>