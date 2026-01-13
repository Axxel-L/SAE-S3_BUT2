<?php
require_once 'classes/init.php';
$search = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'recent';
$validSorts = ['recent', 'alpha', 'popular'];
$validFilters = ['all', 'with_candidat', 'popular'];
if (!in_array($sort, $validSorts)) $sort = 'recent';
if (!in_array($filter, $validFilters)) $filter = 'all';

$db = ServiceContainer::getDatabase();
$validationService = ServiceContainer::getValidationService();
$jeux = [];
$popular_jeux = [];
$active_candidats = [];
$stats = [
    'total_jeux' => 0,
    'total_candidats' => 0,
    'total_comments' => 0
];

try {
    $stmt = $db->query("SELECT COUNT(*) FROM jeu");
    $stats['total_jeux'] = intval($stmt->fetchColumn());
    
    $stmt = $db->query("SELECT COUNT(*) FROM candidat WHERE statut = 'valide'");
    $stats['total_candidats'] = intval($stmt->fetchColumn());
    
    $stmt = $db->query("SELECT COUNT(*) FROM commentaire");
    $stats['total_comments'] = intval($stmt->fetchColumn());
} catch (Exception $e) {}

try {
    $baseQuery = "
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
    
    if (!empty($search)) {
        $baseQuery .= " AND (j.titre LIKE ? OR j.editeur LIKE ? OR c.nom LIKE ?)";
        $likeSearch = "%{$search}%";
        $params = [$likeSearch, $likeSearch, $likeSearch];
    }
    
    if ($filter === 'with_candidat') {
        $baseQuery .= " AND c.id_candidat IS NOT NULL";
    }
    
    switch ($sort) {
        case 'alpha':
            $baseQuery .= " ORDER BY j.titre ASC";
            break;
        case 'popular':
            $baseQuery .= " ORDER BY nb_votes DESC, nb_comments DESC, j.titre ASC";
            break;
        case 'recent':
        default:
            $baseQuery .= " ORDER BY j.id_jeu DESC";
            break;
    }
    
    $baseQuery .= " LIMIT 50";
    $stmt = $db->prepare($baseQuery);
    $stmt->execute($params);
    $jeux = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching games: " . $e->getMessage());
    $jeux = [];
}

