<?php
// Gestion AJAX pour la connexion
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    include_once 'dbconnect.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';
    
    // CONNEXION
    if ($action === 'login') {
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis !']);
            exit;
        }
        
        try {
            $stmt = $connexion->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect !']);
            } else {
                $password_hash = hash('sha256', $password . $user['salt']);
                if ($password_hash === $user['mot_de_passe']) {
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['type'];
                    $_SESSION['user_date'] = $user['date_inscription'];
                    
                    echo json_encode(['success' => true, 'message' => 'Connexion réussie !']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect !']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur : ' . $e->getMessage()]);
        }
    }
    
    // INSCRIPTION
    if ($action === 'register') {
        $register_email = trim($_POST['register_email'] ?? '');
        $register_password = $_POST['register_password'] ?? '';
        $register_confirm_password = $_POST['register_confirm_password'] ?? '';
        $register_type = $_POST['register_type'] ?? 'joueur';
        
        if (empty($register_email) || empty($register_password)) {
            echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis !']);
        } elseif ($register_password !== $register_confirm_password) {
            echo json_encode(['success' => false, 'error' => 'Les mots de passe ne correspondent pas !']);
        } elseif (strlen($register_password) < 8) {
            echo json_encode(['success' => false, 'error' => 'Le mot de passe doit contenir au minimum 8 caractères !']);
        } else {
            try {
                $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                $stmt->execute([$register_email]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé !']);
                } else {
                    $salt = bin2hex(random_bytes(16));
                    $password_hash = hash('sha256', $register_password . $salt);
                    
                    $stmt = $connexion->prepare("INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) VALUES (?, ?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$register_email, $password_hash, $salt, $register_type])) {
                        echo json_encode(['success' => true, 'message' => '✓ Compte créé avec succès !']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du compte !']);
                    }
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erreur : ' . $e->getMessage()]);
            }
        }
    }
}
