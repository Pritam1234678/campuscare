<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$admin = requireRole(['admin']);
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle Staff Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_staff') {
    $name = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $hostelId = ($_POST['hostel_id'] ?? '') !== '' ? (int) $_POST['hostel_id'] : null;

    if (!$name || !$email || !$password || !in_array($role, ['mentor', 'warden', 'iro'])) {
        $message = 'Name, email, password, and valid role are required.';
        $messageType = 'error';
    } else {
        // Validation for warden
        if ($role === 'warden' && $hostelId === null) {
            $message = 'Warden requires a Hostel assignment.';
            $messageType = 'error';
        } else {
            // Check email
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                $message = 'Email is already in use.';
                $messageType = 'error';
            } else {
                try {
                    $insert = $pdo->prepare(
                        'INSERT INTO users (name, email, password, role, phone, hostel_id) 
                         VALUES (:name, :email, :password, :role, :phone, :hostel_id)'
                    );
                    $insert->execute([
                        'name' => $name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_ARGON2ID),
                        'role' => $role,
                        'phone' => $phone,
                        'hostel_id' => ($role === 'warden') ? $hostelId : null
                    ]);
                    $message = 'Staff account created successfully.';
                    $messageType = 'success';
                } catch(Exception $e) {
                    $message = 'Database error: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Fetch Staff
$stmt = $pdo->query("SELECT u.id, u.name, u.email, u.role, u.phone, u.status, h.name as hostel_name 
                     FROM users u 
                     LEFT JOIN hostels h ON u.hostel_id = h.id 
                     WHERE u.role IN ('mentor', 'warden', 'iro') 
                     ORDER BY u.role, u.name");
$staffList = $stmt->fetchAll();

// Fetch Hostels for Warden dropdown
$hostels = $pdo->query("SELECT id, name FROM hostels ORDER BY name")->fetchAll();

ob_start();
?>
<div class="mb-8 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-white tracking-tight">Staff Management</h2>
        <p class="text-sm text-gray-400 mt-1">Add and manage Mentors, Wardens, and IROs.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 border <?= $messageType === 'success' ? 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/30' : 'bg-red-500/10 text-red-500 border-red-500/30' ?>">
        <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($message) ?></p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    
    <!-- Add Staff Form -->
    <div class="lg:col-span-1">
        <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl p-6">
            <h3 class="text-lg font-bold text-white mb-4 border-b border-[#333] pb-3">Create New Staff</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_staff">
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Role</label>
                    <select name="role" id="role_select" onchange="toggleHostel()" required class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                        <option value="mentor">Mentor</option>
                        <option value="warden">Hostel Warden</option>
                        <option value="iro">IRO Officer</option>
                    </select>
                </div>
                
                <div id="hostel_group" class="hidden">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Assign Hostel</label>
                    <select name="hostel_id" class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                        <option value="">Select Hostel...</option>
                        <?php foreach($hostels as $h): ?>
                            <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Phone <span class="text-gray-600">(Optional)</span></label>
                    <input type="text" name="phone" class="w-full bg-[#121212] border border-[#333] text-white px-3 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                </div>
                
                <button type="submit" class="w-full mt-4 bg-[#13ec87] text-[#121212] font-bold py-2 rounded-lg hover:bg-[#0fae62] transition-colors text-sm">
                    Create Account
                </button>
            </form>
        </div>
    </div>

    <!-- Staff List Table -->
    <div class="lg:col-span-2">
        <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden h-full">
            <div class="p-6 border-b border-[#333]">
                <h3 class="text-lg font-bold text-white">Active Staff Directory</h3>
            </div>
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#2a2a2a] border-y border-[#333]">
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Name / Contact</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#333]">
                        <?php foreach($staffList as $staff): ?>
                        <tr class="hover:bg-[#2a2a2a]/50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-white"><?= htmlspecialchars($staff['name']) ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($staff['email']) ?></div>
                                <?php if($staff['phone']): ?>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($staff['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-block px-2 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] uppercase font-bold tracking-wider rounded">
                                    <?= htmlspecialchars($staff['role']) ?>
                                </div>
                                <?php if($staff['role'] === 'warden' && $staff['hostel_name']): ?>
                                    <div class="text-xs text-emerald-400 mt-1.5"><i data-lucide="home" class="w-3 h-3 inline"></i> <?= htmlspecialchars($staff['hostel_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs flex items-center gap-1 <?= $staff['status'] === 'active' ? 'text-[#13ec87]' : 'text-red-500' ?>">
                                    <span class="w-2 h-2 rounded-full <?= $staff['status'] === 'active' ? 'bg-[#13ec87]' : 'bg-red-500' ?>"></span>
                                    <?= ucfirst(htmlspecialchars($staff['status'])) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($staffList)): ?>
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 text-sm">No staff members found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleHostel() {
        const role = document.getElementById('role_select').value;
        const group = document.getElementById('hostel_group');
        if (role === 'warden') {
            group.classList.remove('hidden');
        } else {
            group.classList.add('hidden');
        }
    }
    // Initialize state on load
    toggleHostel();
</script>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'Manage Staff';
$pageSubtitle = '';
require_once __DIR__ . '/../components/layout.php';
