<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifie si l'utilisateur est un admin
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo "<script>
        alert('Accès réservé aux administrateurs');
        window.location.href = './dashboard.php';
    </script>";
    exit;
}

$id_admin = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
$filter = $_GET['filter'] ?? 'all';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $candidat_id = intval($_POST['candidat_id'] ?? 0);
    
    if ($candidat_id > 0) {
        try {
            switch ($_POST['action']) {
                // Valider un candidat
                case 'valider':
                    $stmt = $connexion->prepare("UPDATE candidat SET statut = 'valide' WHERE id_candidat = ?");
                    $stmt->execute([$candidat_id]);
                    $success = "✅ Candidature validée avec succès !";
                    
                    // Ajoute aux logs
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'ADMIN_CANDIDAT_VALIDE', ?, ?)");
                    $stmt->execute([$id_admin, "Candidat ID: $candidat_id validé", $_SERVER['REMOTE_ADDR'] ?? '']);
                    break;
                
                // Refuser un candidat
                case 'refuser':
                    $motif = htmlspecialchars(trim($_POST['motif'] ?? 'Candidature refusée'), ENT_QUOTES, 'UTF-8');
                    $stmt = $connexion->prepare("UPDATE candidat SET statut = 'refuse' WHERE id_candidat = ?");
                    $stmt->execute([$candidat_id]);
                    $success = "❌ Candidature refusée.";
                    
                    // Ajoute aux logs
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'ADMIN_CANDIDAT_REFUSE', ?, ?)");
                    $stmt->execute([$id_admin, "Candidat ID: $candidat_id refusé - Motif: $motif", $_SERVER['REMOTE_ADDR'] ?? '']);
                    break;
                
                // Remettre en attente
                case 'attente':
                    $stmt = $connexion->prepare("UPDATE candidat SET statut = 'en_attente' WHERE id_candidat = ?");
                    $stmt->execute([$candidat_id]);
                    $success = "⏳ Candidature remise en attente.";
                    break;
                
                // Supprimer un candidat
                case 'supprimer':
                    // Récupérer l'id_utilisateur
                    $stmt = $connexion->prepare("SELECT id_utilisateur FROM candidat WHERE id_candidat = ?");
                    $stmt->execute([$candidat_id]);
                    $candidat_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($candidat_data) {
                        $connexion->beginTransaction();
                        
                        // Supprimer les candidatures aux événements
                        $stmt = $connexion->prepare("DELETE FROM event_candidat WHERE id_candidat = ?");
                        $stmt->execute([$candidat_id]);
                        
                        // Supprimer le profil candidat
                        $stmt = $connexion->prepare("DELETE FROM candidat WHERE id_candidat = ?");
                        $stmt->execute([$candidat_id]);
                        
                        // Changer le type de l'utilisateur en joueur
                        $stmt = $connexion->prepare("UPDATE utilisateur SET type = 'joueur' WHERE id_utilisateur = ?");
                        $stmt->execute([$candidat_data['id_utilisateur']]);
                        
                        $connexion->commit();
                        $success = "🗑️ Candidat supprimé (compte converti en joueur).";
                        
                        // Ajouter aux logs
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'ADMIN_CANDIDAT_DELETE', ?, ?)");
                        $stmt->execute([$id_admin, "Candidat ID: $candidat_id supprimé", $_SERVER['REMOTE_ADDR'] ?? '']);
                    }
                    break;
            }
        } catch (Exception $e) {
            if ($connexion->inTransaction()) {
                $connexion->rollBack();
            }
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Liste des candidats
$candidats = [];
$counts = ['all' => 0, 'en_attente' => 0, 'valide' => 0, 'refuse' => 0];

try {
    // Compter par statut
    $stmt = $connexion->query("SELECT statut, COUNT(*) as nb FROM candidat GROUP BY statut");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[$row['statut']] = $row['nb'];
        $counts['all'] += $row['nb'];
    }
    
    // Requête principale
    $query = "
        SELECT 
            c.id_candidat,
            c.id_utilisateur,
            c.nom,
            c.bio,
            c.photo,
            c.statut,
            c.date_inscription,
            u.email,
            j.id_jeu,
            j.titre as jeu_titre,
            j.image as jeu_image,
            j.editeur,
            (SELECT COUNT(*) FROM event_candidat ec WHERE ec.id_candidat = c.id_candidat) as nb_candidatures,
            (SELECT COUNT(*) FROM commentaire cm WHERE cm.id_jeu = c.id_jeu) as nb_commentaires
        FROM candidat c
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
    ";
    
    $params = [];
    if ($filter !== 'all') {
        $query .= " WHERE c.statut = ?";
        $params[] = $filter;
    }
    
    $query .= " ORDER BY 
        CASE c.statut 
            WHEN 'en_attente' THEN 1 
            WHEN 'valide' THEN 2 
            WHEN 'refuse' THEN 3 
        END,
        c.date_inscription DESC";
    
    $stmt = $connexion->prepare($query);
    $stmt->execute($params);
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement : " . $e->getMessage();
}

