<?php
/**
 * Service de validation des données
 */
class ValidationService {
    /**
     * Valide un email
     */
    public static function validateEmail(string $email): array {
        $errors = [];
        if (empty($email)) {
            $errors[] = "L'email est requis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email est invalide";
        }
        return $errors;
    }
    
    /**
     * Valide un pseudo
     */
    public static function validatePseudo(string $pseudo, int $minLength = 3, int $maxLength = 30): array {
        $errors = [];
        if (empty($pseudo)) {
            $errors[] = "Le pseudo est requis";
        } elseif (strlen($pseudo) < $minLength) {
            $errors[] = "Le pseudo doit contenir au minimum $minLength caractères";
        } elseif (strlen($pseudo) > $maxLength) {
            $errors[] = "Le pseudo doit contenir au maximum $maxLength caractères";
        }
        return $errors;
    }
    
    /**
     * Valide un mot de passe
     */
    public static function validatePassword(string $password, string $confirm = ''): array {
        $errors = [];
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au minimum 8 caractères";
        }
        if (!empty($confirm) && $password !== $confirm) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        return $errors;
    }
    
    /**
     * Valide une URL
     */
    public static function validateUrl(string $url): array {
        $errors = [];
        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "L'URL est invalide: $url";
        }
        return $errors;
    }
    
    /**
     * Valide un texte
     */
    public static function validateText(string $text, int $minLength = 1, int $maxLength = 5000): array {
        $errors = [];
        if (empty($text)) {
            $errors[] = "Le texte est requis";
        } elseif (strlen($text) < $minLength) {
            $errors[] = "Le texte doit contenir au minimum $minLength caractères";
        } elseif (strlen($text) > $maxLength) {
            $errors[] = "Le texte doit contenir au maximum $maxLength caractères";
        }
        return $errors;
    }
    
    /**
     * Valide un nombre entier
     */
    public static function validateInteger(mixed $value, ?int $min = null, ?int $max = null): array {
        $errors = [];
        $intValue = intval($value);
        if ($intValue === 0 && $value !== 0 && $value !== '0') {
            $errors[] = "La valeur doit être un nombre entier valide";
        } elseif ($min !== null && $intValue < $min) {
            $errors[] = "La valeur doit être au minimum $min";
        } elseif ($max !== null && $intValue > $max) {
            $errors[] = "La valeur doit être au maximum $max";
        }
        return $errors;
    }
    
    /**
     * Sanitize un email
     */
    public static function sanitizeEmail(string $email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize du texte HTML
     */
    public static function sanitizeHtml(string $text): string {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize une URL
     */
    public static function sanitizeUrl(string $url): string {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
}
?>
