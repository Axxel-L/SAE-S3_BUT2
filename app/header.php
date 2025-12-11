<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 🔄 MISE À JOUR AUTOMATIQUE DES STATUTS D'ÉVÉNEMENTS
if (!isset($connexion)) {
    require_once 'dbconnect.php';
}

// Mise à jour automatique des statuts selon les dates
try {
    // Essayer d'appeler la procédure stockée
    $connexion->query("CALL update_event_statuts()");
} catch (Exception $e) {
    // Si la procédure n'existe pas, faire les mises à jour manuellement
    try {
        // 1. Préparation → Ouvert Catégories
        $stmt = $connexion->prepare("
            UPDATE evenement 
            SET statut = 'ouvert_categories' 
            WHERE statut = 'preparation' 
            AND NOW() >= date_ouverture 
            AND NOW() < date_fermeture
        ");
        $stmt->execute();
        
        // 2. Ouvert Catégories → Fermé Catégories (attente vote final)
        $stmt = $connexion->prepare("
            UPDATE evenement 
            SET statut = 'ferme_categories' 
            WHERE statut = 'ouvert_categories' 
            AND NOW() >= date_fermeture 
            AND (date_debut_vote_final IS NULL OR NOW() < date_debut_vote_final)
        ");
        $stmt->execute();
        
        // 3. Fermé Catégories → Ouvert Final
        $stmt = $connexion->prepare("
            UPDATE evenement 
            SET statut = 'ouvert_final' 
            WHERE statut IN ('ouvert_categories', 'ferme_categories')
            AND date_debut_vote_final IS NOT NULL
            AND NOW() >= date_debut_vote_final 
            AND NOW() < date_fermeture_vote_final
        ");
        $stmt->execute();
        
        // 4. Ouvert Final → Clôture
        $stmt = $connexion->prepare("
            UPDATE evenement 
            SET statut = 'cloture' 
            WHERE statut IN ('ouvert_categories', 'ferme_categories', 'ouvert_final')
            AND date_fermeture_vote_final IS NOT NULL
            AND NOW() >= date_fermeture_vote_final
        ");
        $stmt->execute();
        
        // 5. Cas spécial : pas de vote final défini, clôturer après catégories
        $stmt = $connexion->prepare("
            UPDATE evenement 
            SET statut = 'cloture' 
            WHERE statut = 'ferme_categories'
            AND date_debut_vote_final IS NULL
            AND NOW() >= DATE_ADD(date_fermeture, INTERVAL 1 DAY)
        ");
        $stmt->execute();
        
    } catch (Exception $e2) {
        // Erreur silencieuse - on continue
    }
}

// Vérifier si connecté
$loggedin = isset($_SESSION['id_utilisateur']) ? true : false;
$usertype = $_SESSION['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Édition pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>

<body class="font-inter">
    <!-- Navbar -->
    <nav class="glass-effect-nav fixed top-0 left-0 right-0 z-50 mx-2 mt-4 px-2 py-3 lg:mx-4 lg:px-4 lg:py-4 rounded-6xl">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- Logo -->
            <div class="logo-container">
                <div class="glass-button p-2 rounded-3xl">
                    <img src="../assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
                </div>
                <span class="logo-text">GAME<span class="accent-gradient">CROWN</span></span>
            </div>

            <!-- Menu Desktop -->
            <div class="nav-desktop flex items-center gap-2">
                <div class="flex items-center gap-2">
                    
                <?php if (!$loggedin && $usertype === ''): ?>
                <!-- Accueil -->
                    <a href="index.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-home text-accent"></i>
                        <span>Accueil</span>
                    </a>

                    <!-- Présentation -->
                    <a href="index.php#presentation" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-info-circle text-accent"></i>
                        <span>Présentation</span>
                    </a>

                    <!-- Mode de scrutin -->
                    <a href="index.php#scrutin" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-award text-accent"></i>
                        <span>Mode de scrutin</span>
                    </a>
                <?php endif; ?>

                <!-- Résultats (pour tous) -->
                <a href="resultats.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                    <i class="fas fa-trophy text-accent"></i>
                    <span>Résultats</span>
                </a>

                <!-- Menu Électeur (si connecté joueur) -->
                <?php if ($loggedin && $usertype === 'joueur'): ?>
                    <a href="joueur-events.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-calendar-alt text-accent"></i>
                        <span>Événements</span>
                    </a>

                    <a href="salon-jeux.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-calendar-alt text-accent"></i>
                        <span>Salon des jeux</span>
                    </a>
                
                    <a href="vote.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-vote-yea text-accent"></i>
                        <span>Vote Catégories</span>
                    </a>

                    <a href="vote-final.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-crown text-accent"></i>
                        <span>Vote Final</span>
                    </a>

                    <a href="dashboard.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-user-circle text-accent"></i>
                        <span>Mon Espace</span>
                    </a>
                <?php endif; ?>

                <!-- Menu Admin (si connecté admin) -->
                <?php if ($loggedin && $usertype === 'admin'): ?>
                    <a href="admin-events.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-calendar text-accent"></i>
                        <span>Événements</span>
                    </a>

                    <a href="admin-candidatures.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-tags text-accent"></i>
                        <span>Participations</span>
                    </a>

                    <a href="admin-utilisateurs.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-users text-accent"></i>
                        <span>Utilisateurs</span>
                    </a>

                    <a href="admin-candidats.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-star text-accent"></i>
                        <span>Candidatures</span>
                    </a>

                    <a href="admin-logs.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-clipboard-list text-accent"></i>
                        <span>Logs</span>
                    </a>
                <?php endif; ?>

                <?php if ($loggedin && $usertype === 'candidat'): ?>
                    <a href="candidat-profil.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-user-crown text-accent"></i>
                        <span>Mon Profil</span>
                    </a>

                    <a href="candidat-campagne.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-bullhorn text-accent"></i>
                        <span>Campagne</span>
                    </a>

                    <a href="candidat-statistiques.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-chart-bar text-accent"></i>
                        <span>Statistiques</span>
                    </a>

                    <a href="candidat-events.php" class="nav-link glass-button px-5 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm lg:text-base">
                        <i class="fas fa-calendar-check text-accent"></i>
                        <span>Événements</span>
                    </a>
                <?php endif; ?>
                </div>

                <div class="h-8 w-px bg-accent/30 mx-2"></div>

                <!-- Si connecté : affiche info utilisateur + déconnexion -->
                <?php if ($loggedin): ?>
                    <div class="flex items-center gap-3">
                        <span class="badge badge-<?php echo strtolower($usertype); ?> px-4 py-2 rounded-3xl text-sm font-medium">
                            <?php
                            $types = ['joueur' => 'Joueur', 'admin' => 'Administrateur', 'candidat' => 'Candidat'];
                            echo $types[$usertype] ?? ucfirst($usertype);
                            ?>
                        </span>
                        <a href="logout.php" class="glass-button px-6 py-3 rounded-3xl font-medium flex items-center gap-2 text-sm bg-red-500/20 border border-red-500/30 hover:bg-red-500/30 transition-all duration-300">
                            <i class="fas fa-sign-out-alt text-red-400"></i>
                            <span class="text-red-400">Déconnexion</span>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="#" onclick="openResponsiveWindow('login.php'); return false;" class="glass-button px-6 py-3 rounded-3xl font-medium flex items-center gap-3 text-sm lg:text-base bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-300">
                        <i class="fa-solid fa-user text-accent text-lg"></i>
                        <span class="text-accent font-semibold">Connexion</span>
                    </a>
                <?php endif; ?>
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
                <?php if (!$loggedin && $usertype === ''): ?>
                <a href="index.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-home text-accent"></i>
                    <span>Accueil</span>
                </a>

                <a href="index.php#presentation" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-info-circle text-accent"></i>
                    <span>Présentation</span>
                </a>

                <a href="index.php#scrutin" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-award text-accent"></i>
                    <span>Mode de scrutin</span>
                </a>
                <?php endif; ?>

                <!-- Résultats mobile -->
                <a href="resultats.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                    <i class="fas fa-trophy text-accent"></i>
                    <span>Résultats</span>
                </a>

                <!-- Menu mobile électeur -->
                <?php if ($loggedin && $usertype === 'joueur'): ?>
                    <a href="joueur-events.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-calendar-alt text-accent"></i>
                        <span>Événements</span>
                    </a>

                    <a href="salon-jeux.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-calendar-alt text-accent"></i>
                        <span>Salon des jeux</span>
                    </a>

                    <a href="vote.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-vote-yea text-accent"></i>
                        <span>Vote Catégories</span>
                    </a>

                    <a href="vote-final.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-crown text-accent"></i>
                        <span>Vote Final</span>
                    </a>

                    <a href="dashboard.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-user-circle text-accent"></i>
                        <span>Mon Espace</span>
                    </a>
                <?php endif; ?>

                <!-- Menu mobile admin -->
                <?php if ($loggedin && $usertype === 'admin'): ?>
                    <a href="admin-events.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-calendar text-accent"></i>
                        <span>Événements</span>
                    </a>

                    <a href="admin-candidatures.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-tags text-accent"></i>
                        <span>Participations</span>
                    </a>

                    <a href="admin-utilisateurs.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-users text-accent"></i>
                        <span>Utilisateurs</span>
                    </a>

                    <a href="admin-candidats.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-star text-accent"></i>
                        <span>Candidatures</span>
                    </a>

                    <a href="admin-logs.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-clipboard-list text-accent"></i>
                        <span>Logs</span>
                    </a>
                <?php endif; ?>

                <?php if ($loggedin && $usertype === 'candidat'): ?>
                    <a href="candidat-profil.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-user-crown text-accent"></i>
                        <span>Mon Profil</span>
                    </a>

                    <a href="candidat-campagne.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-bullhorn text-accent"></i>
                        <span>Campagne</span>
                    </a>

                    <a href="candidat-statistiques.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-chart-bar text-accent"></i>
                        <span>Statistiques</span>
                    </a>

                    <a href="candidat-events.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3">
                        <i class="fas fa-calendar-check text-accent"></i>
                        <span>Événements</span>
                    </a>
                <?php endif; ?>

                <div class="h-px bg-accent/30 my-2"></div>

                <!-- Si connecté : mobile -->
                <?php if ($loggedin): ?>
                    <div class="flex flex-col gap-3">
                        <div class="glass-button px-6 py-4 rounded-3xl text-center">
                            <span class="badge badge-<?php echo strtolower($usertype); ?> px-4 py-2 rounded-3xl text-sm font-medium inline-block">
                                <?php
                                $types = ['joueur' => 'Joueur', 'admin' => 'Administrateur', 'candidat' => 'Candidat'];
                                echo $types[$usertype] ?? ucfirst($usertype);
                                ?>
                            </span>
                        </div>
                        <a href="logout.php" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3 bg-red-500/20 border border-red-500/30">
                            <i class="fas fa-sign-out-alt text-red-400"></i>
                            <span class="text-red-400 font-semibold">Déconnexion</span>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="#" onclick="openResponsiveWindow('login.php'); return false;" class="glass-button px-6 py-4 rounded-3xl text-center flex items-center justify-center gap-3 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30">
                        <i class="fas fa-sign-in-alt text-accent"></i>
                        <span class="text-accent font-semibold">Se connecter</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        function openResponsiveWindow(url) {
            const width = Math.round(window.innerWidth * 0.3);
            const height = Math.round(window.innerHeight * 0.7);
            const left = Math.round((window.innerWidth - width) / 2);
            const top = Math.round((window.innerHeight - height) / 2);
            window.open(url, 'blank', `width=${width},height=${height},left=${left},top=${top}`);
        }
    </script>
</body>
</html>