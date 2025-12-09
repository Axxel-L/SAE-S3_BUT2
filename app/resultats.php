<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';
require_once 'header.php';

$id_evenement = intval($_GET['event'] ?? 0);
$error = '';

// Récupérer l'événement
$event = null;
if ($id_evenement > 0) {
    try {
        $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ? AND statut = 'cloture'");
        $stmt->execute([$id_evenement]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            $error = "Événement non trouvé ou pas encore clôturé.";
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les événements FERMÉS uniquement
$events = [];
try {
    $stmt = $connexion->query("SELECT * FROM evenement WHERE statut = 'cloture' ORDER BY date_ouverture DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignorer
}

// Résultats par catégorie
$resultatsCat = [];
if ($event) {
    try {
        $stmt = $connexion->prepare("
            SELECT c.id_categorie, c.nom as categorie, j.id_jeu, j.titre, j.image, COUNT(bc.id_bulletin) as nb_voix
            FROM categorie c
            LEFT JOIN bulletin_categorie bc ON c.id_categorie = bc.id_categorie AND bc.id_evenement = ?
            LEFT JOIN jeu j ON bc.id_jeu = j.id_jeu
            WHERE c.id_evenement = ?
            GROUP BY c.id_categorie, j.id_jeu
            ORDER BY c.nom, nb_voix DESC
        ");
        $stmt->execute([$id_evenement, $id_evenement]);
        $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
        // Ignorer
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
        // Ignorer
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - GameCrown</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">

<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        
        <!-- En-tête -->
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-trophy mr-3"></i>Résultats
            </h1>
            <p class="text-xl text-light-80">Découvrez les gagnants des événements clôturés</p>
        </div>

        <!-- Sélecteur d'événement -->
        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border text-center mb-12">
                <i class="fas fa-info-circle text-4xl text-accent mb-4"></i>
                <p class="text-xl text-light-80">Aucun événement clôturé pour le moment.</p>
                <p class="text-sm text-light-80 mt-2">Les résultats seront disponibles une fois les événements terminés.</p>
            </div>
        <?php else: ?>
            <div class="glass-card rounded-3xl p-6 modern-border mb-12">
                <h3 class="text-lg font-bold mb-4">Sélectionner un événement clôturé</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($events as $evt): ?>
                        <a 
                            href="?event=<?php echo $evt['id_evenement']; ?>"
                            class="p-4 rounded-2xl border <?php echo $id_evenement === $evt['id_evenement'] ? 'bg-accent/20 border-accent' : 'bg-white/5 border-white/10 hover:border-accent/50'; ?> transition-colors"
                        >
                            <div class="font-bold text-light"><?php echo htmlspecialchars($evt['nom']); ?></div>
                            <div class="text-sm text-light-80"><?php echo date('d/m/Y', strtotime($evt['date_ouverture'])); ?></div>
                            <span class="inline-block mt-2 px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-400 border border-red-500/30">
                                <i class="fas fa-check-circle mr-1"></i>Clôturé
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Résultats (uniquement si événement sélectionné et fermé) -->
        <?php if ($event && $id_evenement): ?>
            <!-- Résultats par catégorie -->
            <div class="mb-16">
                <h2 class="text-4xl font-bold font-orbitron mb-8 flex items-center gap-3">
                    <i class="fas fa-tags text-accent"></i> Résultats par Catégorie
                </h2>
                
                <?php if (empty($resultatsCat)): ?>
                    <div class="glass-card rounded-3xl p-12 modern-border text-center">
                        <p class="text-light-80 text-lg">Aucun résultat disponible.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <?php foreach ($resultatsCat as $categorie): ?>
                            <div class="glass-card rounded-3xl p-8 modern-border">
                                <h3 class="text-2xl font-bold font-orbitron text-light mb-6 pb-4 border-b border-white/10">
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </h3>
                                
                                <?php if (empty($categorie['jeux'])): ?>
                                    <p class="text-light-80 italic">Aucun vote.</p>
                                <?php else: ?>
                                    <div class="space-y-5">
                                        <?php 
                                        $totalVotes = array_sum(array_column($categorie['jeux'], 'nb_voix'));
                                        foreach ($categorie['jeux'] as $index => $jeu): 
                                            $percentage = $totalVotes > 0 ? ($jeu['nb_voix'] / $totalVotes * 100) : 0;
                                            $medals = ['🥇', '🥈', '🥉'];
                                            $medal = $medals[$index] ?? ('# ' . ($index + 1));
                                        ?>
                                            <div class="flex items-center gap-3">
                                                <div class="text-2xl w-8 text-center"><?php echo $medal; ?></div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-light font-medium truncate"><?php echo htmlspecialchars($jeu['titre']); ?></span>
                                                        <span class="text-accent font-bold ml-2"><?php echo $jeu['nb_voix']; ?></span>
                                                    </div>
                                                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                                        <div class="h-full bg-gradient-to-r from-accent to-accent-dark" style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <div class="text-xs text-light-80 mt-1"><?php echo round($percentage, 1); ?>%</div>
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
                <h2 class="text-4xl font-bold font-orbitron mb-8 flex items-center gap-3">
                    <i class="fas fa-crown text-yellow-400"></i> 🏆 Jeu de l'Année
                </h2>
                
                <?php if (empty($resultatsFinal)): ?>
                    <div class="glass-card rounded-3xl p-12 modern-border text-center">
                        <p class="text-light-80 text-lg">Aucun vote final.</p>
                    </div>
                <?php else: ?>
                    <!-- Podium (top 3) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                        <?php 
                        $totalVotesFinal = array_sum(array_column($resultatsFinal, 'nb_voix'));
                        $topCount = min(3, count($resultatsFinal));
                        for ($i = 0; $i < $topCount; $i++):
                            $jeu = $resultatsFinal[$i];
                            $percentage = $totalVotesFinal > 0 ? ($jeu['nb_voix'] / $totalVotesFinal * 100) : 0;
                            $positions = [
                                ['medal' => '🥇', 'height' => 'h-48', 'bg' => 'from-yellow-500/20 to-yellow-500/5'],
                                ['medal' => '🥈', 'height' => 'h-40', 'bg' => 'from-gray-400/20 to-gray-400/5'],
                                ['medal' => '🥉', 'height' => 'h-32', 'bg' => 'from-orange-500/20 to-orange-500/5']
                            ];
                            $pos = $positions[$i];
                        ?>
                            <div class="flex flex-col items-center">
                                <div class="glass-card rounded-2xl p-4 modern-border w-full mb-4 text-center">
                                    <?php if ($jeu['image']): ?>
                                        <div class="rounded-lg overflow-hidden h-24 bg-black/50 mb-3">
                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="<?php echo htmlspecialchars($jeu['titre']); ?>" class="w-full h-full object-cover">
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="font-bold text-light mb-2"><?php echo htmlspecialchars($jeu['titre']); ?></h4>
                                    <div class="text-2xl font-bold text-accent"><?php echo $jeu['nb_voix']; ?></div>
                                    <div class="text-xs text-light-80">votes</div>
                                </div>
                                <div class="w-20 <?php echo $pos['height']; ?> bg-gradient-to-b <?php echo $pos['bg']; ?> border border-white/10 rounded-t-lg flex items-end justify-center pb-2">
                                    <div class="text-4xl"><?php echo $pos['medal']; ?></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Classement complet -->
                    <?php if (count($resultatsFinal) > 3): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border">
                            <h3 class="text-lg font-bold mb-6">Classement complet</h3>
                            <div class="space-y-3">
                                <?php for ($i = 3; $i < count($resultatsFinal); $i++):
                                    $jeu = $resultatsFinal[$i];
                                    $percentage = $totalVotesFinal > 0 ? ($jeu['nb_voix'] / $totalVotesFinal * 100) : 0;
                                ?>
                                    <div class="flex items-center gap-4">
                                        <div class="text-lg font-bold text-accent w-8"><?php echo '#' . ($i + 1); ?></div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-light"><?php echo htmlspecialchars($jeu['titre']); ?></span>
                                                <span class="text-accent font-bold"><?php echo $jeu['nb_voix']; ?></span>
                                            </div>
                                            <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-accent to-accent-dark" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'footer.php'; ?>
</body>
</html>