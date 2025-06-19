<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Menu</h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="/projet2/dashboard/client/index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
        </a>
        <a href="/projet2/dashboard/client/mes_reservations.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'mes_reservations.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-calendar-check me-2"></i> Mes réservations
        </a>
        <a href="/projet2/dashboard/client/modifier_profil.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'modifier_profil.php' ? 'active bg-gold text-white' : ''; ?>">
            <i class="fas fa-user-edit me-2"></i> Modifier mon profil
        </a>
        <a href="/projet2/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Besoin d'aide ?</h5>
    </div>
    <div class="card-body">
        <p class="card-text">Notre service client est disponible 24/7 pour vous aider.</p>
        <a href="#" class="btn btn-outline-gold w-100">
            <i class="fas fa-headset me-2"></i> Contacter le support
        </a>
    </div>
</div>