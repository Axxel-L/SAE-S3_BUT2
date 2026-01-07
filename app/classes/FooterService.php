<?php
/**
 * FooterService.php
 * 
 * Gère la logique métier du pied de page (footer)
 * - Informations de l'application
 * - Liens sociaux
 * - Données dynamiques
 * 
 * Single Responsibility: Logique métier footer
 */



class FooterService {
    
    /**
     * Récupère les informations de l'application
     * 
     * @return array ['name' => string, 'year' => int, 'copyright' => string]
     */
    public function getAppInfo(): array {
        return [
            'name' => 'GameCrown',
            'year' => date('Y'),
            'copyright' => '© ' . date('Y') . ' GameCrown. Tous droits réservés.'
        ];
    }
    
    /**
     * Récupère les liens sociaux
     * 
     * @return array
     */
    public function getSocialLinks(): array {
        return [
            'twitter' => [
                'url' => 'https://twitter.com/gamecrown',
                'icon' => 'fab fa-twitter',
                'label' => 'Twitter'
            ],
            'facebook' => [
                'url' => 'https://facebook.com/gamecrown',
                'icon' => 'fab fa-facebook-f',
                'label' => 'Facebook'
            ],
            'instagram' => [
                'url' => 'https://instagram.com/gamecrown',
                'icon' => 'fab fa-instagram',
                'label' => 'Instagram'
            ],
            'youtube' => [
                'url' => 'https://youtube.com/gamecrown',
                'icon' => 'fab fa-youtube',
                'label' => 'YouTube'
            ]
        ];
    }
    
    /**
     * Récupère les liens du footer
     * 
     * @return array
     */
    public function getFooterLinks(): array {
        return [
            'legal' => [
                'label' => 'Mentions légales',
                'url' => '#legal'
            ],
            'privacy' => [
                'label' => 'Politique de confidentialité',
                'url' => '#privacy'
            ],
            'terms' => [
                'label' => 'Conditions d\'utilisation',
                'url' => '#terms'
            ],
            'contact' => [
                'label' => 'Contact',
                'url' => 'index.php#contact'
            ]
        ];
    }
}

?>
