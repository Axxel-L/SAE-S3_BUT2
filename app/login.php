<?php
/**
 * AUTHENTIFICATION - GameCrown
 * - Connexion avec vérification du statut candidat
 * - Inscription joueur (direct) ou candidat (2 étapes + validation admin)
 * - Protection contre les injections SQL
 */

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

// ========================================
// TRAITEMENT CONNEXION
// ========================================
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
                    
                    // Log tentative échouée
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_FAILED', 'Mot de passe incorrect', ?)");
                    $stmt->execute([$user['id_utilisateur'], $_SERVER['REMOTE_ADDR'] ?? '']);
                } else {
                    // ========================================
                    // VÉRIFICATION SPÉCIALE POUR LES CANDIDATS
                    // ========================================
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
                            // Candidat validé - OK
                            $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                            $_SESSION['useremail'] = $user['email'];
                            $_SESSION['type'] = $user['type'];
                            
                            $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_SUCCESS', 'Connexion candidat', ?)");
                            $stmt->execute([$user['id_utilisateur'], $_SERVER['REMOTE_ADDR'] ?? '']);
                            
                            echo "<script>
                                if (window.opener) { window.opener.location.reload(); window.close(); }
                                else { window.location.href = 'candidat-profil.php'; }
                            </script>";
                            exit;
                        }
                    } else {
                        // Joueur ou Admin - connexion directe
                        $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                        $_SESSION['useremail'] = $user['email'];
                        $_SESSION['type'] = $user['type'];
                        
                        $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'LOGIN_SUCCESS', ?, ?)");
                        $stmt->execute([$user['id_utilisateur'], "Connexion " . $user['type'], $_SERVER['REMOTE_ADDR'] ?? '']);
                        
                        echo "<script>
                            if (window.opener) { window.opener.location.reload(); window.close(); }
                            else { window.location.href = 'index.php'; }
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

