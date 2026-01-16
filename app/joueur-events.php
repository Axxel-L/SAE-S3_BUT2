<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
if (!AuthenticationService::isAuthenticated()) {
    header('Location: index.php');
    exit;
}

if (AuthenticationService::getAuthenticatedUserType() !== 'joueur') {
    header('Location: index.php');
    exit;
}

$userId = AuthenticationService::getAuthenticatedUserId();
$db = DatabaseConnection::getInstance();
$eventService = ServiceContainer::getEventService();
$error = '';
$success = '';
try {
    $db->query("CALL update_event_statuts()");
} catch (Exception $e) {}

$events = [];
$eventResult = $eventService->getActiveEvents($userId);
if (!$eventResult['success']) {
    $error = implode(' | ', $eventResult['errors']);
} else {
    $events = $eventResult['events'] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['action']) && 
    $_POST['action'] === 'register') {
    $eventId = intval($_POST['id_evenement'] ?? 0);
    $registerResult = $eventService->registerEvent($userId, $eventId);
    if ($registerResult['success']) {
        $success = "Inscription réussie ! ✅";
        $eventResult = $eventService->getActiveEvents($userId);
        if ($eventResult['success']) {
            $events = $eventResult['events'] ?? [];
        }
        $error = '';
    } else {
        $error = implode(' | ', $registerResult['errors']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['action']) && 
    $_POST['action'] === 'unregister') {
    $eventId = intval($_POST['id_evenement'] ?? 0);
    $unregisterResult = $eventService->unregisterEvent($userId, $eventId);
    if ($unregisterResult['success']) {
        $success = "Désinscription réussie ! ✅";
        $eventResult = $eventService->getActiveEvents($userId);
        if ($eventResult['success']) {
            $events = $eventResult['events'] ?? [];
        }
        $error = '';
    } else {
        $error = implode(' | ', $unregisterResult['errors']);
    }
}

$statut_config = [
    'preparation' => [
        'label' => 'Préparation',
        'color' => 'yellow',
        'icon' => 'fa-hourglass-start',
        'can_vote_cat' => false,
        'can_vote_final' => false
    ],
    'ouvert_categories' => [
        'label' => 'Vote Catégories',
        'color' => 'green',
        'icon' => 'fa-vote-yea',
        'can_vote_cat' => true,
        'can_vote_final' => false
    ],
    'ferme_categories' => [
        'label' => 'Attente Vote Final',
        'color' => 'blue',
        'icon' => 'fa-pause-circle',
        'can_vote_cat' => false,
        'can_vote_final' => false
    ],
    'ouvert_final' => [
        'label' => 'Vote Final',
        'color' => 'purple',
        'icon' => 'fa-crown',
        'can_vote_cat' => false,
        'can_vote_final' => true
    ],
    'cloture' => [
        'label' => 'Clôturé',
        'color' => 'red',
        'icon' => 'fa-times-circle',
        'can_vote_cat' => false,
        'can_vote_final' => false
    ]
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
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
            <br>  
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
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-8">
            <h3 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-accent"></i> Comment ça marche ?
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-green-500/10 border border-green-500/30">
                    <span class="w-10 h-10 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center flex-shrink-0 text-lg font-bold">1</span>
                    <div>
                        <p class="font-bold text-green-400 text-lg mb-1">Vote par Catégories</p>
                        <p class="text-light/60">Votez pour vos jeux préférés dans chaque catégorie. Chaque catégorie élit son vainqueur.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-purple-500/10 border border-purple-500/30">
                    <span class="w-10 h-10 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center flex-shrink-0 text-lg font-bold">2</span>
                    <div>
                        <p class="font-bold text-purple-400 text-lg mb-1">Vote Final</p>
                        <p class="text-light/60">Élisez le Jeu de l'Année parmi les vainqueurs de chaque catégorie.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center">
                <i class="fas fa-inbox text-6xl text-light-80 mb-4"></i>
                <p class="text-xl text-light-80">Aucun événement disponible actuellement.</p>
                <p class="text-light/60 mt-2">Revenez plus tard pour participer aux prochains votes !</p>
            </div>
        <?php else: ?>
            <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                    <i class="fas fa-list text-accent"></i> Événements disponibles (<?php echo count($events); ?>)
                </h2>
                <div class="space-y-6">
                    <?php foreach ($events as $event): 
                        $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                        $colors = $color_classes[$status['color']];
                    ?>
                        <div class="glass-card rounded-2xl p-6 modern-border border border-white/10">
                            <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-2xl font-bold text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h3>
                                            <?php if ($event['description']): ?>
                                                <p class="text-sm text-light-80"><?php echo htmlspecialchars($event['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $colors['bg']; ?> <?php echo $colors['text']; ?> border <?php echo $colors['border']; ?>">
                                            <i class="fas <?php echo $status['icon']; ?> mr-2"></i><?php echo $status['label']; ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="p-4 rounded-2xl <?php echo $status['can_vote_cat'] ? 'bg-green-500/20 border-2 border-green-500/50' : 'bg-white/5 border border-white/10'; ?>">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-layer-group <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/60'; ?> text-lg"></i>
                                                    <span class="font-bold <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/80'; ?>">Vote Catégories</span>
                                                </div>
                                                <?php if ($status['can_vote_cat']): ?>
                                                    <span class="px-3 py-1 rounded-full text-xs bg-green-500 text-dark font-bold animate-pulse">OUVERT</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-light/60">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                                → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                            </p>
                                        </div>
                                        <div class="p-4 rounded-2xl <?php echo $status['can_vote_final'] ? 'bg-purple-500/20 border-2 border-purple-500/50' : 'bg-white/5 border border-white/10'; ?>">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-crown <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/60'; ?> text-lg"></i>
                                                    <span class="font-bold <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/80'; ?>">Vote Final</span>
                                                </div>
                                                <?php if ($status['can_vote_final']): ?>
                                                    <span class="px-3 py-1 rounded-full text-xs bg-purple-500 text-white font-bold animate-pulse">OUVERT</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($event['date_debut_vote_final'])): ?>
                                                <p class="text-sm text-light/60">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                                    → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-sm text-light/40 italic">
                                                    <i class="fas fa-clock mr-2"></i>Dates à venir
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 lg:w-56">
                                    <?php if ($event['is_registered'] > 0): ?>
                                        <div class="flex items-center justify-center gap-2 p-4 rounded-2xl bg-green-500/20 border-2 border-green-500/50 text-green-400">
                                            <i class="fas fa-check-circle text-xl"></i>
                                            <span class="font-bold text-lg">Inscrit</span>
                                        </div>
                                        
                                        <?php if ($status['can_vote_cat']): ?>
                                            <a href="vote.php" class="text-center px-6 py-4 rounded-2xl bg-gradient-to-r from-green-500 to-green-600 text-dark font-bold hover:from-green-600 hover:to-green-700 transition-all duration-300 border border-green-500/30">
                                                <i class="fas fa-vote-yea mr-3"></i>Voter (Catégories)
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($status['can_vote_final']): ?>
                                            <a href="vote-final.php" class="text-center px-6 py-4 rounded-2xl bg-gradient-to-r from-purple-500 to-purple-600 text-white font-bold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 border border-purple-500/30">
                                                <i class="fas fa-crown mr-3"></i>Voter (Final)
                                            </a>
                                        <?php elseif (!$status['can_vote_cat'] && $event['statut'] !== 'preparation'): ?>
                                            <a href="vote-final.php" class="text-center px-6 py-4 rounded-2xl bg-purple-500/20 text-purple-400 border-2 border-purple-500/30 font-bold hover:bg-purple-500/30 transition-colors">
                                                <i class="fas fa-crown mr-3"></i>Voir Vote Final
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="register">
                                            <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                            <button type="submit" class="w-full flex items-center justify-center gap-3 px-6 py-4 rounded-2xl bg-gradient-to-r from-accent to-accent/80 text-dark font-bold hover:from-accent/90 hover:to-accent transition-all duration-300 border border-accent/30">
                                                <i class="fas fa-user-plus text-xl"></i> S'inscrire
                                            </button>
                                        </form>
                                        <div class="text-xs text-light/60 text-center mt-2">
                                            <i class="fas fa-info-circle mr-1"></i>Inscription requise pour voter
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="text-center mt-12">
            <a href="./dashboard.php" class="inline-flex items-center gap-3 px-8 py-4 rounded-2xl bg-white/5 border-2 border-white/10 hover:border-accent/50 hover:bg-white/10 transition-all duration-300 text-lg">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</section>
<?php require_once 'footer.php';
?>