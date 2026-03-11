<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$admin = requireRole(['admin']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Settings updated successfully.';
}

ob_start();
?>
<div class="max-w-4xl">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white tracking-tight">System Configuration</h2>
        <p class="text-sm text-gray-400 mt-1">Manage global platform behaviors and integration parameters.</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg flex items-start gap-3 border bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/30">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium leading-relaxed"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-[#333]">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i data-lucide="bell" class="w-5 h-5 text-gray-400"></i> Notifications
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-white">Email Routing</h4>
                        <p class="text-xs text-gray-500 mt-1">Send assignment alerts to staff emails automatically.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="email_alerts" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-[#333] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#13ec87]"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-white">Escalation Thresholds</h4>
                        <p class="text-xs text-gray-500 mt-1">Automatically escalate complaints unresolved after 7 days.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_escalate" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-[#333] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#13ec87]"></div>
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-[#1e1e1e] border border-[#333] rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-[#333]">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i data-lucide="shield" class="w-5 h-5 text-gray-400"></i> Security
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Session Timeout (Minutes)</label>
                    <input type="number" name="timeout" value="60" class="w-full max-w-xs bg-[#121212] border border-[#333] text-white px-4 py-2 rounded-lg focus:outline-none focus:border-[#13ec87] text-sm">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-[#13ec87] text-[#121212] font-bold rounded-lg shadow-[0_0_15px_rgba(19,236,135,0.2)] hover:bg-[#0fae62] transition-all flex items-center gap-2 text-sm">
                <i data-lucide="save" class="w-4 h-4"></i> Save Configuration
            </button>
        </div>
    </form>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'Settings';
require_once __DIR__ . '/../components/layout.php';
