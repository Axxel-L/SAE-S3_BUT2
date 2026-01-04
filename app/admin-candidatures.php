<?php
if (session_status() == PHP_SESSION_NONE) {
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

// Filtres
$filter_event = intval($_GET['event'] ?? 0);
$filter_status = $_GET['status'] ?? 'en_attente';

// Approuver une candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approuver') {
    $id_candidature = intval($_POST['id_candidature'] ?? 0);
    try {
        $connexion->beginTransaction();
        // Récupérer les infos de la candidature
        $stmt = $connexion->prepare("
            SELECT ec.*, c.id_jeu, j.titre as jeu_titre
            FROM event_candidat ec
            JOIN candidat c ON ec.id_candidat = c.id_candidat
            LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
            WHERE ec.id_event_candidat = ?
        ");
        $stmt->execute([$id_candidature]);
        $candidature = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidature) {
            throw new Exception("Candidature non trouvée.");
        }
        // Mettre à jour le statut
        $stmt = $connexion->prepare("
            UPDATE event_candidat 
            SET statut_candidature = 'approuve', 
                date_validation = NOW(), 
                valide_par = ?,
                motif_refus = NULL
            WHERE id_event_candidat = ?
        ");
        $stmt->execute([$id_admin, $id_candidature]);
        // Ajouter automatiquement le jeu dans les nominations si pas déjà nominé
        $stmt = $connexion->prepare("
            SELECT id_nomination FROM nomination 
            WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?
        ");
        $stmt->execute([
            $candidature['id_jeu'], 
            $candidature['id_categorie'], 
            $candidature['id_evenement']
        ]);
        
        if ($stmt->rowCount() === 0) {
            $stmt = $connexion->prepare("
                INSERT INTO nomination (id_jeu, id_categorie, id_evenement)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $candidature['id_jeu'],
                $candidature['id_categorie'],
                $candidature['id_evenement']
            ]);
        }
        // Ajout dans les logs
        $stmt = $connexion->prepare("
            INSERT INTO journal_securite (id_utilisateur, action, details)
            VALUES (?, 'ADMIN_CANDIDATURE_APPROUVE', ?)
        ");
        $stmt->execute([
            $id_admin,
            "Candidature #$id_candidature approuvée - Jeu: " . $candidature['jeu_titre']
        ]);
        $connexion->commit();
        $success = "Candidature approuvée ! Le jeu a été ajouté aux nominations.";
    } catch (Exception $e) {
        $connexion->rollBack();
        $error = $e->getMessage();
    }
}

// Refuser une candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refuser') {
    $id_candidature = intval($_POST['id_candidature'] ?? 0);
    $motif = trim($_POST['motif'] ?? '');
    try {
        // Mettre à jour le statut
        $stmt = $connexion->prepare("
            UPDATE event_candidat 
            SET statut_candidature = 'refuse', 
                date_validation = NOW(), 
                valide_par = ?,
                motif_refus = ?
            WHERE id_event_candidat = ?
        ");
        $stmt->execute([$id_admin, $motif ?: 'Non spécifié', $id_candidature]);
        // Ajout dans les logs        
        $stmt = $connexion->prepare("
            INSERT INTO journal_securite (id_utilisateur, action, details)
            VALUES (?, 'ADMIN_CANDIDATURE_REFUSE', ?)
        ");
        $stmt->execute([$id_admin, "Candidature #$id_candidature refusée - Motif: $motif"]);
        $success = "Candidature refusée.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer les événements pour le filtre
$events = [];
try {
    $stmt = $connexion->query("SELECT id_evenement, nom, statut FROM evenement ORDER BY date_ouverture DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Récupérer les candidatures
$candidatures = [];
try {
    $query = "
        SELECT 
            ec.id_event_candidat,
            ec.id_evenement,
            ec.id_categorie,
            ec.statut_candidature,
            ec.date_inscription,
            ec.date_validation,
            ec.motif_refus,
            e.nom as evenement_nom,
            e.statut as evenement_statut,
            cat.nom as categorie_nom,
            c.id_candidat,
            c.nom as candidat_nom,
            c.id_jeu,
            j.titre as jeu_titre,
            j.image as jeu_image,
            j.editeur as jeu_editeur,
            u.email as candidat_email,
            admin.email as valide_par_email
        FROM event_candidat ec
        JOIN evenement e ON ec.id_evenement = e.id_evenement
        LEFT JOIN categorie cat ON ec.id_categorie = cat.id_categorie
        JOIN candidat c ON ec.id_candidat = c.id_candidat
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        LEFT JOIN utilisateur admin ON ec.valide_par = admin.id_utilisateur
        WHERE 1=1
    ";
    $params = [];
    if ($filter_event > 0) {
        $query .= " AND ec.id_evenement = ?";
        $params[] = $filter_event;
    }
    if (!empty($filter_status)) {
        $query .= " AND ec.statut_candidature = ?";
        $params[] = $filter_status;
    }
    $query .= " ORDER BY ec.date_inscription DESC";
    $stmt = $connexion->prepare($query);
    $stmt->execute($params);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Compter les candidatures en attente
$nbEnAttente = 0;
try {
    $stmt = $connexion->query("SELECT COUNT(*) FROM event_candidat WHERE statut_candidature = 'en_attente'");
    $nbEnAttente = $stmt->fetchColumn();
} catch (Exception $e) {
    // Ignorer
}
require_once 'header.php';
?>
<br><br><br> <!-- Espace pour le header -->
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-clipboard-check text-accent mr-3"></i>Candidatures
                </h1>
                <p class="text-xl text-light-80">Validez ou refusez les candidatures des jeux</p>
            </div>
            <?php if ($nbEnAttente > 0): ?>
            <div class="px-4 py-2 rounded-2xl bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">
                <i class="fas fa-bell mr-2"></i>
                <span class="font-bold"><?php echo $nbEnAttente; ?></span> candidature(s) en attente
            </div>
            <?php endif; ?>
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
        
        <!-- Carte des filtres -->
        <div class="glass-card rounded-3xl p-6 modern-border border-2 border-white/10 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <!-- Événement -->
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-light-80 mb-2">Événement</label>
                    <div class="relative">
                        <select name="event" class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                            <option value="0" class="text-black bg-white">Tous les événements</option>
                            <?php foreach ($events as $evt): ?>
                                <option value="<?php echo $evt['id_evenement']; ?>" <?php echo $filter_event === $evt['id_evenement'] ? 'selected' : ''; ?> class="text-black bg-white">
                                    <?php echo htmlspecialchars($evt['nom']); ?> (<?php echo $evt['statut']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <i class="fas fa-chevron-down text-light-80"></i>
                        </div>
                    </div>
                </div>
                <!-- Statut -->
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-light-80 mb-2">Statut</label>
                    <div class="relative">
                        <select name="status" class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                            <option value="" class="text-black bg-white">Tous</option>
                            <option value="en_attente" <?php echo $filter_status === 'en_attente' ? 'selected' : ''; ?> class="text-black bg-white">⏳ En attente</option>
                            <option value="approuve" <?php echo $filter_status === 'approuve' ? 'selected' : ''; ?> class="text-black bg-white">✅ Approuvées</option>
                            <option value="refuse" <?php echo $filter_status === 'refuse' ? 'selected' : ''; ?> class="text-black bg-white">❌ Refusées</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <i class="fas fa-chevron-down text-light-80"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" class="px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
            </form>
        </div>

        <!-- Liste des candidatures -->
        <?php if (empty($candidatures)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center">
                <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                <p class="text-xl text-light-80">Aucune candidature trouvée.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($candidatures as $cand): ?>
                    <?php 
                    $statusClass = match($cand['statut_candidature']) {
                        'approuve' => 'border-green-500/30 bg-green-500/5',
                        'refuse' => 'border-red-500/30 bg-red-500/5',
                        default => 'border-yellow-500/30 bg-yellow-500/5'
                    };
                    ?>
                    <div class="glass-card rounded-2xl p-6 modern-border border border-white/10 <?php echo $statusClass; ?>">
                        <div class="flex flex-wrap gap-6">
                            <!-- Image du jeu -->
                            <div class="flex-shrink-0">
                                <?php if ($cand['jeu_image']): ?>
                                    <img src="<?php echo htmlspecialchars($cand['jeu_image']); ?>" alt="" class="w-24 h-24 rounded-2xl object-cover">
                                <?php else: ?>
                                    <div class="w-24 h-24 rounded-2xl bg-white/10 flex items-center justify-center">
                                        <i class="fas fa-gamepad text-3xl text-light-80"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Infos -->
                            <div class="flex-1 min-w-64">
                                <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-light"><?php echo htmlspecialchars($cand['jeu_titre'] ?? 'Jeu inconnu'); ?></h3>
                                        <?php if ($cand['jeu_editeur']): ?>
                                            <p class="text-sm text-light-80"><?php echo htmlspecialchars($cand['jeu_editeur']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Badge statut -->
                                    <?php 
                                    $badgeClass = match($cand['statut_candidature']) {
                                        'approuve' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                        'refuse' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                        default => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
                                    };
                                    $badgeIcon = match($cand['statut_candidature']) {
                                        'approuve' => 'fa-check-circle',
                                        'refuse' => 'fa-times-circle',
                                        default => 'fa-hourglass-half'
                                    };
                                    $badgeText = match($cand['statut_candidature']) {
                                        'approuve' => 'Approuvée',
                                        'refuse' => 'Refusée',
                                        default => 'En attente'
                                    };
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium border <?php echo $badgeClass; ?>">
                                        <i class="fas <?php echo $badgeIcon; ?> mr-1"></i><?php echo $badgeText; ?>
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <p class="text-light-80">Événement</p>
                                        <p class="font-medium text-light"><?php echo htmlspecialchars($cand['evenement_nom']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-light-80">Catégorie</p>
                                        <p class="font-medium text-accent"><?php echo htmlspecialchars($cand['categorie_nom'] ?? 'Non définie'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-light-80">Candidat</p>
                                        <p class="font-medium text-light"><?php echo htmlspecialchars($cand['candidat_nom']); ?></p>
                                        <p class="text-xs text-light-80"><?php echo htmlspecialchars($cand['candidat_email']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-xs text-light-80">
                                    <i class="fas fa-clock mr-1"></i>
                                    Soumis le <?php echo date('d/m/Y à H:i', strtotime($cand['date_inscription'])); ?>
                                    <?php if ($cand['date_validation']): ?>
                                        | Traité le <?php echo date('d/m/Y à H:i', strtotime($cand['date_validation'])); ?>
                                        par <?php echo htmlspecialchars($cand['valide_par_email'] ?? 'Admin'); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($cand['statut_candidature'] === 'refuse' && $cand['motif_refus']): ?>
                                    <div class="mt-3 p-3 rounded-2xl bg-red-500/10 border border-red-500/20">
                                        <p class="text-sm text-red-400">
                                            <i class="fas fa-comment-slash mr-1"></i>
                                            <strong>Motif:</strong> <?php echo htmlspecialchars($cand['motif_refus']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <?php if ($cand['statut_candidature'] === 'en_attente'): ?>
                            <div class="flex-shrink-0 flex flex-col gap-2">
                                <!-- Approuver -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="approuver">
                                    <input type="hidden" name="id_candidature" value="<?php echo $cand['id_event_candidat']; ?>">
                                    <button type="submit" class="w-full px-4 py-2 rounded-2xl bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30 transition-colors" onclick="return confirm('Approuver cette candidature ?');">
                                        <i class="fas fa-check mr-2"></i>Approuver
                                    </button>
                                </form>
                                
                                <!-- Refuser -->
                                <button type="button" onclick="openRefusModal(<?php echo $cand['id_event_candidat']; ?>, '<?php echo htmlspecialchars($cand['jeu_titre'], ENT_QUOTES); ?>')" class="w-full px-4 py-2 rounded-2xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors">
                                    <i class="fas fa-times mr-2"></i>Refuser
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 text-center text-light-80">
                <?php echo count($candidatures); ?> candidature(s) affichée(s)
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal refus -->
<div id="refusModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 hidden">
    <div class="glass-card rounded-3xl p-8 max-w-md w-full mx-4 border-2 border-white/10">
        <h3 class="text-2xl font-bold font-orbitron text-light mb-4 flex items-center gap-2">
            <i class="fas fa-times-circle text-red-400"></i>Refuser la candidature
        </h3>
        <p class="text-light-80 mb-4">Jeu: <span id="refusJeuTitre" class="font-bold text-accent"></span></p>
        
        <form method="POST" id="refusForm">
            <input type="hidden" name="action" value="refuser">
            <input type="hidden" name="id_candidature" id="refusIdCandidature">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-light-80 mb-2">Motif du refus (optionnel)</label>
                <textarea name="motif" rows="3" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light" placeholder="Ex: Jeu non éligible dans cette catégorie..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeRefusModal()" class="flex-1 px-4 py-3 rounded-2xl bg-white/10 text-light hover:bg-white/20 transition-colors border border-white/10">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-3 rounded-2xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors">
                    <i class="fas fa-times mr-2"></i>Refuser
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRefusModal(id, titre) {
    document.getElementById('refusIdCandidature').value = id;
    document.getElementById('refusJeuTitre').textContent = titre;
    document.getElementById('refusModal').classList.remove('hidden');
}

function closeRefusModal() {
    document.getElementById('refusModal').classList.add('hidden');
}

// Fermer le modal en cliquant en dehors
document.getElementById('refusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRefusModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>
</body>
</html>