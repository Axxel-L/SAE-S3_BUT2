<?php
/**
 * SALON DES JEUX - GameCrown
 * Exploration de tous les jeux et leurs campagnes
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

$search = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$filter = $_GET['filter'] ?? 'all'; // all, with_candidat, popular
$sort = $_GET['sort'] ?? 'recent'; // recent, alpha, popular
$jeux = [];
$stats = ['total_jeux' => 0, 'total_candidats' => 0, 'total_comments' => 0];

// Stats globales
try {
    $stmt = $connexion->query("SELECT COUNT(*) FROM jeu");
    $stats['total_jeux'] = $stmt->fetchColumn();
    
    $stmt = $connexion->query("SELECT COUNT(*) FROM candidat WHERE statut = 'valide'");
    $stats['total_candidats'] = $stmt->fetchColumn();
    
    $stmt = $connexion->query("SELECT COUNT(*) FROM commentaire");
    $stats['total_comments'] = $stmt->fetchColumn();
} catch (Exception $e) {}

// Récupérer les jeux
try {
    $query = "
        SELECT 
            j.*,
            c.id_candidat,
            c.nom as candidat_nom,
            c.photo as candidat_photo,
            (SELECT COUNT(*) FROM commentaire com WHERE com.id_jeu = j.id_jeu) as nb_comments,
            (SELECT COUNT(*) FROM bulletin_categorie bc WHERE bc.id_jeu = j.id_jeu) + 
            (SELECT COUNT(*) FROM bulletin_final bf WHERE bf.id_jeu = j.id_jeu) as nb_votes
        FROM jeu j
        LEFT JOIN candidat c ON j.id_jeu = c.id_jeu AND c.statut = 'valide'
        WHERE 1=1
    ";
    
    $params = [];
    
    // Recherche
    if (!empty($search)) {
        $query .= " AND (j.titre LIKE ? OR j.editeur LIKE ? OR c.nom LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Filtre
    if ($filter === 'with_candidat') {
        $query .= " AND c.id_candidat IS NOT NULL";
    }
    
    // Tri
    switch ($sort) {
        case 'alpha':
            $query .= " ORDER BY j.titre ASC";
            break;
        case 'popular':
            $query .= " ORDER BY nb_votes DESC, nb_comments DESC";
            break;
        case 'recent':
        default:
            $query .= " ORDER BY j.id_jeu DESC";
            break;
    }
    
    $query .= " LIMIT 50";
    
    $stmt = $connexion->prepare($query);
    $stmt->execute($params);
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jeux = [];
}

// Jeux populaires (pour sidebar)
$popular_jeux = [];
try {
    $stmt = $connexion->query("
        SELECT j.id_jeu, j.titre, j.image,
            (SELECT COUNT(*) FROM bulletin_categorie bc WHERE bc.id_jeu = j.id_jeu) + 
            (SELECT COUNT(*) FROM bulletin_final bf WHERE bf.id_jeu = j.id_jeu) as nb_votes
        FROM jeu j
        ORDER BY nb_votes DESC
        LIMIT 5
    ");
    $popular_jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Candidats actifs (pour sidebar)
$active_candidats = [];
try {
    $stmt = $connexion->query("
        SELECT c.id_candidat, c.nom, c.photo, j.id_jeu, j.titre as jeu_titre,
            (SELECT COUNT(*) FROM commentaire com WHERE com.id_utilisateur = c.id_utilisateur) as nb_posts
        FROM candidat c
        JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.statut = 'valide'
        ORDER BY nb_posts DESC
        LIMIT 5
    ");
    $active_candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

require_once 'header.php';
?>

<section class="py-12 px-6 min-h-screen">
    <div class="container mx-auto max-w-7xl">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-cyan-500/20 to-purple-500/20 border border-cyan-500/30 mb-6">
                <i class="fas fa-store text-4xl text-cyan-400"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold font-orbitron text-white mb-4">
                Salon des Jeux
            </h1>
            <p class="text-white/60 text-lg max-w-2xl mx-auto">
                Explorez tous les jeux en compétition, découvrez leurs campagnes et participez aux discussions !
            </p>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mb-12">
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-cyan-400 mb-1"><?php echo $stats['total_jeux']; ?></div>
                <div class="text-xs text-white/60">Jeux</div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-yellow-400 mb-1"><?php echo $stats['total_candidats']; ?></div>
                <div class="text-xs text-white/60">Candidats</div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-purple-400 mb-1"><?php echo $stats['total_comments']; ?></div>
                <div class="text-xs text-white/60">Commentaires</div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6 order-2 lg:order-1">
                
                <!-- Recherche -->
                <div class="glass-card rounded-2xl p-5 modern-border">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-search text-cyan-400"></i> Rechercher
                    </h3>
                    <form method="GET" class="space-y-3">
                        <input type="text" name="q" value="<?php echo $search; ?>" 
                            placeholder="Nom du jeu, éditeur, candidat..."
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 focus:border-cyan-500/50 outline-none text-white placeholder-white/30 text-sm">
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        <button type="submit" class="w-full py-2 rounded-xl bg-cyan-500/20 text-cyan-400 text-sm font-medium hover:bg-cyan-500 hover:text-dark transition-all">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </form>
                </div>
                
                <!-- Filtres -->
                <div class="glass-card rounded-2xl p-5 modern-border">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-filter text-cyan-400"></i> Filtres
                    </h3>

                    
                    <hr class="border-white/10 my-4">
                    
                    <h4 class="text-sm font-medium text-white/60 mb-3">Trier par</h4>
                    <div class="space-y-2">
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=recent" 
                           class="flex items-center gap-3 p-2 rounded-lg <?php echo $sort === 'recent' ? 'text-cyan-400' : 'text-white/60 hover:text-white'; ?> transition-colors text-sm">
                            <i class="fas fa-clock w-4"></i> Plus récents
                        </a>
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=alpha" 
                           class="flex items-center gap-3 p-2 rounded-lg <?php echo $sort === 'alpha' ? 'text-cyan-400' : 'text-white/60 hover:text-white'; ?> transition-colors text-sm">
                            <i class="fas fa-sort-alpha-down w-4"></i> Alphabétique
                        </a>
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=popular" 
                           class="flex items-center gap-3 p-2 rounded-lg <?php echo $sort === 'popular' ? 'text-cyan-400' : 'text-white/60 hover:text-white'; ?> transition-colors text-sm">
                            <i class="fas fa-fire w-4"></i> Populaires
                        </a>
                    </div>
                </div>
                
                <!-- Top Jeux -->
                <div class="glass-card rounded-2xl p-5 modern-border">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-400"></i> Top Jeux
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($popular_jeux as $i => $pjeu): ?>
                            <a href="jeu-campagne.php?id=<?php echo $pjeu['id_jeu']; ?>" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-colors group">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-yellow-500/30 to-orange-500/30 flex items-center justify-center text-xs font-bold text-yellow-400"><?php echo $i + 1; ?></span>
                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-white/5 flex-shrink-0">
                                    <?php if ($pjeu['image']): ?>
                                        <img src="<?php echo htmlspecialchars($pjeu['image']); ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center"><i class="fas fa-gamepad text-white/20"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-sm font-medium truncate group-hover:text-cyan-400 transition-colors"><?php echo htmlspecialchars($pjeu['titre']); ?></p>
                                    <p class="text-white/40 text-xs"><?php echo $pjeu['nb_votes']; ?> votes</p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Candidats Actifs -->
                <div class="glass-card rounded-2xl p-5 modern-border">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-star text-purple-400"></i> Candidats Actifs
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($active_candidats as $candidat): ?>
                            <a href="jeu-campagne.php?id=<?php echo $candidat['id_jeu']; ?>" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-colors group">
                                <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-yellow-500/20 to-orange-500/20 border-2 border-yellow-500/30 flex-shrink-0">
                                    <?php if ($candidat['photo']): ?>
                                        <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center"><i class="fas fa-user text-yellow-400"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-yellow-400 text-sm font-medium truncate"><?php echo htmlspecialchars($candidat['nom']); ?></p>
                                    <p class="text-white/40 text-xs truncate"><?php echo htmlspecialchars($candidat['jeu_titre']); ?></p>
                                </div>
                                <span class="text-xs text-white/40"><?php echo $candidat['nb_posts']; ?> posts</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Grille des jeux -->
            <div class="lg:col-span-3 order-1 lg:order-2">
                
                <!-- Résultats -->
                <?php if (!empty($search)): ?>
                    <div class="mb-6 flex items-center justify-between">
                        <p class="text-white/60">
                            <span class="text-white font-medium"><?php echo count($jeux); ?></span> résultat(s) pour "<span class="text-cyan-400"><?php echo $search; ?></span>"
                        </p>
                        <a href="salon-jeux.php" class="text-sm text-white/40 hover:text-white transition-colors">
                            <i class="fas fa-times mr-1"></i>Effacer
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($jeux)): ?>
                    <div class="glass-card rounded-3xl p-12 modern-border text-center">
                        <i class="fas fa-search text-5xl text-white/20 mb-6"></i>
                        <h2 class="text-2xl font-bold text-white mb-3">Aucun jeu trouvé</h2>
                        <p class="text-white/60 mb-6">Essayez avec d'autres critères de recherche</p>
                        <a href="salon-jeux.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-cyan-500 text-dark font-bold hover:bg-cyan-400 transition-colors">
                            <i class="fas fa-redo"></i> Voir tous les jeux
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($jeux as $jeu): ?>
                            <a href="jeu-campagne.php?id=<?php echo $jeu['id_jeu']; ?>" class="group">
                                <div class="glass-card rounded-2xl overflow-hidden modern-border hover:border-cyan-500/50 transition-all duration-300 h-full">
                                    <!-- Image -->
                                    <div class="relative h-44 bg-gradient-to-br from-cyan-500/10 to-purple-500/10 overflow-hidden">
                                        <?php if ($jeu['image']): ?>
                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-gamepad text-5xl text-white/10"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Overlay stats -->
                                        <div class="absolute top-3 right-3 flex gap-2">
                                            <?php if ($jeu['nb_votes'] > 0): ?>
                                                <span class="px-2 py-1 rounded-lg bg-black/60 backdrop-blur-sm text-xs text-cyan-400">
                                                    <i class="fas fa-vote-yea mr-1"></i><?php echo $jeu['nb_votes']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($jeu['nb_comments'] > 0): ?>
                                                <span class="px-2 py-1 rounded-lg bg-black/60 backdrop-blur-sm text-xs text-white/80">
                                                    <i class="fas fa-comments mr-1"></i><?php echo $jeu['nb_comments']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Badge candidat -->
                                        <?php if ($jeu['candidat_nom']): ?>
                                            <div class="absolute bottom-3 left-3 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm border border-yellow-500/30">
                                                <div class="w-6 h-6 rounded-full overflow-hidden bg-yellow-500/20 border border-yellow-500/50">
                                                    <?php if ($jeu['candidat_photo']): ?>
                                                        <img src="<?php echo htmlspecialchars($jeu['candidat_photo']); ?>" alt="" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center"><i class="fas fa-user text-yellow-400 text-xs"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="text-yellow-400 text-xs font-medium"><?php echo htmlspecialchars($jeu['candidat_nom']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Infos -->
                                    <div class="p-5">
                                        <h3 class="text-lg font-bold text-white mb-1 group-hover:text-cyan-400 transition-colors truncate"><?php echo htmlspecialchars($jeu['titre']); ?></h3>
                                        <?php if ($jeu['editeur']): ?>
                                            <p class="text-white/50 text-sm mb-3"><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center justify-between pt-3 border-t border-white/10">
                                            <span class="text-xs text-white/40">
                                                <?php if ($jeu['candidat_nom']): ?>
                                                    <i class="fas fa-crown text-yellow-500 mr-1"></i>Avec candidat
                                                <?php else: ?>
                                                    <i class="fas fa-gamepad mr-1"></i>Sans candidat
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-cyan-400 text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                                Voir <i class="fas fa-arrow-right ml-1"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
</section>

<?php require_once 'footer.php'; ?>