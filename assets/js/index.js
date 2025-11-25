// Gestion du menu mobile
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
const hamburger = document.querySelector('.hamburger');

mobileMenuBtn.addEventListener('click', function () {
    mobileMenu.classList.toggle('open');
    hamburger.classList.toggle('active');
});

// Fermer le menu mobile si on clique sur un lien
const mobileLinks = document.querySelectorAll('#mobile-menu a');
mobileLinks.forEach(link => {
    link.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('active');
    });
});

// Fermer le menu mobile si on clique en dehors du menu
document.addEventListener('click', function (event) {
    const isClickInsideNav = event.target.closest('nav');
    if (!isClickInsideNav && mobileMenu.classList.contains('open')) {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('active');
    }
});

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