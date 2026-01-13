<?php



/**
 * AdminCandidateService - Gestion des candidats (Admin)
 * 
 * Responsabilités:
 * - CRUD candidats
 * - Gestion des statuts (en_attente, valide, refuse)
 * - Suppression avec conversion en joueur
 * - Récupération avec stats
 * - Validation des données
 * 
 * SOLID principles:
 * - S: Une seule responsabilité (gestion candidats)
 * - O: Facile d'ajouter de nouveaux statuts
 * - L: Services substitutables
 * - I: Méthodes spécifiques et claires
 * - D: Dépendances injectées (DB, ValidationService, AuditLogger)
 */
class AdminCandidateService
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
     * 📋 Récupère tous les candidats avec filtres optionnels
     * 
     * @param string $status Filtrer par statut ('en_attente', 'valide', 'refuse', '' = tous)
     * @return array[] Liste des candidats avec tous leurs détails
     */
    public function getCandidates(string $status = ''): array
    {
        try {
            $query = "
                SELECT 
                    c.id_candidat,
                    c.id_utilisateur,
                    c.nom,
                    c.bio,
                    c.photo,
                    c.statut,
                    c.date_inscription,
                    u.email,
                    j.id_jeu,
                    j.titre as jeu_titre,
                    j.image as jeu_image,
                    j.editeur,
                    (SELECT COUNT(*) FROM event_candidat ec WHERE ec.id_candidat = c.id_candidat) as nb_candidatures,
                    (SELECT COUNT(*) FROM commentaire cm WHERE cm.id_jeu = c.id_jeu) as nb_commentaires
                FROM candidat c
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
            ";

            $params = [];
            if (!empty($status)) {
                $query .= " WHERE c.statut = ?";
                $params[] = $status;
            }

            $query .= " ORDER BY 
                CASE c.statut 
                    WHEN 'en_attente' THEN 1 
                    WHEN 'valide' THEN 2 
                    WHEN 'refuse' THEN 3 
                END,
                c.date_inscription DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminCandidateService::getCandidates() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 📊 Récupère les statistiques des candidats par statut
     * 
     * @return array ['all' => int, 'en_attente' => int, 'valide' => int, 'refuse' => int]
     */
    public function getCandidateStats(): array
    {
        try {
            $stats = [
                'all' => 0,
                'en_attente' => 0,
                'valide' => 0,
                'refuse' => 0
            ];

            $stmt = $this->db->query("SELECT statut, COUNT(*) as nb FROM candidat GROUP BY statut");
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                if (isset($stats[$row['statut']])) {
                    $stats[$row['statut']] = (int)$row['nb'];
                    $stats['all'] += (int)$row['nb'];
                }
            }

            return $stats;
        } catch (\Exception $e) {
            error_log("AdminCandidateService::getCandidateStats() Error: " . $e->getMessage());
            return [
                'all' => 0,
                'en_attente' => 0,
                'valide' => 0,
                'refuse' => 0
            ];
        }
    }

    /**
     * ✅ Valide un candidat
     * 
     * @param int $candidateId ID du candidat
     * @param int $adminId ID de l'admin qui valide
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateCandidate(int $candidateId, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE candidat SET statut = 'valide' WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);

            // Log audit
            $this->auditLogger->log(
                'ADMIN_CANDIDAT_VALIDE',
                "Candidat ID: $candidateId validé",
                $adminId
            );

            return [
                'success' => true,
                'message' => '✅ Candidature validée avec succès !'
            ];
        } catch (\Exception $e) {
            error_log("AdminCandidateService::validateCandidate() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ❌ Refuse un candidat avec motif optionnel
     * 
     * @param int $candidateId ID du candidat
     * @param string $reason Motif du refus (optionnel)
     * @param int $adminId ID de l'admin qui refuse
     * @return array ['success' => bool, 'message' => string]
     */
    public function rejectCandidate(int $candidateId, string $reason, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE candidat SET statut = 'refuse' WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);

            // Log audit
            $this->auditLogger->log(
                'ADMIN_CANDIDAT_REFUSE',
                "Candidat ID: $candidateId refusé - Motif: " . (trim($reason) ?: 'Non spécifié'),
                $adminId
            );

            return [
                'success' => true,
                'message' => '❌ Candidature refusée.'
            ];
        } catch (\Exception $e) {
            error_log("AdminCandidateService::rejectCandidate() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ⏳ Remet un candidat en attente
     * 
     * @param int $candidateId ID du candidat
     * @param int $adminId ID de l'admin
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetCandidate(int $candidateId, int $adminId): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE candidat SET statut = 'en_attente' WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);

            // Log audit
            $this->auditLogger->log(
                'ADMIN_CANDIDAT_ATTENTE',
                "Candidat ID: $candidateId remis en attente",
                $adminId
            );

            return [
                'success' => true,
                'message' => '⏳ Candidature remise en attente.'
            ];
        } catch (\Exception $e) {
            error_log("AdminCandidateService::resetCandidate() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🗑️ Supprime un candidat et convertit l'utilisateur en joueur
     * 
     * Processus transactionnel:
     * 1. Récupère l'id_utilisateur du candidat
     * 2. Supprime les candidatures aux événements
     * 3. Supprime le profil candidat
     * 4. Convertit l'utilisateur en joueur
     * 
     * @param int $candidateId ID du candidat à supprimer
     * @param int $adminId ID de l'admin qui supprime
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteCandidate(int $candidateId, int $adminId): array
    {
        try {
            $this->db->beginTransaction();

            // Récupérer l'id_utilisateur
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM candidat WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);
            $candidateData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$candidateData) {
                throw new \Exception("Candidat non trouvé.");
            }

            // Supprimer les candidatures aux événements
            $stmt = $this->db->prepare("DELETE FROM event_candidat WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);

            // Supprimer le profil candidat
            $stmt = $this->db->prepare("DELETE FROM candidat WHERE id_candidat = ?");
            $stmt->execute([$candidateId]);

            // Convertir l'utilisateur en joueur
            $stmt = $this->db->prepare("UPDATE utilisateur SET type = 'joueur' WHERE id_utilisateur = ?");
            $stmt->execute([$candidateData['id_utilisateur']]);

            $this->db->commit();

            // Log audit
            $this->auditLogger->log(
                'ADMIN_CANDIDAT_DELETE',
                "Candidat ID: $candidateId supprimé et converti en joueur",
                $adminId
            );

            return [
                'success' => true,
                'message' => '🗑️ Candidat supprimé (compte converti en joueur).'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("AdminCandidateService::deleteCandidate() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🎯 Récupère un candidat spécifique
     * 
     * @param int $candidateId ID du candidat
     * @return array|null Détails du candidat
     */
    public function getCandidate(int $candidateId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id_candidat,
                    c.id_utilisateur,
                    c.nom,
                    c.bio,
                    c.photo,
                    c.statut,
                    c.date_inscription,
                    u.email,
                    j.id_jeu,
                    j.titre as jeu_titre,
                    j.image as jeu_image,
                    j.editeur
                FROM candidat c
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE c.id_candidat = ?
            ");
            $stmt->execute([$candidateId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("AdminCandidateService::getCandidate() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 🎨 Récupère la configuration des statuts avec styles
     * 
     * @return array Configuration des statuts
     */
    public static function getStatusConfig(): array
    {
        return [
            'en_attente' => [
                'label' => 'En attente',
                'color' => 'yellow',
                'icon' => 'fa-clock',
                'bg' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
            ],
            'valide' => [
                'label' => 'Validé',
                'color' => 'green',
                'icon' => 'fa-check-circle',
                'bg' => 'bg-green-500/20 text-green-400 border-green-500/30'
            ],
            'refuse' => [
                'label' => 'Refusé',
                'color' => 'red',
                'icon' => 'fa-times-circle',
                'bg' => 'bg-red-500/20 text-red-400 border-red-500/30'
            ]
        ];
    }
}
