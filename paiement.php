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
    header('Location: index.php?error=indisponible');
    exit;
}

// Calcul du nombre de nuits et du prix total
$date1 = new DateTime($date_arrivee);
$date2 = new DateTime($date_depart);
$nb_nuits = $date2->diff($date1)->days;
$prix_total = $chambre['prix_nuit'] * $nb_nuits;
$taxes = $prix_total * 0.1; // 10% de taxes
$prix_final = $prix_total + $taxes;

// Récupération des informations de l'utilisateur
$client = fetchOne("SELECT * FROM clients WHERE id = ?", "i", [$_SESSION['user_id']]);

$errors = [];
$success = false;

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Récupération des données de paiement
        $nom_carte = cleanInput($_POST['nom_carte'] ?? '');
        $numero_carte = cleanInput($_POST['numero_carte'] ?? '');
        $expiration = cleanInput($_POST['expiration'] ?? '');
        $cvv = cleanInput($_POST['cvv'] ?? '');
        $adresse_facturation = cleanInput($_POST['adresse_facturation'] ?? '');
        $ville_facturation = cleanInput($_POST['ville_facturation'] ?? '');
        $code_postal = cleanInput($_POST['code_postal'] ?? '');
        
        // Validation des données de paiement
        if (empty($nom_carte)) {
            $errors[] = "Le nom sur la carte est requis.";
        }
        
        if (empty($numero_carte) || !preg_match('/^[0-9]{16}$/', str_replace(' ', '', $numero_carte))) {
            $errors[] = "Le numéro de carte doit contenir 16 chiffres.";
        }
        
        if (empty($expiration) || !preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiration)) {
            $errors[] = "La date d'expiration doit être au format MM/AA.";
        }
        
        if (empty($cvv) || !preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $errors[] = "Le CVV doit contenir 3 ou 4 chiffres.";
        }
        
        if (empty($adresse_facturation)) {
            $errors[] = "L'adresse de facturation est requise.";
        }
        
        if (empty($ville_facturation)) {
            $errors[] = "La ville de facturation est requise.";
        }
        
        if (empty($code_postal)) {
            $errors[] = "Le code postal est requis.";
        }
        
        // Simulation du traitement de paiement
        if (empty($errors)) {
            // Ici, vous intégreriez un vrai processeur de paiement (Stripe, PayPal, etc.)
            // Pour la simulation, on considère que le paiement est toujours réussi
            
            // Enregistrement de la transaction
            $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
            
            $sql_transaction = "INSERT INTO transactions (client_id, montant, statut, transaction_id, methode_paiement, created_at) 
                              VALUES (?, ?, 'réussie', ?, 'carte_credit', NOW())";
            $transaction_db_id = insertAndGetId($sql_transaction, "ids", [$_SESSION['user_id'], $prix_final, $transaction_id]);
            
            if ($transaction_db_id) {
                // Redirection vers la page de réservation avec l'ID de transaction
                $redirect_url = "reserver.php?chambre_id={$chambre_id}&date_arrivee={$date_arrivee}&date_depart={$date_depart}&transaction_id={$transaction_db_id}";
                header("Location: {$redirect_url}");
                exit;
            } else {
                $errors[] = "Erreur lors de l'enregistrement de la transaction.";
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Bannière de paiement -->
<section class="py-5 bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Paiement sécurisé</h1>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="progress mb-4">
                    <div class="progress-bar bg-gold" role="progressbar" style="width: 66%" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-center">
                    <div class="step completed">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mt-2">Sélection</p>
                    </div>
                    <div class="step active">
                        <i class="fas fa-credit-card text-gold"></i>
                        <p class="mt-2">Paiement</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-calendar-check text-muted"></i>
                        <p class="mt-2">Confirmation</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Formulaire de paiement -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($errors)) : ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h3 class="card-title mb-0"><i class="fas fa-credit-card me-2"></i>Informations de paiement</h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Méthodes de paiement -->
                            <div class="mb-4">
                                <h5 class="mb-3">Méthode de paiement</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card payment-method active">
                                            <div class="card-body text-center">
                                                <i class="fas fa-credit-card fa-2x text-gold mb-2"></i>
                                                <p class="mb-0">Carte de crédit</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card payment-method disabled">
                                            <div class="card-body text-center">
                                                <i class="fab fa-paypal fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">PayPal</p>
                                                <small class="text-muted">(Bientôt disponible)</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card payment-method disabled">
                                            <div class="card-body text-center">
                                                <i class="fas fa-university fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">Virement</p>
                                                <small class="text-muted">(Bientôt disponible)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informations de la carte -->
                            <div class="mb-4">
                                <h5 class="mb-3">Informations de la carte</h5>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="nom_carte" class="form-label">Nom sur la carte</label>
                                        <input type="text" class="form-control" id="nom_carte" name="nom_carte" required>
                                        <div class="invalid-feedback">Veuillez entrer le nom sur la carte.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="numero_carte" class="form-label">Numéro de carte</label>
                                        <input type="text" class="form-control" id="numero_carte" name="numero_carte" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        <div class="invalid-feedback">Veuillez entrer un numéro de carte valide.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                                        <div class="invalid-feedback">Veuillez entrer le CVV.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="expiration" class="form-label">Date d'expiration</label>
                                        <input type="text" class="form-control" id="expiration" name="expiration" placeholder="MM/AA" maxlength="5" required>
                                        <div class="invalid-feedback">Veuillez entrer la date d'expiration.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adresse de facturation -->
                            <div class="mb-4">
                                <h5 class="mb-3">Adresse de facturation</h5>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="adresse_facturation" class="form-label">Adresse</label>
                                        <input type="text" class="form-control" id="adresse_facturation" name="adresse_facturation" required>
                                        <div class="invalid-feedback">Veuillez entrer votre adresse.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="ville_facturation" class="form-label">Ville</label>
                                        <input type="text" class="form-control" id="ville_facturation" name="ville_facturation" required>
                                        <div class="invalid-feedback">Veuillez entrer votre ville.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="code_postal" class="form-label">Code postal</label>
                                        <input type="text" class="form-control" id="code_postal" name="code_postal" required>
                                        <div class="invalid-feedback">Veuillez entrer votre code postal.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="conditions_paiement" required>
                                <label class="form-check-label" for="conditions_paiement">
                                    J'accepte les <a href="#" target="_blank">conditions de paiement</a> et confirme que les informations fournies sont exactes
                                </label>
                                <div class="invalid-feedback">Vous devez accepter les conditions pour continuer.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-gold btn-lg">
                                    
                                    <i class="fas fa-lock me-2"></i>Payer <?php echo number_format($prix_final, 2, ',', ' '); ?> MAD
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Résumé de la commande -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Résumé de la réservation</h4>
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
                            <span>Sous-total</span>
                            <span><?php echo number_format($prix_total, 2, ',', ' '); ?> MAD</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxes et frais</span>
                            <span><?php echo number_format($taxes, 2, ',', ' '); ?> MAD</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total à payer</span>
                            <span class="text-gold"><?php echo number_format($prix_final, 2, ',', ' '); ?> MAD</span>
                        </div>
                    </div>
                </div>
                
                <!-- Sécurité -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-shield-alt text-success me-2"></i>Paiement sécurisé</h5>
                        <p class="card-text">Vos informations de paiement sont protégées par un cryptage SSL 256 bits. Nous ne stockons jamais vos données bancaires.</p>
                        <div class="d-flex justify-content-center mt-3">
                            <i class="fab fa-cc-visa fa-2x me-2 text-muted"></i>
                            <i class="fab fa-cc-mastercard fa-2x me-2 text-muted"></i>
                            <i class="fab fa-cc-amex fa-2x me-2 text-muted"></i>
                            <i class="fas fa-lock fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.payment-method {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.payment-method.active {
    border-color: #d4af37;
    background-color: #faf8f0;
}

.payment-method.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.payment-method:hover:not(.disabled) {
    border-color: #d4af37;
}

.step {
    flex: 1;
}

.step.completed i {
    color: #28a745;
}

.step.active i {
    color: #d4af37;
}
</style>

<script>
// Formatage automatique du numéro de carte
document.getElementById('numero_carte').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

// Formatage de la date d'expiration
document.getElementById('expiration').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// Validation CVV
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
</script>

<?php include 'includes/footer.php'; ?>