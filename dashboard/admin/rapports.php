<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Récupérer la période sélectionnée
$periode = isset($_GET['periode']) ? cleanInput($_GET['periode']) : 'mois';
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

// Définir les dates de début et de fin en fonction de la période
$date_debut = '';
$date_fin = date('Y-m-d');

switch ($periode) {
    case 'semaine':
        $date_debut = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mois':
        $date_debut = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'trimestre':
        $date_debut = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'annee':
        $date_debut = date('Y-m-d', strtotime('-365 days'));
        break;
    case 'personnalise':
        $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : date('Y-m-d', strtotime('-30 days'));
        $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : date('Y-m-d');
        break;
}

// Récupérer les hôtels pour le filtre
$hotels = fetchAll("SELECT id, nom, ville FROM hotels ORDER BY nom ASC");

// Statistiques générales
$sql_stats = "SELECT 
                COUNT(DISTINCT r.id) as nb_reservations,
                SUM(r.montant_total) as chiffre_affaires,
                COUNT(DISTINCT r.client_id) as nb_clients,
                AVG(DATEDIFF(r.date_depart, r.date_arrivee)) as duree_moyenne_sejour
            FROM reservations r
            JOIN chambres ch ON r.chambre_id = ch.id";

$params = [];
$types = "";

// Ajouter les conditions de filtre
if (!empty($date_debut) && !empty($date_fin)) {
    $sql_stats .= " WHERE r.date_reservation BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types .= "ss";
    
    if ($hotel_id > 0) {
        $sql_stats .= " AND ch.hotel_id = ?";
        $params[] = $hotel_id;
        $types .= "i";
    }
}

$stats = fetchOne($sql_stats, $types, $params);

// Répartition des réservations par statut
$sql_statuts = "SELECT 
                r.statut,
                COUNT(r.id) as nb_reservations,
                SUM(r.montant_total) as montant_total
            FROM reservations r
            JOIN chambres ch ON r.chambre_id = ch.id
            WHERE r.date_reservation BETWEEN ? AND ?";

$params_statuts = [$date_debut, $date_fin];
$types_statuts = "ss";

if ($hotel_id > 0) {
    $sql_statuts .= " AND ch.hotel_id = ?";
    $params_statuts[] = $hotel_id;
    $types_statuts .= "i";
}

$sql_statuts .= " GROUP BY r.statut";

$statuts = fetchAll($sql_statuts, $types_statuts, $params_statuts);

// Répartition des réservations par hôtel
$sql_hotels = "SELECT 
                h.id,
                h.nom,
                h.ville,
                COUNT(r.id) as nb_reservations,
                SUM(r.montant_total) as chiffre_affaires
            FROM reservations r
            JOIN chambres ch ON r.chambre_id = ch.id
            JOIN hotels h ON ch.hotel_id = h.id
            WHERE r.date_reservation BETWEEN ? AND ?
            GROUP BY h.id
            ORDER BY nb_reservations DESC";

$hotels_stats = fetchAll($sql_hotels, "ss", [$date_debut, $date_fin]);

// Répartition des réservations par mois
$sql_mois = "SELECT 
                DATE_FORMAT(r.date_reservation, '%Y-%m') as mois,
                COUNT(r.id) as nb_reservations,
                SUM(r.montant_total) as chiffre_affaires
            FROM reservations r
            JOIN chambres ch ON r.chambre_id = ch.id
            WHERE r.date_reservation BETWEEN ? AND ?";

$params_mois = [$date_debut, $date_fin];
$types_mois = "ss";

if ($hotel_id > 0) {
    $sql_mois .= " AND ch.hotel_id = ?";
    $params_mois[] = $hotel_id;
    $types_mois .= "i";
}

$sql_mois .= " GROUP BY mois
            ORDER BY mois ASC";

$mois_stats = fetchAll($sql_mois, $types_mois, $params_mois);

// Top 5 des clients
$sql_clients = "SELECT 
                c.id,
                c.nom,
                c.prenom,
                c.email,
                COUNT(r.id) as nb_reservations,
                SUM(r.montant_total) as montant_total
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            JOIN chambres ch ON r.chambre_id = ch.id
            WHERE r.date_reservation BETWEEN ? AND ?";

$params_clients = [$date_debut, $date_fin];
$types_clients = "ss";

if ($hotel_id > 0) {
    $sql_clients .= " AND ch.hotel_id = ?";
    $params_clients[] = $hotel_id;
    $types_clients .= "i";
}

$sql_clients .= " GROUP BY c.id
                ORDER BY montant_total DESC
                LIMIT 5";

$top_clients = fetchAll($sql_clients, $types_clients, $params_clients);

