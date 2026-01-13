<?php

/**
 * init.php - UPDATED with EventService
 * Bootstrappe toute l'architecture SOLID
 * 
 * Utilisation:
 * require_once 'classes/init.php';
 * $authService = ServiceContainer::getAuthenticationService();
 * $eventService = ServiceContainer::getEventService();
 */


// ==================== CHARGEMENT DES CLASSES ====================


// Database
require_once __DIR__ . '/DatabaseConnection.php';


// Models
require_once __DIR__ . '/User.php';


// Security
require_once __DIR__ . '/PasswordManager.php';
require_once __DIR__ . '/AuditLogger.php';


// Services
require_once __DIR__ . '/ValidationService.php';
require_once __DIR__ . '/AuthenticationService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/VoteService.php';
require_once __DIR__ . '/EventService.php';
require_once __DIR__ . '/GameService.php';
require_once __DIR__ . '/IndexService.php';
require_once __DIR__ . '/HeaderService.php';
require_once __DIR__ . '/FooterService.php';
require_once __DIR__ . '/ResultatsService.php';
require_once __DIR__ . '/DashboardService.php';
require_once __DIR__ . '/CandidatStatisticsService.php';
require_once __DIR__ . '/CandidatProfileService.php';
require_once __DIR__ . '/CandidatEventsService.php';
require_once __DIR__ . '/AuditLogsService.php';
require_once __DIR__ . '/CandidatCampaignService.php';
require_once __DIR__ . '/CandidatEventsService.php';
require_once __DIR__ . '/AdminUserService.php';
require_once __DIR__ . '/AdminEventService.php';
require_once __DIR__ . '/AdminApplicationService.php';
require_once __DIR__ . '/AdminCandidateService.php';

// ==================== SERVICE CONTAINER (INJECTION DE DÉPENDANCES) ====================


/**
 * ServiceContainer
 * Conteneur d'injection de dépendances
 * Gère la création et le cache des services
 */
class ServiceContainer
{
    private static array $services = [];

    /**
     * Récupère l'instance DatabaseConnection (Singleton)
     */
    public static function getDatabase(): DatabaseConnection
    {
        if (!isset(self::$services['database'])) {
            self::$services['database'] = DatabaseConnection::getInstance();
        }
        return self::$services['database'];
    }

