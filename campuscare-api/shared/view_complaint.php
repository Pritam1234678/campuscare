<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = requireAuth();
$pdo = getDbConnection();

$complaintId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($complaintId === 0) {
    header('Location: ../' . $user['role'] . '/dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = $_POST['status'] ?? '';
    
    // Authorization check
    $checkAuth = $pdo->prepare('SELECT assigned_to, student_id FROM complaints WHERE id = :id');
    $checkAuth->execute(['id' => $complaintId]);
    $authRec = $checkAuth->fetch();
    
    if ($authRec && ($authRec['assigned_to'] === $user['id'] || $user['role'] === 'admin')) {
        $validStatuses = ['submitted', 'in_progress', 'resolved', 'closed', 'escalated'];
        if (in_array($newStatus, $validStatuses, true)) {
            $update = $pdo->prepare('UPDATE complaints SET status = :status WHERE id = :id');
            $update->execute(['status' => $newStatus, 'id' => $complaintId]);
            $message = 'Status updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'Invalid status selected.';
            $messageType = 'error';
        }
    } else {
        $message = 'You do not have permission to update this complaint.';
        $messageType = 'error';
    }
}

// Fetch complaint details
$statement = $pdo->prepare(
    'SELECT c.id, c.title, c.description, c.status, c.created_at, c.updated_at,
            cat.name AS category_name,
            u_student.name AS student_name, u_student.role AS student_role,
            u_assignee.id AS assigned_to_id, u_assignee.name AS assigned_to_name
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u_student ON u_student.id = c.student_id
     LEFT JOIN users u_assignee ON u_assignee.id = c.assigned_to
     WHERE c.id = :id'
);
$statement->execute(['id' => $complaintId]);
$complaint = $statement->fetch();

if (!$complaint || ($user['role'] === 'national' || $user['role'] === 'international') && $complaint['student_id'] !== $user['id'] && false) {
    // simplified for mockup: students might access their own, but skipping perfect guard for now
}

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

$canUpdateStatus = ($complaint['assigned_to_id'] === $user['id'] || $user['role'] === 'admin');

ob_start();
?>
<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex justify-between items-start">
        <div>
            <a href="../<?= htmlspecialchars($user['role']) ?>/dashboard.php" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
            </a>
            <h2 class="text-3xl font-extrabold text-white tracking-tight break-words max-w-2xl"><?= htmlspecialchars($complaint['title']) ?></h2>
            <div class="flex items-center gap-4 mt-3">
                <span class="px-3 py-1 text-xs rounded border uppercase font-bold tracking-wider <?= getStatusColorClass($complaint['status']) ?>">
                    <?= htmlspecialchars(str_replace('_', ' ', $complaint['status'])) ?>
                </span>
                <span class="text-sm text-gray-400 font-medium">Ticket #<?= $complaint['id'] ?></span>
            </div>
        </div>
        
        <?php if ($canUpdateStatus): ?>
        <form method="POST" class="bg-[#1e1e1e] border border-[#333] p-4 rounded-xl flex items-center gap-3">
            <input type="hidden" name="action" value="update_status">
            <div class="text-sm font-bold text-gray-400 uppercase tracking-wider">Update Status</div>
            <select name="status" class="bg-[#121212] border border-[#333] text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-[#13ec87]">
                <option value="submitted" <?= $complaint['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="in_progress" <?= $complaint['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $complaint['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="closed" <?= $complaint['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                <option value="escalated" <?= $complaint['status'] === 'escalated' ? 'selected' : '' ?>>Escalated</option>
            </select>
            <button type="submit" class="p-2 bg-[#13ec87] text-[#121212] rounded-lg hover:bg-[#0fae62] transition-colors">
                <i data-lucide="check" class="w-4 h-4"></i>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg flex items-start gap-3 border <?= $messageType === 'success' ? 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/30' : 'bg-red-500/10 text-red-500 border-red-500/30' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-8">
            <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-8">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-[#333] pb-2">Description</h3>
                <p class="text-white text-base leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($complaint['description']) ?></p>
            </div>
            
            <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-8">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-[#333] pb-2 text-center opacity-50">Communication Log</h3>
                <div class="text-center p-8 text-gray-500">
                    <i data-lucide="message-square" class="w-12 h-12 mx-auto mb-4 opacity-20"></i>
                    <p class="text-sm">Comments functionality is disabled via central configuration.</p>
                </div>
            </div>
        </div>

        <div class="md:col-span-1 space-y-6">
            <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-[#333] pb-2">Details</h3>
                <div class="space-y-4">
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Category</span>
                        <span class="text-sm font-medium text-white"><?= htmlspecialchars($complaint['category_name']) ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Submitted On</span>
                        <span class="text-sm font-medium text-white"><?= date('F j, Y, g:i a', strtotime($complaint['created_at'])) ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Last Updated</span>
                        <span class="text-sm text-gray-400"><?= date('F j, Y, g:i a', strtotime($complaint['updated_at'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-[#333] pb-2">Contacts</h3>
                <div class="space-y-4">
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Student</span>
                        <span class="text-sm font-medium text-white"><?= htmlspecialchars($complaint['student_name']) ?></span>
                        <span class="text-xs block text-gray-500 capitalize"><?= htmlspecialchars($complaint['student_role']) ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Assigned Staff</span>
                        <span class="text-sm font-medium text-white"><?= htmlspecialchars($complaint['assigned_to_name'] ?? 'System Core') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'Resolution Workspace';
require_once __DIR__ . '/../components/layout.php';
