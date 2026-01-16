<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = (int)$_SESSION['id_utilisateur'];
$useremail = (string)($_SESSION['useremail'] ?? '');
$dashboardService = ServiceContainer::getDashboardService();
$data = $dashboardService->getUserDashboardData($id_utilisateur);
$data = $dashboardService->getUserDashboardData($id_utilisateur);
$user = $data['user'];
$events = $data['events'];
$votes = $data['votes'];
$voteStatus = $data['voteStatus'];
$error = $data['error'];
$stats = $data['statistics'];
require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-user-circle text-accent mr-3"></i>Mon Espace
            </h1>
            <p class="text-xl text-light-80">Gérez vos votes et informations personnelles</p>
        </div>
        <!-- Messages d'erreur -->
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profil utilisateur -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-6">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-accent/20 border-2 border-accent/30 mb-4">
                            <i class="fas fa-user text-3xl text-accent"></i>
                        </div>
                        <h2 class="text-xl font-bold text-light break-words">
                            <?php echo htmlspecialchars($useremail); ?>
                        </h2>
                        <?php if ($user): ?>
                            <p class="text-sm text-light-80 mt-1">
                                Inscrit depuis <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($user): ?>
                        <div class="mb-6 pb-6 border-b border-white/10">
                            <div class="text-sm text-light-80 mb-2">Type de compte</div>
                            <div class="inline-block px-4 py-2 rounded-full bg-accent/20 text-accent border-2 border-accent/30">
                                <i class="fas fa-tag mr-2"></i>
                                <?php 
                                $typeLabels = [
                                    'joueur' => 'Joueur',
                                    'admin' => 'Administrateur',
                                    'candidat' => 'Candidat'
                                ];
                                echo $typeLabels[$user['type']] ?? ucfirst($user['type']);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-light-80 flex items-center gap-2">
                                <i class="fas fa-calendar text-accent"></i> Événements
                            </span>
                            <span class="text-2xl font-bold text-accent"><?php echo $stats['total_events']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-light-80 flex items-center gap-2">
                                <i class="fas fa-vote-yea text-accent"></i> Votes
                            </span>
                            <span class="text-2xl font-bold text-accent"><?php echo $stats['total_votes']; ?></span>
                        </div>
                    </div>
                    <div class="mt-8 space-y-3">
                        <a href="vote.php" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 hover:bg-accent/5 transition-colors text-light-80 hover:text-accent">
                            <i class="fas fa-vote-yea"></i> Aller voter
                        </a>
                    </div>
                </div>
            </div>

            <!-- Événements et historique -->
            <div class="lg:col-span-2 space-y-8">
                <div>
                    <h3 class="text-2xl font-bold font-orbitron mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-check text-accent"></i> Mes Événements
                    </h3>
                    <?php if (empty($events)): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 text-center">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-light-80">Vous n'êtes inscrit à aucun événement.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($events as $event): ?>
                                <?php
                                $status = $voteStatus[$event['id_evenement']] ?? [];
                                $isOpen = DashboardService::isEventOpen($event);
                                ?>
                                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-bold text-light"><?php echo htmlspecialchars($event['nom']); ?></h4>
                                            <p class="text-sm text-light-80 mt-1">
                                                <i class="fas fa-calendar-alt mr-1 text-accent"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?> 
                                                à 
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                            </p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $isOpen ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-white/10 text-light-80 border border-white/10'; ?>">
                                            <?php echo $isOpen ? '🟢 Ouvert' : '⚫ Fermé'; ?>
                                        </span>
                                    </div>
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-light-80">Progression des votes</span>
                                            <span class="text-sm font-bold text-accent">
                                                <?php 
                                                $catVoted = 0;
                                                $catTotal = 0;
                                                if (isset($status['categories'])) {
                                                    $catVoted = (int)$status['categories']['voted_categories'];
                                                    $catTotal = (int)$status['categories']['total_categories'];
                                                }
                                                echo "$catVoted/$catTotal";
                                                ?>
                                            </span>
                                        </div>
                                        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-gradient-to-r from-accent to-accent-dark transition-all duration-300"
                                                style="width: <?php echo $catTotal > 0 ? ($catVoted / $catTotal * 100) : 0; ?>%"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="mb-4 flex items-center gap-2">
                                        <span class="text-sm text-light-80">Vote final</span>
                                        <?php if ($status['final'] ?? false): ?>
                                            <span class="px-2 py-1 rounded-lg bg-green-500/20 text-green-400 text-xs font-medium border border-green-500/30">
                                                <i class="fas fa-check mr-1"></i> Voté
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-lg bg-white/5 text-light-80 text-xs font-medium border border-white/10">
                                                Non commencé
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isOpen): ?>
                                        <a href="vote.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-2xl bg-accent/20 text-accent hover:bg-accent/30 transition-colors text-sm font-medium border border-white/10">
                                            <i class="fas fa-vote-yea"></i> Voter maintenant
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Historique des votes -->
                <div>
                    <h3 class="text-2xl font-bold font-orbitron mb-4 flex items-center gap-2">
                        <i class="fas fa-history text-accent"></i> Historique des Votes
                    </h3>
                    <?php if (empty($votes)): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 text-center">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-light-80">Vous n'avez pas encore voté.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($votes as $vote): ?>
                                <div class="glass-card rounded-2xl p-4 modern-border border border-white/10">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <?php if ($vote['type'] === 'final'): ?>
                                                    <i class="fas fa-crown text-yellow-400"></i>
                                                    <span class="text-sm font-bold text-light">Vote Final</span>
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle text-accent"></i>
                                                    <span class="text-sm font-bold text-light"><?php echo htmlspecialchars($vote['categorie']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-light-80 text-sm"><?php echo htmlspecialchars($vote['evenement']); ?></p>
                                        </div>
                                        <div class="text-right text-xs text-light-80">
                                            <?php echo date('d/m/Y H:i', strtotime($vote['datevote'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'footer.php'; ?>