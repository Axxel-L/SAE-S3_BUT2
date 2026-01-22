<?php
require_once __DIR__ . '/classes/init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $headerService = ServiceContainer::getHeaderService();
    $headerService->updateEventStatuses();
    $authData = $headerService->getAuthenticationData();
    $menuItems = $headerService->getMenuItems($authData['userType']);
    $userTypeLabel = $headerService->getUserTypeLabel($authData['userType']);
    
} catch (Exception $e) {
    error_log("Header Error: " . $e->getMessage());
    $authData = ['isLogged' => false, 'userType' => '', 'userId' => null];
    $menuItems = [];
    $userTypeLabel = '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - V1</title>
    <script src="https://cdn.agence-prestige-numerique.fr/tailwindcss/3.4.17.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="/assets/css/index.css">
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
</head>

<body class="font-inter">
    <nav class="glass-effect-nav fixed top-0 left-0 right-0 z-50 mx-2 mt-4 px-2 py-2 sm:px-3 sm:py-3 lg:mx-4 lg:px-4 lg:py-4 rounded-[1.5rem] sm:rounded-[2rem] lg:rounded-[2.5rem] border-2 border-white/10">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-2 sm:gap-3 lg:gap-4">
            <!-- Logo -->
            <div class="logo-container flex-shrink-0 flex items-center gap-1 sm:gap-2 lg:gap-3">
                <div class="glass-button p-1 sm:p-1.5 lg:p-2 rounded-[0.8rem] sm:rounded-[1rem] border border-white/10">
                    <img src="/assets/img/logo.png" alt="Logo GameCrown" class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8">
                </div>
                <span class="logo-text text-sm sm:text-base lg:text-lg font-bold whitespace-nowrap hidden lg:inline">
                    GAME<span class="accent-gradient">CROWN</span>
                </span>
            </div>

            <!-- Menu Desktop -->
            <div class="nav-desktop hidden md:flex items-center gap-1 lg:gap-2 flex-1 min-w-0 justify-end">
                <!-- Container pour le défilement horizontal -->
                <div class="flex items-center flex-1 min-w-0">
                    <!-- Items menu avec défilement horizontal -->
                    <div class="menu-scroll-container overflow-x-auto hover:overflow-x-scroll flex items-center gap-0.5 lg:gap-1 xl:gap-2 flex-shrink px-2 py-1 transition-all duration-300"
                         style="scrollbar-width: thin; scrollbar-color: rgba(0, 212, 255, 0.3) transparent;">
                        <?php foreach ($menuItems as $key => $item):
                            if (!isset($item['visible']) || !$item['visible']) continue;
                        ?>
                            <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                               class="nav-link glass-button px-2 sm:px-3 lg:px-4 xl:px-5 py-2 sm:py-2.5 lg:py-3 rounded-[0.8rem] lg:rounded-[1rem] font-medium flex items-center justify-center gap-1 lg:gap-2 text-xs sm:text-sm lg:text-base border border-white/10 whitespace-nowrap transition-all duration-200 hover:scale-[1.02] flex-shrink-0">
                                <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-accent text-xs sm:text-sm lg:text-base"></i>
                                <span class="hidden sm:inline"><?php echo htmlspecialchars($item['label']); ?></span>
                                <span class="sm:hidden"><?php echo htmlspecialchars(substr($item['label'], 0, 3)); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="h-6 sm:h-7 lg:h-8 w-px bg-accent/30 mx-1 sm:mx-2 flex-shrink-0"></div>
                
                <!-- Auth Section -->
                <div class="flex items-center gap-1 sm:gap-2 lg:gap-3 flex-shrink-0">
                    <?php if ($authData['isLogged']): ?>
                        <span class="badge badge-<?php echo htmlspecialchars(strtolower($authData['userType'])); ?> px-2 sm:px-3 lg:px-4 py-1.5 sm:py-2 rounded-[0.8rem] lg:rounded-[1rem] text-xs sm:text-sm font-medium border border-white/10 whitespace-nowrap">
                            <?php echo htmlspecialchars($userTypeLabel); ?>
                        </span>
                        <a href="logout.php" class="glass-button px-2 sm:px-3 lg:px-4 xl:px-5 py-2 sm:py-2.5 lg:py-3 rounded-[0.8rem] lg:rounded-[1rem] font-medium flex items-center justify-center gap-1 lg:gap-2 text-xs sm:text-sm lg:text-base bg-red-500/20 border border-red-500/30 hover:bg-red-500/30 transition-all duration-200 hover:scale-[1.02] whitespace-nowrap flex-shrink-0">
                            <i class="fas fa-sign-out-alt text-red-400 text-xs sm:text-sm lg:text-base"></i>
                            <span class="hidden sm:inline text-red-400">Déconnexion</span>
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="openResponsiveWindow('login.php'); return false;" 
                           class="glass-button px-2 sm:px-3 lg:px-4 xl:px-5 py-2 sm:py-2.5 lg:py-3 rounded-[0.8rem] lg:rounded-[1rem] font-medium flex items-center justify-center gap-1 lg:gap-2 text-xs sm:text-sm lg:text-base bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 hover:from-accent/30 hover:to-accent/20 transition-all duration-200 hover:scale-[1.02] whitespace-nowrap flex-shrink-0">
                            <i class="fa-solid fa-user text-accent text-xs sm:text-sm lg:text-base"></i>
                            <span class="hidden sm:inline text-accent font-semibold">Connexion</span>
                            <span class="sm:hidden text-accent font-semibold">Connex.</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bouton mobile -->
            <button id="mobile-menu-btn" class="md:hidden mobile-menu-button glass-button p-2 sm:p-2.5 rounded-[0.8rem] sm:rounded-[1rem] border border-white/10 flex-shrink-0">
                <div class="hamburger flex flex-col gap-1 w-5 h-5 sm:w-6 sm:h-6 justify-center items-center">
                    <i class="fa-solid fa-bars fa-lg sm:fa-xl" style="color: #00d4ff;"></i>
                </div>
            </button>
        </div>

        <!-- Menu Mobile -->
        <div id="mobile-menu" class="md:hidden mobile-menu mt-3 hidden">
            <div class="flex flex-col gap-2 sm:gap-3 pb-3 sm:pb-4">
                <?php foreach ($menuItems as $key => $item):
                    if (!isset($item['visible']) || !$item['visible']) continue;
                ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                       class="glass-button px-4 sm:px-5 py-3 sm:py-4 rounded-[0.8rem] sm:rounded-[1rem] text-center flex items-center justify-center gap-2 sm:gap-3 border border-white/10 text-sm sm:text-base">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?> text-accent text-sm sm:text-base"></i>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
                <div class="h-px bg-accent/30 my-1 sm:my-2"></div>
                <div class="flex flex-col gap-2 sm:gap-3">
                    <?php if ($authData['isLogged']): ?>
                        <div class="glass-button px-4 sm:px-5 py-3 sm:py-4 rounded-[0.8rem] sm:rounded-[1rem] text-center border border-white/10">
                            <span class="badge badge-<?php echo htmlspecialchars(strtolower($authData['userType'])); ?> px-3 sm:px-4 py-1.5 sm:py-2 rounded-[0.8rem] text-xs sm:text-sm font-medium inline-block border border-white/10">
                                <?php echo htmlspecialchars($userTypeLabel); ?>
                            </span>
                        </div>
                        <a href="logout.php" class="glass-button px-4 sm:px-5 py-3 sm:py-4 rounded-[0.8rem] sm:rounded-[1rem] text-center flex items-center justify-center gap-2 sm:gap-3 bg-red-500/20 border border-red-500/30 text-sm sm:text-base">
                            <i class="fas fa-sign-out-alt text-red-400 text-sm sm:text-base"></i>
                            <span class="text-red-400 font-semibold">Déconnexion</span>
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="openResponsiveWindow('login.php'); return false;" 
                           class="glass-button px-4 sm:px-5 py-3 sm:py-4 rounded-[0.8rem] sm:rounded-[1rem] text-center flex items-center justify-center gap-2 sm:gap-3 bg-gradient-to-r from-accent/20 to-accent/10 border border-accent/30 text-sm sm:text-base">
                            <i class="fas fa-sign-in-alt text-accent text-sm sm:text-base"></i>
                            <span class="text-accent font-semibold">Se connecter</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <style>
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .menu-scroll-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .menu-scroll-container::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 3px;
        }
        
        .menu-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(0, 212, 255, 0.3);
            border-radius: 3px;
            transition: background 0.3s ease;
        }
        
        .menu-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 212, 255, 0.5);
        }
        
        .menu-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        
        .menu-scroll-container:hover {
            scrollbar-color: rgba(0, 212, 255, 0.3) transparent;
        }
    </style>
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