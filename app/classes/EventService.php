<?php
/**
 * Gère tous les événements et inscriptions
 */
class EventService {
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
     * Récupère tous les événements actifs pour un utilisateur
     * @param int $userId ID utilisateur
     * @return array ['success' => bool, 'events' => [], 'errors' => []]
     */
    public function getActiveEvents(int $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*,
                       (SELECT COUNT(*) FROM registre_electoral 
                        WHERE id_evenement = e.id_evenement 
                        AND id_utilisateur = ?) as is_registered
                FROM evenement e
                WHERE e.statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final')
                ORDER BY e.date_ouverture DESC
            ");
            $stmt->execute([$userId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return [
                'success' => true,
                'events' => $events,
                'errors' => []
            ];
        } catch (Exception $e) {
            error_log("EventService Error: " . $e->getMessage());
            return [
                'success' => false,
                'events' => [],
                'errors' => ["Erreur lors de la récupération des événements"]
            ];
        }
    }
    
    /**
     * Récupère tous les événements
     * @param int|null $userId ID utilisateur (optionnel pour is_registered)
     * @return array
     */
    public function getAllEvents(?int $userId = null): array {
        try {
            if ($userId === null) {
                $stmt = $this->db->prepare("
                    SELECT * FROM evenement
                    ORDER BY date_ouverture DESC
                ");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT e.*,
                           (SELECT COUNT(*) FROM registre_electoral 
                            WHERE id_evenement = e.id_evenement 
                            AND id_utilisateur = ?) as is_registered
                    FROM evenement e
                    ORDER BY e.date_ouverture DESC
                ");
                $stmt->execute([$userId]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("EventService Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les détails d'un événement
     * @param int $eventId ID événement
     * @return array|null
     */
    public function getEventDetails(int $eventId): ?array {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM evenement WHERE id_evenement = ?"
            );
            $stmt->execute([$eventId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Inscrit un utilisateur à un événement
     * @param int $userId ID utilisateur
     * @param int $eventId ID événement
     * @return array ['success' => bool, 'errors' => []]
     */
    public function registerEvent(int $userId, int $eventId): array {
        $errors = [];
        $errors = array_merge($errors, 
            $this->validator->validateInteger($eventId, 1)
        );
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT statut FROM evenement WHERE id_evenement = ?"
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$event) {
                throw new Exception("Événement non trouvé");
            }
            if ($event['statut'] === 'cloture') {
                throw new Exception("Cet événement n'accepte plus les inscriptions");
            }
            $stmt = $this->db->prepare("
                SELECT id_registre FROM registre_electoral 
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $eventId]);
            if ($stmt->fetch()) {
                throw new Exception("Vous êtes déjà inscrit à cet événement !");
            }
            $stmt = $this->db->prepare("
                INSERT INTO registre_electoral 
                (id_utilisateur, id_evenement, date_inscription)
                VALUES (?, ?, NOW())
            ");
            $success = $stmt->execute([$userId, $eventId]);
            if (!$success) {
                throw new Exception("Erreur lors de l'inscription");
            }
            $this->auditLogger->log('EVENT_REGISTRATION', "Événement ID: $eventId", $userId);
            $this->db->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Vérifie si un utilisateur est inscrit à un événement
     * @param int $userId ID utilisateur
     * @param int $eventId ID événement
     * @return bool
     */
    public function isRegistered(int $userId, int $eventId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT id_registre FROM registre_electoral
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$userId, $eventId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Récupère les inscriptions d'un utilisateur
     * @param int $userId ID utilisateur
     * @return array
     */
    public function getUserRegistrations(int $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT re.*, e.nom, e.statut
                FROM registre_electoral re
                JOIN evenement e ON re.id_evenement = e.id_evenement
                WHERE re.id_utilisateur = ?
                ORDER BY e.date_ouverture DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Compte les utilisateurs inscrits à un événement
     * @param int $eventId ID événement
     * @return int
     */
    public function getRegistrationCount(int $eventId): int {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM registre_electoral
                WHERE id_evenement = ?
            ");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obtient tous les événements par statut
     * @param string $statut Statut à filtrer
     * @return array
     */
    public function getEventsByStatus(string $statut): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM evenement
                WHERE statut = ?
                ORDER BY date_ouverture DESC
            ");
            $stmt->execute([$statut]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Vérifie si un événement est ouvert aux votes catégories
     * @param int $eventId ID événement
     * @return bool
     */
    public function isEventOpenForCategoryVote(int $eventId): bool {
        try {
            $event = $this->getEventDetails($eventId);
            return $event && $event['statut'] === 'ouvert_categories';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Vérifie si un événement est ouvert au vote final
     * @param int $eventId ID événement
     * @return bool
     */
    public function isEventOpenForFinalVote(int $eventId): bool {
        try {
            $event = $this->getEventDetails($eventId);
            return $event && $event['statut'] === 'ouvert_final';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Désinscrire un utilisateur d'un événement
     * @param int $userId ID utilisateur
     * @param int $eventId ID événement
     * @return array ['success' => bool, 'errors' => []]
     */
    public function unregisterEvent(int $userId, int $eventId): array {
        try {
            $this->db->beginTransaction();
            $event = $this->getEventDetails($eventId);
            if (!$event) {
                throw new Exception("Événement non trouvé");
            }
            if ($event['statut'] === 'cloture') {
                throw new Exception("Impossible de se désinscrire d'un événement clôturé");
            }
            $stmt = $this->db->prepare("
                DELETE FROM registre_electoral
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $success = $stmt->execute([$userId, $eventId]);
            if (!$success) {
                throw new Exception("Erreur lors de la désinscription");
            }
            $this->auditLogger->log('EVENT_UNREGISTRATION', "Événement ID: $eventId", $userId);
            $this->db->commit();
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
}

?>