<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

$loginerror = '';
$registererror = '';
$registersuccess = '';

// Traitement de la CONNEXION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['loginemail'] ?? '');
    $password = $_POST['loginpassword'] ?? '';

    if (empty($email) || empty($password)) {
        $loginerror = 'Email et mot de passe requis !';
    } else {
        try {
            $stmt = $connexion->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $loginerror = 'Email ou mot de passe incorrect !';
            } else {
                $passwordhash = hash('sha256', $password . $user['salt']);
                if ($passwordhash === $user['mot_de_passe']) {
                    $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                    $_SESSION['useremail'] = $user['email'];
                    $_SESSION['type'] = $user['type'];
                    $_SESSION['userdate'] = $user['date_inscription'];

                    // ✨ NOUVEAU : Au lieu de header('Location: ...'), on ferme la pop-up et recharge la page parente
                    echo "<script>
                        if (window.opener) {
                            // La pop-up a une page parente (index.php)
                            window.opener.location.reload();
                            window.close();
                        } else {
                            // Si pas de page parente, rediriger directement
                            window.location.href = 'index.php';
                        }
                    </script>";
                    exit;
                } else {
                    $loginerror = 'Email ou mot de passe incorrect !';
                }
            }
        } catch (Exception $e) {
            $loginerror = 'Erreur : ' . $e->getMessage();
        }
    }
}

// Traitement de l'INSCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = trim($_POST['registeremail'] ?? '');
    $password = $_POST['registerpassword'] ?? '';
    $confirmpassword = $_POST['registerconfirmpassword'] ?? '';
    $type = $_POST['registertype'] ?? 'joueur';

    if (empty($email) || empty($password)) {
        $registererror = 'Email et mot de passe requis !';
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
                    $registersuccess = 'Compte créé avec succès ! Redirection...';
                    echo "<script>
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    </script>";
                } else {
                    $registererror = 'Erreur lors de la création du compte !';
                }
            }
        } catch (Exception $e) {
            $registererror = 'Erreur : ' . $e->getMessage();
        }
    }
}

