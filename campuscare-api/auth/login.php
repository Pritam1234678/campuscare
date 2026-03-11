<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (isset($_SESSION['user']['id'])) {
    header('Location: /campuscare/campuscare-api/' . $_SESSION['user']['role'] . '/dashboard.php');
    exit;
}

$error = $_GET['error'] ?? null;
if ($error === 'AccountNotFound') $error = 'Session expired or account not found.';
if ($error === 'AccountDisabled') $error = 'Your account has been disabled.';
if ($error === 'AccessDenied') $error = 'You do not have permission to view that page.';
if ($error === 'LoggedOut') $error = 'Successfully logged out.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email address is required.';
    } else {
        $pdo = getDbConnection();
        $statement = $pdo->prepare(
            'SELECT id, name, email, password, role, roll_number, gender, phone, hostel_id, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif (($user['status'] ?? 'active') !== 'active') {
            $error = 'This account is disabled.';
        } else {
            // Unset password before saving to session
            unset($user['password']);
            $_SESSION['user'] = $user;
            
            // Redirect based on role
            header('Location: /campuscare/campuscare-api/' . $user['role'] . '/dashboard.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<div class="min-h-screen flex flex-col md:flex-row">
    <!-- Left Pattern/Branding -->
    <div class="hidden md:flex md:w-1/2 bg-[#1e1e1e] border-r border-[#333] flex-col justify-between p-12 relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
            <span class="text-xl font-bold tracking-tight text-white">CampusCare</span>
        </div>
        
        <div class="relative z-10 max-w-lg mt-auto">
            <div class="inline-block px-3 py-1 mb-4 rounded-md border border-[#13ec87]/30 bg-[#13ec87]/10 text-[#13ec87] text-xs font-semibold tracking-wide uppercase">
                Secure Access
            </div>
            <h1 class="text-4xl font-extrabold text-white mb-4 leading-tight">Welcome back to your workspace.</h1>
            <p class="text-gray-400 text-lg">Log in to track, manage, and resolve institutional complaints efficiently.</p>
        </div>
        
        <!-- Decoration -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#13ec87]/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-cyan-400/5 rounded-full blur-3xl"></div>
    </div>

    <!-- Right Login Form -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8 relative">
        <div class="w-full max-w-md">
            
            <div class="md:hidden flex items-center gap-2 mb-12">
                <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
                <span class="text-xl font-bold tracking-tight text-white">CampusCare</span>
            </div>

            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl font-bold text-white mb-2">Sign in</h2>
                <p class="text-gray-400">Enter your official credentials to continue.</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-lg text-red-500 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-6">
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-gray-300">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-lg focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all"
                        placeholder="you@campus.edu"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-gray-300">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-lg focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all"
                        placeholder="••••••••"
                        required
                    />
                </div>

                <button 
                    type="submit" 
                    id="submit-btn"
                    class="w-full bg-[#13ec87] text-[#121212] font-bold py-3 px-4 rounded-lg shadow-[0_0_15px_rgba(19,236,135,0.2)] hover:bg-[#0fae62] hover:shadow-[0_0_25px_rgba(19,236,135,0.4)] transition-all flex items-center justify-center gap-2"
                >
                    Sign In <i data-lucide="log-in" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    const form = document.querySelector('form');
    const btn = document.getElementById('submit-btn');
    form.addEventListener('submit', () => {
        btn.innerHTML = 'Signing in...';
        btn.disabled = true;
        btn.classList.add('opacity-70');
        form.submit();
    });
</script>
</body>
</html>
