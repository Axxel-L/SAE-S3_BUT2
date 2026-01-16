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
$auditLogger = ServiceContainer::getAuditLogger();
$db = ServiceContainer::getDatabase();

try {
    $db->query("CALL update_event_statuts()");
} catch (Exception $e) {}

try {
    $stmt = $db->prepare("
        SELECT * FROM evenement 
        WHERE statut IN ('ouvert_categories', 'ferme_categories', 'ouvert_final', 'cloture')
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
        $success = '✓ Vote enregistré avec succès ! 👑';
        $auditLogger->logFinalVote($id_utilisateur, intval($_POST['id_jeu']), intval($_POST['id_evenement']));
    } else {
        $error = implode(' | ', $result['errors']);
    }
}

$statut_config = [
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
                <i class="fas fa-crown text-accent mr-3"></i>Vote Final
            </h1>
            <p class="text-xl text-light-80">Phase 2 : Élisez le Jeu de l'Année</p>
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
                <p class="text-light-80">Le vote final n'est pas encore accessible.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): 
                $status = $statut_config[$event['statut']] ?? $statut_config['ferme_categories'];
                try {
                    $stmt = $db->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                    $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                    $isRegistered = $stmt->rowCount() > 0;
                } catch (Exception $e) { 
                    $isRegistered = false; 
                }
                
                $finalistes = [];
                try {
                    $stmt = $db->prepare("
                        SELECT j.*, 
                               COUNT(DISTINCT bf.id_bulletin_final) as nb_voix_final,
                               (SELECT GROUP_CONCAT(DISTINCT c.nom SEPARATOR ', ') 
                                FROM bulletin_categorie bc2 
                                JOIN categorie c ON bc2.id_categorie = c.id_categorie 
                                WHERE bc2.id_jeu = j.id_jeu AND bc2.id_evenement = ?) as categories
                        FROM jeu j
                        LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu AND bf.id_evenement = ?
                        WHERE j.id_jeu IN (SELECT DISTINCT id_jeu FROM bulletin_categorie WHERE id_evenement = ?)
                        GROUP BY j.id_jeu
                        ORDER BY nb_voix_final DESC, j.titre ASC
                    ");
                    $stmt->execute([$event['id_evenement'], $event['id_evenement'], $event['id_evenement']]);
                    $finalistes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (Exception $e) { 
                    $finalistes = []; 
                }
                
                try {
                    $stmt = $db->prepare("SELECT id_emargement_final FROM emargement_final WHERE id_utilisateur = ? AND id_evenement = ?");
                    $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                    $dejaVoteFinal = $stmt->rowCount() > 0;
                } catch (Exception $e) { 
                    $dejaVoteFinal = false; 
                }
            ?>
                <div class="glass-card rounded-3xl p-8 mb-8 modern-border border-2 border-white/10 <?php echo $status['can_vote_final'] ? 'border-purple-500/50' : ''; ?>">
                    <div class="mb-8">
                        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                            <div>
                                <h2 class="text-3xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h2>
                                <?php if ($event['description']): ?>
                                    <p class="text-light-80"><?php echo htmlspecialchars($event['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?> border <?php echo $status['border']; ?>">
                                    <i class="fas <?php echo $status['icon']; ?> mr-2"></i><?php echo $status['label']; ?>
                                </span>
                                <?php if ($dejaVoteFinal): ?>
                                    <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-sm">
                                        <i class="fas fa-check mr-1"></i>Vous avez voté
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-layer-group text-light-80"></i>
                                    <span class="font-bold text-light">Phase 1 : Vote Catégories</span>
                                    <?php if ($event['statut'] !== 'ouvert_categories'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-500/50 text-light-80">TERMINÉ</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-light-80">
                                    Du <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                    au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                </p>
                            </div>
                            <div class="p-4 rounded-2xl <?php echo $status['can_vote_final'] ? 'bg-purple-500/20 border-purple-500/50' : 'bg-white/5 border-white/10'; ?> border-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-crown <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light-80'; ?>"></i>
                                    <span class="font-bold <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light'; ?>">Phase 2 : Vote Final</span>
                                    <?php if ($status['can_vote_final']): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-purple-500 text-light font-bold animate-pulse">EN COURS</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($event['date_debut_vote_final'])): ?>
                                    <p class="text-sm text-light-80">
                                        Du <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                        au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!$isRegistered): ?>
                        <div class="p-6 rounded-2xl bg-yellow-500/10 border border-yellow-500/30 text-center">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl mb-3"></i>
                            <p class="text-yellow-400 text-lg mb-4">Vous devez vous inscrire pour voter.</p>
                            <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </a>
                        </div>
                    
                    <!-- Vote final pas ouvert -->
                    <?php elseif (!$status['can_vote_final']): ?>
                        <div class="p-8 rounded-2xl bg-purple-500/10 border border-purple-500/30 text-center">
                            <?php if ($event['statut'] === 'ouvert_categories'): ?>
                                <i class="fas fa-hourglass-half text-purple-400 text-4xl mb-4"></i>
                                <h3 class="text-2xl font-bold text-purple-400 mb-3">Vote final pas encore disponible</h3>
                                <p class="text-light-80 mb-4">Le vote par catégories est en cours.</p>
                                <p class="text-purple-300">Ouverture du vote final le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?></p>
                                <a href="vote.php" class="inline-flex items-center gap-2 px-6 py-3 mt-4 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                                    <i class="fas fa-vote-yea"></i> Voter par catégorie
                                </a>
                            <?php elseif ($event['statut'] === 'ferme_categories'): ?>
                                <i class="fas fa-clock text-purple-400 text-4xl mb-4 animate-pulse"></i>
                                <h3 class="text-2xl font-bold text-purple-400 mb-3">Bientôt le vote final !</h3>
                                <p class="text-light-80">Ouverture le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?></p>
                            <?php elseif ($event['statut'] === 'cloture'): ?>
                                <i class="fas fa-flag-checkered text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-2xl font-bold text-gray-400 mb-3">Événement terminé</h3>
                                <p class="text-light-80">Les résultats sont disponibles.</p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($finalistes)): ?>
                            <div class="mt-8">
                                <h3 class="text-xl font-bold font-orbitron text-purple-400 mb-4"><i class="fas fa-trophy mr-2"></i>Finalistes</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($finalistes as $jeu): ?>
                                        <div class="glass-card rounded-2xl p-4 modern-border border border-white/10 opacity-75 hover:opacity-100 transition-opacity">
                                            <?php if ($jeu['image']): ?>
                                                <div class="mb-3 rounded-xl overflow-hidden h-32 bg-black/50">
                                                    <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover">
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="font-bold text-light"><?php echo htmlspecialchars($jeu['titre']); ?></h4>
                                            <?php if ($jeu['editeur']): ?>
                                                <p class="text-xs text-light-80 mt-1"><i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($jeu['categories']): ?>
                                                <p class="text-xs text-purple-300 mt-1"><i class="fas fa-tags mr-1"></i><?php echo htmlspecialchars($jeu['categories']); ?></p>
                                            <?php endif; ?>
                                            <div class="mt-3 text-center">
                                                <div class="text-lg font-bold text-purple-400"><?php echo intval($jeu['nb_voix_final']); ?></div>
                                                <div class="text-xs text-light-80">voix actuelles</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    
                    <!-- Vote final ouvert -->
                    <?php else: ?>
                        <?php if (empty($finalistes)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-gamepad text-4xl text-light/20 mb-4"></i>
                                <p class="text-light-80">Aucun finaliste pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <h3 class="text-xl font-bold font-orbitron text-purple-400 mb-6"><i class="fas fa-trophy mr-2"></i>Sélectionnez le Jeu de l'Année</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($finalistes as $jeu): ?>
                                    <form method="POST" class="flex">
                                        <input type="hidden" name="action" value="vote_final">
                                        <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                        <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                        <button type="submit" class="w-full group" <?php echo $dejaVoteFinal ? 'disabled' : ''; ?> onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> ?');">
                                            <div class="glass-card rounded-3xl p-6 h-full modern-border border-2 border-white/10 hover:border-purple-500/50 transition-all duration-300 <?php echo $dejaVoteFinal ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                                <?php if ($jeu['image']): ?>
                                                    <div class="mb-4 rounded-xl overflow-hidden h-40 bg-black/50">
                                                        <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mb-4 rounded-xl h-40 bg-white/5 flex items-center justify-center">
                                                        <i class="fas fa-gamepad text-4xl text-light/30"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <h3 class="text-xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($jeu['titre']); ?></h3>
                                                <?php if ($jeu['editeur']): ?>
                                                    <p class="text-sm text-light-80 mb-2"><i class="fas fa-building mr-1 text-accent"></i><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($jeu['categories']): ?>
                                                    <p class="text-xs text-purple-300 mb-3"><i class="fas fa-tags mr-1"></i><?php echo htmlspecialchars($jeu['categories']); ?></p>
                                                <?php endif; ?>
                                                
                                                <div class="my-4 p-3 rounded-lg bg-purple-500/10 border border-purple-500/20 text-center">
                                                    <div class="text-2xl font-bold text-purple-400"><?php echo intval($jeu['nb_voix_final']); ?></div>
                                                    <div class="text-xs text-light-80">voix</div>
                                                </div>
                                                <?php if (!$dejaVoteFinal): ?>
                                                    <div class="mt-4 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-crown text-purple-400 mr-2"></i>
                                                        <span class="text-purple-400 text-sm font-medium">Voter</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mt-4 text-center text-green-400 text-sm"><i class="fas fa-check mr-1"></i>Vote enregistré</div>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="text-center mt-8">
            <a href="vote.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-light/10 text-light hover:bg-light/20 transition-colors border border-white/10">
                <i class="fas fa-arrow-left"></i> Retour aux votes par catégories
            </a>
        </div>
    </div>
</section>
<?php require_once 'footer.php'; ?>