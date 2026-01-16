<?php
require_once 'classes/init.php';
date_default_timezone_set('Europe/Paris');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
$validationService = ServiceContainer::getValidationService();
$auditLogger = ServiceContainer::getAuditLogger();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'errors' => ['Méthode non autorisée. Utilisez POST.']
    ]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$errors = [];

if (empty($name)) {
    $errors[] = 'Le nom est obligatoire';
} else {
    $nameErrors = $validationService->validateText($name, 2, 100);
    $errors = array_merge($errors, $nameErrors);
}

if (empty($email)) {
    $errors[] = 'L\'email est obligatoire';
} else {
    $emailErrors = $validationService->validateEmail($email);
    $errors = array_merge($errors, $emailErrors);
}

if (empty($subject)) {
    $errors[] = 'Le sujet est obligatoire';
} else {
    $subjectErrors = $validationService->validateText($subject, 3, 200);
    $errors = array_merge($errors, $subjectErrors);
}

if (empty($message)) {
    $errors[] = 'Le message est obligatoire';
} else {
    $messageErrors = $validationService->validateText($message, 10, 5000);
    $errors = array_merge($errors, $messageErrors);
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

$name = $validationService->sanitizeHtml($name);
$email = $validationService->sanitizeEmail($email);
$subject = $validationService->sanitizeHtml($subject);
$message = $validationService->sanitizeHtml($message);
try {
    $directory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'messages';
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            throw new Exception('Impossible de créer le répertoire de messages');
        }
    }

    if (!is_writable($directory)) {
        throw new Exception('Répertoire non accessible en écriture');
    }

    $filename = $directory . DIRECTORY_SEPARATOR . 'messages.txt';
    $timestamp = date('d/m/Y à H:i:s');
    $content = "\n" . str_repeat("=", 80) . "\n";
    $content .= "Date: {$timestamp}\n";
    $content .= "Nom: {$name}\n";
    $content .= "Email: {$email}\n";
    $content .= "Sujet: {$subject}\n";
    $content .= "Message:\n{$message}\n";
    $content .= str_repeat("=", 80) . "\n";
    $bytesWritten = file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    
    if ($bytesWritten === false) {
        throw new Exception('Erreur lors de la sauvegarde du message');
    }

    if (isLogged()) {
        $auditLogger->log( 'CONTACT_MESSAGE_SENT', "Email: {$email} | Sujet: {$subject}", getAuthUserId() );
    } else {}

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '✓ Message envoyé avec succès ! Nous vous répondrons bientôt.',
        'timestamp' => $timestamp
    ]);

} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors' => ['Une erreur est survenue. Veuillez réessayer plus tard.']
    ]);
}
?>