// Titre de la page
$page_title = "Rapports et statistiques";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Rapports et statistiques</h1>
                <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimer</button>
            </div>
            
            <!-- Filtres -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode" onchange="toggleDateInputs()">
                                <option value="semaine" <?php echo $periode === 'semaine' ? 'selected' : ''; ?>>7 derniers jours</option>
                                <option value="mois" <?php echo $periode === 'mois' ? 'selected' : ''; ?>>30 derniers jours</option>
                                <option value="trimestre" <?php echo $periode === 'trimestre' ? 'selected' : ''; ?>>90 derniers jours</option>
                                <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>365 derniers jours</option>
                                <option value="personnalise" <?php echo $periode === 'personnalise' ? 'selected' : ''; ?>>Personnalisée</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_id" class="form-label">Hôtel</label>
                            <select class="form-select" id="hotel_id" name="hotel_id">
                                <option value="0">Tous les hôtels</option>
                                <?php foreach ($hotels as $hotel) : ?>
                                    <option value="<?php echo $hotel['id']; ?>" <?php echo ($hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 date-input" style="display: <?php echo $periode === 'personnalise' ? 'block' : 'none'; ?>">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-2 date-input" style="display: <?php echo $periode === 'personnalise' ? 'block' : 'none'; ?>">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Statistiques générales -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Statistiques générales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Réservations</h6>
                                    <h2 class="mb-0"><?php echo $stats['nb_reservations'] ?? 0; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Chiffre d'affaires</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['chiffre_affaires'] ?? 0, 2, ',', ' '); ?> €</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Clients uniques</h6>
                                    <h2 class="mb-0"><?php echo $stats['nb_clients'] ?? 0; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Durée moyenne</h6>
                                    <h2 class="mb-0"><?php echo round($stats['duree_moyenne_sejour'] ?? 0, 1); ?> nuits</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Répartition par statut -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Répartition par statut</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Statut</th>
                                            <th class="text-end">Réservations</th>
                                            <th class="text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($statuts as $statut) : ?>
                                            <tr>
                                                <td>
                                                    <?php if ($statut['statut'] === 'en_attente') : ?>
                                                        <span class="badge bg-warning">En attente</span>
                                                    <?php elseif ($statut['statut'] === 'confirmee') : ?>
                                                        <span class="badge bg-success">Confirmée</span>
                                                    <?php elseif ($statut['statut'] === 'annulee') : ?>
                                                        <span class="badge bg-danger">Annulée</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end"><?php echo $statut['nb_reservations']; ?></td>
                                                <td class="text-end"><?php echo number_format($statut['montant_total'], 2, ',', ' '); ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top 5 clients -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Top 5 des clients</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_clients)) : ?>
                                <p class="text-center text-muted my-4">Aucune donnée disponible.</p>
                            <?php else : ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th class="text-end">Réservations</th>
                                                <th class="text-end">Montant total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_clients as $client) : ?>
                                                <tr>
                                                    <td>
                                                        <a href="voir_client.php?id=<?php echo $client['id']; ?>">
                                                            <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end"><?php echo $client['nb_reservations']; ?></td>
                                                    <td class="text-end"><?php echo number_format($client['montant_total'], 2, ',', ' '); ?> €</td>
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
            
            <div class="row">
                <!-- Répartition par hôtel -->
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Répartition par hôtel</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($hotels_stats)) : ?>
                                <p class="text-center text-muted my-4">Aucune donnée disponible.</p>
                            <?php else : ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Hôtel</th>
                                                <th class="text-end">Réservations</th>
                                                <th class="text-end">Chiffre d'affaires</th>
                                                <th class="text-end">% du CA total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hotels_stats as $hotel) : ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?></td>
                                                    <td class="text-end"><?php echo $hotel['nb_reservations']; ?></td>
                                                    <td class="text-end"><?php echo number_format($hotel['chiffre_affaires'], 2, ',', ' '); ?> €</td>
                                                    <td class="text-end">
                                                        <?php 
                                                        $pourcentage = ($stats['chiffre_affaires'] > 0) ? ($hotel['chiffre_affaires'] / $stats['chiffre_affaires']) * 100 : 0;
                                                        echo number_format($pourcentage, 1, ',', ' ') . '%';
                                                        ?>
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
            
            <!-- Évolution mensuelle -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Évolution mensuelle</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mois_stats)) : ?>
                        <p class="text-center text-muted my-4">Aucune donnée disponible.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mois</th>
                                        <th class="text-end">Réservations</th>
                                        <th class="text-end">Chiffre d'affaires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mois_stats as $mois) : ?>
                                        <tr>
                                            <td><?php echo date('F Y', strtotime($mois['mois'] . '-01')); ?></td>
                                            <td class="text-end"><?php echo $mois['nb_reservations']; ?></td>
                                            <td class="text-end"><?php echo number_format($mois['chiffre_affaires'], 2, ',', ' '); ?> €</td>
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

<script>
function toggleDateInputs() {
    const periode = document.getElementById('periode').value;
    const dateInputs = document.querySelectorAll('.date-input');
    
    if (periode === 'personnalise') {
        dateInputs.forEach(input => input.style.display = 'block');
    } else {
        dateInputs.forEach(input => input.style.display = 'none');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>