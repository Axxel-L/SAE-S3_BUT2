<?php
// ========================================
// GESTION DES CANDIDATURES - ADMIN
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// ✅ SÉCURITÉ : Vérifier que l'utilisateur est admin
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo "<script>
        alert('Accès réservé aux administrateurs');
        window.location.href = 'index.php';
    </script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
$candidats = [];
$filter = $_GET['filter'] ?? 'all'; // all, pending, approved, rejected

// ========================================
// 1️⃣ RÉCUPÉRER LES CANDIDATS
// ========================================
try {
    $query = "
        SELECT 
            c.id_candidat,
            c.id_utilisateur,
            u.email,
            c.nom,
            c.bio,
            c.photo,
            c.status,
            c.motivation,
            j.titre as jeu_titre,
            c.date_inscription,
            COUNT(DISTINCT ec.id_evenement) as events_joined
        FROM candidat c
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
    ";
    
    if ($filter !== 'all') {
        $query .= " WHERE c.status = ?";
    }
    
    $query .= " GROUP BY c.id_candidat ORDER BY c.date_inscription DESC";
    
    $stmt = $connexion->prepare($query);
    if ($filter !== 'all') {
        $stmt->execute([$filter]);
    } else {
        $stmt->execute();
    }
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des candidatures: " . $e->getMessage();
}

