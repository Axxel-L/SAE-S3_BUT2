<?php
/**
 * Gère les statistiques des candidats
 */
class CandidatStatisticsService
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
     * Récupère les stats du candidat
     */
    public function getCandidatStatistics(int $userId): array
    {
        try {
            $candidat = $this->getCandidatInfo($userId);
            if (!$candidat) {
                return [
                    'candidat' => null,
                    'stats' => [],
                    'error' => 'Candidat non trouvé'
                ];
            }
            return [
                'candidat' => $candidat,
                'stats' => $this->getGameStatistics($candidat['id_jeu'] ?? null),
                'error' => null
            ];
        } catch (\Exception $e) {
            error_log("CandidatStatisticsService Error: " . $e->getMessage());
            return [
                'candidat' => null,
                'stats' => [],
                'error' => 'Erreur lors du chargement des données'
            ];
        }
    }

    /**
     * Récupère les informations du candidat
     */
    private function getCandidatInfo(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, j.titre as titre_jeu 
                FROM candidat c 
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE c.id_utilisateur = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("getCandidatInfo Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques du jeu
     */
    private function getGameStatistics(?int $idJeu): array
    {
        $stats = [
            'commentaires' => 0,
            'votes_categorie' => 0,
            'votes_final' => 0,
            'votes_total' => 0,
            'derniers_commentaires' => []
        ];
        if (!$idJeu) {
            return $stats;
        }
        try {
            $stats['commentaires'] = $this->getCommentairesCount($idJeu);
            $stats['votes_categorie'] = $this->getVotesCategorieCount($idJeu);
            $stats['votes_final'] = $this->getVotesFinalCount($idJeu);
            $stats['votes_total'] = $stats['votes_categorie'] + $stats['votes_final'];
            $stats['derniers_commentaires'] = $this->getDerniersCommentaires($idJeu);
        } catch (\Exception $e) {
            error_log("getGameStatistics Error: " . $e->getMessage());
        }
        return $stats;
    }

    /**
     * Compte les commentaires
     */
    private function getCommentairesCount(int $idJeu): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM commentaire WHERE id_jeu = ?
            ");
            $stmt->execute([$idJeu]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log("getCommentairesCount Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compte les votes catégories
     */
    private function getVotesCategorieCount(int $idJeu): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_jeu = ?
            ");
            $stmt->execute([$idJeu]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log("getVotesCategorieCount Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compte les votes finaux
     */
    private function getVotesFinalCount(int $idJeu): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM bulletin_final WHERE id_jeu = ?
            ");
            $stmt->execute([$idJeu]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log("getVotesFinalCount Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les 10 derniers commentaires
     */
    private function getDerniersCommentaires(int $idJeu): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.email, date_format(c.date_commentaire, '%d/%m/%Y %H:%i') as date_format
                FROM commentaire c 
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
                WHERE c.id_jeu = ? 
                ORDER BY c.date_commentaire DESC 
                LIMIT 10
            ");
            $stmt->execute([$idJeu]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log("getDerniersCommentaires Error: " . $e->getMessage());
            return [];
        }
    }
}
