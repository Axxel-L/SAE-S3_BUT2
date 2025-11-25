<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Édition Épurée</title>
    <script src="http://cdn.agence-prestige-numerique.fr/tailwindcss/3.4.17.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="./assets/css/index.css">
    <link rel="icon" type="image/png" href="./assets/img/logo.png">
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
        /* Styles pour les modals */
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
    <!-- Navbar -->
    <nav class="glass-effect-nav fixed top-0 left-0 right-0 z-50 mx-2 mt-4 px-2 py-3 lg:mx-4 lg:px-4 lg:py-4 rounded-6xl">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="logo-container">
                <div class="glass-button p-2 rounded-3xl">
                    <img src="./assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
                </div>
                <span class="logo-text">
                    GAME<span class="accent-gradient">CROWN</span>
                </span>
            </div>

            <!-- Menu Desktop -->
            <div class="nav-desktop flex items-center gap-2">
                <div class="flex items-center gap-2">
                    <a href="#accueil" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-home text-accent"></i>
                        <span>Accueil</span>
                    </a>
                    <a href="#presentation" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-info-circle text-accent"></i>
                        <span>Présentation</span>
                    </a>
                    <a href="#scrutin" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-award text-accent"></i>
                        <span>Mode de scrutin</span>
                    </a>
                    <a href="#contact" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-envelope text-accent"></i>
                        <span>Contact</span>
                    </a>
                </div>
                <div class="h-8 w-px bg-accent/30 mx-2"></div>
                <!-- Bouton Connexion Desktop -->
                <button id="openLoginBtn" class="nav-link glass-button px-6 py-3 rounded-3xl font-medium flex items-center gap-3 text-sm lg:text-base bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-300 cursor-pointer">
                    <i class="fa-solid fa-user text-accent text-lg"></i>
                    <span class="text-accent font-semibold">Connexion</span>
                </button>
            </div>

            <!-- Bouton menu mobile -->
            <button id="mobile-menu-btn" class="mobile-menu-button glass-button p-3 rounded-3xl">
                <div class="hamburger flex flex-col gap-1.5 w-6 h-6 justify-center items-center">
                    <i class="fa-solid fa-bars fa-2xl" style="color: #00d4ff;"></i>
                </div>
            </button>
        </div>

        <!-- Menu Mobile -->
        <div id="mobile-menu" class="mobile-menu mt-4">
            <div class="flex flex-col gap-3 pb-4">
                <a href="#accueil" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-home text-accent"></i>
                    <span>Accueil</span>
                </a>
                <a href="#presentation" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-info-circle text-accent"></i>
                    <span>Présentation</span>
                </a>
                <a href="#scrutin" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-award text-accent"></i>
                    <span>Mode de scrutin</span>
                </a>
                <a href="#contact" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-envelope text-accent"></i>
                    <span>Contact</span>
                </a>
                <div class="h-px bg-accent/30 my-2"></div>
                <!-- Bouton Connexion Mobile -->
                <button id="openLoginBtnMobile" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 cursor-pointer">
                    <i class="fas fa-sign-in-alt text-accent"></i>
                    <span class="text-accent font-semibold">Se connecter</span>
                </button>
            </div>
        </div>
    </nav>

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

    <!-- ==================== MODAL CONNEXION ==================== -->
    <div id="loginOverlay" class="fixed inset-0 z-[100] backdrop-blur-md modal-backdrop hidden opacity-0 transition-opacity duration-300"></div>
    <div id="loginModal" class="fixed inset-0 z-[101] flex items-center justify-center p-4 overflow-y-auto hidden">
        <div id="loginModalContent" class="relative w-full max-w-md my-8 opacity-0 transform scale-95 -translate-y-5 transition-all duration-300">
            <!-- Bouton fermer -->
            <button id="closeLoginModal" class="close-btn absolute -top-3 -right-3 z-10 w-11 h-11 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center text-light hover:bg-accent/20 hover:border-accent/50 transition-all duration-300 group">
                <i class="fas fa-times text-lg group-hover:rotate-90 group-hover:text-accent transition-all duration-300"></i>
            </button>

            <div class="modal-content rounded-[2.5rem] p-8 md:p-10 backdrop-blur-xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-block mb-5 animate-float">
                        <div class="rounded-3xl p-4 mx-auto w-20 h-20 flex items-center justify-center bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 shadow-lg shadow-accent/20">
                            <i class="fas fa-user-lock text-3xl text-accent"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-3 font-orbitron text-light tracking-wide">Connexion</h1>
                    <p class="text-light/60 text-sm md:text-base">Accédez à votre compte GameCrown</p>
                </div>

                <!-- Formulaire -->
                <form id="loginForm" class="space-y-5">
                    <div>
                        <label for="loginEmail" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-envelope text-accent mr-2"></i>Adresse email
                        </label>
                        <input type="email" id="loginEmail" name="email" required
                            class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                            placeholder="votre@email.com">
                    </div>
                    
                    <div>
                        <label for="loginPassword" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-key text-accent mr-2"></i>Mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="loginPassword" name="password" required
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Votre mot de passe">
                            <button type="button" id="toggleLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded accent-accent bg-white/5 border border-white/10 cursor-pointer">
                            <label for="remember" class="ml-2 text-light/60 cursor-pointer hover:text-light/80 transition-colors">Se souvenir de moi</label>
                        </div>
                        <a href="#" class="text-accent hover:text-accent-dark transition-colors hover:underline">Mot de passe oublié ?</a>
                    </div>
                    
                    <button type="submit" class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-white flex items-center justify-center space-x-3 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 mt-6">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Se connecter</span>
                    </button>
                    
                    <div id="loginMessage" class="mt-4"></div>
                </form>

                <!-- Séparateur -->
                <div class="flex items-center my-6">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    <span class="px-4 text-white/40 text-sm">ou</span>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                </div>

                <!-- Lien inscription -->
                <div class="text-center">
                    <p class="text-light/60 text-sm">Pas encore de compte ? 
                        <button type="button" id="switchToRegister" class="text-accent font-medium hover:text-accent-dark transition-colors hover:underline">Créer un compte</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================== FIN MODAL CONNEXION ==================== -->

    <!-- ==================== MODAL INSCRIPTION ==================== -->
    <div id="registerOverlay" class="fixed inset-0 z-[100] backdrop-blur-md modal-backdrop hidden opacity-0 transition-opacity duration-300"></div>
    <div id="registerModal" class="fixed inset-0 z-[101] flex items-center justify-center p-4 overflow-y-auto hidden">
        <div id="registerModalContent" class="relative w-full max-w-md my-8 opacity-0 transform scale-95 -translate-y-5 transition-all duration-300">
            <!-- Bouton fermer -->
            <button id="closeRegisterModal" class="close-btn absolute -top-3 -right-3 z-10 w-11 h-11 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center text-light hover:bg-accent/20 hover:border-accent/50 transition-all duration-300 group">
                <i class="fas fa-times text-lg group-hover:rotate-90 group-hover:text-accent transition-all duration-300"></i>
            </button>

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
                        <label for="registerEmail" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-envelope text-accent mr-2"></i>Adresse email
                        </label>
                        <input type="email" id="registerEmail" name="email" required
                            class="input-glow w-full rounded-2xl p-4 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                            placeholder="votre@email.com">
                    </div>
                    
                    <!-- Mot de passe -->
                    <div>
                        <label for="registerPassword" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-lock text-accent mr-2"></i>Mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="registerPassword" name="password" required minlength="8"
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Minimum 8 caractères">
                            <button type="button" id="toggleRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Indicateur de force -->
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
                        <label for="registerPasswordConfirm" class="block mb-2 font-medium text-light text-sm">
                            <i class="fas fa-lock text-accent mr-2"></i>Confirmer le mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" id="registerPasswordConfirm" name="password_confirm" required
                                class="input-glow w-full rounded-2xl p-4 pr-12 text-light bg-white/5 backdrop-blur-sm border border-white/10 focus:border-accent/50 focus:outline-none transition-all duration-300 placeholder-white/30"
                                placeholder="Confirmez votre mot de passe">
                            <button type="button" id="toggleRegisterPasswordConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-accent transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="passwordMatchMessage" class="text-xs mt-1 hidden"></p>
                    </div>
                    
                    <!-- Bouton inscription -->
                    <button type="submit" class="btn-glow w-full py-4 rounded-2xl font-semibold bg-gradient-to-r from-accent to-accent-dark text-white flex items-center justify-center space-x-3 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 mt-6">
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
                        <button type="button" id="switchToLogin" class="text-accent font-medium hover:text-accent-dark transition-colors hover:underline">Se connecter</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================== FIN MODAL INSCRIPTION ==================== -->

    <!-- Section Hero -->
    <section id="accueil" class="py-32 px-6 hero-bg relative overflow-hidden" style="padding-top: 10rem;">
        <div class="container mx-auto text-center relative z-10">
            <div class="floating-element inline-block mb-10">
                <div class="glass-card rounded-full p-8 w-40 h-40 mx-auto flex items-center justify-center modern-border">
                    <i class="fas fa-trophy text-6xl accent-gradient trophy-icon"></i>
                </div>
            </div>

            <h1 class="text-7xl md:text-9xl font-bold mb-8 font-orbitron tracking-tight fade-in">
                <span class="accent-gradient glow-text">GAME</span><br>
                <span class="text-light">CROWN</span>
            </h1>
            <div class="typewriter text-2xl md:text-3xl max-w-3xl mx-auto mb-16 text-light/80">
                Célébrons l'<span class="text-accent font-medium">excellence</span> et l'<span class="text-accent font-medium">innovation</span> du jeu vidéo
            </div>
            <div class="flex flex-col md:flex-row justify-center gap-6">
                <a href="#presentation" class="glass-button px-12 py-5 rounded-3xl text-xl font-medium flex items-center justify-center space-x-3 modern-border">
                    <i class="fas fa-gamepad text-accent"></i>
                    <span class="text-light">Découvrir les nominés</span>
                </a>
                <a href="#contact" class="glass-button px-12 py-5 rounded-3xl text-xl font-medium flex items-center justify-center space-x-3 modern-border">
                    <i class="fas fa-comment-dots text-accent"></i>
                    <span class="text-light">Nous contacter</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Section Présentation -->
    <section id="presentation" class="py-28 px-6">
        <div class="container mx-auto">
            <h2 class="text-5xl font-bold text-center mb-20 font-orbitron section-title text-light">Présentation du site</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div class="glass-card rounded-5xl p-12 modern-border">
                    <div class="flex items-start mb-8">
                        <div class="glass-button rounded-3xl p-4 mr-6 modern-border">
                            <i class="fas fa-bullseye text-2xl text-accent"></i>
                        </div>
                        <div>
                            <h3 class="text-3xl font-bold font-orbitron text-light mb-2">Notre mission</h3>
                            <div class="w-20 h-1 bg-accent rounded-full"></div>
                        </div>
                    </div>
                    <p class="text-xl mb-6 text-light/80 leading-relaxed">
                        Créer une plateforme technique innovante dédiée aux organisateurs d'événements gaming, leur offrant une solution complète et fiable pour gérer des procédures de vote électronique.
                    </p>
                    <div class="space-y-4 mt-8">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-shield-alt text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Système de vote sécurisé et transparent</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-cogs text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Adapté aux besoins spécifiques du secteur gaming</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-users text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Pour associations, médias, festivals et communautés</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-5xl p-12 modern-border">
                    <div class="flex items-start mb-8">
                        <div class="glass-button rounded-3xl p-4 mr-6 modern-border">
                            <i class="fas fa-globe text-2xl text-accent"></i>
                        </div>
                        <div>
                            <h3 class="text-3xl font-bold font-orbitron text-light mb-2">Le contexte</h3>
                            <div class="w-20 h-1 bg-secondary rounded-full"></div>
                        </div>
                    </div>
                    <p class="text-xl mb-6 text-light/80 leading-relaxed">
                        Face à la digitalisation accélérée du secteur, les organisateurs d'événements gaming recherchent des outils spécialisés capables de répondre à leurs exigences uniques.
                    </p>
                    <div class="space-y-4 mt-8">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-virus text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Digitalisation accélérée post-crise sanitaire</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-layer-group text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Gestion des spécificités complexes du vote gaming</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-broadcast-tower text-accent text-sm"></i>
                            </div>
                            <p class="text-light/80">Diffusion des résultats en direct et en temps réel</p>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <div class="separator container mx-auto"></div>

    <!-- Section Mode de scrutin -->
    <section id="scrutin" class="py-28 px-6">
        <div class="container mx-auto">
            <h2 class="text-5xl font-bold text-center mb-20 font-orbitron section-title text-light">Mode de scrutin</h2>

            <div class="glass-card rounded-5xl p-12 max-w-5xl mx-auto modern-border fade-in">
                <div class="flex items-center mb-10">
                    <div class="glass-button rounded-3xl p-4 mr-6 modern-border">
                        <i class="fas fa-vote-yea text-2xl text-accent"></i>
                    </div>
                    <h3 class="text-3xl font-bold font-orbitron text-light">Notre système de vote en deux étapes</h3>
                </div>
                <p class="text-xl mb-8 text-light/80 leading-relaxed">
                    Notre système de vote se déroule en deux phases distinctes qui permettent de déterminer d'abord les meilleurs jeux par catégorie, puis le jeu ultime de l'année.
                </p>

                <div class="mb-12">
                    <h4 class="text-2xl font-bold mb-6 font-orbitron text-light border-b border-accent/30 pb-2">Étape 1 : Vote par catégorie</h4>
                    <p class="text-xl mb-6 text-light/80 leading-relaxed">
                        Lors de nos événements, les utilisateurs votent pour leurs jeux favoris dans chaque catégorie. Le processus est simple et intuitif :
                    </p>
                    <ul class="text-lg text-light/80 mb-8 space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check text-accent mt-1 mr-3"></i>
                            <span>Parcourez les différentes catégories (meilleur gameplay, graphismes, narration, etc.)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-accent mt-1 mr-3"></i>
                            <span>Sélectionnez votre jeu préféré dans chaque catégorie</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-accent mt-1 mr-3"></i>
                            <span>Cliquez sur le bouton "Voter" pour valider vos choix</span>
                        </li>
                    </ul>
                    <p class="text-lg text-light/80 italic bg-gradient-to-r from-accent/10 to-transparent p-4 rounded-3xl">
                        Un seul jeu est élu par catégorie et devient finaliste pour la grande finale.
                    </p>
                </div>

                <div class="mb-12">
                    <h4 class="text-2xl font-bold mb-6 font-orbitron text-light border-b border-accent/30 pb-2">Étape 2 : Élection du jeu de l'année</h4>
                    <p class="text-xl mb-6 text-light/80 leading-relaxed">
                        Une fois les catégories déterminées, la seconde phase de vote débute pour élire le meilleur jeu de l'année :
                    </p>
                    <ul class="text-lg text-light/80 mb-8 space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-crown text-accent mt-1 mr-3"></i>
                            <span>Tous les jeux élus dans chaque catégorie deviennent finalistes</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-crown text-accent mt-1 mr-3"></i>
                            <span>Les utilisateurs votent pour LE meilleur jeu parmi ces finalistes</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-crown text-accent mt-1 mr-3"></i>
                            <span>Le jeu qui remporte le plus de voix est sacré "Jeu de l'Année"</span>
                        </li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-12">
                    <div class="text-center">
                        <div class="glass-button rounded-3xl p-6 mb-6 mx-auto w-24 h-24 flex items-center justify-center modern-border">
                            <i class="fas fa-tags text-3xl text-accent"></i>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 font-orbitron text-light">Vote par catégorie</h4>
                        <p class="text-lg text-light/80">Sélectionnez votre jeu préféré dans chaque catégorie lors de la première étape.</p>
                    </div>
                    <div class="text-center">
                        <div class="glass-button rounded-3xl p-6 mb-6 mx-auto w-24 h-24 flex items-center justify-center modern-border">
                            <i class="fas fa-trophy text-3xl text-accent"></i>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 font-orbitron text-light">Finalistes</h4>
                        <p class="text-lg text-light/80">Les gagnants de chaque catégorie deviennent finalistes pour le titre suprême.</p>
                    </div>
                    <div class="text-center">
                        <div class="glass-button rounded-3xl p-6 mb-6 mx-auto w-24 h-24 flex items-center justify-center modern-border">
                            <i class="fas fa-award text-3xl text-accent"></i>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 font-orbitron text-light">Vote final</h4>
                        <p class="text-lg text-light/80">Élisez le meilleur jeu de l'année parmi les finalistes lors de la seconde étape.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="separator container mx-auto"></div>

    <!-- Section Contact -->
    <section id="contact" class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <h2 class="text-4xl font-bold text-center mb-16 font-orbitron text-light">Contactez-nous</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <div class="flex items-center mb-6">
                        <div class="glass-button rounded-3xl p-3 mr-4 modern-border">
                            <i class="fas fa-envelope text-accent"></i>
                        </div>
                        <h3 class="text-2xl font-bold font-orbitron text-light">Formulaire de contact</h3>
                    </div>

                    <form id="contactForm" class="space-y-6">
                        <div>
                            <label for="name" class="block mb-2 font-medium text-light">Nom complet *</label>
                            <input type="text" id="name" name="name" required
                                class="w-full form-input rounded-3xl p-3 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50">
                        </div>
                        <div>
                            <label for="contactEmail" class="block mb-2 font-medium text-light">Adresse email *</label>
                            <input type="email" id="contactEmail" name="email" required
                                class="w-full form-input rounded-3xl p-3 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50">
                        </div>
                        <div>
                            <label for="subject" class="block mb-2 font-medium text-light">Sujet *</label>
                            <input type="text" id="subject" name="subject" required
                                class="w-full form-input rounded-3xl p-3 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50">
                        </div>
                        <div>
                            <label for="message" class="block mb-2 font-medium text-light">Message *</label>
                            <textarea id="message" name="message" rows="4" required
                                class="w-full form-input rounded-3xl p-3 text-light/90 bg-white/5 border border-white/10 focus:border-accent/50"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full py-3 rounded-3xl font-semibold bg-accent text-white flex items-center justify-center space-x-2 hover:bg-accent/90 transition-colors">
                            <i class="fas fa-paper-plane"></i>
                            <span>Envoyer le message</span>
                        </button>
                    </form>

                    <div id="formMessage" class="mt-4"></div>
                </div>

                <!-- Informations contact -->
                <div class="glass-card rounded-4xl p-8 modern-border">
                    <div class="flex items-center mb-6">
                        <div class="glass-button rounded-3xl p-3 mr-4 modern-border">
                            <i class="fas fa-address-card text-accent"></i>
                        </div>
                        <h3 class="text-2xl font-bold font-orbitron text-light">Informations</h3>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="glass-button rounded-3xl p-2 mr-4 modern-border">
                                <i class="fas fa-map-marker-alt text-accent text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-light mb-1">Adresse</h4>
                                <p class="text-light/80">11 Rue de l'Université, <br>88100 Saint-Dié-des-Vosges, France</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="glass-button rounded-3xl p-2 mr-4 modern-border">
                                <i class="fas fa-phone text-accent text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-light mb-1">Téléphone</h4>
                                <p class="text-light/80">+33 6 00 00 00 00</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="glass-button rounded-3xl p-2 mr-4 modern-border">
                                <i class="fas fa-envelope text-accent text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-light mb-1">Email</h4>
                                <p class="text-light/80">contact@gamecrown.fr</p>
                            </div>
                        </div>
                    </div>
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
                        <img src="./assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
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

    <script src="./assets/js/index.js"></script>

    <!-- Script pour les modals Login et Register -->
    <script>
        // ==================== ÉLÉMENTS DU DOM ====================
        // Login
        const loginOverlay = document.getElementById('loginOverlay');
        const loginModal = document.getElementById('loginModal');
        const loginModalContent = document.getElementById('loginModalContent');
        const closeLoginModal = document.getElementById('closeLoginModal');
        const openLoginBtn = document.getElementById('openLoginBtn');
        const openLoginBtnMobile = document.getElementById('openLoginBtnMobile');
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const loginPassword = document.getElementById('loginPassword');
        const switchToRegister = document.getElementById('switchToRegister');

        // Register
        const registerOverlay = document.getElementById('registerOverlay');
        const registerModal = document.getElementById('registerModal');
        const registerModalContent = document.getElementById('registerModalContent');
        const closeRegisterModal = document.getElementById('closeRegisterModal');
        const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
        const toggleRegisterPasswordConfirm = document.getElementById('toggleRegisterPasswordConfirm');
        const registerPassword = document.getElementById('registerPassword');
        const registerPasswordConfirm = document.getElementById('registerPasswordConfirm');
        const switchToLogin = document.getElementById('switchToLogin');

        // ==================== FONCTIONS LOGIN ====================
        function openLoginPopup() {
            loginOverlay.classList.remove('hidden');
            loginModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            requestAnimationFrame(() => {
                loginOverlay.classList.remove('opacity-0');
                loginOverlay.classList.add('opacity-100');
                loginModalContent.classList.remove('opacity-0', 'scale-95', '-translate-y-5');
                loginModalContent.classList.add('opacity-100', 'scale-100', 'translate-y-0');
            });

            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
            }
        }

        function closeLoginPopup() {
            loginOverlay.classList.remove('opacity-100');
            loginOverlay.classList.add('opacity-0');
            loginModalContent.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            loginModalContent.classList.add('opacity-0', 'scale-95', '-translate-y-5');
            
            setTimeout(() => {
                loginOverlay.classList.add('hidden');
                loginModal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // ==================== FONCTIONS REGISTER ====================
        function openRegisterPopup() {
            registerOverlay.classList.remove('hidden');
            registerModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            requestAnimationFrame(() => {
                registerOverlay.classList.remove('opacity-0');
                registerOverlay.classList.add('opacity-100');
                registerModalContent.classList.remove('opacity-0', 'scale-95', '-translate-y-5');
                registerModalContent.classList.add('opacity-100', 'scale-100', 'translate-y-0');
            });
        }

        function closeRegisterPopup() {
            registerOverlay.classList.remove('opacity-100');
            registerOverlay.classList.add('opacity-0');
            registerModalContent.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            registerModalContent.classList.add('opacity-0', 'scale-95', '-translate-y-5');
            
            setTimeout(() => {
                registerOverlay.classList.add('hidden');
                registerModal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // ==================== SWITCH ENTRE MODALS ====================
        function switchFromLoginToRegister() {
            loginOverlay.classList.remove('opacity-100');
            loginOverlay.classList.add('opacity-0');
            loginModalContent.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            loginModalContent.classList.add('opacity-0', 'scale-95', '-translate-y-5');
            
            setTimeout(() => {
                loginOverlay.classList.add('hidden');
                loginModal.classList.add('hidden');
                openRegisterPopup();
            }, 300);
        }

        function switchFromRegisterToLogin() {
            registerOverlay.classList.remove('opacity-100');
            registerOverlay.classList.add('opacity-0');
            registerModalContent.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            registerModalContent.classList.add('opacity-0', 'scale-95', '-translate-y-5');
            
            setTimeout(() => {
                registerOverlay.classList.add('hidden');
                registerModal.classList.add('hidden');
                openLoginPopup();
            }, 300);
        }

        // ==================== ÉVÉNEMENTS LOGIN ====================
        openLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginPopup();
        });

        openLoginBtnMobile.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginPopup();
        });

        closeLoginModal.addEventListener('click', closeLoginPopup);
        loginOverlay.addEventListener('click', closeLoginPopup);
        loginModalContent.addEventListener('click', (e) => e.stopPropagation());

        toggleLoginPassword.addEventListener('click', () => {
            const type = loginPassword.type === 'password' ? 'text' : 'password';
            loginPassword.type = type;
            toggleLoginPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        switchToRegister.addEventListener('click', switchFromLoginToRegister);

        // ==================== ÉVÉNEMENTS REGISTER ====================
        closeRegisterModal.addEventListener('click', closeRegisterPopup);
        registerOverlay.addEventListener('click', closeRegisterPopup);
        registerModalContent.addEventListener('click', (e) => e.stopPropagation());

        toggleRegisterPassword.addEventListener('click', () => {
            const type = registerPassword.type === 'password' ? 'text' : 'password';
            registerPassword.type = type;
            toggleRegisterPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        toggleRegisterPasswordConfirm.addEventListener('click', () => {
            const type = registerPasswordConfirm.type === 'password' ? 'text' : 'password';
            registerPasswordConfirm.type = type;
            toggleRegisterPasswordConfirm.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        switchToLogin.addEventListener('click', switchFromRegisterToLogin);

        // ==================== VALIDATION MOT DE PASSE ====================
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

        registerPassword.addEventListener('input', () => {
            updateStrengthIndicator(checkPasswordStrength(registerPassword.value));
            checkPasswordMatch();
        });

        function checkPasswordMatch() {
            const password = registerPassword.value;
            const confirmPassword = registerPasswordConfirm.value;
            const message = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword.length === 0) {
                message.classList.add('hidden');
                registerPasswordConfirm.classList.remove('border-green-500', 'border-red-500');
                return;
            }
            
            message.classList.remove('hidden');
            
            if (password === confirmPassword) {
                message.textContent = '✓ Les mots de passe correspondent';
                message.className = 'text-xs mt-1 text-green-400';
                registerPasswordConfirm.classList.remove('border-red-500');
                registerPasswordConfirm.classList.add('border-green-500');
            } else {
                message.textContent = '✗ Les mots de passe ne correspondent pas';
                message.className = 'text-xs mt-1 text-red-400';
                registerPasswordConfirm.classList.remove('border-green-500');
                registerPasswordConfirm.classList.add('border-red-500');
            }
        }

        registerPasswordConfirm.addEventListener('input', checkPasswordMatch);

        // Validation formulaire inscription
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const password = registerPassword.value;
            const confirmPassword = registerPasswordConfirm.value;
            const messageDiv = document.getElementById('registerMessage');
            
            if (password !== confirmPassword) {
                messageDiv.innerHTML = '<p class="text-red-400 text-sm text-center"><i class="fas fa-exclamation-circle mr-2"></i>Les mots de passe ne correspondent pas</p>';
                return;
            }
            
            if (password.length < 8) {
                messageDiv.innerHTML = '<p class="text-red-400 text-sm text-center"><i class="fas fa-exclamation-circle mr-2"></i>Le mot de passe doit contenir au moins 8 caractères</p>';
                return;
            }
            
            messageDiv.innerHTML = '<p class="text-green-400 text-sm text-center"><i class="fas fa-check-circle mr-2"></i>Compte créé avec succès !</p>';
        });

        // ==================== TOUCHE ESCAPE ====================
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (!loginModal.classList.contains('hidden')) closeLoginPopup();
                if (!registerModal.classList.contains('hidden')) closeRegisterPopup();
            }
        });
    </script>
</body>
</html>