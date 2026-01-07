<?php

declare(strict_types=1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'classes/init.php';

// ✅ VÉRIFIER QUE L'UTILISATEUR EST CANDIDAT
if (!isCandidate()) {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
    exit;
}

$id_utilisateur = (int)getAuthUserId();

// ✅ RÉCUPÉRER LE SERVICE VIA SERVICECONTAINER
$campaignService = ServiceContainer::getCandidatCampaignService();

// ✅ RÉCUPÉRER LES DONNÉES
$data = $campaignService->getCampaignData($id_utilisateur);
$candidat = $data['candidat'];
$commentaires = $data['commentaires'];
$stats = $data['stats'];
$error = '';
$success = '';

// ✅ TRAITEMENT SOUMISSION - GESTION COMMENTAIRES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_comment') {
        $result = $campaignService->addComment($id_utilisateur, $_POST['contenu'] ?? '');
        
        if ($result['success']) {
            $success = $result['message'];
            // Rafraîchir les données
            $data = $campaignService->getCampaignData($id_utilisateur);
            $commentaires = $data['commentaires'];
            $stats = $data['stats'];
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'delete_comment') {
        $result = $campaignService->deleteComment($id_utilisateur, (int)($_POST['id_comment'] ?? 0));
        
        if ($result['success']) {
            $success = $result['message'];
            // Rafraîchir les données
            $data = $campaignService->getCampaignData($id_utilisateur);
            $commentaires = $data['commentaires'];
            $stats = $data['stats'];
        } else {
            $error = $result['message'];
        }
    }
}

