<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$user = requireRole(['admin']);
$pdo = getDbConnection();

$summary = [
    'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'total_complaints' => (int) $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn(),
    'open_complaints' => (int) $pdo->query(
        "SELECT COUNT(*) FROM complaints WHERE status IN ('submitted', 'in_progress', 'escalated')"
    )->fetchColumn(),
    'closed_complaints' => (int) $pdo->query(
        "SELECT COUNT(*) FROM complaints WHERE status = 'closed'"
    )->fetchColumn(),
];

$complaintsByCategory = $pdo->query(
    'SELECT cat.name AS category_name, cat.route_to, COUNT(c.id) AS total
     FROM categories cat
     LEFT JOIN complaints c ON c.category_id = cat.id
     GROUP BY cat.id, cat.name, cat.route_to
     ORDER BY total DESC, cat.name ASC'
)->fetchAll();

$usersByRole = $pdo->query(
    'SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role
     ORDER BY total DESC'
)->fetchAll();

// HTML Rendering Start
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex items-center gap-4">
        <div class="p-4 bg-[#2a2a2a] rounded-xl border border-[#444]">
            <i data-lucide="users" class="w-6 h-6 text-blue-400"></i>
        </div>
        <div>
            <h4 class="text-3xl font-extrabold text-white"><?= $summary['total_users'] ?></h4>
            <p class="text-sm text-gray-400 font-medium">Total Active Users</p>
        </div>
    </div>
    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex items-center gap-4">
        <div class="p-4 bg-[#2a2a2a] rounded-xl border border-[#444]">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-400"></i>
        </div>
        <div>
            <h4 class="text-3xl font-extrabold text-white"><?= $summary['open_complaints'] ?></h4>
            <p class="text-sm text-gray-400 font-medium">Open Queries</p>
        </div>
    </div>
    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex items-center gap-4">
        <div class="p-4 bg-[#2a2a2a] rounded-xl border border-[#444]">
            <i data-lucide="check-circle" class="w-6 h-6 text-[#13ec87]"></i>
        </div>
        <div>
            <h4 class="text-3xl font-extrabold text-white"><?= $summary['closed_complaints'] ?></h4>
            <p class="text-sm text-gray-400 font-medium">Closed Issues</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-[#333] flex justify-between items-center">
            <h2 class="text-lg font-bold text-white">Complaints by Category</h2>
            <button class="text-xs text-[#13ec87] hover:underline">View All</button>
        </div>
        <div class="p-6 space-y-4">
            <?php if (count($complaintsByCategory) > 0): ?>
                <?php foreach ($complaintsByCategory as $cat): ?>
                    <div class="flex justify-between items-center p-3 bg-[#2a2a2a] rounded-lg border border-[#444]">
                        <span class="text-sm font-medium text-white"><?= htmlspecialchars($cat['category_name']) ?></span>
                        <span class="text-sm font-bold text-gray-300"><?= $cat['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">No category data available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-[#333] flex justify-between items-center">
            <h2 class="text-lg font-bold text-white">User Distribution</h2>
            <button class="text-xs text-[#13ec87] hover:underline">Manage Users</button>
        </div>
        <div class="p-6 space-y-4">
            <?php if (count($usersByRole) > 0): ?>
                <?php foreach ($usersByRole as $roleInfo): ?>
                    <div class="flex justify-between items-center p-3 bg-[#2a2a2a] rounded-lg border border-[#444]">
                        <span class="text-sm font-medium text-white capitalize"><?= htmlspecialchars($roleInfo['role']) ?></span>
                        <span class="text-sm font-bold text-gray-300"><?= $roleInfo['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">No user data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'System Admin Overview';
$pageSubtitle = 'Global platform analytics and user administration.';

require_once __DIR__ . '/../components/layout.php';

