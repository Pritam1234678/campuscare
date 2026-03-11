<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$student = requireRole(['national', 'international']);
$pdo = getDbConnection();

// Fetch categories for the dropdown
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($description) || $categoryId === 0) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } else {
        $categoryStatement = $pdo->prepare('SELECT id, route_to FROM categories WHERE id = :id LIMIT 1');
        $categoryStatement->execute(['id' => $categoryId]);
        $category = $categoryStatement->fetch();

        if (!$category) {
            $message = 'Invalid category selected.';
            $messageType = 'error';
        } else {
            try {
                $assigneeId = resolveComplaintAssignee($pdo, $student, $category['route_to']);

                $insert = $pdo->prepare(
                    'INSERT INTO complaints (student_id, category_id, title, description, assigned_to, status)
                     VALUES (:student_id, :category_id, :title, :description, :assigned_to, :status)'
                );
                $insert->execute([
                    'student_id' => (int) $student['id'],
                    'category_id' => (int) $category['id'],
                    'title' => $title,
                    'description' => $description,
                    'assigned_to' => $assigneeId,
                    'status' => 'submitted',
                ]);

                $message = 'Complaint submitted successfully and routed to the appropriate authority.';
                $messageType = 'success';
                // Clear inputs on success
                unset($_POST['title'], $_POST['description'], $_POST['category_id']);
            } catch (Exception $e) {
                $message = 'System Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

ob_start();
?>
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="dashboard.php" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-4 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
        </a>
        <h2 class="text-3xl font-extrabold text-white tracking-tight">File a New Complaint</h2>
        <p class="text-gray-400 mt-2">Submit detailed information about your issue. Intelligent routing will assign this ticket to the fastest available responder.</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg flex items-start gap-3 border <?= $messageType === 'success' ? 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/30' : 'bg-red-500/10 text-red-500 border-red-500/30' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-8 space-y-8">
        <div class="space-y-2">
            <label for="category_id" class="text-sm font-bold text-white uppercase tracking-wider block">Issue Category <span class="text-red-500">*</span></label>
            <div class="relative">
                <select id="category_id" name="category_id" required class="w-full bg-[#121212] border border-[#333] text-white px-4 py-3.5 rounded-xl appearance-none focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all">
                    <option value="" disabled <?= empty($_POST['category_id']) ? 'selected' : '' ?>>Select the most relevant category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && (int) $_POST['category_id'] === $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i data-lucide="chevron-down" class="w-5 h-5 absolute right-4 top-4 text-gray-500 pointer-events-none"></i>
            </div>
            <p class="text-xs text-gray-500 ml-1">Category determines which staff member gets your complaint.</p>
        </div>

        <div class="space-y-2">
            <label for="title" class="text-sm font-bold text-white uppercase tracking-wider block">Complaint Title <span class="text-red-500">*</span></label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                maxlength="255"
                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                placeholder="Brief summary of the issue (e.g., Broken Window in Room 101)"
                required
                class="w-full bg-[#121212] border border-[#333] text-white px-4 py-3.5 rounded-xl focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all"
            />
        </div>

        <div class="space-y-2">
            <label for="description" class="text-sm font-bold text-white uppercase tracking-wider block">Detailed Description <span class="text-red-500">*</span></label>
            <textarea 
                id="description" 
                name="description" 
                rows="6"
                placeholder="Provide all necessary details, context, and steps taken so far..."
                required
                class="w-full bg-[#121212] border border-[#333] text-white px-4 py-4 rounded-xl focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all resize-y"
            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-[#333]">
            <button 
                type="submit" 
                class="w-full sm:w-auto px-8 py-4 bg-[#13ec87] text-[#121212] font-extrabold rounded-xl shadow-[0_0_15px_rgba(19,236,135,0.2)] hover:bg-[#0fae62] hover:shadow-[0_0_25px_rgba(19,236,135,0.4)] transition-all flex items-center justify-center gap-2"
            >
                <i data-lucide="send" class="w-5 h-5"></i> Submit Complaint
            </button>
        </div>
    </form>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'Submit Complaint';
require_once __DIR__ . '/../components/layout.php';

