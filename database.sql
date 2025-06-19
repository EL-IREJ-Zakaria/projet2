CREATE DATABASE IF NOT EXISTS hotel_reservation;
USE hotel_reservation;

-- Table clients
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME
);

-- Table administrateurs
CREATE TABLE IF NOT EXISTS administrateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME
);

-- Table hotels
CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    ville VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    description TEXT,
    etoiles INT NOT NULL,
    image_principale VARCHAR(255),
    coordonnees_gps VARCHAR(100),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table chambres
CREATE TABLE IF NOT EXISTS chambres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    prix_nuit DECIMAL(10,2) NOT NULL,
    capacite INT NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    image VARCHAR(255),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Table reservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    chambre_id INT NOT NULL,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    statut ENUM('confirmée', 'en attente', 'annulée') DEFAULT 'en attente',
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (chambre_id) REFERENCES chambres(id) ON DELETE CASCADE
);

-- Insertion d'un administrateur par défaut (mot de passe: admin123)
INSERT INTO administrateurs (nom, prenom, email, mot_de_passe, role) 
VALUES ('Admin', 'System', 'admin@luxuryhotels.com', '$2y$10$8tGIx5g5hWD4YZtJRyAE3uUm6IM8kUGsC1VCRS/Xnm2OYWnFPzwIi', 'super_admin');

-- Insertion de quelques hôtels de démonstration
INSERT INTO hotels (nom, adresse, ville, pays, description, etoiles, image_principale) VALUES
('Le Grand Palais', '15 Avenue des Champs-Élysées', 'Paris', 'France', 'Un hôtel luxueux au cœur de Paris avec vue sur la Tour Eiffel.', 5, 'hotel_paris.jpg'),
('Riviera Luxury', '7 Promenade des Anglais', 'Nice', 'France', 'Profitez de la vue imprenable sur la Méditerranée dans cet établissement d\'exception.', 5, 'hotel_nice.jpg'),
('Château Royal', '25 Boulevard de la Croisette', 'Cannes', 'France', 'Le summum du luxe sur la Croisette, à deux pas du Palais des Festivals.', 5, 'hotel_cannes.jpg'),
('Lyon Palace', '10 Place Bellecour', 'Lyon', 'France', 'Élégance et raffinement au cœur de la capitale gastronomique.', 4, 'hotel_lyon.jpg'),
('Bordeaux Vineyard', '5 Quai des Chartrons', 'Bordeaux', 'France', 'Un havre de paix au milieu des vignobles bordelais.', 4, 'hotel_bordeaux.jpg');

-- Insertion de chambres pour chaque hôtel
INSERT INTO chambres (hotel_id, numero, type, description, prix_nuit, capacite, image) VALUES
(1, '101', 'Suite Présidentielle', 'Une suite somptueuse avec jacuzzi privatif et vue panoramique.', 950.00, 2, 'suite_presidentielle.jpg'),
(1, '102', 'Suite Deluxe', 'Espace et confort dans un cadre raffiné.', 750.00, 2, 'suite_deluxe.jpg'),
(1, '103', 'Chambre Supérieure', 'Élégance et confort pour un séjour inoubliable.', 450.00, 2, 'chambre_superieure.jpg'),
(2, '201', 'Suite Royale', 'Le summum du luxe avec terrasse privée vue mer.', 1200.00, 2, 'suite_royale.jpg'),
(2, '202', 'Suite Junior', 'Confort moderne dans un cadre élégant.', 650.00, 2, 'suite_junior.jpg'),
(3, '301', 'Penthouse', 'Le must du luxe avec piscine privée sur le toit.', 1500.00, 4, 'penthouse.jpg'),
(3, '302', 'Suite Familiale', 'Espace et confort pour toute la famille.', 850.00, 4, 'suite_familiale.jpg'),
(4, '401', 'Suite Exécutive', 'Idéale pour les voyageurs d\'affaires exigeants.', 550.00, 2, 'suite_executive.jpg'),
(5, '501', 'Suite Vineyard', 'Vue imprenable sur les vignobles depuis votre balcon privé.', 750.00, 2, 'suite_vineyard.jpg');