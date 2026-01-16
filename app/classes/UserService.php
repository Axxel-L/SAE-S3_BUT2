<?php
/**
 * Service de gestion des utilisateurs
 */
class UserService {
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
     * Crée un nouvel utilisateur
     * @return array ['success' => bool, 'user' => User|null, 'errors' => string[]]
     */
    public function register(
        string $email,
        string $pseudo,
        string $password,
        string $confirmPassword,
        string $type = 'joueur'
    ): array {
        $errors = [];
        $errors = array_merge($errors, $this->validator->validateEmail($email));
        if ($type === 'joueur') {
            $errors = array_merge($errors, $this->validator->validatePseudo($pseudo));
        }
        $errors = array_merge(
            $errors,
            $this->validator->validatePassword($password, $confirmPassword)
        );
        if (!empty($errors)) {
            return ['success' => false, 'user' => null, 'errors' => $errors];
        }
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'user' => null,
                    'errors' => ['Cet email est déjà utilisé']
                ];
            }
            if ($type === 'joueur' && !empty($pseudo)) {
                $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE pseudo = ?");
                $stmt->execute([$pseudo]);
                if ($stmt->fetch()) {
                    return [
                        'success' => false,
                        'user' => null,
                        'errors' => ['Ce pseudo est déjà pris']
                    ];
                }
            }
            
            $salt = PasswordManager::generateSalt();
            $passwordHash = PasswordManager::hashPassword($password, $salt);
            $pseudoToSave = ($type === 'joueur') ? $pseudo : null;
            $stmt = $this->db->prepare("
                INSERT INTO utilisateur 
                (email, pseudo, mot_de_passe, salt, type, date_inscription)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $success = $stmt->execute([
                $email,
                $pseudoToSave,
                $passwordHash,
                $salt,
                $type
            ]);
            if (!$success) {
                return [
                    'success' => false,
                    'user' => null,
                    'errors' => ['Erreur lors de la création du compte']
                ];
            }
            
            $userId = $this->db->lastInsertId();
            $user = new User($userId, $email, $pseudoToSave ?? '', $passwordHash, $salt, $type);
            $this->auditLogger->logUserRegistration($userId, $type);
            return ['success' => true, 'user' => $user, 'errors' => [], 'id' => $userId];
        } catch (\Exception $e) {
            error_log("Register Error: " . $e->getMessage());
            return [
                'success' => false,
                'user' => null,
                'errors' => ['Erreur système']
            ];
        }
    }
    
    /**
     * Récupère un utilisateur par ID
     */
    public function getUserById(int $userId): ?User {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $data ? User::fromDatabase($data) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère un utilisateur par email
     */
    public function getUserByEmail(string $email): ?User {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $data ? User::fromDatabase($data) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Met à jour le pseudo d'un utilisateur
     */
    public function updatePseudo(int $userId, string $pseudo): array {
        $errors = $this->validator->validatePseudo($pseudo);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $stmt = $this->db->prepare("UPDATE utilisateur SET pseudo = ? WHERE id_utilisateur = ?");
            $success = $stmt->execute([$pseudo, $userId]);
            return ['success' => $success, 'errors' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Erreur lors de la mise à jour']];
        }
    }
    
    /**
     * Change le mot de passe d'un utilisateur
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): array {
        $errors = [];
        $errors = array_merge($errors, $this->validator->validatePassword($newPassword));
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'errors' => ['Utilisateur non trouvé']];
            }
            if (!PasswordManager::verifyPassword($oldPassword, $user->getPasswordHash(), $user->getSalt())) {
                return ['success' => false, 'errors' => ['Ancien mot de passe incorrect']];
            }
            $newSalt = PasswordManager::generateSalt();
            $newHash = PasswordManager::hashPassword($newPassword, $newSalt);
            $stmt = $this->db->prepare("
                UPDATE utilisateur 
                SET mot_de_passe = ?, salt = ? 
                WHERE id_utilisateur = ?
            ");
            $success = $stmt->execute([$newHash, $newSalt, $userId]);
            return ['success' => $success, 'errors' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Erreur système']];
        }
    }
}
?>
