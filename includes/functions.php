<?php
// Fonction pour nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour générer un jeton CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier la disponibilité d'une chambre spécifique
function verifierDisponibiliteChambre($chambre_id, $date_arrivee, $date_depart) {
    $sql = "SELECT COUNT(*) as count FROM reservations 
           WHERE chambre_id = ? 
           AND statut != 'annulée'
           AND (
               (date_arrivee <= ? AND date_depart > ?) OR
               (date_arrivee < ? AND date_depart >= ?) OR
               (date_arrivee >= ? AND date_depart <= ?)
           )";
    
    $params = [$chambre_id, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
    $result = fetchOne($sql, "issssss", $params);
    
    return $result['count'] == 0;
}

// Fonction pour obtenir toutes les chambres disponibles d'un type donné
function getChambresDisponiblesParType($hotel_id, $type_chambre, $date_arrivee, $date_depart, $capacite_min = 1) {
    $sql = "SELECT c.* FROM chambres c 
           WHERE c.hotel_id = ? 
           AND c.type = ? 
           AND c.capacite >= ?
           AND c.disponible = 1
           AND NOT EXISTS (
               SELECT 1 FROM reservations r 
               WHERE r.chambre_id = c.id 
               AND r.statut != 'annulée'
               AND (
                   (r.date_arrivee <= ? AND r.date_depart > ?) OR
                   (r.date_arrivee < ? AND r.date_depart >= ?) OR
                   (r.date_arrivee >= ? AND r.date_depart <= ?)
               )
           )
           ORDER BY c.numero ASC";
    
    $params = [$hotel_id, $type_chambre, $capacite_min, $date_depart, $date_arrivee, $date_arrivee, $date_depart, $date_arrivee, $date_depart];
    return fetchAll($sql, "ississssss", $params);
}
?>