<?php
/**
 * AuthenticationService
 * Service d'authentification utilisateur
 * SOLID: Single Responsibility (authentification)
 *        Dependency Inversion (utilise ValidationService, PasswordManager, AuditLogger)
 *        Open/Closed (extensible pour d'autres méthodes auth)
 */

class AuthenticationService {
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
     * Authentifie un utilisateur par email/mot de passe
     * 
     * @return array ['success' => bool, 'user' => User|null, 'errors' => string[]]
     */
    public function authenticate(string $email, string $password): array {
        // Valider l'email et le mot de passe
        $emailErrors = $this->validator->validateEmail($email);
        if (!empty($emailErrors)) {
            $this->auditLogger->logLoginFailure($email);
            return [
                'success' => false,
                'user' => null,
                'errors' => $emailErrors
            ];
        }
        
        if (empty($password)) {
            $this->auditLogger->logLoginFailure($email);
            return [
                'success' => false,
                'user' => null,
                'errors' => ['Le mot de passe est requis']
            ];
        }
        
        try {
            // Récupérer l'utilisateur par email
            $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userData) {
                $this->auditLogger->logLoginFailure($email);
                return [
                    'success' => false,
                    'user' => null,
                    'errors' => ['Email ou mot de passe incorrect']
                ];
            }
            
            // Vérifier le mot de passe
            if (!PasswordManager::verifyPassword(
                $password,
                $userData['mot_de_passe'],
                $userData['salt']
            )) {
                $this->auditLogger->logLoginFailure($email);
                return [
                    'success' => false,
                    'user' => null,
                    'errors' => ['Email ou mot de passe incorrect']
                ];
            }
            
            // Vérifier le statut du candidat si applicable
            if ($userData['type'] === 'candidat') {
                $errors = $this->checkCandidateStatus($userData['id_utilisateur']);
                if (!empty($errors)) {
                    $this->auditLogger->logLoginFailure($email);
                    return [
                        'success' => false,
                        'user' => null,
                        'errors' => $errors
                    ];
                }
            }
            
            // Authentification réussie
            $user = User::fromDatabase($userData);
            $this->auditLogger->logLoginSuccess($user->getId());
            
            return [
                'success' => true,
                'user' => $user,
                'errors' => []
            ];
        } catch (\Exception $e) {
            error_log("Authentication Error: " . $e->getMessage());
            return [
                'success' => false,
                'user' => null,
                'errors' => ['Erreur système. Réessayez plus tard.']
            ];
        }
    }
    
    /**
     * Vérifie le statut d'un candidat
     */
    private function checkCandidateStatus(int $userId): array {
        try {
            $stmt = $this->db->prepare("SELECT statut FROM candidat WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $candidate = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$candidate) {
                return ["Profil candidat introuvable. Contactez l'administrateur."];
            }
            
            if ($candidate['statut'] === 'en_attente') {
                return ["⏳ Votre candidature est en attente de validation par un administrateur."];
            }
            
            if ($candidate['statut'] === 'refuse') {
                return ["❌ Votre candidature a été refusée. Contactez l'administrateur."];
            }
            
            return [];
        } catch (\Exception $e) {
            return ["Erreur lors de la vérification du statut candidat."];
        }
    }
    
    /**
     * Crée une nouvelle session utilisateur
     */
    public function createSession(User $user): void {
        $_SESSION['id_utilisateur'] = $user->getId();
        $_SESSION['useremail'] = $user->getEmail();
        $_SESSION['pseudo'] = $user->getPseudo();
        $_SESSION['type'] = $user->getType();
    }
    
    /**
     * Détruit la session
     */
    public static function destroySession(): void {
        session_destroy();
        session_start();
        $_SESSION = [];
    }
    
    /**
     * Vérifie si un utilisateur est authentifié
     */
    public static function isAuthenticated(): bool {
        return isset($_SESSION['id_utilisateur']) && !empty($_SESSION['id_utilisateur']);
    }
    
    /**
     * Récupère l'ID de l'utilisateur authentifié
     */
    public static function getAuthenticatedUserId(): ?int {
        return $_SESSION['id_utilisateur'] ?? null;
    }
    
    /**
     * Récupère le type de l'utilisateur authentifié
     */
    public static function getAuthenticatedUserType(): ?string {
        return $_SESSION['type'] ?? null;
    }

    /**
     * Récupère l'email de l'utilisateur authentifié
     */
    public static function getAuthenticatedUserEmail(): ?string {
        return $_SESSION['useremail'] ?? null;
    }


    
    /**
     * Vérifie si l'utilisateur est admin
     */
    public static function isAdmin(): bool {
        return self::getAuthenticatedUserType() === 'admin';
    }
    
    /**
     * Vérifie si l'utilisateur est candidat
     */
    public static function isCandidate(): bool {
        return self::getAuthenticatedUserType() === 'candidat';
    }
}
?>
