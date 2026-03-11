<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$warden = requireRole(['warden']);
$pdo = getDbConnection();

$statement = $pdo->prepare(
    'SELECT c.id, c.title, c.description, c.status, c.created_at,
            cat.name AS category_name,
            u.name AS student_name, u.room_number
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u ON u.id = c.student_id
     WHERE c.assigned_to = :assigned_to OR c.hostel_id = :hostel_id
     ORDER BY c.created_at DESC'
);
$statement->execute([
    'assigned_to' => (int) $warden['id'],
    'hostel_id' => (int) $warden['hostel_id']
]);
$complaints = $statement->fetchAll();

$activeCount = count(array_filter($complaints, fn($c) => in_array($c['status'], ['submitted', 'in_progress'])));
$escalatedCount = count(array_filter($complaints, fn($c) => $c['status'] === 'escalated'));

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex flex-col items-center text-center hover:border-emerald-400/50 transition-colors cursor-default">
        <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mb-4 text-emerald-400">
            <i data-lucide="home" class="w-8 h-8"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2"><?= $activeCount ?> Active</h3>
        <p class="text-sm text-gray-400">Hostel Infrastructure</p>
    </div>
    
    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex flex-col items-center text-center hover:border-red-400/50 transition-colors cursor-default">
        <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mb-4 text-red-400">
            <i data-lucide="tool" class="w-8 h-8"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2"><?= $escalatedCount ?> Urgent</h3>
        <p class="text-sm text-gray-400">Maintenance Fast-Track</p>
    </div>
</div>

<div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-[#333] flex justify-between">
        <h2 class="text-lg font-bold text-white">Hostel Complaints & Maintenance</h2>
    </div>
    
    <?php if (count($complaints) > 0): ?>
        <div class="divide-y divide-[#333]">
            <?php foreach($complaints as $c): ?>
                <a href="../shared/view_complaint.php?id=<?= $c['id'] ?>" class="p-4 hover:bg-[#2a2a2a] flex justify-between items-center transition-colors block">
                    <div>
                        <h4 class="text-white font-medium"><?= htmlspecialchars($c['title']) ?></h4>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($c['student_name']) ?> (Room: <?= htmlspecialchars((string) ($c['room_number'] ?? 'N/A')) ?>) • <?= date('M j, Y', strtotime($c['created_at'])) ?></p>
                    </div>
                    <span class="px-2 py-1 text-xs border border-gray-500/30 rounded text-gray-400 uppercase font-bold tracking-wider"><?= htmlspecialchars(str_replace('_', ' ', $c['status'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-8 text-center text-gray-500 flex flex-col items-center">
             <i data-lucide="shield-check" class="w-10 h-10 mb-2 opacity-30 text-emerald-500"></i>
            <p>Infrastructure is optimal. No active complaints.</p>
        </div>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'Hostel Warden Console';
$pageSubtitle = "Welcome, {$warden['name']} - Track and manage hostel facilities.";
require_once __DIR__ . '/../components/layout.php';
