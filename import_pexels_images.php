<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fonction pour rechercher des images sur Pexels
function searchPexelsImages($query, $count = 5) {
    $apiKey = 'VOTRE_CLE_API_PEXELS'; // Obtenez une clé gratuite sur pexels.com/api
    
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=" . $count;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['photos'] ?? [];
    }
    
    return [];
}

// Fonction pour télécharger depuis Pexels
function downloadPexelsImages($hotelName, $city, $hotelId) {
    $queries = [
        "luxury hotel {$city}",
        "hotel lobby luxury",
        "hotel restaurant fine dining",
        "hotel pool luxury",
        "hotel spa wellness"
    ];
    
    $imageTypes = ['principale', 'interieur', 'restaurant', 'piscine', 'spa'];
    $descriptions = [
        "Vue extérieure de l'hôtel {$hotelName}",
        "Hall d'accueil luxueux",
        "Restaurant gastronomique",
        "Piscine et espace détente",
        "Spa et centre de bien-être"
    ];
    
    $uploadDir = 'assets/images/hotels/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $successCount = 0;
    
    foreach ($queries as $index => $query) {
        $photos = searchPexelsImages($query, 1);
        
        if (!empty($photos)) {
            $photo = $photos[0];
            $imageUrl = $photo['src']['large'] ?? $photo['src']['original'];
            
            $filename = 'hotel_' . $hotelId . '_' . ($index + 1) . '.jpg';
            $destination = $uploadDir . $filename;
            
            if (downloadImage($imageUrl, $destination)) {
                $insert_sql = "INSERT INTO hotel_images (hotel_id, nom_image, description, type_image, ordre_affichage) VALUES (?, ?, ?, ?, ?)";
                executeQuery($insert_sql, "isssi", [
                    $hotelId,
                    $filename,
                    $descriptions[$index],
                    $imageTypes[$index],
                    $index + 1
                ]);
                
                $successCount++;
                echo "<p>✓ Image " . ($index + 1) . " téléchargée: " . $descriptions[$index] . "</p>";
            }
        }
        
        sleep(2); // Respecter les limites de l'API
    }
    
    return $successCount;
}
?>