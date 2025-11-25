<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Édition Épurée</title>
    
    <!-- Ces 3 lignes suffisent pour tous les styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <!-- Navbar -->
    <nav
        class="glass-effect-nav fixed top-0 left-0 right-0 z-50 mx-2 mt-4 px-2 py-3 lg:mx-4 lg:px-4 lg:py-4 rounded-6xl">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="logo-container">
                <div class="glass-button p-2 rounded-3xl">
                    <img src="../assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
                </div>
                <span class="logo-text">
                    GAME<span class="accent-gradient">CROWN</span>
                </span>
            </div>

            <!-- Menu Desktop -->
            <div class="nav-desktop flex items-center gap-2">
                <div class="flex items-center gap-2">
                    <a href="#accueil"
                        class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-home text-accent"></i>
                        <span>Accueil</span>
                    </a>
                    <a href="#presentation"
                        class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-info-circle text-accent"></i>
                        <span>Présentation</span>
                    </a>
                    <a href="#scrutin"
                        class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-award text-accent"></i>
                        <span>Mode de scrutin</span>
                    </a>
                    <a href="#contact"
                        class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-envelope text-accent"></i>
                        <span>Contact</span>
                    </a>
                </div>
                <div class="h-8 w-px bg-accent/30 mx-2"></div>
                <a href="./app/login"
                    class="nav-link glass-button px-6 py-3 rounded-3xl font-medium flex items-center gap-3 text-sm lg:text-base bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-300">
                    <i class="fa-solid fa-user text-accent text-lg"></i>
                    <span class="text-accent font-semibold">Connexion</span>
                </a>
            </div>

            <!-- Bouton pour dérouler la navbar mobile -->
            <button id="mobile-menu-btn" class="mobile-menu-button glass-button p-3 rounded-3xl">
                <div class="hamburger flex flex-col gap-1.5 w-6 h-6 justify-center items-center">
                    <i class="fa-solid fa-bars fa-2xl" style="color: #00d4ff;"></i>
                </div>
            </button>
        </div>

        <!-- Menu Mobile -->
        <div id="mobile-menu" class="mobile-menu mt-4">
            <div class="flex flex-col gap-3 pb-4">
                <a href="#accueil"
                    class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-home text-accent"></i>
                    <span>Accueil</span>
                </a>
                <a href="#presentation"
                    class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-info-circle text-accent"></i>
                    <span>Présentation</span>
                </a>
                <a href="#scrutin"
                    class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-award text-accent"></i>
                    <span>Mode de scrutin</span>
                </a>
                <a href="#contact"
                    class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-envelope text-accent"></i>
                    <span>Contact</span>
                </a>
                <div class="h-px bg-accent/30 my-2"></div>
                <a href="./app/login"
                    class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30">
                    <i class="fas fa-sign-in-alt text-accent"></i>
                    <span class="text-accent font-semibold">Se connecter</span>
                </a>
            </div>
        </div>
    </nav>