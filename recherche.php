<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Récupération des paramètres de recherche
$ville = isset($_GET['ville']) ? cleanInput($_GET['ville']) : '';
$date_arrivee = isset($_GET['date_arrivee']) ? cleanInput($_GET['date_arrivee']) : '';
$date_depart = isset($_GET['date_depart']) ? cleanInput($_GET['date_depart']) : '';
$nb_personnes = isset($_GET['nb_personnes']) ? (int)$_GET['nb_personnes'] : 2;

// Validation des dates
$date_error = false;
if (!empty($date_arrivee) && !empty($date_depart)) {
    $today = date('Y-m-d');
    if ($date_arrivee < $today) {
        $date_error = "La date d'arrivée ne peut pas être dans le passé.";
    } elseif ($date_depart <= $date_arrivee) {
        $date_error = "La date de départ doit être postérieure à la date d'arrivée.";
    }
}

// Recherche des hôtels disponibles
$hotels = [];
if (!empty($ville) && !empty($date_arrivee) && !empty($date_depart) && !$date_error) {
    // Requête pour trouver les hôtels dans la ville spécifiée avec des chambres disponibles
    $sql = "SELECT h.*, 
           (SELECT MIN(c.prix_nuit) FROM chambres c WHERE c.hotel_id = h.id AND c.capacite >= ? AND c.disponible = 1) as prix_min
           FROM hotels h 
           WHERE h.ville = ? 
           AND EXISTS (
               SELECT 1 FROM chambres c 
               WHERE c.hotel_id = h.id 
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
           )
           ORDER BY h.etoiles DESC, prix_min ASC";
    
    $params = [$nb_personnes, $ville, $nb_personnes, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
    $result = executeQuery($sql, "issssssss", $params);
    
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

include 'includes/header.php';
?>

<!-- Bannière de recherche -->
<section class="search-banner py-5 bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Recherche d'hôtels</h1>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-form shadow p-4 bg-white">
                    <form action="recherche.php" method="GET" class="row g-3 needs-validation" novalidate>
                        <div class="col-md-3">
                            <label for="ville" class="form-label">Destination</label>
                            <select class="form-select" id="ville" name="ville" required>
                                <option value="" <?php echo empty($ville) ? 'selected' : ''; ?> disabled>Choisir une ville</option>
                                <option value="Paris" <?php echo $ville === 'Paris' ? 'selected' : ''; ?>>Paris</option>
                                <option value="Nice" <?php echo $ville === 'Nice' ? 'selected' : ''; ?>>Nice</option>
                                <option value="Cannes" <?php echo $ville === 'Cannes' ? 'selected' : ''; ?>>Cannes</option>
                                <option value="Lyon" <?php echo $ville === 'Lyon' ? 'selected' : ''; ?>>Lyon</option>
                                <option value="Bordeaux" <?php echo $ville === 'Bordeaux' ? 'selected' : ''; ?>>Bordeaux</option>
                            </select>
                            <div class="invalid-feedback">Veuillez choisir une destination.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_arrivee" class="form-label">Arrivée</label>
                            <input type="date" class="form-control" id="date_arrivee" name="date_arrivee" value="<?php echo $date_arrivee; ?>" required>
                            <div class="invalid-feedback">Veuillez choisir une date d'arrivée.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_depart" class="form-label">Départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?php echo $date_depart; ?>" required>
                            <div class="invalid-feedback">Veuillez choisir une date de départ.</div>
                        </div>
                        <div class="col-md-2">
                            <label for="nb_personnes" class="form-label">Personnes</label>
                            <select class="form-select" id="nb_personnes" name="nb_personnes">
                                <?php for ($i = 1; $i <= 6; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php echo $nb_personnes === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-gold w-100">Rechercher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($date_error) : ?>
<div class="container mt-4">
    <div class="alert alert-danger">
        <?php echo $date_error; ?>
    </div>
</div>
<?php endif; ?>

<!-- Résultats de recherche -->
<section class="search-results py-5">
    <div class="container">
        <?php if (!empty($ville) && !empty($date_arrivee) && !empty($date_depart) && !$date_error) : ?>
            <h2 class="mb-4">Résultats pour <?php echo htmlspecialchars($ville); ?></h2>
            
            <?php if (empty($hotels)) : ?>
                <div class="alert alert-info">
                    Aucun hôtel disponible pour ces critères. Veuillez modifier votre recherche.
                </div>
            <?php else : ?>
                <div class="row">
                    <?php foreach ($hotels as $hotel) : ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card hotel-card h-100 shadow-sm">
                                <img src="assets/images/hotels/<?php echo htmlspecialchars($hotel['image_principale']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($hotel['nom']); ?>" 
                                     onerror="this.src='assets/images/hotel_default.jpg'">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($hotel['nom']); ?></h5>
                                        <div>
                                            <?php for ($i = 0; $i < $hotel['etoiles']; $i++) : ?>
                                                <i class="fas fa-star text-gold"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        <?php echo htmlspecialchars($hotel['ville']); ?>, <?php echo htmlspecialchars($hotel['pays']); ?>
                                    </p>
                                    <p class="card-text mb-3"><?php echo substr(htmlspecialchars($hotel['description']), 0, 100); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="text-gold fw-bold mb-0">À partir de <?php echo number_format($hotel['prix_min'], 2, ',', ' '); ?> €</p>
                                        <a href="hotel.php?id=<?php echo $hotel['id']; ?>&date_arrivee=<?php echo urlencode($date_arrivee); ?>&date_depart=<?php echo urlencode($date_depart); ?>&nb_personnes=<?php echo $nb_personnes; ?>" class="btn btn-outline-gold">Voir les chambres</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="text-center py-5">
                <h2 class="mb-4">Trouvez l'hôtel parfait pour votre séjour</h2>
                <p class="lead">Utilisez le formulaire ci-dessus pour rechercher parmi nos hôtels de luxe.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>