require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <!-- Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="md:col-span-2">
                <div class="text-center md:text-left">
                    <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                        <i class="fas fa-megaphone text-accent mr-3"></i>Ma Campagne
                    </h1>
                    <p class="text-xl text-light/80">Interagissez avec les électeurs pour promouvoir votre jeu</p>
                </div>
            </div>
            <div class="flex items-center justify-center">
                <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 text-center">
                    <div class="text-3xl font-bold text-accent mb-2"><?php echo $stats['nb_votes_cat']; ?></div>
                    <p class="text-light/60 text-sm">Votes catégories</p>
                    <div class="mt-4 h-1 bg-gradient-to-r from-accent/20 to-accent/5 rounded-full"></div>
                    <div class="text-2xl font-bold text-accent mt-4 mb-2"><?php echo $stats['nb_votes_final']; ?></div>
                    <p class="text-light/60 text-sm">Votes final</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profil candidat -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-12">
            <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-user-circle text-accent text-xl"></i>
                </div>
                <span>Mon profil</span>
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:items-center">
                <div class="flex flex-col gap-4">
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-light/60 text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-accent text-lg"></i>Nom
                        </p>
                        <p class="text-lg font-bold text-light"><?php echo htmlspecialchars($candidat['nom'] ?? 'Non défini'); ?></p>
                    </div>
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-light/60 text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-envelope text-accent text-lg"></i>Email
                        </p>
                        <p class="text-lg font-bold text-light break-all"><?php echo htmlspecialchars($candidat['email'] ?? ''); ?></p>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-6">
                    <?php if (!empty($candidat['jeu_image'])): ?>
                        <div class="relative">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-accent/20 to-accent/5 blur-xl"></div>
                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" alt="<?php echo htmlspecialchars($candidat['jeu_titre']); ?>" class="relative w-52 h-52 rounded-full object-cover border-4 border-accent/30 shadow-lg shadow-accent/20">
                        </div>
                    <?php else: ?>
                        <div class="w-52 h-52 rounded-full bg-white/5 border-4 border-white/10 flex items-center justify-center">
                            <i class="fas fa-image text-5xl text-light/20"></i>
                        </div>
                    <?php endif; ?>
                    <div class="p-6 rounded-2xl bg-accent/10 border border-accent/30 text-center w-full">
                        <p class="text-light/60 text-sm mb-2 flex items-center justify-center gap-2">
                            <i class="fas fa-gamepad text-accent text-lg"></i>Jeu représenté
                        </p>
                        <p class="text-xl font-bold text-accent"><?php echo htmlspecialchars($candidat['jeu_titre'] ?? 'Aucun jeu sélectionné'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <a href="candidat-profil.php" class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 hover:border-accent/50 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                        <i class="fas fa-edit text-accent text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-light">Modifier mon profil</p>
                        <p class="text-sm text-light/60">Mettez à jour vos informations</p>
                    </div>
                </div>
            </a>
            <a href="candidat-statistiques.php" class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 hover:border-accent/50 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                        <i class="fas fa-chart-bar text-accent text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-light">Voir mes statistiques</p>
                        <p class="text-sm text-light/60">Analyses détaillées</p>
                    </div>
                </div>
            </a>
            <a href="#" class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 hover:border-accent/50 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                        <i class="fas fa-globe text-accent text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-light">Page publique du jeu</p>
                        <p class="text-sm text-light/60">Voir comme les électeurs</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Fil de discussion -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
            <h2 class="text-2xl font-bold font-orbitron mb-8 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-comments text-accent text-xl"></i>
                </div>
                <span>Publier un message</span>
            </h2>

            <?php if (!empty($candidat['id_jeu'])): ?>
                <div class="mb-8 p-6 rounded-2xl bg-white/5 border border-white/10">
                    <p class="text-light/80 mb-4">Partagez des actualités, répondez aux électeurs, faites votre promotion !</p>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_comment">
                        <textarea
                            name="contenu"
                            placeholder="Écrivez votre message ici..."
                            class="form-control bg-white/5 border border-white/10 text-light placeholder-light/40 rounded-2xl focus:border-accent focus:ring-2 focus:ring-accent/20 resize-none"
                            rows="4"
                            maxlength="1000"></textarea>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-light/60"><span class="conteur">0</span>/1000</p>
                            <button type="submit" class="px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors flex items-center gap-2">
                                <i class="fas fa-paper-plane"></i> Publier
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30 mb-8">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-orange-400 text-xl"></i>
                        <div>
                            <p class="text-orange-400 font-bold">Jeu non sélectionné</p>
                            <p class="text-orange-300/80 text-sm">Vous devez d'abord sélectionner un jeu dans votre <a href="candidat-profil.php" class="underline hover:text-orange-200">profil</a> pour publier des messages.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fil de discussion -->
            <h3 class="text-xl font-bold font-orbitron mb-6 flex items-center gap-3">
                <i class="fas fa-comments text-accent"></i>
                <span>Fil de discussion (<?php echo count($commentaires); ?>)</span>
            </h3>

            <?php if (empty($commentaires)): ?>
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/5 mb-4">
                        <i class="fas fa-comment text-3xl text-light/20"></i>
                    </div>
                    <p class="text-light/80 text-lg mb-2">Aucun message pour le moment.</p>
                    <p class="text-light/60">Publiez votre premier message pour lancer la discussion !</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($commentaires as $comment):
                        $isOwner = $comment['is_mine'];
                        $isCandidates = $comment['type'] === 'candidat';
                        $isAdmin = $comment['type'] === 'admin';
                    ?>
                        <div class="p-6 rounded-2xl bg-white/5 border border-white/10 <?php echo $isOwner ? 'border-l-4 border-l-accent bg-accent/5' : ''; ?>">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                                        <i class="fas fa-user-circle text-accent"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-light">
                                            <?php echo htmlspecialchars($comment['pseudo']); ?>
                                            <?php if ($isOwner): ?>
                                                <span class="text-xs ml-2 px-2 py-1 rounded-full bg-accent/20 text-accent">Vous</span>
                                            <?php endif; ?>
                                            <?php if ($isCandidates): ?>
                                                <span class="text-xs ml-2 px-2 py-1 rounded-full bg-purple-500/20 text-purple-400">Candidat</span>
                                            <?php endif; ?>
                                            <?php if ($isAdmin): ?>
                                                <span class="text-xs ml-2 px-2 py-1 rounded-full bg-red-500/20 text-red-400">Admin</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-light/60"><?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?></p>
                                    </div>
                                </div>
                                <?php if ($isOwner): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="id_comment" value="<?php echo $comment['id_commentaire']; ?>">
                                        <button type="submit" class="text-light/40 hover:text-red-400 transition-colors" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="text-light/80 leading-relaxed"><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
// Compteur de caractères
document.querySelector('textarea[name="contenu"]')?.addEventListener('input', function() {
    document.querySelector('.conteur').textContent = this.value.length;
});
</script>

<?php require_once 'footer.php'; ?>