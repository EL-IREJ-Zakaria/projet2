<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Récupérer la liste des clients
$clients = fetchAll("SELECT id, nom, prenom, email FROM clients ORDER BY nom ASC, prenom ASC");

// Récupérer la liste des hôtels
$hotels = fetchAll("SELECT id, nom, ville FROM hotels ORDER BY nom ASC");

// Initialiser les variables
$selected_hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$chambres = [];

// Si un hôtel est sélectionné, récupérer ses chambres disponibles
if ($selected_hotel_id > 0) {
    $chambres = fetchAll("SELECT id, numero, type, capacite, prix_nuit, disponible 
                        FROM chambres 
                        WHERE hotel_id = ? AND disponible = 1 
                        ORDER BY numero ASC", "i", [$selected_hotel_id]);
}

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer et nettoyer les données
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $chambre_id = isset($_POST['chambre_id']) ? intval($_POST['chambre_id']) : 0;
        $date_arrivee = isset($_POST['date_arrivee']) ? cleanInput($_POST['date_arrivee']) : '';
        $date_depart = isset($_POST['date_depart']) ? cleanInput($_POST['date_depart']) : '';
        $commentaires = isset($_POST['commentaires']) ? cleanInput($_POST['commentaires']) : '';
        
        // Validation
        if ($client_id <= 0) {
            $errors[] = "Veuillez sélectionner un client.";
        }
        
        if ($chambre_id <= 0) {
            $errors[] = "Veuillez sélectionner une chambre.";
        }
        
        if (empty($date_arrivee)) {
            $errors[] = "Veuillez sélectionner une date d'arrivée.";
        }
        
        if (empty($date_depart)) {
            $errors[] = "Veuillez sélectionner une date de départ.";
        }
        
        // Vérifier que la date d'arrivée est antérieure à la date de départ
        if (!empty($date_arrivee) && !empty($date_depart) && strtotime($date_arrivee) >= strtotime($date_depart)) {
            $errors[] = "La date d'arrivée doit être antérieure à la date de départ.";
        }
        
        // Vérifier que la date d'arrivée n'est pas dans le passé
        if (!empty($date_arrivee) && strtotime($date_arrivee) < strtotime(date('Y-m-d'))) {
            $errors[] = "La date d'arrivée ne peut pas être dans le passé.";
        }
        
        // Vérifier la disponibilité de la chambre pour les dates sélectionnées
        if ($chambre_id > 0 && !empty($date_arrivee) && !empty($date_depart)) {
            $chambre_disponible = fetchOne("SELECT COUNT(*) as count 
                                        FROM reservations 
                                        WHERE chambre_id = ? 
                                        AND statut IN ('en_attente', 'confirmee') 
                                        AND (
                                            (date_arrivee <= ? AND date_depart > ?) OR
                                            (date_arrivee < ? AND date_depart >= ?) OR
                                            (date_arrivee >= ? AND date_depart <= ?)
                                        )", 
                                        "issssss", 
                                        [$chambre_id, $date_depart, $date_arrivee, $date_depart, $date_arrivee, $date_arrivee, $date_depart]);
            
            if ($chambre_disponible['count'] > 0) {
                $errors[] = "La chambre n'est pas disponible pour les dates sélectionnées.";
            }
        }
        
        // Si pas d'erreurs, calculer le montant et insérer la réservation
        if (empty($errors)) {
            // Récupérer les informations de la chambre
            $chambre = fetchOne("SELECT prix_nuit FROM chambres WHERE id = ?", "i", [$chambre_id]);
            
            // Calculer le nombre de nuits
            $date_arrivee_obj = new DateTime($date_arrivee);
            $date_depart_obj = new DateTime($date_depart);
            $interval = $date_arrivee_obj->diff($date_depart_obj);
            $nb_nuits = $interval->days;
            
            // Calculer le montant total (prix par nuit * nombre de nuits + taxe de séjour)
            $taxe_sejour = 2.50; // Taxe de séjour fixe par nuit
            $montant_total = ($chambre['prix_nuit'] * $nb_nuits) + ($taxe_sejour * $nb_nuits);
            
            // Insérer la réservation
            $result = executeQuery("INSERT INTO reservations 
                                (client_id, chambre_id, date_reservation, date_arrivee, date_depart, 
                                montant_total, taxe_sejour, statut, commentaires) 
                                VALUES (?, ?, NOW(), ?, ?, ?, ?, 'confirmee', ?)", 
                                "iisssdss", 
                                [$client_id, $chambre_id, $date_arrivee, $date_depart, $montant_total, $taxe_sejour, $commentaires]);
            
            if ($result) {
                $reservation_id = mysqli_insert_id($conn);
                $success = true;
                
                // Rediriger vers la page de détails de la réservation
                header("Location: voir_reservation.php?id=$reservation_id&success=1");
                exit;
            } else {
                $errors[] = "Une erreur est survenue lors de la création de la réservation.";
            }
        }
    }
}

