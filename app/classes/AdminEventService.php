<?php
/**
 * Gestion des événements
 */
class AdminEventService
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
     * Récupère tous les événements
     * @return array[] Liste des événements
     */
    public function getAllEvents(): array
    {
        try {
            $this->updateEventStatuses();
            $stmt = $this->db->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminEventService::getAllEvents() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée un nouvel événement
     * @param string $nom Nom de l'événement
     * @param string $description Description optionnelle
     * @param string $dateOuverture Date ouverture vote catégories (ISO format)
     * @param string $dateFermeture Date fermeture vote catégories (ISO format)
     * @param string $dateDebutFinal Date début vote final (ISO format)
     * @param string $dateFermetureFinal Date clôture vote final (ISO format)
     * @param int $adminId ID de l'admin qui crée
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function createEvent(
        string $nom,
        string $description,
        string $dateOuverture,
        string $dateFermeture,
        string $dateDebutFinal,
        string $dateFermetureFinal,
        int $adminId
    ): array {
        $nom = trim($nom);
        $description = trim($description);
        $errors = [];
        if (empty($nom)) {
            $errors[] = "Le nom est obligatoire";
        }
        if (empty($dateOuverture)) {
            $errors[] = "La date d'ouverture est obligatoire";
        }
        if (empty($dateFermeture)) {
            $errors[] = "La date de fin du vote par catégories est obligatoire";
        }
        if (empty($dateDebutFinal)) {
            $errors[] = "La date de début du vote final est obligatoire";
        }
        if (empty($dateFermetureFinal)) {
            $errors[] = "La date de clôture du vote final est obligatoire";
        }
        if (empty($errors)) {
            $d1 = strtotime($dateOuverture);
            $d2 = strtotime($dateFermeture);
            $d3 = strtotime($dateDebutFinal);
            $d4 = strtotime($dateFermetureFinal);
            if ($d2 <= $d1) {
                $errors[] = "La fin du vote par catégories doit être après l'ouverture";
            }
            if ($d3 < $d2) {
                $errors[] = "Le vote final doit commencer après la fin du vote par catégories";
            }
            if ($d4 <= $d3) {
                $errors[] = "La clôture du vote final doit être après son début";
            }
        }
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode("<br>", $errors)
            ];
        }
        try {
            $stmt = $this->db->prepare("
                INSERT INTO evenement 
                (nom, description, date_ouverture, date_fermeture, date_debut_vote_final, date_fermeture_vote_final, statut)
                VALUES (?, ?, ?, ?, ?, ?, 'preparation')
            ");
            $stmt->execute([
                $nom,
                !empty($description) ? $description : null,
                $dateOuverture,
                $dateFermeture,
                $dateDebutFinal,
                $dateFermetureFinal
            ]);
            $newEventId = (int)$this->db->lastInsertId();
            $this->auditLogger->log(
                'ADMIN_EVENT_CREATE',
                "Événement créé: $nom",
                $adminId
            );
            return [
                'success' => true,
                'message' => 'Événement créé avec succès ! ✅',
                'id' => $newEventId
            ];
        } catch (\Exception $e) {
            error_log("AdminEventService::createEvent() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Supprime un événement (si il est cloturé)
     * @param int $eventId ID événement à supprimer
     * @param int $adminId ID de l'admin qui fait l'action
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteEvent(int $eventId, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$event) {
                return [
                    'success' => false,
                    'message' => 'Événement non trouvé!'
                ];
            }
            if ($event['statut'] !== 'cloture') {
                return [
                    'success' => false,
                    'message' => '❌ Vous pouvez seulement supprimer les événements CLÔTURÉS!'
                ];
            }
            $this->db->beginTransaction();
            try {
                $this->db->prepare("DELETE FROM event_candidat WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM resultat WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM nomination WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM categorie WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM emargement_final WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM emargement_categorie WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM bulletin_final WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM bulletin_categorie WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM registre_electoral WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->prepare("DELETE FROM evenement WHERE id_evenement = ?")
                    ->execute([$eventId]);
                $this->db->commit();
                $this->auditLogger->log(
                    'ADMIN_EVENT_DELETE',
                    "Événement #$eventId supprimé",
                    $adminId
                );
                return [
                    'success' => true,
                    'message' => 'Événement supprimé avec succès ! ✅'
                ];
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("AdminEventService::deleteEvent() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Met à jour les statuts de tous les événements
     * @return bool Succès
     */
    private function updateEventStatuses(): bool
    {
        try {
            $this->db->query("CALL update_event_statuts()");
            return true;
        } catch (\Exception $e) {
            error_log("AdminEventService::updateEventStatuses() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configuration des statuts
     * @return array Configuration des statuts
     */
    public static function getStatusConfig(): array
    {
        return [
            'preparation' => [
                'label' => 'Préparation',
                'bg' => 'bg-yellow-500/20',
                'text' => 'text-yellow-400',
                'border' => 'border-yellow-500/30',
                'icon' => 'fa-hourglass-start'
            ],
            'ouvert_categories' => [
                'label' => 'Vote Catégories',
                'bg' => 'bg-green-500/20',
                'text' => 'text-green-400',
                'border' => 'border-green-500/30',
                'icon' => 'fa-vote-yea'
            ],
            'ferme_categories' => [
                'label' => 'Attente Vote Final',
                'bg' => 'bg-blue-500/20',
                'text' => 'text-blue-400',
                'border' => 'border-blue-500/30',
                'icon' => 'fa-pause-circle'
            ],
            'ouvert_final' => [
                'label' => 'Vote Final',
                'bg' => 'bg-purple-500/20',
                'text' => 'text-purple-400',
                'border' => 'border-purple-500/30',
                'icon' => 'fa-crown'
            ],
            'cloture' => [
                'label' => 'Clôturé',
                'bg' => 'bg-red-500/20',
                'text' => 'text-red-400',
                'border' => 'border-red-500/30',
                'icon' => 'fa-times-circle'
            ]
        ];
    }
}
