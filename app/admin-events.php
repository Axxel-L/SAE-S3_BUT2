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

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
$events = [];

// Mets à jour les statuts des événements
try {
    $connexion->query("CALL update_event_statuts()");
} catch (Exception $e){}

// Récupère la liste des événements
try {
    $stmt = $connexion->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
    $events = [];
}

// Créer un évenement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $nom = trim($_POST['nom'] ?? '');
    $date_ouverture = $_POST['date_ouverture'] ?? '';
    $date_fermeture = $_POST['date_fermeture'] ?? '';
    $date_debut_vote_final = $_POST['date_debut_vote_final'] ?? '';
    $date_fermeture_vote_final = $_POST['date_fermeture_vote_final'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $validation_errors = [];
    
    if (empty($nom)) $validation_errors[] = "Le nom est obligatoire";
    if (empty($date_ouverture)) $validation_errors[] = "La date d'ouverture est obligatoire";
    if (empty($date_fermeture)) $validation_errors[] = "La date de fin du vote par catégories est obligatoire";
    if (empty($date_debut_vote_final)) $validation_errors[] = "La date de début du vote final est obligatoire";
    if (empty($date_fermeture_vote_final)) $validation_errors[] = "La date de clôture du vote final est obligatoire";
    if (empty($validation_errors)) {
        $d1 = strtotime($date_ouverture);
        $d2 = strtotime($date_fermeture);
        $d3 = strtotime($date_debut_vote_final);
        $d4 = strtotime($date_fermeture_vote_final);
        if ($d2 <= $d1) $validation_errors[] = "La fin du vote par catégories doit être après l'ouverture";
        if ($d3 < $d2) $validation_errors[] = "Le vote final doit commencer après la fin du vote par catégories";
        if ($d4 <= $d3) $validation_errors[] = "La clôture du vote final doit être après son début";
    }

    if (!empty($validation_errors)) {
        $error = implode("<br>", $validation_errors);
    } else {
        try {
            $stmt = $connexion->prepare("
                INSERT INTO evenement (nom, description, date_ouverture, date_fermeture, date_debut_vote_final, date_fermeture_vote_final, statut) 
                VALUES (?, ?, ?, ?, ?, ?, 'preparation')
            ");
            if ($stmt->execute([$nom, $description, $date_ouverture, $date_fermeture, $date_debut_vote_final, $date_fermeture_vote_final])) {
                $success = "Événement créé avec succès ! ✅";

                $log_stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details) VALUES (?, 'ADMIN_EVENT_CREATE', ?)");
                $log_stmt->execute([$id_utilisateur, "Événement créé: $nom"]);

                $stmt = $connexion->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création : " . $e->getMessage();
        }
    }
}

// Supprimer un évenement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $id_evenement = intval($_POST['id_evenement'] ?? 0);

    try {
        $check_stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $check_stmt->execute([$id_evenement]);
        $event = $check_stmt->fetch();

        if ($event && $event['statut'] === 'cloture') {
            $connexion->prepare("DELETE FROM event_candidat WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM resultat WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM nomination WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM emargement_final WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM emargement_categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM bulletin_final WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM bulletin_categorie WHERE id_evenement = ?")->execute([$id_evenement]);
            $connexion->prepare("DELETE FROM registre_electoral WHERE id_evenement = ?")->execute([$id_evenement]);
            $stmt = $connexion->prepare("DELETE FROM evenement WHERE id_evenement = ?");
            if ($stmt->execute([$id_evenement])) {
                $success = "Événement supprimé avec succès ! ✅";
                $log_stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details) VALUES (?, 'ADMIN_EVENT_DELETE', ?)");
                $log_stmt->execute([$id_utilisateur, "Événement $id_evenement supprimé"]);

                $stmt = $connexion->prepare("SELECT * FROM evenement ORDER BY date_ouverture DESC");
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $error = "❌ Vous pouvez seulement supprimer les événements CLÔTURÉS !";
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Configuration des statuts
$statut_config = [
    'preparation' => [
        'label' => 'Préparation',
        'bg' => 'bg-yellow-500/20',
        'text' => 'text-yellow-400',
        'border' => 'border-yellow-500/30',
        'icon' => 'fa-hourglass-start'
    ],
    'ouvert_categories' => [
        'label' => 'Vote Catégories',
        'bg' => 'bg-green-500/20',
        'text' => 'text-green-400',
        'border' => 'border-green-500/30',
        'icon' => 'fa-vote-yea'
    ],
    'ferme_categories' => [
        'label' => 'Attente Vote Final',
        'bg' => 'bg-blue-500/20',
        'text' => 'text-blue-400',
        'border' => 'border-blue-500/30',
        'icon' => 'fa-pause-circle'
    ],
    'ouvert_final' => [
        'label' => 'Vote Final',
        'bg' => 'bg-purple-500/20',
        'text' => 'text-purple-400',
        'border' => 'border-purple-500/30',
        'icon' => 'fa-crown'
    ],
    'cloture' => [
        'label' => 'Clôturé',
        'bg' => 'bg-red-500/20',
        'text' => 'text-red-400',
        'border' => 'border-red-500/30',
        'icon' => 'fa-times-circle'
    ]
];

require_once 'header.php';
?>
<br><br><br> <!-- Espace pour le header -->
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-calendar text-accent mr-3"></i>Gestion des Événements
            </h1>
            <p class="text-xl text-light-80">Les statuts se mettent à jour automatiquement selon les dates ⏱️</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-accent"></i> Créer Événement
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_event">
                        <div>
                            <label class="block mb-2 text-light-80">Nom *</label>
                            <input type="text" name="nom" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" placeholder="Ex: GameCrown 2025" required>
                        </div>

                        <div>
                            <label class="block mb-2 text-light-80">Description</label>
                            <textarea name="description" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" rows="3" placeholder="Description de l'événement"></textarea>
                        </div>
                        <div class="p-4 rounded-2xl bg-green-500/10 border border-green-500/30">
                            <h4 class="font-bold text-green-400 mb-3 flex items-center gap-2">
                                <i class="fas fa-layer-group"></i> Phase 1 : Vote par Catégories
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block mb-1 text-light-80 text-sm">Ouverture *</label>
                                    <input type="datetime-local" name="date_ouverture" class="w-full px-4 py-2 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light text-sm" required>
                                </div>
                                <div>
                                    <label class="block mb-1 text-light-80 text-sm">Fermeture *</label>
                                    <input type="datetime-local" name="date_fermeture" class="w-full px-4 py-2 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light text-sm" required>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/30">
                            <h4 class="font-bold text-purple-400 mb-3 flex items-center gap-2">
                                <i class="fas fa-crown"></i> Phase 2 : Vote Final
                            </h4>
                            <p class="text-xs text-purple-300 mb-3">Vote parmi les vainqueurs de chaque catégorie</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block mb-1 text-light-80 text-sm">Ouverture *</label>
                                    <input type="datetime-local" name="date_debut_vote_final" class="w-full px-4 py-2 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light text-sm" required>
                                </div>
                                <div>
                                    <label class="block mb-1 text-light-80 text-sm">Clôture définitive *</label>
                                    <input type="datetime-local" name="date_fermeture_vote_final" class="w-full px-4 py-2 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light text-sm" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                            <i class="fas fa-plus mr-2"></i> Créer
                        </button>
                    </form>
                    <div class="mt-6 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30">
                        <p class="text-sm text-blue-400 mb-2"><i class="fas fa-info-circle mr-2"></i><strong>Cycle de vie :</strong></p>
                        <div class="text-xs text-blue-300 space-y-1">
                            <p>🟡 <strong>Préparation</strong> → Candidatures ouvertes</p>
                            <p>🟢 <strong>Vote Catégories</strong> → Phase 1 en cours</p>
                            <p>🔵 <strong>Attente</strong> → Entre les 2 phases</p>
                            <p>🟣 <strong>Vote Final</strong> → Phase 2 en cours</p>
                            <p>🔴 <strong>Clôturé</strong> → Terminé</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                    <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-list text-accent"></i> Événements (<?php echo count($events); ?>)
                    </h2>
                    <?php if (empty($events)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                            <p class="text-light-80">Aucun événement créé.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-[700px] overflow-y-auto">
                            <?php foreach ($events as $event): 
                                $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                                $can_delete = $event['statut'] === 'cloture';
                            ?>
                                <div class="glass-card rounded-2xl p-4 modern-border border border-white/10">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-bold text-light text-lg"><?php echo htmlspecialchars($event['nom']); ?></span>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?> border <?php echo $status['border']; ?>">
                                                    <i class="fas <?php echo $status['icon']; ?> mr-1"></i>
                                                    <?php echo $status['label']; ?>
                                                </span>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div class="p-2 rounded-lg bg-green-500/10">
                                                    <p class="text-green-400 font-medium mb-1"><i class="fas fa-layer-group mr-1"></i>Vote Catégories</p>
                                                    <p class="text-light/60">
                                                        <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                                        → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                                    </p>
                                                </div>
                                                <div class="p-2 rounded-lg bg-purple-500/10">
                                                    <p class="text-purple-400 font-medium mb-1"><i class="fas fa-crown mr-1"></i>Vote Final</p>
                                                    <?php if (!empty($event['date_debut_vote_final'])): ?>
                                                        <p class="text-light/60">
                                                            <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                                            → <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-light/40 italic">Non défini</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($event['description']): ?>
                                        <p class="text-sm text-light-80 mb-3 pb-3 border-b border-white/10">
                                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?><?php echo strlen($event['description']) > 100 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>
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
                                                <button type="submit" class="w-full px-4 py-2 rounded-2xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors text-sm" onclick="return confirm('Supprimer cet événement ?');">
                                                    <i class="fas fa-trash mr-1"></i> Supprimer
                                                </button>
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