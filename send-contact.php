<?php
// On fait un système de contact qui enregistre les messages dans un fichier texte pour le moment
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $errors = [];
    
    // Vérification
    if (empty($name)) $errors[] = "Le nom est obligatoire";
    if (empty($email)) $errors[] = "L'email est obligatoire";
    if (empty($subject)) $errors[] = "Le sujet est obligatoire";
    if (empty($message)) $errors[] = "Le message est obligatoire";
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    // Si erreur on arrête
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $filename = './data/messages/msg.txt';
    
    // Contenu du message
    $content = "Date: " . date('d/m/Y à H:i:s') . "\n";
    $content .= "Nom: " . htmlspecialchars($name) . "\n";
    $content .= "Email: " . htmlspecialchars($email) . "\n";
    $content .= "Sujet: " . htmlspecialchars($subject) . "\n";
    $content .= "Message: " . htmlspecialchars($message) . "\n";
    
    if (file_put_contents($filename, $content, FILE_APPEND | LOCK_EX) !== false) {
        echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès !']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Erreur lors de la sauvegarde']]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Méthode non autorisée']]);
}
?>