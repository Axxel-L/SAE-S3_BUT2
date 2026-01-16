<?php
/**
 * Gestion du hachage et vérification des mots de passe
 */
class PasswordManager {
    private const HASH_ALGO = 'sha256';
    private const SALT_LENGTH = 16;
    
    /**
     * Génère un salt aléatoire
     */
    public static function generateSalt(): string {
        return bin2hex(random_bytes(self::SALT_LENGTH));
    }
    
    /**
     * Hash un mot de passe avec le salt
     */
    public static function hashPassword(string $password, string $salt): string {
        return hash(self::HASH_ALGO, $password . $salt);
    }
    
    /**
     * Vérifie un mot de passe
     */
    public static function verifyPassword(string $password, string $hash, string $salt): bool {
        $computedHash = self::hashPassword($password, $salt);
        return hash_equals($computedHash, $hash);
    }
    
    /**
     * Valide la complexité d'un mot de passe
     */
    public static function validateComplexity(string $password): array {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au minimum 8 caractères";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        return $errors;
    }
}
?>