$showregister = isset($_GET['register']) ? true : false;
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
        .message-box { padding: 12px 15px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .message-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body class="font-inter">
    <!-- Fond gaming -->
    <div class="gaming-bg">
        <div class="diagonal-lines"></div>
        <div class="diagonal-lines-2"></div>
        <div class="diagonal-lines-3"></div>
    </div>

    <!-- Overlay -->
    <div id="authOverlay" class="fixed inset-0 z-40 backdrop-blur-md modal-backdrop transition-opacity duration-300"></div>

    <!-- Modal Authentification -->
    <div id="authModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div id="authModalContent" class="relative w-full max-w-md my-8 transition-all duration-300">
            <!-- Contenu du modal -->
            <div class="modal-content rounded-2.5rem p-8 md:p-10 backdrop-blur-xl">

                <!-- FORMULAIRE CONNEXION -->
                <?php if (!$showregister): ?>
                    <!-- Header Connexion -->
                    <div class="text-center mb-8">
                        <div class="inline-block mb-5">
                            <div class="rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 shadow-lg shadow-accent/20">
                                <i class="fas fa-lock text-3xl text-accent"></i>
                            </div>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-3 font-orbitron text-light tracking-wide">Connexion</h1>
                        <p class="text-light/60 text-sm md:text-base">Accédez à votre compte GameCrown</p>
                    </div>

                    <!-- Messages Connexion -->
                    <?php if ($loginerror): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($loginerror); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire Connexion -->
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">

                        <div>
                            <label for="loginemail" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" id="loginemail" name="loginemail" required class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30" placeholder="votre@email.com">
                        </div>

                        <div>
                            <label for="loginpassword" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-key text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="loginpassword" name="loginpassword" required class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30" placeholder="Votre mot de passe">
                                <button type="button" id="toggleLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 cursor-pointer">
                            <label for="remember" class="ml-2 text-light/60 text-sm cursor-pointer hover:text-light/80 transition-colors">Se souvenir de moi</label>
                        </div>

                        <button type="submit" class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-dark flex items-center justify-center space-x-3 hover:scale-1.02 active:scale-0.98 transition-all duration-300 mt-6">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Se connecter</span>
                        </button>
                    </form>

                    <div class="flex items-center my-6">
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <span class="px-4 text-white/40 text-sm">ou</span>
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-light/60 text-sm">Pas encore de compte ? 
                            <a href="?register=1" class="text-accent font-medium hover:text-accent-dark transition-colors hover:underline">Créer un compte</a>
                        </p>
                    </div>

                <!-- FORMULAIRE INSCRIPTION -->
                <?php else: ?>
                    <!-- Header Inscription -->
                    <div class="text-center mb-8">
                        <div class="inline-block mb-5">
                            <div class="rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 shadow-lg shadow-accent/20">
                                <i class="fas fa-user-plus text-3xl text-accent"></i>
                            </div>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-3 font-orbitron text-light tracking-wide">Inscription</h1>
                        <p class="text-light/60 text-sm md:text-base">Rejoignez la communauté GameCrown</p>
                    </div>

                    <!-- Messages Inscription -->
                    <?php if ($registererror): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($registererror); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($registersuccess): ?>
                        <div class="message-box message-success">
                            <i class="fas fa-check-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($registersuccess); ?></span>
                        </div>
                    <?php else: ?>
                    <!-- Formulaire Inscription -->
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="register">

                        <div>
                            <label for="registeremail" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" id="registeremail" name="registeremail" required class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30" placeholder="votre@email.com">
                        </div>

                        <div>
                            <label for="registerpassword" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="registerpassword" name="registerpassword" required minlength="8" class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30" placeholder="Minimum 8 caractères">
                                <button type="button" id="toggleRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-2 flex gap-1">
                            <div id="strengthBar1" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar2" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar3" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar4" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                        </div>
                        <p id="strengthText" class="text-xs text-white/40 mt-1"></p>

                        <div>
                            <label for="registerconfirmpassword" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Confirmer le mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="registerconfirmpassword" name="registerconfirmpassword" required class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30" placeholder="Confirmez votre mot de passe">
                                <button type="button" id="toggleConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p id="passwordMatchMessage" class="text-xs mt-1 hidden"></p>
                        </div>

                        <div>
                            <label for="registertype" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-user-tag text-accent mr-2"></i>Type de compte
                            </label>
                            <select id="registertype" name="registertype" required class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300">
                                <option value="joueur" style="background: #0a0a0a;">Joueur</option>
                                <option value="admin" style="background: #0a0a0a;">Administrateur</option>
                                <option value="candidat" style="background: #0a0a0a;">Candidat</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-dark flex items-center justify-center space-x-3 hover:scale-1.02 active:scale-0.98 transition-all duration-300 mt-6">
                            <i class="fas fa-user-plus"></i>
                            <span>Créer mon compte</span>
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="flex items-center my-6">
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <span class="px-4 text-white/40 text-sm">ou</span>
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-light/60 text-sm">Déjà un compte ? 
                            <a href="login.php" class="text-accent font-medium hover:text-accent-dark transition-colors hover:underline">Se connecter</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        
        const loginPasswordInput = document.getElementById('loginpassword');
        const registerPasswordInput = document.getElementById('registerpassword');
        const confirmPasswordInput = document.getElementById('registerconfirmpassword');

        toggleLoginPassword?.addEventListener('click', () => {
            const type = loginPasswordInput.type === 'password' ? 'text' : 'password';
            loginPasswordInput.type = type;
            toggleLoginPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        toggleRegisterPassword?.addEventListener('click', () => {
            const type = registerPasswordInput.type === 'password' ? 'text' : 'password';
            registerPasswordInput.type = type;
            toggleRegisterPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        toggleConfirmPassword?.addEventListener('click', () => {
            const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
            confirmPasswordInput.type = type;
            toggleConfirmPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Password strength
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[a-zA-Z]/) && password.match(/[^a-zA-Z]/)) strength++;
            return strength;
        }

        function updateStrengthIndicator(strength) {
            const bars = [
                document.getElementById('strengthBar1'),
                document.getElementById('strengthBar2'),
                document.getElementById('strengthBar3'),
                document.getElementById('strengthBar4')
            ];
            const strengthText = document.getElementById('strengthText');
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const texts = ['Très faible', 'Faible', 'Moyen', 'Fort'];

            bars.forEach((bar, index) => {
                bar.classList.remove('bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-white/10');
                bar.classList.add(index < strength ? colors[strength - 1] : 'bg-white/10');
            });
            strengthText.textContent = strength > 0 ? texts[strength - 1] : '';
        }s

        registerPasswordInput?.addEventListener('input', () => {
            updateStrengthIndicator(checkPasswordStrength(registerPasswordInput.value));
            checkPasswordMatch();
        });

        function checkPasswordMatch() {
            const password = registerPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const message = document.getElementById('passwordMatchMessage');

            if (confirmPassword.length === 0) {
                message.classList.add('hidden');
                confirmPasswordInput.classList.remove('border-green-500', 'border-red-500');
                return;
            }

            message.classList.remove('hidden');
            if (password === confirmPassword) {
                message.textContent = 'Les mots de passe correspondent';
                message.className = 'text-xs mt-1 text-green-400';
                confirmPasswordInput.classList.remove('border-red-500');
                confirmPasswordInput.classList.add('border-green-500');
            } else {
                message.textContent = 'Les mots de passe ne correspondent pas';
                message.className = 'text-xs mt-1 text-red-400';
                confirmPasswordInput.classList.remove('border-green-500');
                confirmPasswordInput.classList.add('border-red-500');
            }
        }

        confirmPasswordInput?.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>