<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Récupération des paramètres
$chambre_id = isset($_GET['chambre_id']) ? (int)$_GET['chambre_id'] : 0;
$date_arrivee = isset($_GET['date_arrivee']) ? cleanInput($_GET['date_arrivee']) : '';
$date_depart = isset($_GET['date_depart']) ? cleanInput($_GET['date_depart']) : '';

// Vérification des paramètres
if ($chambre_id <= 0 || empty($date_arrivee) || empty($date_depart)) {
    header('Location: index.php');
    exit;
}

// Fonction pour trouver une chambre disponible du même type
function trouverChambreDisponible($hotel_id, $type_chambre, $date_arrivee, $date_depart, $capacite) {
    // Rechercher toutes les chambres du même type et capacité dans l'hôtel
    $sql = "SELECT c.* FROM chambres c 
           WHERE c.hotel_id = ? 
           AND c.type = ? 
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
           ORDER BY c.numero ASC
           LIMIT 1";
    
    $params = [$hotel_id, $type_chambre, $capacite, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
    return fetchOne($sql, "ississsss", $params);
}

// Récupération des informations de la chambre initialement sélectionnée
$sql = "SELECT c.*, h.nom as hotel_nom, h.ville as hotel_ville, h.id as hotel_id
       FROM chambres c 
       JOIN hotels h ON c.hotel_id = h.id
       WHERE c.id = ?";

$chambre_originale = fetchOne($sql, "i", [$chambre_id]);

if (!$chambre_originale) {
    header('Location: index.php?error=chambre_introuvable');
    exit;
}

// Rechercher une chambre disponible du même type
$chambre_disponible = trouverChambreDisponible(
    $chambre_originale['hotel_id'], 
    $chambre_originale['type'], 
    $date_arrivee, 
    $date_depart, 
    $chambre_originale['capacite']
);

if (!$chambre_disponible) {
    // Aucune chambre de ce type n'est disponible
    header('Location: index.php?error=aucune_chambre_disponible&type=' . urlencode($chambre_originale['type']));
    exit;
}

// Utiliser la chambre disponible trouvée
$chambre = array_merge($chambre_originale, $chambre_disponible);
$chambre_id_finale = $chambre_disponible['id'];

// Message informatif si la chambre a changé
$chambre_changee = ($chambre_id != $chambre_id_finale);
$message_chambre = '';
if ($chambre_changee) {
    $message_chambre = "Nous vous avons attribué la chambre n°{$chambre_disponible['numero']} du même type ({$chambre_disponible['type']}) car elle était disponible pour vos dates.";
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

// Générer le token CSRF
$csrf_token = generateCSRFToken();

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Vérifier à nouveau la disponibilité avant l'insertion
        $chambre_finale_check = trouverChambreDisponible(
            $chambre['hotel_id'], 
            $chambre['type'], 
            $date_arrivee, 
            $date_depart, 
            $chambre['capacite']
        );
        
        if (!$chambre_finale_check) {
            $errors[] = "Désolé, cette chambre n'est plus disponible. Veuillez recommencer votre recherche.";
        } else {
            // Mettre à jour l'ID de la chambre si elle a encore changé
            $chambre_id_finale = $chambre_finale_check['id'];
            
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
                
                // Insertion de la réservation avec la chambre finale
                $sql = "INSERT INTO reservations (client_id, chambre_id, date_arrivee, date_depart, prix_total, statut, commentaire) 
                       VALUES (?, ?, ?, ?, ?, 'confirmée', ?)";
                $params = [$_SESSION['user_id'], $chambre_id_finale, $date_arrivee, $date_depart, $prix_total, $commentaire];
                $reservation_id = insertAndGetId($sql, "iissds", $params);
                
                if ($reservation_id) {
                    $success = true;
                    // Stocker les informations de la réservation pour l'affichage
                    $_SESSION['derniere_reservation'] = [
                        'id' => $reservation_id,
                        'chambre_numero' => $chambre_finale_check['numero'],
                        'chambre_type' => $chambre_finale_check['type'],
                        'hotel_nom' => $chambre['hotel_nom'],
                        'changement' => ($chambre_id != $chambre_id_finale)
                    ];
                } else {
                    $errors[] = "Erreur lors de l'enregistrement de la réservation.";
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Bannière de réservation -->
<section class="py-5 bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Confirmation de réservation</h1>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="progress mb-4">
                    <div class="progress-bar bg-gold" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-center">
                    <div class="step completed">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mt-2">Sélection</p>
                    </div>
                    <div class="step completed">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mt-2">Paiement</p>
                    </div>
                    <div class="step active">
                        <i class="fas fa-calendar-check text-gold"></i>
                        <p class="mt-2">Confirmation</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Hero avec image de fond -->
<section class="hero-section position-relative" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&h=600&fit=crop') center/cover; min-height: 400px;">
    <div class="container h-100 d-flex align-items-center">
        <div class="row w-100">
            <div class="col-lg-8">
                <div class="text-white">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($chambre['hotel_nom']); ?></h1>
                    <div class="d-flex align-items-center mb-3">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-warning me-1"></i>
                        <?php endfor; ?>
                        <span class="ms-2 fs-5">Hôtel 5 étoiles</span>
                    </div>
                    <p class="fs-5 mb-4">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($chambre['hotel_ville']); ?>, Maroc
                    </p>
                    <div class="bg-white bg-opacity-90 rounded p-3 d-inline-block">
                        <h4 class="text-dark mb-0">Réservation en cours...</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Détails avec image de fond -->
<section class="py-5 position-relative" style="background: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95)), url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1920&h=800&fit=crop') center/cover;">
    <div class="container">
        <?php if ($success) : ?>
            <!-- Section de confirmation avec informations de la chambre attribuée -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,249,250,0.95)); backdrop-filter: blur(10px);">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                            <h2 class="mb-3">Réservation confirmée !</h2>
                            <p class="lead mb-4">Votre réservation a été enregistrée avec succès.</p>
                            
                            <?php if (isset($_SESSION['derniere_reservation']) && $_SESSION['derniere_reservation']['changement']) : ?>
                                <div class="alert alert-info mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Information :</strong> Nous vous avons attribué la chambre n°<?php echo htmlspecialchars($_SESSION['derniere_reservation']['chambre_numero']); ?> 
                                    (<?php echo htmlspecialchars($_SESSION['derniere_reservation']['chambre_type']); ?>) 
                                    qui était disponible pour vos dates.
                                </div>
                            <?php endif; ?>
                            
                            <div class="bg-light rounded p-3 mb-4">
                                <h5 class="mb-2">Détails de votre réservation :</h5>
                                <p class="mb-1"><strong>Hôtel :</strong> <?php echo htmlspecialchars($chambre['hotel_nom']); ?></p>
                                <p class="mb-1"><strong>Chambre :</strong> N°<?php echo htmlspecialchars($chambre_disponible['numero']); ?> - <?php echo htmlspecialchars($chambre_disponible['type']); ?></p>
                                <p class="mb-1"><strong>Dates :</strong> <?php echo date('d/m/Y', strtotime($date_arrivee)); ?> au <?php echo date('d/m/Y', strtotime($date_depart)); ?></p>
                                <p class="mb-0"><strong>Prix total :</strong> <?php echo number_format($prix_total, 2, ',', ' '); ?> MAD</p>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-2">
                                <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
                                <a href="telecharger_confirmation.php?reservation_id=<?php echo isset($_SESSION['derniere_reservation']) ? $_SESSION['derniere_reservation']['id'] : ''; ?>" class="btn btn-success" target="_blank">
                                    <i class="fas fa-download me-2"></i>Télécharger la confirmation
                                </a>
                                <a href="dashboard/client/mes_reservations.php" class="btn btn-gold">Voir mes réservations</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- Affichage des erreurs -->
            <?php if (!empty($errors)) : ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées :</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Message informatif si la chambre a changé -->
            <?php if ($chambre_changee) : ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Information :</strong> <?php echo htmlspecialchars($message_chambre); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Sidebar avec récapitulatif -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Récapitulatif
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="fw-bold mb-1"><?php echo htmlspecialchars($chambre['hotel_nom']); ?></p>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($chambre['hotel_ville']); ?></p>
                            
                            <p class="mb-1"><i class="fas fa-bed me-2 text-gold"></i> <?php echo htmlspecialchars($chambre_disponible['type']); ?></p>
                            <p class="mb-1"><i class="fas fa-door-open me-2 text-gold"></i> Chambre n°<?php echo htmlspecialchars($chambre_disponible['numero']); ?></p>
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
                
                <div class="col-lg-8">
                    <!-- Affichage des détails de la chambre attribuée -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bed me-2 text-gold"></i>
                                Chambre attribuée
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Numéro :</strong> <?php echo htmlspecialchars($chambre_disponible['numero']); ?></p>
                                    <p class="mb-2"><strong>Type :</strong> <?php echo htmlspecialchars($chambre_disponible['type']); ?></p>
                                    <p class="mb-0"><strong>Capacité :</strong> <?php echo htmlspecialchars($chambre_disponible['capacite']); ?> personne(s)</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Prix par nuit :</strong> <?php echo number_format($chambre_disponible['prix_nuit'], 2, ',', ' '); ?> MAD</p>
                                    <p class="mb-0"><strong>Description :</strong> <?php echo htmlspecialchars($chambre_disponible['description'] ?? 'Non spécifiée'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaire de réservation -->
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-gold text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                Informations personnelles
                            </h3>
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
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>
.hero-section {
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(212,175,55,0.1), rgba(184,134,11,0.1));
    z-index: 1;
}

.hero-section .container {
    position: relative;
    z-index: 2;
}

.card.border-0 {
    border-radius: 15px !important;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.backdrop-blur {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

@media (max-width: 768px) {
    .hero-section {
        min-height: 300px;
    }
    
    .display-4 {
        font-size: 2rem;
    }
}
</style>
<script>
function changeMainImage(newSrc) {
    const mainImage = document.querySelector('.main-hotel-image');
    mainImage.style.opacity = '0.7';
    setTimeout(() => {
        mainImage.src = newSrc;
        mainImage.style.opacity = '1';
    }, 150);
}

// Style pour le gradient overlay
const style = document.createElement('style');
style.textContent = `
    .bg-gradient-dark {
        background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.3), transparent);
    }
    .thumbnail-image:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
    }
    .main-hotel-image:hover {
        transform: scale(1.02);
    }
`;
document.head.appendChild(style);
</script>