// Configuration des statuts
$statut_config = [
    'en_attente' => ['label' => 'En attente', 'color' => 'yellow', 'icon' => 'fa-clock', 'bg' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'],
    'valide' => ['label' => 'Validé', 'color' => 'green', 'icon' => 'fa-check-circle', 'bg' => 'bg-green-500/20 text-green-400 border-green-500/30'],
    'refuse' => ['label' => 'Refusé', 'color' => 'red', 'icon' => 'fa-times-circle', 'bg' => 'bg-red-500/20 text-red-400 border-red-500/30']
];

require_once 'header.php';
?>

<br><br><br> <!-- Espace pour le header fixe -->
<section class="py-20 px-6 min-h-screen">
    <div class="container mx-auto max-w-7xl">
        <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-user-check text-accent mr-3"></i>Gestion des Candidats
                </h1>
                <p class="text-xl text-light-80">Validez ou refusez les inscriptions des candidats</p>
            </div>
        </div>
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
        
        <!-- Filtres -->
        <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 mb-8">
            <div class="flex flex-wrap gap-3">
                <a href="?filter=all" 
                   class="px-4 py-3 rounded-2xl border border-white/10 transition-all flex items-center gap-2 <?php echo $filter === 'all' ? 'bg-accent text-dark border-accent font-bold' : 'bg-white/5 hover:border-accent/50'; ?>">
                    <i class="fas fa-list"></i>
                    <span>Tous</span>
                    <span class="px-2 py-1 rounded-full bg-white/20 text-xs"><?php echo $counts['all']; ?></span>
                </a>
                <a href="?filter=en_attente" 
                   class="px-4 py-3 rounded-2xl border border-white/10 transition-all flex items-center gap-2 <?php echo $filter === 'en_attente' ? 'bg-yellow-500 text-dark border-yellow-500 font-bold' : 'bg-white/5 hover:border-yellow-500/50'; ?>">
                    <i class="fas fa-clock"></i>
                    <span>En attente</span>
                    <span class="px-2 py-1 rounded-full <?php echo $filter === 'en_attente' ? 'bg-white/30' : 'bg-yellow-500/30 text-yellow-400'; ?> text-xs"><?php echo $counts['en_attente']; ?></span>
                </a>
                <a href="?filter=valide" 
                   class="px-4 py-3 rounded-2xl border border-white/10 transition-all flex items-center gap-2 <?php echo $filter === 'valide' ? 'bg-green-500 text-dark border-green-500 font-bold' : 'bg-white/5 hover:border-green-500/50'; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Validés</span>
                    <span class="px-2 py-1 rounded-full <?php echo $filter === 'valide' ? 'bg-white/30' : 'bg-green-500/30 text-green-400'; ?> text-xs"><?php echo $counts['valide']; ?></span>
                </a>
                <a href="?filter=refuse" 
                   class="px-4 py-3 rounded-2xl border border-white/10 transition-all flex items-center gap-2 <?php echo $filter === 'refuse' ? 'bg-red-500 text-dark border-red-500 font-bold' : 'bg-white/5 hover:border-red-500/50'; ?>">
                    <i class="fas fa-times-circle"></i>
                    <span>Refusés</span>
                    <span class="px-2 py-1 rounded-full <?php echo $filter === 'refuse' ? 'bg-white/30' : 'bg-red-500/30 text-red-400'; ?> text-xs"><?php echo $counts['refuse']; ?></span>
                </a>
            </div>
        </div>
        
        <!-- Liste des candidats -->
        <?php if (empty($candidats)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center">
                <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                <p class="text-xl text-light-80">Aucun candidat trouvé.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($candidats as $candidat): 
                    $statut = $statut_config[$candidat['statut']] ?? $statut_config['en_attente'];
                ?>
                    <div class="glass-card rounded-3xl p-6 modern-border border border-white/10 <?php echo $candidat['statut'] === 'en_attente' ? 'border-yellow-500/50' : ''; ?>">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6 pb-6 border-b border-white/10">
                            <div class="flex items-start gap-4">
                                <!-- Photo de profil -->
                                <?php if (!empty($candidat['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($candidat['nom']); ?>"
                                         class="w-16 h-16 rounded-2xl object-cover border border-white/10">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center border border-white/10">
                                        <i class="fas fa-user text-2xl text-light-80"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-xl font-bold text-light"><?php echo htmlspecialchars($candidat['nom']); ?></h3>
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-medium border <?php echo $statut['bg']; ?>">
                                            <i class="fas <?php echo $statut['icon']; ?> mr-1"></i>
                                            <?php echo $statut['label']; ?>
                                        </span>
                                    </div>
                                    <p class="text-light-80 text-sm flex items-center gap-1">
                                        <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($candidat['email']); ?>
                                    </p>
                                    <p class="text-light-80 text-xs mt-1 flex items-center gap-1">
                                        <i class="fas fa-calendar mr-1"></i>Inscrit le <?php echo date('d/m/Y à H:i', strtotime($candidat['date_inscription'])); ?>
                                    </p>
                                </div>
                            </div>
                            <!-- Statistiques -->
                            <div class="flex gap-4 text-center">
                                <div class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                                    <div class="text-lg font-bold text-accent"><?php echo $candidat['nb_candidatures']; ?></div>
                                    <div class="text-xs text-light-80">Événements</div>
                                </div>
                                <div class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                                    <div class="text-lg font-bold text-purple-400"><?php echo $candidat['nb_commentaires']; ?></div>
                                    <div class="text-xs text-light-80">Commentaires</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Infos jeu -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Jeu représenté -->
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                                <h4 class="text-sm font-bold text-accent mb-3 flex items-center gap-2">
                                    <i class="fas fa-gamepad"></i>Jeu représenté
                                </h4>
                                <?php if (!empty($candidat['jeu_titre'])): ?>
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($candidat['jeu_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidat['jeu_titre']); ?>"
                                                 class="w-12 h-12 rounded-lg object-cover border border-white/10">
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-light font-medium"><?php echo htmlspecialchars($candidat['jeu_titre']); ?></p>
                                            <?php if (!empty($candidat['editeur'])): ?>
                                                <p class="text-light-80 text-xs"><?php echo htmlspecialchars($candidat['editeur']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-light-80 italic text-sm">Aucun jeu sélectionné</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Biographie -->
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                                <h4 class="text-sm font-bold text-accent mb-3 flex items-center gap-2">
                                    <i class="fas fa-align-left"></i>Biographie
                                </h4>
                                <?php if (!empty($candidat['bio'])): ?>
                                    <p class="text-light-80 text-sm line-clamp-3"><?php echo nl2br(htmlspecialchars($candidat['bio'])); ?></p>
                                <?php else: ?>
                                    <p class="text-light-80 italic text-sm">Aucune biographie</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-6 border-t border-white/10">
                            <?php if ($candidat['statut'] === 'en_attente'): ?>
                                <!-- Valider -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="valider">
                                    <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Valider cette candidature ?')"
                                            class="px-4 py-2 rounded-xl bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30 transition-colors flex items-center gap-2">
                                        <i class="fas fa-check"></i> Valider
                                    </button>
                                </form>
                                
                                <!-- Refuser -->
                                <button type="button" 
                                        onclick="document.getElementById('refus-<?php echo $candidat['id_candidat']; ?>').classList.toggle('hidden')"
                                        class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors flex items-center gap-2">
                                    <i class="fas fa-times"></i> Refuser
                                </button>
                                
                                <!-- Formulaire refus -->
                                <div id="refus-<?php echo $candidat['id_candidat']; ?>" class="hidden w-full mt-3">
                                    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="refuser">
                                            <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                            <label class="block text-sm text-light-80 mb-2">Motif du refus (optionnel) :</label>
                                            <textarea name="motif" rows="2" 
                                                      class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-light mb-3 resize-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300"
                                                      placeholder="Ex: Jeu non éligible, informations incomplètes..."></textarea>
                                            <div class="flex gap-3">
                                                <button type="submit" 
                                                        onclick="return confirm('Confirmer le refus ?')"
                                                        class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors">
                                                    <i class="fas fa-check mr-1"></i> Confirmer le refus
                                                </button>
                                                <button type="button" 
                                                        onclick="document.getElementById('refus-<?php echo $candidat['id_candidat']; ?>').classList.add('hidden')"
                                                        class="px-4 py-2 rounded-xl bg-white/10 text-light hover:bg-white/20 transition-colors border border-white/10">
                                                    Annuler
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                            <?php elseif ($candidat['statut'] === 'valide'): ?>
                                <!-- Remettre en attente -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="attente">
                                    <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Remettre en attente ?')"
                                            class="px-4 py-2 rounded-xl bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/30 transition-colors flex items-center gap-2">
                                        <i class="fas fa-clock"></i> Remettre en attente
                                    </button>
                                </form>
                                
                            <?php elseif ($candidat['statut'] === 'refuse'): ?>
                                <!-- Revalider -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="valider">
                                    <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Valider cette candidature ?')"
                                            class="px-4 py-2 rounded-xl bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30 transition-colors flex items-center gap-2">
                                        <i class="fas fa-undo"></i> Revalider
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <!-- Supprimer -->
                            <form method="POST" class="inline ml-auto">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                <button type="submit" 
                                        onclick="return confirm('⚠️ ATTENTION : Supprimer définitivement ce candidat ?\n\nLe compte sera converti en joueur.')"
                                        class="px-4 py-2 rounded-xl bg-white/5 text-red-400 border border-white/10 hover:bg-red-500/10 hover:border-red-500/30 transition-colors flex items-center gap-2">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-8 glass-card rounded-3xl p-8 modern-border border-2 border-white/10 bg-gradient-to-br from-accent/5 to-transparent">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="glass-effect p-3 rounded-2xl border border-accent/20 bg-accent/10">
                        <i class="fas fa-info-circle text-2xl text-accent"></i>
                    </div>
                </div>
                <div class="text-light-80">
                    <p class="font-bold text-accent text-xl mb-4 flex items-center gap-2">
                        <span>À propos de la validation</span>
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="px-2 py-1 rounded-lg bg-yellow-500/20 text-yellow-400 text-xs font-bold mt-0.5">EN ATTENTE</span>
                            <span class="text-light-80">Le candidat ne peut pas se connecter tant que sa candidature n'est pas validée</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="px-2 py-1 rounded-lg bg-green-500/20 text-green-400 text-xs font-bold mt-0.5">VALIDÉ</span>
                            <span class="text-light-80">Le candidat peut se connecter et gérer sa campagne</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="px-2 py-1 rounded-lg bg-red-500/20 text-red-400 text-xs font-bold mt-0.5">REFUSÉ</span>
                            <span class="text-light-80">Le candidat ne peut pas se connecter</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="px-2 py-1 rounded-lg bg-white/10 text-light text-xs font-bold mt-0.5">SUPPRIMER</span>
                            <span class="text-light-80">Le compte est converti en compte joueur standard</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>