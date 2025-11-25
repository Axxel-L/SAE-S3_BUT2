<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Connexion</title>
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

    <!-- Section Connexion -->
    <section class="min-h-screen py-20 px-6 flex items-center justify-center relative overflow-hidden" style="padding-top: 10rem;">
        <div class="container mx-auto max-w-md">
            <div class="glass-card rounded-5xl p-10 modern-border fade-in">
                <div class="text-center mb-10">
                    <div class="floating-element inline-block mb-6">
                        <div class="glass-button rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center modern-border">
                            <i class="fas fa-user-lock text-3xl text-accent"></i>
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold mb-4 font-orbitron text-light">Connexion</h1>
                    <p class="text-light/70">Accédez à votre compte GameCrown</p>
                </div>

                <form id="loginForm" class="space-y-6">
                    <div>
                        <label for="email" class="block mb-3 font-medium text-light">
                            <i class="fas fa-envelope text-accent mr-2"></i>Adresse email
                        </label>
                        <input type="email" id="email" name="email" required
                            class="w-full form-input rounded-3xl p-4 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50"
                            placeholder="votre@email.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block mb-3 font-medium text-light">
                            <i class="fas fa-key text-accent mr-2"></i>Mot de passe
                        </label>
                        <input type="password" id="password" name="password" required
                            class="w-full form-input rounded-3xl p-4 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50"
                            placeholder="Votre mot de passe">
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember"
                                class="w-4 h-4 rounded accent-accent bg-white/5 border border-white/10">
                            <label for="remember" class="ml-2 text-sm text-light/70">Se souvenir de moi</label>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full py-4 rounded-3xl font-semibold bg-accent text-white flex items-center justify-center space-x-2 hover:bg-accent/90 transition-colors">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Se connecter</span>
                    </button>
                    
                    <div id="loginMessage" class="mt-4"></div>
                </form>

                <div class="text-center mt-8 pt-6 border-t border-white/10">
                    <p class="text-light/70">Pas encore de compte ? 
                        <a href="#" class="text-accent font-medium hover:text-accent-dark transition-colors">Créer un compte</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="glass-effect-footer py-16 px-6 mt-20 rounded-t-6xl modern-border">
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
                    <a href="#"
                        class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-twitter text-accent"></i>
                    </a>
                    <a href="#"
                        class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-facebook-f text-accent"></i>
                    </a>
                    <a href="#"
                        class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
                        <i class="fab fa-instagram text-accent"></i>
                    </a>
                    <a href="#"
                        class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border">
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
</body>

</html>