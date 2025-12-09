<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'header.php';
require_once 'dbconnect.php';

$error = '';
$success = '';
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;

// Traitement de l'INSCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // STEP 1 : Création du compte utilisateur (candidat ou joueur)
    if (isset($_POST['action']) && $_POST['action'] === 'register_step1') {
        $email = trim($_POST['registeremail'] ?? '');
        $password = $_POST['registerpassword'] ?? '';
        $confirmpassword = $_POST['registerconfirmpassword'] ?? '';
        $type = $_POST['registertype'] ?? 'joueur';

        if (empty($email) || empty($password)) {
            $error = "Email et mot de passe requis !";
        } elseif ($password !== $confirmpassword) {
            $error = "Les mots de passe ne correspondent pas !";
        } elseif (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au minimum 8 caractères !";
        } else {
            try {
                $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Cet email est déjà utilisé !";
                } else {
                    // Générer salt et hasher le mot de passe
                    $salt = bin2hex(random_bytes(16));
                    $password_hash = hash('sha256', $password . $salt);
                    
                    // Insérer l'utilisateur
                    $stmt = $connexion->prepare("
                        INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$email, $password_hash, $salt, $type])) {
                        $id_utilisateur = $connexion->lastInsertId();
                        
                        // Log audit
                        $stmt = $connexion->prepare("
                            INSERT INTO journal_securite (id_utilisateur, action, details) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$id_utilisateur, 'USER_REGISTRATION', "Type: $type"]);
                        
                        // Si candidat, aller à STEP 2
                        if ($type === 'candidat') {
                            $_SESSION['temp_id_utilisateur'] = $id_utilisateur;
                            $_SESSION['temp_email'] = $email;
                            $step = 2;
                            $success = "✓ Compte créé ! Complétez maintenant votre profil de candidat.";
                        } else {
                            // Si joueur, inscription terminée
                            $success = "✓ Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                            $_POST = [];
                        }
                    } else {
                        $error = "Erreur lors de la création du compte !";
                    }
                }
            } catch (Exception $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
    
    // STEP 2 : Création du profil candidat
    if (isset($_POST['action']) && $_POST['action'] === 'register_step2') {
        $id_utilisateur = $_SESSION['temp_id_utilisateur'] ?? 0;
        $nom = trim($_POST['nom'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $photo = trim($_POST['photo'] ?? '');
        $id_jeu = intval($_POST['id_jeu'] ?? 0);
        $jeu_choice = $_POST['jeu_choice'] ?? 'existant';
        
        if (empty($id_utilisateur)) {
            $error = "Session expirée, recommencez l'inscription !";
        } elseif (empty($nom)) {
            $error = "Le nom du candidat est requis !";
        } elseif ($jeu_choice === 'nouveau') {
            // CRÉER UN NOUVEAU JEU
            $nouveau_jeu_titre = trim($_POST['nouveau_jeu_titre'] ?? '');
            $nouveau_jeu_editeur = trim($_POST['nouveau_jeu_editeur'] ?? '');
            $nouveau_jeu_image = trim($_POST['nouveau_jeu_image'] ?? '');
            $nouveau_jeu_date = trim($_POST['nouveau_jeu_date'] ?? '');
            $nouveau_jeu_description = trim($_POST['nouveau_jeu_description'] ?? '');
            
            if (empty($nouveau_jeu_titre)) {
                $error = "Le titre du jeu est requis !";
            } else {
                try {
                    // Créer le jeu
                    $stmt = $connexion->prepare("
                        INSERT INTO jeu (titre, editeur, image, date_sortie, description) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $nouveau_jeu_titre,
                        !empty($nouveau_jeu_editeur) ? $nouveau_jeu_editeur : null,
                        !empty($nouveau_jeu_image) ? $nouveau_jeu_image : null,
                        !empty($nouveau_jeu_date) ? $nouveau_jeu_date : null,
                        !empty($nouveau_jeu_description) ? $nouveau_jeu_description : null
                    ]);
                    $id_jeu = $connexion->lastInsertId();
                    
                    // Créer le profil candidat
                    $stmt = $connexion->prepare("
                        INSERT INTO candidat (id_utilisateur, nom, bio, photo, id_jeu, date_inscription) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$id_utilisateur, $nom, $bio ?: null, $photo ?: null, $id_jeu]);
                    
                    // Log audit
                    $stmt = $connexion->prepare("
                        INSERT INTO journal_securite (id_utilisateur, action, details) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$id_utilisateur, 'CANDIDAT_CREATION', "Candidat créé avec nouveau jeu: $nouveau_jeu_titre"]);
                    
                    $success = "✓ Profil candidat et jeu créés avec succès ! Vous pouvez maintenant vous connecter.";
                    $_POST = [];
                    $step = 1;
                    unset($_SESSION['temp_id_utilisateur']);
                    unset($_SESSION['temp_email']);
                } catch (Exception $e) {
                    $error = "Erreur : " . $e->getMessage();
                }
            }
        } elseif ($jeu_choice === 'existant') {
            // SÉLECTIONNER JEU EXISTANT
            if (empty($id_jeu)) {
                $error = "Sélectionnez un jeu !";
            } else {
                try {
                    // Vérifier que le jeu existe
                    $stmt = $connexion->prepare("SELECT id_jeu FROM jeu WHERE id_jeu = ?");
                    $stmt->execute([$id_jeu]);
                    if ($stmt->rowCount() === 0) {
                        $error = "Le jeu sélectionné n'existe pas !";
                    } else {
                        // Créer le profil candidat
                        $stmt = $connexion->prepare("
                            INSERT INTO candidat (id_utilisateur, nom, bio, photo, id_jeu, date_inscription) 
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$id_utilisateur, $nom, $bio ?: null, $photo ?: null, $id_jeu]);
                        
                        // Log audit
                        $stmt = $connexion->prepare("
                            INSERT INTO journal_securite (id_utilisateur, action, details) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$id_utilisateur, 'CANDIDAT_CREATION', "Candidat créé avec jeu existant"]);
                        
                        $success = "✓ Profil candidat créé avec succès ! Vous pouvez maintenant vous connecter.";
                        $_POST = [];
                        $step = 1;
                        unset($_SESSION['temp_id_utilisateur']);
                        unset($_SESSION['temp_email']);
                    }
                } catch (Exception $e) {
                    $error = "Erreur : " . $e->getMessage();
                }
            }
        }
    }
}

