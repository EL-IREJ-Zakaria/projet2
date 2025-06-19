<?php
require_once 'db.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour enregistrer un nouvel utilisateur
function registerUser($nom, $prenom, $email, $password, $telephone = null, $adresse = null) {
    // Vérifier si l'email existe déjà
    $user = fetchOne("SELECT id FROM clients WHERE email = ?", "s", [$email]);
    
    if ($user) {
        return ["success" => false, "message" => "Cet email est déjà utilisé"];
    }
    
    // Hachage du mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertion dans la base de données
    $sql = "INSERT INTO clients (nom, prenom, email, mot_de_passe, telephone, adresse) VALUES (?, ?, ?, ?, ?, ?)";
    $id = insertAndGetId($sql, "ssssss", [$nom, $prenom, $email, $hashed_password, $telephone, $adresse]);
    
    if ($id) {
        return ["success" => true, "user_id" => $id];
    } else {
        return ["success" => false, "message" => "Erreur lors de l'inscription"];
    }
}

// Fonction pour connecter un utilisateur
function loginUser($email, $password) {
    $user = fetchOne("SELECT id, nom, prenom, email, mot_de_passe FROM clients WHERE email = ?", "s", [$email]);
    
    if (!$user) {
        return ["success" => false, "message" => "Email ou mot de passe incorrect"];
    }
    
    if (password_verify($password, $user['mot_de_passe'])) {
        // Mise à jour de la dernière connexion
        executeQuery("UPDATE clients SET derniere_connexion = NOW() WHERE id = ?", "i", [$user['id']]);
        
        // Stockage des informations de session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = 'client';
        
        return ["success" => true, "user" => $user];
    } else {
        return ["success" => false, "message" => "Email ou mot de passe incorrect"];
    }
}

// Fonction pour connecter un administrateur
function loginAdmin($email, $password) {
    $admin = fetchOne("SELECT id, nom, prenom, email, mot_de_passe, role FROM administrateurs WHERE email = ?", "s", [$email]);
    
    if (!$admin) {
        return ["success" => false, "message" => "Email ou mot de passe incorrect"];
    }
    
    if (password_verify($password, $admin['mot_de_passe'])) {
        // Mise à jour de la dernière connexion
        executeQuery("UPDATE administrateurs SET derniere_connexion = NOW() WHERE id = ?", "i", [$admin['id']]);
        
        // Stockage des informations de session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nom'] = $admin['nom'];
        $_SESSION['admin_prenom'] = $admin['prenom'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['user_type'] = 'admin';
        
        return ["success" => true, "admin" => $admin];
    } else {
        return ["success" => false, "message" => "Email ou mot de passe incorrect"];
    }
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'administrateur est connecté
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Fonction pour déconnecter l'utilisateur
function logout() {
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Détruire la session
    session_destroy();
    
    // Rediriger vers la page d'accueil
    header("Location: /projet/index.php");
    exit;
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /projet/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Fonction pour rediriger si non admin
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: /projet/admin/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
?>