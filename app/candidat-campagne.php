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
$success = '';
$candidat = null;
$commentaires = [];

// Vérifier que le candidat a un profil
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

// Traitement ajout commentaire (si le formulaire est soumis)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_comment') {
        $contenu = trim($_POST['contenu'] ?? '');
        
        if (!empty($contenu) && !empty($candidat['id_jeu'])) {
            try {
                $stmt = $connexion->prepare("
                    INSERT INTO commentaire (id_utilisateur, id_jeu, contenu, date_commentaire) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$id_utilisateur, $candidat['id_jeu'], $contenu]);
                $success = "Commentaire publié ! 📝";
                
                // Log audit
                $stmt = $connexion->prepare("
                    INSERT INTO journal_securite (id_utilisateur, action, details) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$id_utilisateur, 'CAMPAGNE_COMMENT_ADD', "Commentaire sur jeu: " . $candidat['id_jeu']]);
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez écrire un commentaire !";
        }
    }
}

// Récupérer les commentaires reçus sur ce jeu
try {
    $stmt = $connexion->prepare("
        SELECT c.*, u.email, date_format(c.date_commentaire, '%d/%m/%Y %H:%i') as date_format
        FROM commentaire c 
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
        WHERE c.id_jeu = ?
        ORDER BY c.date_commentaire DESC 
        LIMIT 50
    ");
    $stmt->execute([$candidat['id_jeu']]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $commentaires = [];
}
?>

<div class="page-content">
    <div class="container-wrapper">
        <h1>📢 Campagne - <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'Mon jeu'); ?></h1>
        <p style="color: #b0b0b0; margin-bottom: 2rem;">Partagez et interagissez avec les électeurs</p>
        
        <?php if (!empty($error)): ?>
            <div style="background: rgba(211, 47, 47, 0.1); border-left: 4px solid #d32f2f; border-radius: 4px; padding: 1rem; margin-bottom: 2rem; color: #ef9a9a;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div style="background: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; border-radius: 4px; padding: 1rem; margin-bottom: 2rem; color: #a5d6a7;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Publier un message -->
        <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
            <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">✍️ Publier un message</h2>
            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Votre message</label>
                    <textarea name="contenu" required style="width: 100%; min-height: 120px; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0; font-family: Arial, sans-serif;" placeholder="Dites-moi ce que vous pensez de mon jeu..."></textarea>
                </div>
                
                <input type="hidden" name="action" value="add_comment">
                <button type="submit" style="background-color: #00bcd4; color: #0f0f0f; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s;">📤 Publier</button>
            </form>
        </div>
        
        <!-- Commentaires reçus -->
        <div>
            <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">💬 Commentaires reçus (<?php echo count($commentaires); ?>)</h2>
            
            <?php if (empty($commentaires)): ?>
                <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; text-align: center;">
                    <p style="color: #b0b0b0;">Aucun commentaire pour le moment.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($commentaires as $comment): ?>
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