<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// Fonction pour déterminer la phase de vote
function getVotePhase($event) {
    $now = time();
    $date_ouverture = strtotime($event['date_ouverture']);
    $date_fermeture = strtotime($event['date_fermeture']);
    $date_debut_vote_final = isset($event['date_debut_vote_final']) ? strtotime($event['date_debut_vote_final']) : null;
    $date_fermeture_vote_final = isset($event['date_fermeture_vote_final']) ? strtotime($event['date_fermeture_vote_final']) : null;
    
    if ($now < $date_ouverture) {
        return ['phase' => 'preparation', 'vote_categories_open' => false, 'vote_final_open' => false, 'message' => 'Les votes ouvriront le ' . date('d/m/Y à H:i', $date_ouverture)];
    }
    
    if ($now >= $date_ouverture && $now < $date_fermeture) {
        return ['phase' => 'vote_categories', 'vote_categories_open' => true, 'vote_final_open' => false, 'message' => 'Le vote par catégories est en cours.', 'vote_final_info' => $date_debut_vote_final ? 'Le vote final ouvrira le ' . date('d/m/Y à H:i', $date_debut_vote_final) : null];
    }
    
    if ($date_debut_vote_final && $now >= $date_fermeture && $now < $date_debut_vote_final) {
        $time_until = $date_debut_vote_final - $now;
        return ['phase' => 'attente_final', 'vote_categories_open' => false, 'vote_final_open' => false, 'time_until_final' => $time_until, 'days_until' => floor($time_until / 86400), 'hours_until' => floor(($time_until % 86400) / 3600), 'message' => 'Vote final bientôt', 'full_message' => 'Le vote par catégories est terminé. Le vote final ouvrira le ' . date('d/m/Y à H:i', $date_debut_vote_final)];
    }
    
    if ($date_debut_vote_final && $date_fermeture_vote_final && $now >= $date_debut_vote_final && $now < $date_fermeture_vote_final) {
        $time_left = $date_fermeture_vote_final - $now;
        return ['phase' => 'vote_final', 'vote_categories_open' => false, 'vote_final_open' => true, 'time_left' => $time_left, 'days_left' => floor($time_left / 86400), 'hours_left' => floor(($time_left % 86400) / 3600), 'message' => 'Vote final en cours !'];
    }
    
    return ['phase' => 'cloture', 'vote_categories_open' => false, 'vote_final_open' => false, 'message' => 'Cet événement est terminé.'];
}

