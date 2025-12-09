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

// Récupérer les événements en phase finale
try {
    $stmt = $connexion->prepare("
        SELECT * FROM evenement 
        WHERE statut = 'ouvert'
        ORDER BY date_ouverture DESC
    ");
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
        // Vérifier que l'utilisateur est inscrit à cet événement (correction du nom de table)
        $stmt = $connexion->prepare("
            SELECT id_registre FROM registre_electoral 
            WHERE id_utilisateur = ? AND id_evenement = ?
        ");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        if ($stmt->rowCount() === 0) {
            $error = "Vous n'êtes pas inscrit à cet événement.";
        } else {
            // Vérifier qu'il n'a pas déjà voté pour la finale
            $stmt = $connexion->prepare("
                SELECT id_emargement_final FROM emargement_final 
                WHERE id_utilisateur = ? AND id_evenement = ?
            ");
            $stmt->execute([$id_utilisateur, $id_evenement]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Vous avez déjà voté pour la finale !";
            } else {
                // Vérifier que le jeu est finaliste (a reçu au moins un vote dans les catégories)
                $stmt = $connexion->prepare("
                    SELECT DISTINCT id_jeu FROM bulletin_categorie 
                    WHERE id_jeu = ? AND id_evenement = ?
                    LIMIT 1
                ");
                $stmt->execute([$id_jeu, $id_evenement]);
                
                if ($stmt->rowCount() === 0) {
                    $error = "Ce jeu n'est pas finaliste.";
                } else {
                    // Enregistrer le vote final (ANONYME)
                    $connexion->beginTransaction();
                    
                    try {
                        $stmt = $connexion->prepare("
                            INSERT INTO bulletin_final (id_jeu, id_evenement)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$id_jeu, $id_evenement]);
                        
                        // Marquer l'utilisateur comme ayant voté pour la finale
                        $stmt = $connexion->prepare("
                            INSERT INTO emargement_final (id_utilisateur, id_evenement)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$id_utilisateur, $id_evenement]);
                        
                        // Log de sécurité
                        $stmt = $connexion->prepare("
                            INSERT INTO journal_securite (id_utilisateur, action, details)
                            VALUES (?, 'VOTE_FINAL', ?)
                        ");
                        $stmt->execute([$id_utilisateur, "Événement: $id_evenement"]);
                        
                        $connexion->commit();
                        $success = "Vote final enregistré avec succès !";
                        
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
</head>
<body class="font-inter bg-dark text-light">
    <?php require_once 'header.php'; ?>

    <section class="py-20 px-6 min-h-screen">
        <div class="container mx-auto max-w-6xl">
            <!-- En-tête -->
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-crown mr-3"></i>Vote Final
                </h1>
                <p class="text-xl text-light/80">Élisez le Jeu de l'Année parmi les finalistes</p>
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

            <!-- Événements -->
            <?php if (empty($events)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-info-circle text-accent text-4xl mb-4"></i>
                    <p class="text-xl text-light/80">Aucun événement actuellement.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    // Vérifier si l'utilisateur est inscrit à cet événement
                    try {
                        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $isRegistered = $stmt->rowCount() > 0;
                    } catch (Exception $e) {
                        $isRegistered = false;
                    }
                    
                    // Récupérer les jeux finalistes (ceux qui ont reçu des votes dans les catégories)
                    try {
                        $stmt = $connexion->prepare("
                            SELECT j.*, COUNT(bf.id_bulletin_final) as nb_voix
                            FROM jeu j
                            LEFT JOIN bulletin_final bf ON j.id_jeu = bf.id_jeu AND bf.id_evenement = ?
                            WHERE j.id_jeu IN (
                                SELECT DISTINCT id_jeu FROM bulletin_categorie 
                                WHERE id_evenement = ?
                            )
                            GROUP BY j.id_jeu
                            ORDER BY nb_voix DESC
                        ");
                        $stmt->execute([$event['id_evenement'], $event['id_evenement']]);
                        $finalistes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $finalistes = [];
                    }

                    // Vérifier si l'utilisateur a déjà voté pour la finale
                    try {
                        $stmt = $connexion->prepare("
                            SELECT id_emargement_final FROM emargement_final 
                            WHERE id_utilisateur = ? AND id_evenement = ?
                        ");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $dejaVoteFinal = $stmt->rowCount() > 0;
                    } catch (Exception $e) {
                        $dejaVoteFinal = false;
                    }
                    ?>
                    
                    <div class="glass-card rounded-4xl p-8 mb-8 modern-border">
                        <!-- Infos événement -->
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold font-orbitron text-light mb-2">
                                <?php echo htmlspecialchars($event['nom']); ?>
                            </h2>
                            <div class="flex flex-wrap gap-4 text-sm text-light/80">
                                <span><i class="fas fa-trophy mr-2 text-accent"></i>
                                    Phase Finale
                                </span>
                                <span class="px-3 py-1 rounded-full bg-accent/20 text-accent border border-accent/30">
                                    <?php echo ucfirst($event['statut']); ?>
                                </span>
                                <?php if ($dejaVoteFinal): ?>
                                    <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                                        <i class="fas fa-check mr-1"></i>Vous avez voté
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$isRegistered): ?>
                            <!-- Message pour s'inscrire -->
                            <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-orange-400 text-3xl mb-3"></i>
                                <p class="text-orange-400 text-lg mb-4">Vous devez vous inscrire à cet événement pour pouvoir voter.</p>
                                <a href="joueur_events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                    <i class="fas fa-user-plus"></i> S'inscrire à l'événement
                                </a>
                            </div>
                        <?php elseif (empty($finalistes)): ?>
                            <div class="text-center py-8">
                                <p class="text-light/80">Aucun finaliste pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <!-- Sélecteur de jeu (vue grille) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($finalistes as $jeu): ?>
                                    <form method="POST" class="flex">
                                        <input type="hidden" name="action" value="vote_final">
                                        <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                        <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                        
                                        <button 
                                            type="submit" 
                                            class="w-full group"
                                            <?php echo $dejaVoteFinal ? 'disabled' : ''; ?>
                                        >
                                            <div class="glass-card rounded-3xl p-6 h-full modern-border hover:border-accent/50 hover:shadow-lg hover:shadow-accent/20 transition-all duration-300 <?php echo $dejaVoteFinal ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                                <!-- Image -->
                                                <?php if ($jeu['image']): ?>
                                                    <div class="mb-4 rounded-xl overflow-hidden h-40 bg-black/50">
                                                        <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Infos jeu -->
                                                <h3 class="text-xl font-bold font-orbitron text-light mb-2">
                                                    <?php echo htmlspecialchars($jeu['titre']); ?>
                                                </h3>

                                                <?php if ($jeu['editeur']): ?>
                                                    <p class="text-sm text-light/80 mb-3">
                                                        <i class="fas fa-building mr-1 text-accent"></i>
                                                        <?php echo htmlspecialchars($jeu['editeur']); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <!-- Voix actuelles -->
                                                <div class="my-4 p-3 rounded-lg bg-accent/10 border border-accent/20">
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-accent">
                                                            <?php echo intval($jeu['nb_voix']); ?>
                                                        </div>
                                                        <div class="text-xs text-light/80">
                                                            <?php echo intval($jeu['nb_voix']) > 1 ? 'voix' : 'voix'; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Bouton vote -->
                                                <?php if (!$dejaVoteFinal): ?>
                                                    <div class="mt-4 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-crown text-accent mr-2"></i>
                                                        <span class="text-accent text-sm font-medium">Voter</span>
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

            <!-- Lien retour -->
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