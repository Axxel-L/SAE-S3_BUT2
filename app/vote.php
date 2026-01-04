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

// Mettre à jour les statuts
try {
    $connexion->query("CALL update_event_statuts()");
} catch (Exception $e) {}

// Récupérer les événements
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
        
        if (!$event) throw new Exception("Événement non trouvé.");
        if ($event['statut'] !== 'ouvert_categories') throw new Exception("Le vote par catégories n'est pas ouvert.");
        
        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        if ($stmt->rowCount() === 0) throw new Exception("Vous n'êtes pas inscrit à cet événement.");
        
        $stmt = $connexion->prepare("SELECT id_emargement FROM emargement_categorie WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
        if ($stmt->rowCount() > 0) throw new Exception("Vous avez déjà voté pour cette catégorie !");
        
        $stmt = $connexion->prepare("SELECT id_nomination FROM nomination WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
        if ($stmt->rowCount() === 0) throw new Exception("Ce jeu n'est pas nominé dans cette catégorie.");
        
        $connexion->beginTransaction();
        $stmt = $connexion->prepare("INSERT INTO bulletin_categorie (id_jeu, id_categorie, id_evenement) VALUES (?, ?, ?)");
        $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
        
        $stmt = $connexion->prepare("INSERT INTO emargement_categorie (id_utilisateur, id_categorie, id_evenement) VALUES (?, ?, ?)");
        $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
        
        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'VOTE_CATEGORIE', ?, ?)");
        $stmt->execute([$id_utilisateur, "Catégorie $id_categorie, événement $id_evenement", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $connexion->commit();
        $success = "Vote enregistré avec succès !";
    } catch (Exception $e) {
        if ($connexion->inTransaction()) $connexion->rollBack();
        $error = $e->getMessage();
    }
}

$statut_config = [
    'preparation' => [
        'label' => 'Préparation',
        'bg' => 'bg-yellow-500/20',
        'text' => 'text-yellow-400',
        'border' => 'border-yellow-500/30',
        'icon' => 'fa-hourglass-start',
        'can_vote_cat' => false
    ],
    'ouvert_categories' => [
        'label' => 'Vote Catégories',
        'bg' => 'bg-green-500/20',
        'text' => 'text-green-400',
        'border' => 'border-green-500/30',
        'icon' => 'fa-vote-yea',
        'can_vote_cat' => true
    ],
    'ferme_categories' => [
        'label' => 'Attente Vote Final',
        'bg' => 'bg-blue-500/20',
        'text' => 'text-blue-400',
        'border' => 'border-blue-500/30',
        'icon' => 'fa-pause-circle',
        'can_vote_cat' => false
    ],
    'ouvert_final' => [
        'label' => 'Vote Final',
        'bg' => 'bg-purple-500/20',
        'text' => 'text-purple-400',
        'border' => 'border-purple-500/30',
        'icon' => 'fa-crown',
        'can_vote_cat' => false
    ],
    'cloture' => [
        'label' => 'Clôturé',
        'bg' => 'bg-red-500/20',
        'text' => 'text-red-400',
        'border' => 'border-red-500/30',
        'icon' => 'fa-times-circle',
        'can_vote_cat' => false
    ]
];

require_once 'header.php';
?>

<br><br><br> <!-- Espace pour le header -->
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-vote-yea text-accent mr-3"></i>Vote par Catégorie
            </h1>
            <p class="text-xl text-light-80">Votez pour vos jeux préférés dans chaque catégorie</p>
        </div>
        
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3 max-w-2xl mx-auto">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3 max-w-2xl mx-auto">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border border-2 border-white/10 text-center max-w-2xl mx-auto">
                <i class="fas fa-calendar-times text-5xl text-light/20 mb-6"></i>
                <h2 class="text-2xl font-bold text-light mb-3">Aucun événement disponible</h2>
                <p class="text-light-80">Les votes ne sont pas encore ouverts. Revenez bientôt !</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): 
                $status = $statut_config[$event['statut']] ?? $statut_config['preparation'];
                
                // Vérifier inscription
                try {
                    $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                    $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                    $isRegistered = $stmt->rowCount() > 0;
                } catch (Exception $e) { $isRegistered = false; }
                
                // Récupérer catégories
                try {
                    $stmt = $connexion->prepare("SELECT c.*, (SELECT COUNT(*) FROM nomination n WHERE n.id_categorie = c.id_categorie AND n.id_evenement = c.id_evenement) as nb_jeux FROM categorie c WHERE c.id_evenement = ? ORDER BY c.nom ASC");
                    $stmt->execute([$event['id_evenement']]);
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $categories = []; }
            ?>
                <div class="glass-card rounded-3xl modern-border border-2 border-white/10 mb-8 overflow-hidden">
                    <!-- Header événement -->
                    <div class="p-6 border-b border-white/10 bg-white/5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h2>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?> border <?php echo $status['border']; ?>">
                                        <i class="fas <?php echo $status['icon']; ?> mr-1"></i><?php echo $status['label']; ?>
                                    </span>
                                    <?php if ($isRegistered): ?>
                                        <span class="px-3 py-1 rounded-full text-xs bg-green-500/20 text-green-400 border border-green-500/30">
                                            <i class="fas fa-check mr-1"></i>Inscrit
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($event['statut'] === 'ouvert_final'): ?>
                                <a href="vote-final.php" class="px-5 py-2 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors flex items-center gap-2 border border-white/10">
                                    <i class="fas fa-crown"></i> Vote Final
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if (!$isRegistered): ?>
                            <div class="p-6 rounded-2xl bg-yellow-500/10 border border-yellow-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl mb-3"></i>
                                <p class="text-yellow-400 text-lg mb-4">Inscrivez-vous pour voter !</p>
                                <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </a>
                            </div>
                        <?php elseif (!$status['can_vote_cat']): ?>
                            <div class="p-6 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-center">
                                <i class="fas fa-info-circle text-blue-400 text-3xl mb-3"></i>
                                <p class="text-blue-400 text-lg">
                                    <?php if ($event['statut'] === 'preparation'): ?>
                                        Votes ouvrent le <?php echo date('d/m/Y à H:i', strtotime($event['date_ouverture'])); ?>
                                    <?php elseif ($event['statut'] === 'ferme_categories'): ?>
                                        Vote final le <?php echo date('d/m/Y à H:i', strtotime($event['date_debut_vote_final'])); ?>
                                    <?php else: ?>
                                        Vote par catégories terminé.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-8">
                                <?php foreach ($categories as $categorie):
                                    try {
                                        $stmt = $connexion->prepare("SELECT id_emargement FROM emargement_categorie WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?");
                                        $stmt->execute([$id_utilisateur, $categorie['id_categorie'], $event['id_evenement']]);
                                        $dejaVote = $stmt->rowCount() > 0;
                                    } catch (Exception $e) { $dejaVote = false; }

                                    try {
                                        $stmt = $connexion->prepare("SELECT j.* FROM jeu j JOIN nomination n ON j.id_jeu = n.id_jeu WHERE n.id_categorie = ? AND n.id_evenement = ? ORDER BY j.titre ASC");
                                        $stmt->execute([$categorie['id_categorie'], $event['id_evenement']]);
                                        $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (Exception $e) { $jeux = []; }
                                ?>
                                    <div class="rounded-2xl border <?php echo $dejaVote ? 'border-green-500/30 bg-green-500/5' : 'border-white/10'; ?> p-6">
                                        <div class="flex items-start justify-between mb-4">
                                            <div>
                                                <h3 class="text-xl font-bold text-light mb-1"><?php echo htmlspecialchars($categorie['nom']); ?></h3>
                                                <?php if ($categorie['description']): ?>
                                                    <p class="text-light-80 text-sm"><?php echo htmlspecialchars($categorie['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($dejaVote): ?>
                                                <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-sm font-medium border border-green-500/30">
                                                    <i class="fas fa-check mr-1"></i>Voté
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($dejaVote): ?>
                                            <p class="text-light-80 italic text-sm"><i class="fas fa-lock mr-1"></i>Vote enregistré (anonyme et définitif)</p>
                                        <?php elseif (empty($jeux)): ?>
                                            <p class="text-light-80 italic">Aucun jeu nominé.</p>
                                        <?php else: ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <?php foreach ($jeux as $jeu): ?>
                                                    <div class="group relative glass-card rounded-2xl overflow-hidden hover:border-accent/50 transition-all border border-white/10">
                                                        <!-- Image -->
                                                        <div class="h-36 bg-accent/10">
                                                            <?php if ($jeu['image']): ?>
                                                                <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="" class="w-full h-full object-cover">
                                                            <?php else: ?>
                                                                <div class="w-full h-full flex items-center justify-center">
                                                                    <i class="fas fa-gamepad text-4xl text-light/20"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- Infos -->
                                                        <div class="p-4">
                                                            <h4 class="font-bold text-light mb-1 truncate"><?php echo htmlspecialchars($jeu['titre']); ?></h4>
                                                            <?php if ($jeu['editeur']): ?>
                                                                <p class="text-xs text-light-80 mb-3"><?php echo htmlspecialchars($jeu['editeur']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Actions -->
                                                            <div class="flex gap-2">
                                                                <form method="POST" class="flex-1">
                                                                    <input type="hidden" name="action" value="vote">
                                                                    <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                                                    <input type="hidden" name="id_categorie" value="<?php echo $categorie['id_categorie']; ?>">
                                                                    <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                                                    <button type="submit" onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> ?');" 
                                                                        class="w-full py-2 rounded-xl bg-accent/20 text-accent text-sm font-medium hover:bg-accent hover:text-dark transition-colors flex items-center justify-center gap-1 border border-white/10">
                                                                        <i class="fas fa-vote-yea"></i> Voter
                                                                    </button>
                                                                </form>
                                                                <a href="jeu-campagne.php?id=<?php echo $jeu['id_jeu']; ?>" 
                                                                   class="px-3 py-2 rounded-xl bg-white/5 text-light-80 hover:text-light hover:bg-white/10 transition-all border border-white/10" 
                                                                   title="Voir la campagne">
                                                                    <i class="fas fa-comments"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="vote-final.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                <i class="fas fa-crown"></i> Voir le Vote Final
            </a>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>