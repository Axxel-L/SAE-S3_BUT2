<?php
/**
 * PAGE CAMPAGNE CANDIDAT - GameCrown
 * Interface moderne pour gérer sa campagne
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier candidat
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
$candidat = null;
$commentaires = [];

// Vérifier que le candidat a un profil validé
try {
    $stmt = $connexion->prepare("
        SELECT c.*, j.id_jeu, j.titre as titre_jeu, j.image as jeu_image, j.editeur, j.description as jeu_description
        FROM candidat c 
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$candidat) {
        header('Location: candidat-profil.php');
        exit;
    }
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Traitement ajout commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_comment') {
        $contenu = htmlspecialchars(trim($_POST['contenu'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        if (!empty($contenu) && !empty($candidat['id_jeu'])) {
            if (strlen($contenu) < 3) {
                $error = "Message trop court (min 3 caractères) !";
            } elseif (strlen($contenu) > 1000) {
                $error = "Message trop long (max 1000 caractères) !";
            } else {
                try {
                    $stmt = $connexion->prepare("
                        INSERT INTO commentaire (id_utilisateur, id_jeu, contenu, date_commentaire) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$id_utilisateur, $candidat['id_jeu'], $contenu]);
                    $success = "Message publié !";
                    
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'CAMPAGNE_POST', ?, ?)");
                    $stmt->execute([$id_utilisateur, "Jeu: " . $candidat['id_jeu'], $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (Exception $e) {
                    $error = "Erreur lors de la publication.";
                }
            }
        } else {
            $error = "Veuillez écrire un message !";
        }
    }
    
    // Supprimer un commentaire
    if ($_POST['action'] === 'delete_comment') {
        $id_comment = intval($_POST['id_comment'] ?? 0);
        if ($id_comment > 0) {
            try {
                $stmt = $connexion->prepare("DELETE FROM commentaire WHERE id_commentaire = ? AND id_utilisateur = ?");
                $stmt->execute([$id_comment, $id_utilisateur]);
                $success = "Message supprimé !";
            } catch (Exception $e) {
                $error = "Erreur lors de la suppression.";
            }
        }
    }
}

// Récupérer les commentaires du jeu
try {
    $stmt = $connexion->prepare("
        SELECT 
            com.id_commentaire,
            com.contenu,
            com.date_commentaire,
            u.id_utilisateur,
            u.pseudo,
            u.type,
            CASE WHEN u.id_utilisateur = ? THEN 1 ELSE 0 END as is_mine
        FROM commentaire com
        JOIN utilisateur u ON com.id_utilisateur = u.id_utilisateur
        WHERE com.id_jeu = ?
        ORDER BY com.date_commentaire DESC
        LIMIT 100
    ");
    $stmt->execute([$id_utilisateur, $candidat['id_jeu']]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $commentaires = [];
}

// Stats
$stats = [
    'nb_votes_cat' => 0,
    'nb_votes_final' => 0,
    'nb_comments' => count($commentaires),
    'my_comments' => 0
];

try {
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM bulletin_categorie WHERE id_jeu = ?");
    $stmt->execute([$candidat['id_jeu']]);
    $stats['nb_votes_cat'] = $stmt->fetchColumn();
    
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM bulletin_final WHERE id_jeu = ?");
    $stmt->execute([$candidat['id_jeu']]);
    $stats['nb_votes_final'] = $stmt->fetchColumn();
    
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM commentaire WHERE id_jeu = ? AND id_utilisateur = ?");
    $stmt->execute([$candidat['id_jeu'], $id_utilisateur]);
    $stats['my_comments'] = $stmt->fetchColumn();
} catch (Exception $e) {}

require_once 'header.php';
?>

<section class="py-12 px-6 min-h-screen">
    <div class="container mx-auto max-w-6xl">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-white/60 text-sm mb-4">
                <a href="candidat-profil.php" class="hover:text-cyan-400 transition-colors">Mon profil</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-cyan-400">Ma campagne</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold font-orbitron text-white mb-2">
                <i class="fas fa-bullhorn text-yellow-400 mr-3"></i>Ma Campagne
            </h1>
            <p class="text-white/60">Interagissez avec les électeurs pour <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'votre jeu'); ?></p>
        </div>
        
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-cyan-400 mb-1"><?php echo $stats['nb_votes_cat']; ?></div>
                <div class="text-xs text-white/60"><i class="fas fa-vote-yea mr-1"></i>Votes catégories</div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-purple-400 mb-1"><?php echo $stats['nb_votes_final']; ?></div>
                <div class="text-xs text-white/60"><i class="fas fa-crown mr-1"></i>Votes final</div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-white mb-1"><?php echo $stats['nb_comments']; ?></div>
                <div class="text-xs text-white/60"><i class="fas fa-comments mr-1"></i>Commentaires</div>
            </div>
            <div class="glass-card rounded-2xl p-5 modern-border text-center">
                <div class="text-3xl font-bold text-yellow-400 mb-1"><?php echo $stats['my_comments']; ?></div>
                <div class="text-xs text-white/60"><i class="fas fa-pen mr-1"></i>Mes posts</div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Colonne principale -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Publier un message -->
                <div class="glass-card rounded-3xl p-6 modern-border border-l-4 border-l-yellow-500">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-edit text-yellow-400"></i> Publier un message
                    </h2>
                    <p class="text-white/60 text-sm mb-4">Partagez des actualités, répondez aux électeurs, faites votre promotion !</p>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_comment">
                        <div>
                            <textarea name="contenu" required minlength="3" maxlength="1000" rows="4"
                                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-yellow-500/50 outline-none text-white placeholder-white/30 resize-none transition-all"
                                placeholder="Écrivez votre message ici... (max 1000 caractères)"></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-white/40">Signé : <span class="text-yellow-400 font-medium"><?php echo htmlspecialchars($candidat['nom']); ?></span> <span class="px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400 text-xs ml-1">Candidat</span></p>
                            <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 text-dark font-bold hover:shadow-lg hover:shadow-yellow-500/30 transition-all flex items-center gap-2">
                                <i class="fas fa-paper-plane"></i> Publier
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Fil des commentaires -->
                <div class="glass-card rounded-3xl p-6 modern-border">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-stream text-cyan-400"></i> Fil de discussion (<?php echo count($commentaires); ?>)
                    </h2>
                    
                    <?php if (empty($commentaires)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comment-slash text-4xl text-white/20 mb-4"></i>
                            <p class="text-white/60">Aucun message pour le moment.</p>
                            <p class="text-white/40 text-sm mt-2">Publiez votre premier message pour lancer la discussion !</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($commentaires as $comment): 
                                $is_mine = $comment['is_mine'] == 1;
                                $is_candidat = $comment['type'] === 'candidat';
                                $is_admin = $comment['type'] === 'admin';
                                
                                if ($is_mine) {
                                    $pseudo_class = 'text-yellow-400 font-bold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30"><i class="fas fa-crown mr-1"></i>Vous</span>';
                                    $card_class = 'border-l-4 border-l-yellow-500 bg-yellow-500/5';
                                } elseif ($is_candidat) {
                                    $pseudo_class = 'text-purple-400 font-semibold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-purple-500/20 text-purple-400 border border-purple-500/30">Candidat</span>';
                                    $card_class = '';
                                } elseif ($is_admin) {
                                    $pseudo_class = 'text-red-400 font-semibold';
                                    $badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-red-500/20 text-red-400 border border-red-500/30">Admin</span>';
                                    $card_class = '';
                                } else {
                                    $pseudo_class = 'text-cyan-400';
                                    $badge = '';
                                    $card_class = '';
                                }
                            ?>
                                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors <?php echo $card_class; ?>">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center flex-wrap">
                                            <span class="<?php echo $pseudo_class; ?>"><?php echo htmlspecialchars($comment['pseudo'] ?? 'Anonyme'); ?></span>
                                            <?php echo $badge; ?>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-white/40">
                                                <?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?>
                                            </span>
                                            <?php if ($is_mine): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce message ?');">
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="id_comment" value="<?php echo $comment['id_commentaire']; ?>">
                                                    <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors">
                                                        <i class="fas fa-trash-alt text-sm"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-white/80 leading-relaxed"><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Mon jeu -->
                <div class="glass-card rounded-3xl overflow-hidden modern-border">
                    <?php if ($candidat['jeu_image']): ?>
                        <div class="h-40 bg-gradient-to-br from-cyan-500/20 to-purple-500/20">
                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" alt="" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-white mb-2"><?php echo htmlspecialchars($candidat['titre_jeu']); ?></h3>
                        <?php if ($candidat['editeur']): ?>
                            <p class="text-cyan-400 text-sm mb-3"><i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($candidat['editeur']); ?></p>
                        <?php endif; ?>
                        <a href="jeu-campagne.php?id=<?php echo $candidat['id_jeu']; ?>" class="inline-flex items-center gap-2 text-sm text-white/60 hover:text-cyan-400 transition-colors">
                            <i class="fas fa-external-link-alt"></i> Voir la page publique
                        </a>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="glass-card rounded-3xl p-5 modern-border">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-400"></i> Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="candidat-profil.php" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-white/80 hover:text-white">
                            <i class="fas fa-user-edit text-cyan-400 w-5"></i>
                            <span>Modifier mon profil</span>
                        </a>
                        <a href="candidat-stats.php" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-white/80 hover:text-white">
                            <i class="fas fa-chart-line text-green-400 w-5"></i>
                            <span>Voir mes statistiques</span>
                        </a>
                        <a href="jeu-campagne.php?id=<?php echo $candidat['id_jeu']; ?>" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-white/80 hover:text-white">
                            <i class="fas fa-eye text-purple-400 w-5"></i>
                            <span>Page publique du jeu</span>
                        </a>
                    </div>
                </div>
                
                <!-- Conseils -->
                <div class="glass-card rounded-3xl p-5 modern-border bg-gradient-to-br from-yellow-500/5 to-orange-500/5">
                    <h3 class="text-lg font-bold text-yellow-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> Conseils
                    </h3>
                    <ul class="space-y-3 text-sm text-white/70">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1"></i>
                            <span>Publiez régulièrement pour rester visible</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1"></i>
                            <span>Répondez aux commentaires des électeurs</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1"></i>
                            <span>Partagez des actualités sur votre jeu</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1"></i>
                            <span>Restez courtois et professionnel</span>
                        </li>
                    </ul>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>