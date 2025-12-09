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

// Récupérer les événements ouverts au vote (phase 1)
try {
    $stmt = $connexion->prepare("
        SELECT * FROM evenement 
        WHERE statut = 'ouvert' 
        AND NOW() BETWEEN date_ouverture AND date_fermeture 
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
        // Vérifier que l'utilisateur est inscrit à cet événement
        $stmt = $connexion->prepare("
            SELECT id_registre FROM registre_electoral 
            WHERE id_utilisateur = ? AND id_evenement = ?
        ");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        
        if ($stmt->rowCount() === 0) {
            $error = "Vous n'êtes pas inscrit à cet événement.";
        } else {
            // Vérifier qu'il n'a pas déjà voté pour cette catégorie
            $stmt = $connexion->prepare("
                SELECT id_emargement FROM emargement_categorie 
                WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?
            ");
            $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Vous avez déjà voté pour cette catégorie !";
            } else {
                // Vérifier que le jeu est bien nominé dans cette catégorie
                // (soit via la table nomination, soit via une candidature approuvée)
                $stmt = $connexion->prepare("
                    SELECT id_nomination FROM nomination 
                    WHERE id_jeu = ? AND id_categorie = ? AND id_evenement = ?
                ");
                $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
                
                if ($stmt->rowCount() === 0) {
                    $error = "Ce jeu n'est pas nominé dans cette catégorie.";
                } else {
                    // Enregistrer le vote ANONYME - sans id_utilisateur
                    $connexion->beginTransaction();
                    try {
                        // Insérer le vote ANONYME
                        $stmt = $connexion->prepare("
                            INSERT INTO bulletin_categorie (id_jeu, id_categorie, id_evenement) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$id_jeu, $id_categorie, $id_evenement]);
                        
                        // Marquer l'utilisateur comme ayant voté pour cette catégorie
                        $stmt = $connexion->prepare("
                            INSERT INTO emargement_categorie (id_utilisateur, id_categorie, id_evenement) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$id_utilisateur, $id_categorie, $id_evenement]);
                        
                        // Log de sécurité (sans révéler le vote)
                        $stmt = $connexion->prepare("
                            INSERT INTO journal_securite (id_utilisateur, action, details) 
                            VALUES (?, 'VOTE_CATEGORIE', ?)
                        ");
                        $stmt->execute([
                            $id_utilisateur, 
                            "Catégorie $id_categorie, événement $id_evenement"
                        ]);
                        
                        $connexion->commit();
                        $success = "Vote enregistré avec succès ! 🗳️";
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
            <!-- En-tête -->
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                    <i class="fas fa-vote-yea mr-3"></i>Vote par Catégorie
                </h1>
                <p class="text-xl text-light/80">Votez pour vos jeux préférés dans chaque catégorie</p>
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
                <div class="glass-card rounded-3xl p-12 modern-border text-center">
                    <i class="fas fa-info-circle text-accent text-4xl mb-4"></i>
                    <p class="text-xl text-light/80 mb-4">Aucun événement ouvert au vote actuellement.</p>
                    <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                        <i class="fas fa-calendar"></i> Voir les événements disponibles
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    // Vérifier si l'utilisateur est inscrit
                    try {
                        $stmt = $connexion->prepare("
                            SELECT id_registre FROM registre_electoral 
                            WHERE id_utilisateur = ? AND id_evenement = ?
                        ");
                        $stmt->execute([$id_utilisateur, $event['id_evenement']]);
                        $isRegistered = $stmt->rowCount() > 0;
                    } catch (Exception $e) {
                        $isRegistered = false;
                    }
                ?>
                    <div class="glass-card rounded-3xl p-8 modern-border mb-8">
                        <!-- Header événement -->
                        <div class="mb-8 pb-6 border-b border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-3xl font-bold font-orbitron text-light mb-2">
                                        <?php echo htmlspecialchars($event['nom']); ?>
                                    </h2>
                                    <?php if ($event['description']): ?>
                                        <p class="text-light/60"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-light/80">
                                        <i class="fas fa-clock mr-2 text-accent"></i>
                                        Jusqu'au <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                                    </span>
                                    <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                                        <i class="fas fa-check-circle mr-1"></i>Ouvert
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isRegistered): ?>
                            <!-- Message pour s'inscrire -->
                            <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-center">
                                <i class="fas fa-exclamation-triangle text-orange-400 text-3xl mb-3"></i>
                                <p class="text-orange-400 text-lg mb-4">Vous devez vous inscrire à cet événement pour pouvoir voter.</p>
                                <a href="joueur-events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                    <i class="fas fa-user-plus"></i> S'inscrire à l'événement
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Catégories et jeux -->
                            <div class="space-y-8">
                                <?php 
                                // Récupérer les catégories qui ont des jeux nominés
                                try {
                                    $stmt = $connexion->prepare("
                                        SELECT DISTINCT c.* 
                                        FROM categorie c
                                        JOIN nomination n ON c.id_categorie = n.id_categorie
                                        WHERE c.id_evenement = ? 
                                        ORDER BY c.nom ASC
                                    ");
                                    $stmt->execute([$event['id_evenement']]);
                                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) {
                                    $categories = [];
                                }

                                if (empty($categories)):
                                ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-info-circle text-accent text-3xl mb-3"></i>
                                        <p class="text-light/80">Aucune catégorie avec des jeux nominés pour le moment.</p>
                                        <p class="text-light/60 text-sm mt-2">Les candidatures sont peut-être encore en cours de validation.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($categories as $categorie):
                                        // Vérifier si l'utilisateur a déjà voté pour cette catégorie
                                        try {
                                            $stmt = $connexion->prepare("
                                                SELECT id_emargement FROM emargement_categorie 
                                                WHERE id_utilisateur = ? AND id_categorie = ? AND id_evenement = ?
                                            ");
                                            $stmt->execute([$id_utilisateur, $categorie['id_categorie'], $event['id_evenement']]);
                                            $dejaVote = $stmt->rowCount() > 0;
                                        } catch (Exception $e) {
                                            $dejaVote = false;
                                        }

                                        // Récupérer les jeux nominés (via la table nomination)
                                        try {
                                            $stmt = $connexion->prepare("
                                                SELECT j.*, n.id_nomination 
                                                FROM jeu j 
                                                JOIN nomination n ON j.id_jeu = n.id_jeu 
                                                WHERE n.id_categorie = ? AND n.id_evenement = ? 
                                                ORDER BY j.titre ASC
                                            ");
                                            $stmt->execute([$categorie['id_categorie'], $event['id_evenement']]);
                                            $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            $jeux = [];
                                        }
                                    ?>
                                        <div class="border border-accent/20 rounded-3xl p-6 <?php echo $dejaVote ? 'bg-green-500/5' : ''; ?>">
                                            <div class="flex items-start justify-between mb-4">
                                                <div>
                                                    <h3 class="text-2xl font-bold font-orbitron text-light mb-1">
                                                        <?php echo htmlspecialchars($categorie['nom']); ?>
                                                    </h3>
                                                    <?php if ($categorie['description']): ?>
                                                        <p class="text-light/80 text-sm"><?php echo htmlspecialchars($categorie['description']); ?></p>
                                                    <?php endif; ?>
                                                    <p class="text-xs text-light/50 mt-1">
                                                        <i class="fas fa-gamepad mr-1"></i><?php echo count($jeux); ?> jeu(x) en compétition
                                                    </p>
                                                </div>
                                                <?php if ($dejaVote): ?>
                                                    <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-xs font-medium flex items-center gap-1">
                                                        <i class="fas fa-check"></i> Voté
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($dejaVote): ?>
                                                <p class="text-light/80 italic">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Vous avez déjà voté pour cette catégorie. Votre vote est anonyme et ne peut pas être modifié.
                                                </p>
                                            <?php elseif (empty($jeux)): ?>
                                                <p class="text-light/60 italic">Aucun jeu nominé dans cette catégorie.</p>
                                            <?php else: ?>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                    <?php foreach ($jeux as $jeu): ?>
                                                        <form method="POST" class="flex">
                                                            <input type="hidden" name="action" value="vote">
                                                            <input type="hidden" name="id_jeu" value="<?php echo $jeu['id_jeu']; ?>">
                                                            <input type="hidden" name="id_categorie" value="<?php echo $categorie['id_categorie']; ?>">
                                                            <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                                            
                                                            <button type="submit" class="w-full group" onclick="return confirm('Voter pour <?php echo htmlspecialchars($jeu['titre'], ENT_QUOTES); ?> dans cette catégorie ?');">
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
                                                                    
                                                                    <h4 class="font-bold text-light text-center mb-1">
                                                                        <?php echo htmlspecialchars($jeu['titre']); ?>
                                                                    </h4>
                                                                    
                                                                    <?php if ($jeu['editeur']): ?>
                                                                        <p class="text-xs text-light/60 text-center">
                                                                            <?php echo htmlspecialchars($jeu['editeur']); ?>
                                                                        </p>
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

            <!-- Lien vers vote final -->
            <div class="text-center mt-12">
                <p class="text-light/60 mb-4">Vous avez terminé de voter par catégorie ?</p>
                <a href="vote-final.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                    <i class="fas fa-crown mr-2"></i>
                    Accéder au vote final (Jeu de l'Année)
                </a>
            </div>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>