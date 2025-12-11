<?php
/**
 * PROFIL CANDIDAT - GameCrown
 * Le candidat peut voir ses infos mais NE PEUT PAS changer de jeu
 */

session_start();
require_once 'dbconnect.php';

// Vérifier candidat AVANT d'inclure header.php
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Récupérer les infos du candidat
try {
    $stmt = $connexion->prepare("
        SELECT c.*, u.email, u.date_inscription, j.titre as titre_jeu, j.image as image_jeu, j.editeur
        FROM candidat c 
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$candidat) {
        header('Location: register.php');
        exit;
    }
} catch (Exception $e) {
    $candidat = null;
}

require_once 'header.php';

$error = '';
$success = '';

// Traitement de la mise à jour du PROFIL (pas du jeu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profil') {
        // Nettoyage des données
        $nom = htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bio = htmlspecialchars(trim($_POST['bio'] ?? ''), ENT_QUOTES, 'UTF-8');
        $photo = filter_var(trim($_POST['photo'] ?? ''), FILTER_SANITIZE_URL);
        
        // Validation
        if (empty($nom) || strlen($nom) < 2 || strlen($nom) > 100) {
            $error = "Le nom doit contenir entre 2 et 100 caractères !";
        } elseif (!empty($photo) && !filter_var($photo, FILTER_VALIDATE_URL)) {
            $error = "L'URL de la photo n'est pas valide !";
        } else {
            try {
                $stmt = $connexion->prepare("
                    UPDATE candidat 
                    SET nom = ?, bio = ?, photo = ?
                    WHERE id_utilisateur = ?
                ");
                $stmt->execute([
                    $nom, 
                    !empty($bio) ? $bio : null, 
                    !empty($photo) ? $photo : null, 
                    $id_utilisateur
                ]);
                
                $success = "Profil mis à jour avec succès ! ✅";
                
                // Log audit
                $stmt = $connexion->prepare("
                    INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) 
                    VALUES (?, 'CANDIDAT_PROFIL_UPDATE', 'Mise à jour du profil', ?)
                ");
                $stmt->execute([$id_utilisateur, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                // Rafraîchir les données
                $stmt = $connexion->prepare("
                    SELECT c.*, u.email, u.date_inscription, j.titre as titre_jeu, j.image as image_jeu, j.editeur
                    FROM candidat c 
                    JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
                    LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                    WHERE c.id_utilisateur = ?
                ");
                $stmt->execute([$id_utilisateur]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    }
}

// Statut du candidat
$statut_config = [
    'en_attente' => ['label' => 'En attente de validation', 'color' => 'yellow', 'icon' => 'fa-clock'],
    'valide' => ['label' => 'Validé', 'color' => 'green', 'icon' => 'fa-check-circle'],
    'refuse' => ['label' => 'Refusé', 'color' => 'red', 'icon' => 'fa-times-circle']
];
$statut = $statut_config[$candidat['statut'] ?? 'en_attente'] ?? $statut_config['en_attente'];
?>

<section class="py-20 px-6 min-h-screen">
    <div class="container mx-auto max-w-4xl">
        
        <div class="mb-8">
            <h1 class="text-4xl md:text-5xl font-bold font-orbitron mb-2">
                <i class="fas fa-user-circle text-accent mr-3"></i>Mon Profil
            </h1>
            <p class="text-light/60">Gérez votre profil de candidat</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($candidat): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Colonne gauche : Infos du jeu (non modifiable) -->
                <div class="lg:col-span-1">
                    <div class="glass-card rounded-3xl p-6 modern-border">
                        <h2 class="text-xl font-bold text-accent mb-4 flex items-center gap-2">
                            <i class="fas fa-gamepad"></i> Jeu représenté
                        </h2>
                        
                        <?php if (!empty($candidat['image_jeu'])): ?>
                            <div class="rounded-2xl overflow-hidden h-40 bg-black/50 mb-4">
                                <img src="<?php echo htmlspecialchars($candidat['image_jeu']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidat['titre_jeu']); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                        <?php else: ?>
                            <div class="rounded-2xl h-40 bg-white/5 mb-4 flex items-center justify-center">
                                <i class="fas fa-gamepad text-4xl text-light/30"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-lg font-bold text-light mb-1">
                            <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'Aucun jeu'); ?>
                        </h3>
                        <?php if (!empty($candidat['editeur'])): ?>
                            <p class="text-sm text-light/60 mb-4">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($candidat['editeur']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="p-3 rounded-xl bg-orange-500/10 border border-orange-500/30">
                            <p class="text-xs text-orange-400">
                                <i class="fas fa-lock mr-1"></i>
                                Le jeu ne peut pas être modifié après l'inscription.
                            </p>
                        </div>
                        
                        <!-- Statut -->
                        <div class="mt-4 p-3 rounded-xl bg-<?php echo $statut['color']; ?>-500/10 border border-<?php echo $statut['color']; ?>-500/30">
                            <div class="flex items-center gap-2">
                                <i class="fas <?php echo $statut['icon']; ?> text-<?php echo $statut['color']; ?>-400"></i>
                                <span class="text-<?php echo $statut['color']; ?>-400 font-medium text-sm">
                                    <?php echo $statut['label']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques rapides -->
                    <div class="glass-card rounded-3xl p-6 modern-border mt-6">
                        <h2 class="text-xl font-bold text-accent mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </h2>
                        
                        <?php
                        // Récupérer stats rapides
                        $votes_cat = 0; $votes_final = 0; $commentaires = 0;
                        try {
                            $stmt = $connexion->prepare("SELECT COUNT(*) as t FROM bulletin_categorie WHERE id_jeu = ?");
                            $stmt->execute([$candidat['id_jeu']]);
                            $votes_cat = $stmt->fetch()['t'];
                            
                            $stmt = $connexion->prepare("SELECT COUNT(*) as t FROM bulletin_final WHERE id_jeu = ?");
                            $stmt->execute([$candidat['id_jeu']]);
                            $votes_final = $stmt->fetch()['t'];
                            
                            $stmt = $connexion->prepare("SELECT COUNT(*) as t FROM commentaire WHERE id_jeu = ?");
                            $stmt->execute([$candidat['id_jeu']]);
                            $commentaires = $stmt->fetch()['t'];
                        } catch (Exception $e) {}
                        ?>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-light/60 text-sm">Votes catégories</span>
                                <span class="text-green-400 font-bold"><?php echo $votes_cat; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-light/60 text-sm">Votes finaux</span>
                                <span class="text-purple-400 font-bold"><?php echo $votes_final; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-light/60 text-sm">Commentaires</span>
                                <span class="text-accent font-bold"><?php echo $commentaires; ?></span>
                            </div>
                        </div>
                        
                        <a href="candidat-statistiques.php" class="block mt-4 text-center px-4 py-2 rounded-xl bg-accent/10 text-accent border border-accent/30 hover:bg-accent/20 transition-colors text-sm">
                            <i class="fas fa-chart-line mr-1"></i> Voir détails
                        </a>
                    </div>
                </div>
                
                <!-- Colonne droite : Formulaire profil (modifiable) -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-3xl p-8 modern-border">
                        <h2 class="text-xl font-bold text-accent mb-6 flex items-center gap-2">
                            <i class="fas fa-edit"></i> Modifier mon profil
                        </h2>
                        
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="update_profil">
                            
                            <!-- Email (non modifiable) -->
                            <div>
                                <label class="block mb-2 text-light/80 text-sm font-medium">
                                    <i class="fas fa-envelope text-accent mr-2"></i>Email
                                </label>
                                <input type="email" disabled
                                    class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light/50 cursor-not-allowed"
                                    value="<?php echo htmlspecialchars($candidat['email']); ?>">
                                <p class="text-xs text-light/40 mt-1">L'email ne peut pas être modifié.</p>
                            </div>
                            
                            <!-- Inscrit depuis -->
                            <div>
                                <label class="block mb-2 text-light/80 text-sm font-medium">
                                    <i class="fas fa-calendar text-accent mr-2"></i>Inscrit depuis
                                </label>
                                <input type="text" disabled
                                    class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light/50 cursor-not-allowed"
                                    value="<?php echo date('d/m/Y', strtotime($candidat['date_inscription'])); ?>">
                            </div>
                            
                            <hr class="border-white/10">
                            
                            <!-- Nom -->
                            <div>
                                <label class="block mb-2 text-light/80 text-sm font-medium">
                                    <i class="fas fa-id-card text-accent mr-2"></i>Nom *
                                </label>
                                <input type="text" name="nom" required minlength="2" maxlength="100"
                                    class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all"
                                    value="<?php echo htmlspecialchars($candidat['nom'] ?? ''); ?>"
                                    placeholder="Votre nom">
                            </div>
                            
                            <!-- Bio -->
                            <div>
                                <label class="block mb-2 text-light/80 text-sm font-medium">
                                    <i class="fas fa-align-left text-accent mr-2"></i>Biographie
                                </label>
                                <textarea name="bio" maxlength="500" rows="4"
                                    class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all resize-none"
                                    placeholder="Parlez de vous..."><?php echo htmlspecialchars($candidat['bio'] ?? ''); ?></textarea>
                                <p class="text-xs text-light/40 mt-1">Maximum 500 caractères</p>
                            </div>
                            
                            <!-- Photo -->
                            <div>
                                <label class="block mb-2 text-light/80 text-sm font-medium">
                                    <i class="fas fa-image text-accent mr-2"></i>Photo (URL)
                                </label>
                                <input type="url" name="photo" maxlength="500"
                                    class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all"
                                    value="<?php echo htmlspecialchars($candidat['photo'] ?? ''); ?>"
                                    placeholder="https://...">
                                
                                <?php if (!empty($candidat['photo'])): ?>
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" 
                                             alt="Photo actuelle" 
                                             class="w-16 h-16 rounded-xl object-cover border border-white/10">
                                        <span class="text-xs text-light/40">Photo actuelle</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="w-full px-6 py-4 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                    
                    <!-- Liens rapides -->
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <a href="candidat-campagne.php" class="glass-card rounded-2xl p-4 modern-border text-center hover:border-accent/50 transition-all">
                            <i class="fas fa-bullhorn text-2xl text-accent mb-2"></i>
                            <p class="text-light font-medium">Campagne</p>
                        </a>
                        <a href="candidat-statistiques.php" class="glass-card rounded-2xl p-4 modern-border text-center hover:border-accent/50 transition-all">
                            <i class="fas fa-chart-pie text-2xl text-purple-400 mb-2"></i>
                            <p class="text-light font-medium">Statistiques</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'footer.php'; ?>