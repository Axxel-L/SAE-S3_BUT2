<?php
/**
 * PROFIL CANDIDAT - GameCrown
 * Le candidat peut voir ses infos mais NE PEUT PAS changer de jeu
 */

session_start();
require_once 'dbconnect.php';

// Vérifier candidat AVANT d'inclure header.php
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
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

<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-4xl">
        
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-user-circle text-accent mr-3"></i>Mon Profil
            </h1>
            <p class="text-xl text-light/80">Gérez votre profil de candidat</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($candidat): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Colonne gauche : Infos du jeu (non modifiable) -->
                <div class="lg:col-span-1">
                    <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                        <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                            <i class="fas fa-gamepad text-accent"></i> Jeu représenté
                        </h2>
                        
                        <?php if (!empty($candidat['image_jeu'])): ?>
                            <div class="rounded-2xl overflow-hidden h-48 bg-black/50 mb-6">
                                <img src="<?php echo htmlspecialchars($candidat['image_jeu']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidat['titre_jeu']); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                        <?php else: ?>
                            <div class="rounded-2xl h-48 bg-white/5 mb-6 flex items-center justify-center">
                                <i class="fas fa-gamepad text-5xl text-light/30"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-2xl font-bold mb-2">
                            <span class="text-accent font-bold"><?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'Aucun jeu'); ?></span>
                        </h3>
                        <?php if (!empty($candidat['editeur'])): ?>
                            <p class="text-sm text-light/60 mb-6">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($candidat['editeur']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="p-4 rounded-2xl bg-orange-500/10 border border-orange-500/30 mb-6">
                            <p class="text-sm text-orange-400 flex items-center gap-2">
                                <i class="fas fa-lock"></i>
                                <span>Le jeu ne peut pas être modifié après l'inscription.</span>
                            </p>
                        </div>
                        
                        <!-- Statut -->
                        <div class="p-4 rounded-2xl bg-<?php echo $statut['color']; ?>-500/10 border border-<?php echo $statut['color']; ?>-500/30">
                            <div class="flex items-center gap-3">
                                <i class="fas <?php echo $statut['icon']; ?> text-2xl text-<?php echo $statut['color']; ?>-400"></i>
                                <div>
                                    <p class="text-<?php echo $statut['color']; ?>-400 font-bold text-lg">
                                        <?php echo $statut['label']; ?>
                                    </p>
                                    <p class="text-<?php echo $statut['color']; ?>-300/70 text-xs">Statut de votre candidature</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques rapides -->
                    <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mt-8">
                        <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-bar text-accent"></i> Statistiques
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
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 rounded-xl bg-green-500/10 border border-green-500/30">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-layer-group text-green-400 text-lg"></i>
                                    <div>
                                        <p class="text-light/60 text-xs">Votes catégories</p>
                                        <p class="text-green-400 font-bold text-xl"><?php echo $votes_cat; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-xl bg-purple-500/10 border border-purple-500/30">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-crown text-purple-400 text-lg"></i>
                                    <div>
                                        <p class="text-light/60 text-xs">Votes finaux</p>
                                        <p class="text-purple-400 font-bold text-xl"><?php echo $votes_final; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-xl bg-accent/10 border border-accent/30">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-comment text-accent text-lg"></i>
                                    <div>
                                        <p class="text-light/60 text-xs">Commentaires</p>
                                        <p class="text-accent font-bold text-xl"><?php echo $commentaires; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <a href="candidat-statistiques.php" class="flex items-center justify-center mt-6 w-full px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10 gap-2">
                            <i class="fas fa-chart-line"></i> Voir détails
                        </a>
                    </div>
                </div>
                
                <!-- Colonne droite : Formulaire profil (modifiable) -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                        <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                            <i class="fas fa-edit text-accent"></i> Modifier mon profil
                        </h2>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_profil">
                            
                            <!-- Email (non modifiable) -->
                            <div>
                                <label class="block mb-3 text-light/80 text-sm font-medium">
                                    <i class="fas fa-envelope text-accent mr-2"></i>Email
                                </label>
                                <input type="email" disabled
                                    class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light/50 cursor-not-allowed font-medium"
                                    value="<?php echo htmlspecialchars($candidat['email']); ?>">
                                <p class="text-xs text-light/40 mt-2 ml-1">L'email ne peut pas être modifié.</p>
                            </div>
                            
                            <!-- Inscrit depuis -->
                            <div>
                                <label class="block mb-3 text-light/80 text-sm font-medium">
                                    <i class="fas fa-calendar text-accent mr-2"></i>Inscrit depuis
                                </label>
                                <input type="text" disabled
                                    class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light/50 cursor-not-allowed font-medium"
                                    value="<?php echo date('d/m/Y', strtotime($candidat['date_inscription'])); ?>">
                            </div>
                            
                            <div class="h-px bg-white/10 my-4"></div>
                            
                            <!-- Nom -->
                            <div>
                                <label class="block mb-3 text-light/80 text-sm font-medium">
                                    <i class="fas fa-id-card text-accent mr-2"></i>Nom *
                                </label>
                                <input type="text" name="nom" required minlength="2" maxlength="100"
                                    class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all font-medium placeholder:text-light/40"
                                    value="<?php echo htmlspecialchars($candidat['nom'] ?? ''); ?>"
                                    placeholder="Votre nom">
                            </div>
                            
                            <!-- Bio -->
                            <div>
                                <label class="block mb-3 text-light/80 text-sm font-medium">
                                    <i class="fas fa-align-left text-accent mr-2"></i>Biographie
                                </label>
                                <textarea name="bio" maxlength="500" rows="5"
                                    class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all resize-none font-medium placeholder:text-light/40"
                                    placeholder="Parlez de vous..."><?php echo htmlspecialchars($candidat['bio'] ?? ''); ?></textarea>
                                <div class="flex justify-between mt-2">
                                    <p class="text-xs text-light/40 ml-1">Maximum 500 caractères</p>
                                    <p class="text-xs text-light/40">
                                        <span id="bio-count"><?php echo strlen($candidat['bio'] ?? ''); ?></span>/500
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Photo -->
                            <div>
                                <label class="block mb-3 text-light/80 text-sm font-medium">
                                    <i class="fas fa-image text-accent mr-2"></i>Photo (URL)
                                </label>
                                <input type="url" name="photo" maxlength="500"
                                    class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none transition-all font-medium placeholder:text-light/40"
                                    value="<?php echo htmlspecialchars($candidat['photo'] ?? ''); ?>"
                                    placeholder="https://...">
                                
                                <?php if (!empty($candidat['photo'])): ?>
                                    <div class="mt-4 p-4 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-4">
                                        <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" 
                                             alt="Photo actuelle" 
                                             class="w-20 h-20 rounded-2xl object-cover border-2 border-white/10">
                                        <div>
                                            <p class="text-sm font-medium text-light mb-1">Photo actuelle</p>
                                            <p class="text-xs text-light/60">URL: <?php echo substr(htmlspecialchars($candidat['photo']), 0, 50); ?>...</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="w-full px-8 py-4 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border-2 border-white/10 flex items-center justify-center gap-3 text-lg">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                    
                    <!-- Liens rapides -->
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <a href="candidat-campagne.php" class="glass-card rounded-2xl p-6 modern-border border-2 border-white/10 text-center hover:border-accent/50 transition-all group">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-accent/10 mb-4 group-hover:bg-accent/20 transition-colors">
                                <i class="fas fa-bullhorn text-2xl text-accent"></i>
                            </div>
                            <p class="text-light font-bold text-lg">Campagne</p>
                            <p class="text-light/60 text-sm mt-1">Gérez votre campagne</p>
                        </a>
                        <a href="candidat-statistiques.php" class="glass-card rounded-2xl p-6 modern-border border-2 border-white/10 text-center hover:border-purple-500/50 transition-all group">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-purple-500/10 mb-4 group-hover:bg-purple-500/20 transition-colors">
                                <i class="fas fa-chart-pie text-2xl text-purple-400"></i>
                            </div>
                            <p class="text-light font-bold text-lg">Statistiques</p>
                            <p class="text-light/60 text-sm mt-1">Analyses détaillées</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Compteur de caractères pour la bio
const bioTextarea = document.querySelector('textarea[name="bio"]');
const bioCount = document.getElementById('bio-count');

if (bioTextarea && bioCount) {
    bioTextarea.addEventListener('input', function() {
        bioCount.textContent = this.value.length;
        
        if (this.value.length > 500) {
            bioCount.classList.add('text-red-400');
        } else {
            bioCount.classList.remove('text-red-400');
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>