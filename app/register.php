<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Inscription</title>
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
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '3rem',
                        '6xl': '4rem',
                    },
                    animation: {
                        'float': 'float 8s ease-in-out infinite',
                        'fade-in': 'fade-in 1s ease-out',
                        'glow': 'glow 3s ease-in-out infinite',
                        'slide': 'slide 40s linear infinite',
                        'pulse-slow': 'pulse-slow 4s ease-in-out infinite',
                        'trophy-glow': 'trophy-glow 6s ease-in-out infinite',
                        'vote-pulse': 'vote-pulse 2s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        /* Styles pour le modal */
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
        <div class="controller-icons" id="controller-icons"></div>
        <div class="vote-aura" style="top: 10%; left: 5%;"></div>
        <div class="vote-aura" style="top: 60%; left: 80%;"></div>
        <div class="vote-aura" style="top: 80%; left: 20%;"></div>
    </div>

    <!-- Overlay fond flou transparent -->
    <div id="registerOverlay" class="fixed inset-0 z-40 backdrop-blur-md modal-backdrop transition-opacity duration-300"></div>

    <!-- Popup Modal Inscription -->
    <div id="registerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div id="registerModalContent" class="relative w-full max-w-md my-8 transition-all duration-300">
            
            <!-- Bouton fermer -->
            <button id="closeModal" class="close-btn absolute -top-3 -right-3 z-10 w-11 h-11 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center text-light hover:bg-accent/20 hover:border-accent/50 transition-all duration-300 group">
                <i class="fas fa-times text-lg group-hover:rotate-90 group-hover:text-accent transition-all duration-300"></i>
            </button>

            <!-- Contenu du popup -->
            <div class="modal-content rounded-[2.5rem] p-8 md:p-10 backdrop-blur-xl">
                
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-block mb-5 animate-float">
                        <div class="rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 shadow-lg shadow-accent/20">
                            <i class="fas fa-user-plus text-3xl text-accent"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-3 font-orbitron text-light tracking-wide">Inscription</h1>
                    <p class="text-light/60 text-sm md:text-base">Rejoignez la communauté GameCrown</p>
                </div>

                <!-- Formulaire -->
                <form id="registerForm" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-envelope text-accent mr-2"></i>Adresse email
                        </label>
                        <input type="email" id="email" name="email" required
                            class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                            placeholder="votre@email.com">
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
                        <label for="passwordConfirm" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-lock text-accent mr-2"></i>Confirmer le mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="passwordConfirm" name="password_confirm" required
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Confirmez votre mot de passe">
                            <button type="button" id="togglePasswordConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="passwordMatchMessage" class="text-xs mt-1 hidden"></p>
                    </div>
                    
                    <!-- Bouton inscription -->
                    <button type="submit"
                        class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-white flex items-center justify-center space-x-3 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 mt-6">
                        <i class="fas fa-user-plus"></i>
                        <span>Créer mon compte</span>
                    </button>
                    
                    <div id="registerMessage" class="mt-4"></div>
                </form>

                <!-- Séparateur -->
                <div class="flex items-center my-6">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    <span class="px-4 text-white/40 text-sm">ou</span>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                </div>

                <!-- Lien connexion -->
                <div class="text-center">
                    <p class="text-light/60 text-sm">Déjà un compte ? 
                        <a href="login.php" class="text-accent font-medium hover:text-accent-dark transition-colors hover:underline">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer (visible en arrière-plan) -->
    <footer class="glass-effect-footer py-16 px-6 mt-20 rounded-t-6xl modern-border opacity-30">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-8 md:mb-0">
                    <div class="glass-button p-2 rounded-3xl">
                        <img src="../assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
                    </div>
                    <span class="logo-text">
                        GAME<span class="accent-gradient">CROWN</span>
                    </span>
                </div>

                <div class="flex space-x-5">
                    <a href="#" class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-twitter text-accent"></i>
                    </a>
                    <a href="#" class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-facebook-f text-accent"></i>
                    </a>
                    <a href="#" class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-instagram text-accent"></i>
                    </a>
                    <a href="#" class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-youtube text-accent"></i>
                    </a>
                </div>
            </div>

            <div class="separator mt-10"></div>

            <div class="text-center text-base text-light/70">
                <p>&copy;2025 GameCrown. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Script pour gérer le popup -->
    <script>
        const registerOverlay = document.getElementById('registerOverlay');
        const registerModal = document.getElementById('registerModal');
        const registerModalContent = document.getElementById('registerModalContent');
        const closeModal = document.getElementById('closeModal');
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('passwordConfirm');

        // Fonction pour fermer le popup avec animation
        function closeRegisterPopup() {
            registerOverlay.style.transition = 'opacity 0.3s ease-out';
            registerOverlay.style.opacity = '0';
            
            registerModalContent.style.transition = 'all 0.3s ease-out';
            registerModalContent.style.opacity = '0';
            registerModalContent.style.transform = 'scale(0.95) translateY(-20px)';
            
            setTimeout(() => {
                registerOverlay.style.display = 'none';
                registerModal.style.display = 'none';
            }, 300);
        }

        // Événement de fermeture sur le bouton croix
        closeModal.addEventListener('click', closeRegisterPopup);
        
        // Fermeture au clic sur l'overlay
        registerOverlay.addEventListener('click', closeRegisterPopup);

        // Empêcher la fermeture quand on clique sur le popup lui-même
        registerModalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeRegisterPopup();
            }
        });

        // Toggle affichage mot de passe
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePassword.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        });

        // Toggle affichage confirmation mot de passe
        togglePasswordConfirm.addEventListener('click', () => {
            const type = passwordConfirmInput.type === 'password' ? 'text' : 'password';
            passwordConfirmInput.type = type;
            togglePasswordConfirm.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        });

        // Vérification de la force du mot de passe
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
            strengthText.className = strength > 0 ? 'text-xs mt-1 ' + colors[strength - 1].replace('bg-', 'text-') : 'text-xs mt-1 text-white/40';
        }

        passwordInput.addEventListener('input', () => {
            updateStrengthIndicator(checkPasswordStrength(passwordInput.value));
            checkPasswordMatch();
        });

        // Vérification correspondance mots de passe
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

        passwordConfirmInput.addEventListener('input', checkPasswordMatch);

        // Validation du formulaire
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = passwordInput.value;
            const confirmPassword = passwordConfirmInput.value;
            const messageDiv = document.getElementById('registerMessage');
            
            // Vérifications
            if (password !== confirmPassword) {
                messageDiv.innerHTML = '<p class="text-red-400 text-sm text-center"><i class="fas fa-exclamation-circle mr-2"></i>Les mots de passe ne correspondent pas</p>';
                return;
            }
            
            if (password.length < 8) {
                messageDiv.innerHTML = '<p class="text-red-400 text-sm text-center"><i class="fas fa-exclamation-circle mr-2"></i>Le mot de passe doit contenir au moins 8 caractères</p>';
                return;
            }
            
            // Si tout est OK, afficher un message de succès
            messageDiv.innerHTML = '<p class="text-green-400 text-sm text-center"><i class="fas fa-check-circle mr-2"></i>Compte créé avec succès !</p>';
            
            // Ici vous pouvez ajouter l'envoi des données au serveur
            // fetch('/api/register', { ... })
        });
    </script>
</body>

</html>