try {
    $stmt = $db->query("
        SELECT j.id_jeu, j.titre, j.image,
            (SELECT COUNT(*) FROM bulletin_categorie bc WHERE bc.id_jeu = j.id_jeu) + 
            (SELECT COUNT(*) FROM bulletin_final bf WHERE bf.id_jeu = j.id_jeu) as nb_votes
        FROM jeu j
        ORDER BY nb_votes DESC, j.id_jeu DESC
        LIMIT 5
    ");
    $popular_jeux = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching popular games: " . $e->getMessage());
}

try {
    $stmt = $db->query("
        SELECT c.id_candidat, c.nom, c.photo, j.id_jeu, j.titre as jeu_titre,
            (SELECT COUNT(*) FROM commentaire com WHERE com.id_utilisateur = c.id_utilisateur) as nb_posts
        FROM candidat c
        JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.statut = 'valide'
        ORDER BY nb_posts DESC, c.id_candidat DESC
        LIMIT 5
    ");
    $active_candidats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching active candidates: " . $e->getMessage());
}
require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-store text-accent mr-3"></i>Salon des Jeux
            </h1>
            <p class="text-xl text-light-80 max-w-2xl mx-auto">
                Explorez tous les jeux en compétition, découvrez leurs campagnes et participez aux discussions !
            </p>
        </div>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mb-12">
            <div class="glass-card rounded-2xl p-5 modern-border border-2 border-white/10 text-center">
                <div class="text-3xl font-bold text-accent mb-1"><?php echo intval($stats['total_jeux']); ?></div>
                <div class="text-xs text-light-80 flex items-center justify-center gap-2">
                    <i class="fas fa-gamepad text-accent"></i>Jeux
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border border-2 border-white/10 text-center">
                <div class="text-3xl font-bold text-yellow-400 mb-1"><?php echo intval($stats['total_candidats']); ?></div>
                <div class="text-xs text-light-80 flex items-center justify-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i>Candidats
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border border-2 border-white/10 text-center">
                <div class="text-3xl font-bold text-purple-400 mb-1"><?php echo intval($stats['total_comments']); ?></div>
                <div class="text-xs text-light-80 flex items-center justify-center gap-2">
                    <i class="fas fa-comments text-purple-400"></i>Commentaires
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-1 space-y-6 order-2 lg:order-1">
                <div class="glass-card rounded-3xl p-5 modern-border border-2 border-white/10">
                    <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-search text-accent"></i> Rechercher
                    </h3>
                    <form method="GET" class="space-y-3">
                        <input 
                            type="text" 
                            name="q" 
                            value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Nom du jeu, éditeur, candidat..."
                            class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light placeholder-light/30 text-sm">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <button type="submit" class="w-full py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </form>
                </div>
                
                <!-- Filtres de tri -->
                <div class="glass-card rounded-3xl p-5 modern-border border-2 border-white/10">
                    <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-filter text-accent"></i> Trier par
                    </h3>
                    <div class="space-y-2">
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo htmlspecialchars($filter); ?>&sort=recent" 
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl font-medium transition-all duration-300 text-sm border <?php echo $sort === 'recent' ? 'bg-accent/20 text-accent border-accent/30 shadow-lg shadow-accent/10' : 'bg-white/5 text-light-80 border-white/10 hover:bg-white/10 hover:border-accent/30'; ?>">
                            <i class="fas fa-clock"></i> Plus récents
                        </a>
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo htmlspecialchars($filter); ?>&sort=alpha" 
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl font-medium transition-all duration-300 text-sm border <?php echo $sort === 'alpha' ? 'bg-accent/20 text-accent border-accent/30 shadow-lg shadow-accent/10' : 'bg-white/5 text-light-80 border-white/10 hover:bg-white/10 hover:border-accent/30'; ?>">
                            <i class="fas fa-sort-alpha-down"></i> Alphabétique
                        </a>
                        <a href="?q=<?php echo urlencode($search); ?>&filter=<?php echo htmlspecialchars($filter); ?>&sort=popular" 
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl font-medium transition-all duration-300 text-sm border <?php echo $sort === 'popular' ? 'bg-accent/20 text-accent border-accent/30 shadow-lg shadow-accent/10' : 'bg-white/5 text-light-80 border-white/10 hover:bg-white/10 hover:border-accent/30'; ?>">
                            <i class="fas fa-fire"></i> Populaires
                        </a>
                    </div>
                </div>
                <div class="glass-card rounded-3xl p-5 modern-border border-2 border-white/10">
                    <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-400"></i> Top 5 Jeux
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($popular_jeux as $i => $pjeu): ?>
                            <a href="jeu-campagne.php?id=<?php echo intval($pjeu['id_jeu']); ?>" 
                               class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition-colors group border border-white/10 hover:border-accent/30">
                                <span class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center text-xs font-bold text-yellow-400 border border-yellow-500/30">
                                    <?php echo $i + 1; ?>
                                </span>
                                <div class="w-8 h-8 rounded-lg overflow-hidden bg-white/5 flex-shrink-0 border border-white/10">
                                    <?php if (!empty($pjeu['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($pjeu['image']); ?>" alt="<?php echo htmlspecialchars($pjeu['titre']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-gamepad text-light/20 text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-light text-sm font-medium truncate group-hover:text-accent transition-colors">
                                        <?php echo htmlspecialchars($pjeu['titre']); ?>
                                    </p>
                                    <p class="text-light-80 text-xs">
                                        <i class="fas fa-vote-yea mr-1"></i><?php echo intval($pjeu['nb_votes']); ?> votes
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Candidats Actifs -->
                <div class="glass-card rounded-3xl p-5 modern-border border-2 border-white/10">
                    <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-star text-yellow-400"></i> Candidats Actifs
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($active_candidats as $candidat): ?>
                            <a href="jeu-campagne.php?id=<?php echo intval($candidat['id_jeu']); ?>" 
                               class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition-colors group border border-white/10 hover:border-yellow-500/30">
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-yellow-500/20 border-2 border-yellow-500/30 flex-shrink-0">
                                    <?php if (!empty($candidat['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" alt="<?php echo htmlspecialchars($candidat['nom']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-user text-yellow-400 text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-yellow-400 text-sm font-medium truncate">
                                        <?php echo htmlspecialchars($candidat['nom']); ?>
                                    </p>
                                    <p class="text-light-80 text-xs truncate">
                                        <?php echo htmlspecialchars(substr($candidat['jeu_titre'], 0, 20)); ?>
                                    </p>
                                </div>
                                <span class="text-xs text-light-80 flex-shrink-0">
                                    <i class="fas fa-comments mr-1"></i><?php echo intval($candidat['nb_posts']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-3 order-1 lg:order-2">
                <?php if (!empty($search)): ?>
                    <div class="mb-6 flex items-center justify-between">
                        <p class="text-light-80">
                            <span class="text-light font-medium"><?php echo count($jeux); ?></span> résultat(s) pour 
                            "<span class="text-accent font-medium"><?php echo htmlspecialchars($search); ?></span>"
                        </p>
                        <a href="salon-jeux.php" class="text-sm text-light-80 hover:text-light transition-colors">
                            <i class="fas fa-times mr-1"></i>Réinitialiser
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (empty($jeux)): ?>
                    <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center">
                        <i class="fas fa-search text-5xl text-light/20 mb-6"></i>
                        <h2 class="text-2xl font-bold text-light mb-3">Aucun jeu trouvé</h2>
                        <p class="text-light-80 mb-6">Essayez avec d'autres critères de recherche</p>
                        <a href="salon-jeux.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                            <i class="fas fa-redo"></i> Voir tous les jeux
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($jeux as $jeu): ?>
                            <a href="jeu-campagne.php?id=<?php echo intval($jeu['id_jeu']); ?>" class="group">
                                <div class="glass-card rounded-2xl overflow-hidden modern-border border-2 border-white/10 hover:border-accent/50 transition-all duration-300 h-full flex flex-col">
                                    <div class="relative h-44 bg-accent/10 overflow-hidden">
                                        <?php if (!empty($jeu['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-gamepad text-5xl text-light/10"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute top-3 right-3 flex gap-2">
                                            <?php if (intval($jeu['nb_votes']) > 0): ?>
                                                <span class="px-3 py-1.5 rounded-lg bg-black/60 backdrop-blur-sm text-xs font-medium text-accent border border-accent/30">
                                                    <i class="fas fa-vote-yea mr-1"></i><?php echo intval($jeu['nb_votes']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (intval($jeu['nb_comments']) > 0): ?>
                                                <span class="px-3 py-1.5 rounded-lg bg-black/60 backdrop-blur-sm text-xs font-medium text-purple-400 border border-purple-500/30">
                                                    <i class="fas fa-comments mr-1"></i><?php echo intval($jeu['nb_comments']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($jeu['candidat_nom'])): ?>
                                            <div class="absolute bottom-3 left-3 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm border border-yellow-500/30">
                                                <div class="w-5 h-5 rounded-full overflow-hidden bg-yellow-500/20 border border-yellow-500/50">
                                                    <?php if (!empty($jeu['candidat_photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($jeu['candidat_photo']); ?>" alt="<?php echo htmlspecialchars($jeu['candidat_nom']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <i class="fas fa-user text-yellow-400 text-xs"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="text-yellow-400 text-xs font-medium">
                                                    <?php echo htmlspecialchars($jeu['candidat_nom']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-5 flex flex-col flex-1">
                                        <h3 class="text-lg font-bold text-light mb-1 group-hover:text-accent transition-colors truncate">
                                            <?php echo htmlspecialchars($jeu['titre']); ?>
                                        </h3>
                                        <?php if (!empty($jeu['editeur'])): ?>
                                            <p class="text-light-80 text-sm mb-3 truncate">
                                                <?php echo htmlspecialchars($jeu['editeur']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between pt-3 mt-auto border-t border-white/10">
                                            <span class="text-xs text-light-80">
                                                <?php if (!empty($jeu['candidat_nom'])): ?>
                                                    <i class="fas fa-crown text-yellow-500 mr-1"></i>
                                                    <span class="font-medium text-yellow-400">Avec candidat</span>
                                                <?php else: ?>
                                                    <i class="fas fa-gamepad mr-1"></i>
                                                    <span>Sans candidat</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-accent text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
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