// ========================================
// TRAITEMENT INSCRIPTION - ÉTAPE 1
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_step1') {
    $email = filter_var(trim($_POST['registeremail'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['registerpassword'] ?? '';
    $confirmpassword = $_POST['registerconfirmpassword'] ?? '';
    $type = $_POST['registertype'] ?? 'joueur';
    
    // Sécurité : seuls joueur et candidat sont autorisés
    if (!in_array($type, ['joueur', 'candidat'])) {
        $type = 'joueur';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registererror = 'Adresse email invalide !';
    } elseif (empty($password)) {
        $registererror = 'Mot de passe requis !';
    } elseif ($password !== $confirmpassword) {
        $registererror = 'Les mots de passe ne correspondent pas !';
    } elseif (strlen($password) < 8) {
        $registererror = 'Le mot de passe doit contenir au minimum 8 caractères !';
    } else {
        try {
            $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $registererror = 'Cet email est déjà utilisé !';
            } else {
                $salt = bin2hex(random_bytes(16));
                $passwordhash = hash('sha256', $password . $salt);

                $stmt = $connexion->prepare("INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) VALUES (?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$email, $passwordhash, $salt, $type])) {
                    $id_utilisateur = $connexion->lastInsertId();
                    
                    // Log
                    $stmt = $connexion->prepare("INSERT INTO journal_securite (id_utilisateur, action, details, adresse_ip) VALUES (?, 'USER_REGISTRATION', ?, ?)");
                    $stmt->execute([$id_utilisateur, "Type: $type", $_SERVER['REMOTE_ADDR'] ?? '']);
                    
                    if ($type === 'candidat') {
                        // Passer à l'étape 2
                        $_SESSION['temp_id_utilisateur'] = $id_utilisateur;
                        $_SESSION['temp_email'] = $email;
                        $step = 2;
                        $registersuccess = '✓ Compte créé ! Complétez votre profil candidat.';
                    } else {
                        // Joueur - inscription terminée
                        $registersuccess = '✓ Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                    }
                } else {
                    $registererror = 'Erreur lors de la création du compte !';
                }
            }
        } catch (Exception $e) {
            $registererror = 'Erreur système. Réessayez.';
            error_log("Register error: " . $e->getMessage());
        }
    }
}

// ========================================
// TRAITEMENT INSCRIPTION - ÉTAPE 2 (CANDIDAT)
// ========================================
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
            
            // Créer le profil candidat (EN ATTENTE)
            $stmt = $connexion->prepare("INSERT INTO candidat (id_utilisateur, nom, bio, photo, id_jeu, statut, date_inscription) VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())");
            $stmt->execute([$id_utilisateur, $nom, $bio ?: null, $photo ?: null, $id_jeu]);
            
            // Log
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
    <title>GameCrown - Authentification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        .modal-content { background: rgba(10, 10, 10, 0.9); border: 1px solid rgba(255, 255, 255, 0.1); }
        .message-box { padding: 12px 15px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .message-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .message-info { background: rgba(0, 212, 255, 0.1); color: #00d4ff; border: 1px solid rgba(0, 212, 255, 0.3); }
        .message-warning { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .input-glow:focus { box-shadow: 0 0 20px rgba(0, 212, 255, 0.15); }
        .candidat-info { display: none; margin-top: 1rem; padding: 1rem; background: rgba(147, 51, 234, 0.1); border: 1px solid rgba(147, 51, 234, 0.3); border-radius: 12px; }
        .candidat-info.show { display: block; }
        .nouveau-jeu { display: none; margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; }
        .nouveau-jeu.show { display: block; }
    </style>
</head>
<body class="font-inter">
    <div class="gaming-bg">
        <div class="diagonal-lines"></div>
        <div class="diagonal-lines-2"></div>
    </div>

    <div class="fixed inset-0 z-40 backdrop-blur-md" style="background: rgba(0,0,0,0.6);"></div>

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative w-full max-w-md my-8">
            <div class="modal-content rounded-3xl p-8 backdrop-blur-xl">

                <!-- ========================================
                     FORMULAIRE CONNEXION
                     ======================================== -->
                <?php if (!$showregister): ?>
                    <div class="text-center mb-8">
                        <div class="inline-block mb-4">
                            <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 mx-auto">
                                <i class="fas fa-lock text-2xl text-accent"></i>
                            </div>
                        </div>
                        <h1 class="text-3xl font-bold font-orbitron text-light mb-2">Connexion</h1>
                        <p class="text-light/60 text-sm">Accédez à votre compte GameCrown</p>
                    </div>

                    <?php if ($loginerror): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo $loginerror; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" name="loginemail" required
                                class="input-glow w-full rounded-xl p-4 text-light bg-white/5 border border-white/10 focus:border-accent/50 focus:outline-none transition-all"
                                placeholder="votre@email.com">
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" name="loginpassword" id="loginpassword" required
                                    class="input-glow w-full rounded-xl p-4 pr-12 text-light bg-white/5 border border-white/10 focus:border-accent/50 focus:outline-none transition-all"
                                    placeholder="Votre mot de passe">
                                <button type="button" onclick="togglePassword('loginpassword', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-accent to-cyan-600 text-dark flex items-center justify-center gap-2 hover:opacity-90 transition-all mt-6">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Se connecter</span>
                        </button>
                    </form>

                    <div class="flex items-center my-6">
                        <div class="flex-1 h-px bg-white/20"></div>
                        <span class="px-4 text-white/40 text-sm">ou</span>
                        <div class="flex-1 h-px bg-white/20"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-light/60 text-sm">Pas encore de compte ? 
                            <a href="?register=1" class="text-accent font-medium hover:underline">S'inscrire</a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- ========================================
                     FORMULAIRE INSCRIPTION - ÉTAPE 1
                     ======================================== -->
                <?php if ($showregister && $step === 1): ?>
                    <div class="text-center mb-6">
                        <div class="inline-block mb-4">
                            <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 mx-auto">
                                <i class="fas fa-user-plus text-2xl text-accent"></i>
                            </div>
                        </div>
                        <h1 class="text-3xl font-bold font-orbitron text-light mb-2">Inscription</h1>
                        <p class="text-light/60 text-sm">Rejoignez la communauté GameCrown</p>
                    </div>

                    <?php if ($registererror): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo $registererror; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($registersuccess): ?>
                        <div class="message-box message-success">
                            <i class="fas fa-check-circle flex-shrink-0"></i>
                            <span><?php echo $registersuccess; ?></span>
                        </div>
                        <?php if ($registerinfo): ?>
                            <div class="message-box message-info">
                                <i class="fas fa-info-circle flex-shrink-0"></i>
                                <span><?php echo $registerinfo; ?></span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register_step1">
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" name="registeremail" required
                                class="input-glow w-full rounded-xl p-4 text-light bg-white/5 border border-white/10 focus:border-accent/50 focus:outline-none transition-all"
                                placeholder="votre@email.com"
                                value="<?php echo htmlspecialchars($_POST['registeremail'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" name="registerpassword" id="registerpassword" required minlength="8"
                                    class="input-glow w-full rounded-xl p-4 pr-12 text-light bg-white/5 border border-white/10 focus:border-accent/50 focus:outline-none transition-all"
                                    placeholder="Minimum 8 caractères">
                                <button type="button" onclick="togglePassword('registerpassword', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="flex gap-1 mt-2">
                                <div id="bar1" class="h-1 flex-1 rounded-full bg-white/10"></div>
                                <div id="bar2" class="h-1 flex-1 rounded-full bg-white/10"></div>
                                <div id="bar3" class="h-1 flex-1 rounded-full bg-white/10"></div>
                                <div id="bar4" class="h-1 flex-1 rounded-full bg-white/10"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Confirmer
                            </label>
                            <input type="password" name="registerconfirmpassword" id="confirmpassword" required
                                class="input-glow w-full rounded-xl p-4 text-light bg-white/5 border border-white/10 focus:border-accent/50 focus:outline-none transition-all"
                                placeholder="Confirmez le mot de passe">
                            <p id="matchMsg" class="text-xs mt-1 hidden"></p>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-user-tag text-accent mr-2"></i>Type de compte
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer" id="labelJoueur">
                                    <input type="radio" name="registertype" value="joueur" checked class="hidden" id="typeJoueur" onchange="updateTypeSelection()">
                                    <div id="boxJoueur" class="p-3 rounded-xl border-2 border-accent bg-accent/10 text-center transition-all">
                                        <i class="fas fa-gamepad text-xl text-accent mb-1"></i>
                                        <p class="text-light text-sm font-medium">Joueur</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer" id="labelCandidat">
                                    <input type="radio" name="registertype" value="candidat" class="hidden" id="typeCandidat" onchange="updateTypeSelection()">
                                    <div id="boxCandidat" class="p-3 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all">
                                        <i class="fas fa-trophy text-xl text-purple-400 mb-1"></i>
                                        <p class="text-light text-sm font-medium">Candidat</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div id="candidatInfo" class="candidat-info">
                                <p class="text-sm text-purple-300 mb-2"><i class="fas fa-info-circle mr-1"></i> En tant que candidat :</p>
                                <ul class="text-xs text-light/60 space-y-1 mb-2">
                                    <li>• Vous représenterez un jeu vidéo</li>
                                    <li>• Vous devrez compléter un profil</li>
                                </ul>
                                <p class="text-xs text-orange-400"><i class="fas fa-exclamation-triangle mr-1"></i>Validation admin requise</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-accent to-cyan-600 text-dark flex items-center justify-center gap-2 hover:opacity-90 transition-all mt-4">
                            <i class="fas fa-arrow-right"></i>
                            <span>Continuer</span>
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="flex items-center my-6">
                        <div class="flex-1 h-px bg-white/20"></div>
                        <span class="px-4 text-white/40 text-sm">ou</span>
                        <div class="flex-1 h-px bg-white/20"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-light/60 text-sm">Déjà un compte ? 
                            <a href="login.php" class="text-accent font-medium hover:underline">Se connecter</a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- ========================================
                     FORMULAIRE INSCRIPTION - ÉTAPE 2 (CANDIDAT)
                     ======================================== -->
                <?php if ($step === 2): ?>
                    <div class="text-center mb-6">
                        <div class="inline-block mb-4">
                            <div class="rounded-2xl p-4 w-16 h-16 flex items-center justify-center bg-gradient-to-br from-purple-500/20 to-purple-500/5 border border-purple-500/30 mx-auto">
                                <i class="fas fa-trophy text-2xl text-purple-400"></i>
                            </div>
                        </div>
                        <h1 class="text-2xl font-bold font-orbitron text-light mb-2">Profil Candidat</h1>
                        <p class="text-light/60 text-sm">Étape 2/2 - Complétez votre profil</p>
                        <div class="flex justify-center gap-2 mt-3">
                            <div class="w-3 h-3 rounded-full bg-accent"></div>
                            <div class="w-3 h-3 rounded-full bg-accent"></div>
                        </div>
                    </div>

                    <?php if ($registererror): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo $registererror; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($registersuccess): ?>
                        <div class="message-box message-success">
                            <i class="fas fa-check-circle flex-shrink-0"></i>
                            <span><?php echo $registersuccess; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="message-box message-info mb-4">
                        <i class="fas fa-user flex-shrink-0"></i>
                        <span>Email : <strong><?php echo htmlspecialchars($_SESSION['temp_email'] ?? ''); ?></strong></span>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register_step2">
                        <input type="hidden" name="step" value="2">
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-id-card text-purple-400 mr-2"></i>Votre nom *
                            </label>
                            <input type="text" name="nom" required minlength="2" maxlength="100"
                                class="input-glow w-full rounded-xl p-3 text-light bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all"
                                placeholder="ex: Jean Dupont"
                                value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-align-left text-purple-400 mr-2"></i>Biographie
                            </label>
                            <textarea name="bio" rows="2" maxlength="500"
                                class="input-glow w-full rounded-xl p-3 text-light bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all resize-none"
                                placeholder="Parlez de vous..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-image text-purple-400 mr-2"></i>Photo (URL)
                            </label>
                            <input type="url" name="photo" maxlength="500"
                                class="input-glow w-full rounded-xl p-3 text-light bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none transition-all"
                                placeholder="https://..."
                                value="<?php echo htmlspecialchars($_POST['photo'] ?? ''); ?>">
                        </div>
                        
                        <hr class="border-white/10">
                        
                        <div>
                            <label class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-gamepad text-purple-400 mr-2"></i>Jeu représenté *
                            </label>
                            <p class="text-xs text-orange-400 mb-3"><i class="fas fa-exclamation-triangle mr-1"></i>Vous ne pourrez pas changer de jeu après inscription !</p>
                            
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-white/5 cursor-pointer">
                                    <input type="radio" name="jeu_choice" value="existant" checked class="accent-purple-500" onchange="toggleNouveauJeu()">
                                    <span class="text-light text-sm">Jeu existant</span>
                                </label>
                                
                                <select name="id_jeu" id="jeuSelect" class="w-full rounded-xl p-3 text-light bg-white/5 border border-white/10 focus:border-purple-500/50 focus:outline-none">
                                    <option value="">-- Choisir --</option>
                                    <?php foreach ($jeux as $jeu): ?>
                                        <option value="<?php echo $jeu['id_jeu']; ?>"><?php echo htmlspecialchars($jeu['titre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-white/5 cursor-pointer">
                                    <input type="radio" name="jeu_choice" value="nouveau" class="accent-purple-500" onchange="toggleNouveauJeu()">
                                    <span class="text-light text-sm">Créer un nouveau jeu</span>
                                </label>
                                
                                <div id="nouveauJeu" class="nouveau-jeu space-y-3">
                                    <input type="text" name="nouveau_jeu_titre" id="nouveauTitre" maxlength="200"
                                        class="w-full rounded-lg p-2 text-light bg-white/5 border border-white/10 text-sm"
                                        placeholder="Titre du jeu *">
                                    <input type="text" name="nouveau_jeu_editeur" maxlength="200"
                                        class="w-full rounded-lg p-2 text-light bg-white/5 border border-white/10 text-sm"
                                        placeholder="Éditeur">
                                    <input type="url" name="nouveau_jeu_image"
                                        class="w-full rounded-lg p-2 text-light bg-white/5 border border-white/10 text-sm"
                                        placeholder="URL image">
                                    <input type="date" name="nouveau_jeu_date"
                                        class="w-full rounded-lg p-2 text-light bg-white/5 border border-white/10 text-sm">
                                    <textarea name="nouveau_jeu_description" rows="2"
                                        class="w-full rounded-lg p-2 text-light bg-white/5 border border-white/10 text-sm resize-none"
                                        placeholder="Description"></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-xl font-semibold bg-gradient-to-r from-purple-500 to-purple-600 text-white flex items-center justify-center gap-2 hover:opacity-90 transition-all mt-4">
                            <i class="fas fa-paper-plane"></i>
                            <span>Soumettre ma candidature</span>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.innerHTML = input.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        }
        
        // Password strength
        document.getElementById('registerpassword')?.addEventListener('input', function() {
            let s = 0;
            if (this.value.length >= 8) s++;
            if (/[a-z]/.test(this.value) && /[A-Z]/.test(this.value)) s++;
            if (/\d/.test(this.value)) s++;
            if (/[^a-zA-Z\d]/.test(this.value)) s++;
            
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            for (let i = 1; i <= 4; i++) {
                document.getElementById('bar' + i).className = 'h-1 flex-1 rounded-full ' + (i <= s ? colors[s-1] : 'bg-white/10');
            }
            checkMatch();
        });
        
        function checkMatch() {
            const p1 = document.getElementById('registerpassword')?.value || '';
            const p2 = document.getElementById('confirmpassword')?.value || '';
            const msg = document.getElementById('matchMsg');
            if (!msg || !p2) { msg?.classList.add('hidden'); return; }
            msg.classList.remove('hidden');
            msg.textContent = p1 === p2 ? '✓ Correspondent' : '✗ Ne correspondent pas';
            msg.className = 'text-xs mt-1 ' + (p1 === p2 ? 'text-green-400' : 'text-red-400');
        }
        document.getElementById('confirmpassword')?.addEventListener('input', checkMatch);
        
        // Update type selection visual
        function updateTypeSelection() {
            const joueurChecked = document.getElementById('typeJoueur')?.checked;
            const boxJoueur = document.getElementById('boxJoueur');
            const boxCandidat = document.getElementById('boxCandidat');
            const candidatInfo = document.getElementById('candidatInfo');
            
            if (boxJoueur && boxCandidat) {
                if (joueurChecked) {
                    boxJoueur.className = 'p-3 rounded-xl border-2 border-accent bg-accent/10 text-center transition-all';
                    boxCandidat.className = 'p-3 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all';
                } else {
                    boxJoueur.className = 'p-3 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all';
                    boxCandidat.className = 'p-3 rounded-xl border-2 border-purple-500 bg-purple-500/10 text-center transition-all';
                }
            }
            
            if (candidatInfo) {
                candidatInfo.classList.toggle('show', !joueurChecked);
            }
        }
        
        // Init on page load
        document.addEventListener('DOMContentLoaded', updateTypeSelection);
        
        // Toggle nouveau jeu
        function toggleNouveauJeu() {
            const choice = document.querySelector('input[name="jeu_choice"]:checked')?.value;
            const select = document.getElementById('jeuSelect');
            const nouveau = document.getElementById('nouveauJeu');
            const titre = document.getElementById('nouveauTitre');
            
            if (choice === 'nouveau') {
                select.disabled = true;
                nouveau.classList.add('show');
                if (titre) titre.required = true;
            } else {
                select.disabled = false;
                nouveau.classList.remove('show');
                if (titre) titre.required = false;
            }
        }
    </script>
</body>
</html>