    /**
     * Récupère le AdminCandidateService (Singleton)
     */
    public static function getAdminCandidateService(): AdminCandidateService
    {
        if (!isset(self::$services['adminCandidateService'])) {
            self::$services['adminCandidateService'] = new AdminCandidateService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['adminCandidateService'];
    }


    /**
     * Récupère le AdminCategoryService (Singleton)
     */
    public static function getAdminCategoryService(): AdminCategoryService
    {
        if (!isset(self::$services['adminCategoryService'])) {
            self::$services['adminCategoryService'] = new AdminCategoryService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['adminCategoryService'];
    }

    /**
     * Récupère le AdminApplicationService (Singleton)
     */
    public static function getAdminApplicationService(): AdminApplicationService
    {
        if (!isset(self::$services['adminApplicationService'])) {
            self::$services['adminApplicationService'] = new AdminApplicationService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['adminApplicationService'];
    }

    /**
     * Récupère le CandidatCampaignService (Singleton)
     */
    public static function getCandidatCampaignService(): CandidatCampaignService
    {
        if (!isset(self::$services['candidatCampaignService'])) {
            self::$services['candidatCampaignService'] = new CandidatCampaignService(
                self::getDatabase(),
                self::getUserService(),
                self::getAuditLogger()
            );
        }
        return self::$services['candidatCampaignService'];
    }

    /**
     * Récupère le AdminUserService (Singleton)
     */
    public static function getAdminUserService(): AdminUserService
    {
        if (!isset(self::$services['adminUserService'])) {
            self::$services['adminUserService'] = new AdminUserService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['adminUserService'];
    }

    /**
     * Récupère le AdminEventService (Singleton)
     */
    public static function getAdminEventService(): AdminEventService
    {
        if (!isset(self::$services['adminEventService'])) {
            self::$services['adminEventService'] = new AdminEventService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['adminEventService'];
    }

    private static ?CandidatEventsService $candidatEventsService = null;

    public static function getCandidatEventsService(): CandidatEventsService
    {
        if (self::$candidatEventsService === null) {
            self::$candidatEventsService = new CandidatEventsService(
                self::getDatabase(),
                self::getUserService(),
                self::getAuditLogger()
            );
        }
        return self::$candidatEventsService;
    }






    public static function getDashboardService(): DashboardService
    {
        if (!isset(self::$services['dashboardService'])) {
            self::$services['dashboardService'] = new DashboardService(
                self::getDatabase(),
                self::getUserService(),
                self::getAuditLogger()
            );
        }
        return self::$services['dashboardService'];
    }

    public static function getCandidatStatisticsService(): CandidatStatisticsService
    {
        if (!isset(self::$services['candidatStatsService'])) {
            self::$services['candidatStatsService'] = new CandidatStatisticsService(
                self::getDatabase(),
                self::getUserService(),
                self::getAuditLogger()
            );
        }
        return self::$services['candidatStatsService'];
    }

    public static function getCandidatProfileService(): CandidatProfileService
    {
        if (!isset(self::$services['candidatProfileService'])) {
            self::$services['candidatProfileService'] = new CandidatProfileService(
                self::getDatabase(),
                self::getUserService(),
                self::getAuditLogger()
            );
        }
        return self::$services['candidatProfileService'];
    }



    /**
     *  Récupère le ResultatsService
     */
    public static function getResultatsService(): ResultatsService
    {
        if (!isset(self::$services['resultatsService'])) {
            self::$services['resultatsService'] = new ResultatsService(
                self::getDatabase(),
                self::getValidationService()
            );
        }
        return self::$services['resultatsService'];
    }

    /**
     *  Récupère le FooterService
     */
    public static function getFooterService(): FooterService
    {
        if (!isset(self::$services['footerService'])) {
            self::$services['footerService'] = new FooterService();
        }
        return self::$services['footerService'];
    }


    /**
     * Récupère le HeaderService
     */
    public static function getHeaderService(): HeaderService
    {
        if (!isset(self::$services['headerService'])) {
            self::$services['headerService'] = new HeaderService(
                self::getDatabase(),
                AuthenticationService::class
            );
        }
        return self::$services['headerService'];
    }

    /**
     * Récupère l'IndexService
     */
    public static function getIndexService(): IndexService
    {
        if (!isset(self::$services['indexService'])) {
            self::$services['indexService'] = new IndexService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['indexService'];
    }


    /**
     *  Récupère le GameService
     */
    public static function getGameService(): GameService
    {
        if (!isset(self::$services['gameService'])) {
            self::$services['gameService'] = new GameService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['gameService'];
    }

    /**
     * Récupère le ValidationService
     */
    public static function getValidationService(): ValidationService
    {
        return new ValidationService();
    }

    /**
     * Récupère l'AuditLogger
     */
    public static function getAuditLogger(): AuditLogger
    {
        if (!isset(self::$services['auditLogger'])) {
            self::$services['auditLogger'] = new AuditLogger(self::getDatabase());
        }
        return self::$services['auditLogger'];
    }

    /**
     * Récupère l'AuthenticationService
     */
    public static function getAuthenticationService(): AuthenticationService
    {
        if (!isset(self::$services['authService'])) {
            self::$services['authService'] = new AuthenticationService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['authService'];
    }

    /**
     * Récupère l'AuthenticationService
     */
    public static function getAuditLogsService(): AuditLogsService
    {
        if (!isset(self::$services['auditLogsService'])) {
            self::$services['auditLogsService'] = new AuditLogsService(
                self::getDatabase(),

            );
        }
        return self::$services['auditLogsService'];
    }

    /**
     * Récupère l'UserService
     */
    public static function getUserService(): UserService
    {
        if (!isset(self::$services['userService'])) {
            self::$services['userService'] = new UserService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['userService'];
    }

    /**
     * Récupère le VoteService
     */
    public static function getVoteService(): VoteService
    {
        if (!isset(self::$services['voteService'])) {
            self::$services['voteService'] = new VoteService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['voteService'];
    }

    /**
     * ✅ NOUVEAU! Récupère l'EventService
     */
    public static function getEventService(): EventService
    {
        if (!isset(self::$services['eventService'])) {
            self::$services['eventService'] = new EventService(
                self::getDatabase(),
                self::getValidationService(),
                self::getAuditLogger()
            );
        }
        return self::$services['eventService'];
    }
}


// ==================== SETUP SESSION ====================


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==================== AIDE: UTILITY HELPERS ====================


/**
 * Récupère l'ID utilisateur connecté
 */
function getAuthUserId(): ?int
{
    return AuthenticationService::getAuthenticatedUserId();
}


/**
 * Vérifie si l'utilisateur est connecté
 */
function isLogged(): bool
{
    return AuthenticationService::isAuthenticated();
}


/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin(): bool
{
    return AuthenticationService::isAdmin();
}


/**
 * Vérifie si l'utilisateur est candidat
 */
function isCandidate(): bool
{
    return AuthenticationService::isCandidate();
}


/**
 * ✅ NOUVEAU! Vérifie si l'utilisateur est joueur
 */
function isPlayer(): bool
{
    return AuthenticationService::getAuthenticatedUserType() === 'joueur';
}


/**
 * Redirect avec message
 */
function redirect(string $url, ?string $message = null, string $type = 'info'): void
{
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit;
}


/**
 * Récupère et efface le message de session
 */
function getMessage(): ?array
{
    if (isset($_SESSION['message'])) {
        $message = [
            'text' => $_SESSION['message'],
            'type' => $_SESSION['message_type'] ?? 'info'
        ];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return $message;
    }
    return null;
}
