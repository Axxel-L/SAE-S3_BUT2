<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

$loginerror = '';
$registererror = '';
$registersuccess = '';
$registerinfo = '';
$step = intval($_POST['step'] ?? 1);
$jeux = [];

// Récupérer la liste des jeux pour l'inscription candidat
try {
    $stmt = $connexion->prepare("SELECT id_jeu, titre, editeur FROM jeu ORDER BY titre ASC");
    $stmt->execute();
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Action de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = filter_var(trim($_POST['loginemail'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['loginpassword'] ?? '';

    if (empty($email) || empty($password)) {
        $loginerror = 'Email et mot de passe requis !';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginerror = 'Adresse email invalide !';
    } else {
        try {
            $stmt = $connexion->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $loginerror = 'Email ou mot de passe incorrect !';
            } else {
                $passwordhash = hash('sha256', $password . $user['salt']);
                
                if ($passwordhash !== $user['mot_de_passe']) {
                    $loginerror = 'Email ou mot de passe incorrect !';
                    
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_FAILED', 'Mot de passe incorrect', ?)");
                    $stmt->execute([$user['id_utilisateur'], $_SERVER['REMOTE_ADDR'] ?? '']);
                } else {
                    // Vérifier le statut du candidat si applicable
                    if ($user['type'] === 'candidat') {
                        $stmt = $connexion->prepare("SELECT statut FROM candidat WHERE id_utilisateur = ?");
                        $stmt->execute([$user['id_utilisateur']]);
                        $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$candidat) {
                            $loginerror = "Profil candidat introuvable. Contactez l'administrateur.";
                        } elseif ($candidat['statut'] === 'en_attente') {
                            $loginerror = "⏳ Votre candidature est en attente de validation par un administrateur.";
                        } elseif ($candidat['statut'] === 'refuse') {
                            $loginerror = "❌ Votre candidature a été refusée. Contactez l'administrateur.";
                        } else {
                            $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                            $_SESSION['useremail'] = $user['email'];
                            $_SESSION['pseudo'] = $user['pseudo'] ?? $user['email'];
                            $_SESSION['type'] = $user['type'];
                            
                            $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_SUCCESS', 'Connexion candidat', ?)");
                            $stmt->execute([$user['id_utilisateur'], $_SERVER['REMOTE_ADDR'] ?? '']);
                            
                            echo "<script>
                                if (window.opener) { window.opener.location.reload(); window.close(); }
                                else { window.location.href = './candidat-profil.php'; }
                            </script>";
                            exit;
                        }
                    } else {
                        $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                        $_SESSION['useremail'] = $user['email'];
                        $_SESSION['pseudo'] = $user['pseudo'] ?? $user['email'];
                        $_SESSION['type'] = $user['type'];
                        
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_SUCCESS', ?, ?)");
                        $stmt->execute([$user['id_utilisateur'], "Connexion " . $user['type'], $_SERVER['REMOTE_ADDR'] ?? '']);
                        
                        echo "<script>
                            if (window.opener) { window.opener.location.reload(); window.close(); }
                            else { window.location.href = './dashboard.php'; }
                        </script>";
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            $loginerror = 'Erreur de connexion. Réessayez.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Action d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_step1') {
    $email = filter_var(trim($_POST['registeremail'] ?? ''), FILTER_SANITIZE_EMAIL);
    $pseudo = htmlspecialchars(trim($_POST['registerpseudo'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = $_POST['registerpassword'] ?? '';
    $confirmpassword = $_POST['registerconfirmpassword'] ?? '';
    $type = $_POST['registertype'] ?? 'joueur';
    
    // Seul 'joueur' et 'candidat' sont autorisés
    if (!in_array($type, ['joueur', 'candidat'])) {
        $type = 'joueur';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registererror = 'Adresse email invalide !';
    } elseif ($type === 'joueur' && (empty($pseudo) || strlen($pseudo) < 3 || strlen($pseudo) > 30)) {
        $registererror = 'Pseudo requis (3-30 caractères) !';
    } elseif (empty($password)) {
        $registererror = 'Mot de passe requis !';
    } elseif ($password !== $confirmpassword) {
        $registererror = 'Les mots de passe ne correspondent pas !';
    } elseif (strlen($password) < 8) {
        $registererror = 'Le mot de passe doit contenir au minimum 8 caractères !';
    } else {
        try {
            // Vérifier email unique
            $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $registererror = 'Cet email est déjà utilisé !';
            } else {
                // Vérifier pseudo unique pour les joueurs
                if ($type === 'joueur' && !empty($pseudo)) {
                    $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE pseudo = ?");
                    $stmt->execute([$pseudo]);
                    if ($stmt->rowCount() > 0) {
                        $registererror = 'Ce pseudo est déjà pris !';
                    }
                }
                
                if (empty($registererror)) {
                    $salt = bin2hex(random_bytes(16));
                    $passwordhash = hash('sha256', $password . $salt);
                    
                    // Pour les candidats, le pseudo sera leur nom
                    $pseudo_to_save = ($type === 'joueur') ? $pseudo : null;

                    $stmt = $connexion->prepare("INSERT INTO utilisateur (email, pseudo, mot_de_passe, salt, type, date_inscription) VALUES (?, ?, ?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$email, $pseudo_to_save, $passwordhash, $salt, $type])) {
                        $id_utilisateur = $connexion->lastInsertId();
                        
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'USER_REGISTRATION', ?, ?)");
                        $stmt->execute([$id_utilisateur, "Type: $type", $_SERVER['REMOTE_ADDR'] ?? '']);
                        
                        if ($type === 'candidat') {
                            $_SESSION['temp_id_utilisateur'] = $id_utilisateur;
                            $_SESSION['temp_email'] = $email;
                            $step = 2;
                            $registersuccess = '✓ Compte créé ! Complétez votre profil candidat.';
                        } else {
                            $registersuccess = '✓ Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                        }
                    } else {
                        $registererror = 'Erreur lors de la création du compte !';
                    }
                }
            }
        } catch (Exception $e) {
            $registererror = 'Erreur système. Réessayez.';
            error_log("Register error: " . $e->getMessage());
        }
    }
}

// Action inscription candidat - Candidat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_step2') {
    $id_utilisateur = intval($_SESSION['temp_id_utilisateur'] ?? 0);
    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8');
    $bio = htmlspecialchars(trim($_POST['bio'] ?? ''), ENT_QUOTES, 'UTF-8');
    $photo = filter_var(trim($_POST['photo'] ?? ''), FILTER_SANITIZE_URL);
    $jeu_choice = $_POST['jeu_choice'] ?? 'existant';
    $id_jeu = intval($_POST['id_jeu'] ?? 0);
    
    if (empty($id_utilisateur)) {
        $registererror = "Session expirée ! Recommencez l'inscription.";
        $step = 1;
    } elseif (empty($nom) || strlen($nom) < 2) {
        $registererror = "Le nom est requis (minimum 2 caractères) !";
        $step = 2;
    } elseif (!empty($photo) && !filter_var($photo, FILTER_VALIDATE_URL)) {
        $registererror = "L'URL de la photo n'est pas valide !";
        $step = 2;
    } else {
        try {
            $connexion->beginTransaction();
            
            // Créer un nouveau jeu si demandé
            if ($jeu_choice === 'nouveau') {
                $nouveau_titre = htmlspecialchars(trim($_POST['nouveau_jeu_titre'] ?? ''), ENT_QUOTES, 'UTF-8');
                $nouveau_editeur = htmlspecialchars(trim($_POST['nouveau_jeu_editeur'] ?? ''), ENT_QUOTES, 'UTF-8');
                $nouveau_image = filter_var(trim($_POST['nouveau_jeu_image'] ?? ''), FILTER_SANITIZE_URL);
                $nouveau_date = $_POST['nouveau_jeu_date'] ?? '';
                $nouveau_desc = htmlspecialchars(trim($_POST['nouveau_jeu_description'] ?? ''), ENT_QUOTES, 'UTF-8');
                
                if (empty($nouveau_titre)) {
                    throw new Exception("Le titre du jeu est requis !");
                }
                $stmt = $connexion->prepare("INSERT INTO jeu (titre, editeur, image, date_sortie, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nouveau_titre,
                    $nouveau_editeur ?: null,
                    ($nouveau_image && filter_var($nouveau_image, FILTER_VALIDATE_URL)) ? $nouveau_image : null,
                    $nouveau_date ?: null,
                    $nouveau_desc ?: null
                ]);
                $id_jeu = $connexion->lastInsertId();
            } else {
                if (empty($id_jeu)) {
                    throw new Exception("Veuillez sélectionner un jeu !");
                }
            }
            
            // Créer le profil candidat
            $stmt = $connexion->prepare("INSERT INTO candidat (id_utilisateur, nom, bio, photo, id_jeu, statut, date_inscription) VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())");
            $stmt->execute([$id_utilisateur, $nom, $bio ?: null, $photo ?: null, $id_jeu]);
            
            // Mettre à jour le pseudo de l'utilisateur avec son nom de candidat
            $stmt = $connexion->prepare("UPDATE utilisateur SET pseudo = ? WHERE id_utilisateur = ?");
            $stmt->execute([$nom, $id_utilisateur]);
            
            // Ajout aux logs
            $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'CANDIDAT_REGISTRATION', ?, ?)");
            $stmt->execute([$id_utilisateur, "Candidat: $nom, Jeu: $id_jeu", $_SERVER['REMOTE_ADDR'] ?? '']);
            
            $connexion->commit();
            
            unset($_SESSION['temp_id_utilisateur']);
            unset($_SESSION['temp_email']);
            
            $registersuccess = "✓ Candidature soumise ! Un administrateur doit valider votre inscription.";
            $registerinfo = "Vous recevrez une notification une fois votre candidature examinée.";
            $step = 1;
            
        } catch (Exception $e) {
            $connexion->rollBack();
            $registererror = $e->getMessage();
            $step = 2;
        }
    }
}

$showregister = isset($_GET['register']) || $step === 2;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - V1</title>
    <script src="http://cdn.agence-prestige-numerique.fr/tailwindcss/3.4.17.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <style>
        .modal-content { background: rgba(10, 10, 10, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); }
        .message-box { padding: 12px 15px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .message-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .message-info { background: rgba(0, 212, 255, 0.1); color: #00d4ff; border: 1px solid rgba(0, 212, 255, 0.3); }
        .input-glow:focus { box-shadow: 0 0 20px rgba(0, 212, 255, 0.15); }
        .candidat-info { display: none; }
        .candidat-info.show { display: block; }
        .joueur-pseudo.hide { display: none; }
        .nouveau-jeu { display: none; }
        .nouveau-jeu.show { display: block; }
    </style>
</head>
<body class="font-inter">
    <div class="gaming-bg"><div class="diagonal-lines"></div></div>
    <div class="fixed inset-0 z-40 backdrop-blur-md" style="background: rgba(0,0,0,0.7);"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative w-full max-w-md my-8">
            <div class="modal-content rounded-3xl p-8 backdrop-blur-xl shadow-2xl">
                <?php if (!$showregister): ?>
                    <div class="text-center mb-8">
                        <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-cyan-500/20 to-cyan-500/5 border border-cyan-500/30 mx-auto mb-4 shadow-lg shadow-cyan-500/20">
                            <i class="fas fa-crown text-2xl text-cyan-400"></i>
                        </div>
                        <h1 class="text-3xl font-bold font-orbitron text-white mb-2">Connexion</h1>
                        <p class="text-white/60 text-sm">Accédez à votre compte GameCrown</p>
                    </div>

                    <?php if ($loginerror): ?>
                        <div class="message-box message-error"><i class="fas fa-exclamation-circle"></i><span><?php echo $loginerror; ?></span></div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-envelope text-cyan-400 mr-2"></i>Email</label>
                            <input type="email" name="loginemail" required class="input-glow w-full rounded-xl p-4 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="votre@email.com">
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-lock text-cyan-400 mr-2"></i>Mot de passe</label>
                            <div class="relative">
                                <input type="password" name="loginpassword" id="loginpassword" required class="input-glow w-full rounded-xl p-4 pr-12 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="Votre mot de passe">
                                <button type="button" onclick="togglePassword('loginpassword', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-cyan-400"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-cyan-500 to-cyan-600 text-dark flex items-center justify-center gap-2 hover:shadow-lg hover:shadow-cyan-500/30 transition-all mt-6">
                            <i class="fas fa-sign-in-alt"></i><span>Se connecter</span>
                        </button>
                    </form>
                    <div class="flex items-center my-6"><div class="flex-1 h-px bg-white/20"></div><span class="px-4 text-white/40 text-sm">ou</span><div class="flex-1 h-px bg-white/20"></div></div>
                    <div class="text-center"><p class="text-white/60 text-sm">Pas encore de compte ? <a href="?register=1" class="text-cyan-400 font-medium hover:underline">S'inscrire</a></p></div>
                <?php endif; ?>

                <!-- INSCRIPTION ÉTAPE 1 -->
                <?php if ($showregister && $step === 1): ?>
                    <div class="text-center mb-6">
                        <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-cyan-500/20 to-cyan-500/5 border border-cyan-500/30 mx-auto mb-4">
                            <i class="fas fa-user-plus text-2xl text-cyan-400"></i>
                        </div>
                        <h1 class="text-3xl font-bold font-orbitron text-white mb-2">Inscription</h1>
                        <p class="text-white/60 text-sm">Rejoignez la communauté GameCrown</p>
                    </div>

                    <?php if ($registererror): ?><div class="message-box message-error"><i class="fas fa-exclamation-circle"></i><span><?php echo $registererror; ?></span></div><?php endif; ?>
                    <?php if ($registersuccess): ?><div class="message-box message-success"><i class="fas fa-check-circle"></i><span><?php echo $registersuccess; ?></span></div><?php if ($registerinfo): ?><div class="message-box message-info"><i class="fas fa-info-circle"></i><span><?php echo $registerinfo; ?></span></div><?php endif; ?><?php else: ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register_step1">
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-envelope text-cyan-400 mr-2"></i>Email *</label>
                            <input type="email" name="registeremail" required class="input-glow w-full rounded-xl p-4 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="votre@email.com" value="<?php echo htmlspecialchars($_POST['registeremail'] ?? ''); ?>">
                        </div>
                        <div id="pseudoField" class="joueur-pseudo">
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-user text-cyan-400 mr-2"></i>Pseudo * <span class="text-white/40 text-xs">(visible publiquement)</span></label>
                            <input type="text" name="registerpseudo" id="registerpseudo" minlength="3" maxlength="30" class="input-glow w-full rounded-xl p-4 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="Votre pseudo (3-30 car.)" value="<?php echo htmlspecialchars($_POST['registerpseudo'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-lock text-cyan-400 mr-2"></i>Mot de passe *</label>
                            <div class="relative">
                                <input type="password" name="registerpassword" id="registerpassword" required minlength="8" class="input-glow w-full rounded-xl p-4 pr-12 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="Minimum 8 caractères">
                                <button type="button" onclick="togglePassword('registerpassword', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-cyan-400"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="flex gap-1 mt-2"><div id="bar1" class="h-1 flex-1 rounded-full bg-white/10"></div><div id="bar2" class="h-1 flex-1 rounded-full bg-white/10"></div><div id="bar3" class="h-1 flex-1 rounded-full bg-white/10"></div><div id="bar4" class="h-1 flex-1 rounded-full bg-white/10"></div></div>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-lock text-cyan-400 mr-2"></i>Confirmer *</label>
                            <input type="password" name="registerconfirmpassword" id="confirmpassword" required class="input-glow w-full rounded-xl p-4 text-white bg-white/5 border border-white/10 focus:border-cyan-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="Confirmez le mot de passe">
                            <p id="matchMsg" class="text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-user-tag text-cyan-400 mr-2"></i>Type de compte</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer"><input type="radio" name="registertype" value="joueur" checked class="hidden" id="typeJoueur" onchange="updateTypeSelection()"><div id="boxJoueur" class="p-4 rounded-xl border-2 border-cyan-500 bg-cyan-500/10 text-center transition-all"><i class="fas fa-gamepad text-2xl text-cyan-400 mb-2"></i><p class="text-white text-sm font-medium">Joueur</p><p class="text-white/40 text-xs mt-1">Votez & commentez</p></div></label>
                                <label class="cursor-pointer"><input type="radio" name="registertype" value="candidat" class="hidden" id="typeCandidat" onchange="updateTypeSelection()"><div id="boxCandidat" class="p-4 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all"><i class="fas fa-trophy text-2xl text-purple-400 mb-2"></i><p class="text-white text-sm font-medium">Candidat</p><p class="text-white/40 text-xs mt-1">Représentez un jeu</p></div></label>
                            </div>
                            <div id="candidatInfo" class="candidat-info mt-4 p-4 bg-purple-500/10 border border-purple-500/30 rounded-xl">
                                <p class="text-sm text-purple-300 mb-2"><i class="fas fa-info-circle mr-1"></i> En tant que candidat :</p>
                                <ul class="text-xs text-white/60 space-y-1 mb-2"><li>• Votre nom sera votre pseudo public</li><li>• Vous pourrez gérer votre campagne</li></ul>
                                <p class="text-xs text-orange-400"><i class="fas fa-exclamation-triangle mr-1"></i>Validation admin requise</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-cyan-500 to-cyan-600 text-dark flex items-center justify-center gap-2 hover:shadow-lg hover:shadow-cyan-500/30 transition-all mt-4"><i class="fas fa-arrow-right"></i><span>Continuer</span></button>
                    </form>
                    <?php endif; ?>
                    <div class="flex items-center my-6"><div class="flex-1 h-px bg-white/20"></div><span class="px-4 text-white/40 text-sm">ou</span><div class="flex-1 h-px bg-white/20"></div></div>
                    <div class="text-center"><p class="text-white/60 text-sm">Déjà un compte ? <a href="login.php" class="text-cyan-400 font-medium hover:underline">Se connecter</a></p></div>
                <?php endif; ?>

                <!-- INSCRIPTION ÉTAPE 2 (CANDIDAT) -->
                <?php if ($step === 2): ?>
                    <div class="text-center mb-6">
                        <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-purple-500/20 to-purple-500/5 border border-purple-500/30 mx-auto mb-4"><i class="fas fa-trophy text-2xl text-purple-400"></i></div>
                        <h1 class="text-2xl font-bold font-orbitron text-white mb-2">Profil Candidat</h1>
                        <p class="text-white/60 text-sm">Étape 2/2</p>
                        <div class="flex justify-center gap-2 mt-3"><div class="w-3 h-3 rounded-full bg-purple-500"></div><div class="w-3 h-3 rounded-full bg-purple-500"></div></div>
                    </div>

                    <?php if ($registererror): ?><div class="message-box message-error"><i class="fas fa-exclamation-circle"></i><span><?php echo $registererror; ?></span></div><?php endif; ?>
                    <div class="message-box message-info mb-4"><i class="fas fa-user"></i><span>Email : <strong><?php echo htmlspecialchars($_SESSION['temp_email'] ?? ''); ?></strong></span></div>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register_step2">
                        
                        <div><label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-id-card text-purple-400 mr-2"></i>Votre nom * <span class="text-white/40 text-xs">(sera votre pseudo)</span></label><input type="text" name="nom" required minlength="2" maxlength="100" class="input-glow w-full rounded-xl p-3 text-white bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="ex: Jean Dupont"></div>
                        <div><label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-align-left text-purple-400 mr-2"></i>Biographie</label><textarea name="bio" rows="2" maxlength="500" class="input-glow w-full rounded-xl p-3 text-white bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all resize-none placeholder-white/30" placeholder="Parlez de vous..."></textarea></div>
                        <div><label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-image text-purple-400 mr-2"></i>Photo (URL)</label><input type="url" name="photo" maxlength="500" class="input-glow w-full rounded-xl p-3 text-white bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all placeholder-white/30" placeholder="https://..."></div>
                        <hr class="border-white/10">
                        <div>
                            <label class="block mb-2 font-medium text-white text-sm"><i class="fas fa-gamepad text-purple-400 mr-2"></i>Jeu représenté *</label>
                            <p class="text-xs text-orange-400 mb-3"><i class="fas fa-exclamation-triangle mr-1"></i>Non modifiable après inscription !</p>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-white/5 cursor-pointer"><input type="radio" name="jeu_choice" value="existant" checked class="accent-purple-500" onchange="toggleNouveauJeu()"><span class="text-white text-sm">Jeu existant</span></label>
                                <select name="id_jeu" id="jeuSelect" class="w-full rounded-xl p-3 text-white bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none"><option value="">-- Choisir --</option><?php foreach ($jeux as $jeu): ?><option value="<?php echo $jeu['id_jeu']; ?>"><?php echo htmlspecialchars($jeu['titre']); ?></option><?php endforeach; ?></select>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-white/5 cursor-pointer"><input type="radio" name="jeu_choice" value="nouveau" class="accent-purple-500" onchange="toggleNouveauJeu()"><span class="text-white text-sm">Créer un nouveau jeu</span></label>
                                <div id="nouveauJeu" class="nouveau-jeu mt-3 p-4 bg-white/5 border border-white/10 rounded-xl space-y-3">
                                    <input type="text" name="nouveau_jeu_titre" id="nouveauTitre" maxlength="200" class="w-full rounded-lg p-3 text-white bg-white/5 border border-white/10 text-sm placeholder-white/30" placeholder="Titre du jeu *">
                                    <input type="text" name="nouveau_jeu_editeur" maxlength="200" class="w-full rounded-lg p-3 text-white bg-white/5 border border-white/10 text-sm placeholder-white/30" placeholder="Éditeur">
                                    <input type="url" name="nouveau_jeu_image" class="w-full rounded-lg p-3 text-white bg-white/5 border border-white/10 text-sm placeholder-white/30" placeholder="URL image">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-purple-500 to-purple-600 text-white flex items-center justify-center gap-2 hover:shadow-lg hover:shadow-purple-500/30 transition-all mt-4"><i class="fas fa-paper-plane"></i><span>Soumettre ma candidature</span></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(id, btn) { const i = document.getElementById(id); i.type = i.type === 'password' ? 'text' : 'password'; btn.innerHTML = i.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>'; }
        document.getElementById('registerpassword')?.addEventListener('input', function() {
            let s = 0; if (this.value.length >= 8) s++; if (/[a-z]/.test(this.value) && /[A-Z]/.test(this.value)) s++; if (/\d/.test(this.value)) s++; if (/[^a-zA-Z\d]/.test(this.value)) s++;
            const c = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            for (let i = 1; i <= 4; i++) document.getElementById('bar' + i).className = 'h-1 flex-1 rounded-full ' + (i <= s ? c[s-1] : 'bg-white/10');
            checkMatch();
        });
        
        function checkMatch() { const p1 = document.getElementById('registerpassword')?.value || '', p2 = document.getElementById('confirmpassword')?.value || '', m = document.getElementById('matchMsg'); if (!m || !p2) { m?.classList.add('hidden'); return; } m.classList.remove('hidden'); m.textContent = p1 === p2 ? '✓ Correspondent' : '✗ Ne correspondent pas'; m.className = 'text-xs mt-1 ' + (p1 === p2 ? 'text-green-400' : 'text-red-400'); }
        document.getElementById('confirmpassword')?.addEventListener('input', checkMatch);
        
        function updateTypeSelection() {
            const j = document.getElementById('typeJoueur')?.checked, bJ = document.getElementById('boxJoueur'), bC = document.getElementById('boxCandidat'), cI = document.getElementById('candidatInfo'), pF = document.getElementById('pseudoField'), pI = document.getElementById('registerpseudo');
            if (bJ && bC) { bJ.className = 'p-4 rounded-xl border-2 text-center transition-all ' + (j ? 'border-cyan-500 bg-cyan-500/10' : 'border-white/10 bg-white/5'); bC.className = 'p-4 rounded-xl border-2 text-center transition-all ' + (j ? 'border-white/10 bg-white/5' : 'border-purple-500 bg-purple-500/10'); }
            if (pF) { pF.classList.toggle('hide', !j); if (pI) pI.required = j; }
            if (cI) cI.classList.toggle('show', !j);
        }
        document.addEventListener('DOMContentLoaded', updateTypeSelection);
        
        function toggleNouveauJeu() { const c = document.querySelector('input[name="jeu_choice"]:checked')?.value, s = document.getElementById('jeuSelect'), n = document.getElementById('nouveauJeu'), t = document.getElementById('nouveauTitre'); if (c === 'nouveau') { s.disabled = true; s.classList.add('opacity-50'); n.classList.add('show'); if (t) t.required = true; } else { s.disabled = false; s.classList.remove('opacity-50'); n.classList.remove('show'); if (t) t.required = false; } }
    </script>
</body>
</html>