<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$admin = requireRole(['admin']);
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle Reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reassign') {
    $complaintId = (int) ($_POST['complaint_id'] ?? 0);
    $newAssigneeId = (int) ($_POST['new_assignee_id'] ?? 0);

    if ($complaintId > 0 && $newAssigneeId > 0) {
        $update = $pdo->prepare('UPDATE complaints SET assigned_to = :assigned_to WHERE id = :id');
        $update->execute(['assigned_to' => $newAssigneeId, 'id' => $complaintId]);
        $message = 'Complaint reassigned successfully.';
        $messageType = 'success';
    } else {
        $message = 'Invalid parameters for reassignment.';
        $messageType = 'error';
    }
}

// Fetch all staff members for the dropdown
$staffStatement = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('mentor', 'warden', 'iro') ORDER BY name ASC");
$staffMembers = $staffStatement->fetchAll();

// Fetch complaints
$statement = $pdo->prepare(
    'SELECT c.id, c.title, c.description, c.status, c.created_at, 
            cat.name AS category_name,
            u_student.name AS student_name,
            u_assignee.id AS assignee_id,
            u_assignee.name AS assignee_name
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u_student ON u_student.id = c.student_id
     LEFT JOIN users u_assignee ON u_assignee.id = c.assigned_to
     ORDER BY c.created_at DESC'
);
$statement->execute();
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
<div class="mb-8 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-white tracking-tight">System Complaints</h2>
        <p class="text-sm text-gray-400 mt-1">Review, monitor, and reassign all active institutional tickets.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 border <?= $messageType === 'success' ? 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/30' : 'bg-red-500/10 text-red-500 border-red-500/30' ?>">
        <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($message) ?></p>
    </div>
<?php endif; ?>

<div class="bg-[#1e1e1e] border border-[#333] rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
                <tr class="bg-[#2a2a2a] border-b border-[#333]">
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Issue</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Assigned Staff</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#333]">
                <?php foreach ($complaints as $c): ?>
                <tr class="hover:bg-[#2a2a2a]/50 transition-colors cursor-default">
                    <td class="px-6 py-4 text-sm text-gray-300 whitespace-nowrap">
                        <?= date('M j, Y', strtotime($c['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 max-w-[250px]">
                        <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($c['title']) ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($c['category_name']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-300">
                        <?= htmlspecialchars($c['student_name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-[10px] rounded border uppercase font-bold tracking-wider <?= getStatusColorClass($c['status']) ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $c['status'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                        <!-- Quick Reassign Form -->
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="action" value="reassign">
                            <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                            <select name="new_assignee_id" onchange="this.form.submit()" class="bg-[#121212] border border-[#333] text-gray-300 text-xs rounded-lg px-2 py-1.5 focus:outline-none focus:border-[#13ec87] w-full max-w-[160px] cursor-pointer hover:border-gray-500 transition-colors">
                                <?php foreach ($staffMembers as $staff): ?>
                                    <option value="<?= $staff['id'] ?>" <?= ((int)$c['assignee_id'] === $staff['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($complaints)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">No complaints found in the system.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'Manage Complaints';
$pageSubtitle = '';
require_once __DIR__ . '/../components/layout.php';