// Générer un nouveau token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Titre de la page
$page_title = "Ajouter une réservation";
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Ajouter une réservation</h1>
                <a href="reservations.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
            </div>
            
            <?php if ($success) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    La réservation a été créée avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Informations client</h5>
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client</label>
                                    <select class="form-select" id="client_id" name="client_id" required>
                                        <option value="">Sélectionner un client</option>
                                        <?php foreach ($clients as $client) : ?>
                                            <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom'] . ' (' . $client['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Sélection de la chambre</h5>
                                <div class="mb-3">
                                    <label for="hotel_id" class="form-label">Hôtel</label>
                                    <select class="form-select" id="hotel_id" name="hotel_id" required onchange="this.form.submit()">
                                        <option value="">Sélectionner un hôtel</option>
                                        <?php foreach ($hotels as $hotel) : ?>
                                            <option value="<?php echo $hotel['id']; ?>" <?php echo ($selected_hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($hotel['nom'] . ' (' . $hotel['ville'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if ($selected_hotel_id > 0) : ?>
                                    <div class="mb-3">
                                        <label for="chambre_id" class="form-label">Chambre</label>
                                        <select class="form-select" id="chambre_id" name="chambre_id" required>
                                            <option value="">Sélectionner une chambre</option>
                                            <?php foreach ($chambres as $chambre) : ?>
                                                <option value="<?php echo $chambre['id']; ?>" <?php echo (isset($_POST['chambre_id']) && $_POST['chambre_id'] == $chambre['id']) ? 'selected' : ''; ?> data-prix="<?php echo $chambre['prix_nuit']; ?>">
                                                    Chambre <?php echo htmlspecialchars($chambre['numero'] . ' - ' . $chambre['type'] . ' - ' . $chambre['capacite'] . ' pers. - ' . number_format($chambre['prix_nuit'], 2, ',', ' ') . ' MAD/nuit'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (empty($chambres)) : ?>
                                            <div class="form-text text-danger">Aucune chambre disponible pour cet hôtel.</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Dates de séjour</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_arrivee" class="form-label">Date d'arrivée</label>
                                        <input type="date" class="form-control" id="date_arrivee" name="date_arrivee" value="<?php echo isset($_POST['date_arrivee']) ? $_POST['date_arrivee'] : date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_depart" class="form-label">Date de départ</label>
                                        <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?php echo isset($_POST['date_depart']) ? $_POST['date_depart'] : date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="mb-3">Commentaires</h5>
                            <div class="mb-3">
                                <label for="commentaires" class="form-label">Commentaires ou demandes spéciales</label>
                                <textarea class="form-control" id="commentaires" name="commentaires" rows="3"><?php echo isset($_POST['commentaires']) ? htmlspecialchars($_POST['commentaires']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="reservations.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" name="submit" class="btn btn-gold">Créer la réservation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calcul dynamique du prix total
document.addEventListener('DOMContentLoaded', function() {
    const chambreSelect = document.getElementById('chambre_id');
    const dateArrivee = document.getElementById('date_arrivee');
    const dateDepart = document.getElementById('date_depart');
    
    function calculerPrixTotal() {
        if (chambreSelect.value && dateArrivee.value && dateDepart.value) {
            const chambreOption = chambreSelect.options[chambreSelect.selectedIndex];
            const prixNuit = parseFloat(chambreOption.dataset.prix);
            
            const arrivee = new Date(dateArrivee.value);
            const depart = new Date(dateDepart.value);
            
            // Calculer la différence en jours
            const diffTime = Math.abs(depart - arrivee);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays > 0 && !isNaN(prixNuit)) {
                const taxeSejour = 2.50; // Taxe de séjour fixe par nuit
                const prixTotal = (prixNuit * diffDays) + (taxeSejour * diffDays);
                
                // Afficher le prix total (à implémenter si nécessaire)
                console.log(`Prix total: ${prixTotal.toFixed(2)} MAD`);
            }
        }
    }
    
    // Ajouter les écouteurs d'événements
    if (chambreSelect) chambreSelect.addEventListener('change', calculerPrixTotal);
    if (dateArrivee) dateArrivee.addEventListener('change', calculerPrixTotal);
    if (dateDepart) dateDepart.addEventListener('change', calculerPrixTotal);
});
</script>

<?php include '../../includes/footer.php'; ?>