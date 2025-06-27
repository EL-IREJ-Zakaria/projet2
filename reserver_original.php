<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Stocker l'URL actuelle pour rediriger après connexion
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Récupération des paramètres
$chambre_id = isset($_GET['chambre_id']) ? (int)$_GET['chambre_id'] : 0;
$date_arrivee = isset($_GET['date_arrivee']) ? cleanInput($_GET['date_arrivee']) : '';
$date_depart = isset($_GET['date_depart']) ? cleanInput($_GET['date_depart']) : '';

// Vérification des paramètres (SANS transaction_id)
if ($chambre_id <= 0 || empty($date_arrivee) || empty($date_depart)) {
    header('Location: index.php');
    exit;
}

// Vérification de la disponibilité de la chambre
$sql = "SELECT c.*, h.nom as hotel_nom, h.ville as hotel_ville 
       FROM chambres c 
       JOIN hotels h ON c.hotel_id = h.id
       WHERE c.id = ? 
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
       )";

$params = [$chambre_id, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
$chambre = fetchOne($sql, "issssss", $params);

if (!$chambre) {
    // La chambre n'est pas disponible pour ces dates
    header('Location: index.php?error=indisponible');
    exit;
}

// Calcul du nombre de nuits et du prix total
$date1 = new DateTime($date_arrivee);
$date2 = new DateTime($date_depart);
$nb_nuits = $date2->diff($date1)->days;
$prix_total = $chambre['prix_nuit'] * $nb_nuits;

// Récupération des informations de l'utilisateur
$client = fetchOne("SELECT * FROM clients WHERE id = ?", "i", [$_SESSION['user_id']]);

$errors = [];
$success = false;

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $nom = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $telephone = cleanInput($_POST['telephone'] ?? '');
        $commentaire = cleanInput($_POST['commentaire'] ?? '');
        
        // Validation des données
        if (empty($nom)) {
            $errors[] = "Le nom est requis.";
        }
        
        if (empty($prenom)) {
            $errors[] = "Le prénom est requis.";
        }
        
        if (empty($email)) {
            $errors[] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide.";
        }
        
        if (empty($telephone)) {
            $errors[] = "Le téléphone est requis.";
        }
        
        // Si pas d'erreurs, enregistrer la réservation
        if (empty($errors)) {
            // Mise à jour des informations du client si nécessaire
            if ($client['telephone'] !== $telephone) {
                executeQuery("UPDATE clients SET telephone = ? WHERE id = ?", "si", [$telephone, $_SESSION['user_id']]);
            }
            
            // Insertion de la réservation (SANS transaction_id)
            $sql = "INSERT INTO reservations (client_id, chambre_id, date_arrivee, date_depart, prix_total, statut, commentaire) 
                   VALUES (?, ?, ?, ?, ?, 'confirmée', ?)";
            $params = [$_SESSION['user_id'], $chambre_id, $date_arrivee, $date_depart, $prix_total, $commentaire];
            $reservation_id = insertAndGetId($sql, "iissds", $params);
            
            if ($reservation_id) {
                $success = true;
            } else {
                $errors[] = "Erreur lors de l'enregistrement de la réservation.";
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Bannière de réservation -->
<section class="py-5 bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Réservation</h1>
    </div>
</section>

<!-- Formulaire de réservation -->
<section class="py-5">
    <div class="container">
        <?php if ($success) : ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                            <h2 class="mb-3">Réservation confirmée !</h2>
                            <p class="lead mb-4">Votre réservation a été enregistrée avec succès. Un email de confirmation vous a été envoyé.</p>
                            <div class="d-flex justify-content-center">
                                <a href="dashboard/client/mes_reservations.php" class="btn btn-gold me-3">Voir mes réservations</a>
                                <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php if (!empty($errors)) : ?>
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0">Détails de la réservation</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Hôtel</h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($chambre['hotel_nom']); ?></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($chambre['hotel_ville']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Chambre</h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($chambre['type']); ?> (N°<?php echo htmlspecialchars($chambre['numero']); ?>)</p>
                                    <p class="text-muted">Capacité: <?php echo $chambre['capacite']; ?> personnes</p>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Dates</h5>
                                    <p class="mb-1">Arrivée: <?php echo date('d/m/Y', strtotime($date_arrivee)); ?></p>
                                    <p>Départ: <?php echo date('d/m/Y', strtotime($date_depart)); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Tarif</h5>
                                    <p class="mb-1"><?php echo number_format($chambre['prix_nuit'], 2, ',', ' '); ?> MAD x <?php echo $nb_nuits; ?> nuits</p>
                                    <p class="text-gold fw-bold">Total: <?php echo number_format($prix_total, 2, ',', ' '); ?> MAD</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0">Informations personnelles</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($client['nom']); ?>" required>
                                        <div class="invalid-feedback">Veuillez entrer votre nom.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($client['prenom']); ?>" required>
                                        <div class="invalid-feedback">Veuillez entrer votre prénom.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                        <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Veuillez entrer votre numéro de téléphone.</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="commentaire" class="form-label">Demandes spéciales (optionnel)</label>
                                    <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="conditions" required>
                                    <label class="form-check-label" for="conditions">J'accepte les conditions générales de vente</label>
                                    <div class="invalid-feedback">Vous devez accepter les conditions générales pour continuer.</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-gold btn-lg">Confirmer la réservation</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h4 class="card-title mb-0">Résumé</h4>
                        </div>
                        <div class="card-body">
                            <p class="fw-bold mb-1"><?php echo htmlspecialchars($chambre['hotel_nom']); ?></p>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($chambre['hotel_ville']); ?></p>
                            
                            <p class="mb-1"><i class="fas fa-bed me-2 text-gold"></i> <?php echo htmlspecialchars($chambre['type']); ?></p>
                            <p class="mb-3"><i class="fas fa-calendar me-2 text-gold"></i> <?php echo date('d/m/Y', strtotime($date_arrivee)); ?> - <?php echo date('d/m/Y', strtotime($date_depart)); ?> (<?php echo $nb_nuits; ?> nuits)</p>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Prix par nuit</span>
                                <span><?php echo number_format($chambre['prix_nuit'], 2, ',', ' '); ?> MAD</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Nombre de nuits</span>
                                <span><?php echo $nb_nuits; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Taxes de séjour</span>
                                <span>Incluses</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span class="text-gold"><?php echo number_format($prix_total, 2, ',', ' '); ?> MAD</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Politique d'annulation</h5>
                            <p class="card-text">Annulation gratuite jusqu'à 48 heures avant l'arrivée. Après cette période, le montant total de la réservation sera facturé.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>