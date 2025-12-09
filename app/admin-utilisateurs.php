<?php
// ========================================
// GESTION DES UTILISATEURS - ADMIN
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
$users = [];
$action = $_GET['action'] ?? '';

// ========================================
// 1️⃣ RÉCUPÉRER TOUS LES UTILISATEURS
// ========================================
try {
    $stmt = $connexion->prepare("
        SELECT 
            u.id_utilisateur,
            u.email,
            u.type as type,
            u.date_inscription,
            u.is_active,
            u.last_login,
            COUNT(DISTINCT c.id_candidat) as candidatures,
            COUNT(DISTINCT ec.id_evenement) as events_joined
        FROM utilisateur u
        LEFT JOIN candidat c ON u.id_utilisateur = c.id_utilisateur
        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
        GROUP BY u.id_utilisateur
        ORDER BY u.date_inscription DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des utilisateurs: " . $e->getMessage();
}

// ========================================
// 2️⃣ ACTIVER/DÉSACTIVER UN UTILISATEUR
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_active') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_status = intval($_POST['is_active'] ?? 0);
        
        if ($user_id > 0 && $user_id !== $id_utilisateur) { // Impossible de se désactiver soi-même
            try {
                $stmt = $connexion->prepare("UPDATE utilisateur SET is_active = ? WHERE id_utilisateur = ?");
                if ($stmt->execute([$new_status, $user_id])) {
                    $success = "Statut de l'utilisateur mis à jour avec succès ! ✅";
                    
                    // Log audit
                    $log_stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, 'ADMIN_USER_STATUS_CHANGE', ?)
                    ");
                    $log_stmt->execute([$id_utilisateur, "Utilisateur $user_id: is_active = $new_status"]);
                    
                    // Rafraîchir la liste
                    $stmt = $connexion->prepare("
                        SELECT 
                            u.id_utilisateur,
                            u.email,
                            u.type as type,
                            u.date_inscription,
                            u.is_active,
                            u.last_login,
                            COUNT(DISTINCT c.id_candidat) as candidatures,
                            COUNT(DISTINCT ec.id_evenement) as events_joined
                        FROM utilisateur u
                        LEFT JOIN candidat c ON u.id_utilisateur = c.id_utilisateur
                        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                        GROUP BY u.id_utilisateur
                        ORDER BY u.date_inscription DESC
                    ");
                    $stmt->execute();
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Impossible de modifier ce compte !";
        }
    }
    
    // ========================================
    // 3️⃣ AJOUTER UN NOUVEL UTILISATEUR
    // ========================================
    elseif ($_POST['action'] === 'add_user') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $type = $_POST['type'] ?? 'joueur';
        
        if (!empty($email) && !empty($password) && in_array($type, ['joueur', 'candidat', 'admin'])) {
            try {
                // Vérifier email unique
                $check_stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                $check_stmt->execute([$email]);
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "Cet email existe déjà !";
                } else {
                    $hashed_pwd = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $connexion->prepare("
                        INSERT INTO utilisateur (email, mot_de_passe, type, date_inscription, is_active) 
                        VALUES (?, ?, ?, NOW(), 1)
                    ");
                    
                    if ($stmt->execute([$email, $hashed_pwd, $type])) {
                        $success = "Utilisateur créé avec succès ! ✅";
                        
                        // Log audit
                        $log_stmt = $connexion->prepare("
                            INSERT INTO journal_securite (id_utilisateur, action, details) 
                            VALUES (?, 'ADMIN_USER_CREATE', ?)
                        ");
                        $log_stmt->execute([$id_utilisateur, "Utilisateur créé: $email ($type)"]);
                        
                        // Rafraîchir
                        $stmt = $connexion->prepare("
                            SELECT 
                                u.id_utilisateur,
                                u.email,
                                u.type as type,
                                u.date_inscription,
                                u.is_active,
                                u.last_login,
                                COUNT(DISTINCT c.id_candidat) as candidatures,
                                COUNT(DISTINCT ec.id_evenement) as events_joined
                            FROM utilisateur u
                            LEFT JOIN candidat c ON u.id_utilisateur = c.id_utilisateur
                            LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                            GROUP BY u.id_utilisateur
                            ORDER BY u.date_inscription DESC
                        ");
                        $stmt->execute();
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez remplir tous les champs correctement !";
        }
    }
    
    // ========================================
    // 4️⃣ SUPPRIMER UN UTILISATEUR
    // ========================================
    elseif ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0 && $user_id !== $id_utilisateur) {
            try {
                // Supprimer les votes de cet utilisateur (anonyme)
                $connexion->prepare("DELETE FROM emargement_categorie WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM emargement_final WHERE id_utilisateur = ?")->execute([$user_id]);
                
                // Supprimer les candidatures
                $connexion->prepare("DELETE FROM candidat WHERE id_utilisateur = ?")->execute([$user_id]);
                
                // Supprimer l'utilisateur
                $stmt = $connexion->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
                if ($stmt->execute([$user_id])) {
                    $success = "Utilisateur supprimé avec succès ! ✅";
                    
                    // Log audit
                    $log_stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, 'ADMIN_USER_DELETE', ?)
                    ");
                    $log_stmt->execute([$id_utilisateur, "Utilisateur $user_id supprimé"]);
                    
                    // Rafraîchir
                    $stmt = $connexion->prepare("
                        SELECT 
                            u.id_utilisateur,
                            u.email,
                            u.type as type,
                            u.date_inscription,
                            u.is_active,
                            u.last_login,
                            COUNT(DISTINCT c.id_candidat) as candidatures,
                            COUNT(DISTINCT ec.id_evenement) as events_joined
                        FROM utilisateur u
                        LEFT JOIN candidat c ON u.id_utilisateur = c.id_utilisateur
                        LEFT JOIN event_candidat ec ON c.id_candidat = ec.id_candidat
                        GROUP BY u.id_utilisateur
                        ORDER BY u.date_inscription DESC
                    ");
                    $stmt->execute();
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Impossible de supprimer ce compte !";
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
                <i class="fas fa-users text-accent mr-3"></i>Gestion des Utilisateurs
            </h1>
            <p class="text-xl text-light-80">Administrez les électeurs et gérez les accès</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne gauche : Ajouter utilisateur -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-user-plus text-accent"></i> Ajouter Utilisateur
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                        </div>
                        
                        <div>
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        
                        <div>
                            <label class="form-label">Rôle</label>
                            <select name="type" class="form-control" required>
                                <option value="joueur">Électeur</option>
                                <option value="candidat">Candidat</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full btn btn--primary btn--lg">
                            <i class="fas fa-plus mr-2"></i> Créer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Colonne droite : Liste utilisateurs -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-list text-accent"></i> Utilisateurs (<?php echo count($users); ?>)
                    </h2>
                    
                    <?php if (empty($users)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-light-80">Aucun utilisateur créé.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($users as $user): ?>
                                <?php 
                                $badge_color = $user['type'] === 'admin' ? 'bg-red-50020 text-red-400' 
                                             : ($user['type'] === 'candidat' ? 'bg-purple-50020 text-purple-400' 
                                             : 'bg-blue-50020 text-blue-400');
                                $status_color = $user['is_active'] ? 'bg-green-50020 text-green-400' : 'bg-gray-50020 text-gray-400';
                                ?>
                                <div class="glass-card rounded-2xl p-4 modern-border border-white5">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-bold text-light"><?php echo htmlspecialchars($user['email']); ?></span>
                                                <span class="badge badge-sm <?php echo $badge_color; ?>">
                                                    <i class="fas fa-tag mr-1"></i>
                                                    <?php echo ucfirst($user['type']); ?>
                                                </span>
                                                <span class="status status--<?php echo $user['is_active'] ? 'success' : 'error'; ?> text-xs">
                                                    <?php echo $user['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-light-80">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                Inscrit: <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                                            </p>
                                            <?php if ($user['last_login']): ?>
                                                <p class="text-sm text-light-80">
                                                    <i class="fas fa-sign-in-alt mr-1"></i>
                                                    Dernière connexion: <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <?php if ($user['candidatures'] > 0 || $user['events_joined'] > 0): ?>
                                                <span class="text-xs text-light-80">
                                                    <?php if ($user['candidatures'] > 0): ?>
                                                        <i class="fas fa-star text-yellow-400"></i> <?php echo $user['candidatures']; ?> cand.
                                                    <?php endif; ?>
                                                    <?php if ($user['events_joined'] > 0): ?>
                                                        <i class="fas fa-check text-green-400"></i> <?php echo $user['events_joined']; ?> evt.
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex gap-2 pt-2 border-t border-white5">
                                        <form method="POST" class="flex-1" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                            <button type="submit" class="w-full btn btn--sm <?php echo $user['is_active'] ? 'btn--secondary' : 'btn--primary'; ?>" 
                                                onclick="return confirm('Confirmer ?');">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?> mr-1"></i>
                                                <?php echo $user['is_active'] ? 'Désactiver' : 'Activer'; ?>
                                            </button>
                                        </form>
                                        
                                        <?php if ($user['id_utilisateur'] !== $id_utilisateur): ?>
                                            <form method="POST" class="flex-1" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                                <button type="submit" class="w-full btn btn--sm bg-red-50020 text-red-400 border border-red-50030 hover:bg-red-50030" 
                                                    onclick="return confirm('Supprimer cet utilisateur ? Cette action est irréversible !');"><i class="fas fa-trash mr-1"></i> Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>