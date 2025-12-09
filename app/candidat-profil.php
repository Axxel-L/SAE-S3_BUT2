<?php
session_start();
require_once 'dbconnect.php';

// Vérifier candidat AVANT d'inclure header.php
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Récupérer les infos du candidat AVANT d'inclure header.php
try {
    $stmt = $connexion->prepare("
        SELECT c.*, u.email, u.date_inscription, j.titre as titre_jeu
        FROM candidat c 
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$candidat) {
        // Pas de profil candidat, rediriger AVANT header.php
        header('Location: register-candidat.php');
        exit;
    }
} catch (Exception $e) {
    $candidat = null;
}

// MAINTENANT on peut inclure header.php
require_once 'header.php';

$error = '';
$success = '';
$jeux = [];

// Traitement de la mise à jour du jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_jeu') {
        $id_jeu = intval($_POST['id_jeu'] ?? 0);
        
        if (!empty($id_jeu)) {
            try {
                $stmt = $connexion->prepare("
                    UPDATE candidat SET id_jeu = ? WHERE id_utilisateur = ?
                ");
                $stmt->execute([$id_jeu, $id_utilisateur]);
                $success = "Jeu mise à jour avec succès ! ✅";
                
                // Log audit
                $stmt = $connexion->prepare("
                    INSERT INTO journal_securite (id_utilisateur, action, details) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$id_utilisateur, 'CANDIDAT_JEU_CHANGE', "Nouveau jeu: $id_jeu"]);
                
                // Rafraîchir les données
                $stmt = $connexion->prepare("
                    SELECT c.*, u.email, u.date_inscription, j.titre as titre_jeu
                    FROM candidat c 
                    JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur 
                    LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
                    WHERE c.id_utilisateur = ?
                ");
                $stmt->execute([$id_utilisateur]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez sélectionner un jeu !";
        }
    }
}

// Récupérer tous les jeux
try {
    $stmt = $connexion->prepare("SELECT * FROM jeu ORDER BY titre ASC");
    $stmt->execute();
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jeux = [];
}
?>

<div class="page-content">
    <div class="container-wrapper">
        <h1>👤 Mon Profil Candidat</h1>
        <p style="color: #b0b0b0; margin-bottom: 2rem;">Gérez votre candidature</p>
        
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
        
        <?php if ($candidat): ?>
            <!-- Infos candidat -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
                <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">📋 Vos informations</h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="color: #b0b0b0; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Email</label>
                        <p style="color: #e0e0e0; font-weight: 500;"><?php echo htmlspecialchars($candidat['email']); ?></p>
                    </div>
                    <div>
                        <label style="color: #b0b0b0; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Inscrit depuis</label>
                        <p style="color: #e0e0e0; font-weight: 500;"><?php echo date('d/m/Y', strtotime($candidat['date_inscription'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Jeu représenté -->
            <div style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
                <h2 style="color: #00bcd4; margin-bottom: 1.5rem;">🎮 Jeu représenté</h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <label style="color: #b0b0b0; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Jeu actuel</label>
                        <p style="color: #00bcd4; font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($candidat['titre_jeu'] ?? 'Aucun jeu sélectionné'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Formulaire modification -->
                <form method="POST">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Changer de jeu</label>
                        <select name="id_jeu" required style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;">
                            <option value="">-- Sélectionner un jeu --</option>
                            <?php foreach ($jeux as $jeu): ?>
                                <option value="<?php echo $jeu['id_jeu']; ?>" <?php echo ($candidat['id_jeu'] == $jeu['id_jeu']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jeu['titre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="action" value="update_jeu">
                    <button type="submit" style="background-color: #00bcd4; color: #0f0f0f; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s;">💾 Mettre à jour</button>
                </form>
            </div>
            
            <!-- Info candidat -->
            <div style="background: rgba(0, 188, 212, 0.1); border: 1px solid rgba(0, 188, 212, 0.3); border-radius: 8px; padding: 1.5rem;">
                <p style="color: #b0b0b0; margin: 0;">
                    <i class="fas fa-info-circle"></i> <strong>Vous êtes candidat !</strong> Vous pouvez maintenant accéder aux sections Campagne et Statistiques pour gérer votre candidature.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>