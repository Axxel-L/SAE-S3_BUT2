<?php
session_start();
require_once 'dbconnect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $type = $_POST['type'] ?? 'joueur';

    if (empty($email) || empty($password)) {
        $error = "Email et mot de passe requis !";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas !";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au minimum 8 caractères !";
    } else {
        // Vérification si l'email existe déjà
        $stmt = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Cet email est déjà utilisé !";
        } else {
            // Générer salt et hasher le mot de passe
            $salt = bin2hex(random_bytes(16)); // Salt aléatoire
            $password_hash = hash('sha256', $password . $salt); // Hachage SHA-256

            // Insérer dans la BD
            $stmt = $connexion->prepare("INSERT INTO utilisateur (email, mot_de_passe, salt, type, date_inscription) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$email, $password_hash, $salt, $type])) {
                $success = "✓ Compte créé avec succès !";
                $_POST = [];
            } else {
                $error = "Erreur lors de la création du compte !";
            }
        }
    }
}
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
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '3rem',
                        '6xl': '4rem',
                    },
                }
            }
        }
    </script>
    <style>
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.6);
        }
        
        .modal-content {
            background: rgba(10, 10, 10, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 80px rgba(0, 212, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .input-glow:focus {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.15);
        }

        .btn-glow:hover {
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.4);
        }

        .close-btn:hover {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

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
        <div class="award-grid"></div>
        <div class="trophy-pattern"></div>
    </div>

    <!-- Overlay fond flou transparent -->
    <div id="registerOverlay" class="fixed inset-0 z-40 backdrop-blur-md modal-backdrop transition-opacity duration-300"></div>
    <!-- Popup Modal Inscription -->
    <div id="registerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div id="registerModalContent" class="relative w-full max-w-md my-8 transition-all duration-300">
            <div class="modal-content rounded-[2.5rem] p-8 md:p-10 backdrop-blur-xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-block mb-5">
                        <div class="rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 shadow-lg shadow-accent/20">
                            <i class="fas fa-user-plus text-3xl text-accent"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-3 font-orbitron text-light tracking-wide">Inscription</h1>
                    <p class="text-light/60 text-sm md:text-base">Rejoignez la communauté GameCrown</p>
                </div>
                <!-- Messages PHP -->
                <?php if ($error): ?>
                    <div class="message-box message-error">
                        <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message-box message-success">
                        <i class="fas fa-check-circle flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <p class="text-center text-light/60 text-sm mb-4">Redirection vers la connexion...</p>
                    <script>
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    </script>
                <?php else: ?>
                <!-- Formulaire -->
                <form method="POST" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-envelope text-accent mr-2"></i>Adresse email
                        </label>
                        <input type="email" id="email" name="email" required
                            class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                            placeholder="votre@email.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <!-- Mot de passe -->
                    <div>
                        <label for="password" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="8"
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Minimum 8 caractères">
                            <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Indicateur de force du mot de passe -->
                        <div class="mt-2 flex gap-1">
                            <div id="strengthBar1" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar2" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar3" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                            <div id="strengthBar4" class="h-1 flex-1 rounded-full bg-white/10 transition-all duration-300"></div>
                        </div>
                        <p id="strengthText" class="text-xs text-white/40 mt-1"></p>
                    </div>
                    <!-- Confirmation mot de passe -->
                    <div>
                        <label for="confirm_password" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-lock text-accent mr-2"></i>Confirmer le mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Confirmez votre mot de passe">
                            <button type="button" id="togglePasswordConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="passwordMatchMessage" class="text-xs mt-1 hidden"></p>
                    </div>
                    <!-- Type de compte -->
                    <div>
                        <label for="type" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-user-tag text-accent mr-2"></i>Type de compte
                        </label>
                        <select id="type" name="type" required
                            class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300">
                            <option value="joueur" style="background: #0a0a0a; color: #f5f5f5;">🎮 Joueur</option>
                            <option value="admin" style="background: #0a0a0a; color: #f5f5f5;">👨‍💼 Administrateur</option>
                            <option value="candidat" style="background: #0a0a0a; color: #f5f5f5;">🏆 Candidat</option>
                        </select>
                    </div>
                    <!-- Bouton inscription -->
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
            </div>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('confirm_password');
        const closeModal = document.getElementById('closeModal');
        const registerOverlay = document.getElementById('registerOverlay');
        const registerModalContent = document.getElementById('registerModalContent');
        togglePassword?.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePassword.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        });
        togglePasswordConfirm?.addEventListener('click', () => {
            const type = passwordConfirmInput.type === 'password' ? 'text' : 'password';
            passwordConfirmInput.type = type;
            togglePasswordConfirm.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        });

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

        passwordInput?.addEventListener('input', () => {
            updateStrengthIndicator(checkPasswordStrength(passwordInput.value));
            checkPasswordMatch();
        });

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = passwordConfirmInput.value;
            const message = document.getElementById('passwordMatchMessage');
            if (confirmPassword.length === 0) {
                message.classList.add('hidden');
                passwordConfirmInput.classList.remove('border-green-500', 'border-red-500');
                return;
            }
            message.classList.remove('hidden');
            if (password === confirmPassword) {
                message.textContent = '✓ Les mots de passe correspondent';
                message.className = 'text-xs mt-1 text-green-400';
                passwordConfirmInput.classList.remove('border-red-500');
                passwordConfirmInput.classList.add('border-green-500');
            } else {
                message.textContent = '✗ Les mots de passe ne correspondent pas';
                message.className = 'text-xs mt-1 text-red-400';
                passwordConfirmInput.classList.remove('border-green-500');
                passwordConfirmInput.classList.add('border-red-500');
            }
        }
        passwordConfirmInput?.addEventListener('input', checkPasswordMatch);

        function closeRegisterPopup() {
            registerOverlay.style.opacity = '0';
            registerModalContent.style.opacity = '0';
            setTimeout(() => {
                window.history.back();
            }, 300);
        }
        closeModal?.addEventListener('click', closeRegisterPopup);
        registerOverlay?.addEventListener('click', closeRegisterPopup);
        registerModalContent?.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeRegisterPopup();
            }
        });
    </script>
</body>

</html>