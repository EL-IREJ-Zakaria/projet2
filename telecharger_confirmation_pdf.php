use TCPDF;
<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'vendor/autoload.php'; // Si vous utilisez Composer

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

if (!$reservation_id) {
    die('ID de réservation manquant');
}

// Récupérer les détails de la réservation (même code que précédemment)
// ...

// Créer le PDF avec TCPDF
$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator('Luxury Hotels');
$pdf->SetAuthor('Luxury Hotels');
$pdf->SetTitle('Confirmation de Réservation #' . $reservation['id']);
$pdf->SetSubject('Confirmation de Réservation');

// Supprimer l'en-tête et le pied de page par défaut
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Ajouter une page
$pdf->AddPage();

// Définir la police
$pdf->SetFont('helvetica', '', 12);

// Contenu HTML (même que précédemment mais adapté pour TCPDF)
$html = '<!-- Votre HTML ici -->';

// Écrire le HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Sortir le PDF
$pdf->Output('confirmation_reservation_' . $reservation['id'] . '.pdf', 'D');
?>