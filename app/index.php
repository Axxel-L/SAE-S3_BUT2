<?php
require('header.php');


?>

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
<?php
require('footer.php');