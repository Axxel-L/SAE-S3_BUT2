<?php
/**
 * Gestion des catégories d'événements
 */
class AdminCategoryService
{
    private DatabaseConnection $db;
    private ValidationService $validationService;
    private AuditLogger $auditLogger;
    public function __construct(
        DatabaseConnection $db,
        ValidationService $validationService,
        AuditLogger $auditLogger
    ) {
        $this->db = $db;
        $this->validationService = $validationService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Récupère toutes les catégories d'un événement avec stats
     * @param int $eventId ID de l'événement
     * @return array[] Liste des catégories avec nombre de jeux nominés
     */
    public function getCategoriesByEvent(int $eventId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(DISTINCT n.id_nomination) as nb_jeux
                FROM categorie c
                LEFT JOIN nomination n ON c.id_categorie = n.id_categorie
                WHERE c.id_evenement = ?
                GROUP BY c.id_categorie
                ORDER BY c.nom ASC
            ");
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminCategoryService::getCategoriesByEvent() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les jeux nominés pour une catégorie
     * @param int $categoryId ID de la catégorie
     * @param int $eventId ID de l'événement
     * @return array[] Liste des jeux nominés
     */
    public function getNominatedGames(int $categoryId, int $eventId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT j.id_jeu, j.titre, j.image, j.editeur
                FROM nomination n
                JOIN jeu j ON n.id_jeu = j.id_jeu
                WHERE n.id_categorie = ? AND n.id_evenement = ?
                ORDER BY j.titre ASC
            ");
            $stmt->execute([$categoryId, $eventId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminCategoryService::getNominatedGames() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte les candidatures en attente pour une catégorie 
     * @param int $categoryId ID de la catégorie
     * @param int $eventId ID de l'événement
     * @return int Nombre de candidatures en attente
     */
    public function countPendingApplications(int $categoryId, int $eventId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM event_candidat 
                WHERE id_categorie = ? AND id_evenement = ? AND statut_candidature = 'en_attente'
            ");
            $stmt->execute([$categoryId, $eventId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("AdminCategoryService::countPendingApplications() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compte les candidatures en attente pour un événement
     * @param int $eventId ID de l'événement
     * @return int Nombre de candidatures en attente
     */
    public function countEventPendingApplications(int $eventId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM event_candidat 
                WHERE id_evenement = ? AND statut_candidature = 'en_attente'
            ");
            $stmt->execute([$eventId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("AdminCategoryService::countEventPendingApplications() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ➕ Crée une nouvelle catégorie
     * 
     * @param string $nom Nom de la catégorie
     * @param string $description Description (optionnelle)
     * @param int $eventId ID de l'événement
     * @param int $adminId ID de l'admin qui crée
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function createCategory(
        string $nom,
        string $description,
        int $eventId,
        int $adminId
    ): array {
        $nom = trim($nom);
        $description = trim($description);
        if (empty($nom)) {
            return [
                'success' => false,
                'message' => 'Le nom est obligatoire!'
            ];
        }
        try {
            $stmt = $this->db->prepare("
                INSERT INTO categorie (nom, description, id_evenement)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $nom,
                !empty($description) ? $description : null,
                $eventId
            ]);
            $newCategoryId = (int)$this->db->lastInsertId();
            $this->auditLogger->log(
                'ADMIN_CATEGORY_CREATE',
                "Catégorie '$nom' créée pour événement #$eventId",
                $adminId
            );
            return [
                'success' => true,
                'message' => 'Catégorie créée avec succès!',
                'id' => $newCategoryId
            ];
        } catch (\Exception $e) {
            error_log("AdminCategoryService::createCategory() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Supprime une catégorie
     * @param int $categoryId ID de la catégorie à supprimer
     * @param int $eventId ID de l'événement (sécurité)
     * @param int $adminId ID de l'admin qui supprime
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteCategory(int $categoryId, int $eventId, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT nom FROM categorie WHERE id_categorie = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$category) {
                return [
                    'success' => false,
                    'message' => 'Catégorie non trouvée!'
                ];
            }
            $stmt = $this->db->prepare(
                "DELETE FROM categorie WHERE id_categorie = ? AND id_evenement = ?"
            );
            $stmt->execute([$categoryId, $eventId]);
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Catégorie non trouvée dans cet événement!'
                ];
            }
            $this->auditLogger->log(
                'ADMIN_CATEGORY_DELETE',
                "Catégorie '{$category['nom']}' (#$categoryId) supprimée",
                $adminId
            );
            return [
                'success' => true,
                'message' => 'Catégorie supprimée!'
            ];
        } catch (\Exception $e) {
            error_log("AdminCategoryService::deleteCategory() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère une catégorie
     * @param int $categoryId ID de la catégorie
     * @param int $eventId ID de l'événement (sécurité)
     * @return array|null Détails de la catégorie
     */
    public function getCategory(int $categoryId, int $eventId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM categorie WHERE id_categorie = ? AND id_evenement = ?"
            );
            $stmt->execute([$categoryId, $eventId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("AdminCategoryService::getCategory() Error: " . $e->getMessage());
            return null;
        }
    }
}