// Récupérer les jeux existants
$jeux = [];
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
        <div style="max-width: 600px; margin: 0 auto;">
            <h1 style="text-align: center; color: #00bcd4; margin-bottom: 2rem;">
                <?php echo ($step === 2) ? '👤 Profil Candidat' : '📝 S\'inscrire'; ?>
            </h1>
            
            <?php if (!empty($error)): ?>
                <div style="background: rgba(211, 47, 47, 0.1); border-left: 4px solid #d32f2f; border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: #ef9a9a;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div style="background: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: #a5d6a7;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- STEP 1 : Compte utilisateur -->
            <?php if ($step === 1): ?>
            <form method="POST" style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Email</label>
                    <input type="email" name="registeremail" required style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;" value="<?php echo htmlspecialchars($_POST['registeremail'] ?? ''); ?>">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Mot de passe</label>
                    <input type="password" name="registerpassword" required style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Confirmer le mot de passe</label>
                    <input type="password" name="registerconfirmpassword" required style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Type de compte</label>
                    <select name="registertype" id="typeinscription" style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;">
                        <option value="joueur">Joueur</option>
                        <option value="candidat">Candidat (Représenter un jeu)</option>
                    </select>
                </div>
                
                <input type="hidden" name="action" value="register_step1">
                <button type="submit" style="width: 100%; background-color: #00bcd4; color: #0f0f0f; padding: 0.75rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s;">📝 Continuer</button>
            </form>
            
            <!-- STEP 2 : Profil candidat -->
            <?php elseif ($step === 2): ?>
            <form method="POST" style="background: #1a2332; border: 1px solid #2a3a50; border-radius: 8px; padding: 2rem;">
                <p style="color: #b0b0b0; margin-bottom: 1.5rem;">Email : <strong><?php echo htmlspecialchars($_SESSION['temp_email'] ?? ''); ?></strong></p>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Votre nom *</label>
                    <input type="text" name="nom" required style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;" placeholder="ex: Jean Dupont" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Biographie</label>
                    <textarea name="bio" style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0; font-family: inherit; min-height: 80px;" placeholder="Parlez de vous..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Photo (URL)</label>
                    <input type="url" name="photo" style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;" placeholder="https://..." value="<?php echo htmlspecialchars($_POST['photo'] ?? ''); ?>">
                </div>
                
                <hr style="border: none; border-top: 1px solid #2a3a50; margin: 2rem 0;">
                
                <h3 style="color: #00bcd4; margin-bottom: 1.5rem;">🎮 Jeu représenté</h3>
                
                <!-- Option 1 : Sélectionner un jeu existant -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">
                        <input type="radio" name="jeu_choice" value="existant" checked onchange="toggleJeuOptions()"> Sélectionner un jeu existant
                    </label>
                    <select name="id_jeu" id="jeu_select" style="width: 100%; padding: 0.75rem; background-color: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; color: #e0e0e0;">
                        <option value="">-- Choisir un jeu --</option>
                        <?php foreach ($jeux as $jeu): ?>
                            <option value="<?php echo $jeu['id_jeu']; ?>" <?php echo (isset($_POST['id_jeu']) && $_POST['id_jeu'] == $jeu['id_jeu']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jeu['titre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Option 2 : Créer un nouveau jeu -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">
                        <input type="radio" name="jeu_choice" value="nouveau" onchange="toggleJeuOptions()"> Créer un nouveau jeu
                    </label>
                    
                    <div id="nouveau_jeu_fields" style="display: none; margin-top: 1rem; background: #0f0f0f; border: 1px solid #2a3a50; border-radius: 4px; padding: 1rem;">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Titre du jeu *</label>
                            <input type="text" name="nouveau_jeu_titre" id="nouveau_jeu_titre" style="width: 100%; padding: 0.75rem; background-color: #1a1a1a; border: 1px solid #333; border-radius: 4px; color: #e0e0e0;" placeholder="ex: Minecraft" value="<?php echo htmlspecialchars($_POST['nouveau_jeu_titre'] ?? ''); ?>">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Éditeur</label>
                            <input type="text" name="nouveau_jeu_editeur" style="width: 100%; padding: 0.75rem; background-color: #1a1a1a; border: 1px solid #333; border-radius: 4px; color: #e0e0e0;" placeholder="ex: Mojang" value="<?php echo htmlspecialchars($_POST['nouveau_jeu_editeur'] ?? ''); ?>">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Image (URL)</label>
                            <input type="url" name="nouveau_jeu_image" style="width: 100%; padding: 0.75rem; background-color: #1a1a1a; border: 1px solid #333; border-radius: 4px; color: #e0e0e0;" placeholder="https://..." value="<?php echo htmlspecialchars($_POST['nouveau_jeu_image'] ?? ''); ?>">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Date de sortie</label>
                            <input type="date" name="nouveau_jeu_date" style="width: 100%; padding: 0.75rem; background-color: #1a1a1a; border: 1px solid #333; border-radius: 4px; color: #e0e0e0;" value="<?php echo htmlspecialchars($_POST['nouveau_jeu_date'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #00bcd4; font-weight: 600;">Description</label>
                            <textarea name="nouveau_jeu_description" style="width: 100%; padding: 0.75rem; background-color: #1a1a1a; border: 1px solid #333; border-radius: 4px; color: #e0e0e0; font-family: inherit; min-height: 80px;" placeholder="Description du jeu..."><?php echo htmlspecialchars($_POST['nouveau_jeu_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="action" value="register_step2">
                <input type="hidden" name="step" value="2">
                <button type="submit" style="width: 100%; background-color: #00bcd4; color: #0f0f0f; padding: 0.75rem; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.3s;">✅ Créer mon profil</button>
            </form>
            <?php endif; ?>
            
            <p style="text-align: center; color: #b0b0b0; margin-top: 1.5rem;">
                Déjà inscrit ? <a href="login.php" style="color: #00bcd4; text-decoration: none;">Se connecter</a>
            </p>
        </div>
    </div>
</div>

<script>
function toggleJeuOptions() {
    const choice = document.querySelector('input[name="jeu_choice"]:checked').value;
    const selectField = document.getElementById('jeu_select');
    const newJeuFields = document.getElementById('nouveau_jeu_fields');
    const newJeuTitre = document.getElementById('nouveau_jeu_titre');
    
    if (choice === 'nouveau') {
        selectField.disabled = true;
        selectField.value = '';
        selectField.required = false;
        newJeuFields.style.display = 'block';
        newJeuTitre.required = true;
    } else {
        selectField.disabled = false;
        selectField.required = true;
        newJeuFields.style.display = 'none';
        newJeuTitre.required = false;
    }
}
</script>

<?php require_once 'footer.php'; ?>