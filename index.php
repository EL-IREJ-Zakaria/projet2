<?php include 'includes/header.php'; ?>

<!-- Hero Section avec image de fond améliorée -->
<section class="hero position-relative" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&h=800&fit=crop') center/cover; min-height: 100vh;">
    <div class="container h-100 d-flex align-items-center">
        <div class="row w-100">
            <div class="col-lg-8 hero-content text-white">
                <h1 class="display-3 fw-bold mb-4">Découvrez le luxe à son apogée</h1>
                <p class="lead mb-5 fs-4">Réservez votre séjour dans les plus beaux hôtels du monde et vivez une expérience inoubliable.</p>
                <a href="#search" class="btn btn-gold btn-lg px-5 py-3 fs-5">Réserver maintenant</a>
            </div>
        </div>
    </div>
    <!-- Overlay décoratif -->
    <div class="position-absolute bottom-0 start-0 end-0" style="height: 100px; background: linear-gradient(to top, rgba(255,255,255,1), transparent);"></div>
</section>

<!-- Section de recherche avec image de fond -->
<section id="search" class="py-5 position-relative" style="background: linear-gradient(rgba(255,255,255,0.95), rgba(255,255,255,0.95)), url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1920&h=600&fit=crop') center/cover;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-form shadow-lg border-0 rounded-4" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
                    <div class="p-5">
                        <h3 class="text-center mb-4 text-primary fw-bold">Trouvez l'hôtel parfait pour votre séjour</h3>
                    <form action="recherche.php" method="GET" class="row g-3 needs-validation" novalidate>
                        <div class="col-md-4">
                            <label for="destination" class="form-label">Destination</label>
                            <select class="form-select" id="destination" name="ville" required>
                                <option value="" selected disabled>Choisir une ville</option>
                                <option value="Marrakech">Marrakech</option>
                                <option value="Casablanca">Casablanca</option>
                                <option value="Fès">Fès</option>
                                <option value="Agadir">Agadir</option>
                                <option value="Rabat">Rabat</option>
                            </select>
                            <div class="invalid-feedback">
                                Veuillez choisir une destination.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_arrivee" class="form-label">Arrivée</label>
                            <input type="date" class="form-control datepicker" id="date_arrivee" name="date_arrivee" required>
                            <div class="invalid-feedback">
                                Veuillez choisir une date d'arrivée.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_depart" class="form-label">Départ</label>
                            <input type="date" class="form-control datepicker" id="date_depart" name="date_depart" required>
                            <div class="invalid-feedback">
                                Veuillez choisir une date de départ.
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="personnes" class="form-label">Personnes</label>
                            <select class="form-select" id="personnes" name="personnes" required>
                                <option value="1">1</option>
                                <option value="2" selected>2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5+</option>
                            </select>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-gold px-5 py-2">Rechercher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hôtels en vedette -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Nos hôtels en vedette</h2>
        <div class="row">
            <?php
            // Récupérer les hôtels en vedette (à remplacer par une requête réelle)
            $hotels = [
                [
                    'id' => 1,
                    'nom' => 'La Mamounia',
                    'ville' => 'Marrakech',
                    'pays' => 'Maroc',
                    'etoiles' => 5,
                    'prix' => 450,
                    'image' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80'
                ],
                [
                    'id' => 2,
                    'nom' => 'Four Seasons Resort',
                    'ville' => 'Casablanca',
                    'pays' => 'Maroc',
                    'etoiles' => 5,
                    'prix' => 520,
                    'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80'
                ],
                [
                    'id' => 3,
                    'nom' => 'Royal Mansour',
                    'ville' => 'Marrakech',
                    'pays' => 'Maroc',
                    'etoiles' => 5,
                    'prix' => 680,
                    'image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80'
                ],
            ];
            
            foreach ($hotels as $hotel): 
            ?>
            <div class="col-md-4 mb-4">
                <div class="card hotel-card h-100" data-ville="<?php echo htmlspecialchars($hotel['ville']); ?>" data-etoiles="<?php echo $hotel['etoiles']; ?>">
                    <img src="<?php echo htmlspecialchars($hotel['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($hotel['nom']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($hotel['nom']); ?></h5>
                        <p class="card-text mb-1">
                            <i class="fas fa-map-marker-alt text-gold me-2"></i>
                            <?php echo htmlspecialchars($hotel['ville']); ?>, <?php echo htmlspecialchars($hotel['pays']); ?>
                        </p>
                        <p class="card-text stars mb-3">
                            <?php for ($i = 0; $i < $hotel['etoiles']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </p>
                        <p class="card-text fw-bold">À partir de <?php echo $hotel['prix']; ?> MAD / nuit</p>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline-gold">Voir les détails</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="hotels.php" class="btn btn-outline-dark">Voir tous nos hôtels</a>
        </div>
    </div>
</section>

<!-- Services -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Nos services exclusifs</h2>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="p-4 h-100">
                    <i class="fas fa-concierge-bell fa-3x text-gold mb-4"></i>
                    <h4>Conciergerie 24/7</h4>
                    <p class="text-muted">Un service de conciergerie disponible à tout moment pour répondre à vos besoins.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4 h-100">
                    <i class="fas fa-spa fa-3x text-gold mb-4"></i>
                    <h4>Spa & Bien-être</h4>
                    <p class="text-muted">Profitez de nos installations spa pour un moment de détente absolue.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4 h-100">
                    <i class="fas fa-utensils fa-3x text-gold mb-4"></i>
                    <h4>Gastronomie</h4>
                    <p class="text-muted">Des restaurants étoilés pour une expérience culinaire inoubliable.</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="p-4 h-100">
                    <i class="fas fa-car fa-3x text-gold mb-4"></i>
                    <h4>Service de voiturier</h4>
                    <p class="text-muted">Un service de voiturier et de location de véhicules de luxe.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Témoignages -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Ce que disent nos clients</h2>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-gold">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"Une expérience inoubliable au Grand Hôtel Paris. Le service était impeccable et les chambres somptueuses. Je recommande vivement !"</p>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="https://randomuser.me/api/portraits/women/12.jpg" alt="Sophie Martin" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Sophie Martin</h6>
                                <small class="text-muted">Paris, France</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-gold">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"Le Riviera Palace à Nice est tout simplement magnifique. La vue sur la mer est à couper le souffle et le personnel est aux petits soins."</p>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Thomas Dubois" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Thomas Dubois</h6>
                                <small class="text-muted">Lyon, France</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-gold">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"Séjour parfait au Royal Cannes. L'emplacement est idéal, à deux pas de la Croisette. Le spa est un véritable havre de paix."</p>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="https://randomuser.me/api/portraits/women/28.jpg" alt="Émilie Laurent" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Émilie Laurent</h6>
                                <small class="text-muted">Bordeaux, France</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <h3 class="mb-4">Restez informé de nos offres exclusives</h3>
                <p class="mb-4">Inscrivez-vous à notre newsletter pour recevoir nos meilleures offres et promotions.</p>
                <form class="row g-3 justify-content-center">
                    <div class="col-8">
                        <input type="email" class="form-control" placeholder="Votre adresse email" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-gold">S'inscrire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>