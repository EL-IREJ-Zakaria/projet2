<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Récupération des filtres
$ville = isset($_GET['ville']) ? cleanInput($_GET['ville']) : '';
$etoiles = isset($_GET['etoiles']) ? (int)$_GET['etoiles'] : 0;
$budget_min = isset($_GET['budget_min']) ? (int)$_GET['budget_min'] : 0;
$budget_max = isset($_GET['budget_max']) ? (int)$_GET['budget_max'] : 0;

// Récupération des prix minimum et maximum dynamiques
$prix_range_sql = "SELECT 
    MIN(c.prix_nuit) as prix_min_global, 
    MAX(c.prix_nuit) as prix_max_global 
    FROM chambres c 
    INNER JOIN hotels h ON c.hotel_id = h.id";

// Ajouter les filtres de ville et étoiles si nécessaires pour le range dynamique
$range_conditions = [];
$range_types = "";
$range_params = [];

if (!empty($ville)) {
    $range_conditions[] = "h.ville = ?";
    $range_types .= "s";
    $range_params[] = $ville;
}

if ($etoiles > 0) {
    $range_conditions[] = "h.etoiles = ?";
    $range_types .= "i";
    $range_params[] = $etoiles;
}

if (!empty($range_conditions)) {
    $prix_range_sql .= " WHERE " . implode(" AND ", $range_conditions);
}

// Exécution de la requête pour obtenir le range de prix
if (!empty($range_params)) {
    $prix_range_result = executeQuery($prix_range_sql, $range_types, $range_params);
} else {
    $prix_range_result = executeQuery($prix_range_sql);
}

$prix_range = $prix_range_result->fetch_assoc();
$prix_min_global = (int)$prix_range['prix_min_global'] ?? 0;
$prix_max_global = (int)$prix_range['prix_max_global'] ?? 1000;

// Arrondir les prix pour des valeurs plus pratiques
$prix_min_global = floor($prix_min_global / 50) * 50; // Arrondir vers le bas au multiple de 50
$prix_max_global = ceil($prix_max_global / 50) * 50;   // Arrondir vers le haut au multiple de 50

// Construction de la requête SQL avec sous-requête pour le prix minimum
$sql = "SELECT h.*, (SELECT MIN(c.prix_nuit) FROM chambres c WHERE c.hotel_id = h.id) as prix_min FROM hotels h WHERE 1=1";
$types = "";
$params = [];

if (!empty($ville)) {
    $sql .= " AND h.ville = ?";
    $types .= "s";
    $params[] = $ville;
}

if ($etoiles > 0) {
    $sql .= " AND h.etoiles = ?";
    $types .= "i";
    $params[] = $etoiles;
}

// Filtre de budget
if ($budget_min > 0 || $budget_max > 0) {
    $sql .= " HAVING 1=1";
    
    if ($budget_min > 0) {
        $sql .= " AND prix_min >= ?";
        $types .= "i";
        $params[] = $budget_min;
    }
    
    if ($budget_max > 0) {
        $sql .= " AND prix_min <= ?";
        $types .= "i";
        $params[] = $budget_max;
    }
}

$sql .= " ORDER BY h.etoiles DESC, h.nom ASC";

// Exécution de la requête
if (!empty($params)) {
    $result = executeQuery($sql, $types, $params);
} else {
    $result = executeQuery($sql);
}

// Récupération des résultats avec les images
$hotels = [];
while ($row = $result->fetch_assoc()) {
    // Récupération des images pour chaque hôtel
    $images_sql = "SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY ordre_affichage ASC";
    $images_result = executeQuery($images_sql, "i", [$row['id']]);
    
    $images = [];
    while ($img = $images_result->fetch_assoc()) {
        $images[] = $img;
    }
    
    $row['images'] = $images;
    $hotels[] = $row;
}

// Récupération des villes disponibles pour le filtre
$villes = [];
$result_villes = executeQuery("SELECT DISTINCT ville FROM hotels ORDER BY ville ASC");
while ($row = $result_villes->fetch_assoc()) {
    $villes[] = $row['ville'];
}

include 'includes/header.php';
?>

<!-- Bannière avec image de fond -->
<section class="py-5 position-relative" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=1920&h=600&fit=crop') center/cover; min-height: 400px;">
    <div class="container h-100 d-flex align-items-center">
        <div class="text-center text-white w-100">
            <h1 class="display-4 fw-bold mb-4">Nos Hôtels de Luxe</h1>
            <p class="lead fs-4">Découvrez notre collection d'hôtels d'exception</p>
            <div class="d-flex justify-content-center align-items-center mt-4">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star text-warning me-1 fs-4"></i>
                <?php endfor; ?>
                <span class="ms-3 fs-5">Excellence garantie</span>
            </div>
        </div>
    </div>
</section>

