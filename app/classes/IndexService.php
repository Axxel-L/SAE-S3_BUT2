<?php
/**
 * Gère la logique de la page d'accueil
 */
class IndexService {
    private $db;
    private $validator;
    private $auditLogger;
    public function __construct($db, $validator, $auditLogger) {
        $this->db = $db;
        $this->validator = $validator;
        $this->auditLogger = $auditLogger;
    }
    
    /**
     * Récupère les jeux en vedette pour l'accueil
     * @param int $limit Nombre de jeux à retourner
     * @return array
     */
    public function getFeaturedGames(int $limit = 6): array {
        try {
            $stmt = $this->db->prepare("
                SELECT j.*,
                COUNT(DISTINCT com.id_commentaire) as nb_comments,
                COUNT(DISTINCT bc.id_bulletin) as nb_votes_cat,
                COUNT(DISTINCT bf.id_bulletin) as nb_votes_final
                FROM jeu j
                LEFT JOIN commentaire com ON j.id_jeu = com.id_jeu
                LEFT JOIN bulletin_categorie bc ON j.id_jeu = bc.id_jeu
                LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu
                GROUP BY j.id_jeu
                ORDER BY (COUNT(DISTINCT bc.id_bulletin) + COUNT(DISTINCT bf.id_bulletin)) DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("IndexService Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère l'événement actif
     * @return array|null
     */
    public function getActiveEvent(): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM evenement
                WHERE statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final')
                ORDER BY date_ouverture DESC
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("IndexService getActiveEvent Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les statistiques globales du site
     * @return array ['nb_games' => int, 'nb_users' => int, 'nb_votes' => int, 'nb_comments' => int]
     */
    public function getGlobalStats(): array {
        $stats = [
            'nb_games' => 0,
            'nb_users' => 0,
            'nb_votes' => 0,
            'nb_comments' => 0
        ];
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jeu");
            $stmt->execute();
            $stats['nb_games'] = (int)$stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateur");
            $stmt->execute();
            $stats['nb_users'] = (int)$stmt->fetchColumn();
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM (
                    SELECT id_bulletin FROM bulletin_categorie
                    UNION ALL
                    SELECT id_bulletin FROM bulletin_final
                ) as votes
            ");
            $stmt->execute();
            $stats['nb_votes'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM commentaire");
            $stmt->execute();
            $stats['nb_comments'] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("IndexService getGlobalStats Error: " . $e->getMessage());
        }
        return $stats;
    }
    
    /**
     * Envoie un message de contact
     * @param string $name Nom complet
     * @param string $email Email
     * @param string $subject Sujet
     * @param string $message Message
     * @return array ['success' => bool, 'errors' => []]
     */
    public function sendContactMessage(string $name, string $email, string $subject, string $message): array {
        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        }
        if (empty($subject) || strlen($subject) < 3) {
            $errors[] = "Le sujet doit contenir au moins 3 caractères";
        }
        if (empty($message) || strlen($message) < 10) {
            $errors[] = "Le message doit contenir au moins 10 caractères";
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars(trim($email), ENT_QUOTES, 'UTF-8');
            $subject = htmlspecialchars(trim($subject), ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');
            
            $to = 'contact@gamecrown.fr';
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            $fullMessage = "Nom: $name\n";
            $fullMessage .= "Email: $email\n";
            $fullMessage .= "---\n\n";
            $fullMessage .= $message;
            
            $success = mail($to, "[GameCrown] $subject", $fullMessage, $headers);
            if ($success) {
                return ['success' => true, 'errors' => []];
            } else {
                return ['success' => false, 'errors' => ["Erreur lors de l'envoi du message. Veuillez réessayer."]];
            }
        } catch (Exception $e) {
            error_log("IndexService sendContactMessage Error: " . $e->getMessage());
            return ['success' => false, 'errors' => ["Erreur serveur: " . $e->getMessage()]];
        }
    }
}
?>