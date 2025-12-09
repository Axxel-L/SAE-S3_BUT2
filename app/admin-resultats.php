<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id_evenement = intval($_GET['event'] ?? 0);
$error = '';

// Récupérer l'événement
try {
    $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
    $stmt->execute([$id_evenement]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        $error = "Événement non trouvé.";
    }
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
    $event = [];
}

// Résultats par catégorie
$resultatsCat = [];
if ($event) {
    try {
        $stmt = $connexion->prepare("
            SELECT c.id_categorie, c.nom as categorie, j.id_jeu, j.titre, COUNT(bc.id_bulletin) as nb_voix
            FROM categorie c
            LEFT JOIN bulletin_categorie bc ON c.id_categorie = bc.id_categorie AND bc.id_evenement = ?
            LEFT JOIN jeu j ON bc.id_jeu = j.id_jeu
            WHERE c.id_evenement = ?
            GROUP BY c.id_categorie, j.id_jeu
            ORDER BY c.nom, nb_voix DESC
        ");
        $stmt->execute([$id_evenement, $id_evenement]);
        $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser par catégorie
        foreach ($allResults as $result) {
            if (!isset($resultatsCat[$result['id_categorie']])) {
                $resultatsCat[$result['id_categorie']] = [
                    'nom' => $result['categorie'],
                    'jeux' => []
                ];
            }
            if ($result['id_jeu'] !== null) {
                $resultatsCat[$result['id_categorie']]['jeux'][] = $result;
            }
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la récupération des résultats : " . $e->getMessage();
    }
}

// Résultats finale
$resultatsFinal = [];
if ($event) {
    try {
        $stmt = $connexion->prepare("
            SELECT j.id_jeu, j.titre, j.image, COUNT(bf.id_bulletin_final) as nb_voix
            FROM bulletin_final bf
            LEFT JOIN jeu j ON bf.id_jeu = j.id_jeu
            WHERE bf.id_evenement = ?
            GROUP BY j.id_jeu
            ORDER BY nb_voix DESC
        ");
        $stmt->execute([$id_evenement]);
        $resultatsFinal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Erreur lors de la récupération des résultats finaux : " . $e->getMessage();
    }
}

// Statistiques générales
$stats = [
    'total_votes_cat' => 0,
    'total_votes_final' => 0,
    'nb_votants' => 0
];

if ($event) {
    try {
        $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $stats['total_votes_cat'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
        
        $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM bulletin_final WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $stats['total_votes_final'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
        
        $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM registre_electoral WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $stats['nb_votants'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    } catch (Exception $e) {
        // Ignorer
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Résultats</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">
    <?php require_once 'header.php'; ?>

    <section class="py-20 px-6">
        <div class="container mx-auto max-w-7xl">
            <!-- En-tête -->
            <div class="mb-12 flex items-center justify-between">
                <div>
                    <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                        <i class="fas fa-chart-bar text-accent mr-3"></i>Résultats
                    </h1>
                    <?php if ($event): ?>
                        <p class="text-xl text-light80"><?php echo htmlspecialchars($event['nom']); ?></p>
                    <?php endif; ?>
                </div>
                <a href="admin-events.php" class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                    <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($event): ?>
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="glass-card rounded-3xl p-6 modern-border text-center">
                        <div class="text-3xl font-bold text-accent mb-2"><?php echo $stats['nb_votants']; ?></div>
                        <div class="text-light80 text-sm"><i class="fas fa-users mr-1"></i>Votants inscrits</div>
                    </div>
                    <div class="glass-card rounded-3xl p-6 modern-border text-center">
                        <div class="text-3xl font-bold text-accent mb-2"><?php echo $stats['total_votes_cat']; ?></div>
                        <div class="text-light80 text-sm"><i class="fas fa-vote-yea mr-1"></i>Votes (catégories)</div>
                    </div>
                    <div class="glass-card rounded-3xl p-6 modern-border text-center">
                        <div class="text-3xl font-bold text-accent mb-2"><?php echo $stats['total_votes_final']; ?></div>
                        <div class="text-light80 text-sm"><i class="fas fa-crown mr-1"></i>Votes (finale)</div>
                    </div>
                </div>

                <!-- Résultats par catégorie -->
                <div class="mb-12">
                    <h2 class="text-3xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-tags text-accent"></i> Résultats par Catégorie
                    </h2>
                    
                    <?php if (empty($resultatsCat)): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border text-center">
                            <p class="text-light80">Aucun vote pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-8">
                            <?php foreach ($resultatsCat as $categorie): ?>
                                <div class="glass-card rounded-3xl p-6 modern-border">
                                    <h3 class="text-2xl font-bold text-light mb-6">
                                        <?php echo htmlspecialchars($categorie['nom']); ?>
                                    </h3>
                                    
                                    <?php if (empty($categorie['jeux'])): ?>
                                        <p class="text-light80 italic">Aucun vote pour cette catégorie.</p>
                                    <?php else: ?>
                                        <div class="space-y-4">
                                            <?php 
                                            $totalVotes = array_sum(array_column($categorie['jeux'], 'nb_voix'));
                                            foreach ($categorie['jeux'] as $index => $jeu): 
                                                $percentage = $totalVotes > 0 ? ($jeu['nb_voix'] / $totalVotes * 100) : 0;
                                            ?>
                                                <div class="flex items-center gap-4">
                                                    <div class="text-center w-8">
                                                        <span class="text-lg font-bold text-accent"><?php echo $index + 1; ?></span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-light font-medium"><?php echo htmlspecialchars($jeu['titre']); ?></span>
                                                            <span class="text-accent font-bold"><?php echo $jeu['nb_voix']; ?> voix</span>
                                                        </div>
                                                        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                                            <div class="h-full bg-gradient-to-r from-accent to-accent-dark" style="width: <?php echo $percentage; ?>%"></div>
                                                        </div>
                                                        <div class="text-xs text-light80 mt-1"><?php echo round($percentage, 1); ?>%</div>
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

                <!-- Résultats finale -->
                <div>
                    <h2 class="text-3xl font-bold font-orbitron mb-6 flex items-center gap-2">
                        <i class="fas fa-crown text-yellow-400"></i> Résultats Finals
                    </h2>
                    
                    <?php if (empty($resultatsFinal)): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border text-center">
                            <p class="text-light80">Aucun vote final pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php 
                            $totalVotesFinal = array_sum(array_column($resultatsFinal, 'nb_voix'));
                            foreach ($resultatsFinal as $index => $jeu): 
                                $percentage = $totalVotesFinal > 0 ? ($jeu['nb_voix'] / $totalVotesFinal * 100) : 0;
                            ?>
                                <div class="glass-card rounded-3xl p-6 modern-border">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-2xl font-bold text-accent">
                                            <?php 
                                            if ($index === 0) echo '🥇';
                                            elseif ($index === 1) echo '🥈';
                                            elseif ($index === 2) echo '🥉';
                                            else echo '#' . ($index + 1);
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($jeu['image']): ?>
                                        <div class="mb-4 rounded-xl overflow-hidden h-32 bg-black/50">
                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="text-lg font-bold text-light mb-3"><?php echo htmlspecialchars($jeu['titre']); ?></h3>
                                    
                                    <div class="mb-3 p-3 rounded-lg bg-accent/10 border border-accent/20">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-accent"><?php echo $jeu['nb_voix']; ?></div>
                                            <div class="text-xs text-light80">votes</div>
                                        </div>
                                    </div>
                                    
                                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-accent to-accent-dark" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="text-xs text-light80 mt-2 text-center"><?php echo round($percentage, 1); ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>