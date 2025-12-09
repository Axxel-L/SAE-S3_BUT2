<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'dbconnect.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$filters = [
    'user' => intval($_GET['user'] ?? 0),
    'action' => $_GET['action'] ?? '',
    'days' => intval($_GET['days'] ?? 30)
];

// Récupérer les logs
$logs = [];
try {
    $query = "
        SELECT j.*, u.email 
        FROM journal_securite j
        LEFT JOIN utilisateur u ON j.id_utilisateur = u.id_utilisateur
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($filters['user'] > 0) {
        $query .= " AND j.id_utilisateur = ?";
        $params[] = $filters['user'];
    }
    
    if (!empty($filters['action'])) {
        $query .= " AND j.action = ?";
        $params[] = $filters['action'];
    }
    
    if ($filters['days'] > 0) {
        $query .= " AND j.date_action >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $filters['days'];
    }
    
    $query .= " ORDER BY j.date_action DESC LIMIT 500";
    
    $stmt = $connexion->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

// Récupérer les actions uniques
$actions = [];
try {
    $stmt = $connexion->prepare("SELECT DISTINCT action FROM journal_securite ORDER BY action");
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Ignorer
}

// Récupérer les utilisateurs
$users = [];
try {
    $stmt = $connexion->prepare("SELECT id_utilisateur, email FROM utilisateur ORDER BY email");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignorer
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameCrown - Logs de Sécurité</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="http://cdn.agence-prestige-numerique.fr/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>
<body class="font-inter bg-dark text-light">
    <?php require_once 'header.php'; ?>

    <section class="py-20 px-6">
        <div class="container mx-auto max-w-7xl">
            <!-- En-tête -->
            <div class="mb-12 flex items-center justify-between">
                <div>
                    <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4">
                        <i class="fas fa-shield-alt text-accent mr-3"></i>Logs de Sécurité
                    </h1>
                    <p class="text-xl text-light80">Historique des actions utilisateurs</p>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                    <span class="text-red-400"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="glass-card rounded-3xl p-6 modern-border mb-8">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <!-- Utilisateur -->
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-light80 mb-2">Utilisateur</label>
                        <select name="user" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-light">
                            <option value="0">Tous</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id_utilisateur']; ?>" <?php echo $filters['user'] === $user['id_utilisateur'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Action -->
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-light80 mb-2">Action</label>
                        <select name="action" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-light">
                            <option value="">Toutes</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Période -->
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-light80 mb-2">Période</label>
                        <select name="days" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-light">
                            <option value="1" <?php echo $filters['days'] === 1 ? 'selected' : ''; ?>>Dernier jour</option>
                            <option value="7" <?php echo $filters['days'] === 7 ? 'selected' : ''; ?>>7 derniers jours</option>
                            <option value="30" <?php echo $filters['days'] === 30 ? 'selected' : ''; ?>>30 derniers jours</option>
                            <option value="90" <?php echo $filters['days'] === 90 ? 'selected' : ''; ?>>90 derniers jours</option>
                            <option value="0" <?php echo $filters['days'] === 0 ? 'selected' : ''; ?>>Tous les logs</option>
                        </select>
                    </div>

                    <!-- Bouton -->
                    <button type="submit" class="px-6 py-2 rounded-lg bg-accent text-dark font-medium hover:bg-accent/90 transition-colors">
                        <i class="fas fa-search mr-2"></i>Filtrer
                    </button>
                </form>
            </div>

            <!-- Tableau des logs -->
            <div class="glass-card rounded-3xl p-6 modern-border overflow-x-auto">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-light80 mb-4"></i>
                        <p class="text-light80">Aucun log trouvé.</p>
                    </div>
                <?php else: ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent">Date/Heure</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent">Détails</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 text-light80">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['date_action'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-light">
                                        <?php echo htmlspecialchars($log['email'] ?? 'Système'); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-lg bg-accent/20 text-accent text-xs font-medium">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-light80 max-w-xs truncate">
                                        <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-4 text-center text-light80 text-sm">
                        <?php echo count($logs); ?> résultat(s) trouvé(s)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>