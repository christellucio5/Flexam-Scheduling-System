<?php
session_start();
require_once 'config/Db.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $userData = null;

        try {
            $db = getDB(); 
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $userData = [
                        'id'        => (int)$user['id'],   // ← always cast to int
                        'username'  => $user['username'],
                        'role'      => $user['role'],
                        'full_name' => $user['full_name'],
                        'email'     => $user['email']   ?? '',
                        'campus'    => $user['campus']  ?? '',
                        'college'   => $user['college'] ?? '',
                        'program'   => $user['program'] ?? '',
                    ];
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'Username not found.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }

        if ($userData) {
            $_SESSION['user'] = $userData;

            $target = 'Guest/guest.php';
            if ($userData['role'] === 'Admin') {
                // Super Admin = no campus assigned → sees all campuses
                // Campus Admin = campus assigned → sees only their campus
                $target = empty($userData['campus'])
                    ? 'Admin/admin.php'
                    : 'Admin/campus_admin.php';
            }
            if ($userData['role'] === 'Program Head') $target = 'Head/head.php';

            $jsonUser = json_encode($userData, JSON_HEX_APOS | JSON_HEX_QUOT);

            echo "<!DOCTYPE html><html><body>
                <script>
                    try {
                        sessionStorage.setItem('currentUser', '$jsonUser');
                        window.location.href = '$target';
                    } catch (e) {
                        window.location.href = '$target';
                    }
                </script>
                <p>Redirecting...</p>
            </body></html>";
            exit;
        }

    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLEXAM - Login</title>
    <link rel="icon" type="image/svg+xml" href="Image/OLFU.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        .fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .btn-primary { background: #047857; transition: all 0.2s ease; }
        .btn-primary:hover { background: #065f46; transform: translateY(-1px); }
    </style>
</head>
<body class="min-h-full" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 50%, #ecfdf5 100%);">

<div class="min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">
    <div class="rounded-2xl shadow-xl w-full max-w-sm sm:max-w-md px-6 py-8 sm:p-10 fade-in" style="background: #fafafa; border: 1px solid rgba(0,0,0,0.05);">

        <!-- Logo & Title -->
        <div class="text-center mb-7 sm:mb-8">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md overflow-hidden" style="background: #ffffff; border: 2px solid #047857;">
                <img src="Image/OLFU.png" alt="OLFU Logo" class="w-full h-full object-contain p-1">
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">FLEXAM</h1>
            <p class="text-xs sm:text-sm mt-1 font-medium" style="color: #047857;">Exam Scheduling System</p>
            <p class="text-xs text-slate-400 mt-0.5">Our Lady of Fatima University</p>
        </div>

        <!-- Form -->
        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Username</label>
                <input type="text" name="username"
                    class="w-full px-4 py-3 rounded-xl border text-sm transition outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-[#047857]"
                    style="background: #ffffff; border-color: #e2e8f0;"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="login-password" name="password"
                        class="w-full px-4 py-3 pr-12 rounded-xl border text-sm transition outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-[#047857]"
                        style="background: #ffffff; border-color: #e2e8f0;"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>
                    <button type="button" id="toggle-password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="flex items-center gap-2 text-red-600 text-xs font-semibold px-4 py-3 bg-red-50 rounded-xl border border-red-100">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="w-full btn-primary text-white py-3 sm:py-3.5 rounded-xl text-sm sm:text-base font-bold shadow-lg shadow-emerald-700/20 mt-2">
                Sign In
            </button>
        </form>

        <!-- Footer link -->
        <div class="mt-6 sm:mt-8 pt-5 sm:pt-6 border-t border-slate-100 text-center">
            <a href="Guest/guest.php" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900 hover:underline transition">
                Continue as Student →
            </a>
        </div>
    </div>
</div>

<script>
    document.getElementById('toggle-password').addEventListener('click', () => {
        const passwordInput = document.getElementById('login-password');
        passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    });
</script>
</body>
</html>