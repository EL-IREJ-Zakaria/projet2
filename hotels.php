<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Récupération des filtres
$ville = isset($_GET['ville']) ? cleanInput($_GET['ville']) : '';
$etoiles = isset($_GET['etoiles']) ? (int)$_GET['etoiles'] : 0;

// Construction de la requête SQL
$sql = "SELECT * FROM hotels WHERE 1=1";
$types = "";
$params = [];

if (!empty($ville)) {
    $sql .= " AND ville = ?";
    $types .= "s";
    $params[] = $ville;
}

if ($etoiles > 0) {
    $sql .= " AND etoiles = ?";
    $types .= "i";
    $params[] = $etoiles;
}

$sql .= " ORDER BY etoiles DESC, nom ASC";

// Exécution de la requête
if (!empty($params)) {
    $result = executeQuery($sql, $types, $params);
} else {
    $result = executeQuery($sql);
}

// Récupération des résultats
$hotels = [];
while ($row = $result->fetch_assoc()) {
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

<!-- Bannière -->
<section class="py-5 bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Nos Hôtels de Luxe</h1>
    </div>
</section>

<!-- Filtres -->
<section class="py-4">
    <div class="container">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Filtrer les hôtels</h5>
                <form action="hotels.php" method="GET" class="row g-3">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-gold me-2">Filtrer</button>
                        <a href="hotels.php" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Liste des hôtels -->
<section class="py-4">
    <div class="container">
        <?php if (empty($hotels)) : ?>
            <div class="alert alert-info">
                Aucun hôtel ne correspond à vos critères de recherche.
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
                                    <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline-gold">Voir les détails</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>