<!-- Filtres avec image de fond -->
<section class="py-4 position-relative" style="background: linear-gradient(rgba(248,249,250,0.95), rgba(255,255,255,0.95)), url('https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1920&h=400&fit=crop') center/cover;">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-4" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
            <div class="card-body p-4">
                <h5 class="card-title mb-3 text-primary fw-bold">
                    <i class="fas fa-filter me-2"></i>Filtrer les hôtels
                </h5>
                <div class="card-body">
                    <h5 class="card-title mb-3">Filtrer les hôtels</h5>
                    <form action="hotels.php" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="ville" class="form-label">Ville</label>
                            <select class="form-select" id="ville" name="ville">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $v) : ?>
                                    <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $ville === $v ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="etoiles" class="form-label">Catégorie</label>
                            <select class="form-select" id="etoiles" name="etoiles">
                                <option value="0">Toutes les catégories</option>
                                <?php for ($i = 3; $i <= 5; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php echo $etoiles === $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> étoiles
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3" data-prix-min="<?php echo $prix_min_global; ?>" data-prix-max="<?php echo $prix_max_global; ?>">
                            <label for="budget" class="form-label">Budget par nuit (MAD)</label>
                            <div class="mb-3">
                                <!-- Slider de budget -->
                                <div id="budget-slider" class="mb-3"></div>
                                
                                <!-- Affichage des valeurs -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-light text-dark">
                                        <span id="budget-min-display"><?php echo $budget_min > 0 ? $budget_min : $prix_min_global; ?></span> MAD
                                    </span>
                                    <span class="text-muted">à</span>
                                    <span class="badge bg-light text-dark">
                                        <span id="budget-max-display"><?php echo $budget_max > 0 ? $budget_max : $prix_max_global; ?></span> MAD
                                    </span>
                                </div>
                                
                                <!-- Champs cachés pour les valeurs -->
                                <input type="hidden" id="budget_min" name="budget_min" value="<?php echo $budget_min > 0 ? $budget_min : $prix_min_global; ?>">
                                <input type="hidden" id="budget_max" name="budget_max" value="<?php echo $budget_max > 0 ? $budget_max : $prix_max_global; ?>">
                            </div>
                            <small class="text-muted">Prix minimum des chambres (<?php echo $prix_min_global; ?> - <?php echo $prix_max_global; ?> MAD)</small>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-gold me-2">Filtrer</button>
                            <a href="hotels.php" class="btn btn-outline-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section d'affichage des hôtels avec galerie -->
<div class="row">
    <?php foreach ($hotels as $hotel) : ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card hotel-card h-100 shadow-sm">
                <!-- Carousel pour les images -->
                <?php if (!empty($hotel['images'])) : ?>
                    <div id="carousel<?php echo $hotel['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($hotel['images'] as $index => $image) : ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="assets/images/hotels/<?php echo htmlspecialchars($image['nom_image']); ?>" 
                                         class="d-block w-100 hotel-image" 
                                         alt="<?php echo htmlspecialchars($image['description']); ?>"
                                         style="height: 250px; object-fit: cover;"
                                         onerror="this.src='assets/images/hotel_default.jpg'">
                                    <div class="carousel-caption d-none d-md-block">
                                        <p class="mb-0"><?php echo htmlspecialchars($image['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Contrôles du carousel -->
                        <?php if (count($hotel['images']) > 1) : ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?php echo $hotel['id']; ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Précédent</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carousel<?php echo $hotel['id']; ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Suivant</span>
                            </button>
                            
                            <!-- Indicateurs -->
                            <div class="carousel-indicators">
                                <?php foreach ($hotel['images'] as $index => $image) : ?>
                                    <button type="button" data-bs-target="#carousel<?php echo $hotel['id']; ?>" 
                                            data-bs-slide-to="<?php echo $index; ?>" 
                                            <?php echo $index === 0 ? 'class="active" aria-current="true"' : ''; ?>
                                            aria-label="Image <?php echo $index + 1; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <!-- Image par défaut si aucune image -->
                    <img src="assets/images/hotels/<?php echo htmlspecialchars($hotel['image_principale']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($hotel['nom']); ?>"
                         style="height: 250px; object-fit: cover;"
                         onerror="this.src='assets/images/hotel_default.jpg'">
                <?php endif; ?>
                
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
                    
                    <!-- Badges pour les types d'images disponibles -->
                    <?php if (!empty($hotel['images'])) : ?>
                        <div class="mb-3">
                            <?php 
                            $types_disponibles = array_unique(array_column($hotel['images'], 'type_image'));
                            foreach ($types_disponibles as $type) :
                                $badge_class = match($type) {
                                    'restaurant' => 'bg-success',
                                    'spa' => 'bg-info',
                                    'piscine' => 'bg-primary',
                                    'exterieur' => 'bg-warning',
                                    'interieur' => 'bg-secondary',
                                    default => 'bg-dark'
                                };
                            ?>
                                <span class="badge <?php echo $badge_class; ?> me-1"><?php echo ucfirst($type); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="text-gold fw-bold mb-0">À partir de 300 MAD</p>
                        <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline-gold">Voir l'hôtel</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include 'includes/footer.php'; ?>