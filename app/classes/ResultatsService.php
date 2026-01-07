<?php
/**
 * ResultatsService.php - CORRIGÉ
 * 
 * Gère la logique métier de la page des résultats
 * - Récupération événement
 * - Résultats par catégorie
 * - Vote final
 * - Statistiques
 * 
 * Single Responsibility: Logique métier résultats
 */



class ResultatsService {
    
    private $db;
    private $validator;
    
    public function __construct($db, $validator = null) {
        $this->db = $db;
        $this->validator = $validator;
    }
    
    /**
     * Récupère l'événement avec statut clôturé
     * 
     * @param int $eventId ID événement
     * @return array|null
     */
    public function getClosedEvent(int $eventId): ?array {
        if ($eventId <= 0) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM evenement 
                WHERE id_evenement = ? AND statut = 'cloture'
            ");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("ResultatsService getClosedEvent Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les résultats par catégorie
     * 
     * @param int $eventId ID événement
     * @return array
     */
    public function getResultsByCategory(int $eventId): array {
        $resultatsCat = [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT c.id_categorie, c.nom as categorie, j.id_jeu, j.titre, COUNT(bc.id_bulletin) as nb_voix
                FROM categorie c
                LEFT JOIN bulletin_categorie bc ON c.id_categorie = bc.id_categorie AND bc.id_evenement = ?
                LEFT JOIN jeu j ON bc.id_jeu = j.id_jeu
                WHERE c.id_evenement = ?
                GROUP BY c.id_categorie, j.id_jeu
                ORDER BY c.nom, nb_voix DESC
            ");
            $stmt->execute([$eventId, $eventId]);
            $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($allResults as $result) {
                if (!isset($resultatsCat[$result['id_categorie']])) {
                    $resultatsCat[$result['id_categorie']] = [
                        'nom' => $result['categorie'],
                        'jeux' => []
                    ];
                }
                if ($result['id_jeu'] !== null) {
                    $resultatsCat[$result['id_categorie']]['jeux'][] = $result;
                }
            }
        } catch (Exception $e) {
            error_log("ResultatsService getResultsByCategory Error: " . $e->getMessage());
        }
        
        return $resultatsCat;
    }
    
    /**
     * Récupère les résultats du vote final
     * 
     * @param int $eventId ID événement
     * @return array
     */
    public function getFinalResults(int $eventId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT j.id_jeu, j.titre, COUNT(bf.id_bulletin_final) as nb_voix
                FROM bulletin_final bf
                LEFT JOIN jeu j ON bf.id_jeu = j.id_jeu
                WHERE bf.id_evenement = ?
                GROUP BY j.id_jeu
                ORDER BY nb_voix DESC
            ");
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ResultatsService getFinalResults Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les statistiques de l'événement
     * 
     * @param int $eventId ID événement
     * @param int $categoryCount Nombre de catégories
     * @return array
     */
    public function getEventStats(int $eventId, int $categoryCount): array {
        $stats = [
            'total_votes_categories' => 0,
            'total_votes_final' => 0,
            'nb_categories' => $categoryCount,
            'nb_inscrits' => 0
        ];
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_evenement = ?
            ");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_votes_categories'] = $result ? (int)$result['total'] : 0;
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM bulletin_final WHERE id_evenement = ?
            ");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_votes_final'] = $result ? (int)$result['total'] : 0;
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM registre_electoral WHERE id_evenement = ?
            ");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['nb_inscrits'] = $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("ResultatsService getEventStats Error: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Calcule le pourcentage d'un vote
     * 
     * @param int $votes Nombre de votes
     * @param int $total Total des votes
     * @return float
     */
    public function calculatePercentage(int $votes, int $total): float {
        if ($total <= 0) {
            return 0.0;
        }
        return round($votes / $total * 100, 1);
    }
}

?>