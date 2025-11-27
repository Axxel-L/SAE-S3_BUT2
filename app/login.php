<?php
session_start();
require_once 'dbconnect.php';

$login_error = '';
$register_error = '';
$register_success = '';

// Traitement de la CONNEXION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = "Email et mot de passe requis !";
    } else {
        try {
            $stmt = $connexion->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $login_error = "Email ou mot de passe incorrect !";
            } else {
                $password_hash = hash('sha256', $password . $user['salt']);
                
                if ($password_hash === $user['mot_de_passe']) {
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['type'];
                    $_SESSION['user_date'] = $user['date_inscription'];
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $login_error = "Email ou mot de passe incorrect !";
                }
            }
        } catch (Exception $e) {
            $login_error = "Erreur : " . $e->getMessage();
        }
    }
}

// Traitement de l'INSCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = trim($_POST['register_email'] ?? '');
    $password = $_POST['register_password'] ?? '';
    $confirm_password = $_POST['register_confirm_password'] ?? '';
    $type = $_POST['register_type'] ?? 'joueur';

    if (empty($email) || empty($password)) {
        $register_error = "Email et mot de passe requis !";
    } elseif ($password !== $confirm_password) {
        $register_error = "Les mots de passe ne correspondent pas !";
    } elseif (strlen($password) < 8) {
        $register_error = "Le mot de passe doit contenir au minimum 8 caractères !";
    } else {
        try {
            $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $register_error = "Cet email est déjà utilisé !";
            } else {
                $salt = bin2hex(random_bytes(16));
                $password_hash = hash('sha256', $password . $salt);

                $stmt = $connexion->prepare("INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) VALUES (?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$email, $password_hash, $salt, $type])) {
                    $register_success = "✓ Compte créé avec succès ! Redirection...";
                    echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $register_error = "Erreur lors de la création du compte !";
                }
            }
        } catch (Exception $e) {
            $register_error = "Erreur : " . $e->getMessage();
        }
    }
}

$show_register = isset($_GET['register']) || $register_error || $register_success;
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#000000',
                        'accent': '#00D4FF',
                        'accent-dark': '#0099CC',
                        'dark': '#0A0A0A',
                        'light': '#F5F5F5',
                        'gray-dark': '#1A1A1A',
                    },
                    fontFamily: {
                        'orbitron': ['Orbitron', 'sans-serif'],
                        'inter': ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .modal-backdrop { background: rgba(0, 0, 0, 0.6); }
        .modal-content {
            background: rgba(10, 10, 10, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 80px rgba(0, 212, 255, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .input-glow:focus { box-shadow: 0 0 20px rgba(0, 212, 255, 0.15); }
        .btn-glow:hover { box-shadow: 0 10px 40px rgba(0, 212, 255, 0.4); }
        .close-btn:hover { box-shadow: 0 0 20px rgba(0, 212, 255, 0.3); }
        .message-box {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message-success {
            background: rgba(74, 222, 128, 0.1);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
        .message-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
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
            
            <!-- Bouton fermer -->
            <button id="closeModal" type="button" class="close-btn absolute -top-3 -right-3 z-10 w-11 h-11 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center text-light hover:bg-accent/20 hover:border-accent/50 transition-all duration-300 group">
                <i class="fas fa-times text-lg group-hover:rotate-90 group-hover:text-accent transition-all duration-300"></i>
            </button>

            <!-- Contenu du modal -->
            <div class="modal-content rounded-[2.5rem] p-8 md:p-10 backdrop-blur-xl">
                
                <!-- ========== FORMULAIRE CONNEXION ========== -->
                <?php if (!$show_register): ?>
                
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
                    <?php if ($login_error): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($login_error); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire Connexion -->
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">
                        
                        <div>
                            <label for="login_email" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" id="login_email" name="login_email" required
                                class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="votre@email.com">
                        </div>
                        
                        <div>
                            <label for="login_password" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="login_password" name="login_password" required
                                    class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                    placeholder="Votre mot de passe">
                                <button type="button" id="toggleLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 cursor-pointer">
                            <label for="remember" class="ml-2 text-light/60 text-sm cursor-pointer">Se souvenir de moi</label>
                        </div>
                        
                        <button type="submit"
                            class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-dark flex items-center justify-center space-x-3 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 mt-6">
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

                <!-- ========== FORMULAIRE INSCRIPTION ========== -->
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
                    <?php if ($register_error): ?>
                        <div class="message-box message-error">
                            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($register_error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($register_success): ?>
                        <div class="message-box message-success">
                            <i class="fas fa-check-circle flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($register_success); ?></span>
                        </div>
                    <?php else: ?>

                    <!-- Formulaire Inscription -->
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="register">
                        
                        <div>
                            <label for="register_email" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-envelope text-accent mr-2"></i>Email
                            </label>
                            <input type="email" id="register_email" name="register_email" required
                                class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="votre@email.com">
                        </div>
                        
                        <div>
                            <label for="register_password" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="register_password" name="register_password" required minlength="8"
                                    class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                    placeholder="Minimum 8 caractères">
                                <button type="button" id="toggleRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="mt-2 flex gap-1">
                                <div id="strengthBar1" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                                <div id="strengthBar2" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                                <div id="strengthBar3" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                                <div id="strengthBar4" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            </div>
                            <p id="strengthText" class="text-xs text-white/40 mt-1"></p>
                        </div>
                        
                        <div>
                            <label for="register_confirm_password" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-lock text-accent mr-2"></i>Confirmer le mot de passe
                            </label>
                            <div class="relative">
                                <input type="password" id="register_confirm_password" name="register_confirm_password" required
                                    class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                    placeholder="Confirmez votre mot de passe">
                                <button type="button" id="toggleConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p id="passwordMatchMessage" class="text-xs mt-1 hidden"></p>
                        </div>

                        <div>
                            <label for="register_type" class="block mb-2 font-medium text-light text-sm">
                                <i class="fas fa-user-tag text-accent mr-2"></i>Type de compte
                            </label>
                            <select id="register_type" name="register_type" required
                                class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300">
                                <option value="joueur" style="background: #0a0a0a;">🎮 Joueur</option>
                                <option value="admin" style="background: #0a0a0a;">👨‍💼 Administrateur</option>
                                <option value="candidat" style="background: #0a0a0a;">🏆 Candidat</option>
                            </select>
                        </div>
                        
                        <button type="submit"
                            class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-dark flex items-center justify-center space-x-3 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 mt-6">
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
        // Elements
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const loginPasswordInput = document.getElementById('login_password');
        const registerPasswordInput = document.getElementById('register_password');
        const confirmPasswordInput = document.getElementById('register_confirm_password');

        // Toggle password visibility
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
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
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
        }

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
                message.textContent = '✓ Les mots de passe correspondent';
                message.className = 'text-xs mt-1 text-green-400';
                confirmPasswordInput.classList.remove('border-red-500');
                confirmPasswordInput.classList.add('border-green-500');
            } else {
                message.textContent = '✗ Les mots de passe ne correspondent pas';
                message.className = 'text-xs mt-1 text-red-400';
                confirmPasswordInput.classList.remove('border-green-500');
                confirmPasswordInput.classList.add('border-red-500');
            }
        }

        confirmPasswordInput?.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>