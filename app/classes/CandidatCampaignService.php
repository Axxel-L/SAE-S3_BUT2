<?php



/**
 * Service pour gérer les campagnes des candidats
 * Gère les commentaires, statistiques et interactions avec les électeurs
 */
class CandidatCampaignService
{
    private DatabaseConnection $db;
    private UserService $userService;
    private AuditLogger $auditLogger;

    public function __construct(
        DatabaseConnection $db,
        UserService $userService,
        AuditLogger $auditLogger
    ) {
        $this->db = $db;
        $this->userService = $userService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Récupère toutes les données nécessaires pour la page de campagne
     */
    public function getCampaignData(int $userId): array
    {
        $candidat = $this->getCandidatInfo($userId);
        $commentaires = $candidat ? $this->getCommentaires($userId, $candidat['id_jeu']) : [];
        $stats = $candidat ? $this->getStatistics($userId, $candidat['id_jeu']) : [];

        return [
            'candidat' => $candidat,
            'commentaires' => $commentaires,
            'stats' => $stats,
            'error' => '',
            'events' => [] // Peut être étendu plus tard
        ];
    }

    /**
     * Récupère les informations du candidat et son jeu
     */
    private function getCandidatInfo(int $userId): ?array
    {
        try {
            $query = "
                SELECT c.*, 
                       j.id_jeu, 
                       j.titre as jeu_titre, 
                       j.image as jeu_image, 
                       j.editeur, 
                       j.description as jeu_description
                FROM candidat c
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE c.id_utilisateur = ?
            ";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->auditLogger->log( 'ERROR_GET_CANDIDAT', 'Erreur: ' . $e->getMessage(),$userId);
            return null;
        }
    }

    /**
     * Ajoute un commentaire sur la campagne
     */
    public function addComment(int $userId, string $content): array
    {
        // Validation
        $content = htmlspecialchars(trim($content), ENT_QUOTES, 'UTF-8');

        if (empty($content)) {
            return ['success' => false, 'message' => 'Veuillez écrire un message !'];
        }

        if (strlen($content) < 3) {
            return ['success' => false, 'message' => 'Message trop court (min 3 caractères) !'];
        }

        if (strlen($content) > 1000) {
            return ['success' => false, 'message' => 'Message trop long (max 1000 caractères) !'];
        }

        // Récupérer le jeu du candidat
        $candidat = $this->getCandidatInfo($userId);
        if (!$candidat || empty($candidat['id_jeu'])) {
            return ['success' => false, 'message' => 'Vous devez d\'abord sélectionner un jeu.'];
        }

        try {
            // Insérer le commentaire
            $query = "
                INSERT INTO commentaire (id_utilisateur, id_jeu, contenu, date_commentaire)
                VALUES (?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$userId, $candidat['id_jeu'], $content]);

            // Log audit
            $this->auditLogger->log(
                
                'CAMPAGNE_COMMENT_ADD',
                'Jeu: ' . $candidat['id_jeu'] . ' | Contenu: ' . substr($content, 0, 50),
                $userId
            );

            return ['success' => true, 'message' => 'Message publié !'];
        } catch (\Exception $e) {
            $this->auditLogger->log( 'ERROR_ADD_COMMENT', 'Erreur: ' . $e->getMessage(),$userId);
            return ['success' => false, 'message' => 'Erreur lors de la publication.'];
        }
    }

    /**
     * Supprime un commentaire
     */
    public function deleteComment(int $userId, int $commentId): array
    {
        try {
            // Vérifier que le commentaire appartient à l'utilisateur
            $query = "DELETE FROM commentaire WHERE id_commentaire = ? AND id_utilisateur = ?";
            
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$commentId, $userId]);

            if ($stmt->rowCount() > 0) {
                $this->auditLogger->log( 'CAMPAGNE_COMMENT_DELETE', 'Commentaire: ' . $commentId,$userId);
                return ['success' => true, 'message' => 'Message supprimé !'];
            }

            return ['success' => false, 'message' => 'Commentaire non trouvé.'];
        } catch (\Exception $e) {
            $this->auditLogger->log( 'ERROR_DELETE_COMMENT', 'Erreur: ' . $e->getMessage(),$userId);
            return ['success' => false, 'message' => 'Erreur lors de la suppression.'];
        }
    }

    /**
     * Récupère tous les commentaires du jeu du candidat
     */
    private function getCommentaires(int $userId, int $gameId): array
    {
        try {
            $query = "
                SELECT
                    com.id_commentaire,
                    com.contenu,
                    com.date_commentaire,
                    u.id_utilisateur,
                    u.pseudo,
                    u.type,
                    CASE WHEN u.id_utilisateur = ? THEN 1 ELSE 0 END as is_mine
                FROM commentaire com
                JOIN utilisateur u ON com.id_utilisateur = u.id_utilisateur
                WHERE com.id_jeu = ?
                ORDER BY com.date_commentaire DESC
                LIMIT 100
            ";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$userId, $gameId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $this->auditLogger->log( 'ERROR_GET_COMMENTAIRES', 'Erreur: ' . $e->getMessage(),$userId);
            return [];
        }
    }

    /**
     * Récupère les statistiques de campagne
     */
    private function getStatistics(int $userId, int $gameId): array
    {
        $stats = [
            'nb_votes_cat' => 0,
            'nb_votes_final' => 0,
            'nb_comments' => 0,
            'my_comments' => 0
        ];

        try {
            $query = "SELECT COUNT(*) FROM bulletin_categorie WHERE id_jeu = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$gameId]);
            $stats['nb_votes_cat'] = (int)$stmt->fetchColumn();

            $query = "SELECT COUNT(*) FROM bulletin_final WHERE id_jeu = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$gameId]);
            $stats['nb_votes_final'] = (int)$stmt->fetchColumn();

            $query = "SELECT COUNT(*) FROM commentaire WHERE id_jeu = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$gameId]);
            $stats['nb_comments'] = (int)$stmt->fetchColumn();

            $query = "SELECT COUNT(*) FROM commentaire WHERE id_jeu = ? AND id_utilisateur = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$gameId, $userId]);
            $stats['my_comments'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            $this->auditLogger->log('ERROR_GET_STATS', 'Erreur: ' . $e->getMessage(),$userId);
        }

        return $stats;
    }

    /**
     * Configuration des statuts de candidature (utilisation cohérente avec CandidatEventsService)
     * 
     * @param string $statut
     * @return array
     */
    public static function getStatutConfig(string $statut = ''): array
    {
        $config = [
            'en_attente' => [
                'color' => 'yellow',
                'label' => 'En attente',
                'icon' => 'fa-hourglass-end',
                'class' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30'
            ],
            'accepte' => [
                'color' => 'green',
                'label' => 'Accepté',
                'icon' => 'fa-check-circle',
                'class' => 'bg-green-500/10 text-green-400 border-green-500/30'
            ],
            'rejete' => [
                'color' => 'red',
                'label' => 'Rejeté',
                'icon' => 'fa-times-circle',
                'class' => 'bg-red-500/10 text-red-400 border-red-500/30'
            ],
        ];

        if ($statut && isset($config[$statut])) {
            return $config[$statut];
        }

        return $config;
    }
}
