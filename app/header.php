<?php
/**
 * header.php - REFACTORISÉ avec SOLID
 * 
 * En-tête principal de l'application
 * Navigation responsive, gestion de connexion
 * 
 * Utilise HeaderService pour toute la logique métier
 */

require_once __DIR__ . '/classes/init.php';



// ==================== INITIALISATION ====================

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mettre à jour les statuts des événements
try {
    $headerService = ServiceContainer::getHeaderService();
    $headerService->updateEventStatuses();
    
    // Récupérer les données de connexion et menu
    $authData = $headerService->getAuthenticationData();
    $menuItems = $headerService->getMenuItems($authData['userType']);
    $userTypeLabel = $headerService->getUserTypeLabel($authData['userType']);
    
} catch (Exception $e) {
    error_log("Header Error: " . $e->getMessage());
    // Fallback values
    $authData = ['isLogged' => false, 'userType' => '', 'userId' => null];
    $menuItems = [];
    $userTypeLabel = '';
}

?><!DOCTYPE html>
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
</head>

<body class="font-inter">
    <!-- Navbar -->
    <nav class="glass-effect-nav fixed top-0 left-0 right-0 z-50 mx-2 mt-4 px-2 py-3 lg:mx-4 lg:px-4 lg:py-4 rounded-[2rem] lg:rounded-[2.5rem] border-2 border-white/10">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-4">
            <!-- Logo -->
            <div class="logo-container flex-shrink-0">
                <div class="glass-button p-2 rounded-[1rem] border border-white/10">
                    <img src="../assets/img/logo.png" alt="Logo GameCrown" class="logo-image">
                </div>
                <span class="logo-text">GAME<span class="accent-gradient">CROWN</span></span>
            </div>

            <!-- Menu Desktop -->
            <div class="nav-desktop flex items-center gap-2 flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    
                    <?php foreach ($menuItems as $key => $item):
                        if (!isset($item['visible']) || !$item['visible']) continue;
                    ?>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="nav-link glass-button px-5 py-3 rounded-[1rem] font-medium flex items-center justify-center gap-2 text-sm lg:text-base border border-white/10 whitespace-nowrap h-fit">
                            <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-accent"></i>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="h-8 w-px bg-accent/30 mx-2"></div>

                <!-- Actions de connexion -->
                <div class="flex items-center gap-3">
                    <?php if ($authData['isLogged']): ?>
                        <!-- Connecté: badge + déconnexion -->
                        <span class="badge badge-<?php echo htmlspecialchars(strtolower($authData['userType'])); ?> px-4 py-2 rounded-[1rem] text-sm font-medium border border-white/10">
                            <?php echo htmlspecialchars($userTypeLabel); ?>
                        </span>
                        <a href="logout.php" class="glass-button px-5 py-3 rounded-[1rem] font-medium flex items-center justify-center gap-2 text-sm bg-red-500/20 border border-red-500/30 hover:bg-red-500/30 transition-all duration-300 whitespace-nowrap h-fit">
                            <i class="fas fa-sign-out-alt text-red-400"></i>
                            <span class="text-red-400">Déconnexion</span>
                        </a>
                    <?php else: ?>
                        <!-- Non connecté: bouton connexion -->
                        <a href="#" onclick="openResponsiveWindow('login.php'); return false;" 
                           class="glass-button px-5 py-3 rounded-[1rem] font-medium flex items-center justify-center gap-2 text-sm lg:text-base bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-300 whitespace-nowrap h-fit">
                            <i class="fa-solid fa-user text-accent text-lg"></i>
                            <span class="text-accent font-semibold">Connexion</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bouton menu mobile -->
            <button id="mobile-menu-btn" class="mobile-menu-button glass-button p-3 rounded-[1rem] border border-white/10 flex-shrink-0">
                <div class="hamburger flex flex-col gap-1.5 w-6 h-6 justify-center items-center">
                    <i class="fa-solid fa-bars fa-2xl" style="color: #00d4ff;"></i>
                </div>
            </button>
        </div>

        <!-- Menu Mobile -->
        <div id="mobile-menu" class="mobile-menu mt-4">
            <div class="flex flex-col gap-3 pb-4">
                <?php foreach ($menuItems as $key => $item):
                    if (!isset($item['visible']) || !$item['visible']) continue;
                ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                       class="glass-button px-6 py-4 rounded-[1rem] text-center flex items-center justify-center gap-3 border border-white/10">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-accent"></i>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>

                <div class="h-px bg-accent/30 my-2"></div>

                <!-- Actions mobiles -->
                <div class="flex flex-col gap-3">
                    <?php if ($authData['isLogged']): ?>
                        <!-- Connecté: badge + déconnexion -->
                        <div class="glass-button px-6 py-4 rounded-[1rem] text-center border border-white/10">
                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($authData['userType'])); ?> px-4 py-2 rounded-[1rem] text-sm font-medium inline-block border border-white/10">
                                <?php echo htmlspecialchars($userTypeLabel); ?>
                            </span>
                        </div>
                        <a href="logout.php" class="glass-button px-6 py-4 rounded-[1rem] text-center flex items-center justify-center gap-3 bg-red-500/20 border border-red-500/30 border border-white/10">
                            <i class="fas fa-sign-out-alt text-red-400"></i>
                            <span class="text-red-400 font-semibold">Déconnexion</span>
                        </a>
                    <?php else: ?>
                        <!-- Non connecté: bouton connexion -->
                        <a href="#" onclick="openResponsiveWindow('login.php'); return false;" 
                           class="glass-button px-6 py-4 rounded-[1rem] text-center flex items-center justify-center gap-3 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 border border-white/10">
                            <i class="fas fa-sign-in-alt text-accent"></i>
                            <span class="text-accent font-semibold">Se connecter</span>
                        </a>
                    <?php endif; ?>
                </div>
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