<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier que c'est un joueur
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'joueur') {
    header('Location: index.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$error = '';
$success = '';

// Récupérer les événements ouverts
$events = [];
try {
    $stmt = $connexion->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM registre_electoral WHERE id_evenement = e.id_evenement AND id_utilisateur = ?) as is_registered
        FROM evenement e
        WHERE e.statut = 'ouvert'
        ORDER BY e.date_ouverture DESC
    ");
    $stmt->execute([$id_utilisateur]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// S'inscrire à un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $id_evenement = intval($_POST['id_evenement'] ?? 0);
    
    try {
        // Vérifier que l'événement est ouvert
        $stmt = $connexion->prepare("SELECT statut FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $event = $stmt->fetch();
        
        if (!$event || $event['statut'] !== 'ouvert') {
            throw new Exception("Cet événement n'est pas ouvert aux inscriptions.");
        }
        
        // Vérifier que le joueur n'est pas déjà inscrit (correction du nom de table)
        $stmt = $connexion->prepare("SELECT id_registre FROM registre_electoral WHERE id_utilisateur = ? AND id_evenement = ?");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Vous êtes déjà inscrit à cet événement !";
        } else {
            // Inscrire le joueur (correction du nom de table)
            $stmt = $connexion->prepare("INSERT INTO registre_electoral (id_utilisateur, id_evenement, date_inscription) VALUES (?, ?, NOW())");
            $stmt->execute([$id_utilisateur, $id_evenement]);
            $success = "Inscription réussie ! Vous pouvez maintenant voter.";
            
            // Rafraîchir les événements
            $stmt = $connexion->prepare("
                SELECT e.*, 
                       (SELECT COUNT(*) FROM registre_electoral WHERE id_evenement = e.id_evenement AND id_utilisateur = ?) as is_registered
                FROM evenement e
                WHERE e.statut = 'ouvert'
                ORDER BY e.date_ouverture DESC
            ");
            $stmt->execute([$id_utilisateur]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - GameCrown</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">

<section class="py-20 px-6">
    <div class="container mx-auto max-w-6xl">
        
        <!-- En-tête -->
        <div class="mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                <i class="fas fa-calendar text-accent mr-3"></i>Événements Ouverts
            </h1>
            <p class="text-xl text-light-80">Inscrivez-vous pour pouvoir voter</p>
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

        <!-- Liste des événements -->
        <?php if (empty($events)): ?>
            <div class="glass-card rounded-3xl p-12 modern-border text-center">
                <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                <p class="text-xl text-light-80">Aucun événement ouvert actuellement.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($events as $event): ?>
                    <div class="glass-card rounded-3xl p-6 modern-border flex flex-col">
                        <div class="mb-4">
                            <h3 class="text-2xl font-bold text-light mb-2"><?php echo htmlspecialchars($event['nom']); ?></h3>
                            <?php if ($event['description']): ?>
                                <p class="text-sm text-light-80"><?php echo htmlspecialchars($event['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-2 mb-4 text-sm">
                            <p class="text-light-80">
                                <i class="fas fa-calendar-alt text-accent mr-2"></i>
                                Ouvert du <?php echo date('d/m/Y', strtotime($event['date_ouverture'])); ?>
                            </p>
                            <p class="text-light-80">
                                <i class="fas fa-clock text-accent mr-2"></i>
                                Fermeture : <?php echo date('d/m/Y H:i', strtotime($event['date_fermeture'])); ?>
                            </p>
                        </div>
                        
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium mb-4 bg-green-500/20 text-green-400 border border-green-500/30">
                            <i class="fas fa-check-circle mr-1"></i>Ouvert
                        </span>
                        
                        <?php if ($event['is_registered'] > 0): ?>
                            <div class="flex items-center gap-2 p-3 rounded-2xl bg-green-500/10 border border-green-500/30 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium">Inscrit</span>
                            </div>
                            <a href="vote.php" class="mt-2 text-center px-4 py-2 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                <i class="fas fa-vote-yea mr-2"></i>Aller voter
                            </a>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="register">
                                <input type="hidden" name="id_evenement" value="<?php echo $event['id_evenement']; ?>">
                                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Lien retour -->
        <div class="text-center mt-12">
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/5 border border-white/10 hover:border-accent/50 transition-colors">
                <i class="fas fa-arrow-left"></i> Retour au dashboard
            </a>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>
</body>
</html>