<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

if (!$reservation_id) {
    die('ID de réservation manquant');
}

// Récupérer les détails de la réservation
$sql = "SELECT r.*, c.numero as chambre_numero, c.type as chambre_type, 
               h.nom as hotel_nom, h.adresse as hotel_adresse, h.ville as hotel_ville,
               h.pays as hotel_pays, h.etoiles as hotel_etoiles,
               cl.nom as client_nom, cl.prenom as client_prenom, cl.email as client_email
        FROM reservations r
        JOIN chambres c ON r.chambre_id = c.id
        JOIN hotels h ON c.hotel_id = h.id
        JOIN clients cl ON r.client_id = cl.id
        WHERE r.id = ? AND r.client_id = ?";

$result = executeQuery($sql, "ii", [$reservation_id, $_SESSION['client_id']]);
$reservation = $result->fetch_assoc();

if (!$reservation) {
    die('Réservation non trouvée');
}

// Calculer le nombre de nuits
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$nb_nuits = $date_arrivee->diff($date_depart)->days;

// Générer le contenu HTML pour le PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirmation de Réservation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #d4af37; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #d4af37; }
        .confirmation { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .details { margin: 20px 0; }
        .row { display: flex; justify-content: space-between; margin: 10px 0; }
        .label { font-weight: bold; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #666; }
        .success-icon { color: #28a745; font-size: 48px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LUXURY HOTELS</div>
        <h1>Confirmation de Réservation</h1>
        <p>Numéro de réservation: #<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></p>
    </div>

    <div class="confirmation">
        <div class="success-icon">✓</div>
        <h2 style="text-align: center; color: #28a745;">Réservation Confirmée</h2>
        <p style="text-align: center;">Votre réservation a été enregistrée avec succès.</p>
    </div>

    <div class="details">
        <h3>Informations Client</h3>
        <div class="row">
            <span class="label">Nom:</span>
            <span><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></span>
        </div>
        <div class="row">
            <span class="label">Email:</span>
            <span><?php echo htmlspecialchars($reservation['client_email']); ?></span>
        </div>
    </div>

    <div class="details">
        <h3>Détails de l'Hôtel</h3>
        <div class="row">
            <span class="label">Hôtel:</span>
            <span><?php echo htmlspecialchars($reservation['hotel_nom']); ?> (<?php echo $reservation['hotel_etoiles']; ?> étoiles)</span>
        </div>
        <div class="row">
            <span class="label">Adresse:</span>
            <span><?php echo htmlspecialchars($reservation['hotel_adresse'] . ', ' . $reservation['hotel_ville'] . ', ' . $reservation['hotel_pays']); ?></span>
        </div>
    </div>

    <div class="details">
        <h3>Détails de la Réservation</h3>
        <div class="row">
            <span class="label">Chambre:</span>
            <span>N°<?php echo htmlspecialchars($reservation['chambre_numero']); ?> - <?php echo htmlspecialchars($reservation['chambre_type']); ?></span>
        </div>
        <div class="row">
            <span class="label">Date d'arrivée:</span>
            <span><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></span>
        </div>
        <div class="row">
            <span class="label">Date de départ:</span>
            <span><?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></span>
        </div>
        <div class="row">
            <span class="label">Nombre de nuits:</span>
            <span><?php echo $nb_nuits; ?></span>
        </div>
        <div class="row">
            <span class="label">Nombre de personnes:</span>
            <span><?php echo $reservation['nb_personnes']; ?></span>
        </div>
        <div class="row">
            <span class="label">Statut:</span>
            <span style="color: #28a745; font-weight: bold;"><?php echo ucfirst($reservation['statut']); ?></span>
        </div>
    </div>

    <div class="details">
        <h3>Informations Financières</h3>
        <div class="row">
            <span class="label">Prix total:</span>
            <span style="font-size: 18px; font-weight: bold; color: #d4af37;"><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> MAD</span>
        </div>
        <div class="row">
            <span class="label">Date de réservation:</span>
            <span><?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'])); ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Merci de votre confiance. Nous vous souhaitons un excellent séjour !</p>
        <p>Pour toute question, contactez-nous à contact@luxuryhotels.com</p>
        <p>Document généré le <?php echo date('d/m/Y à H:i'); ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Si vous voulez utiliser une bibliothèque PDF comme TCPDF ou mPDF, installez-la via Composer
// Pour une solution simple, on peut utiliser DomPDF
// Ici, je fournis une version HTML qui peut être imprimée en PDF par le navigateur

// Définir les en-têtes pour le téléchargement
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: inline; filename="confirmation_reservation_' . $reservation['id'] . '.html"');

echo $html;
?>