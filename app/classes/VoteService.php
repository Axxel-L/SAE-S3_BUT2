<?php
/**
 * VoteService
 * Service de gestion des votes
 * SOLID: Single Responsibility (logique des votes)
 *        Dependency Inversion (ValidationService, AuditLogger injectés)
 */

class VoteService {
    private DatabaseConnection $db;
    private ValidationService $validator;
    private AuditLogger $auditLogger;
    
    public function __construct(
        DatabaseConnection $db,
        ValidationService $validator,
        AuditLogger $auditLogger
    ) {
        $this->db = $db;
        $this->validator = $validator;
        $this->auditLogger = $auditLogger;
    }
    
    /**
     * Effectue un vote en catégorie
     * 
     * @return array ['success' => bool, 'errors' => string[]]
     */
    public function voteCategoryVote(
        int $userId,
        int $gameId,
        int $categoryId,
        int $eventId
    ): array {
        $errors = [];
        
        // Valider les IDs
        $errors = array_merge($errors, $this->validator->validateInteger($gameId, 1));
        $errors = array_merge($errors, $this->validator->validateInteger($categoryId, 1));
        $errors = array_merge($errors, $this->validator->validateInteger($eventId, 1));
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->db->beginTransaction();
            
            // 1. Vérifier l'événement
            $stmt = $this->db->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$event) {
                throw new \Exception("Événement non trouvé");
            }
            
            if ($event['statut'] !== 'ouvert_categories') {
                throw new \Exception("Le vote par catégories n'est pas ouvert");
            }
            
            // 2. Vérifier l'inscription électorale
            $stmt = $this->db->prepare("
                SELECT id_registre FROM registre_electoral 
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $eventId]);
            if (!$stmt->fetch()) {
                throw new \Exception("Vous n'êtes pas inscrit à cet événement");
            }
            
            // 3. Vérifier qu'on n'a pas déjà voté pour cette catégorie
            $stmt = $this->db->prepare("
                SELECT id_emargement FROM emargement_categorie 
                WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $categoryId, $eventId]);
            if ($stmt->fetch()) {
                throw new \Exception("Vous avez déjà voté pour cette catégorie");
            }
            
            // 4. Vérifier que le jeu est nominé dans cette catégorie
            $stmt = $this->db->prepare("
                SELECT id_nomination FROM nomination 
                WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?
            ");
            $stmt->execute([$gameId, $categoryId, $eventId]);
            if (!$stmt->fetch()) {
                throw new \Exception("Ce jeu n'est pas nominé dans cette catégorie");
            }
            
            // 5. Enregistrer le vote
            $stmt = $this->db->prepare("
                INSERT INTO bulletin_categorie (id_jeu, id_categorie, id_evenement)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$gameId, $categoryId, $eventId]);
            
            // 6. Enregistrer l'émargement
            $stmt = $this->db->prepare("
                INSERT INTO emargement_categorie (id_utilisateur, id_categorie, id_evenement)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $categoryId, $eventId]);
            
            // Logger
            $this->auditLogger->logCategoryVote($userId, $categoryId, $eventId);
            
            $this->db->commit();
            
            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Effectue un vote final
     * 
     * @return array ['success' => bool, 'errors' => string[]]
     */
    public function voteFinalVote(
        int $userId,
        int $gameId,
        int $eventId
    ): array {
        $errors = [];
        
        // Valider les IDs
        $errors = array_merge($errors, $this->validator->validateInteger($gameId, 1));
        $errors = array_merge($errors, $this->validator->validateInteger($eventId, 1));
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->db->beginTransaction();
            
            // 1. Vérifier l'événement
            $stmt = $this->db->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$event) {
                throw new \Exception("Événement non trouvé");
            }
            
            if ($event['statut'] !== 'ouvert_final') {
                throw new \Exception("Le vote final n'est pas ouvert");
            }
            
            // 2. Vérifier l'inscription électorale
            $stmt = $this->db->prepare("
                SELECT id_registre FROM registre_electoral 
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $eventId]);
            if (!$stmt->fetch()) {
                throw new \Exception("Vous n'êtes pas inscrit à cet événement");
            }
            
            // 3. Vérifier qu'on n'a pas déjà voté pour le final
            $stmt = $this->db->prepare("
                SELECT id_emargement FROM emargement_final 
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $eventId]);
            if ($stmt->fetch()) {
                throw new \Exception("Vous avez déjà voté au vote final");
            }
            
            // 4. Vérifier que le jeu est finaliste
            $stmt = $this->db->prepare("
                SELECT id_finaliste FROM finaliste 
                WHERE id_jeu = ? AND id_evenement = ?
            ");
            $stmt->execute([$gameId, $eventId]);
            if (!$stmt->fetch()) {
                throw new \Exception("Ce jeu n'est pas en final");
            }
            
            // 5. Enregistrer le vote
            $stmt = $this->db->prepare("
                INSERT INTO bulletin_final (id_jeu, id_evenement)
                VALUES (?, ?)
            ");
            $stmt->execute([$gameId, $eventId]);
            
            // 6. Enregistrer l'émargement
            $stmt = $this->db->prepare("
                INSERT INTO emargement_final (id_utilisateur, id_evenement)
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $eventId]);
            
            // Logger
            $this->auditLogger->logFinalVote($userId, $gameId, $eventId);
            
            $this->db->commit();
            
            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Récupère le résultat d'une catégorie
     */
    public function getCategoryResults(int $categoryId, int $eventId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    j.id_jeu,
                    j.titre,
                    COUNT(bc.id_bulletin) as votes
                FROM jeu j
                LEFT JOIN bulletin_categorie bc ON j.id_jeu = bc.id_jeu 
                    AND bc.id_categorie = ? AND bc.id_evenement = ?
                WHERE j.id_jeu IN (
                    SELECT id_jeu FROM nomination 
                    WHERE id_categorie = ? AND id_evenement = ?
                )
                GROUP BY j.id_jeu
                ORDER BY votes DESC
            ");
            
            $stmt->execute([$categoryId, $eventId, $categoryId, $eventId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Récupère le résultat final
     */
    public function getFinalResults(int $eventId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    j.id_jeu,
                    j.titre,
                    COUNT(bf.id_bulletin) as votes
                FROM jeu j
                LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu AND bf.id_evenement = ?
                WHERE j.id_jeu IN (
                    SELECT id_jeu FROM finaliste WHERE id_evenement = ?
                )
                GROUP BY j.id_jeu
                ORDER BY votes DESC
            ");
            
            $stmt->execute([$eventId, $eventId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
?>
