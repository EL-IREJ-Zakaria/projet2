<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = "Nos Services";
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-4">Nos Services Exclusifs</h1>
            <p class="lead text-muted">Découvrez l'excellence de nos services de luxe</p>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="row g-4">
        <!-- Conciergerie -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-concierge-bell fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Conciergerie 24/7</h4>
                    <p class="card-text">Notre équipe de conciergerie est à votre disposition 24h/24 pour répondre à tous vos besoins et organiser vos activités.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Réservations de restaurants</li>
                        <li><i class="fas fa-check text-success me-2"></i>Billets de spectacles</li>
                        <li><i class="fas fa-check text-success me-2"></i>Transferts aéroport</li>
                        <li><i class="fas fa-check text-success me-2"></i>Excursions privées</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Spa & Bien-être -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-spa fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Spa & Bien-être</h4>
                    <p class="card-text">Détendez-vous dans nos espaces spa luxueux avec des soins personnalisés par des thérapeutes experts.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Massages thérapeutiques</li>
                        <li><i class="fas fa-check text-success me-2"></i>Soins du visage</li>
                        <li><i class="fas fa-check text-success me-2"></i>Piscines chauffées</li>
                        <li><i class="fas fa-check text-success me-2"></i>Saunas et hammams</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Restauration -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-utensils fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Restauration Gastronomique</h4>
                    <p class="card-text">Savourez une cuisine d'exception préparée par nos chefs étoilés dans nos restaurants gastronomiques.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Restaurants étoilés</li>
                        <li><i class="fas fa-check text-success me-2"></i>Service en chambre 24h/24</li>
                        <li><i class="fas fa-check text-success me-2"></i>Bars à cocktails</li>
                        <li><i class="fas fa-check text-success me-2"></i>Menus personnalisés</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transport -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-car fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Transport de Luxe</h4>
                    <p class="card-text">Voyagez avec style grâce à notre flotte de véhicules de luxe et nos services de transport premium.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Limousines avec chauffeur</li>
                        <li><i class="fas fa-check text-success me-2"></i>Transferts aéroport VIP</li>
                        <li><i class="fas fa-check text-success me-2"></i>Hélicoptères privés</li>
                        <li><i class="fas fa-check text-success me-2"></i>Yachts de luxe</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Événements -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-calendar-alt fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Événements Privés</h4>
                    <p class="card-text">Organisez vos événements les plus importants dans nos espaces d'exception avec un service sur mesure.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Mariages de luxe</li>
                        <li><i class="fas fa-check text-success me-2"></i>Conférences d'affaires</li>
                        <li><i class="fas fa-check text-success me-2"></i>Réceptions privées</li>
                        <li><i class="fas fa-check text-success me-2"></i>Anniversaires exclusifs</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Business Center -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-briefcase fa-3x text-gold"></i>
                    </div>
                    <h4 class="card-title mb-3">Business Center</h4>
                    <p class="card-text">Travaillez dans des conditions optimales grâce à nos espaces business équipés des dernières technologies.</p>
                    <ul class="list-unstyled text-start">
                        <li><i class="fas fa-check text-success me-2"></i>Salles de réunion équipées</li>
                        <li><i class="fas fa-check text-success me-2"></i>WiFi haut débit</li>
                        <li><i class="fas fa-check text-success me-2"></i>Services de secrétariat</li>
                        <li><i class="fas fa-check text-success me-2"></i>Équipements audiovisuels</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="row mt-5">
        <div class="col-12 text-center">
            <div class="bg-light p-5 rounded">
                <h3 class="mb-3">Besoin d'un service personnalisé ?</h3>
                <p class="mb-4">Notre équipe est à votre disposition pour créer une expérience sur mesure.</p>
                <a href="/projet2/contact.php" class="btn btn-gold btn-lg">Nous contacter</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>