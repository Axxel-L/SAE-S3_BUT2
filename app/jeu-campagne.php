<?php
require_once 'classes/init.php';
$id_jeu = intval($_GET['id'] ?? 0);
$error = '';
$success = '';
$jeu = null;
$commentaires = [];
$stats = ['nb_votes_cat' => 0, 'nb_votes_final' => 0, 'nb_comments' => 0];
$userId = AuthenticationService::getAuthenticatedUserId();
try {
    $gameService = ServiceContainer::getGameService();
    $jeu = $gameService->getGameWithCandidat($id_jeu);
    if (!$jeu) {
        header('Location: index.php');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        if (!$userId) {
            $error = "Vous devez être connecté pour commenter !";
        } else {
            $contenu = $_POST['contenu'] ?? '';
            $result = $gameService->addComment($userId, $id_jeu, $contenu);
            
            if ($result['success']) {
                $success = "Commentaire publié !";
            } else {
                $error = implode(', ', $result['errors']);
            }
        }
    }
    
    $commentaires = $gameService->getComments($id_jeu);
    $stats = $gameService->getGameStats($id_jeu);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}
require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6 lg:px-16">
    <div class="container mx-auto max-w-7xl">
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
        
        <!-- Entête du jeu -->
        <div class="glass-card rounded-3xl overflow-hidden modern-border border-2 border-white/10 mb-8">
            <div class="relative h-48 md:h-64 bg-accent/10">
                <?php if ($jeu['image']): ?>
                    <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover opacity-50">
                    <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/50 to-transparent"></div>
                <?php endif; ?>
                <div class="absolute bottom-0 left-0 right-0 p-6">
                    <div class="flex items-end gap-6">
                        <div class="w-24 h-24 md:w-32 md:h-32 rounded-2xl overflow-hidden border-4 border-dark shadow-xl bg-dark/80 flex-shrink-0">
                            <?php if ($jeu['image']): ?>
                                <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-white/5">
                                    <i class="fas fa-gamepad text-4xl text-light/30"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 mb-2">
                            <h1 class="text-3xl md:text-4xl font-bold font-orbitron text-light mb-2">
                                <?php echo htmlspecialchars($jeu['titre']); ?>
                            </h1>
                            <?php if ($jeu['editeur']): ?>
                                <p class="text-accent font-medium">
                                    <i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($jeu['editeur']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="p-6 grid grid-cols-3 gap-4 border-t border-white/10">
                <div class="text-center">
                    <div class="text-2xl font-bold text-accent"><?php echo $stats['nb_votes_cat']; ?></div>
                    <div class="text-xs text-light-80">Votes catégories</div>
                </div>
                <div class="text-center border-x border-white/10">
                    <div class="text-2xl font-bold text-purple-400"><?php echo $stats['nb_votes_final']; ?></div>
                    <div class="text-xs text-light-80">Votes final</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-light"><?php echo $stats['nb_comments']; ?></div>
                    <div class="text-xs text-light-80">Commentaires</div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <?php if ($jeu['description']): ?>
                    <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                        <h2 class="text-xl font-bold text-light mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-accent"></i> À propos du jeu
                        </h2>
                        <p class="text-light-80 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($jeu['description'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                    <h2 class="text-xl font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-comment-dots text-accent"></i> Laisser un commentaire
                    </h2>
                    <?php if ($userId): ?>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_comment">
                            <textarea 
                                name="contenu" 
                                required 
                                minlength="3" 
                                maxlength="1000" 
                                rows="3"
                                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light placeholder-light/30 resize-none transition-all"
                                placeholder="Partagez votre avis sur ce jeu...">
                            </textarea>
                            <p class="text-xs text-light-80">
                                Votre pseudo : <span class="text-accent font-medium">
                                    <?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Anonyme'); ?>
                                </span>
                            </p>
                            <button type="submit" class="px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors flex items-center gap-2 border border-white/10">
                                <i class="fas fa-paper-plane"></i> Publier
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-lock text-4xl text-light/20 mb-4"></i>
                            <p class="text-light-80 mb-4">Connectez-vous pour laisser un commentaire</p>
                            <a href="login.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                    <h2 class="text-xl font-bold text-light mb-6 flex items-center gap-2">
                        <i class="fas fa-comments text-accent"></i> Commentaires (<?php echo count($commentaires); ?>)
                    </h2>
                    <?php if (empty($commentaires)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comment-slash text-4xl text-light/20 mb-4"></i>
                            <p class="text-light-80">Aucun commentaire pour le moment.</p>
                            <p class="text-light-80 text-sm mt-2">Soyez le premier à donner votre avis !</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($commentaires as $comment): 
                                $is_owner = $comment['is_owner'] == 1;
                                $is_candidat = $comment['type'] === 'candidat';
                                $is_admin = $comment['type'] === 'admin';
                                if ($is_owner) {
                                    $pseudo_class = 'text-yellow-400 font-bold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30"><i class="fas fa-crown mr-1"></i>Candidat</span>';
                                } elseif ($is_candidat) {
                                    $pseudo_class = 'text-purple-400 font-semibold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-purple-500/20 text-purple-400 border border-purple-500/30"><i class="fas fa-trophy mr-1"></i>Candidat</span>';
                                } elseif ($is_admin) {
                                    $pseudo_class = 'text-red-400 font-semibold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-red-500/20 text-red-400 border border-red-500/30"><i class="fas fa-shield-alt mr-1"></i>Admin</span>';
                                } else {
                                    $pseudo_class = 'text-accent';
                                    $badge = '';
                                }
                            ?>
                                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors <?php echo $is_owner ? 'border-l-4 border-l-yellow-500' : ''; ?>">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center">
                                            <span class="<?php echo $pseudo_class; ?>">
                                                <?php echo htmlspecialchars($comment['pseudo'] ?? 'Anonyme'); ?>
                                            </span>
                                            <?php echo $badge; ?>
                                        </div>
                                        <span class="text-xs text-light-80">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-light-80 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($comment['contenu'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="space-y-6">
                <?php if ($jeu['candidat_nom']): ?>
                    <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                        <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                            <i class="fas fa-user-tie text-yellow-400"></i> Représenté par
                        </h3>
                        <div class="text-center">
                            <div class="w-24 h-24 rounded-full mx-auto mb-4 overflow-hidden border-4 border-yellow-500/30 shadow-lg shadow-yellow-500/20">
                                <?php if ($jeu['candidat_photo']): ?>
                                    <img src="<?php echo htmlspecialchars($jeu['candidat_photo']); ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-yellow-500/20 flex items-center justify-center">
                                        <i class="fas fa-user text-3xl text-yellow-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-xl font-bold text-yellow-400 mb-2">
                                <?php echo htmlspecialchars($jeu['candidat_nom']); ?>
                            </h4>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-yellow-500/20 text-yellow-400 text-xs border border-yellow-500/30">
                                <i class="fas fa-crown"></i> Candidat Officiel
                            </span>
                            <?php if ($jeu['candidat_bio']): ?>
                                <p class="text-light-80 text-sm mt-4 leading-relaxed">
                                    <?php 
                                    $bio = substr($jeu['candidat_bio'], 0, 200);
                                    echo nl2br(htmlspecialchars($bio));
                                    if(strlen($jeu['candidat_bio']) > 200) echo '...';
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Infos du jeu -->
                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                    <h3 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                        <i class="fas fa-gamepad text-accent"></i> Informations
                    </h3>
                    <div class="space-y-3">
                        <?php if ($jeu['editeur']): ?>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-light-80 text-sm">Éditeur</span>
                                <span class="text-light font-medium"><?php echo htmlspecialchars($jeu['editeur']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($jeu['date_sortie']): ?>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-light-80 text-sm">Date de sortie</span>
                                <span class="text-light font-medium">
                                    <?php echo date('d/m/Y', strtotime($jeu['date_sortie'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-light-80 text-sm">Total votes</span>
                            <span class="text-accent font-bold">
                                <?php echo $stats['nb_votes_cat'] + $stats['nb_votes_final']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'footer.php'; ?>