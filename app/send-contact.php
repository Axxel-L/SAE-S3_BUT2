<?php
/**
 * send-contact.php - REFACTORISÉ avec Architecture SOLID
 * 
 * API REST pour traiter les messages de contact
 * 
 * Nouvelles features:
 * - Validation centralisée via ValidationService
 * - Journalisation d'audit via AuditLogger
 * - Gestion des erreurs structurée
 * - Response JSON propre
 * - Injection de dépendances
 * 
 * SOLID principles:
 * - S: Chaque service a UNE responsabilité
 * - O: Facile d'ajouter de nouveaux validateurs
 * - L: Services substitutables
 * - I: Services exposent méthodes spécifiques
 * - D: Services injectés via ServiceContainer
 */

require_once 'classes/init.php';

// ==================== SETUP ====================

date_default_timezone_set('Europe/Paris');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

// ==================== SERVICES ====================

$validationService = ServiceContainer::getValidationService();
$auditLogger = ServiceContainer::getAuditLogger();

// ==================== TRAITEMENT ====================

// Seulement POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'errors' => ['Méthode non autorisée. Utilisez POST.']
    ]);
    exit;
}

// ==================== RÉCUPÉRATION & SANITIZATION ====================

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// ==================== VALIDATION ====================

$errors = [];

// Valider chaque champ
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

// Si erreurs, retourner
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// ==================== SANITIZATION ====================

$name = $validationService->sanitizeHtml($name);
$email = $validationService->sanitizeEmail($email);
$subject = $validationService->sanitizeHtml($subject);
$message = $validationService->sanitizeHtml($message);

// ==================== SAUVEGARDE ====================

try {
    // Créer le répertoire data/messages s'il n'existe pas
    $directory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'messages';
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            throw new Exception('Impossible de créer le répertoire de messages');
        }
    }

    // Vérifier les permissions en écriture
    if (!is_writable($directory)) {
        throw new Exception('Répertoire non accessible en écriture');
    }

    // Composer le contenu du message
    $filename = $directory . DIRECTORY_SEPARATOR . 'messages.txt';
    $timestamp = date('d/m/Y à H:i:s');
    
    $content = "\n" . str_repeat("=", 80) . "\n";
    $content .= "Date: {$timestamp}\n";
    $content .= "Nom: {$name}\n";
    $content .= "Email: {$email}\n";
    $content .= "Sujet: {$subject}\n";
    $content .= "Message:\n{$message}\n";
    $content .= str_repeat("=", 80) . "\n";
    
    // Sauvegarder le message
    $bytesWritten = file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    
    if ($bytesWritten === false) {
        throw new Exception('Erreur lors de la sauvegarde du message');
    }

    // Log audit (si utilisateur authentifié)
    if (isLogged()) {
        $auditLogger->log( 'CONTACT_MESSAGE_SENT', "Email: {$email} | Sujet: {$subject}", getAuthUserId() );
    } else {
        // Optionnel: log pour les non-authentifiés
        // File::append('contact_messages.log', "{$timestamp} | {$email} | {$subject}");
    }

    // Réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '✓ Message envoyé avec succès ! Nous vous répondrons bientôt.',
        'timestamp' => $timestamp
    ]);

} catch (Exception $e) {
    // Log erreur
    error_log("Contact form error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors' => ['Une erreur est survenue. Veuillez réessayer plus tard.']
    ]);
}

?>