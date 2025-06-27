// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Animation au défilement
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 50) {
                element.classList.add('fade-in');
            }
        });
    };
    
    // Exécuter l'animation au chargement et au défilement
    animateOnScroll();
    window.addEventListener('scroll', animateOnScroll);
    
    // Validation des formulaires
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Datepicker pour les dates de réservation
    const dateInputs = document.querySelectorAll('.datepicker');
    if (dateInputs.length > 0) {
        dateInputs.forEach(input => {
            // Utilisation de l'API native de date HTML5
            input.setAttribute('min', new Date().toISOString().split('T')[0]);
            
            // Logique pour date_fin >= date_debut
            if (input.id === 'date_fin') {
                const dateDebut = document.getElementById('date_debut');
                if (dateDebut) {
                    dateDebut.addEventListener('change', function() {
                        input.setAttribute('min', this.value);
                        if (input.value && input.value < this.value) {
                            input.value = this.value;
                        }
                    });
                }
            }
        });
    }
    
    // Calcul dynamique du prix total
    const calculerPrixTotal = function() {
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        const prixParNuit = document.getElementById('prix_par_nuit');
        const prixTotalElement = document.getElementById('prix_total');
        
        if (dateDebut && dateFin && prixParNuit && prixTotalElement) {
            dateDebut.addEventListener('change', updatePrixTotal);
            dateFin.addEventListener('change', updatePrixTotal);
            
            function updatePrixTotal() {
                if (dateDebut.value && dateFin.value) {
                    const debut = new Date(dateDebut.value);
                    const fin = new Date(dateFin.value);
                    const diffTemps = fin.getTime() - debut.getTime();
                    const diffJours = Math.ceil(diffTemps / (1000 * 3600 * 24));
                    
                    if (diffJours > 0) {
                        const prixTotal = diffJours * parseFloat(prixParNuit.value);
                        prixTotalElement.textContent = prixTotal.toFixed(2) + ' MAD';
                        
                        // Mettre à jour le champ caché pour le formulaire
                        const prixTotalInput = document.getElementById('prix_total_input');
                        if (prixTotalInput) {
                            prixTotalInput.value = prixTotal.toFixed(2);
                        }
                    }
                }
            }
        }
    };
    
    calculerPrixTotal();
    
    // Filtres dynamiques pour la recherche d'hôtels
    const filtreHotels = document.getElementById('filtre-hotels');
    if (filtreHotels) {
        const inputRecherche = filtreHotels.querySelector('input[type="search"]');
        const selectVille = filtreHotels.querySelector('select[name="ville"]');
        const selectEtoiles = filtreHotels.querySelector('select[name="etoiles"]');
        
        const appliquerFiltres = function() {
            const recherche = inputRecherche.value.toLowerCase();
            const ville = selectVille.value;
            const etoiles = selectEtoiles.value;
            
            const hotels = document.querySelectorAll('.hotel-card');
            
            hotels.forEach(hotel => {
                const nom = hotel.querySelector('.card-title').textContent.toLowerCase();
                const villeHotel = hotel.dataset.ville;
                const etoilesHotel = hotel.dataset.etoiles;
                
                let afficher = true;
                
                if (recherche && !nom.includes(recherche)) {
                    afficher = false;
                }
                
                if (ville && villeHotel !== ville) {
                    afficher = false;
                }
                
                if (etoiles && etoilesHotel !== etoiles) {
                    afficher = false;
                }
                
                hotel.style.display = afficher ? 'block' : 'none';
            });
        };
        
        if (inputRecherche) inputRecherche.addEventListener('input', appliquerFiltres);
        if (selectVille) selectVille.addEventListener('change', appliquerFiltres);
        if (selectEtoiles) selectEtoiles.addEventListener('change', appliquerFiltres);
    }
});

// Validation du formulaire d'inscription - vérification des mots de passe
const passwordConfirmValidation = function() {
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    
    if (password && passwordConfirm) {
        passwordConfirm.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Les mots de passe ne correspondent pas.');
            } else {
                this.setCustomValidity('');
            }
        });
        
        password.addEventListener('input', function() {
            if (passwordConfirm.value !== '' && passwordConfirm.value !== this.value) {
                passwordConfirm.setCustomValidity('Les mots de passe ne correspondent pas.');
            } else {
                passwordConfirm.setCustomValidity('');
            }
        });
    }
};

