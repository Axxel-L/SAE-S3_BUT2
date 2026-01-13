<?php
/**
 * footer.php - REFACTORISÉ avec SOLID
 * 
 * Pied de page principal de l'application
 * Informations, liens sociaux, copyright
 * 
 * Utilise FooterService pour toute la logique métier
 */

try {
    $footerService = ServiceContainer::getFooterService();
    $appInfo = $footerService->getAppInfo();
    $socialLinks = $footerService->getSocialLinks();
    $footerLinks = $footerService->getFooterLinks();
} catch (Exception $e) {
    error_log("Footer Error: " . $e->getMessage());
    // Fallback values
    $appInfo = ['name' => 'GameCrown', 'year' => date('Y'), 'copyright' => '© ' . date('Y') . ' GameCrown'];
    $socialLinks = [];
    $footerLinks = [];
}

?>
    <!-- Footer -->
    <footer class="glass-effect-footer py-16 px-6 mt-20 rounded-t-6xl modern-border">
        <div class="container mx-auto">
            <!-- Logo + Réseaux sociaux -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-8 md:mb-0">
                    <div class="glass-button p-2 rounded-3xl">
                        <img src="../assets/img/logo.png" alt="Logo <?php echo htmlspecialchars($appInfo['name']); ?>" class="logo-image">
                    </div>
                    <span class="logo-text">
                        GAME<span class="accent-gradient">CROWN</span>
                    </span>
                </div>

                <!-- Liens sociaux -->
                <div class="flex space-x-5">
                    <?php foreach ($socialLinks as $key => $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" 
                           title="<?php echo htmlspecialchars($social['label']); ?>"
                           class="glass-button rounded-3xl p-3 w-12 h-12 flex items-center justify-center modern-border hover:bg-accent/20 transition-colors"
                           target="_blank" rel="noopener noreferrer">
                            <i class="<?php echo htmlspecialchars($social['icon']); ?> text-accent"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Séparateur -->
            <div class="separator mt-10"></div>

            <!-- Copyright -->
            <div class="text-center text-base text-light/70">
                <p><?php echo htmlspecialchars($appInfo['copyright']); ?></p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="../assets/js/index.js"></script>

    <!-- Script pour les modals Login et Register -->
    <script>
        // ==================== VARIABLES GLOBALES ====================
        const loginOverlay = document.getElementById('loginOverlay');
        const loginModal = document.getElementById('loginModal');
        const loginModalContent = document.getElementById('loginModalContent');
        const closeLoginModal = document.getElementById('closeLoginModal');
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const loginPassword = document.getElementById('loginPassword');
        const switchToRegister = document.getElementById('switchToRegister');

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