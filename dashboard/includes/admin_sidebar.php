<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Menu administrateur</h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="/projet2/dashboard/admin/index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
        </a>
        <a href="/projet2/dashboard/admin/hotels.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'hotels.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-hotel me-2"></i> Gestion des hôtels
        </a>
        <a href="/projet2/dashboard/admin/chambres.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'chambres.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-bed me-2"></i> Gestion des chambres
        </a>
        <a href="/projet2/dashboard/admin/reservations.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-calendar-check me-2"></i> Gestion des réservations
        </a>
        <a href="/projet2/dashboard/admin/clients.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'clients.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-users me-2"></i> Gestion des clients
        </a>
        <a href="/projet2/dashboard/admin/rapports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'rapports.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-chart-bar me-2"></i> Rapports et statistiques
        </a>
        <a href="/projet2/dashboard/admin/parametres.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'parametres.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-cog me-2"></i> Paramètres
        </a>
        <a href="/projet2/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Raccourcis</h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <a href="/projet2/dashboard/admin/ajouter_hotel.php" class="btn btn-sm btn-outline-gold">
                <i class="fas fa-plus-circle me-2"></i> Ajouter un hôtel
            </a>
            <a href="/projet2/dashboard/admin/ajouter_chambre.php" class="btn btn-sm btn-outline-gold">
                <i class="fas fa-plus-circle me-2"></i> Ajouter une chambre
            </a>
            <a href="/projet2/dashboard/admin/nouvelle_reservation.php" class="btn btn-sm btn-outline-gold">
                <i class="fas fa-plus-circle me-2"></i> Nouvelle réservation
            </a>
        </div>
    </div>
</div>