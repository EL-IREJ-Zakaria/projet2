<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Import d'images pour les hôtels</h1>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Options d'import</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Information</h6>
                                <p>Cet outil permet d'importer automatiquement des images de haute qualité pour vos hôtels depuis des sources libres de droits.</p>
                            </div>
                            
                            <form id="importForm">
                                <div class="mb-3">
                                    <label class="form-label">Source des images</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="source" id="unsplash" value="unsplash" checked>
                                        <label class="form-check-label" for="unsplash">
                                            <strong>Unsplash</strong> - Images de haute qualité (nécessite une clé API)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="source" id="pexels" value="pexels">
                                        <label class="form-check-label" for="pexels">
                                            <strong>Pexels</strong> - Images professionnelles (nécessite une clé API)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="source" id="predefined" value="predefined">
                                        <label class="form-check-label" for="predefined">
                                            <strong>Images prédéfinies</strong> - Collection d'images sélectionnées (sans API)
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="apiKeyDiv" style="display: none;">
                                    <label for="apiKey" class="form-label">Clé API</label>
                                    <input type="text" class="form-control" id="apiKey" placeholder="Entrez votre clé API">
                                    <div class="form-text">
                                        <a href="https://unsplash.com/developers" target="_blank">Obtenir une clé Unsplash</a> | 
                                        <a href="https://www.pexels.com/api/" target="_blank">Obtenir une clé Pexels</a>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Hôtels à traiter</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hotels" id="allHotels" value="all" checked>
                                        <label class="form-check-label" for="allHotels">
                                            Tous les hôtels sans images
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="hotels" id="specificHotel" value="specific">
                                        <label class="form-check-label" for="specificHotel">
                                            Hôtel spécifique
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="hotelSelectDiv" style="display: none;">
                                    <select class="form-select" id="hotelSelect">
                                        <option value="">Choisir un hôtel</option>
                                        <?php
                                        $hotels_sql = "SELECT id, nom, ville FROM hotels ORDER BY nom ASC";
                                        $hotels_result = executeQuery($hotels_sql);
                                        while ($hotel = $hotels_result->fetch_assoc()) {
                                            echo "<option value='{$hotel['id']}'>{$hotel['nom']} - {$hotel['ville']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <button type="button" class="btn btn-primary" onclick="startImport()">
                                    <i class="fas fa-download"></i> Commencer l'import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Progression</h5>
                        </div>
                        <div class="card-body">
                            <div id="progressContainer" style="display: none;">
                                <div class="progress mb-3">
                                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="progressText">En attente...</div>
                            </div>
                            <div id="results"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Afficher/masquer les champs selon la source sélectionnée
document.querySelectorAll('input[name="source"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const apiKeyDiv = document.getElementById('apiKeyDiv');
        if (this.value === 'predefined') {
            apiKeyDiv.style.display = 'none';
        } else {
            apiKeyDiv.style.display = 'block';
        }
    });
});

// Afficher/masquer la sélection d'hôtel
document.querySelectorAll('input[name="hotels"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const hotelSelectDiv = document.getElementById('hotelSelectDiv');
        if (this.value === 'specific') {
            hotelSelectDiv.style.display = 'block';
        } else {
            hotelSelectDiv.style.display = 'none';
        }
    });
});

function startImport() {
    const source = document.querySelector('input[name="source"]:checked').value;
    const hotels = document.querySelector('input[name="hotels"]:checked').value;
    const apiKey = document.getElementById('apiKey').value;
    const hotelId = document.getElementById('hotelSelect').value;
    
    // Validation
    if ((source === 'unsplash' || source === 'pexels') && !apiKey) {
        alert('Veuillez entrer votre clé API');
        return;
    }
    
    if (hotels === 'specific' && !hotelId) {
        alert('Veuillez sélectionner un hôtel');
        return;
    }
    
    // Afficher la progression
    document.getElementById('progressContainer').style.display = 'block';
    document.getElementById('progressText').textContent = 'Démarrage de l\'import...';
    
    // Appel AJAX vers le script d'import
    const formData = new FormData();
    formData.append('source', source);
    formData.append('hotels', hotels);
    formData.append('apiKey', apiKey);
    formData.append('hotelId', hotelId);
    
    fetch('process_import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('progressBar').style.width = '100%';
        document.getElementById('progressText').textContent = 'Import terminé !';
        document.getElementById('results').innerHTML = `
            <div class="alert alert-success">
                <h6>Import réussi !</h6>
                <p>${data.message}</p>
                <p><strong>${data.totalImages}</strong> images importées</p>
            </div>
        `;
    })
    .catch(error => {
        document.getElementById('results').innerHTML = `
            <div class="alert alert-danger">
                <h6>Erreur</h6>
                <p>Une erreur s'est produite lors de l'import.</p>
            </div>
        `;
    });
}
</script>

<?php include '../includes/footer.php'; ?>