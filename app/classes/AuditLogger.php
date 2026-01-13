<?php
/**
 * Système d'audit centralisé
 */
class AuditLogger
{
    private DatabaseConnection $db;
    public function __construct(DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /** Enregistre une inscription utilisateur
     * @param int $userId 
     * @param string $userType Type d'utilisateur
     * @return bool Succès 
     */
    public function logUserRegistration(int $userId, string $userType = 'joueur'): bool
    {
        $details = "Type: $userType | Nouvelle inscription";
        return $this->log('USER_REGISTRATION', $details, $userId);
    }

    /**
     * Enregistre un vote en catégorie
     * @param int $userId
     * @param int $categoryId ID de la catégorie
     * @param int $eventId ID de l'événement
     * @return bool Succès
     */
    public function logCategoryVote(
        int $userId,
        int $categoryId,
        int $eventId
    ): bool {
        $details = "Catégorie: $categoryId | Événement: $eventId";
        return $this->log('VOTE_CATEGORY', $details, $userId);
    }

    /**
     * Enregistre un vote final
     * @param int $userId
     * @param int $gameId ID du jeu voté
     * @param int $eventId ID de l'événement
     * @return bool Succès
     */
    public function logFinalVote(
        int $userId,
        int $gameId,
        int $eventId
    ): bool {
        $details = "Jeu: $gameId | Événement: $eventId";
        return $this->log('VOTE_FINAL', $details, $userId);
    }

    /**
     * Enregistre l'inscription d'un candidat
     * @param int $userId
     * @param string $nom Nom/prénom du candidat
     * @param int $gameId ID du jeu choisi
     * @return bool Succès
     */
    public function logCandidateRegistration(
        int $userId,
        string $nom,
        int $gameId
    ): bool {
        $details = "Nom: $nom | Jeu: $gameId | Inscription candidat";
        return $this->log('CANDIDATE_REGISTRATION', $details, $userId);
    }


    /**
     * Base pour tout logging
     * @param string $action Type d'action (LOGIN, LOGOUT, UPDATE, etc.)
     * @param string $details Contexte optionnel
     * @param int|null $userId ID utilisateur (optionnel)
     * @return bool Succès
     */
    public function log(
        string $action,
        string $details = '',
        ?int $userId = null
    ): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO journal_securite
                (id_utilisateur, action, details, date_action)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                strtoupper($action),
                !empty($details) ? $details : null
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("AuditLogger::log() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre une connexion réussie
     * @param int $userId
     * @param string|null $email (optionnel)
     * @param string|null $userType Type d'utilisateur (optionnel)
     * @return bool Succès
     */
    public function logLoginSuccess(
        int $userId,
        ?string $email = null,
        ?string $userType = null
    ): bool {
        $details = "User ID: $userId";
        if (!empty($email)) {
            $details = "Email: $email";
        }
        if (!empty($userType)) {
            $details .= " | Type: $userType";
        }
        return $this->log('LOGIN_SUCCESS', $details, $userId);
    }

    /**
     * Enregistre un échec de connexion
     * @param string $email Email qui a échoué
     * @param string $reason Raison de l'échec (optionnel)
     * @return bool Succès
     */
    public function logLoginFailure(
        string $email,
        string $reason = 'Bad credentials'
    ): bool {
        $details = "Email: $email | Raison: $reason";
        return $this->log('LOGIN_FAILURE', $details);
    }

    /**
     * Enregistre une déconnexion
     * @param int $userId
     * @param string $email (optionnel)
     * @return bool Succès
     */
    public function logLogout(
        int $userId,
        string $email = ''
    ): bool {
        $details = !empty($email) ? "Email: $email" : '';
        return $this->log('LOGOUT', $details, $userId);
    }

    /**
     * Enregistre une action liée à un candidat
     * @param int $candidatId ID du candidat
     * @param string $action Type d'action
     * @param string $details Contexte optionnel
     * @return bool Succès
     */
    public function logCandidatAction(
        int $candidatId,
        string $action,
        string $details = ''
    ): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT id_utilisateur FROM candidat WHERE id_candidat = ?
            ");
            $stmt->execute([$candidatId]);
            $candidat = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$candidat) {
                error_log("AuditLogger::logCandidatAction() - Candidat non trouvé: $candidatId");
                return false;
            }
            $fullDetails = "Candidat: $candidatId";
            if (!empty($details)) {
                $fullDetails .= " | $details";
            }
            return $this->log($action, $fullDetails, $candidat['id_utilisateur']);
        } catch (\Exception $e) {
            error_log("AuditLogger::logCandidatAction() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre une erreur avec contexte
     * @param string $action Contexte (quelle action a échoué)
     * @param \Exception $exception L'exception
     * @param int|null $userId ID utilisateur
     * @return bool Succès
     */
    public function logError(
        string $action,
        \Exception $exception,
        ?int $userId = null
    ): bool {
        $details = sprintf(
            "ERROR in %s | Message: %s | File: %s:%d",
            $action,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        return $this->log('ERROR_' . $action, $details, $userId);
    }

    /**
     * Enregistre une actions critiques
     * @param string $action Type d'action sécurité
     * @param string $reason Raison
     * @param int|null $userId ID utilisateur
     * @return bool Succès
     */
    public function logSecurityEvent(
        string $action,
        string $reason,
        ?int $userId = null
    ): bool {
        $details = "SECURITY EVENT: $reason";
        return $this->log('SECURITY_' . $action, $details, $userId);
    }

    /**
     * Enregistre un accès refusé
     * @param int|null $userId ID utilisateur
     * @param string $resource Ressource demandée
     * @param string $reason Raison du refus
     * @return bool Succès
     */
    public function logAccessDenied(
        ?int $userId,
        string $resource,
        string $reason = 'Unauthorized'
    ): bool {
        $details = "Resource: $resource | Reason: $reason";
        return $this->log('ACCESS_DENIED', $details, $userId);
    }

    /**
     * Enregistre une suppression
     * @param int $userId
     * @param string $entityType Type d'entité supprimée
     * @param int $entityId ID de l'entité
     * @return bool Succès
     */
    public function logDataDelete(
        int $userId,
        string $entityType,
        int $entityId
    ): bool {
        $details = "$entityType #$entityId supprimé";
        return $this->log('DATA_DELETE', $details, $userId);
    }

    /**
     * Récupère les logs d'un utilisateur
     * @param int $userId
     * @param int $limit Nombre de logs max
     * @return array[] Liste des logs
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM journal_securite
                WHERE id_utilisateur = ?
                ORDER BY date_action DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("getUserLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les logs d'une action spécifique
     * @param string $action Type d'action
     * @param int $limit
     * @return array[] Liste des logs
     */
    public function getActionLogs(string $action, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM journal_securite
                WHERE action = ?
                ORDER BY date_action DESC
                LIMIT ?
            ");
            $stmt->execute([strtoupper($action), $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("getActionLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les logs récents
     * @param int $limit
     * @return array[] Liste des logs
     */
    public function getRecentLogs(int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM journal_securite
                ORDER BY date_action DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("getRecentLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les logs de sécurité
     * @param int $days Nombre de jours (défaut 7)
     * @return array[] Liste des logs sécurité
     */
    public function getSecurityLogs(int $days = 7): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM journal_securite
                WHERE (action LIKE 'SECURITY_%' OR action LIKE 'LOGIN_FAILURE' OR action LIKE 'ACCESS_DENIED')
                AND date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY date_action DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("getSecurityLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les logs d'erreurs
     * @param int $limit
     * @return array[] Liste des erreurs
     */
    public function getErrorLogs(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM journal_securite
                WHERE action LIKE 'ERROR_%'
                ORDER BY date_action DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("getErrorLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Nettoie les vieux logs 
     * @param int $daysOld Nombre de jours
     * @return int Nombre de logs supprimés
     */
    public function cleanOldLogs(int $daysOld = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM journal_securite
                WHERE date_action < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysOld]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("cleanOldLogs Error: " . $e->getMessage());
            return 0;
        }
    }
}
