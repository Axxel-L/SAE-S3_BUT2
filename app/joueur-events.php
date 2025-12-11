<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'joueur') {
    header('Location: index.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// 🔄 Mettre à jour automatiquement les statuts
try {
    $connexion->query("CALL update_event_statuts()");
} catch (Exception $e) {}

// Récupérer les événements actifs
$events = [];
try {
    $stmt = $connexion->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM registre_electoral WHERE id_evenement = e.id_evenement AND id_utilisateur = ?) as is_registered
        FROM evenement e
        WHERE e.statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final')
        ORDER BY e.date_ouverture DESC
    ");
    $stmt->execute([$id_utilisateur]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Inscription à un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $id_evenement = intval($_POST['id_evenement'] ?? 0);
    
    try {
        $stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $event = $stmt->fetch();
        
        if (!$event || $event['statut'] === 'cloture') {
            throw new Exception("Cet événement n'accepte plus les inscriptions.");
        }
        
        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Vous êtes déjà inscrit !";
        } else {
            $stmt = $connexion->prepare("INSERT INTO registre_electoral (id_utilisateur, id_evenement, date_inscription) VALUES (?, ?, NOW())");
            $stmt->execute([$id_utilisateur, $id_evenement]);
            $success = "Inscription réussie ! ✅";
            
            // Rafraîchir
            $stmt = $connexion->prepare("
                SELECT e.*, 
                       (SELECT COUNT(*) FROM registre_electoral WHERE id_evenement = e.id_evenement AND id_utilisateur = ?) as is_registered
                FROM evenement e
                WHERE e.statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final')
                ORDER BY e.date_ouverture DESC
            ");
            $stmt->execute([$id_utilisateur]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Config statuts
$statut_config = [
    'preparation' => ['label' => 'Préparation', 'color' => 'yellow', 'icon' => 'fa-hourglass-start', 'can_vote_cat' => false, 'can_vote_final' => false],
    'ouvert_categories' => ['label' => 'Vote Catégories', 'color' => 'green', 'icon' => 'fa-vote-yea', 'can_vote_cat' => true, 'can_vote_final' => false],
    'ferme_categories' => ['label' => 'Attente Vote Final', 'color' => 'blue', 'icon' => 'fa-pause-circle', 'can_vote_cat' => false, 'can_vote_final' => false],
    'ouvert_final' => ['label' => 'Vote Final', 'color' => 'purple', 'icon' => 'fa-crown', 'can_vote_cat' => false, 'can_vote_final' => true],
    'cloture' => ['label' => 'Clôturé', 'color' => 'red', 'icon' => 'fa-times-circle', 'can_vote_cat' => false, 'can_vote_final' => false]
];

$color_classes = [
    'yellow' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-400', 'border' => 'border-yellow-500/30'],
    'green' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-400', 'border' => 'border-green-500/30'],
    'blue' => ['bg' => 'bg-blue-500/20', 'text' => 'text-blue-400', 'border' => 'border-blue-500/30'],
    'purple' => ['bg' => 'bg-purple-500/20', 'text' => 'text-purple-400', 'border' => 'border-purple-500/30'],
    'red' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-400', 'border' => 'border-red-500/30']
];

require_once 'header.php';
?>

<section class="py-20 px-6">
    <div class="container mx-auto max-w-6xl">
        
        <div class="mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                <i class="fas fa-calendar text-accent mr-3"></i>Événements
            </h1>
            <p class="text-xl text-light-80">Inscrivez-vous pour participer aux votes</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Explication -->
        <div class="mb-8 p-6 rounded-3xl bg-white/5 border border-white/10">
            <h3 class="text-lg font-bold text-light mb-4"><i class="fas fa-info-circle text-accent mr-2"></i>Comment ça marche ?</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex items-start gap-3">
                    <span class="w-8 h-8 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center flex-shrink-0">1</span>
                    <div>
                        <p class="font-bold text-green-400">Vote par Catégories</p>
                        <p class="text-light/60">Votez pour vos jeux préférés dans chaque catégorie</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-8 h-8 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center flex-shrink-0">2</span>
                    <div>
                        <p class="font-bold text-purple-400">Vote Final</p>
                        <p class="text-light/60">Élisez le Jeu de l'Année parmi les vainqueurs</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border text-center">
                <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                <p class="text-xl text-light-80">Aucun événement disponible.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($events as $event): 
                    $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                    $colors = $color_classes[$status['color']];
                ?>
                    <div class="glass-card rounded-3xl p-6 modern-border">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-2xl font-bold text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h3>
                                        <?php if ($event['description']): ?>
                                            <p class="text-sm text-light-80"><?php echo htmlspecialchars($event['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $colors['bg']; ?> <?php echo $colors['text']; ?> border <?php echo $colors['border']; ?>">
                                        <i class="fas <?php echo $status['icon']; ?> mr-1"></i><?php echo $status['label']; ?>
                                    </span>
                                </div>
                                
                                <!-- Timeline -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div class="p-3 rounded-xl <?php echo $status['can_vote_cat'] ? 'bg-green-500/20 border-green-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fas fa-layer-group <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/60'; ?>"></i>
                                            <span class="text-sm font-bold <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/80'; ?>">Vote Catégories</span>
                                            <?php if ($status['can_vote_cat']): ?>
                                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-500 text-dark font-bold animate-pulse">OUVERT</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-light/60">
                                            <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                            → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="p-3 rounded-xl <?php echo $status['can_vote_final'] ? 'bg-purple-500/20 border-purple-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fas fa-crown <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/60'; ?>"></i>
                                            <span class="text-sm font-bold <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/80'; ?>">Vote Final</span>
                                            <?php if ($status['can_vote_final']): ?>
                                                <span class="px-2 py-0.5 rounded-full text-xs bg-purple-500 text-white font-bold animate-pulse">OUVERT</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($event['date_debut_vote_final'])): ?>
                                            <p class="text-xs text-light/60">
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                                → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-xs text-light/40 italic">Dates à venir</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex flex-col gap-3 lg:w-48">
                                <?php if ($event['is_registered'] > 0): ?>
                                    <div class="flex items-center gap-2 p-3 rounded-2xl bg-green-500/10 border border-green-500/30 text-green-400">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="font-medium">Inscrit</span>
                                    </div>
                                    
                                    <?php if ($status['can_vote_cat']): ?>
                                        <a href="vote.php" class="text-center px-4 py-3 rounded-2xl bg-green-500 text-dark font-bold hover:bg-green-400 transition-colors">
                                            <i class="fas fa-vote-yea mr-2"></i>Voter (Catégories)
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($status['can_vote_final']): ?>
                                        <a href="vote-final.php" class="text-center px-4 py-3 rounded-2xl bg-purple-500 text-white font-bold hover:bg-purple-400 transition-colors">
                                            <i class="fas fa-crown mr-2"></i>Voter (Final)
                                        </a>
                                    <?php elseif (!$status['can_vote_cat'] && $event['statut'] !== 'preparation'): ?>
                                        <a href="vote-final.php" class="text-center px-4 py-3 rounded-2xl bg-purple-500/20 text-purple-400 border border-purple-500/30 font-bold hover:bg-purple-500/30 transition-colors">
                                            <i class="fas fa-crown mr-2"></i>Voir Vote Final
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="register">
                                        <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                            <i class="fas fa-user-plus"></i> S'inscrire
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-12">
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 transition-colors">
                <i class="fas fa-arrow-left"></i> Retour au dashboard
            </a>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>