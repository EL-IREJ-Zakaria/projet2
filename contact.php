<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$message_sent = false;
$error_message = '';

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = cleanInput($_POST['nom'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $sujet = cleanInput($_POST['sujet'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error_message = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Adresse email invalide.';
    } else {
        // Enregistrer le message en base de données
        $sql = "INSERT INTO messages_contact (nom, email, sujet, message, date_envoi) VALUES (?, ?, ?, ?, NOW())";
        $result = executeQuery($sql, "ssss", [$nom, $email, $sujet, $message]);
        
        if ($result) {
            $message_sent = true;
        } else {
            $error_message = 'Erreur lors de l\'envoi du message. Veuillez réessayer.';
        }
    }
}

$page_title = "Contact";
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-4">Contactez-nous</h1>
            <p class="lead text-muted">Notre équipe est à votre disposition pour répondre à toutes vos questions</p>
        </div>
    </div>

    <div class="row">
        <!-- Formulaire de contact -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4">Envoyez-nous un message</h3>
                    
                    <?php if ($message_sent): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet *</label>
                            <select class="form-select" id="sujet" name="sujet" required>
                                <option value="">Choisissez un sujet</option>
                                <option value="Réservation" <?php echo (($_POST['sujet'] ?? '') === 'Réservation') ? 'selected' : ''; ?>>Réservation</option>
                                <option value="Information" <?php echo (($_POST['sujet'] ?? '') === 'Information') ? 'selected' : ''; ?>>Demande d'information</option>
                                <option value="Réclamation" <?php echo (($_POST['sujet'] ?? '') === 'Réclamation') ? 'selected' : ''; ?>>Réclamation</option>
                                <option value="Partenariat" <?php echo (($_POST['sujet'] ?? '') === 'Partenariat') ? 'selected' : ''; ?>>Partenariat</option>
                                <option value="Autre" <?php echo (($_POST['sujet'] ?? '') === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required placeholder="Décrivez votre demande en détail..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-gold btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Informations de contact -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">Nos coordonnées</h4>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-map-marker-alt text-gold me-2"></i>Adresse</h6>
                        <p class="text-muted mb-0">CMC TAMESNA<br>RABAT SALé KéNITRA, Maroc</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-phone text-gold me-2"></i>Téléphone</h6>
                        <p class="text-muted mb-0">+212 6 49 14 44 68</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-envelope text-gold me-2"></i>Email</h6>
                        <p class="text-muted mb-0">ELLEIREJZAKARIA@gmail.com</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-clock text-gold me-2"></i>Horaires</h6>
                        <p class="text-muted mb-0">
                            Lun - Ven: 9h00 - 18h00<br>
                            Sam - Dim: 10h00 - 16h00
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">Urgences 24h/24</h4>
                    <p class="text-muted mb-3">Pour toute urgence concernant votre séjour :</p>
                    <p class="fw-bold text-gold fs-5">+212 5 24 38 86 00</p>
                    <p class="small text-muted">Service disponible 24h/24 et 7j/7</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Questions fréquentes</h3>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            Comment puis-je modifier ma réservation ?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Vous pouvez modifier votre réservation en vous connectant à votre compte client ou en nous contactant directement. Les modifications sont possibles jusqu'à 24h avant votre arrivée.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            Quelles sont les conditions d'annulation ?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            L'annulation gratuite est possible jusqu'à 48h avant votre arrivée. Au-delà, des frais d'annulation peuvent s'appliquer selon le type de chambre réservée.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            Acceptez-vous les animaux de compagnie ?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Oui, nous acceptons les animaux de compagnie dans certains de nos hôtels. Des frais supplémentaires peuvent s'appliquer. Veuillez nous contacter pour plus d'informations.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
