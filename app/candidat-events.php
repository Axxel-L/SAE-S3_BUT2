<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier candidat
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = './dashboard.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// Récupérer les infos du candidat
$candidat = null;
try {
    $stmt = $connexion->prepare("
        SELECT c.*, u.email, j.titre as jeu_titre, j.image as jeu_image
        FROM candidat c
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
        LEFT JOIN jeu j ON c.id_jeu = j.id_jeu
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidat) {
        header('Location: candidat-profil.php');
        exit;
    }

    // Vérifier que le candidat a un jeu associé
    if (empty($candidat['id_jeu'])) {
        $error = "Vous devez d'abord sélectionner un jeu dans votre profil avant de postuler !";
    }
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
}

// Postuler à une catégorie d'un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'postuler') {
    $id_event = intval($_POST['event_id'] ?? 0);
    $id_categorie = intval($_POST['categorie_id'] ?? 0);
    try {
        if (!$candidat) {
            throw new Exception("Candidat non trouvé");
        }

        if (empty($candidat['id_jeu'])) {
            throw new Exception("Vous devez d'abord sélectionner un jeu dans votre profil !");
        }

        if ($id_categorie <= 0) {
            throw new Exception("Veuillez sélectionner une catégorie !");
        }

        // Vérifier que l'événement est en préparation
        $stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_event]);
        $evt = $stmt->fetch();
        if (!$evt || $evt['statut'] !== 'preparation') {
            throw new Exception("Cet événement n'accepte plus les candidatures.");
        }

        // Vérifier que la catégorie appartient à cet événement
        $stmt = $connexion->prepare("SELECT id_categorie, nom FROM categorie WHERE id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_categorie, $id_event]);
        $categorie = $stmt->fetch();
        if (!$categorie) {
            throw new Exception("Cette catégorie n'existe pas pour cet événement.");
        }

        // Vérifier qu'il n'y a pas déjà une candidature de ce candidat dans cette catégorie
        $stmt = $connexion->prepare("
            SELECT id_event_candidat 
            FROM event_candidat
            WHERE id_evenement = ? 
            AND id_categorie = ? 
            AND id_candidat = ?
        ");
        $stmt->execute([$id_event, $id_categorie, $candidat['id_candidat']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Vous avez déjà postulé à cette catégorie !");
        }

        // Créer la candidature en attente
        $stmt = $connexion->prepare("
            INSERT INTO event_candidat (id_evenement, id_candidat, id_categorie, statut_candidature, date_inscription)
            VALUES (?, ?, ?, 'en_attente', NOW())
        ");
        $stmt->execute([$id_event, $candidat['id_candidat'], $id_categorie]);

        // Ajout aux logs
        $stmt = $connexion->prepare("
            INSERT INTO journal_securite (id_utilisateur, action, details) 
            VALUES (?, 'CANDIDATURE_SOUMISE', ?)
        ");
        $stmt->execute([
            $id_utilisateur,
            "Événement: $id_event, Catégorie: {$categorie['nom']}, Jeu: " . $candidat['jeu_titre']
        ]);

        $success = "✅ Candidature soumise avec succès pour la catégorie \"" . htmlspecialchars($categorie['nom']) . "\" ! Elle sera examinée par un administrateur.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer les événements en préparation
$events = [];
try {
    $stmt = $connexion->prepare("
        SELECT 
            e.id_evenement, 
            e.nom as titre, 
            e.description, 
            e.date_ouverture as date_debut, 
            e.date_fermeture as date_fin, 
            e.statut as etat
        FROM evenement e
        WHERE e.statut = 'preparation'
        ORDER BY e.date_ouverture ASC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les catégories disponibles
    foreach ($events as &$event) {
        $stmt = $connexion->prepare("
            SELECT c.id_categorie, c.nom, c.description
            FROM categorie c
            WHERE c.id_evenement = ?
            ORDER BY c.nom ASC
        ");
        $stmt->execute([$event['id_evenement']]);
        $event['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Vérifier les candidatures existantes de ce candidat pour cet événement
        $event['mes_candidatures'] = [];
        if ($candidat) {
            try {
                $stmt = $connexion->prepare("
                    SELECT ec.id_categorie, ec.statut_candidature, cat.nom as categorie_nom
                    FROM event_candidat ec
                    LEFT JOIN categorie cat ON ec.id_categorie = cat.id_categorie
                    WHERE ec.id_candidat = ? AND ec.id_evenement = ?
                ");
                $stmt->execute([$candidat['id_candidat'], $event['id_evenement']]);
                $event['mes_candidatures'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // La colonne id_categorie n'existe pas encore
                $event['mes_candidatures'] = [];
            }
        }
    }
    unset($event);
} catch (Exception $e) {
    if (empty($error)) {
        $error = "Erreur lors du chargement des événements: " . $e->getMessage();
    }
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
                                            $statusClass = match ($statut) {
                                                'approuve' => 'bg-green-500/10 text-green-400 border-green-500/30',
                                                'refuse' => 'bg-red-500/10 text-red-400 border-red-500/30',
                                                default => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30'
                                            };
                                            $statusIcon = match ($statut) {
                                                'approuve' => 'fa-check-circle',
                                                'refuse' => 'fa-times-circle',
                                                default => 'fa-hourglass-half'
                                            };
                                            $statusText = match ($statut) {
                                                'approuve' => 'Approuvée',
                                                'refuse' => 'Refusée',
                                                default => 'En attente'
                                            };
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
    // Réinitialiser toutes les bordures
    document.querySelectorAll('input[name="categorie_id"]').forEach(r => {
        const label = r.nextElementSibling;
        if (label && !label.classList.contains('pointer-events-none')) {
            label.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        }
    });
    // Appliquer la bordure accent à la sélection
    const selectedLabel = input.nextElementSibling;
    if (selectedLabel) {
        selectedLabel.style.borderColor = '#00d4ff';
    }
}
</script>
<?php require_once 'footer.php'; ?>