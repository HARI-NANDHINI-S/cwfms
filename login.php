<?php
require_once 'includes/config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['session']) && $_GET['session'] === 'expired') {
    $error = 'Your session has expired. Please log in again.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $db = new Supabase(true);
        $result = $db->select('user_roles', [
            'user_email' => 'eq.' . strtolower($email),
            'is_active'  => 'eq.true',
        ]);

        // Built-in seed accounts matching database baseline
        $demoUsers = [
            'admin@studio3.com'   => ['id' => '00000000-0000-0000-0000-000000000001', 'user_name' => 'Admin User', 'user_email' => 'admin@studio3.com', 'role' => 'admin'],
            'manager@studio3.com' => ['id' => '00000000-0000-0000-0000-000000000002', 'user_name' => 'Sales Manager', 'user_email' => 'manager@studio3.com', 'role' => 'manager'],
            'staff@studio3.com'   => ['id' => '00000000-0000-0000-0000-000000000003', 'user_name' => 'Sales Executive', 'user_email' => 'staff@studio3.com', 'role' => 'staff'],
        ];

        $userRecord = null;
        if ($result['status'] === 200 && !empty($result['data'])) {
            $userRecord = $result['data'][0];
        } elseif (isset($demoUsers[strtolower($email)])) {
            // Built-in seed fallback when database is initializing or offline
            $userRecord = $demoUsers[strtolower($email)];
        }

        if ($userRecord) {
            $validPassword = ($password === 'password123'); // Demo logic

            if ($validPassword) {
                $_SESSION['user'] = [
                    'id'            => $userRecord['id'],
                    'name'          => $userRecord['user_name'],
                    'email'         => $userRecord['user_email'],
                    'role'          => $userRecord['role'],
                    'role_label'    => getRoleLabel($userRecord['role']),
                    'business_role' => normalizeRole($userRecord['role']),
                ];
                $_SESSION['login_time'] = time();
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Ira Sportswear Lead Management</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/public.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        (function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();
    </script>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; height: 100vh; display: flex; }
        .login-split { display: flex; width: 100%; height: 100vh; }
        
        /* Left Side: Animated Image */
        .login-visual {
            flex: 1.2; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
            background: #0f1426;
        }
        .visual-img {
            width: 100%; height: 100%; object-fit: cover; opacity: 0.55;
            transform: scale(1.1); transition: filter 0.5s ease;
        }
        .visual-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(22,30,56,0.85) 0%, rgba(236,30,36,0.3) 50%, rgba(15,20,38,0.92) 100%);
            display: flex; flex-direction: column; justify-content: center; padding: 10%; color: #fff;
        }
        .visual-card {
            background: rgba(15, 20, 38, 0.45); backdrop-filter: blur(15px);
            padding: 40px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.18);
            max-width: 520px; transform: translateY(40px); opacity: 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        /* Right Side: Form */
        .login-form-side {
            flex: 1; background: var(--bg-light); display: flex; align-items: center; justify-content: center;
            position: relative; padding: 40px;
        }
        .login-card {
            background: var(--surface); width: 100%; max-width: 440px; padding: 46px 42px;
            border-radius: 28px; border: 1px solid var(--border); box-shadow: var(--shadow-premium);
            position: relative; z-index: 10;
        }

        /* Professional Shapes */
        .shape-circle {
            position: absolute; width: 300px; height: 300px; border-radius: 50%;
            background: linear-gradient(135deg, #161e38 0%, transparent 100%);
            opacity: 0.08; filter: blur(50px); top: -100px; right: -100px;
        }
        .shape-square {
            position: absolute; width: 200px; height: 200px; 
            background: #ec1e24; opacity: 0.06; filter: blur(40px);
            bottom: -50px; left: -50px; border-radius: 30px; transform: rotate(45deg);
        }

        .input-group { margin-bottom: 22px; }
        .input-label { display: block; font-weight: 800; font-size: 0.78rem; text-transform: uppercase; margin-bottom: 8px; color: var(--text-muted); letter-spacing: 0.05em; }
        .login-input {
            width: 100%; padding: 15px 16px; border-radius: 12px; border: 1.5px solid var(--border);
            background: var(--bg-light); color: var(--text-primary); font-family: inherit;
            transition: all 0.25s ease; outline: none; font-weight: 500; font-size: 0.92rem;
            box-sizing: border-box;
        }
        .login-input:focus { border-color: #ec1e24; box-shadow: 0 0 0 4px rgba(236,30,36,0.12); transform: translateY(-1px); }

        .btn-submit {
            width: 100%; padding: 15px; border-radius: 12px; background: #ec1e24; color: #fff;
            border: none; font-weight: 800; font-size: 0.98rem; cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 14px;
            letter-spacing: 0.02em;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(236,30,36,0.32); background: #d0161b; }

        .demo-pill {
            background: var(--bg-light); border: 1px solid var(--border); padding: 7px 14px; border-radius: 50px;
            font-size: 0.78rem; color: var(--text-muted); cursor: pointer; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px; margin-right: 6px; margin-bottom: 8px; font-weight: 600;
        }
        .demo-pill:hover { border-color: #ec1e24; color: #ec1e24; background: rgba(236,30,36,0.04); }

        @media (max-width: 1024px) {
            .login-visual { display: none; }
        }
    </style>
</head>
<body>

<div class="login-split">
    <!-- Visual Side -->
    <div class="login-visual">
        <img src="assets/images/purchase.png" alt="Ira Sportswear Custom Apparel" class="visual-img" id="visualImg">
        <div class="visual-overlay">
            <div class="visual-card" id="visualCard">
                <span style="color: #00e676; font-weight: 900; letter-spacing: 2px; font-size:0.78rem">OFFICIAL LEAD MANAGEMENT MODULE</span>
                <h2 style="font-size: 2.8rem; font-weight: 900; line-height: 1.1; margin: 15px 0;">Ira Sportswear <br>Sales & Workflow.</h2>
                <p style="opacity:0.88; line-height: 1.6; font-size: 1.05rem;">Manage team uniforms, academy orders, and custom apparel enquiries with high-speed sales tracking and workflow automation.</p>
                <div style="margin-top: 28px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #00e676; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#0f1426" stroke-width="3" width="20"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span style="font-weight: 700; font-size: 0.95rem;">Performance Custom Sportswear Solutions</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Side -->
    <div class="login-form-side">
        <div class="shape-circle" id="circleShape"></div>
        <div class="shape-square" id="squareShape"></div>

        <div class="login-card" id="loginCard">
            <a href="index.php" style="text-decoration:none; display:flex; align-items:center; gap:12px; margin-bottom:28px">
                <img src="assets/ira_logo_web.png" alt="Ira Sportswear" style="height:44px; width:auto; object-fit:contain; border-radius:8px; background:#fff; padding:4px 8px; box-shadow:0 2px 8px rgba(0,0,0,0.12); border:1px solid var(--border)">
                <div>
                    <h2 style="font-size:1.25rem; font-weight:900; color:var(--text-primary); margin:0; line-height:1.2">Ira Sportswear</h2>
                    <span style="font-size:0.72rem; font-weight:700; color:#ec1e24; text-transform:uppercase; letter-spacing:0.06em">Lead Management</span>
                </div>
            </a>

            <h1 style="font-size: 1.6rem; font-weight: 900; margin-bottom: 6px;">Welcome Back</h1>
            <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem;">Sign in to access leads, pipeline, and team orders.</p>

            <div style="margin-bottom: 24px;">
                <p style="font-weight: 800; font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.08em;">Quick Demo Access</p>
                <div class="demo-pill" onclick="fillForm('admin@studio3.com')">👑 Admin</div>
                <div class="demo-pill" onclick="fillForm('manager@studio3.com')">💼 Sales Manager</div>
                <div class="demo-pill" onclick="fillForm('staff@studio3.com')">🏃 Sales Executive</div>
            </div>

            <?php if ($error): ?>
                <div style="background: rgba(236,30,36,0.1); color: #ec1e24; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(236,30,36,0.2);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label class="input-label">Email Address</label>
                    <input type="email" class="login-input" name="email" id="email" placeholder="name@irasportswear.com" required autofocus>
                </div>
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <input type="password" class="login-input" name="password" id="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">
                    Sign In
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="18"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div style="margin-top: 26px; text-align: center; border-top: 1px solid var(--border); padding-top: 18px;">
                <p style="color: var(--text-muted); font-size: 0.82rem;">&copy; <?= date('Y') ?> Ira Sportswear. All rights reserved.</p>
            </div>
        </div>

        <!-- Floating Theme Toggle -->
        <button onclick="toggleTheme()" style="position: absolute; top: 32px; right: 32px; background: var(--surface); border: 1px solid var(--border); width: 44px; height: 44px; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-primary); transition: all 0.3s; box-shadow: var(--shadow-sm);" onmouseover="this.style.borderColor='#ec1e24'" onmouseout="this.style.borderColor='var(--border)'">
            <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </div>
</div>

<script>
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('cm-theme', next);
    updateIcons();
}
function updateIcons() {
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    document.querySelectorAll('.sun-icon').forEach(el => el.style.display = theme === 'light' ? 'block' : 'none');
    document.querySelectorAll('.moon-icon').forEach(el => el.style.display = theme === 'dark' ? 'block' : 'none');
}
updateIcons();

function fillForm(email) {
    const emailField = document.getElementById('email');
    const passField = document.getElementById('password');
    emailField.value = email;
    passField.value = 'password123';
    
    // Animate input focus
    gsap.to([emailField, passField], { scale: 1.05, duration: 0.2, yoyo: true, repeat: 1 });
}

// GSAP Animations
window.addEventListener('load', () => {
    const tl = gsap.timeline();
    
    // Image Side Entrance
    tl.to('.visual-img', { scale: 1, duration: 2.5, ease: 'power2.out' })
      .to('.visual-card', { opacity: 1, y: 0, duration: 1, ease: 'back.out(1.7)' }, "-=1.5");
      
    // Parallax Hover Effect
    document.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 50;
        const y = (window.innerHeight / 2 - e.pageY) / 50;
        gsap.to('#visualImg', { x: x, y: y, duration: 1, ease: 'power2.out' });
        gsap.to('#visualCard', { x: -x*1.5, y: -y*1.5, duration: 1, ease: 'power2.out' });
        
        // Shape animations
        gsap.to('#circleShape', { x: x*2, y: y*2, duration: 2 });
        gsap.to('#squareShape', { x: -x*1.5, y: -y*1.5, duration: 2 });
    });

    // Form Entrance
    gsap.from('#loginCard', { opacity:0, x: 40, duration: 1.2, ease: 'power3.out', delay: 0.5 });
    gsap.from('.input-group', { opacity:0, y: 20, stagger: 0.15, duration: 0.8, ease: 'power2.out', delay: 1 });
    gsap.from('.demo-pill', { opacity:0, scale:0.8, stagger: 0.05, duration: 0.5, ease: 'back.out(2)', delay: 1.5 });
});
</script>
</body>
</html>
