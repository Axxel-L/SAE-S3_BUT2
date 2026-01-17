<?php
require_once 'classes/init.php';

if (!isLogged()) {
    redirect('login.php', 'Vous devez vous connecter', 'error');
}

$id_utilisateur = getAuthUserId();
$error = '';
$success = '';
$events = [];
$voteService = ServiceContainer::getVoteService();
$db = ServiceContainer::getDatabase();

try {
    $db->query("CALL update_event_statuts()");
} catch (Exception $e) {}

try {
    $stmt = $db->prepare("
        SELECT * FROM evenement 
        WHERE statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final', 'cloture')
        ORDER BY date_ouverture DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Erreur lors de la récupération des événements';
    $events = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_final') {
    $result = $voteService->voteFinalVote(
        $id_utilisateur,
        intval($_POST['id_jeu'] ?? 0),
        intval($_POST['id_evenement'] ?? 0)
    );
    
    if ($result['success']) {
        $success = '✓ Vote enregistré avec succès !';
        redirect($_SERVER['REQUEST_URI'], 'Vote enregistré!', 'success');
    } else {
        $error = implode(' | ', $result['errors']);
    }
}

$statut_config = [
    'preparation' => [
        'label' => 'Préparation',
        'bg' => 'bg-yellow-500/20',
        'text' => 'text-yellow-400',
        'border' => 'border-yellow-500/30',
        'icon' => 'fa-hourglass-start',
        'can_vote_final' => false
    ],
    'ouvert_categories' => [
        'label' => 'Vote Catégories',
        'bg' => 'bg-green-500/20',
        'text' => 'text-green-400',
        'border' => 'border-green-500/30',
        'icon' => 'fa-vote-yea',
        'can_vote_final' => false
    ],
    'ferme_categories' => [
        'label' => 'Attente Vote Final',
        'bg' => 'bg-blue-500/20',
        'text' => 'text-blue-400',
        'border' => 'border-blue-500/30',
        'icon' => 'fa-pause-circle',
        'can_vote_final' => false
    ],
    'ouvert_final' => [
        'label' => 'Vote Final',
        'bg' => 'bg-purple-500/20',
        'text' => 'text-purple-400',
        'border' => 'border-purple-500/30',
        'icon' => 'fa-crown',
        'can_vote_final' => true
    ],
    'cloture' => [
        'label' => 'Clôturé',
        'bg' => 'bg-red-500/20',
        'text' => 'text-red-400',
        'border' => 'border-red-500/30',
        'icon' => 'fa-times-circle',
        'can_vote_final' => false
    ]
];

require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-crown text-accent mr-3"></i>Vote par catégories
            </h1>
            <p class="text-xl text-light-80">Phase 1 : Élisez le jeu de la catégorie</p>
        </div>
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3 max-w-2xl mx-auto">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3 max-w-2xl mx-auto">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center max-w-2xl mx-auto">
                <i class="fas fa-calendar-times text-5xl text-light/20 mb-6"></i>
                <h2 class="text-2xl font-bold text-light mb-3">Aucun événement disponible</h2>
                <p class="text-light-80">Les votes ne sont pas encore ouverts. Revenez bientôt !</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): 
                $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                
                // Vérifie inscription
                try {
                    $stmt = $db->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                    $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                    $isRegistered = $stmt->rowCount() > 0;
                } catch (Exception $e) { 
                    $isRegistered = false; 
                }
                
                // Récupére les finalistes
                try {
                    $stmt = $db->prepare("
                        SELECT j.* 
                        FROM jeu j
                        JOIN finaliste f ON j.id_jeu = f.id_jeu
                        WHERE f.id_evenement = ?
                        ORDER BY j.titre ASC
                    ");
                    $stmt->execute([$event['id_evenement']]);
                    $finalistes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (Exception $e) { 
                    $finalistes = []; 
                }
                
                // Vérifie si l'utilisateur a déjà voté au final
                try {
                    $stmt = $db->prepare("
                        SELECT id_emargement FROM emargement_final 
                        WHERE id_utilisateur = ? AND id_evenement = ?
                    ");
                    $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                    $dejaVoteFinal = $stmt->rowCount() > 0;
                } catch (Exception $e) { 
                    $dejaVoteFinal = false; 
                }
            ?>
                <div class="glass-card rounded-3xl modern-border border-2 border-white/10 mb-8 overflow-hidden">
                    <div class="p-6 border-b border-white/10 bg-white/5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold font-orbitron text-light mb-2">
                                    <?php echo htmlspecialchars($event['nom']); ?>
                                </h2>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?> border <?php echo $status['border']; ?>">
                                        <i class="fas <?php echo $status['icon']; ?> mr-1"></i><?php echo $status['label']; ?>
                                    </span>
                                    <?php if ($isRegistered): ?>
                                        <span class="px-3 py-1 rounded-full text-xs bg-green-500/20 text-green-400 border border-green-500/30">
                                            <i class="fas fa-check mr-1"></i>Inscrit
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (!$isRegistered): ?>
                            <div class="p-6 rounded-2xl bg-yellow-500/10 border border-yellow-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl mb-3"></i>
                                <p class="text-yellow-400 text-lg mb-4">Vous devez vous inscrire pour voter !</p>
                                <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </a>
                            </div>

                        <?php elseif (!$status['can_vote_final']): ?>
                            <div class="p-6 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-center">
                                <i class="fas fa-info-circle text-blue-400 text-3xl mb-3"></i>
                                <p class="text-blue-400 text-lg">
                                    <?php if ($event['statut'] === 'preparation'): ?>
                                        Le vote final n'est pas encore accessible.
                                        <br><small class="block mt-2">Du <?php echo date('d/m/Y', strtotime($event['date_ouverture'])); ?> au <?php echo date('d/m/Y', strtotime($event['date_fermeture'])); ?></small>
                                    <?php elseif ($event['statut'] === 'ouvert_categories'): ?>
                                        Le vote par catégories est en cours.
                                        <br><small class="block mt-2">Ouverture du vote final le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?></small>
                                    <?php elseif ($event['statut'] === 'ferme_categories'): ?>
                                        Attente du vote final
                                        <br><small class="block mt-2">Ouverture le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?></small>
                                    <?php else: ?>
                                        Les résultats sont disponibles.
                                    <?php endif; ?>
                                </p>
                            </div>
                        
                        <!-- Vote final ouvert -->
                        <?php else: ?>
                            <?php if (empty($finalistes)): ?>
                                <div class="p-6 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-center">
                                    <i class="fas fa-gamepad text-blue-400 text-3xl mb-3"></i>
                                    <p class="text-blue-400 text-lg">Aucun finaliste pour le moment.</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-6">
                                    <h3 class="text-xl font-bold text-light mb-4">
                                        <?php if ($dejaVoteFinal): ?>
                                            ✓ Vous avez voté
                                        <?php else: ?>
                                            Sélectionnez votre jeu préféré
                                        <?php endif; ?>
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($finalistes as $jeu): ?>
                                            <div class="group relative glass-card rounded-2xl overflow-hidden hover:border-accent/50 transition-all border border-white/10 <?php echo $dejaVoteFinal ? 'opacity-75' : ''; ?>">
                                                <div class="h-40 bg-accent/10">
                                                    <?php if ($jeu['image']): ?>
                                                        <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <i class="fas fa-gamepad text-4xl text-light/20"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="p-4">
                                                    <h4 class="font-bold text-light mb-1 truncate"><?php echo htmlspecialchars($jeu['titre']); ?></h4>
                                                    <?php if ($jeu['editeur']): ?>
                                                        <p class="text-xs text-light-80 mb-3"><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                                    <?php endif; ?>
                                                    <div class="flex gap-2">
                                                        <?php if (!$dejaVoteFinal): ?>
                                                            <form method="POST" class="flex-1">
                                                                <input type="hidden" name="action" value="vote_final">
                                                                <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                                                <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                                                <button type="submit" onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> en tant que Jeu de l\'Année?');" 
                                                                    class="w-full py-2 rounded-xl bg-accent/20 text-accent text-sm font-medium hover:bg-accent hover:text-dark transition-colors flex items-center justify-center gap-1 border border-white/10">
                                                                    <i class="fas fa-crown"></i> Voter
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="jeu-campagne.php?id=<?php echo $jeu['id_jeu']; ?>" 
                                                           class="px-3 py-2 rounded-xl bg-white/5 text-light-80 hover:text-light hover:bg-white/10 transition-all border border-white/10" 
                                                           title="Voir la campagne">
                                                            <i class="fas fa-comments"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'footer.php'; ?>