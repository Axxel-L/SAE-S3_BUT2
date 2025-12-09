<?php
session_start();
require_once 'header.php';
require_once 'dbconnect.php';

// Vérifier candidat - CORRECTION : utiliser 'type' pas 'type'
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$candidat = null;
$stats = [
    'commentaires' => 0,
    'votes_categorie' => 0,
    'votes_final' => 0,
    'votes_total' => 0,
    'derniers_commentaires' => []
];

// Récupérer les infos du candidat
try {
    $stmt = $connexion->prepare("
        SELECT c.*, j.titre as titre_jeu 
        FROM candidat c 
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$candidat) {
        header('Location: candidat-profil.php');
        exit;
    }
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Récupérer les statistiques complètes
if ($candidat && !empty($candidat['id_jeu'])) {
    try {
        // Commentaires reçus
        $stmt = $connexion->prepare("
            SELECT COUNT(*) as total FROM commentaire WHERE id_jeu = ?
        ");
        $stmt->execute([$candidat['id_jeu']]);
        $stats['commentaires'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Votes pour ce jeu (par catégorie)
        $stmt = $connexion->prepare("
            SELECT COUNT(*) as total FROM bulletin_categorie WHERE id_jeu = ?
        ");
        $stmt->execute([$candidat['id_jeu']]);
        $stats['votes_categorie'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Votes finaux
        $stmt = $connexion->prepare("
            SELECT COUNT(*) as total FROM bulletin_final WHERE id_jeu = ?
        ");
        $stmt->execute([$candidat['id_jeu']]);
        $stats['votes_final'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total votes
        $stats['votes_total'] = $stats['votes_categorie'] + $stats['votes_final'];
        
        // Derniers commentaires
        $stmt = $connexion->prepare("
            SELECT c.*, u.email, date_format(c.date_commentaire, '%d/%m/%Y %H:%i') as date_format
            FROM commentaire c 
            JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
            WHERE c.id_jeu = ? 
            ORDER BY c.date_commentaire DESC 
            LIMIT 10
        ");
        $stmt->execute([$candidat['id_jeu']]);
        $stats['derniers_commentaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Erreur silencieuse
    }
}
?>

<div class="page-content">
    <div class="container-wrapper">
        <h1>📊 Statistiques - <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'Mon jeu'); ?></h1>
        <p style="color: #b0b0b0; margin-bottom: 2rem;">Suivez la performance de votre jeu</p>
        
        <?php if (!empty($error)): ?>
            <div style="background: rgba(211, 47, 47, 0.1); border-left: 4px solid #d32f2f; border-radius: 4px; padding: 1rem; margin-bottom: 2rem; color: #ef9a9a;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Résumé statistiques -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Commentaires -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 1.5rem; text-align: center;">
                <div style="color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem;">💬 Commentaires</div>
                <div style="color: #00bcd4; font-size: 2rem; font-weight: bold;"><?php echo $stats['commentaires']; ?></div>
            </div>
            
            <!-- Votes catégories -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 1.5rem; text-align: center;">
                <div style="color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem;">🗳️ Votes Catégories</div>
                <div style="color: #4caf50; font-size: 2rem; font-weight: bold;"><?php echo $stats['votes_categorie']; ?></div>
            </div>
            
            <!-- Votes finaux -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 1.5rem; text-align: center;">
                <div style="color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem;">🏆 Votes Finaux</div>
                <div style="color: #ff9800; font-size: 2rem; font-weight: bold;"><?php echo $stats['votes_final']; ?></div>
            </div>
            
            <!-- Total votes -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 1.5rem; text-align: center;">
                <div style="color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem;">📈 Total Votes</div>
                <div style="color: #e91e63; font-size: 2rem; font-weight: bold;"><?php echo $stats['votes_total']; ?></div>
            </div>
        </div>
        
        <!-- Graphique votes -->
        <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">📊 Répartition des votes</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Phase 1 -->
                <div>
                    <p style="color: #b0b0b0; font-weight: 600; margin-bottom: 1rem;">Phase 1 - Catégories</p>
                    <div style="background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.5); border-radius: 8px; padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 100%; height: 30px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: linear-gradient(90deg, #4caf50, #81c784); width: <?php echo ($stats['votes_total'] > 0) ? ($stats['votes_categorie'] / $stats['votes_total'] * 100) : 0; ?>%;"></div>
                            </div>
                            <span style="color: #4caf50; font-weight: bold; min-width: 60px; text-align: right;"><?php echo $stats['votes_categorie']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Phase 2 -->
                <div>
                    <p style="color: #b0b0b0; font-weight: 600; margin-bottom: 1rem;">Phase 2 - Final</p>
                    <div style="background: rgba(255, 152, 0, 0.2); border: 1px solid rgba(255, 152, 0, 0.5); border-radius: 8px; padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 100%; height: 30px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: linear-gradient(90deg, #ff9800, #ffb74d); width: <?php echo ($stats['votes_total'] > 0) ? ($stats['votes_final'] / $stats['votes_total'] * 100) : 0; ?>%;"></div>
                            </div>
                            <span style="color: #ff9800; font-weight: bold; min-width: 60px; text-align: right;"><?php echo $stats['votes_final']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <p style="color: #999; font-size: 0.85rem; margin-top: 1rem; font-style: italic;">ℹ️ Les votes sont anonymes - vous ne voyez que les totaux</p>
        </div>
        
        <!-- Derniers commentaires -->
        <div>
            <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">💬 Derniers commentaires reçus</h2>
            
            <?php if (empty($stats['derniers_commentaires'])): ?>
                <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; text-align: center;">
                    <p style="color: #b0b0b0;">Pas encore de commentaires reçus.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($stats['derniers_commentaires'] as $comment): ?>
                        <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                <div>
                                    <p style="color: #00bcd4; font-weight: 600; margin: 0;"><?php echo htmlspecialchars($comment['email']); ?></p>
                                    <p style="color: #b0b0b0; font-size: 0.9rem; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($comment['date_format']); ?></p>
                                </div>
                            </div>
                            <p style="color: #e0e0e0; margin: 1rem 0 0 0;"><?php echo htmlspecialchars($comment['contenu']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>