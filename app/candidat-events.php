<?php
// ⚠️ Sécurité PHP - SESSION et VÉRIFICATIONS EN PREMIER
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// ⚠️ VÉRIFIER AUTHENTIFICATION AVANT TOUT HTML
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'candidat') {
    echo "<script>alert('Accès réservé aux candidats'); window.location.href = 'index.php';</script>";
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// 📋 Récupérer les infos du candidat
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

    // Vérifier que le candidat a bien un jeu associé
    if (empty($candidat['id_jeu'])) {
        $error = "Vous devez d'abord sélectionner un jeu dans votre profil avant de postuler !";
    }
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
}

// ➕ Postuler à une catégorie
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

        // Vérifier que l'événement est bien en préparation
        $stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_event]);
        $evt = $stmt->fetch();
        if (!$evt || $evt['statut'] !== 'preparation') {
            throw new Exception("Cet événement n'accepte plus les candidatures.");
        }

        // Vérifier que la catégorie appartient bien à cet événement
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

        // Log audit
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

// 🎮 Récupérer les événements en préparation avec leurs catégories
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

    // Pour chaque événement, récupérer les catégories disponibles
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
                // La colonne id_categorie n'existe peut-être pas encore
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

// Inclure header APRÈS vérifications
require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatures - GameCrown</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>

<body class="font-inter bg-dark text-light">

    <section class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">

            <!-- En-tête -->
            <div class="mb-12">
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                    <i class="fas fa-gamepad text-accent mr-3"></i>Postuler aux Événements
                </h1>
                <p class="text-xl text-light/80">Inscrivez votre jeu dans les catégories qui vous correspondent</p>
            </div>

            <!-- Messages d'erreur/succès -->
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
            <div class="glass-card rounded-3xl p-8 modern-border mb-12">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle text-accent"></i>Votre candidature
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                    <div>
                        <p class="text-light/60 text-sm">Nom</p>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars($candidat['nom'] ?? 'Non défini'); ?></p>
                    </div>
                    <div>
                        <p class="text-light/60 text-sm">Email</p>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars($candidat['email'] ?? ''); ?></p>
                    </div>
                    <div>
                        <p class="text-light/60 text-sm">Jeu représenté</p>
                        <p class="text-lg font-bold text-accent"><?php echo htmlspecialchars($candidat['jeu_titre'] ?? 'Aucun jeu sélectionné'); ?></p>
                    </div>
                    <?php if (!empty($candidat['jeu_image'])): ?>
                        <div class="flex justify-center md:justify-end">
                            <img src="<?php echo htmlspecialchars($candidat['jeu_image']); ?>" alt="" class="w-20 h-20 rounded-lg object-cover">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($candidat['id_jeu'])): ?>
                    <div class="mt-4 p-4 rounded-xl bg-orange-500/10 border border-orange-500/30">
                        <p class="text-orange-400">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Attention:</strong> Vous devez d'abord sélectionner un jeu dans votre
                            <a href="candidat-profil.php" class="underline hover:text-orange-300">profil</a>
                            avant de pouvoir postuler.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Liste des événements -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-calendar-check text-accent"></i>📅 Événements en préparation
                </h2>

                <?php if (empty($events)): ?>
                    <div class="glass-card rounded-3xl p-8 modern-border text-center">
                        <i class="fas fa-inbox text-4xl text-light/60 mb-3"></i>
                        <p class="text-light/60">Aucun événement disponible actuellement.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-8">
                        <?php foreach ($events as $event): ?>
                            <div class="glass-card rounded-3xl p-6 modern-border">
                                <!-- Header événement -->
                                <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                                    <div>
                                        <h3 class="text-2xl font-bold text-accent mb-2"><?php echo htmlspecialchars($event['titre']); ?></h3>
                                        <p class="text-light/60 text-sm mb-2">
                                            <?php echo htmlspecialchars($event['description'] ?? 'Pas de description'); ?>
                                        </p>
                                        <p class="text-light/60 text-sm">
                                            <i class="fas fa-calendar text-accent mr-2"></i>
                                            Du <?php echo date('d/m/Y', strtotime($event['date_debut'])); ?>
                                            au <?php echo date('d/m/Y', strtotime($event['date_fin'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">
                                        <i class="fas fa-clock mr-1"></i>En préparation
                                    </span>
                                </div>

                                <!-- Mes candidatures existantes -->
                                <?php if (!empty($event['mes_candidatures'])): ?>
                                    <div class="mb-6 p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-bold text-light mb-3">
                                            <i class="fas fa-clipboard-list text-accent mr-2"></i>Mes candidatures pour cet événement:
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($event['mes_candidatures'] as $cand):
                                                $statut = $cand['statut_candidature'] ?? 'en_attente';
                                                $statusClass = match ($statut) {
                                                    'approuve' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                                    'refuse' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                                    default => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
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
                                                <span class="px-3 py-2 rounded-lg text-sm border <?php echo $statusClass; ?>">
                                                    <i class="fas <?php echo $statusIcon; ?> mr-1"></i>
                                                    <?php echo htmlspecialchars($cand['categorie_nom'] ?? 'Catégorie'); ?>
                                                    <span class="opacity-75">(<?php echo $statusText; ?>)</span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Catégories disponibles -->
                                <?php if (empty($event['categories'])): ?>
                                    <div class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/30 text-center">
                                        <p class="text-orange-400">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Aucune catégorie définie pour cet événement. Revenez plus tard !
                                        </p>
                                    </div>
                                <?php elseif (empty($candidat['id_jeu'])): ?>
                                    <div class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/30 text-center">
                                        <p class="text-orange-400">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Sélectionnez d'abord un jeu dans votre profil pour postuler.
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <h4 class="font-bold text-light mb-4">
                                        <i class="fas fa-tags text-accent mr-2"></i>Choisissez une catégorie pour postuler:
                                    </h4>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="postuler">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id_evenement']; ?>">

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
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
                                                    class="block cursor-pointer p-4 rounded-xl border-2 border-white/10 bg-white/5 transition-all
            <?php echo $dejaPostule ? 'opacity-50 pointer-events-none border-green-500/50 bg-green-500/10' : ''; ?>">

                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex-1">
                                                            <p class="font-bold text-light"><?php echo htmlspecialchars($cat['nom']); ?></p>
                                                            <?php if ($cat['description']): ?>
                                                                <p class="text-xs text-light/60 mt-1"><?php echo htmlspecialchars($cat['description']); ?></p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($dejaPostule): ?>
                                                            <i class="fas fa-check text-green-400 flex-shrink-0 text-lg"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>

                                            <script>
                                                function changeColor(input) {
                                                    document.querySelectorAll('input[name="categorie_id"]').forEach(r => {
                                                        r.nextElementSibling.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                                                    });
                                                    input.nextElementSibling.style.borderColor = '#00ff88';
                                                }
                                            </script>




                                        </div>

                                        <button
                                            type="submit"
                                            class="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                            <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bouton retour -->
            <div class="text-center">
                <a href="candidat-profil.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/10 border border-white/20 hover:border-accent/50 transition-colors">
                    <i class="fas fa-arrow-left"></i> Retour au profil
                </a>
            </div>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>

</body>

</html>