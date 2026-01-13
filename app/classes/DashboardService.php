<?php
/**
 * Gère la logique métier du tableau de bord utilisateur
 */
class DashboardService
{
     private $db;
    private UserService $userService;
    private AuditLogger $auditLogger;
    public function __construct(
        $db,
        UserService $userService,
        AuditLogger $auditLogger
    ) {
        $this->db = $db;
        $this->userService = $userService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Récupère les données du dashboard
     */
    public function getUserDashboardData(int $userId): array
    {
        try {
            return [
                'user' => $this->getUserById($userId),
                'events' => $this->getUserEvents($userId),
                'votes' => $this->getUserVoteHistory($userId),
                'voteStatus' => $this->getVoteStatusPerEvent($userId),
                'statistics' => $this->getUserStatistics($userId),
                'error' => null
            ];
        } catch (\Exception $e) {
            error_log("DashboardService Error: " . $e->getMessage());
            return [
                'user' => null,
                'events' => [],
                'votes' => [],
                'voteStatus' => [],
                'statistics' => ['total_events' => 0, 'total_votes' => 0],
                'error' => 'Erreur lors du chargement des données'
            ];
        }
    }

    /**
     * Récupère les informations de l'utilisateur
     */
    private function getUserById(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id_utilisateur, email, pseudo, type, date_inscription
                FROM utilisateur 
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("getUserById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les événements auxquels l'utilisateur est inscrit
     */
    private function getUserEvents(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.id_evenement,
                    e.nom,
                    e.date_ouverture,
                    e.date_fermeture,
                    r.date_inscription
                FROM evenement e
                JOIN registre_electoral r ON e.id_evenement = r.id_evenement
                WHERE r.id_utilisateur = ?
                ORDER BY e.date_ouverture DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log("getUserEvents Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère l'historique des votes de l'utilisateur
     */
    private function getUserVoteHistory(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 'categorie' as type, j.titre, c.nom as categorie, e.nom as evenement, 
                       ec.date_emargement as datevote
                FROM emargement_categorie ec
                JOIN categorie c ON ec.id_categorie = c.id_categorie
                JOIN evenement e ON ec.id_evenement = e.id_evenement
                LEFT JOIN jeu j ON j.id_jeu IN (
                    SELECT id_jeu FROM bulletin_categorie 
                    WHERE id_evenement = ec.id_evenement 
                    LIMIT 1
                )
                WHERE ec.id_utilisateur = ?
                
                UNION ALL
                
                SELECT 'final' as type, j.titre, 'Finale' as categorie, e.nom as evenement,
                       ef.date_emargement as datevote
                FROM emargement_final ef
                JOIN evenement e ON ef.id_evenement = e.id_evenement
                LEFT JOIN bulletin_final bf ON bf.id_evenement = ef.id_evenement
                LEFT JOIN jeu j ON j.id_jeu = bf.id_jeu
                WHERE ef.id_utilisateur = ?
                
                ORDER BY datevote DESC
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log("getUserVoteHistory Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le statut de vote par événement
     */
    private function getVoteStatusPerEvent(int $userId): array
    {
        $voteStatus = [];
        
        try {
            $events = $this->getUserEvents($userId);
            foreach ($events as $event) {
                $status = [];
                $stmt = $this->db->prepare("
                    SELECT COUNT(DISTINCT c.id_categorie) as total_categories,
                           COUNT(DISTINCT ec.id_categorie) as voted_categories
                    FROM categorie c
                    LEFT JOIN emargement_categorie ec ON c.id_categorie = ec.id_categorie 
                        AND ec.id_utilisateur = ? AND ec.id_evenement = ?
                    WHERE c.id_evenement = ?
                ");
                $stmt->execute([$userId, $event['id_evenement'], $event['id_evenement']]);
                $catResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $status['categories'] = $catResult;
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as has_voted_final
                    FROM emargement_final
                    WHERE id_utilisateur = ? AND id_evenement = ?
                ");
                $stmt->execute([$userId, $event['id_evenement']]);
                $finalResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $status['final'] = $finalResult['has_voted_final'] > 0;
                
                $voteStatus[$event['id_evenement']] = $status;
            }
        } catch (\Exception $e) {
            error_log("getVoteStatusPerEvent Error: " . $e->getMessage());
        }
        return $voteStatus;
    }

    /**
     * Récupère les statistiques de l'utilisateur
     */
    public function getUserStatistics(int $userId): array
    {
        try {
            $events = $this->getUserEvents($userId);
            $votes = $this->getUserVoteHistory($userId);
            return [
                'total_events' => count($events),
                'total_votes' => count($votes)
            ];
        } catch (\Exception $e) {
            return [
                'total_events' => 0,
                'total_votes' => 0
            ];
        }
    }

    /**
     * Vérifie si un événement est ouvert au vote
     * Méthode statique car pas besoin du contexte
     */
    public static function isEventOpen(array $event): bool
    {
        try {
            $now = new \DateTime();
            $ouverture = new \DateTime($event['date_ouverture']);
            $fermeture = new \DateTime($event['date_fermeture']);
            return $now >= $ouverture && $now <= $fermeture;
        } catch (\Exception $e) {
            return false;
        }
    }
}
