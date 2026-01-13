<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';
try {
    $eventsService = ServiceContainer::getCandidatEventsService();
    $stmt = \DatabaseConnection::getInstance()->prepare("
        SELECT id_candidat FROM candidat WHERE id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidatResult = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$candidatResult) {
        header('Location: candidat-profil.php');
        exit;
    }
    
    $id_candidat = $candidatResult['id_candidat'];
    $candidat = $eventsService->getCandidatData($id_candidat);
    if (!$candidat) {
        header('Location: candidat-profil.php');
        exit;
    }

    if (empty($candidat['id_jeu'])) {
        $error = "Vous devez d'abord sélectionner un jeu dans votre profil avant de postuler !";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'postuler') {
        $id_event = intval($_POST['event_id'] ?? 0);
        $id_categorie = intval($_POST['categorie_id'] ?? 0);
        try {
            if (empty($candidat['id_jeu'])) {
                throw new Exception("Vous devez d'abord sélectionner un jeu dans votre profil !");
            }
            if ($id_categorie <= 0) {
                throw new Exception("Veuillez sélectionner une catégorie !");
            }
            $eventsService->submitApplication(
                $id_candidat,
                $id_event,
                $id_categorie,
                $candidat['jeu_titre']
            );
            $success = "✅ Candidature soumise avec succès pour la catégorie \"" . htmlspecialchars($candidat['jeu_titre']) . "\" ! Elle sera examinée par un administrateur.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    $events = $eventsService->getEventsWithDetails($id_candidat);
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
    $candidat = null;
    $events = [];
}
require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-gamepad text-accent mr-3"></i>Postuler aux Événements
            </h1>
            <p class="text-xl text-light/80">Inscrivez votre jeu dans les catégories qui vous correspondent</p>
        </div>
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Infos du candidat et son jeu -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-12">
            <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-user-circle text-accent text-xl"></i>
                </div>
                <span>Votre candidature</span>
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:items-center">
                <div class="flex flex-col gap-4">
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-light/60 text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-user text-accent text-lg"></i>Nom
                        </p>
                        <p class="text-gl font-bold text-light"><?php echo htmlspecialchars($candidat['nom'] ?? 'Non défini'); ?></p>
                    </div>
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-light/60 text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-envelope text-accent text-lg"></i>Email
                        </p>
                        <p class="text-lg font-bold text-light break-all"><?php echo htmlspecialchars($candidat['email'] ?? ''); ?></p>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-6">
                    <?php if (!empty($candidat['jeu_image'])): ?>
                        <div class="relative">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-br from-accent/20 to-accent/5 blur-xl"></div>
                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" alt="<?php echo htmlspecialchars($candidat['jeu_titre']); ?>" class="relative w-52 h-52 rounded-full object-cover border-4 border-accent/30 shadow-lg shadow-accent/20">
                        </div>
                    <?php else: ?>
                        <div class="w-52 h-52 rounded-full bg-white/5 border-4 border-white/10 flex items-center justify-center">
                            <i class="fas fa-image text-5xl text-light/20"></i>
                        </div>
                    <?php endif; ?>
                    <div class="p-6 rounded-2xl bg-accent/10 border border-accent/30 text-center w-full">
                        <p class="text-light/60 text-sm mb-2 flex items-center justify-center gap-2">
                            <i class="fas fa-gamepad text-accent text-lg"></i>Jeu représenté
                        </p>
                        <p class="text-xl font-bold text-accent"><?php echo htmlspecialchars($candidat['jeu_titre'] ?? 'Aucun jeu sélectionné'); ?></p>
                    </div>
                </div>
            </div>
            <?php if (empty($candidat['id_jeu'])): ?>
                <div class="mt-6 p-4 rounded-2xl bg-orange-500/10 border border-orange-500/30">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-orange-400 text-xl"></i>
                        <div>
                            <p class="text-orange-400 font-bold">Attention</p>
                            <p class="text-orange-300/80 text-sm">Vous devez d'abord sélectionner un jeu dans votre 
                                <a href="candidat-profil.php" class="underline hover:text-orange-200">profil</a> avant de pouvoir postuler.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Liste des événements -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold font-orbitron mb-8 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center">
                    <i class="fas fa-calendar-check text-accent text-xl"></i>
                </div>
                <span>Événements en préparation</span>
            </h2>
            <?php if (empty($events)): ?>
                <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white/5 mb-6">
                        <i class="fas fa-inbox text-4xl text-light/20"></i>
                    </div>
                    <p class="text-light/80 text-lg mb-2">Aucun événement disponible actuellement</p>
                    <p class="text-light/60">Les événements en préparation apparaîtront ici</p>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($events as $event): ?>
                        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
                            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6 mb-8">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-4">
                                        <h3 class="text-2xl font-bold text-accent"><?php echo htmlspecialchars($event['titre']); ?></h3>
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">
                                            <i class="fas fa-clock mr-1"></i>En préparation
                                        </span>
                                    </div>
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="text-light/80 mb-4"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-4 text-sm text-light/60">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-calendar text-accent"></i>
                                            <span>Du <?php echo date('d/m/Y', strtotime($event['date_debut'])); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-calendar-times text-accent"></i>
                                            <span>au <?php echo date('d/m/Y', strtotime($event['date_fin'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($event['mes_candidatures'])): ?>
                                <div class="mb-8 p-6 rounded-2xl bg-white/5 border border-white/10">
                                    <h4 class="text-lg font-bold text-light mb-4 flex items-center gap-2">
                                        <i class="fas fa-clipboard-list text-accent"></i>
                                        Mes candidatures pour cet événement
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <?php foreach ($event['mes_candidatures'] as $cand):
                                            $statut = $cand['statut_candidature'] ?? 'en_attente';
                                            $config = CandidatEventsService::getStatutConfig($statut);
                                            $statusClass = $config['class'] ?? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30';
                                            $statusIcon = $config['icon'] ?? 'fa-hourglass-half';
                                            $statusText = $config['label'] ?? 'En attente';
                                        ?>
                                            <div class="p-4 rounded-xl border <?php echo $statusClass; ?>">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-bold"><?php echo htmlspecialchars($cand['categorie_nom'] ?? 'Catégorie'); ?></p>
                                                        <p class="text-xs opacity-75 mt-1"><?php echo $statusText; ?></p>
                                                    </div>
                                                    <i class="fas <?php echo $statusIcon; ?> text-lg"></i>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($event['categories'])): ?>
                                <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-info-circle text-orange-400 text-xl"></i>
                                        <div>
                                            <p class="text-orange-400 font-bold">Aucune catégorie disponible</p>
                                            <p class="text-orange-300/80 text-sm">Aucune catégorie n'est définie pour cet événement. Revenez plus tard !</p>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (empty($candidat['id_jeu'])): ?>
                                <div class="p-6 rounded-2xl bg-orange-500/10 border border-orange-500/30">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-exclamation-triangle text-orange-400 text-xl"></i>
                                        <div>
                                            <p class="text-orange-400 font-bold">Jeu non sélectionné</p>
                                            <p class="text-orange-300/80 text-sm">Sélectionnez d'abord un jeu dans votre profil pour postuler.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-8">
                                    <h4 class="text-lg font-bold text-light mb-6 flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
                                            <i class="fas fa-tags text-accent"></i>
                                        </div>
                                        <span>Choisissez une catégorie pour postuler</span>
                                    </h4>
                                    <form method="POST" class="space-y-6">
                                        <input type="hidden" name="action" value="postuler">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id_evenement']; ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <?php
                                            $candidaturesCategories = array_column($event['mes_candidatures'], 'id_categorie');
                                            foreach ($event['categories'] as $cat):
                                                $dejaPostule = in_array($cat['id_categorie'], $candidaturesCategories);
                                                $cat_id = 'cat_' . $event['id_evenement'] . '_' . $cat['id_categorie'];
                                            ?>
                                                <input
                                                    type="radio"
                                                    name="categorie_id"
                                                    value="<?php echo $cat['id_categorie']; ?>"
                                                    id="<?php echo $cat_id; ?>"
                                                    class="sr-only"
                                                    onchange="changeColor(this)"
                                                    <?php echo $dejaPostule ? 'disabled' : ''; ?>>

                                                <label for="<?php echo $cat_id; ?>"
                                                    class="block cursor-pointer p-5 rounded-2xl border-2 border-white/10 bg-white/5 transition-all hover:border-accent/30
                                                    <?php echo $dejaPostule ? 'opacity-60 pointer-events-none border-green-500/30 bg-green-500/5' : ''; ?>">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex-1">
                                                            <p class="font-bold text-light mb-2"><?php echo htmlspecialchars($cat['nom']); ?></p>
                                                            <?php if ($cat['description']): ?>
                                                                <p class="text-sm text-light/60"><?php echo htmlspecialchars($cat['description']); ?></p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($dejaPostule): ?>
                                                            <div class="flex-shrink-0">
                                                                <i class="fas fa-check text-green-400 text-xl"></i>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex-shrink-0">
                                                                <i class="fas fa-circle text-light/20 text-sm"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="flex justify-center">
                                            <button
                                                type="submit"
                                                class="px-8 py-4 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border-2 border-white/10 flex items-center gap-3">
                                                <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function changeColor(input) {
    document.querySelectorAll('input[name="categorie_id"]').forEach(r => {
        const label = r.nextElementSibling;
        if (label && !label.classList.contains('pointer-events-none')) {
            label.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        }
    });
    const selectedLabel = input.nextElementSibling;
    if (selectedLabel) {
        selectedLabel.style.borderColor = '#00d4ff';
    }
}
</script>
<?php require_once 'footer.php'; ?>