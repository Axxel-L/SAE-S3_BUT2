<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
$auditLogger = ServiceContainer::getAuditLogger();
$authService = ServiceContainer::getAuthenticationService();

if ($authService::isAuthenticated()) {
    $userId = $authService::getAuthenticatedUserId();
    $user_email = $authService::getAuthenticatedUserEmail();
    if ($userId) {
        $auditLogger->logLogout($userId, $user_email);
    }
}

$authService::destroySession();
header('Location: index.php', true, 303);
exit();
?>