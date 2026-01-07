<?php
/**

 * Déconnexion sécurisée utilisant les services SOLID

 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ==================== SERVICES ====================

require_once 'classes/init.php';

$auditLogger = ServiceContainer::getAuditLogger();
$authService = ServiceContainer::getAuthenticationService();

// ==================== LOGGING ====================

// Logger la déconnexion si l'utilisateur était authentifié
if ($authService::isAuthenticated()) {
    $userId = $authService::getAuthenticatedUserId();
    $user_email = $authService::getAuthenticatedUserEmail();
    if ($userId) {
        $auditLogger->logLogout($userId, $user_email);
    }
}

// ==================== DÉCONNEXION ====================

// Détruire la session de manière sécurisée
$authService::destroySession();

// ==================== REDIRECTION ====================

header('Location: index.php', true, 303);
exit();
?>