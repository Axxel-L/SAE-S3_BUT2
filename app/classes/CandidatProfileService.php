<?php
/**
 * CandidatProfileService - Gère le profil des candidats
 */
class CandidatProfileService
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
     * Récupère les infos du profil candidat
     */
    public function getCandidatProfile(int $userId): array
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
                'stats' => $this->getQuickStats($candidat['id_jeu'] ?? null),
                'error' => null
            ];
        } catch (\Exception $e) {
            error_log("CandidatProfileService Error: " . $e->getMessage());
            return [
                'candidat' => null,
                'stats' => [],
                'error' => 'Erreur lors du chargement du profil'
            ];
        }
    }

    /**
     * Met à jour le profil du candidat
     */
    public function updateCandidatProfile(
        int $userId,
        string $nom,
        ?string $bio,
        ?string $photo
    ): array {
        try {
            if (empty($nom) || strlen($nom) < 2 || strlen($nom) > 100) {
                return [
                    'success' => false,
                    'message' => 'Le nom doit contenir entre 2 et 100 caractères !'
                ];
            }
            if (!empty($photo) && !filter_var($photo, FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => "L'URL de la photo n'est pas valide !"
                ];
            }
            if (!empty($bio) && strlen($bio) > 500) {
                return [
                    'success' => false,
                    'message' => 'La biographie ne peut pas dépasser 500 caractères !'
                ];
            }
            $stmt = $this->db->prepare("
                UPDATE candidat 
                SET nom = ?, bio = ?, photo = ?
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([
                $nom,
                !empty($bio) ? $bio : null,
                !empty($photo) ? $photo : null,
                $userId
            ]);
            $this->auditLogger->log(
                'CANDIDAT_PROFIL_UPDATE',
                'Mise à jour du profil candidat',$userId
            );
            return [
                'success' => true,
                'message' => 'Profil mis à jour avec succès ! ✅'
            ];
        } catch (\Exception $e) {
            error_log("updateCandidatProfile Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.'
            ];
        }
    }

    /**
     * Récupère les informations complètes du candidat
     */
    private function getCandidatInfo(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.email, u.date_inscription, j.titre as titre_jeu, 
                       j.image as image_jeu, j.editeur
                FROM candidat c 
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
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
    private function getQuickStats(?int $idJeu): array
    {
        $stats = [
            'votes_categorie' => 0,
            'votes_final' => 0,
            'commentaires' => 0
        ];
        if (!$idJeu) {
            return $stats;
        }
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_jeu = ?");
            $stmt->execute([$idJeu]);
            $stats['votes_categorie'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM bulletin_final WHERE id_jeu = ?");
            $stmt->execute([$idJeu]);
            $stats['votes_final'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM commentaire WHERE id_jeu = ?");
            $stmt->execute([$idJeu]);
            $stats['commentaires'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log("getQuickStats Error: " . $e->getMessage());
        }
        return $stats;
    }

    /**
     * Retourne la configuration du statut candidat
     */
    public static function getStatutConfig(string $statut): array
    {
        $statut_config = [
            'en_attente' => [
                'label' => 'En attente de validation',
                'color' => 'yellow',
                'icon' => 'fa-clock'
            ],
            'valide' => [
                'label' => 'Validé',
                'color' => 'green',
                'icon' => 'fa-check-circle'
            ],
            'refuse' => [
                'label' => 'Refusé',
                'color' => 'red',
                'icon' => 'fa-times-circle'
            ]
        ];
        return $statut_config[$statut] ?? $statut_config['en_attente'];
    }
}
