<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'classes/init.php';
use App\Services\AuditLogsService;
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo "<script>
        alert('Accès réservé aux administrateurs');
        window.location.href = './dashboard.php';
    </script>";
    exit;
}

$auditLogsService = ServiceContainer::getAuditLogsService();
$filters = [
    'user' => intval($_GET['user'] ?? 0),
    'action' => $_GET['action'] ?? '',
    'days' => intval($_GET['days'] ?? 30)
];
$page = intval($_GET['page'] ?? 1);
$data = $auditLogsService->getLogsWithFilters($filters, $page);
$actions = $auditLogsService->getAvailableActions();
$users = $auditLogsService->getAvailableUsers();
$selectedUserEmail = '';
if ($filters['user'] > 0) {
    foreach ($users as $user) {
        if ($user['id_utilisateur'] == $filters['user']) {
            $selectedUserEmail = $user['email'];
            break;
        }
    }
}

require_once 'header.php';
?>
<br><br><br>
<section class="py-20 px-6">
    <div class="container mx-auto max-w-7xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl md:text-6xl font-bold font-orbitron mb-4 accent-gradient">
                <i class="fas fa-shield-alt text-accent mr-3"></i>Logs de Sécurité
            </h1>
            <p class="text-xl text-light-80">Historique des actions</p>
        </div>
        <?php if ($data['error']): ?>
            <div class="mb-8 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400"><?php echo htmlspecialchars($data['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10 mb-8">
            <h2 class="text-2xl font-bold font-orbitron mb-6 flex items-center gap-2">
                <i class="fas fa-filter text-accent"></i> Filtres de recherche
            </h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block mb-2 text-light-80">Utilisateur</label>
                    <div class="relative">
                        <input type="text" 
                               id="userSearchInput" 
                               name="user_email" 
                               value="<?php echo htmlspecialchars($selectedUserEmail); ?>" 
                               placeholder="Rechercher un utilisateur..." 
                               autocomplete="off"
                               class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                        <input type="hidden" name="user" id="userId" value="<?php echo $filters['user']; ?>">
                        <div id="userSuggestions" class="absolute z-50 w-full mt-1 bg-dark-90 border border-white/10 rounded-2xl shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <i class="fas fa-search text-light-80"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 text-light-80">Action</label>
                    <div class="relative">
                        <select name="action" class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                            <option value="" class="text-black bg-white">Toutes les actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?> class="text-black bg-white">
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <i class="fas fa-chevron-down text-light-80"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 text-light-80">Période</label>
                    <div class="relative">
                        <select name="days" class="w-full px-4 py-3 pr-10 rounded-2xl bg-white/5 border border-white/10 text-light appearance-none focus:border-accent/50 focus:outline-none focus:ring-2 focus:ring-accent/30 transition-all duration-300">
                            <option value="1" <?php echo $filters['days'] === 1 ? 'selected' : ''; ?> class="text-black bg-white">Dernier jour</option>
                            <option value="7" <?php echo $filters['days'] === 7 ? 'selected' : ''; ?> class="text-black bg-white">7 derniers jours</option>
                            <option value="30" <?php echo $filters['days'] === 30 ? 'selected' : ''; ?> class="text-black bg-white">30 derniers jours</option>
                            <option value="90" <?php echo $filters['days'] === 90 ? 'selected' : ''; ?> class="text-black bg-white">90 derniers jours</option>
                            <option value="0" <?php echo $filters['days'] === 0 ? 'selected' : ''; ?> class="text-black bg-white">Tous les logs</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                            <i class="fas fa-chevron-down text-light-80"></i>
                        </div>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full px-6 py-3 rounded-2xl bg-accent text-dark font-bold hover:bg-accent/80 transition-colors border border-white/10">
                        <i class="fas fa-search mr-2"></i>Appliquer
                    </button>
                    <button type="button" id="clearUserFilter" class="px-4 py-3 rounded-2xl bg-white/10 text-light hover:bg-white/20 transition-colors border border-white/10">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tableau des logs -->
        <div class="glass-card rounded-3xl p-8 modern-border border-2 border-white/10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold font-orbitron flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-accent"></i> Historique des actions
                </h2>
                <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-light-80 text-sm">
                    <i class="fas fa-list mr-2"></i> <?php echo $data['total']; ?> résultat(s) | Page <?php echo $data['current_page']; ?>/<?php echo $data['pages']; ?>
                </div>
            </div>
            <?php if (empty($data['logs'])): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-4xl text-light-80 mb-3"></i>
                    <p class="text-light-80">Aucun log trouvé avec ces critères.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent uppercase tracking-wider">
                                    <i class="fas fa-calendar-alt mr-2"></i> Date/Heure
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent uppercase tracking-wider">
                                    <i class="fas fa-user mr-2"></i> Utilisateur
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent uppercase tracking-wider">
                                    <i class="fas fa-cog mr-2"></i> Action
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-accent uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-2"></i> Détails
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($data['logs'] as $log): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 text-light-80">
                                        <div class="flex flex-col">
                                            <span class="font-medium"><?php echo date('d/m/Y', strtotime($log['date_action'])); ?></span>
                                            <span class="text-xs opacity-70"><?php echo date('H:i:s', strtotime($log['date_action'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center">
                                                <i class="fas fa-user text-accent text-xs"></i>
                                            </div>
                                            <span class="text-light font-medium">
                                                <?php echo htmlspecialchars($log['email'] ?? 'Système'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1.5 rounded-full bg-accent/20 text-accent text-xs font-medium flex items-center gap-2 w-fit">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-light-80 max-w-md">
                                        <div class="bg-white/5 p-3 rounded-xl border border-white/10">
                                            <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 pt-6 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-light-80">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-info-circle text-accent"></i>
                        <span>Affichage de <?php echo ($data['offset'] + 1); ?> à <?php echo min($data['offset'] + $data['per_page'], $data['total']); ?> sur <?php echo $data['total']; ?> logs</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock text-accent"></i>
                        <span>Mis à jour à <?php echo date('H:i:s'); ?></span>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($data['pages'] > 1): ?>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
                    <?php if ($data['current_page'] > 1): ?>
                        <a href="?page=<?php echo $data['current_page'] - 1; ?>&user=<?php echo $filters['user']; ?>&action=<?php echo urlencode($filters['action']); ?>&days=<?php echo $filters['days']; ?>" class="px-4 py-2 rounded-xl bg-accent text-dark font-medium hover:bg-accent/80 transition-colors flex items-center gap-2">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php else: ?>
                        <button disabled class="px-4 py-2 rounded-xl bg-white/10 text-light-80 font-medium opacity-50 cursor-not-allowed flex items-center gap-2">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </button>
                    <?php endif; ?>
                    <div class="flex gap-1">
                        <?php
                        $start = max(1, $data['current_page'] - 2);
                        $end = min($data['pages'], $data['current_page'] + 2);
                        if ($start > 1): ?>
                            <a href="?page=1&user=<?php echo $filters['user']; ?>&action=<?php echo urlencode($filters['action']); ?>&days=<?php echo $filters['days']; ?>" class="px-3 py-2 rounded-lg bg-white/10 text-light hover:bg-white/20 transition-colors">1</a>
                            <?php if ($start > 2): ?>
                                <span class="px-3 py-2 text-light-80">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i === $data['current_page']): ?>
                                <button disabled class="px-3 py-2 rounded-lg bg-accent text-dark font-bold">
                                    <?php echo $i; ?>
                                </button>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&user=<?php echo $filters['user']; ?>&action=<?php echo urlencode($filters['action']); ?>&days=<?php echo $filters['days']; ?>" class="px-3 py-2 rounded-lg bg-white/10 text-light hover:bg-white/20 transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($end < $data['pages']): ?>
                            <?php if ($end < $data['pages'] - 1): ?>
                                <span class="px-3 py-2 text-light-80">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $data['pages']; ?>&user=<?php echo $filters['user']; ?>&action=<?php echo urlencode($filters['action']); ?>&days=<?php echo $filters['days']; ?>" class="px-3 py-2 rounded-lg bg-white/10 text-light hover:bg-white/20 transition-colors">
                                <?php echo $data['pages']; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($data['current_page'] < $data['pages']): ?>
                        <a href="?page=<?php echo $data['current_page'] + 1; ?>&user=<?php echo $filters['user']; ?>&action=<?php echo urlencode($filters['action']); ?>&days=<?php echo $filters['days']; ?>" class="px-4 py-2 rounded-xl bg-accent text-dark font-medium hover:bg-accent/80 transition-colors flex items-center gap-2">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button disabled class="px-4 py-2 rounded-xl bg-white/10 text-light-80 font-medium opacity-50 cursor-not-allowed flex items-center gap-2">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearchInput = document.getElementById('userSearchInput');
    const userIdInput = document.getElementById('userId');
    const userSuggestions = document.getElementById('userSuggestions');
    const clearUserFilter = document.getElementById('clearUserFilter');
    const users = <?php echo json_encode($users); ?>;
    function filterUsers(query) {
        return users.filter(user => 
            user.email.toLowerCase().includes(query.toLowerCase())
        );
    }
    
    function showSuggestions(results) {
        userSuggestions.innerHTML = '';
        
        if (results.length === 0) {
            userSuggestions.classList.add('hidden');
            return;
        }
        
        results.forEach(user => {
            const div = document.createElement('div');
            div.className = 'px-4 py-3 hover:bg-white/10 cursor-pointer transition-colors';
            div.textContent = user.email;
            div.dataset.id = user.id_utilisateur;
            div.dataset.email = user.email;
            
            div.addEventListener('click', function() {
                userSearchInput.value = this.dataset.email;
                userIdInput.value = this.dataset.id;
                userSuggestions.classList.add('hidden');
            });
            
            userSuggestions.appendChild(div);
        });
        
        userSuggestions.classList.remove('hidden');
    }
    
    // Gestion de la recherche
    let searchTimeout;
    userSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        if (this.value.length === 0) {
            userIdInput.value = 0;
            userSuggestions.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            const results = filterUsers(this.value);
            showSuggestions(results);
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (!userSearchInput.contains(e.target) && !userSuggestions.contains(e.target)) {
            userSuggestions.classList.add('hidden');
        }
    });
    
    clearUserFilter.addEventListener('click', function() {
        userSearchInput.value = '';
        userIdInput.value = 0;
        userSuggestions.classList.add('hidden');
    });
    
    userSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            userSuggestions.classList.add('hidden');
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>