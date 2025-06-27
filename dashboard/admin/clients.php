<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Traitement de la recherche
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Construction de la requête avec recherche
$sql = "SELECT c.*, COUNT(r.id) as nb_reservations, SUM(r.montant_total) as montant_total 
        FROM clients c 
        LEFT JOIN reservations r ON c.id = r.client_id";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " WHERE c.nom LIKE ? OR c.prenom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

$sql .= " GROUP BY c.id ORDER BY c.nom ASC, c.prenom ASC";

// Récupération des clients
$clients = fetchAll($sql, $types, $params);

// Titre de la page
$page_title = "Gestion des clients";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gestion des clients</h1>
            </div>
            
            <!-- Recherche -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher par nom, prénom, email ou téléphone..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex">
                            <button type="submit" class="btn btn-outline-secondary w-100">Rechercher</button>
                        </div>
                        <?php if (!empty($search)) : ?>
                            <div class="col-12">
                                <a href="clients.php" class="btn btn-outline-danger btn-sm">Réinitialiser la recherche</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($clients)) : ?>
                        <p class="text-center text-muted my-4">Aucun client trouvé.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Inscrit le</th>
                                        <th>Réservations</th>
                                        <th>Montant total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client) : ?>
                                        <tr>
                                            <td><?php echo $client['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></div>
                                            </td>
                                            <td><a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a></td>
                                            <td><a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>"><?php echo htmlspecialchars($client['telephone']); ?></a></td>
                                            <td><?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></td>
                                            <td><?php echo $client['nb_reservations']; ?></td>
                                            <td>
                                                <?php if ($client['montant_total']) : ?>
                                                    <?php echo number_format($client['montant_total'], 2, ',', ' '); ?> MAD
                                                <?php else : ?>
                                                    0,00 MAD
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="voir_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="reservations.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-list"></i>
                                                    </a>
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