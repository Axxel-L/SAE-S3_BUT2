<?php
// ========================================
// GESTION DES ÉVÉNEMENTS - ADMIN
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
$events = [];

// 📋 Récupérer tous les événements
try {
    $stmt = $connexion->prepare("
        SELECT * FROM evenement 
        ORDER BY date_ouverture DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
    $events = [];
}

// ========================================
// 1️⃣ CRÉER UN NOUVEL ÉVÉNEMENT
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $nom = trim($_POST['nom'] ?? '');
    $date_ouverture = $_POST['date_ouverture'] ?? '';
    $date_fermeture = $_POST['date_fermeture'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!empty($nom) && !empty($date_ouverture) && !empty($date_fermeture)) {
        try {
            $stmt = $connexion->prepare("
                INSERT INTO evenement (nom, description, date_ouverture, date_fermeture, statut) 
                VALUES (?, ?, ?, ?, 'preparation')
            ");
            if ($stmt->execute([$nom, $description, $date_ouverture, $date_fermeture])) {
                $success = "Événement créé avec succès ! ✅ Le statut se changera automatiquement selon les dates.";

                // Log audit
                $log_stmt = $connexion->prepare("
                    INSERT INTO journal_securite (id_utilisateur, action, details) 
                    VALUES (?, 'ADMIN_EVENT_CREATE', ?)
                ");
                $log_stmt->execute([$id_utilisateur, "Événement créé: $nom"]);

                // Rafraîchir
                $stmt = $connexion->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires !";
    }
}

// ========================================
// 2️⃣ SUPPRIMER UN ÉVÉNEMENT (seulement si fermé)
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $id_evenement = intval($_POST['id_evenement'] ?? 0);

    try {
        // Vérifier que l'événement est fermé
        $check_stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $check_stmt->execute([$id_evenement]);
        $event = $check_stmt->fetch();

        if ($event && $event['statut'] === 'cloture') {
            // Supprimer les données liées
            $connexion->prepare("DELETE FROM event_candidat WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM resultat WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM nomination WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM emargement_final WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM emargement_categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM bulletin_final WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM bulletin_categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM registreelectoral WHERE id_evenement = ?")->execute([$id_evenement]);

            // Supprimer l'événement
            $stmt = $connexion->prepare("DELETE FROM evenement WHERE id_evenement = ?");
            if ($stmt->execute([$id_evenement])) {
                $success = "Événement supprimé avec succès ! ✅";

                // Log audit
                $log_stmt = $connexion->prepare("
                    INSERT INTO journal_securite (id_utilisateur, action, details) 
                    VALUES (?, 'ADMIN_EVENT_DELETE', ?)
                ");
                $log_stmt->execute([$id_utilisateur, "Événement $id_evenement supprimé"]);

                // Rafraîchir
                $stmt = $connexion->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $error = "❌ Vous pouvez seulement supprimer les événements FERMÉS !";
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

require_once 'header.php';
?>

<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <!-- En-tête -->
        <div class="mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                <i class="fas fa-calendar text-accent mr-3"></i>Gestion des Événements
            </h1>
            <p class="text-xl text-light-80">Les statuts se changent automatiquement selon les dates ! ⏱️</p>
        </div>

        <!-- Messages -->
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
            <!-- Colonne gauche : Créer événement -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-accent"></i> Créer Événement
                    </h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_event">

                        <div>
                            <label class="block mb-2 text-light-80">Nom *</label>
                            <input type="text" name="nom" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" placeholder="Ex: Votation 2025" required>
                        </div>

                        <div>
                            <label class="block mb-2 text-light-80">Description</label>
                            <textarea name="description" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" rows="3" placeholder="Description de l'événement"></textarea>
                        </div>

                        <div>
                            <label class="block mb-2 text-light-80">Ouverture *</label>
                            <input type="datetime-local" name="date_ouverture" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" required>
                        </div>

                        <div>
                            <label class="block mb-2 text-light-80">Fermeture *</label>
                            <input type="datetime-local" name="date_fermeture" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" required>
                        </div>

                        <button type="submit" class="w-full px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                            <i class="fas fa-plus mr-2"></i> Créer
                        </button>
                    </form>

                    <div class="mt-6 p-4 rounded-lg bg-blue-500/10 border border-blue-500/30">
                        <p class="text-sm text-blue-400">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Statuts automatiques :</strong>
                        </p>
                        <ul class="text-xs text-blue-300 mt-2 space-y-1">
                            <li>🔵 Préparation → Ouvert (à l'heure d'ouverture)</li>
                            <li>🟢 Ouvert → Fermé (à l'heure de fermeture)</li>
                            <li>🔴 Fermé → Supprimable (optionnel)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : Liste événements -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-list text-accent"></i> Événements (<?php echo count($events); ?>)
                    </h2>

                    <?php if (empty($events)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-light-80">Aucun événement créé.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-[600px] overflow-y-auto">
                            <?php foreach ($events as $event): ?>
                                <?php
                                $status_colors = [
                                    'preparation' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-400', 'border' => 'border-yellow-500/30', 'icon' => 'fa-hourglass-start'],
                                    'ouvert' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-400', 'border' => 'border-green-500/30', 'icon' => 'fa-check-circle'],
                                    'cloture' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-400', 'border' => 'border-red-500/30', 'icon' => 'fa-times-circle']
                                ];
                                $status = $status_colors[$event['statut']] ?? $status_colors['preparation'];
                                $can_delete = $event['statut'] === 'cloture';
                                ?>
                                <div class="glass-card rounded-2xl p-4 modern-border">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-bold text-light"><?php echo htmlspecialchars($event['nom']); ?></span>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?> border <?php echo $status['border']; ?>">
                                                    <i class="fas <?php echo $status['icon']; ?> mr-1"></i>
                                                    <?php echo ucfirst($event['statut']); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-light-80">
                                                <i class="fas fa-door-open mr-1 text-green-400"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                            </p>
                                            <p class="text-sm text-light-80">
                                                <i class="fas fa-door-closed mr-1 text-red-400"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if ($event['description']): ?>
                                        <p class="text-sm text-light-80 mb-3 pb-3 border-b border-white/10">
                                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?><?php echo strlen($event['description']) > 100 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="flex gap-2 pt-2">
                                        <a href="admin-resultats.php?event=<?php echo $event['id_evenement']; ?>" class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 text-center transition-colors text-sm">
                                            <i class="fas fa-chart-bar mr-1"></i> Résultats
                                        </a>

                                        <a href="admin-categories.php?event=<?php echo $event['id_evenement']; ?>" class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 text-center transition-colors text-sm">
                                            <i class="fas fa-tags mr-1"></i> Catégories
                                        </a>

                                        <?php if ($can_delete): ?>
                                            <form method="POST" style="display: inline; flex: 1;">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                                <button type="submit" class="w-full px-4 py-2 rounded-2xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors text-sm"
                                                    onclick="return confirm('Supprimer cet événement ? Cette action est irréversible !');"><i class="fas fa-trash mr-1"></i> Supprimer</button>
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