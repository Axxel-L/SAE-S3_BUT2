<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
require_once 'header.php';
if (!isCandidate()) {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
    exit;
}

$id_utilisateur = (int)getAuthUserId();
$candidatStatsService = ServiceContainer::getCandidatStatisticsService();
$data = $candidatStatsService->getCandidatStatistics($id_utilisateur);
$candidat = $data['candidat'];
$stats = $data['stats'];
$error = $data['error'];
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-chart-bar text-accent mr-3"></i>Statistiques
            </h1>
            <p class="text-xl text-light/80">Suivez la performance de <span class="accent-gradient font-bold"><?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'votre jeu'); ?></span></p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-accent text-2xl"></i>
                    </div>
                    <div class="text-4xl font-bold text-accent mb-2"><?php echo $stats['commentaires']; ?></div>
                    <p class="text-light/60 text-sm">Commentaires</p>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-green-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-layer-group text-green-400 text-2xl"></i>
                    </div>
                    <div class="text-4xl font-bold text-green-400 mb-2"><?php echo $stats['votes_categorie']; ?></div>
                    <p class="text-light/60 text-sm">Votes Catégories</p>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-yellow-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-crown text-yellow-400 text-2xl"></i>
                    </div>
                    <div class="text-4xl font-bold text-yellow-400 mb-2"><?php echo $stats['votes_final']; ?></div>
                    <p class="text-light/60 text-sm">Votes Finaux</p>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-purple-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-purple-400 text-2xl"></i>
                    </div>
                    <div class="text-4xl font-bold text-purple-400 mb-2"><?php echo $stats['votes_total']; ?></div>
                    <p class="text-light/60 text-sm">Total Votes</p>
                </div>
            </div>
        </div>
        
        <!-- Graphique de répartition -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-12">
            <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-chart-pie text-accent text-xl"></i>
                </div>
                <span>Répartition des votes</span>
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="p-6 rounded-2xl bg-green-500/10 border border-green-500/30">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-green-400 flex items-center gap-2">
                                <i class="fas fa-layer-group"></i>
                                Phase 1 - Catégories
                            </h3>
                            <p class="text-green-300/70 text-sm">Votes pendant la phase catégorielle</p>
                        </div>
                        <span class="text-3xl font-bold text-green-400"><?php echo $stats['votes_categorie']; ?></span>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-light/60 mb-2">
                            <span>Progression</span>
                            <span><?php echo ($stats['votes_total'] > 0) ? round(($stats['votes_categorie'] / $stats['votes_total']) * 100, 1) : 0; ?>%</span>
                        </div>
                        <div class="w-full h-4 bg-white/5 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-green-500 to-green-400 rounded-full transition-all duration-1000"
                                style="width: <?php echo ($stats['votes_total'] > 0) ? ($stats['votes_categorie'] / $stats['votes_total'] * 100) : 0; ?>%"
                            ></div>
                        </div>
                    </div>
                </div>
                <div class="p-6 rounded-2xl bg-yellow-500/10 border border-yellow-500/30">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-yellow-400 flex items-center gap-2">
                                <i class="fas fa-crown"></i>
                                Phase 2 - Final
                            </h3>
                            <p class="text-yellow-300/70 text-sm">Votes pendant la phase finale</p>
                        </div>
                        <span class="text-3xl font-bold text-yellow-400"><?php echo $stats['votes_final']; ?></span>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-light/60 mb-2">
                            <span>Progression</span>
                            <span><?php echo ($stats['votes_total'] > 0) ? round(($stats['votes_final'] / $stats['votes_total']) * 100, 1) : 0; ?>%</span>
                        </div>
                        <div class="w-full h-4 bg-white/5 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-yellow-500 to-yellow-400 rounded-full transition-all duration-1000"
                                style="width: <?php echo ($stats['votes_total'] > 0) ? ($stats['votes_final'] / $stats['votes_total'] * 100) : 0; ?>%"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30">
                <p class="text-sm text-blue-400 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i>
                    Les votes sont anonymes - vous ne voyez que les totaux.
                </p>
            </div>
        </div>

        <!-- Commentaires -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold font-orbitron flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                            <i class="fas fa-comment-dots text-accent text-xl"></i>
                        </div>
                        <span>Derniers commentaires reçus</span>
                    </h2>
                    <p class="text-light/60 mt-2">Les 10 derniers commentaires sur votre jeu</p>
                </div>
                <div class="px-4 py-2 rounded-xl bg-accent/10 text-accent border border-accent/30 text-sm font-medium">
                    <?php echo count($stats['derniers_commentaires']); ?> commentaire(s)
                </div>
            </div>
            <?php if (empty($stats['derniers_commentaires'])): ?>
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white/5 mb-6">
                        <i class="fas fa-comment-slash text-4xl text-light/20"></i>
                    </div>
                    <p class="text-light/80 text-lg mb-2">Pas encore de commentaires reçus</p>
                    <p class="text-light/60">Les commentaires des électeurs apparaîtront ici</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($stats['derniers_commentaires'] as $comment): ?>
                        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center">
                                        <i class="fas fa-user text-accent"></i>
                                    </div>
                                    <div>
                                        <p class="text-accent font-bold"><?php echo htmlspecialchars($comment['email']); ?></p>
                                        <p class="text-light/60 text-sm"><?php echo htmlspecialchars($comment['date_format']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-light/80 leading-relaxed"><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'footer.php'; ?>