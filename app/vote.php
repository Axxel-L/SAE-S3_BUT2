<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// 🔄 Mettre à jour automatiquement les statuts
try {
    $connexion->query("CALL update_event_statuts()");
} catch (Exception $e) {}

// Récupérer les événements où le vote catégories est possible
try {
    $stmt = $connexion->prepare("
        SELECT * FROM evenement 
        WHERE statut IN ('preparation', 'ouvert_categories', 'ferme_categories', 'ouvert_final')
        ORDER BY date_ouverture DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Erreur : ' . $e->getMessage();
    $events = [];
}

// Traitement du vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
    $id_jeu = intval($_POST['id_jeu'] ?? 0);
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $id_evenement = intval($_POST['id_evenement'] ?? 0);

    try {
        $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            throw new Exception("Événement non trouvé.");
        }
        
        if ($event['statut'] !== 'ouvert_categories') {
            throw new Exception("Le vote par catégories n'est pas ouvert pour cet événement.");
        }
        
        // Vérifier inscription
        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Vous n'êtes pas inscrit à cet événement.");
        }
        
        // Vérifier vote existant
        $stmt = $connexion->prepare("SELECT id_emargement FROM emargement_categorie WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Vous avez déjà voté pour cette catégorie !");
        }
        
        // Vérifier nomination
        $stmt = $connexion->prepare("SELECT id_nomination FROM nomination WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Ce jeu n'est pas nominé dans cette catégorie.");
        }
        
        // Enregistrer le vote
        $connexion->beginTransaction();
        try {
            $stmt = $connexion->prepare("INSERT INTO bulletin_categorie (id_jeu, id_categorie, id_evenement) VALUES (?, ?, ?)");
            $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
            
            $stmt = $connexion->prepare("INSERT INTO emargement_categorie (id_utilisateur, id_categorie, id_evenement) VALUES (?, ?, ?)");
            $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
            
            $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details) VALUES (?, 'VOTE_CATEGORIE', ?)");
            $stmt->execute([$id_utilisateur, "Catégorie $id_categorie, événement $id_evenement"]);
            
            $connexion->commit();
            $success = "Vote enregistré avec succès ! 🗳️";
        } catch (Exception $e) {
            $connexion->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Config statuts
$statut_config = [
    'preparation' => ['label' => 'Préparation', 'color' => 'yellow', 'icon' => 'fa-hourglass-start', 'can_vote_cat' => false, 'can_vote_final' => false],
    'ouvert_categories' => ['label' => 'Vote Catégories', 'color' => 'green', 'icon' => 'fa-vote-yea', 'can_vote_cat' => true, 'can_vote_final' => false],
    'ferme_categories' => ['label' => 'Attente Vote Final', 'color' => 'blue', 'icon' => 'fa-pause-circle', 'can_vote_cat' => false, 'can_vote_final' => false],
    'ouvert_final' => ['label' => 'Vote Final', 'color' => 'purple', 'icon' => 'fa-crown', 'can_vote_cat' => false, 'can_vote_final' => true],
    'cloture' => ['label' => 'Clôturé', 'color' => 'red', 'icon' => 'fa-times-circle', 'can_vote_cat' => false, 'can_vote_final' => false]
];

$color_classes = [
    'yellow' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-400', 'border' => 'border-yellow-500/30'],
    'green' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-400', 'border' => 'border-green-500/30'],
    'blue' => ['bg' => 'bg-blue-500/20', 'text' => 'text-blue-400', 'border' => 'border-blue-500/30'],
    'purple' => ['bg' => 'bg-purple-500/20', 'text' => 'text-purple-400', 'border' => 'border-purple-500/30'],
    'red' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-400', 'border' => 'border-red-500/30']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Vote par Catégorie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">
    <?php require_once 'header.php'; ?>

    <section class="py-20 px-6 min-h-screen">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-vote-yea mr-3"></i>Vote par Catégorie
                </h1>
                <p class="text-xl text-light/80">Phase 1 : Votez pour vos jeux préférés dans chaque catégorie</p>
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

            <?php if (empty($events)): ?>
                <div class="glass-card rounded-4xl p-12 text-center modern-border">
                    <i class="fas fa-calendar-times text-accent text-5xl mb-4"></i>
                    <h2 class="text-2xl font-bold font-orbitron mb-2">Aucun événement disponible</h2>
                    <p class="text-light/80">Il n'y a pas d'événement de vote en cours.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                    $colors = $color_classes[$status['color']];
                    
                    // Vérifier inscription
                    try {
                        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $isRegistered = $stmt->rowCount() > 0;
                    } catch (Exception $e) { $isRegistered = false; }
                    
                    // Récupérer catégories si vote ouvert
                    $categories = [];
                    if ($isRegistered && $status['can_vote_cat']) {
                        try {
                            $stmt = $connexion->prepare("
                                SELECT DISTINCT c.id_categorie, c.nom, c.description
                                FROM categorie c
                                JOIN nomination n ON c.id_categorie = n.id_categorie
                                WHERE c.id_evenement = ? AND n.id_evenement = ?
                                ORDER BY c.nom ASC
                            ");
                            $stmt->execute([$event['id_evenement'], $event['id_evenement']]);
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) { $categories = []; }
                    }
                ?>
                    <div class="glass-card rounded-4xl p-8 mb-8 modern-border">
                        <div class="mb-6">
                            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                                <div>
                                    <h2 class="text-3xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h2>
                                    <?php if ($event['description']): ?>
                                        <p class="text-light/60"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $colors['bg']; ?> <?php echo $colors['text']; ?> border <?php echo $colors['border']; ?>">
                                    <i class="fas <?php echo $status['icon']; ?> mr-2"></i><?php echo $status['label']; ?>
                                </span>
                            </div>
                            
                            <!-- Timeline -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="p-4 rounded-xl <?php echo $status['can_vote_cat'] ? 'bg-green-500/20 border-green-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-layer-group <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/60'; ?>"></i>
                                        <span class="font-bold <?php echo $status['can_vote_cat'] ? 'text-green-400' : 'text-light/80'; ?>">Phase 1 : Vote Catégories</span>
                                        <?php if ($status['can_vote_cat']): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-500 text-dark font-bold animate-pulse">EN COURS</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-light/60">
                                        Du <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                        au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                    </p>
                                </div>
                                
                                <div class="p-4 rounded-xl <?php echo $status['can_vote_final'] ? 'bg-purple-500/20 border-purple-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-crown <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/60'; ?>"></i>
                                        <span class="font-bold <?php echo $status['can_vote_final'] ? 'text-purple-400' : 'text-light/80'; ?>">Phase 2 : Vote Final</span>
                                        <?php if ($status['can_vote_final']): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-purple-500 text-white font-bold animate-pulse">EN COURS</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($event['date_debut_vote_final'])): ?>
                                        <p class="text-sm text-light/60">
                                            Du <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                            au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-sm text-light/40 italic">Dates non définies</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($status['can_vote_final']): ?>
                                <div class="p-4 rounded-xl bg-purple-500/20 border border-purple-500/50 text-center">
                                    <p class="text-purple-300 mb-3"><i class="fas fa-crown mr-2"></i>Le vote final est ouvert !</p>
                                    <a href="vote-final.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-purple-500 text-white font-bold hover:bg-purple-400 transition-colors">
                                        <i class="fas fa-crown"></i> Accéder au Vote Final
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!$isRegistered): ?>
                            <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-orange-400 text-3xl mb-3"></i>
                                <p class="text-orange-400 text-lg mb-4">Vous devez vous inscrire à cet événement pour voter.</p>
                                <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </a>
                            </div>
                        <?php elseif (!$status['can_vote_cat']): ?>
                            <div class="p-6 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-center">
                                <i class="fas fa-info-circle text-blue-400 text-3xl mb-3"></i>
                                <p class="text-blue-400 text-lg">
                                    <?php if ($event['statut'] === 'preparation'): ?>
                                        Les votes ouvriront le <?php echo date('d/m/Y à H:i', strtotime($event['date_ouverture'])); ?>
                                    <?php elseif ($event['statut'] === 'ferme_categories'): ?>
                                        Vote par catégories terminé. Vote final le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?>
                                    <?php else: ?>
                                        Le vote par catégories n'est pas disponible.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php if (empty($categories)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-info-circle text-accent text-3xl mb-3"></i>
                                        <p class="text-light/80">Aucune catégorie avec des jeux nominés.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($categories as $categorie):
                                        // Vérifier si déjà voté
                                        try {
                                            $stmt = $connexion->prepare("SELECT id_emargement FROM emargement_categorie WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?");
                                            $stmt->execute([$id_utilisateur, $categorie['id_categorie'], $event['id_evenement']]);
                                            $dejaVote = $stmt->rowCount() > 0;
                                        } catch (Exception $e) { $dejaVote = false; }

                                        // Récupérer jeux nominés
                                        try {
                                            $stmt = $connexion->prepare("SELECT j.*, n.id_nomination FROM jeu j JOIN nomination n ON j.id_jeu = n.id_jeu WHERE n.id_categorie = ? AND n.id_evenement = ? ORDER BY j.titre ASC");
                                            $stmt->execute([$categorie['id_categorie'], $event['id_evenement']]);
                                            $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) { $jeux = []; }
                                    ?>
                                        <div class="border border-accent/20 rounded-3xl p-6 <?php echo $dejaVote ? 'bg-green-500/5' : ''; ?>">
                                            <div class="flex items-start justify-between mb-4">
                                                <div>
                                                    <h3 class="text-2xl font-bold font-orbitron text-light mb-1"><?php echo htmlspecialchars($categorie['nom']); ?></h3>
                                                    <?php if ($categorie['description']): ?>
                                                        <p class="text-light/80 text-sm"><?php echo htmlspecialchars($categorie['description']); ?></p>
                                                    <?php endif; ?>
                                                    <p class="text-xs text-light/50 mt-1"><i class="fas fa-gamepad mr-1"></i><?php echo count($jeux); ?> jeu(x)</p>
                                                </div>
                                                <?php if ($dejaVote): ?>
                                                    <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-xs font-medium">
                                                        <i class="fas fa-check"></i> Voté
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($dejaVote): ?>
                                                <p class="text-light/80 italic"><i class="fas fa-info-circle mr-1"></i>Vote enregistré (anonyme et définitif).</p>
                                            <?php elseif (empty($jeux)): ?>
                                                <p class="text-light/60 italic">Aucun jeu nominé.</p>
                                            <?php else: ?>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                    <?php foreach ($jeux as $jeu): ?>
                                                        <form method="POST" class="flex">
                                                            <input type="hidden" name="action" value="vote">
                                                            <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                                            <input type="hidden" name="id_categorie" value="<?php echo $categorie['id_categorie']; ?>">
                                                            <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                                            
                                                            <button type="submit" class="w-full group" onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> ?');">
                                                                <div class="glass-card w-full bg-white/5 border border-white/10 rounded-2xl p-4 hover:bg-accent/10 hover:border-accent/50 transition-all duration-300">
                                                                    <?php if ($jeu['image']): ?>
                                                                        <div class="mb-3 rounded-lg overflow-hidden h-32 bg-black/50">
                                                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover">
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="mb-3 rounded-lg h-32 bg-white/5 flex items-center justify-center">
                                                                            <i class="fas fa-gamepad text-4xl text-light/30"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <h4 class="font-bold text-light text-center mb-1"><?php echo htmlspecialchars($jeu['titre']); ?></h4>
                                                                    <?php if ($jeu['editeur']): ?>
                                                                        <p class="text-xs text-light/60 text-center"><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                                                    <?php endif; ?>
                                                                    <div class="mt-3 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                                        <i class="fas fa-vote-yea text-accent mr-2"></i>
                                                                        <span class="text-accent text-sm font-medium">Voter</span>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </form>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="text-center mt-12">
                <a href="vote-final.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-purple-500/20 text-purple-400 border border-purple-500/30 font-bold hover:bg-purple-500/30 transition-colors">
                    <i class="fas fa-crown mr-2"></i>Voir le vote final
                </a>
            </div>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>