<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier admin
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_evenement = intval($_GET['event'] ?? 0);
$error = '';
$success = '';

// Récupérer l'événement
try {
    $stmt = $connexion->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
    $stmt->execute([$id_evenement]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: admin-events.php');
        exit;
    }
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Récupérer les catégories de cet événement
$categories = [];
try {
    $stmt = $connexion->prepare("
        SELECT c.*, COUNT(DISTINCT n.id_nomination) as nb_jeux
        FROM categorie c
        LEFT JOIN nomination n ON c.id_categorie = n.id_categorie
        WHERE c.id_evenement = ?
        GROUP BY c.id_categorie
        ORDER BY c.nom ASC
    ");
    $stmt->execute([$id_evenement]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// CRÉER UNE CATÉGORIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_category') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!empty($nom)) {
        try {
            $stmt = $connexion->prepare("
                INSERT INTO categorie (nom, description, id_evenement) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nom, $description, $id_evenement]);
            $success = "Catégorie créée avec succès !";
            
            // Log
            $stmt = $connexion->prepare("
                INSERT INTO journal_securite (id_utilisateur, action, details) 
                VALUES (?, 'ADMIN_CATEGORY_CREATE', ?)
            ");
            $stmt->execute([$id_utilisateur, "Catégorie '$nom' créée pour événement #$id_evenement"]);
            
            // Rafraîchir
            $stmt = $connexion->prepare("
                SELECT c.*, COUNT(DISTINCT n.id_nomination) as nb_jeux
                FROM categorie c
                LEFT JOIN nomination n ON c.id_categorie = n.id_categorie
                WHERE c.id_evenement = ?
                GROUP BY c.id_categorie
                ORDER BY c.nom ASC
            ");
            $stmt->execute([$id_evenement]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Le nom est obligatoire !";
    }
}

// SUPPRIMER UNE CATÉGORIE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    
    try {
        $stmt = $connexion->prepare("SELECT nom FROM categorie WHERE id_categorie = ?");
        $stmt->execute([$id_categorie]);
        $cat = $stmt->fetch();
        
        $stmt = $connexion->prepare("DELETE FROM categorie WHERE id_categorie = ? AND id_evenement = ?");
        $stmt->execute([$id_categorie, $id_evenement]);
        $success = "Catégorie supprimée !";
        
        // Rafraîchir
        $stmt = $connexion->prepare("
            SELECT c.*, COUNT(DISTINCT n.id_nomination) as nb_jeux
            FROM categorie c
            LEFT JOIN nomination n ON c.id_categorie = n.id_categorie
            WHERE c.id_evenement = ?
            GROUP BY c.id_categorie
            ORDER BY c.nom ASC
        ");
        $stmt->execute([$id_evenement]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Compter les candidatures en attente pour cet événement
$nbCandidaturesAttente = 0;
try {
    $stmt = $connexion->prepare("
        SELECT COUNT(*) FROM event_candidat 
        WHERE id_evenement = ? AND statut_candidature = 'en_attente'
    ");
    $stmt->execute([$id_evenement]);
    $nbCandidaturesAttente = $stmt->fetchColumn();
} catch (Exception $e) {
    // La colonne n'existe peut-être pas encore
    $nbCandidaturesAttente = 0;
}

require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - GameCrown</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">

<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        
        <!-- En-tête -->
        <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                    <i class="fas fa-tags text-accent mr-3"></i>Catégories
                </h1>
                <p class="text-xl text-light/80"><?php echo htmlspecialchars($event['nom']); ?></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <?php if ($nbCandidaturesAttente > 0): ?>
                <a href="admin-candidatures.php?event=<?php echo $id_evenement; ?>&status=en_attente" class="px-6 py-3 rounded-2xl bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 hover:bg-yellow-500/30 transition-colors flex items-center gap-2">
                    <i class="fas fa-bell"></i> 
                    <span class="font-bold"><?php echo $nbCandidaturesAttente; ?></span> candidature(s) en attente
                </a>
                <?php endif; ?>
                <a href="admin-events.php" class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Retour aux événements
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-8 p-4 rounded-2xl bg-green-500/10 border border-green-500/30 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-400"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Info sur le nouveau système -->
        <div class="mb-8 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30">
            <p class="text-blue-400">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Comment ça marche :</strong> Créez des catégories ici. Les candidats postuleront ensuite à ces catégories avec leur jeu. 
                Vous pourrez approuver ou refuser leurs candidatures dans la page 
                <a href="admin-candidatures.php?event=<?php echo $id_evenement; ?>" class="underline hover:text-blue-300">Candidatures</a>.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Formulaire création catégorie -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-3xl p-8 modern-border sticky top-8">
                    <h2 class="text-2xl font-bold mb-6">
                        <i class="fas fa-plus text-accent mr-2"></i>Créer une catégorie
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_category">
                        
                        <div>
                            <label class="block mb-2 text-light/80">Nom *</label>
                            <input type="text" name="nom" required class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" placeholder="Ex: Meilleur Gameplay">
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-light/80">Description</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 focus:border-accent/50 outline-none text-light" placeholder="Description de la catégorie..."></textarea>
                        </div>
                        
                        <button type="submit" class="w-full px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Créer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Liste des catégories -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-3xl p-8 modern-border">
                    <h2 class="text-2xl font-bold mb-6">
                        <i class="fas fa-list text-accent mr-2"></i>Catégories (<?php echo count($categories); ?>)
                    </h2>
                    
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-light/60 mb-3"></i>
                            <p class="text-light/60">Aucune catégorie créée.</p>
                            <p class="text-sm text-light/40 mt-2">Créez des catégories pour que les candidats puissent postuler.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                // Récupérer les jeux nominés (approuvés) dans cette catégorie
                                $jeux_nomines = [];
                                try {
                                    $stmt = $connexion->prepare("
                                        SELECT j.id_jeu, j.titre, j.image, j.editeur
                                        FROM nomination n
                                        JOIN jeu j ON n.id_jeu = j.id_jeu
                                        WHERE n.id_categorie = ? AND n.id_evenement = ?
                                        ORDER BY j.titre ASC
                                    ");
                                    $stmt->execute([$cat['id_categorie'], $id_evenement]);
                                    $jeux_nomines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) {
                                    // Ignorer
                                }
                                
                                // Compter les candidatures en attente pour cette catégorie
                                $nbAttenteCat = 0;
                                try {
                                    $stmt = $connexion->prepare("
                                        SELECT COUNT(*) FROM event_candidat 
                                        WHERE id_categorie = ? AND id_evenement = ? AND statut_candidature = 'en_attente'
                                    ");
                                    $stmt->execute([$cat['id_categorie'], $id_evenement]);
                                    $nbAttenteCat = $stmt->fetchColumn();
                                } catch (Exception $e) {
                                    // Ignorer
                                }
                                ?>
                                
                                <div class="glass-card rounded-2xl p-6 modern-border">
                                    <div class="flex items-start justify-between gap-4 mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-light mb-1"><?php echo htmlspecialchars($cat['nom']); ?></h3>
                                            <?php if ($cat['description']): ?>
                                                <p class="text-sm text-light/60"><?php echo htmlspecialchars($cat['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Bouton supprimer -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id_categorie" value="<?php echo $cat['id_categorie']; ?>">
                                            <button type="submit" class="px-3 py-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors" onclick="return confirm('Supprimer cette catégorie ?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div class="flex flex-wrap gap-3 mb-4">
                                        <span class="px-3 py-1 rounded-lg bg-green-500/20 text-green-400 text-sm">
                                            <i class="fas fa-gamepad mr-1"></i>
                                            <?php echo count($jeux_nomines); ?> jeu(x) nominé(s)
                                        </span>
                                        <?php if ($nbAttenteCat > 0): ?>
                                        <a href="admin-candidatures.php?event=<?php echo $id_evenement; ?>&status=en_attente" class="px-3 py-1 rounded-lg bg-yellow-500/20 text-yellow-400 text-sm hover:bg-yellow-500/30 transition-colors">
                                            <i class="fas fa-hourglass-half mr-1"></i>
                                            <?php echo $nbAttenteCat; ?> en attente
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Jeux nominés -->
                                    <?php if (!empty($jeux_nomines)): ?>
                                        <div class="border-t border-white/10 pt-4">
                                            <p class="text-xs text-light/50 mb-3">Jeux approuvés (apparaîtront dans les votes) :</p>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($jeux_nomines as $jeu): ?>
                                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                                                        <?php if ($jeu['image']): ?>
                                                            <img src="<?php echo htmlspecialchars($jeu['image']); ?>" alt="" class="w-8 h-8 rounded object-cover">
                                                        <?php else: ?>
                                                            <div class="w-8 h-8 rounded bg-white/10 flex items-center justify-center">
                                                                <i class="fas fa-gamepad text-xs text-light/50"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="text-sm text-light"><?php echo htmlspecialchars($jeu['titre']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="border-t border-white/10 pt-4">
                                            <p class="text-xs text-light/40 italic">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Aucun jeu nominé. Approuvez des candidatures pour ajouter des jeux.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>
</body>
</html>