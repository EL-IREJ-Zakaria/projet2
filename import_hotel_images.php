<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Configuration
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

// Fonction pour télécharger une image depuis une URL
function downloadImage($url, $destination) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false) {
        return file_put_contents($destination, $data);
    }
    
    return false;
}

// Fonction pour rechercher des images sur Unsplash (API gratuite)
function searchUnsplashImages($query, $count = 5) {
    // Clé API Unsplash (vous devez vous inscrire sur unsplash.com/developers)
    $accessKey = 'VOTRE_CLE_API_UNSPLASH'; // À remplacer par votre clé
    
    $url = "https://api.unsplash.com/search/photos?query=" . urlencode($query) . "&per_page=" . $count . "&client_id=" . $accessKey;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Luxury Hotels App');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['results'] ?? [];
    }
    
    return [];
}

// Fonction alternative avec des URLs d'images prédéfinies (sans API)
function getHotelImages($hotelName, $city) {
    // URLs d'images de haute qualité pour les hôtels
    $imageUrls = [
        // Images génériques d'hôtels de luxe
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800', // Hôtel de luxe
        'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=800', // Hall d'hôtel
        'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800', // Restaurant d'hôtel
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800', // Piscine d'hôtel
        'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', // Spa d'hôtel
    ];
    
    // Images spécifiques par ville
    $cityImages = [
        'Paris' => [
            'https://images.unsplash.com/photo-1502602898536-47ad22581b52?w=800', // Paris hôtel
            'https://images.unsplash.com/photo-1549144511-f099e773c147?w=800', // Paris luxury
        ],
        'Casablanca' => [
            'https://images.unsplash.com/photo-1539650116574-75c0c6d73f6e?w=800', // Maroc architecture
            'https://images.unsplash.com/photo-1570829460005-c840387bb1ca?w=800', // Maroc luxury
        ],
        'Marrakech' => [
            'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800', // Marrakech
            'https://images.unsplash.com/photo-1570829460005-c840387bb1ca?w=800', // Riad
        ],
        'Nice' => [
            'https://images.unsplash.com/photo-1549144511-f099e773c147?w=800', // Côte d'Azur
            'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800', // Méditerranée
        ]
    ];
    
    $urls = $imageUrls;
    if (isset($cityImages[$city])) {
        $urls = array_merge($cityImages[$city], $imageUrls);
    }
    
    return array_slice($urls, 0, 5);
}

// Script principal
echo "<h2>Import d'images pour les hôtels</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

// Récupérer tous les hôtels
$hotels_sql = "SELECT * FROM hotels ORDER BY id ASC";
$hotels_result = executeQuery($hotels_sql);

while ($hotel = $hotels_result->fetch_assoc()) {
    echo "<h3>Traitement de l'hôtel: " . htmlspecialchars($hotel['nom']) . " (" . htmlspecialchars($hotel['ville']) . ")</h3>";
    
    // Vérifier si l'hôtel a déjà des images
    $existing_images_sql = "SELECT COUNT(*) as count FROM hotel_images WHERE hotel_id = ?";
    $existing_result = executeQuery($existing_images_sql, "i", [$hotel['id']]);
    $existing_count = $existing_result->fetch_assoc()['count'];
    
    if ($existing_count > 0) {
        echo "<p style='color: orange;'>✓ Cet hôtel a déjà {$existing_count} image(s). Passage au suivant.</p>";
        continue;
    }
    
    // Obtenir les URLs d'images
    $imageUrls = getHotelImages($hotel['nom'], $hotel['ville']);
    
    $imageTypes = ['principale', 'interieur', 'restaurant', 'piscine', 'spa'];
    $imageDescriptions = [
        'Façade principale de l\'hôtel',
        'Hall d\'accueil et réception',
        'Restaurant gastronomique',
        'Piscine et espace détente',
        'Spa et centre de bien-être'
    ];
    
    $uploadDir = 'assets/images/hotels/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $successCount = 0;
    
    foreach ($imageUrls as $index => $imageUrl) {
        if ($index >= 5) break; // Limiter à 5 images par hôtel
        
        $filename = 'hotel_' . $hotel['id'] . '_' . ($index + 1) . '.jpg';
        $destination = $uploadDir . $filename;
        
        echo "<p>Téléchargement de l'image " . ($index + 1) . "... ";
        
        if (downloadImage($imageUrl, $destination)) {
            // Insérer en base de données
            $insert_sql = "INSERT INTO hotel_images (hotel_id, nom_image, description, type_image, ordre_affichage) VALUES (?, ?, ?, ?, ?)";
            $result = executeQuery($insert_sql, "isssi", [
                $hotel['id'],
                $filename,
                $imageDescriptions[$index] ?? 'Image de l\'hôtel',
                $imageTypes[$index] ?? 'autre',
                $index + 1
            ]);
            
            if ($result) {
                echo "<span style='color: green;'>✓ Succès</span></p>";
                $successCount++;
            } else {
                echo "<span style='color: red;'>✗ Erreur base de données</span></p>";
                unlink($destination); // Supprimer le fichier si erreur DB
            }
        } else {
            echo "<span style='color: red;'>✗ Échec du téléchargement</span></p>";
        }
        
        // Pause pour éviter de surcharger les serveurs
        sleep(1);
    }
    
    echo "<p><strong>Résultat: {$successCount} image(s) ajoutée(s) pour cet hôtel.</strong></p>";
    echo "<hr>";
}

echo "<h3 style='color: green;'>Import terminé !</h3>";
echo "</div>";
?>