passwordConfirmValidation();

// Slider de budget dynamique
class BudgetSlider {
    constructor(containerId, minValue, maxValue, currentMin, currentMax) {
        this.container = document.getElementById(containerId);
        this.minValue = minValue;
        this.maxValue = maxValue;
        this.currentMin = currentMin;
        this.currentMax = currentMax;
        this.init();
    }
    
    init() {
        if (!this.container) return;
        
        // Créer la structure HTML du slider
        this.container.innerHTML = `
            <div class="budget-slider">
                <div class="slider-track"></div>
                <div class="slider-handle" data-handle="min"></div>
                <div class="slider-handle" data-handle="max"></div>
            </div>
        `;
        
        this.slider = this.container.querySelector('.budget-slider');
        this.track = this.container.querySelector('.slider-track');
        this.minHandle = this.container.querySelector('[data-handle="min"]');
        this.maxHandle = this.container.querySelector('[data-handle="max"]');
        
        this.updateSlider();
        this.bindEvents();
    }
    
    updateSlider() {
        const minPercent = ((this.currentMin - this.minValue) / (this.maxValue - this.minValue)) * 100;
        const maxPercent = ((this.currentMax - this.minValue) / (this.maxValue - this.minValue)) * 100;
        
        this.minHandle.style.left = minPercent + '%';
        this.maxHandle.style.left = maxPercent + '%';
        
        this.track.style.left = minPercent + '%';
        this.track.style.width = (maxPercent - minPercent) + '%';
        
        // Mettre à jour l'affichage
        document.getElementById('budget-min-display').textContent = this.currentMin;
        document.getElementById('budget-max-display').textContent = this.currentMax;
        document.getElementById('budget_min').value = this.currentMin;
        document.getElementById('budget_max').value = this.currentMax;
    }
    
    bindEvents() {
        let isDragging = false;
        let activeHandle = null;
        
        const startDrag = (e, handle) => {
            isDragging = true;
            activeHandle = handle;
            handle.classList.add('active');
            e.preventDefault();
        };
        
        const drag = (e) => {
            if (!isDragging || !activeHandle) return;
            
            const rect = this.slider.getBoundingClientRect();
            const percent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
            const value = Math.round(this.minValue + (percent / 100) * (this.maxValue - this.minValue));
            
            if (activeHandle.dataset.handle === 'min') {
                this.currentMin = Math.min(value, this.currentMax - 50);
            } else {
                this.currentMax = Math.max(value, this.currentMin + 50);
            }
            
            this.updateSlider();
        };
        
        const stopDrag = () => {
            if (activeHandle) {
                activeHandle.classList.remove('active');
            }
            isDragging = false;
            activeHandle = null;
        };
        
        this.minHandle.addEventListener('mousedown', (e) => startDrag(e, this.minHandle));
        this.maxHandle.addEventListener('mousedown', (e) => startDrag(e, this.maxHandle));
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        
        // Support tactile
        this.minHandle.addEventListener('touchstart', (e) => startDrag(e.touches[0], this.minHandle));
        this.maxHandle.addEventListener('touchstart', (e) => startDrag(e.touches[0], this.maxHandle));
        
        document.addEventListener('touchmove', (e) => drag(e.touches[0]));
        document.addEventListener('touchend', stopDrag);
    }
}

// Initialiser le slider de budget au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les valeurs depuis PHP (vous devrez les passer depuis le serveur)
    const minGlobal = parseInt(document.querySelector('[data-prix-min]')?.dataset.prixMin || '0');
    const maxGlobal = parseInt(document.querySelector('[data-prix-max]')?.dataset.prixMax || '1000');
    const currentMin = parseInt(document.getElementById('budget_min')?.value || minGlobal);
    const currentMax = parseInt(document.getElementById('budget_max')?.value || maxGlobal);
    
    if (document.getElementById('budget-slider')) {
        new BudgetSlider('budget-slider', minGlobal, maxGlobal, currentMin, currentMax);
    }
});