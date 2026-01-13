<?php
/**
 * Utilise ResultatsService pour toute la logique métier
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
$id_evenement = intval($_GET['event'] ?? 0);
$event = null;
$resultatsCat = [];
$resultatsFinal = [];
$stats = [
    'total_votes_categories' => 0,
    'total_votes_final' => 0,
    'nb_categories' => 0,
    'nb_inscrits' => 0
];
try {
    $resultatsService = ServiceContainer::getResultatsService();
    $event = $resultatsService->getClosedEvent($id_evenement);
    if (!$event) {
        header('Location: resultats.php');
        exit;
    }

    $resultatsCat = $resultatsService->getResultsByCategory($id_evenement);
    $resultatsFinal = $resultatsService->getFinalResults($id_evenement);
    $stats = $resultatsService->getEventStats($id_evenement, count($resultatsCat));
} catch (Exception $e) {
    error_log("Resultats Error: " . $e->getMessage());
    die("Erreur : " . htmlspecialchars($e->getMessage()));
}

$totalVotesFinal = array_sum(array_column($resultatsFinal, 'nb_voix'));
require_once 'header.php';
?>
<br><br><br> 
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="flex justify-end gap-4 mb-8 no-print">
            <button onclick="window.print()" class="glass-button px-6 py-3 rounded-2xl font-medium flex items-center gap-2 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-300">
                <i class="fas fa-print text-accent"></i>
                <span class="text-accent font-semibold">Imprimer / PDF</span>
            </button>
        </div>
        <div class="text-center mb-12 pb-8 border-b border-white/10">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                🏆 <?php echo htmlspecialchars($event['nom']); ?>
            </h1>
            <p class="text-xl text-light-80">
                Résultats officiels — Du <?php echo date('d/m/Y', strtotime($event['date_ouverture'])); ?> 
                au <?php echo date('d/m/Y', strtotime($event['date_fermeture_vote_final'] ?? $event['date_fermeture'])); ?>
            </p>
        </div>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold font-orbitron text-accent mb-2"><?php echo $stats['nb_inscrits']; ?></div>
                <div class="text-light-80">Participants</div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold font-orbitron text-accent mb-2"><?php echo $stats['total_votes_categories']; ?></div>
                <div class="text-light-80">Votes catégories</div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold font-orbitron text-accent mb-2"><?php echo $stats['total_votes_final']; ?></div>
                <div class="text-light-80">Votes finale</div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold font-orbitron text-accent mb-2"><?php echo $stats['nb_categories']; ?></div>
                <div class="text-light-80">Catégories</div>
            </div>
        </div>
        
        <!-- Jeu de l'Année -->
        <?php if (!empty($resultatsFinal)): ?>
            <h2 class="text-4xl font-bold font-orbitron mb-10 accent-gradient border-b border-white/10 pb-4"><i class="fa-solid fa-ranking-star" style="color: #FFD43B;"></i> Jeu de l'Année</h2>
            <div class="glass-card rounded-3xl p-10 mb-10 modern-border border-2 border-white/10 bg-gradient-to-r from-[#ffd700]/10 to-[#ff8c00]/5 border border-[#ffd700]/30 text-center">
                <div class="text-6xl mb-6"><i class="fa-solid fa-crown" style="color: #FFD43B;"></i></div>
                <h2 class="text-5xl font-bold font-orbitron mb-6 text-light"><?php echo htmlspecialchars($resultatsFinal[0]['titre']); ?></h2>
                <div class="text-2xl text-light-80">
                    <?php echo $resultatsFinal[0]['nb_voix']; ?> votes 
                    (<?php echo $resultatsService->calculatePercentage($resultatsFinal[0]['nb_voix'], $totalVotesFinal); ?>%)
                </div>
            </div>
            <?php if (count($resultatsFinal) >= 3): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
                    <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 bg-gradient-to-r from-[#c0c0c0]/10 to-[#888]/5 border border-[#c0c0c0]/30 text-center lg:mt-8">
                        <div class="text-5xl mb-6">🥈</div>
                        <div class="text-3xl font-bold font-orbitron mb-4 text-light"><?php echo htmlspecialchars($resultatsFinal[1]['titre']); ?></div>
                        <div class="text-light-80 text-lg">
                            <?php echo $resultatsFinal[1]['nb_voix']; ?> votes 
                            (<?php echo $resultatsService->calculatePercentage($resultatsFinal[1]['nb_voix'], $totalVotesFinal); ?>%)
                        </div>
                    </div>
                    <div class="glass-card rounded-3xl p-10 modern-border border-2 border-white/10 bg-gradient-to-r from-[#ffd700]/20 to-[#ffaa00]/10 border border-[#ffd700]/30 text-center order-first lg:order-none">
                        <div class="text-6xl mb-8">🥇</div>
                        <div class="text-4xl font-bold font-orbitron mb-6 text-light"><?php echo htmlspecialchars($resultatsFinal[0]['titre']); ?></div>
                        <div class="text-light-80 text-xl">
                            <?php echo $resultatsFinal[0]['nb_voix']; ?> votes 
                            (<?php echo $resultatsService->calculatePercentage($resultatsFinal[0]['nb_voix'], $totalVotesFinal); ?>%)
                        </div>
                    </div>
                    <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 bg-gradient-to-r from-[#cd7f32]/10 to-[#8b4513]/5 border border-[#cd7f32]/30 text-center lg:mt-8">
                        <div class="text-5xl mb-6">🥉</div>
                        <div class="text-3xl font-bold font-orbitron mb-4 text-light"><?php echo htmlspecialchars($resultatsFinal[2]['titre']); ?></div>
                        <div class="text-light-80 text-lg">
                            <?php echo $resultatsFinal[2]['nb_voix']; ?> votes 
                            (<?php echo $resultatsService->calculatePercentage($resultatsFinal[2]['nb_voix'], $totalVotesFinal); ?>%)
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Graphique Vote Final -->
            <?php if (count($resultatsFinal) > 1): ?>
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-16">
                    <h3 class="text-3xl font-bold font-orbitron mb-10 text-center accent-gradient"><i class="fa-solid fa-chart-area"></i> Répartition des votes - Vote Final</h3>
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
                        <div class="flex items-end justify-center gap-4 h-80 lg:w-1/2">
                            <?php 
                            $maxVotes = max(array_column($resultatsFinal, 'nb_voix'));
                            foreach (array_slice($resultatsFinal, 0, 8) as $jeu): 
                                $height = $maxVotes > 0 ? ($jeu['nb_voix'] / $maxVotes * 240) : 5;
                            ?>
                                <div class="flex flex-col items-center">
                                    <div class="text-lg font-bold text-accent mb-3"><?php echo $jeu['nb_voix']; ?></div>
                                    <div class="w-12 bg-gradient-to-t from-accent to-[#0080ff] rounded-t-2xl" style="height: <?php echo max($height, 5); ?>px;"></div>
                                    <div class="text-sm text-light-80 mt-3 text-center max-w-20 truncate" title="<?php echo htmlspecialchars($jeu['titre']); ?>">
                                        <?php echo htmlspecialchars(mb_substr($jeu['titre'], 0, 8)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lg:w-1/2">
                            <div class="space-y-4">
                                <?php 
                                $colors = ['#ffd700', '#c0c0c0', '#cd7f32', '#9b59b6', '#3498db', '#2ecc71', '#e74c3c', '#f39c12'];
                                foreach (array_slice($resultatsFinal, 0, 8) as $i => $jeu): 
                                    $percent = $resultatsService->calculatePercentage($jeu['nb_voix'], $totalVotesFinal);
                                ?>
                                    <div class="flex items-center gap-4">
                                        <div class="w-5 h-5 rounded-lg" style="background: <?php echo $colors[$i % count($colors)]; ?>;"></div>
                                        <span class="text-light text-lg"><?php echo htmlspecialchars($jeu['titre']); ?> — <span class="text-accent font-bold"><?php echo $percent; ?>%</span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Classement complet vote final -->
            <?php if (count($resultatsFinal) > 3): ?>
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-16">
                    <h3 class="text-3xl font-bold font-orbitron mb-8 accent-gradient">Classement complet - Vote Final</h3>
                    <div class="space-y-6">
                        <?php foreach (array_slice($resultatsFinal, 3) as $index => $jeu): 
                            $percentage = $resultatsService->calculatePercentage($jeu['nb_voix'], $totalVotesFinal);
                        ?>
                            <div class="flex items-center gap-4">
                                <div class="w-12 text-center font-bold text-light text-xl">#<?php echo $index + 4; ?></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-light text-lg font-semibold mb-2"><?php echo htmlspecialchars($jeu['titre']); ?></p>
                                    <div class="h-4 bg-white/5 rounded-full overflow-hidden border border-white/10 shadow-inner">
                                        <div class="h-full rounded-full transition-all duration-500 ease-out" style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #0056cc, #0080ff); box-shadow: 0 0 20px rgba(0, 128, 255, 0.6);"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-light/60 text-sm"><?php echo $jeu['nb_voix']; ?> votes</span>
                                        <span class="text-accent font-bold text-lg"><?php echo $percentage; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Résultats par Catégorie -->
        <h2 class="text-4xl font-bold font-orbitron mb-10 accent-gradient border-b border-white/10 pb-4"><i class="fa-solid fa-clipboard-list"></i> Résultats par Catégorie</h2>
        <div class="space-y-8">
            <?php foreach ($resultatsCat as $categorie): 
                $totalVotesCat = array_sum(array_column($categorie['jeux'], 'nb_voix'));
            ?>
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h3 class="text-3xl font-bold font-orbitron mb-8 accent-gradient">
                        <?php echo htmlspecialchars($categorie['nom']); ?> 
                        <span class="text-xl font-normal text-light-80">
                            (<?php echo $totalVotesCat; ?> votes)
                        </span>
                    </h3>
                    <?php if (empty($categorie['jeux'])): ?>
                        <p class="text-light-80 italic text-lg">Aucun vote enregistré</p>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($categorie['jeux'] as $index => $jeu): 
                                $percentage = $resultatsService->calculatePercentage($jeu['nb_voix'], $totalVotesCat);
                                $medals = ['🥇', '🥈', '🥉'];
                                $medal = $medals[$index] ?? '#'.($index + 1);
                            ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 text-center text-2xl"><?php echo $medal; ?></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-light text-lg font-semibold mb-2"><?php echo htmlspecialchars($jeu['titre']); ?></p>
                                        <div class="h-4 bg-white/5 rounded-full overflow-hidden border border-white/10 shadow-inner">
                                            <div class="h-full bg-gradient-to-r from-blue-600 to-blue-900 rounded-full transition-all duration-500 ease-out" style="width: <?php echo $percentage; ?>%; box-shadow: 0 0 20px rgba(0, 212, 255, 0.5);"></div>
                                        </div>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-light/60 text-sm"><?php echo $jeu['nb_voix']; ?> votes</span>
                                            <span class="text-accent font-bold text-lg"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require_once 'footer.php'; ?>