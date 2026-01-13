<?php
/**
 * GameService.php
 * 
 * Gère la logique métier des jeux
 * - Récupération des jeux
 * - Gestion des commentaires
 * - Statistiques des jeux
 * 
 * Single Responsibility: Logique métier jeux
 */





class GameService {
    
    private $db;
    private $validator;
    private $auditLogger;
    
    public function __construct(
        $db,
        $validator,
        $auditLogger
    ) {
        $this->db = $db;
        $this->validator = $validator;
        $this->auditLogger = $auditLogger;
    }
    
    /**
     * Récupère les détails complets d'un jeu avec candidat
     * 
     * @param int $gameId ID du jeu
     * @return array|null
     */
    public function getGameWithCandidat(int $gameId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, 
                       c.id_candidat, c.nom as candidat_nom, c.bio as candidat_bio, c.photo as candidat_photo,
                       u.id_utilisateur as candidat_user_id, u.pseudo as candidat_pseudo
                FROM jeu j
                LEFT JOIN candidat c ON j.id_jeu = c.id_jeu AND c.statut = 'valide'
                LEFT JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                WHERE j.id_jeu = ?
            ");
            $stmt->execute([$gameId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("GameService Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajoute un commentaire à un jeu
     * 
     * @param int $userId ID utilisateur
     * @param int $gameId ID jeu
     * @param string $content Contenu du commentaire
     * @return array ['success' => bool, 'errors' => []]
     */
    public function addComment(int $userId, int $gameId, string $content): array {
        $errors = [];
        
        // Validation
        $content = htmlspecialchars(trim($content), ENT_QUOTES, 'UTF-8');
        
        if (empty($content) || strlen($content) < 3) {
            $errors[] = "Le commentaire doit contenir au moins 3 caractères !";
        } elseif (strlen($content) > 1000) {
            $errors[] = "Le commentaire est trop long (max 1000 caractères) !";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Vérifier le jeu existe
            $stmt = $this->db->prepare("SELECT id_jeu FROM jeu WHERE id_jeu = ?");
            $stmt->execute([$gameId]);
            if (!$stmt->fetch()) {
                throw new Exception("Jeu non trouvé");
            }
            
            // Insérer le commentaire
            $stmt = $this->db->prepare("
                INSERT INTO commentaire (id_utilisateur, id_jeu, contenu, date_commentaire) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $gameId, $content]);
            
            // Logger
            $this->auditLogger->logAction(
                $userId,
                'COMMENT_ADD',
                "Jeu: $gameId"
            );
            
            $this->db->commit();
            
            return ['success' => true, 'errors' => []];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("GameService addComment Error: " . $e->getMessage());
            return ['success' => false, 'errors' => ["Erreur lors de la publication."]];
        }
    }
    
    /**
     * Récupère tous les commentaires d'un jeu
     * 
     * @param int $gameId ID jeu
     * @return array
     */
    public function getComments(int $gameId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    com.id_commentaire,
                    com.contenu,
                    com.date_commentaire,
                    u.id_utilisateur,
                    u.pseudo,
                    u.type,
                    CASE WHEN c.id_candidat IS NOT NULL AND c.id_jeu = ? THEN 1 ELSE 0 END as is_owner
                FROM commentaire com
                JOIN utilisateur u ON com.id_utilisateur = u.id_utilisateur
                LEFT JOIN candidat c ON u.id_utilisateur = c.id_utilisateur AND c.statut = 'valide'
                WHERE com.id_jeu = ?
                ORDER BY com.date_commentaire DESC
                LIMIT 100
            ");
            $stmt->execute([$gameId, $gameId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("GameService getComments Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les statistiques d'un jeu
     * 
     * @param int $gameId ID jeu
     * @return array ['nb_votes_cat' => int, 'nb_votes_final' => int, 'nb_comments' => int]
     */
    public function getGameStats(int $gameId): array {
        $stats = [
            'nb_votes_cat' => 0,
            'nb_votes_final' => 0,
            'nb_comments' => 0
        ];
        
        try {
            // Votes catégories
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bulletin_categorie WHERE id_jeu = ?");
            $stmt->execute([$gameId]);
            $stats['nb_votes_cat'] = (int)$stmt->fetchColumn();
            
            // Votes finaux
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bulletin_final WHERE id_jeu = ?");
            $stmt->execute([$gameId]);
            $stats['nb_votes_final'] = (int)$stmt->fetchColumn();
            
            // Commentaires
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM commentaire WHERE id_jeu = ?");
            $stmt->execute([$gameId]);
            $stats['nb_comments'] = (int)$stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log("GameService getGameStats Error: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Vérifie si un jeu existe
     * 
     * @param int $gameId ID jeu
     * @return bool
     */
    public function gameExists(int $gameId): bool {
        try {
            $stmt = $this->db->prepare("SELECT id_jeu FROM jeu WHERE id_jeu = ?");
            $stmt->execute([$gameId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Récupère tous les jeux avec pagination
     * 
     * @param int $limit Limite de résultats
     * @param int $offset Décalage
     * @return array
     */
    public function getAllGames(int $limit = 50, int $offset = 0): array {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*, 
                       COUNT(DISTINCT com.id_commentaire) as nb_comments,
                       COUNT(DISTINCT bc.id_bulletin) as nb_votes_cat,
                       COUNT(DISTINCT bf.id_bulletin) as nb_votes_final
                FROM jeu j
                LEFT JOIN commentaire com ON j.id_jeu = com.id_jeu
                LEFT JOIN bulletin_categorie bc ON j.id_jeu = bc.id_jeu
                LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu
                GROUP BY j.id_jeu
                ORDER BY j.date_sortie DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("GameService getAllGames Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Recherche des jeux par titre ou éditeur
     * 
     * @param string $search Terme de recherche
     * @return array
     */
    public function searchGames(string $search): array {
        try {
            $search = '%' . trim($search) . '%';
            $stmt = $this->db->prepare("
                SELECT * FROM jeu
                WHERE titre LIKE ? OR editeur LIKE ? OR description LIKE ?
                ORDER BY titre ASC
                LIMIT 50
            ");
            $stmt->execute([$search, $search, $search]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("GameService searchGames Error: " . $e->getMessage());
            return [];
        }
    }
}

?>
