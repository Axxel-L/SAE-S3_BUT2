<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

// Vérifier candidat
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
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

// Ajouter un commentaire
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

// Statistiques
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

<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center gap-2 text-light/60 text-sm mb-4">
                <a href="candidat-profil.php" class="hover:text-accent transition-colors">Mon profil</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-accent font-medium">Ma campagne</span>
            </div>
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-bullhorn text-accent mr-3"></i>Ma Campagne
            </h1>
            <p class="text-xl text-light/80">Interagissez avec les électeurs pour <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'votre jeu'); ?></p>
        </div>
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold text-accent mb-2"><?php echo $stats['nb_votes_cat']; ?></div>
                <div class="text-sm text-light/60 flex items-center justify-center gap-2">
                    <i class="fas fa-layer-group text-accent"></i>
                    <span>Votes catégories</span>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold text-purple-400 mb-2"><?php echo $stats['nb_votes_final']; ?></div>
                <div class="text-sm text-light/60 flex items-center justify-center gap-2">
                    <i class="fas fa-crown text-purple-400"></i>
                    <span>Votes final</span>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold text-light mb-2"><?php echo $stats['nb_comments']; ?></div>
                <div class="text-sm text-light/60 flex items-center justify-center gap-2">
                    <i class="fas fa-comments text-light"></i>
                    <span>Commentaires</span>
                </div>
            </div>
            <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                <div class="text-4xl font-bold text-yellow-400 mb-2"><?php echo $stats['my_comments']; ?></div>
                <div class="text-sm text-light/60 flex items-center justify-center gap-2">
                    <i class="fas fa-pen text-yellow-400"></i>
                    <span>Mes posts</span>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Publier un message -->
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                            <i class="fas fa-edit text-accent text-xl"></i>
                        </div>
                        <span>Publier un message</span>
                    </h2>
                    <p class="text-light/80 mb-6">Partagez des actualités, répondez aux électeurs, faites votre promotion !</p>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="add_comment">
                        <div>
                            <textarea name="contenu" required minlength="3" maxlength="1000" rows="5"
                                class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 outline-none placeholder:text-light/40 resize-none transition-all font-medium"
                                placeholder="Écrivez votre message ici... (max 1000 caractères)"></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-light/60">Signé :</span>
                                <span class="text-accent font-bold text-lg"><?php echo htmlspecialchars($candidat['nom']); ?></span>
                                <span class="px-3 py-1 rounded-full bg-accent/20 text-accent border border-accent/30 text-xs">
                                    <i class="fas fa-crown mr-1"></i>Candidat
                                </span>
                            </div>
                            <button type="submit" class="px-8 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border-2 border-white/10 flex items-center gap-3">
                                <i class="fas fa-paper-plane"></i> Publier
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Fil des commentaires -->
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                            <i class="fas fa-stream text-accent text-xl"></i>
                        </div>
                        <span>Fil de discussion (<?php echo count($commentaires); ?>)</span>
                    </h2>
                    <?php if (empty($commentaires)): ?>
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white/5 mb-6">
                                <i class="fas fa-comment-slash text-4xl text-light/20"></i>
                            </div>
                            <p class="text-light/80 text-lg mb-2">Aucun message pour le moment.</p>
                            <p class="text-light/60">Publiez votre premier message pour lancer la discussion !</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                            <?php foreach ($commentaires as $comment): 
                                $is_mine = $comment['is_mine'] == 1;
                                $is_candidat = $comment['type'] === 'candidat';
                                $is_admin = $comment['type'] === 'admin';
                                
                                if ($is_mine) {
                                    $pseudo_class = 'text-accent font-bold';
                                    $badge = '<span class="ml-2 px-3 py-1 rounded-full text-xs bg-accent/20 text-accent border border-accent/30"><i class="fas fa-crown mr-1"></i>Vous</span>';
                                    $card_class = 'border-l-4 border-l-accent bg-accent/5';
                                } elseif ($is_candidat) {
                                    $pseudo_class = 'text-purple-400 font-semibold';
                                    $badge = '<span class="ml-2 px-3 py-1 rounded-full text-xs bg-purple-500/20 text-purple-400 border border-purple-500/30">Candidat</span>';
                                    $card_class = '';
                                } elseif ($is_admin) {
                                    $pseudo_class = 'text-red-400 font-semibold';
                                    $badge = '<span class="ml-2 px-3 py-1 rounded-full text-xs bg-red-500/20 text-red-400 border border-red-500/30">Admin</span>';
                                    $card_class = '';
                                } else {
                                    $pseudo_class = 'text-light';
                                    $badge = '';
                                    $card_class = '';
                                }
                            ?>
                                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors <?php echo $card_class; ?>">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center flex-wrap">
                                            <span class="<?php echo $pseudo_class; ?> text-lg font-medium"><?php echo htmlspecialchars($comment['pseudo'] ?? 'Anonyme'); ?></span>
                                            <?php echo $badge; ?>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-sm text-light/60">
                                                <?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?>
                                            </span>
                                            <?php if ($is_mine): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce message ?');">
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="id_comment" value="<?php echo $comment['id_commentaire']; ?>">
                                                    <button type="submit" class="text-red-400/60 hover:text-red-400 transition-colors">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-light/80 leading-relaxed"><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="space-y-8">
                
                <!-- Jeu voté -->
                <div class="glass-card rounded-3xl overflow-hidden modern-border border-2 border-white/10">
                    <?php if ($candidat['jeu_image']): ?>
                        <div class="h-48 bg-gradient-to-br from-accent/20 to-purple-500/20">
                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" alt="<?php echo htmlspecialchars($candidat['titre_jeu']); ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-light mb-3"><?php echo htmlspecialchars($candidat['titre_jeu']); ?></h3>
                        <?php if ($candidat['editeur']): ?>
                            <p class="text-accent text-sm mb-4 flex items-center gap-2">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($candidat['editeur']); ?>
                            </p>
                        <?php endif; ?>
                        <a href="jeu-campagne.php?id=<?php echo $candidat['id_jeu']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-accent/10 text-accent border border-accent/30 hover:bg-accent/20 transition-colors text-sm font-medium">
                            <i class="fas fa-external-link-alt"></i> Voir la page publique
                        </a>
                    </div>
                </div>
    
                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10">
                    <h3 class="text-xl font-bold font-orbitron mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
                            <i class="fas fa-bolt text-accent"></i>
                        </div>
                        <span>Actions rapides</span>
                    </h3>
                    <div class="space-y-4">
                        <a href="candidat-profil.php" class="flex items-center gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-light hover:text-light border border-white/10">
                            <i class="fas fa-user-edit text-accent text-lg w-6"></i>
                            <div>
                                <p class="font-medium">Modifier mon profil</p>
                                <p class="text-xs text-light/60">Mettez à jour vos informations</p>
                            </div>
                        </a>
                        <a href="candidat-statistiques.php" class="flex items-center gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-light hover:text-light border border-white/10">
                            <i class="fas fa-chart-line text-green-400 text-lg w-6"></i>
                            <div>
                                <p class="font-medium">Voir mes statistiques</p>
                                <p class="text-xs text-light/60">Analyses détaillées</p>
                            </div>
                        </a>
                        <a href="jeu-campagne.php?id=<?php echo $candidat['id_jeu']; ?>" class="flex items-center gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors text-light hover:text-light border border-white/10">
                            <i class="fas fa-eye text-purple-400 text-lg w-6"></i>
                            <div>
                                <p class="font-medium">Page publique du jeu</p>
                                <p class="text-xs text-light/60">Voir comme les électeurs</p>
                            </div>
                        </a>
                    </div>
                </div>               
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>