// Récupérer les événements ouverts
try {
    $stmt = $connexion->prepare("SELECT * FROM evenement WHERE statut = 'ouvert' ORDER BY date_ouverture DESC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
    $events = [];
}

// Traitement du vote final
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_final') {
    $id_jeu = intval($_POST['id_jeu'] ?? 0);
    $id_evenement = intval($_POST['id_evenement'] ?? 0);

    try {
        $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ? AND statut = 'ouvert'");
        $stmt->execute([$id_evenement]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) throw new Exception("Cet événement n'est pas disponible.");
        
        $phase = getVotePhase($event);
        if (!$phase['vote_final_open']) throw new Exception("Le vote final n'est pas ouvert actuellement. " . $phase['message']);
        
        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        
        if ($stmt->rowCount() === 0) {
            $error = "Vous n'êtes pas inscrit à cet événement.";
        } else {
            $stmt = $connexion->prepare("SELECT id_emargement_final FROM emargement_final WHERE id_utilisateur = ? AND id_evenement = ?");
            $stmt->execute([$id_utilisateur, $id_evenement]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Vous avez déjà voté pour la finale !";
            } else {
                // Vérifier que le jeu est finaliste (a reçu des votes)
                $stmt = $connexion->prepare("SELECT DISTINCT id_jeu FROM bulletin_categorie WHERE id_jeu = ? AND id_evenement = ?");
                $stmt->execute([$id_jeu, $id_evenement]);
                
                if ($stmt->rowCount() === 0) {
                    $error = "Ce jeu n'est pas finaliste.";
                } else {
                    $connexion->beginTransaction();
                    try {
                        $stmt = $connexion->prepare("INSERT INTO bulletin_final (id_jeu, id_evenement) VALUES (?, ?)");
                        $stmt->execute([$id_jeu, $id_evenement]);
                        
                        $stmt = $connexion->prepare("INSERT INTO emargement_final (id_utilisateur, id_evenement) VALUES (?, ?)");
                        $stmt->execute([$id_utilisateur, $id_evenement]);
                        
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details) VALUES (?, 'VOTE_FINAL', ?)");
                        $stmt->execute([$id_utilisateur, "Événement: $id_evenement"]);
                        
                        $connexion->commit();
                        $success = "Vote final enregistré avec succès ! 👑";
                    } catch (Exception $e) {
                        $connexion->rollBack();
                        $error = "Erreur lors de l'enregistrement du vote : " . $e->getMessage();
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Vote Final</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <style>
        @keyframes glow { 0%, 100% { box-shadow: 0 0 20px rgba(168, 85, 247, 0.4); } 50% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.8); } }
        .glow-animation { animation: glow 2s ease-in-out infinite; }
    </style>
</head>
<body class="font-inter bg-dark text-light">
    <?php require_once 'header.php'; ?>

    <section class="py-20 px-6 min-h-screen">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                    <i class="fas fa-crown mr-3 text-purple-400"></i>Vote Final
                </h1>
                <p class="text-xl text-light/80">Phase 2 : Élisez le Jeu de l'Année parmi les finalistes</p>
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
                    <i class="fas fa-calendar-times text-purple-400 text-5xl mb-4"></i>
                    <h2 class="text-2xl font-bold font-orbitron mb-2">Aucun événement ouvert</h2>
                    <p class="text-light/80">Il n'y a pas d'événement de vote en cours actuellement.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    $phase = getVotePhase($event);
                    
                    try {
                        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $isRegistered = $stmt->rowCount() > 0;
                    } catch (Exception $e) { $isRegistered = false; }
                    
                    // Récupérer les finalistes (jeux qui ont reçu des votes par catégorie)
                    $finalistes = [];
                    try {
                        $stmt = $connexion->prepare("
                            SELECT j.*, COUNT(bf.id_bulletin_final) as nb_voix_final,
                                   (SELECT GROUP_CONCAT(DISTINCT c.nom SEPARATOR ', ') 
                                    FROM bulletin_categorie bc2 
                                    JOIN categorie c ON bc2.id_categorie = c.id_categorie 
                                    WHERE bc2.id_jeu = j.id_jeu AND bc2.id_evenement = ?) as categories_nominées
                            FROM jeu j
                            LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu AND bf.id_evenement = ?
                            WHERE j.id_jeu IN (SELECT DISTINCT id_jeu FROM bulletin_categorie WHERE id_evenement = ?)
                            GROUP BY j.id_jeu
                            ORDER BY nb_voix_final DESC, j.titre ASC
                        ");
                        $stmt->execute([$event['id_evenement'], $event['id_evenement'], $event['id_evenement']]);
                        $finalistes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) { $finalistes = []; }

                    try {
                        $stmt = $connexion->prepare("SELECT id_emargement_final FROM emargement_final WHERE id_utilisateur = ? AND id_evenement = ?");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $dejaVoteFinal = $stmt->rowCount() > 0;
                    } catch (Exception $e) { $dejaVoteFinal = false; }
                ?>
                    <div class="glass-card rounded-4xl p-8 mb-8 modern-border <?php echo $phase['vote_final_open'] ? 'glow-animation' : ''; ?>">
                        <div class="mb-8">
                            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                                <div>
                                    <h2 class="text-3xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h2>
                                    <?php if ($event['description']): ?>
                                        <p class="text-light/60"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php
                                $badge_colors = [
                                    'preparation' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                                    'vote_categories' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                    'attente_final' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                    'vote_final' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                                    'cloture' => 'bg-red-500/20 text-red-400 border-red-500/30'
                                ];
                                $badge_icons = ['preparation' => 'fa-hourglass-start', 'vote_categories' => 'fa-vote-yea', 'attente_final' => 'fa-clock', 'vote_final' => 'fa-crown', 'cloture' => 'fa-times-circle'];
                                ?>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="px-4 py-2 rounded-full text-sm font-medium border <?php echo $badge_colors[$phase['phase']] ?? $badge_colors['preparation']; ?>">
                                        <i class="fas <?php echo $badge_icons[$phase['phase']] ?? 'fa-info'; ?> mr-2"></i>
                                        <?php echo $phase['message']; ?>
                                    </span>
                                    <?php if ($dejaVoteFinal): ?>
                                        <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-sm">
                                            <i class="fas fa-check mr-1"></i>Vous avez voté
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Timeline des phases -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="p-4 rounded-xl <?php echo $phase['vote_categories_open'] ? 'bg-green-500/20 border-green-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-layer-group <?php echo $phase['vote_categories_open'] ? 'text-green-400' : 'text-light/60'; ?>"></i>
                                        <span class="font-bold <?php echo $phase['vote_categories_open'] ? 'text-green-400' : 'text-light/80'; ?>">Phase 1 : Vote Catégories</span>
                                        <?php if (!$phase['vote_categories_open'] && $phase['phase'] !== 'preparation'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-500/50 text-light/80">TERMINÉ</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-light/60">
                                        Du <?php echo date('d/m/Y H:i', strtotime($event['date_ouverture'])); ?>
                                        au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                    </p>
                                </div>
                                
                                <div class="p-4 rounded-xl <?php echo $phase['vote_final_open'] ? 'bg-purple-500/20 border-purple-500/50' : 'bg-white/5 border-white/10'; ?> border">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-crown <?php echo $phase['vote_final_open'] ? 'text-purple-400' : 'text-light/60'; ?>"></i>
                                        <span class="font-bold <?php echo $phase['vote_final_open'] ? 'text-purple-400' : 'text-light/80'; ?>">Phase 2 : Vote Final</span>
                                        <?php if ($phase['vote_final_open']): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-purple-500 text-white font-bold animate-pulse">EN COURS</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($event['date_debut_vote_final']) && !empty($event['date_fermeture_vote_final'])): ?>
                                        <p class="text-sm text-light/60">
                                            Du <?php echo date('d/m/Y H:i', strtotime($event['date_debut_vote_final'])); ?>
                                            au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture_vote_final'])); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-sm text-light/40 italic">Dates non définies</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isRegistered): ?>
                            <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-orange-400 text-3xl mb-3"></i>
                                <p class="text-orange-400 text-lg mb-4">Vous devez vous inscrire à cet événement pour pouvoir voter.</p>
                                <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                    <i class="fas fa-user-plus"></i> S'inscrire à l'événement
                                </a>
                            </div>
                        <?php elseif (!$phase['vote_final_open']): ?>
                            <div class="p-8 rounded-2xl bg-purple-500/10 border border-purple-500/30 text-center">
                                <?php if ($phase['phase'] === 'vote_categories'): ?>
                                    <i class="fas fa-hourglass-half text-purple-400 text-4xl mb-4"></i>
                                    <h3 class="text-2xl font-bold text-purple-400 mb-3">Vote final pas encore disponible</h3>
                                    <p class="text-light/80 mb-4">Le vote par catégories est toujours en cours.</p>
                                    <?php if (!empty($phase['vote_final_info'])): ?>
                                        <p class="text-purple-300 mb-4"><?php echo $phase['vote_final_info']; ?></p>
                                    <?php endif; ?>
                                    <a href="vote.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-green-500 text-dark font-bold hover:bg-green-400 transition-colors">
                                        <i class="fas fa-vote-yea"></i> Voter par catégorie
                                    </a>
                                <?php elseif ($phase['phase'] === 'attente_final'): ?>
                                    <i class="fas fa-clock text-purple-400 text-4xl mb-4 animate-pulse"></i>
                                    <h3 class="text-2xl font-bold text-purple-400 mb-3">Bientôt le vote final !</h3>
                                    <div class="flex justify-center gap-4 my-6">
                                        <div class="p-4 rounded-xl bg-purple-500/20 text-center">
                                            <div class="text-3xl font-bold text-purple-400"><?php echo $phase['days_until']; ?></div>
                                            <div class="text-sm text-light/60">jours</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-purple-500/20 text-center">
                                            <div class="text-3xl font-bold text-purple-400"><?php echo $phase['hours_until']; ?></div>
                                            <div class="text-sm text-light/60">heures</div>
                                        </div>
                                    </div>
                                    <p class="text-light/80"><?php echo $phase['full_message']; ?></p>
                                <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-400 text-4xl mb-4"></i>
                                    <p class="text-blue-400 text-lg"><?php echo $phase['message']; ?></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif (empty($finalistes)): ?>
                            <div class="text-center py-8">
                                <p class="text-light/80">Aucun finaliste pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <h3 class="text-xl font-bold font-orbitron text-purple-400 mb-6">
                                <i class="fas fa-trophy mr-2"></i>Sélectionnez le Jeu de l'Année
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($finalistes as $jeu): ?>
                                    <form method="POST" class="flex">
                                        <input type="hidden" name="action" value="vote_final">
                                        <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                        <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                        
                                        <button type="submit" class="w-full group" <?php echo $dejaVoteFinal ? 'disabled' : ''; ?> onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> comme Jeu de l\'Année ?');">
                                            <div class="glass-card rounded-3xl p-6 h-full modern-border hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/20 transition-all duration-300 <?php echo $dejaVoteFinal ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                                <?php if ($jeu['image']): ?>
                                                    <div class="mb-4 rounded-xl overflow-hidden h-40 bg-black/50">
                                                        <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mb-4 rounded-xl h-40 bg-white/5 flex items-center justify-center">
                                                        <i class="fas fa-gamepad text-4xl text-light/30"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <h3 class="text-xl font-bold font-orbitron text-light mb-2"><?php echo htmlspecialchars($jeu['titre']); ?></h3>

                                                <?php if ($jeu['editeur']): ?>
                                                    <p class="text-sm text-light/80 mb-2">
                                                        <i class="fas fa-building mr-1 text-accent"></i>
                                                        <?php echo htmlspecialchars($jeu['editeur']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($jeu['categories_nominées'])): ?>
                                                    <p class="text-xs text-purple-300 mb-3">
                                                        <i class="fas fa-tags mr-1"></i>
                                                        <?php echo htmlspecialchars($jeu['categories_nominées']); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="my-4 p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-purple-400"><?php echo intval($jeu['nb_voix_final']); ?></div>
                                                        <div class="text-xs text-light/80">voix</div>
                                                    </div>
                                                </div>

                                                <?php if (!$dejaVoteFinal): ?>
                                                    <div class="mt-4 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-crown text-purple-400 mr-2"></i>
                                                        <span class="text-purple-400 text-sm font-medium">Voter</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mt-4 text-center text-green-400 text-sm">
                                                        <i class="fas fa-check mr-1"></i>Vote enregistré
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="text-center mt-12">
                <a href="vote.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    Retour au vote par catégorie
                </a>
            </div>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>