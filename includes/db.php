<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // À modifier en production
define('DB_PASS', ''); // À modifier en production
define('DB_NAME', 'hotel_reservation1');

// Fonction de connexion à la base de données
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Vérification de la connexion
    if ($conn->connect_error) {
        die("Échec de connexion à la base de données: " . $conn->connect_error);
    }
    
    // Définir l'encodage UTF-8
    $conn->set_charset("utf8");
    
    return $conn;
}

// Fonction pour exécuter des requêtes préparées
function executeQuery($sql, $types = null, $params = []) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Fonction pour obtenir une seule ligne
function fetchOne($sql, $types = null, $params = []) {
    $result = executeQuery($sql, $types, $params);
    return $result->fetch_assoc();
}

// Fonction pour obtenir plusieurs lignes
function fetchAll($sql, $types = null, $params = []) {
    $result = executeQuery($sql, $types, $params);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fonction pour insérer des données et récupérer l'ID
function insertAndGetId($sql, $types, $params) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erreur de préparation de la requête: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $id;
}
?>