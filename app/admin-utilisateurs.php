<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo "<script>alert('Accès réservé aux administrateurs'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
$users = [];

// Récupérer tous les utilisateurs
try {
    $stmt = $connexion->prepare("
        SELECT 
            u.id_utilisateur,
            u.email,
            u.type,
            u.date_inscription,
            (SELECT COUNT(*) FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur) as is_candidat,
            (SELECT c.statut FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur LIMIT 1) as candidat_statut
        FROM utilisateur u
        ORDER BY u.date_inscription DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Traite les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Ajouter un utilisateur
    if ($_POST['action'] === 'add_user') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'joueur';
        
        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email invalide !";
        } elseif (strlen($password) < 8) {
            $error = "Mot de passe minimum 8 caractères !";
        } elseif (!in_array($type, ['joueur', 'candidat', 'admin'])) {
            $error = "Type invalide !";
        } else {
            try {
                // Vérifier que l'email est unique
                $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Cet email existe déjà !";
                } else {
                    // On utilise le même hash que login.php
                    $salt = bin2hex(random_bytes(16));
                    $password_hash = hash('sha256', $password . $salt);
                    
                    $stmt = $connexion->prepare("
                        INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$email, $password_hash, $salt, $type])) {
                        $new_user_id = $connexion->lastInsertId();
                        
                        // Si c'est un candidat on créer aussi le profil candidat
                        if ($type === 'candidat') {
                            $stmt = $connexion->prepare("
                                INSERT INTO candidat (id_utilisateur, nom, statut, date_inscription) 
                                VALUES (?, ?, 'valide', NOW())
                            ");
                            $stmt->execute([$new_user_id, $email]);
                        }
                        $success = "✅ Utilisateur créé avec succès !";
                        
                        // Ajoute aux logs
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'ADMIN_USER_CREATE', ?, ?)");
                        $stmt->execute([$id_utilisateur, "Utilisateur créé: $email ($type)", $_SERVER['REMOTE_ADDR'] ?? '']);
                        
                        // Rafraîchir la liste
                        $stmt = $connexion->prepare("
                            SELECT u.id_utilisateur, u.email, u.type, u.date_inscription,
                                   (SELECT COUNT(*) FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur) as is_candidat,
                                   (SELECT c.statut FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur LIMIT 1) as candidat_statut
                            FROM utilisateur u ORDER BY u.date_inscription DESC
                        ");
                        $stmt->execute();
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
    
    // Changer le type d'un utilisateur
    elseif ($_POST['action'] === 'change_type') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_type = $_POST['new_type'] ?? '';
        
        if ($user_id > 0 && $user_id !== $id_utilisateur && in_array($new_type, ['joueur', 'candidat', 'admin'])) {
            try {
                $stmt = $connexion->prepare("UPDATE utilisateur SET type = ? WHERE id_utilisateur = ?");
                $stmt->execute([$new_type, $user_id]);
                
                // Si devient candidat, créer le profil candidat
                if ($new_type === 'candidat') {
                    $stmt = $connexion->prepare("SELECT id_candidat FROM candidat WHERE id_utilisateur = ?");
                    $stmt->execute([$user_id]);
                    if ($stmt->rowCount() === 0) {
                        $stmt = $connexion->prepare("SELECT email FROM utilisateur WHERE id_utilisateur = ?");
                        $stmt->execute([$user_id]);
                        $user_email = $stmt->fetchColumn();
                        
                        $stmt = $connexion->prepare("INSERT INTO candidat (id_utilisateur, nom, statut, date_inscription) VALUES (?, ?, 'valide', NOW())");
                        $stmt->execute([$user_id, $user_email]);
                    }
                }
                $success = "✅ Type modifié !";
                
                // Rafraîchir la liste
                $stmt = $connexion->prepare("
                    SELECT u.id_utilisateur, u.email, u.type, u.date_inscription,
                           (SELECT COUNT(*) FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur) as is_candidat,
                           (SELECT c.statut FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur LIMIT 1) as candidat_statut
                    FROM utilisateur u ORDER BY u.date_inscription DESC
                ");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
    
    // Supprimer un utilisateur
    elseif ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0 && $user_id !== $id_utilisateur) {
            try {
                $connexion->beginTransaction();
                
                // Supprimer les données liées
                $connexion->prepare("DELETE FROM event_candidat WHERE id_candidat IN (SELECT id_candidat FROM candidat WHERE id_utilisateur = ?)")->execute([$user_id]);
                $connexion->prepare("DELETE FROM candidat WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM registre_electoral WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM bulletin_categorie WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM bulletin_final WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM commentaire WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?")->execute([$user_id]);
                $connexion->commit();
                $success = "🗑️ Utilisateur supprimé !";
                
                // Ajoute aux logs
                $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'ADMIN_USER_DELETE', ?, ?)");
                $stmt->execute([$id_utilisateur, "Utilisateur #$user_id supprimé", $_SERVER['REMOTE_ADDR'] ?? '']);
                
                // Rafraîchir la liste
                $stmt = $connexion->prepare("
                    SELECT u.id_utilisateur, u.email, u.type, u.date_inscription,
                           (SELECT COUNT(*) FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur) as is_candidat,
                           (SELECT c.statut FROM candidat c WHERE c.id_utilisateur = u.id_utilisateur LIMIT 1) as candidat_statut
                    FROM utilisateur u ORDER BY u.date_inscription DESC
                ");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $connexion->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Configuration des types
$type_config = [
    'joueur' => ['label' => 'Joueur', 'color' => 'blue', 'icon' => 'fa-gamepad'],
    'candidat' => ['label' => 'Candidat', 'color' => 'purple', 'icon' => 'fa-trophy'],
    'admin' => ['label' => 'Admin', 'color' => 'red', 'icon' => 'fa-shield-alt']
];

require_once 'header.php';
?>

<br><br><br> <!-- Espace pour le header -->
<section class="py-20 px-6 min-h-screen">
    <div class="container mx-auto max-w-7xl">
        
        <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-users text-accent mr-3"></i>Gestion des Utilisateurs
                </h1>
                <p class="text-xl text-light-80">Gérez les comptes utilisateurs</p>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire création -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 sticky top-24">
                    <h2 class="text-2xl font-bold text-accent mb-4 flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Créer un utilisateur
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_user">
                        <div>
                            <label class="block mb-2 text-light-80 text-sm font-medium">Email *</label>
                            <input type="email" name="email" required
                                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300"
                                placeholder="email@exemple.com">
                        </div>
                        <div>
                            <label class="block mb-2 text-light-80 text-sm font-medium">Mot de passe * (min 8 car.)</label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300"
                                placeholder="••••••••">
                        </div>
                        <div>
                            <label class="block mb-2 text-light-80 text-sm font-medium">Type *</label>
                            <div class="relative">
                                <select name="type" required
                                    class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                                    <option value="joueur" class="text-black bg-white">🎮 Joueur</option>
                                    <option value="candidat" class="text-black bg-white">🏆 Candidat (validé)</option>
                                    <option value="admin" class="text-black bg-white">🛡️ Administrateur</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                                    <i class="fas fa-chevron-down text-light-80"></i>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full px-6 py-4 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                            <i class="fas fa-plus mr-2"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h2 class="text-2xl font-bold text-accent mb-4 flex items-center gap-2">
                        <i class="fas fa-list"></i> Utilisateurs (<?php echo count($users); ?>)
                    </h2>
                    <?php if (empty($users)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-xl text-light-80">Aucun utilisateur.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                            <?php foreach ($users as $user): 
                                $type = $type_config[$user['type']] ?? $type_config['joueur'];
                                $is_me = $user['id_utilisateur'] == $id_utilisateur;
                            ?>
                                <div class="glass-card rounded-2xl p-6 modern-border border border-white/10 <?php echo $is_me ? 'border-accent/50' : ''; ?>">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-bold text-light text-lg"><?php echo htmlspecialchars($user['email']); ?></span>
                                                <?php if ($is_me): ?>
                                                    <span class="px-2 py-1 rounded-full text-xs bg-accent/20 text-accent">Vous</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                                <span class="px-3 py-1.5 rounded-lg bg-<?php echo $type['color']; ?>-500/20 text-<?php echo $type['color']; ?>-400 text-xs font-medium border border-<?php echo $type['color']; ?>-500/30">
                                                    <i class="fas <?php echo $type['icon']; ?> mr-2"></i><?php echo $type['label']; ?>
                                                </span>
                                                <?php if ($user['type'] === 'candidat' && $user['candidat_statut']): ?>
                                                    <?php 
                                                    $statut_colors = ['en_attente' => 'yellow', 'valide' => 'green', 'refuse' => 'red'];
                                                    $statut_labels = ['en_attente' => 'En attente', 'valide' => 'Validé', 'refuse' => 'Refusé'];
                                                    $sc = $statut_colors[$user['candidat_statut']] ?? 'gray';
                                                    $sl = $statut_labels[$user['candidat_statut']] ?? $user['candidat_statut'];
                                                    ?>
                                                    <span class="px-3 py-1.5 rounded-lg bg-<?php echo $sc; ?>-500/20 text-<?php echo $sc; ?>-400 text-xs font-medium border border-<?php echo $sc; ?>-500/30">
                                                        <?php echo $sl; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-light-80 text-xs flex items-center gap-1">
                                                    <i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (!$is_me): ?>
                                            <div class="flex gap-2">
                                                <!-- Changer type -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="change_type">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                                    <div class="relative">
                                                        <select name="new_type" onchange="this.form.submit()" 
                                                            class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-light text-sm appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                                                            <option value="joueur" <?php echo $user['type'] === 'joueur' ? 'selected' : ''; ?> class="text-black bg-white">Joueur</option>
                                                            <option value="candidat" <?php echo $user['type'] === 'candidat' ? 'selected' : ''; ?> class="text-black bg-white">Candidat</option>
                                                            <option value="admin" <?php echo $user['type'] === 'admin' ? 'selected' : ''; ?> class="text-black bg-white">Admin</option>
                                                        </select>
                                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2">
                                                            <i class="fas fa-chevron-down text-light-80 text-xs"></i>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- Supprimer -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id_utilisateur']; ?>">
                                                    <button type="submit" onclick="return confirm('Supprimer cet utilisateur ?')"
                                                        class="px-3 py-2 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500/30 border border-red-500/30 transition-colors text-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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