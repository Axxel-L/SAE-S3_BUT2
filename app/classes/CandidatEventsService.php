<?php


class CandidatEventsService
{
    private DatabaseConnection $db;
    private UserService $userService;
    private AuditLogger $auditLogger;

    public function __construct(DatabaseConnection $db, UserService $userService, AuditLogger $auditLogger)
    {
        $this->db = $db;
        $this->userService = $userService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Récupère les informations du candidat
     */
    public function getCandidatData(int $candidatId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.email, j.titre as jeu_titre, j.image as jeu_image
                FROM candidat c
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                WHERE c.id_candidat = ?
            ");
            $stmt->execute([$candidatId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->auditLogger->log('ERROR', "Erreur récupération candidat: " . $e->getMessage());
            throw new \Exception("Erreur lors de la récupération des données du candidat");
        }
    }

    /**
     * Récupère les événements en préparation avec ses catégories et candidatures
     */
    public function getEventsWithDetails(int $candidatId): array
    {
        try {
            $events = [];
            
            // Récupérer les événements
            $stmt = $this->db->prepare("
                SELECT 
                    e.id_evenement, 
                    e.nom as titre, 
                    e.description, 
                    e.date_ouverture as date_debut, 
                    e.date_fermeture as date_fin, 
                    e.statut as etat
                FROM evenement e
                WHERE e.statut = 'preparation'
                ORDER BY e.date_ouverture ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Enrichir avec catégories et candidatures
            foreach ($events as &$event) {
                // Récupérer les catégories
                $stmt = $this->db->prepare("
                    SELECT c.id_categorie, c.nom, c.description
                    FROM categorie c
                    WHERE c.id_evenement = ?
                    ORDER BY c.nom ASC
                ");
                $stmt->execute([$event['id_evenement']]);
                $event['categories'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Récupérer les candidatures existantes
                $event['mes_candidatures'] = $this->getCandidaturesByEventAndCandidat(
                    $event['id_evenement'],
                    $candidatId
                );
            }
            unset($event);

            return $events;
        } catch (\Exception $e) {
            $this->auditLogger->log('ERROR', "Erreur récupération événements: " . $e->getMessage());
            throw new \Exception("Erreur lors du chargement des événements");
        }
    }

    /**
     * Récupère les candidatures existantes pour un événement et un candidat
     */
    private function getCandidaturesByEventAndCandidat(int $eventId, int $candidatId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ec.id_event_candidat,
                    ec.id_categorie, 
                    ec.statut_candidature, 
                    cat.nom as categorie_nom
                FROM event_candidat ec
                LEFT JOIN categorie cat ON ec.id_categorie = cat.id_categorie
                WHERE ec.id_candidat = ? AND ec.id_evenement = ?
            ");
            $stmt->execute([$candidatId, $eventId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Silencieusement ignorer les erreurs de structure
            return [];
        }
    }

    /**
     * Soumettre une candidature
     */
    public function submitApplication(
        int $candidatId,
        int $eventId,
        int $categoryId,
        string $gameTitle
    ): void {
        try {
            // Vérifier que l'événement est en préparation
            $stmt = $this->db->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
            $stmt->execute([$eventId]);
            $evt = $stmt->fetch();
            if (!$evt || $evt['statut'] !== 'preparation') {
                throw new \Exception("Cet événement n'accepte plus les candidatures.");
            }

            // Vérifier que la catégorie appartient à cet événement
            $stmt = $this->db->prepare("
                SELECT id_categorie, nom FROM categorie 
                WHERE id_categorie = ? AND id_evenement = ?
            ");
            $stmt->execute([$categoryId, $eventId]);
            $categorie = $stmt->fetch();
            if (!$categorie) {
                throw new \Exception("Cette catégorie n'existe pas pour cet événement.");
            }

            // Vérifier qu'il n'y a pas déjà une candidature
            $stmt = $this->db->prepare("
                SELECT id_event_candidat 
                FROM event_candidat
                WHERE id_evenement = ? 
                AND id_categorie = ? 
                AND id_candidat = ?
            ");
            $stmt->execute([$eventId, $categoryId, $candidatId]);
            if ($stmt->rowCount() > 0) {
                throw new \Exception("Vous avez déjà postulé à cette catégorie !");
            }

            // Créer la candidature
            $stmt = $this->db->prepare("
                INSERT INTO event_candidat 
                (id_evenement, id_candidat, id_categorie, statut_candidature, date_inscription)
                VALUES (?, ?, ?, 'en_attente', NOW())
            ");
            $stmt->execute([$eventId, $candidatId, $categoryId]);

            // Logger l'action
            $this->auditLogger->log(
                'CANDIDATURE_SOUMISE',
                "Événement: $eventId, Catégorie: {$categorie['nom']}, Jeu: $gameTitle"
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Configuration des statuts avec leurs styles CSS
     */
    public static function getStatutConfig(string $statut = ''): array
    {
        $config = [
            'en_attente' => [
                'color' => 'yellow',
                'label' => 'En attente',
                'icon' => 'fa-hourglass-end',
                'class' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30'
            ],
            'approuve' => [
                'color' => 'green',
                'label' => 'Approuvée',
                'icon' => 'fa-check-circle',
                'class' => 'bg-green-500/10 text-green-400 border-green-500/30'
            ],
            'refuse' => [
                'color' => 'red',
                'label' => 'Refusée',
                'icon' => 'fa-times-circle',
                'class' => 'bg-red-500/10 text-red-400 border-red-500/30'
            ],
        ];

        return !empty($statut) && isset($config[$statut]) 
            ? $config[$statut] 
            : $config;
    }
}
