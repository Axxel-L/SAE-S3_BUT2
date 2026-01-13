<?php
/**
 * AdminApplicationService - Gestion des candidatures d'événements
 */
class AdminApplicationService
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
     * Récupère toutes les candidatures avec filtres optionnels
     * @param int $eventId Filtrer par événement (0 = tous)
     * @param string $status Filtrer par statut ('en_attente', 'approuve', 'refuse', '' = tous)
     * @return array[] Liste des candidatures avec tous leurs détails
     */
    public function getApplications(int $eventId = 0, string $status = ''): array
    {
        try {
            $query = "
                SELECT 
                    ec.id_event_candidat,
                    ec.id_evenement,
                    ec.id_categorie,
                    ec.statut_candidature,
                    ec.date_inscription,
                    ec.date_validation,
                    ec.motif_refus,
                    e.nom as evenement_nom,
                    e.statut as evenement_statut,
                    cat.nom as categorie_nom,
                    c.id_candidat,
                    c.nom as candidat_nom,
                    c.id_jeu,
                    j.titre as jeu_titre,
                    j.image as jeu_image,
                    j.editeur as jeu_editeur,
                    u.email as candidat_email,
                    admin.email as valide_par_email
                FROM event_candidat ec
                JOIN evenement e ON ec.id_evenement = e.id_evenement
                LEFT JOIN categorie cat ON ec.id_categorie = cat.id_categorie
                JOIN candidat c ON ec.id_candidat = c.id_candidat
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                LEFT JOIN utilisateur admin ON ec.valide_par = admin.id_utilisateur
                WHERE 1=1
            ";
            $params = [];
            if ($eventId > 0) {
                $query .= " AND ec.id_evenement = ?";
                $params[] = $eventId;
            }
            if (!empty($status)) {
                $query .= " AND ec.statut_candidature = ?";
                $params[] = $status;
            }
            $query .= " ORDER BY ec.date_inscription DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminApplicationService::getApplications() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte les candidatures en attente globalement
     * @return int Nombre de candidatures en attente
     */
    public function countPendingApplications(): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM event_candidat WHERE statut_candidature = 'en_attente'"
            );
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("AdminApplicationService::countPendingApplications() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Approuve une candidature et ajoute le jeu aux nominations
     * @param int $applicationId ID de la candidature
     * @param int $adminId ID de l'admin qui approuve
     * @return array ['success' => bool, 'message' => string]
     */
    public function approveApplication(int $applicationId, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ec.*, c.id_jeu, j.titre as jeu_titre
                FROM event_candidat ec
                JOIN candidat c ON ec.id_candidat = c.id_candidat
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE ec.id_event_candidat = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$application) {
                throw new \Exception("Candidature non trouvée.");
            }
            $stmt = $this->db->prepare("
                UPDATE event_candidat 
                SET statut_candidature = 'approuve', 
                    date_validation = NOW(), 
                    valide_par = ?,
                    motif_refus = NULL
                WHERE id_event_candidat = ?
            ");
            $stmt->execute([$adminId, $applicationId]);
            $stmt = $this->db->prepare("
                SELECT id_nomination FROM nomination 
                WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?
            ");
            $stmt->execute([
                $application['id_jeu'],
                $application['id_categorie'],
                $application['id_evenement']
            ]);

            if ($stmt->rowCount() === 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO nomination (id_jeu, id_categorie, id_evenement)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $application['id_jeu'],
                    $application['id_categorie'],
                    $application['id_evenement']
                ]);
            }
            $this->auditLogger->log(
                'ADMIN_CANDIDATURE_APPROUVE',
                "Candidature #$applicationId approuvée - Jeu: " . ($application['jeu_titre'] ?? 'Inconnu'),
                $adminId
            );
            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Candidature approuvée ! Le jeu a été ajouté aux nominations.'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("AdminApplicationService::approveApplication() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Refuse une candidature avec motif
     * @param int $applicationId ID de la candidature
     * @param string $reason Motif du refus
     * @param int $adminId ID de l'admin qui refuse
     * @return array ['success' => bool, 'message' => string]
     */
    public function rejectApplication(int $applicationId, string $reason, int $adminId): array
    {
        try {
            $reason = trim($reason);
            $stmt = $this->db->prepare("
                UPDATE event_candidat 
                SET statut_candidature = 'refuse', 
                    date_validation = NOW(), 
                    valide_par = ?,
                    motif_refus = ?
                WHERE id_event_candidat = ?
            ");
            $stmt->execute([$adminId, !empty($reason) ? $reason : 'Non spécifié', $applicationId]);
            $this->auditLogger->log(
                'ADMIN_CANDIDATURE_REFUSE',
                "Candidature #$applicationId refusée - Motif: $reason",
                $adminId
            );
            return [
                'success' => true,
                'message' => 'Candidature refusée.'
            ];
        } catch (\Exception $e) {
            error_log("AdminApplicationService::rejectApplication() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupère une candidature spécifique
     * @param int $applicationId ID de la candidature
     * @return array|null Détails de la candidature
     */
    public function getApplication(int $applicationId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ec.*, c.id_jeu, j.titre as jeu_titre
                FROM event_candidat ec
                JOIN candidat c ON ec.id_candidat = c.id_candidat
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE ec.id_event_candidat = ?
            ");
            $stmt->execute([$applicationId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("AdminApplicationService::getApplication() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques des candidatures
     * @param int $eventId ID de l'événement
     * @return array ['total' => int, 'pending' => int, 'approved' => int, 'rejected' => int]
     */
    public function getApplicationStats(int $eventId = 0): array
    {
        try {
            $baseQuery = "FROM event_candidat WHERE 1=1";
            $params = [];
            if ($eventId > 0) {
                $baseQuery .= " AND id_evenement = ?";
                $params[] = $eventId;
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) " . $baseQuery);
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) " . $baseQuery . " AND statut_candidature = 'en_attente'"
            );
            $stmt->execute($params);
            $pending = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) " . $baseQuery . " AND statut_candidature = 'approuve'"
            );
            $stmt->execute($params);
            $approved = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) " . $baseQuery . " AND statut_candidature = 'refuse'"
            );
            $stmt->execute($params);
            $rejected = (int)$stmt->fetchColumn();
            return [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected
            ];
        } catch (\Exception $e) {
            error_log("AdminApplicationService::getApplicationStats() Error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
        }
    }

    /**
     * Récupère la configuration des statuts
     * @return array Configuration des statuts
     */
    public static function getStatusConfig(): array
    {
        return [
            'en_attente' => [
                'label' => 'En attente',
                'icon' => 'fa-hourglass-half',
                'badge_class' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                'card_class' => 'border-yellow-500/30 bg-yellow-500/5'
            ],
            'approuve' => [
                'label' => 'Approuvée',
                'icon' => 'fa-check-circle',
                'badge_class' => 'bg-green-500/20 text-green-400 border-green-500/30',
                'card_class' => 'border-green-500/30 bg-green-500/5'
            ],
            'refuse' => [
                'label' => 'Refusée',
                'icon' => 'fa-times-circle',
                'badge_class' => 'bg-red-500/20 text-red-400 border-red-500/30',
                'card_class' => 'border-red-500/30 bg-red-500/5'
            ]
        ];
    }
}
