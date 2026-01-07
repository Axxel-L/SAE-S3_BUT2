<?php



/**
 * AdminUserService - Gestion des utilisateurs (Admin)
 * 
 * Responsabilités:
 * - CRUD utilisateurs
 * - Gestion des types d'utilisateurs
 * - Profils candidats associés
 * - Validation des données
 * 
 * SOLID principles:
 * - S: Une seule responsabilité (gestion utilisateurs admin)
 * - O: Facile d'ajouter de nouveaux types d'utilisateurs
 * - L: Services substitutables
 * - I: Méthodes spécifiques et claires
 * - D: Dépendances injectées (DB, ValidationService, AuditLogger)
 */
class AdminUserService
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
     * 📋 Récupère tous les utilisateurs avec infos candidat
     * 
     * @return array[] Liste des utilisateurs
     */
    public function getAllUsers(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id_utilisateur,
                    u.email,
                    u.type,
                    u.date_inscription,
                    (SELECT COUNT(*) FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur) as is_candidat,
                    (SELECT c.statut FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur LIMIT 1) as candidat_statut
                FROM utilisateur u
                ORDER BY u.date_inscription DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\Exception $e) {
            error_log("AdminUserService::getAllUsers() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ➕ Crée un nouvel utilisateur
     * 
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe en clair
     * @param string $type Type (joueur, candidat, admin)
     * @param int $adminId ID de l'admin qui crée
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function createUser(string $email, string $password, string $type, int $adminId): array
    {
        // Validation
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $password = trim($password);

        // Vérifier format email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email invalide!'
            ];
        }

        // Vérifier longueur mot de passe
        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'Mot de passe minimum 8 caractères!'
            ];
        }

        // Vérifier type valide
        if (!in_array($type, ['joueur', 'candidat', 'admin'])) {
            return [
                'success' => false,
                'message' => 'Type invalide!'
            ];
        }

        try {
            // Vérifier unicité email
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Cet email existe déjà!'
                ];
            }

            // Hash du mot de passe
            $salt = bin2hex(random_bytes(16));
            $password_hash = hash('sha256', $password . $salt);

            // Insérer l'utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([$email, $password_hash, $salt, $type]);
            $new_user_id = (int)$this->db->lastInsertId();

            // Si candidat, créer le profil candidat
            if ($type === 'candidat') {
                $this->createCandidatProfile($new_user_id, $email);
            }

            // Log audit
            $this->auditLogger->log(
                'ADMIN_USER_CREATE',
                "Email: $email | Type: $type",
                $adminId
            );

            return [
                'success' => true,
                'message' => '✅ Utilisateur créé avec succès!',
                'id' => $new_user_id
            ];
        } catch (\Exception $e) {
            error_log("AdminUserService::createUser() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🔄 Change le type d'un utilisateur
     * 
     * @param int $userId ID utilisateur à modifier
     * @param string $newType Nouveau type
     * @param int $adminId ID de l'admin qui fait l'action
     * @return array ['success' => bool, 'message' => string]
     */
    public function changeUserType(int $userId, string $newType, int $adminId): array
    {
        // Vérifier type valide
        if (!in_array($newType, ['joueur', 'candidat', 'admin'])) {
            return [
                'success' => false,
                'message' => 'Type invalide!'
            ];
        }

        // Pas modifier son propre type
        if ($userId === $adminId) {
            return [
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre type!'
            ];
        }

        try {
            // Mettre à jour le type
            $stmt = $this->db->prepare("UPDATE utilisateur SET type = ? WHERE id_utilisateur = ?");
            $stmt->execute([$newType, $userId]);

            // Si devient candidat, créer le profil candidat
            if ($newType === 'candidat') {
                $stmt = $this->db->prepare("SELECT id_candidat FROM candidat WHERE id_utilisateur = ?");
                $stmt->execute([$userId]);

                if ($stmt->rowCount() === 0) {
                    $stmt = $this->db->prepare("SELECT email FROM utilisateur WHERE id_utilisateur = ?");
                    $stmt->execute([$userId]);
                    $email = $stmt->fetchColumn();

                    $this->createCandidatProfile($userId, (string)$email);
                }
            }

            // Log audit
            $this->auditLogger->log(
                'ADMIN_USER_TYPE_CHANGE',
                "Utilisateur #$userId | Nouveau type: $newType",
                $adminId
            );

            return [
                'success' => true,
                'message' => '✅ Type modifié!'
            ];
        } catch (\Exception $e) {
            error_log("AdminUserService::changeUserType() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🗑️ Supprime un utilisateur et toutes ses données
     * 
     * @param int $userId ID utilisateur à supprimer
     * @param int $adminId ID de l'admin qui fait l'action
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteUser(int $userId, int $adminId): array
    {
        // Pas supprimer soi-même
        if ($userId === $adminId) {
            return [
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte!'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Supprimer données liées (ordre important: dépendances)
            $this->db->prepare("DELETE FROM event_candidat WHERE id_candidat IN (SELECT id_candidat FROM candidat WHERE id_utilisateur = ?)")
                ->execute([$userId]);

            $this->db->prepare("DELETE FROM candidat WHERE id_utilisateur = ?")
                ->execute([$userId]);

            $this->db->prepare("DELETE FROM registre_electoral WHERE id_utilisateur = ?")
                ->execute([$userId]);

            $this->db->prepare("DELETE FROM bulletin_categorie WHERE id_utilisateur = ?")
                ->execute([$userId]);

            $this->db->prepare("DELETE FROM bulletin_final WHERE id_utilisateur = ?")
                ->execute([$userId]);

            $this->db->prepare("DELETE FROM commentaire WHERE id_utilisateur = ?")
                ->execute([$userId]);

            // Supprimer l'utilisateur
            $this->db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?")
                ->execute([$userId]);

            $this->db->commit();

            // Log audit
            $this->auditLogger->log(
                'ADMIN_USER_DELETE',
                "Utilisateur #$userId supprimé",
                $adminId
            );

            return [
                'success' => true,
                'message' => '🗑️ Utilisateur supprimé!'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("AdminUserService::deleteUser() Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🎯 Crée le profil candidat pour un utilisateur
     * 
     * @param int $userId ID utilisateur
     * @param string $nom Nom du candidat
     * @return bool Succès
     */
    private function createCandidatProfile(int $userId, string $nom): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO candidat (id_utilisateur, nom, statut, date_inscription)
                VALUES (?, ?, 'valide', NOW())
            ");
            return $stmt->execute([$userId, $nom]);
        } catch (\Exception $e) {
            error_log("AdminUserService::createCandidatProfile() Error: " . $e->getMessage());
            return false;
        }
    }
}
