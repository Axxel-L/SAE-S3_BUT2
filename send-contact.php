<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $errors = [];
    
    // Vérifications
    if (empty($name)) $errors[] = "Le nom est obligatoire";
    if (empty($email)) $errors[] = "L'email est obligatoire";
    if (empty($subject)) $errors[] = "Le sujet est obligatoire";
    if (empty($message)) $errors[] = "Le message est obligatoire";
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // ✅ SOLUTION : Utiliser DIRECTORY_SEPARATOR
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'messages';
    
    // Créer le dossier automatiquement
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $filename = $directory . DIRECTORY_SEPARATOR . 'messages.txt';
    
    // Contenu du message
    $content = "\n" . str_repeat("=", 80) . "\n";
    $content .= "Date: " . date('d/m/Y à H:i:s') . "\n";
    $content .= "Nom: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "\n";
    $content .= "Email: " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "\n";
    $content .= "Sujet: " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "\n";
    $content .= "Message: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "\n";
    $content .= str_repeat("=", 80) . "\n";
    
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
