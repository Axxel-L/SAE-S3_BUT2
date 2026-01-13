<?php
/**
 * HeaderService.php - CORRIGÉ
 * 
 * Gère la logique de l'en-tête (navbar)
 * - Mise à jour des statuts événements
 * - Données d'authentification
 * - Menu items dynamiques
 * - Labels utilisateurs
 */





class HeaderService {
    
    private $db;
    private $authService;
    
    public function __construct($db, $authService) {
        $this->db = $db;
        $this->authService = $authService;
    }
    
    /**
     * Met à jour les statuts des événements selon les dates
     * 
     * @return bool
     */
    public function updateEventStatuses(): bool {
        try {
            // Essayer via procédure stockée
            $this->db->query("CALL update_event_statuts()");
            return true;
        } catch (Exception $e) {
            // Fallback: mise à jour manuelle
            return $this->updateEventStatusesFallback();
        }
    }
    
    /**
     * Fallback pour mise à jour des statuts (sans procédure stockée)
     * 
     * @return bool
     */
    private function updateEventStatusesFallback(): bool {
        try {
            // Ouvert catégories
            $stmt = $this->db->prepare("
                UPDATE evenement
                SET statut = 'ouvert_categories'
                WHERE statut = 'preparation'
                AND NOW() >= date_ouverture
                AND NOW() < date_fermeture
            ");
            $stmt->execute();
            
            // Fermé catégories
            $stmt = $this->db->prepare("
                UPDATE evenement
                SET statut = 'ferme_categories'
                WHERE statut = 'ouvert_categories'
                AND NOW() >= date_fermeture
                AND (date_debut_vote_final IS NULL OR NOW() < date_debut_vote_final)
            ");
            $stmt->execute();
            
            // Ouvert final
            $stmt = $this->db->prepare("
                UPDATE evenement
                SET statut = 'ouvert_final'
                WHERE statut IN ('ouvert_categories', 'ferme_categories')
                AND date_debut_vote_final IS NOT NULL
                AND NOW() >= date_debut_vote_final
                AND NOW() < date_fermeture_vote_final
            ");
            $stmt->execute();
            
            // Clôturé
            $stmt = $this->db->prepare("
                UPDATE evenement
                SET statut = 'cloture'
                WHERE statut IN ('ouvert_categories', 'ferme_categories', 'ouvert_final')
                AND date_fermeture_vote_final IS NOT NULL
                AND NOW() >= date_fermeture_vote_final
            ");
            $stmt->execute();
            
            // Clôturé sans vote final
            $stmt = $this->db->prepare("
                UPDATE evenement
                SET statut = 'cloture'
                WHERE statut = 'ferme_categories'
                AND date_debut_vote_final IS NULL
                AND NOW() >= DATE_ADD(date_fermeture, INTERVAL 1 DAY)
            ");
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("HeaderService updateEventStatusesFallback Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les données de connexion de l'utilisateur
     * 
     * @return array ['isLogged' => bool, 'userType' => string, 'userId' => int|null]
     */
    public function getAuthenticationData(): array {
        return [
            'isLogged' => $this->authService::isAuthenticated(),
            'userType' => $this->authService::getAuthenticatedUserType() ?? 'visiteur',
            'userId' => $this->authService::getAuthenticatedUserId()
        ];
    }
    
    /**
     * Récupère les items de menu selon le type d'utilisateur
     * 
     * ✅ CORRIGÉ: Accepte ?string = null et convertit en 'visiteur' par défaut
     * 
     * @param ?string $userType Type d'utilisateur (null converti en 'visiteur')
     * @param bool $isMobile Format mobile ou desktop
     * @return array
     */
    public function getMenuItems(?string $userType = null, bool $isMobile = false): array {
        // ✅ FIX: Convertir null en 'visiteur'
        $userType = $userType ?? 'visiteur';
        
        $items = [];
        
        // Menu public (non connecté)
        if ($userType === 'visiteur' || $userType === '') {
            $items['home'] = [
                'label' => 'Accueil',
                'url' => 'index.php',
                'icon' => 'fa-home',
                'visible' => true
            ];
            $items['presentation'] = [
                'label' => 'Présentation',
                'url' => 'index.php#presentation',
                'icon' => 'fa-info-circle',
                'visible' => true
            ];
            $items['scrutin'] = [
                'label' => 'Mode de scrutin',
                'url' => 'index.php#scrutin',
                'icon' => 'fa-award',
                'visible' => true
            ];
        }
        
        // Menu résultats (pour tous)
        $items['results'] = [
            'label' => 'Résultats',
            'url' => 'resultats.php',
            'icon' => 'fa-trophy',
            'visible' => true
        ];
        
        // Menu joueur
        if ($userType === 'joueur') {
            $items['events'] = [
                'label' => 'Événements',
                'url' => 'joueur-events.php',
                'icon' => 'fa-calendar-alt',
                'visible' => true
            ];
            $items['games'] = [
                'label' => 'Salon des jeux',
                'url' => 'salon-jeux.php',
                'icon' => 'fa-calendar-alt',
                'visible' => true
            ];
            $items['vote_cat'] = [
                'label' => 'Vote Catégories',
                'url' => 'vote.php',
                'icon' => 'fa-vote-yea',
                'visible' => true
            ];
            $items['vote_final'] = [
                'label' => 'Vote Final',
                'url' => 'vote-final.php',
                'icon' => 'fa-crown',
                'visible' => true
            ];
            $items['dashboard'] = [
                'label' => 'Mon Espace',
                'url' => 'dashboard.php',
                'icon' => 'fa-user-circle',
                'visible' => true
            ];
        }
        
        // Menu admin
        if ($userType === 'admin') {
            $items['admin_events'] = [
                'label' => 'Événements',
                'url' => 'admin-events.php',
                'icon' => 'fa-calendar',
                'visible' => true
            ];
            $items['admin_candidatures'] = [
                'label' => 'Participations',
                'url' => 'admin-candidatures.php',
                'icon' => 'fa-tags',
                'visible' => true
            ];
            $items['admin_users'] = [
                'label' => 'Utilisateurs',
                'url' => 'admin-utilisateurs.php',
                'icon' => 'fa-users',
                'visible' => true
            ];
            $items['admin_candidates'] = [
                'label' => 'Candidatures',
                'url' => 'admin-candidats.php',
                'icon' => 'fa-star',
                'visible' => true
            ];
            $items['admin_logs'] = [
                'label' => 'Logs',
                'url' => 'admin-logs.php',
                'icon' => 'fa-clipboard-list',
                'visible' => true
            ];
        }
        
        // Menu candidat
        if ($userType === 'candidat') {
            $items['candidat_profile'] = [
                'label' => 'Mon Profil',
                'url' => 'candidat-profil.php',
                'icon' => 'fa-crown',
                'visible' => true
            ];
            $items['candidat_campaign'] = [
                'label' => 'Campagne',
                'url' => 'candidat-campagne.php',
                'icon' => 'fa-bullhorn',
                'visible' => true
            ];
            $items['candidat_stats'] = [
                'label' => 'Statistiques',
                'url' => 'candidat-statistiques.php',
                'icon' => 'fa-chart-bar',
                'visible' => true
            ];
            $items['candidat_events'] = [
                'label' => 'Événements',
                'url' => 'candidat-events.php',
                'icon' => 'fa-calendar-check',
                'visible' => true
            ];
        }
        
        return $items;
    }
    
    /**
     * Récupère le label du type d'utilisateur
     * 
     * @param ?string $userType Type d'utilisateur
     * @return string
     */
    public function getUserTypeLabel(?string $userType = null): string {
        // ✅ FIX: Convertir null en 'visiteur'
        $userType = $userType ?? 'visiteur';
        
        $labels = [
            'joueur' => 'Joueur',
            'admin' => 'Administrateur',
            'candidat' => 'Candidat',
            'visiteur' => 'Visiteur'
        ];
        
        return $labels[$userType] ?? ucfirst($userType);
    }
}

?>