// ========================================
// 2️⃣ VALIDER/REJETER UNE CANDIDATURE
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Valider candidature
    if ($_POST['action'] === 'approve') {
        $candidat_id = intval($_POST['candidat_id'] ?? 0);
        
        if ($candidat_id > 0) {
            try {
                $stmt = $connexion->prepare("UPDATE candidat SET status = 'approved' WHERE id_candidat = ?");
                if ($stmt->execute([$candidat_id])) {
                    $success = "Candidature validée avec succès ! ✅";
                    
                    // Log audit
                    $log_stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, 'ADMIN_CANDIDAT_APPROVE', ?)
                    ");
                    $log_stmt->execute([$id_utilisateur, "Candidat $candidat_id approuvé"]);
                    
                    // Rafraîchir
                    $query = "
                        SELECT 
                            c.id_candidat,
                            c.id_utilisateur,
                            u.email,
                            c.nom,
                            c.bio,
                            c.photo,
                            c.status,
                            c.motivation,
                            j.titre as jeu_titre,
                            c.date_inscription,
                            COUNT(DISTINCT ec.id_evenement) as events_joined
                        FROM candidat c
                        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                    ";
                    
                    if ($filter !== 'all') {
                        $query .= " WHERE c.status = ?";
                    }
                    
                    $query .= " GROUP BY c.id_candidat ORDER BY c.date_inscription DESC";
                    
                    $stmt = $connexion->prepare($query);
                    if ($filter !== 'all') {
                        $stmt->execute([$filter]);
                    } else {
                        $stmt->execute();
                    }
                    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
    
    // Rejeter candidature
    elseif ($_POST['action'] === 'reject') {
        $candidat_id = intval($_POST['candidat_id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? '');
        
        if ($candidat_id > 0) {
            try {
                $stmt = $connexion->prepare("
                    UPDATE candidat 
                    SET status = 'rejected', bio = CONCAT(bio, '\n\n[REJETÉ] ', ?) 
                    WHERE id_candidat = ?
                ");
                if ($stmt->execute([$reason ?: "Candidature rejetée par l'administrateur", $candidat_id])) {
                    $success = "Candidature rejetée avec succès ! ✅";
                    
                    // Log audit
                    $log_stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, 'ADMIN_CANDIDAT_REJECT', ?)
                    ");
                    $log_stmt->execute([$id_utilisateur, "Candidat $candidat_id rejeté: $reason"]);
                    
                    // Rafraîchir
                    $query = "
                        SELECT 
                            c.id_candidat,
                            c.id_utilisateur,
                            u.email,
                            c.nom,
                            c.bio,
                            c.photo,
                            c.status,
                            c.motivation,
                            j.titre as jeu_titre,
                            c.date_inscription,
                            COUNT(DISTINCT ec.id_evenement) as events_joined
                        FROM candidat c
                        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                    ";
                    
                    if ($filter !== 'all') {
                        $query .= " WHERE c.status = ?";
                    }
                    
                    $query .= " GROUP BY c.id_candidat ORDER BY c.date_inscription DESC";
                    
                    $stmt = $connexion->prepare($query);
                    if ($filter !== 'all') {
                        $stmt->execute([$filter]);
                    } else {
                        $stmt->execute();
                    }
                    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
    
    // Supprimer candidature
    elseif ($_POST['action'] === 'delete') {
        $candidat_id = intval($_POST['candidat_id'] ?? 0);
        
        if ($candidat_id > 0) {
            try {
                $connexion->prepare("DELETE FROM event_candidat WHERE id_candidat = ?")->execute([$candidat_id]);
                $stmt = $connexion->prepare("DELETE FROM candidat WHERE id_candidat = ?");
                if ($stmt->execute([$candidat_id])) {
                    $success = "Candidature supprimée avec succès ! ✅";
                    
                    // Log audit
                    $log_stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, 'ADMIN_CANDIDAT_DELETE', ?)
                    ");
                    $log_stmt->execute([$id_utilisateur, "Candidat $candidat_id supprimé"]);
                    
                    // Rafraîchir
                    $query = "
                        SELECT 
                            c.id_candidat,
                            c.id_utilisateur,
                            u.email,
                            c.nom,
                            c.bio,
                            c.photo,
                            c.status,
                            c.motivation,
                            j.titre as jeu_titre,
                            c.date_inscription,
                            COUNT(DISTINCT ec.id_evenement) as events_joined
                        FROM candidat c
                        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                    ";
                    
                    if ($filter !== 'all') {
                        $query .= " WHERE c.status = ?";
                    }
                    
                    $query .= " GROUP BY c.id_candidat ORDER BY c.date_inscription DESC";
                    
                    $stmt = $connexion->prepare($query);
                    if ($filter !== 'all') {
                        $stmt->execute([$filter]);
                    } else {
                        $stmt->execute();
                    }
                    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

require_once 'header.php';
?>

<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <!-- En-tête -->
        <div class="mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                <i class="fas fa-star text-accent mr-3"></i>Gestion des Candidatures
            </h1>
            <p class="text-xl text-light-80">Validez ou rejetez les candidatures des participants</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-50010 border border-red-50030 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-50010 border border-green-50030 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="flex gap-2 mb-8 flex-wrap">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn--primary' : 'btn--secondary'; ?>">
                <i class="fas fa-list mr-2"></i> Tous (<?php 
                    $all_count = $connexion->query("SELECT COUNT(*) FROM candidat")->fetch()[0];
                    echo $all_count;
                ?>)
            </a>
            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn--primary' : 'btn--secondary'; ?>">
                <i class="fas fa-hourglass-start mr-2"></i> En attente (<?php 
                    $pending_count = $connexion->query("SELECT COUNT(*) FROM candidat WHERE status = 'pending'")->fetch()[0];
                    echo $pending_count;
                ?>)
            </a>
            <a href="?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn--primary' : 'btn--secondary'; ?>">
                <i class="fas fa-check-circle text-green-400 mr-2"></i> Approuvés (<?php 
                    $approved_count = $connexion->query("SELECT COUNT(*) FROM candidat WHERE status = 'approved'")->fetch()[0];
                    echo $approved_count;
                ?>)
            </a>
            <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn--primary' : 'btn--secondary'; ?>">
                <i class="fas fa-times-circle text-red-400 mr-2"></i> Rejetés (<?php 
                    $rejected_count = $connexion->query("SELECT COUNT(*) FROM candidat WHERE status = 'rejected'")->fetch()[0];
                    echo $rejected_count;
                ?>)
            </a>
        </div>

        <!-- Liste candidats -->
        <?php if (empty($candidats)): ?>
            <div class="glass-card rounded-4xl p-12 modern-border text-center">
                <i class="fas fa-inbox text-6xl text-light-80 mb-4"></i>
                <p class="text-light-80 text-lg">Aucune candidature trouvée.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($candidats as $candidat): ?>
                    <?php 
                    $status_badge = $candidat['status'] === 'approved' ? 'bg-green-50020 text-green-400 border-green-50030'
                                  : ($candidat['status'] === 'rejected' ? 'bg-red-50020 text-red-400 border-red-50030'
                                  : 'bg-yellow-50020 text-yellow-400 border-yellow-50030');
                    $status_icon = $candidat['status'] === 'approved' ? 'fa-check-circle'
                                 : ($candidat['status'] === 'rejected' ? 'fa-times-circle'
                                 : 'fa-hourglass-start');
                    ?>
                    <div class="glass-card rounded-3xl p-6 modern-border">
                        <div class="flex items-start justify-between mb-4 pb-4 border-b border-white10">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-light"><?php echo htmlspecialchars($candidat['nom']); ?></h3>
                                    <span class="status <?php echo $status_badge; ?> border">
                                        <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                                        <?php echo ucfirst($candidat['status']); ?>
                                    </span>
                                </div>
                                <p class="text-light-80">
                                    <i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($candidat['email']); ?>
                                </p>
                                <?php if ($candidat['jeu_titre']): ?>
                                    <p class="text-light-80">
                                        <i class="fas fa-gamepad mr-1"></i> Jeu: <?php echo htmlspecialchars($candidat['jeu_titre']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="text-sm text-light-80 mt-2">
                                    <i class="fas fa-calendar mr-1"></i> Inscrit le <?php echo date('d/m/Y', strtotime($candidat['date_inscription'])); ?>
                                    <i class="fas fa-check ml-3 mr-1"></i> <?php echo $candidat['events_joined']; ?> événement(s)
                                </p>
                            </div>
                            <?php if ($candidat['photo']): ?>
                                <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" alt="Photo" class="w-20 h-20 rounded-lg object-cover">
                            <?php endif; ?>
                        </div>

                        <!-- Bio -->
                        <?php if ($candidat['bio']): ?>
                            <div class="mb-4 pb-4 border-b border-white10">
                                <p class="text-sm font-bold text-accent mb-2">Biographie:</p>
                                <p class="text-light-80 text-sm"><?php echo nl2br(htmlspecialchars($candidat['bio'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Motivation -->
                        <?php if ($candidat['motivation']): ?>
                            <div class="mb-4 pb-4 border-b border-white10">
                                <p class="text-sm font-bold text-accent mb-2">Motivation:</p>
                                <p class="text-light-80 text-sm"><?php echo nl2br(htmlspecialchars($candidat['motivation'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="flex gap-3 flex-wrap">
                            <?php if ($candidat['status'] === 'pending'): ?>
                                <form method="POST" class="flex-1" style="display: inline; min-width: 150px;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                    <button type="submit" class="w-full btn btn--primary btn--sm" onclick="return confirm('Valider cette candidature ?');">
                                        <i class="fas fa-check mr-1"></i> Valider
                                    </button>
                                </form>

                                <button type="button" class="flex-1 btn btn--secondary btn--sm" onclick="document.getElementById('reject-form-<?php echo $candidat['id_candidat']; ?>').style.display = 'block'; this.style.display = 'none';" style="min-width: 150px;">
                                    <i class="fas fa-times mr-1"></i> Rejeter
                                </button>

                                <form method="POST" id="reject-form-<?php echo $candidat['id_candidat']; ?>" style="display: none; width: 100%;" class="mt-3">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                    <textarea name="reject_reason" class="form-control mb-2" placeholder="Motif du rejet..." rows="2"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 btn btn--primary btn--sm" onclick="return confirm('Confirmer le rejet ?');">
                                            <i class="fas fa-send mr-1"></i> Rejeter
                                        </button>
                                        <button type="button" class="flex-1 btn btn--secondary btn--sm" onclick="document.getElementById('reject-form-<?php echo $candidat['id_candidat']; ?>').style.display = 'none';">
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="candidat_id" value="<?php echo $candidat['id_candidat']; ?>">
                                <button type="submit" class="btn btn--sm bg-red-50020 text-red-400 border border-red-50030 hover:bg-red-50030" onclick="return confirm('Supprimer cette candidature ? Cette action est irréversible !');"><i class="fas fa-trash mr-1"></i> Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'footer.php'; ?>