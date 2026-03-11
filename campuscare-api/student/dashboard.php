<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$student = requireRole(['national', 'international']);
$pdo = getDbConnection();

$statement = $pdo->prepare(
    'SELECT c.id, c.title, c.description, c.status, c.created_at,
            cat.name AS category_name,
            u.name AS assigned_to_name
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u ON u.id = c.assigned_to
     WHERE c.student_id = :student_id
     ORDER BY c.created_at DESC'
);
$statement->execute(['student_id' => (int) $student['id']]);
$complaints = $statement->fetchAll();

function getStatusColorClass(string $status): string {
    switch ($status) {
        case 'submitted': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'in_progress': return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        case 'resolved': return 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/20';
        case 'closed': return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
        case 'escalated': return 'bg-red-500/10 text-red-500 border-red-500/20';
        default: return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
    }
}

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <a href="create_complaint.php" class="bg-[#1e1e1e] border border-[#333] hover:border-[#13ec87]/50 transition-colors p-6 rounded-2xl flex items-start gap-4 cursor-pointer group">
        <div class="p-3 bg-[#13ec87]/10 rounded-xl group-hover:bg-[#13ec87]/20 transition-colors">
            <i data-lucide="plus-circle" class="w-8 h-8 text-[#13ec87]"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-white mb-1">Create New Complaint</h3>
            <p class="text-sm text-gray-400">File a new issue related to your hostel, education, or general facilities.</p>
        </div>
    </a>

    <div class="bg-[#1e1e1e] border border-[#333] p-6 rounded-2xl flex items-start gap-4 cursor-default">
        <div class="p-3 bg-blue-500/10 rounded-xl">
            <i data-lucide="file-warning" class="w-8 h-8 text-blue-400"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-white mb-1">Total Active History</h3>
            <p class="text-sm text-gray-400">You have <?= count($complaints) ?> tickets loaded in your history.</p>
        </div>
    </div>
</div>

<div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-[#333] flex justify-between items-center">
        <h2 class="text-lg font-bold text-white">Recent Activity Tracker</h2>
    </div>
    
    <?php if (count($complaints) > 0): ?>
        <div class="divide-y divide-[#333]">
            <?php foreach ($complaints as $c): ?>
                <a href="../shared/view_complaint.php?id=<?= $c['id'] ?>" class="p-6 hover:bg-[#2a2a2a] transition-colors flex justify-between items-center block">
                    <div class="max-w-xl">
                        <h4 class="text-white font-medium text-lg mb-1"><?= htmlspecialchars($c['title']) ?></h4>
                        <p class="text-sm text-gray-400 truncate mb-2"><?= htmlspecialchars($c['description']) ?></p>
                        <div class="flex gap-3 text-xs text-gray-500">
                            <span>Category: <?= htmlspecialchars($c['category_name']) ?></span>
                            <span>•</span>
                            <span>Assigned to: <?= htmlspecialchars($c['assigned_to_name']) ?></span>
                            <span>•</span>
                            <span><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-3">
                        <span class="px-3 py-1 text-xs rounded border uppercase font-bold tracking-wider <?= getStatusColorClass($c['status']) ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $c['status'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-12 text-center text-gray-500 flex flex-col items-center">
            <i data-lucide="file-warning" class="w-12 h-12 mb-4 opacity-20"></i>
            <p>No recent complaints active for this term.</p>
        </div>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'Student Dashboard';
$pageSubtitle = "Welcome back, {$student['name']}. Manage your inquiries here.";

require_once __DIR__ . '/../components/layout.php';

