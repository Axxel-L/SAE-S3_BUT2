<?php


/**
 * Service pour gérer les logs de sécurité
 * Refactorisation: admin-security-logs.php → logique métier centralisée
 * 
 * ✅ Principes SOLID appliqués:
 * - Single Responsibility: Gérer les logs (métier)
 * - Dependency Injection: PDO injecté via constructor
 * - Liskov Substitution: Format uniforme retourné
 * - Interface Segregation: Méthodes publiques cohérentes
 */
class AuditLogsService
{
    private $db;
    private const LOGS_PER_PAGE = 30;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * ⭐ MÉTHODE PRINCIPALE - Récupère les logs avec filtres et pagination
     * 
     * @param array $filters ['user' => int, 'action' => string, 'days' => int]
     * @param int $page Numéro de page (1-indexed)
     * @return array Format uniforme: ['logs' => array, 'total' => int, 'pages' => int, 'current_page' => int, 'error' => string|null]
     */
    public function getLogsWithFilters(array $filters = [], int $page = 1): array
    {
        try {
            // Validation
            $page = max(1, $page);
            $offset = ($page - 1) * self::LOGS_PER_PAGE;
            
            // Normaliser les filtres
            $filters = [
                'user' => intval($filters['user'] ?? 0),
                'action' => trim($filters['action'] ?? ''),
                'days' => intval($filters['days'] ?? 30)
            ];

            // Récupérer le nombre total de logs
            $countQuery = "SELECT COUNT(*) as total FROM journal_securite j WHERE 1=1";
            $countParams = [];

            if ($filters['user'] > 0) {
                $countQuery .= " AND j.id_utilisateur = ?";
                $countParams[] = $filters['user'];
            }
            if (!empty($filters['action'])) {
                $countQuery .= " AND j.action = ?";
                $countParams[] = $filters['action'];
            }
            if ($filters['days'] > 0) {
                $countQuery .= " AND j.date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $countParams[] = $filters['days'];
            }

            $stmt = $this->db->prepare($countQuery);
            $stmt->execute($countParams);
            $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Récupérer les logs filtrés et paginés
            $logsQuery = "
                SELECT j.*, u.email 
                FROM journal_securite j
                LEFT JOIN utilisateur u ON j.id_utilisateur = u.id_utilisateur
                WHERE 1=1
            ";
            $logsParams = [];

            if ($filters['user'] > 0) {
                $logsQuery .= " AND j.id_utilisateur = ?";
                $logsParams[] = $filters['user'];
            }
            if (!empty($filters['action'])) {
                $logsQuery .= " AND j.action = ?";
                $logsParams[] = $filters['action'];
            }
            if ($filters['days'] > 0) {
                $logsQuery .= " AND j.date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $logsParams[] = $filters['days'];
            }

            $logsQuery .= " ORDER BY j.date_action DESC LIMIT ? OFFSET ?";
            $logsParams[] = self::LOGS_PER_PAGE;
            $logsParams[] = $offset;

            $stmt = $this->db->prepare($logsQuery);
            $stmt->execute($logsParams);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculer le nombre de pages
            $totalPages = ceil($totalLogs / self::LOGS_PER_PAGE);

            return [
                'logs' => $logs,
                'total' => $totalLogs,
                'pages' => $totalPages,
                'current_page' => $page,
                'per_page' => self::LOGS_PER_PAGE,
                'offset' => $offset,
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'logs' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => 1,
                'per_page' => self::LOGS_PER_PAGE,
                'offset' => 0,
                'error' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère toutes les actions uniques dans les logs
     * 
     * @return array Liste des actions (string[])
     */
    public function getAvailableActions(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT action FROM journal_securite ORDER BY action");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère tous les utilisateurs pour le filtre
     * 
     * @return array Liste des utilisateurs: ['id_utilisateur' => int, 'email' => string]
     */
    public function getAvailableUsers(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur, email FROM utilisateur ORDER BY email");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les statistiques des logs
     * 
     * @return array Stats: ['total_logs' => int, 'unique_users' => int, 'unique_actions' => int]
     */
    public function getStatistics(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_logs,
                    COUNT(DISTINCT id_utilisateur) as unique_users,
                    COUNT(DISTINCT action) as unique_actions
                FROM journal_securite
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_logs' => $result['total_logs'] ?? 0,
                'unique_users' => $result['unique_users'] ?? 0,
                'unique_actions' => $result['unique_actions'] ?? 0
            ];
        } catch (Exception $e) {
            return ['total_logs' => 0, 'unique_users' => 0, 'unique_actions' => 0];
        }
    }

    /**
     * Récupère les logs d'un utilisateur spécifique
     * 
     * @param int $userId
     * @param int $limit Nombre max de logs
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les logs d'une action spécifique
     * 
     * @param string $action
     * @param int $limit
     * @return array[] Liste des logs
     */
    public function getActionLogs(string $action, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, u.email FROM journal_securite j
                LEFT JOIN utilisateur u ON j.id_utilisateur = u.id_utilisateur
                WHERE j.action = ?
                ORDER BY j.date_action DESC
                LIMIT ?
            ");
            $stmt->execute([$action, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les logs des derniers jours
     * 
     * @param int $days
     * @return array[] Liste des logs
     */
    public function getRecentLogs(int $days = 7): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, u.email FROM journal_securite j
                LEFT JOIN utilisateur u ON j.id_utilisateur = u.id_utilisateur
                WHERE j.date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY j.date_action DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Supprime les logs avant une date donnée
     * 
     * @param int $daysOld Nombre de jours (logs plus anciens que X jours)
     * @return array ['success' => bool, 'message' => string, 'deleted' => int]
     */
    public function deleteLogs(int $daysOld): array
    {
        try {
            if ($daysOld < 1) {
                return [
                    'success' => false,
                    'message' => 'Nombre de jours invalide',
                    'deleted' => 0
                ];
            }

            $stmt = $this->db->prepare("
                DELETE FROM journal_securite
                WHERE date_action < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysOld]);
            $deleted = $stmt->rowCount();

            return [
                'success' => true,
                'message' => $deleted . ' logs supprimés',
                'deleted' => $deleted
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
                'deleted' => 0
            ];
        }
    }

    /**
     * Exporte les logs filtrés au format CSV
     * 
     * @param array $filters
     * @return array ['success' => bool, 'data' => string, 'error' => string|null]
     */
    public function exportToCSV(array $filters = []): array
    {
        try {
            $filters = [
                'user' => intval($filters['user'] ?? 0),
                'action' => trim($filters['action'] ?? ''),
                'days' => intval($filters['days'] ?? 30)
            ];

            $query = "
                SELECT j.*, u.email FROM journal_securite j
                LEFT JOIN utilisateur u ON j.id_utilisateur = u.id_utilisateur
                WHERE 1=1
            ";
            $params = [];

            if ($filters['user'] > 0) {
                $query .= " AND j.id_utilisateur = ?";
                $params[] = $filters['user'];
            }
            if (!empty($filters['action'])) {
                $query .= " AND j.action = ?";
                $params[] = $filters['action'];
            }
            if ($filters['days'] > 0) {
                $query .= " AND j.date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $params[] = $filters['days'];
            }

            $query .= " ORDER BY j.date_action DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Générer le CSV
            $csv = "ID,Date,Utilisateur,Email,Action,Détails\n";
            foreach ($logs as $log) {
                $csv .= sprintf(
                    "%d,%s,%d,%s,%s,%s\n",
                    $log['id_log'] ?? '',
                    $log['date_action'] ?? '',
                    $log['id_utilisateur'] ?? '',
                    '"' . str_replace('"', '""', $log['email'] ?? '') . '"',
                    '"' . str_replace('"', '""', $log['action'] ?? '') . '"',
                    '"' . str_replace('"', '""', $log['details'] ?? '') . '"'
                );
            }

            return [
                'success' => true,
                'data' => $csv,
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => '',
                'error' => 'Erreur export : ' . $e->getMessage()
            ];
        }
    }
}
