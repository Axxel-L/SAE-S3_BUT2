// Gestion du menu mobile
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
const hamburger = document.querySelector('.hamburger');
const hamburgerIcon = document.querySelector('.hamburger i');

mobileMenuBtn.addEventListener('click', function () {
    mobileMenu.classList.toggle('hidden');
    if (mobileMenu.classList.contains('hidden')) {
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
    } else {
        hamburgerIcon.classList.remove('fa-bars');
        hamburgerIcon.classList.add('fa-times');
    }
});

// Fermer le menu mobile si on clique sur un lien
const mobileLinks = document.querySelectorAll('#mobile-menu a');
mobileLinks.forEach(link => {
    link.addEventListener('click', function () {
        mobileMenu.classList.add('hidden');
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
    });
});

// Fermer le menu mobile si on clique en dehors du menu
document.addEventListener('click', function (event) {
    const isClickInsideNav = event.target.closest('nav');
    if (!isClickInsideNav && !mobileMenu.classList.contains('hidden')) {
        mobileMenu.classList.add('hidden');
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
    }
});

// Fonction pour ouvrir une fenêtre responsive
function openResponsiveWindow(url) {
    const width = Math.round(window.innerWidth * 0.3);
    const height = Math.round(window.innerHeight * 0.7);
    const left = Math.round((window.innerWidth - width) / 2);
    const top = Math.round((window.innerHeight - height) / 2);
    window.open(url, 'blank', `width=${width},height=${height},left=${left},top=${top}`);
}

// Gestion du formulaire de contact
document.getElementById('contactForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const submitButton = this.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('formMessage');
    const originalText = submitButton.innerHTML;

    // Message
    messageDiv.innerHTML = '';
    messageDiv.className = 'mt-4';
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Envoi en cours...</span>';
    submitButton.disabled = true;

    try {
        const formData = new FormData(this);

        const response = await fetch('send-contact.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            messageDiv.className = 'mt-4 p-4 rounded-3xl text-center bg-green-500/20 text-green-400 border border-green-500/30';
            messageDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${result.message}`;
            this.reset();
        } else {
            messageDiv.className = 'mt-4 p-4 rounded-3xl text-center bg-red-500/20 text-red-400 border border-red-500/30';
            messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> ${result.errors.join('<br>')}`;
        }
    } catch (error) {
        messageDiv.className = 'mt-4 p-4 rounded-3xl text-center bg-red-500/20 text-red-400 border border-red-500/30';
        messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> Erreur réseau: ${error.message}`;
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});