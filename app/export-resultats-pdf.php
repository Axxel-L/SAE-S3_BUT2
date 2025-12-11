<?php
/**
 * Export des résultats en PDF avec graphiques
 * Génère un rapport HTML stylé imprimable en PDF
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

$id_evenement = intval($_GET['event'] ?? 0);

if ($id_evenement <= 0) {
    header('Location: resultats.php');
    exit;
}

// Récupérer l'événement
try {
    $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ? AND statut = 'cloture'");
    $stmt->execute([$id_evenement]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        die("Événement non trouvé ou pas encore clôturé.");
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Récupérer les résultats par catégorie
$resultatsCat = [];
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
} catch (Exception $e) {}

// Récupérer les résultats du vote final
$resultatsFinal = [];
try {
    $stmt = $connexion->prepare("
        SELECT j.id_jeu, j.titre, COUNT(bf.id_bulletin_final) as nb_voix
        FROM bulletin_final bf
        LEFT JOIN jeu j ON bf.id_jeu = j.id_jeu
        WHERE bf.id_evenement = ?
        GROUP BY j.id_jeu
        ORDER BY nb_voix DESC
    ");
    $stmt->execute([$id_evenement]);
    $resultatsFinal = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Statistiques globales
$stats = [
    'total_votes_categories' => 0,
    'total_votes_final' => 0,
    'nb_categories' => count($resultatsCat),
    'nb_inscrits' => 0
];

try {
    $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_evenement = ?");
    $stmt->execute([$id_evenement]);
    $stats['total_votes_categories'] = $stmt->fetch()['total'];
    
    $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM bulletin_final WHERE id_evenement = ?");
    $stmt->execute([$id_evenement]);
    $stats['total_votes_final'] = $stmt->fetch()['total'];
    
    $stmt = $connexion->prepare("SELECT COUNT(*) as total FROM registre_electoral WHERE id_evenement = ?");
    $stmt->execute([$id_evenement]);
    $stats['nb_inscrits'] = $stmt->fetch()['total'];
} catch (Exception $e) {}

$totalVotesFinal = array_sum(array_column($resultatsFinal, 'nb_voix'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - <?php echo htmlspecialchars($event['nom']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #1a1a2e; 
            color: #fff; 
            padding: 40px;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #ffd700;
        }
        .header h1 { 
            font-size: 2.5em; 
            color: #ffd700; 
            margin-bottom: 10px;
        }
        .header .subtitle { color: #888; font-size: 1.1em; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-box {
            background: linear-gradient(135deg, #252540 0%, #1a1a2e 100%);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-box .number { 
            font-size: 2.5em; 
            font-weight: bold; 
            color: #ffd700;
        }
        .stat-box .label { color: #888; font-size: 0.9em; }
        
        .section-title {
            font-size: 1.8em;
            color: #ffd700;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .winner-card {
            background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
            color: #000;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        .winner-card h2 { font-size: 2em; margin-bottom: 10px; }
        .winner-card .votes { font-size: 1.2em; opacity: 0.8; }
        
        .podium {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            gap: 15px;
            margin-bottom: 40px;
            align-items: end;
        }
        .podium-item {
            background: #252540;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .podium-item.gold { 
            background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
            color: #000;
            padding: 30px 20px;
        }
        .podium-item.silver { background: linear-gradient(135deg, #c0c0c0 0%, #888 100%); color: #000; }
        .podium-item.bronze { background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%); color: #fff; }
        .podium-item .medal { font-size: 2em; margin-bottom: 10px; }
        .podium-item .title { font-weight: bold; margin-bottom: 5px; }
        .podium-item .percent { font-size: 0.9em; opacity: 0.8; }
        
        .category-card {
            background: #252540;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .category-card h3 {
            color: #ffd700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        
        .result-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item .rank { 
            width: 40px; 
            font-size: 1.2em;
            text-align: center;
        }
        .result-item .game-title { flex: 1; }
        .result-item .bar-container {
            width: 200px;
            height: 8px;
            background: #333;
            border-radius: 4px;
            margin: 0 15px;
            overflow: hidden;
        }
        .result-item .bar {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ff8c00);
            border-radius: 4px;
        }
        .result-item .votes {
            width: 100px;
            text-align: right;
            color: #ffd700;
            font-weight: bold;
        }
        
        .chart-container {
            background: #252540;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
        }
        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            color: #ffd700;
        }
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            height: 200px;
            gap: 10px;
        }
        .bar-chart .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 60px;
        }
        .bar-chart .bar-item .bar-fill {
            width: 40px;
            background: linear-gradient(180deg, #ffd700, #ff8c00);
            border-radius: 4px 4px 0 0;
            min-height: 5px;
        }
        .bar-chart .bar-item .bar-label {
            font-size: 0.7em;
            color: #888;
            margin-top: 5px;
            text-align: center;
            max-width: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bar-chart .bar-item .bar-value {
            font-size: 0.8em;
            color: #ffd700;
            margin-bottom: 5px;
        }
        
        /* Pie Chart */
        .pie-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        .pie-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            position: relative;
        }
        .pie-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pie-legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }
        .pie-legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #666;
        }
        
        .btn-group {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        .btn {
            background: #ffd700;
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.95em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { background: #ffaa00; }
        .btn-secondary {
            background: #333;
            color: #fff;
        }
        .btn-secondary:hover { background: #444; }
        
        @media print {
            body { background: #fff; color: #000; padding: 20px; }
            .btn-group { display: none; }
            .stat-box, .category-card, .chart-container { 
                background: #f5f5f5; 
                border: 1px solid #ddd;
            }
            .stat-box .number, .section-title, .category-card h3 { color: #d4a500; }
            .winner-card { background: #ffd700; }
            .podium-item { border: 1px solid #ddd; }
            .result-item .bar-container { background: #ddd; }
            .result-item .votes { color: #d4a500; }
            .header { border-color: #d4a500; }
            .header h1 { color: #d4a500; }
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .podium { grid-template-columns: 1fr; }
            .result-item .bar-container { width: 100px; }
        }
    </style>
</head>
<body>
    <div class="btn-group">
        <button class="btn" onclick="window.print()">
            📄 Imprimer / PDF
        </button>
        <a href="resultats.php?event=<?php echo $id_evenement; ?>" class="btn btn-secondary">
            ← Retour
        </a>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>🏆 <?php echo htmlspecialchars($event['nom']); ?></h1>
            <div class="subtitle">
                Résultats officiels — Du <?php echo date('d/m/Y', strtotime($event['date_ouverture'])); ?> 
                au <?php echo date('d/m/Y', strtotime($event['date_fermeture_vote_final'] ?? $event['date_fermeture'])); ?>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="number"><?php echo $stats['nb_inscrits']; ?></div>
                <div class="label">Participants</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $stats['total_votes_categories']; ?></div>
                <div class="label">Votes catégories</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $stats['total_votes_final']; ?></div>
                <div class="label">Votes finale</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $stats['nb_categories']; ?></div>
                <div class="label">Catégories</div>
            </div>
        </div>
        
        <!-- Jeu de l'Année -->
        <?php if (!empty($resultatsFinal)): ?>
            <h2 class="section-title">🏆 Jeu de l'Année</h2>
            
            <div class="winner-card">
                <div style="font-size: 3em; margin-bottom: 10px;">👑</div>
                <h2><?php echo htmlspecialchars($resultatsFinal[0]['titre']); ?></h2>
                <div class="votes">
                    <?php echo $resultatsFinal[0]['nb_voix']; ?> votes 
                    (<?php echo $totalVotesFinal > 0 ? round($resultatsFinal[0]['nb_voix'] / $totalVotesFinal * 100, 1) : 0; ?>%)
                </div>
            </div>
            
            <?php if (count($resultatsFinal) >= 3): ?>
                <div class="podium">
                    <div class="podium-item silver">
                        <div class="medal">🥈</div>
                        <div class="title"><?php echo htmlspecialchars($resultatsFinal[1]['titre']); ?></div>
                        <div class="percent">
                            <?php echo $resultatsFinal[1]['nb_voix']; ?> votes 
                            (<?php echo $totalVotesFinal > 0 ? round($resultatsFinal[1]['nb_voix'] / $totalVotesFinal * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                    <div class="podium-item gold">
                        <div class="medal">🥇</div>
                        <div class="title"><?php echo htmlspecialchars($resultatsFinal[0]['titre']); ?></div>
                        <div class="percent">
                            <?php echo $resultatsFinal[0]['nb_voix']; ?> votes 
                            (<?php echo $totalVotesFinal > 0 ? round($resultatsFinal[0]['nb_voix'] / $totalVotesFinal * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                    <div class="podium-item bronze">
                        <div class="medal">🥉</div>
                        <div class="title"><?php echo htmlspecialchars($resultatsFinal[2]['titre']); ?></div>
                        <div class="percent">
                            <?php echo $resultatsFinal[2]['nb_voix']; ?> votes 
                            (<?php echo $totalVotesFinal > 0 ? round($resultatsFinal[2]['nb_voix'] / $totalVotesFinal * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Graphique Vote Final -->
            <?php if (count($resultatsFinal) > 1): ?>
                <div class="chart-container">
                    <h3 class="chart-title">📊 Répartition des votes - Vote Final</h3>
                    <div class="pie-container">
                        <!-- Bar Chart -->
                        <div class="bar-chart">
                            <?php 
                            $maxVotes = max(array_column($resultatsFinal, 'nb_voix'));
                            foreach (array_slice($resultatsFinal, 0, 8) as $jeu): 
                                $height = $maxVotes > 0 ? ($jeu['nb_voix'] / $maxVotes * 150) : 5;
                            ?>
                                <div class="bar-item">
                                    <div class="bar-value"><?php echo $jeu['nb_voix']; ?></div>
                                    <div class="bar-fill" style="height: <?php echo max($height, 5); ?>px;"></div>
                                    <div class="bar-label" title="<?php echo htmlspecialchars($jeu['titre']); ?>">
                                        <?php echo htmlspecialchars(mb_substr($jeu['titre'], 0, 8)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Légende -->
                        <div class="pie-legend">
                            <?php 
                            $colors = ['#ffd700', '#c0c0c0', '#cd7f32', '#9b59b6', '#3498db', '#2ecc71', '#e74c3c', '#f39c12'];
                            foreach (array_slice($resultatsFinal, 0, 8) as $i => $jeu): 
                                $percent = $totalVotesFinal > 0 ? round($jeu['nb_voix'] / $totalVotesFinal * 100, 1) : 0;
                            ?>
                                <div class="pie-legend-item">
                                    <div class="pie-legend-color" style="background: <?php echo $colors[$i % count($colors)]; ?>;"></div>
                                    <span><?php echo htmlspecialchars($jeu['titre']); ?> — <?php echo $percent; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Classement complet vote final -->
            <?php if (count($resultatsFinal) > 3): ?>
                <div class="category-card">
                    <h3>Classement complet - Vote Final</h3>
                    <?php foreach (array_slice($resultatsFinal, 3) as $index => $jeu): 
                        $percentage = $totalVotesFinal > 0 ? round($jeu['nb_voix'] / $totalVotesFinal * 100, 1) : 0;
                    ?>
                        <div class="result-item">
                            <div class="rank">#<?php echo $index + 4; ?></div>
                            <div class="game-title"><?php echo htmlspecialchars($jeu['titre']); ?></div>
                            <div class="bar-container">
                                <div class="bar" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div class="votes"><?php echo $jeu['nb_voix']; ?> (<?php echo $percentage; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Résultats par catégorie -->
        <h2 class="section-title">📋 Résultats par Catégorie</h2>
        
        <?php foreach ($resultatsCat as $categorie): 
            $totalVotesCat = array_sum(array_column($categorie['jeux'], 'nb_voix'));
        ?>
            <div class="category-card">
                <h3><?php echo htmlspecialchars($categorie['nom']); ?> 
                    <span style="font-weight: normal; font-size: 0.8em; color: #888;">
                        (<?php echo $totalVotesCat; ?> votes)
                    </span>
                </h3>
                
                <?php if (empty($categorie['jeux'])): ?>
                    <p style="color: #666; font-style: italic;">Aucun vote enregistré</p>
                <?php else: ?>
                    <?php foreach ($categorie['jeux'] as $index => $jeu): 
                        $percentage = $totalVotesCat > 0 ? round($jeu['nb_voix'] / $totalVotesCat * 100, 1) : 0;
                        $medals = ['🥇', '🥈', '🥉'];
                        $medal = $medals[$index] ?? '#'.($index + 1);
                    ?>
                        <div class="result-item">
                            <div class="rank"><?php echo $medal; ?></div>
                            <div class="game-title"><?php echo htmlspecialchars($jeu['titre']); ?></div>
                            <div class="bar-container">
                                <div class="bar" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div class="votes"><?php echo $jeu['nb_voix']; ?> (<?php echo $percentage; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="footer">
            <p>📄 Rapport officiel généré le <?php echo date('d/m/Y à H:i'); ?></p>
            <p><strong>GameCrown</strong> — Système de vote électronique sécurisé</p>
        </div>
    </div>
</body>
</html>