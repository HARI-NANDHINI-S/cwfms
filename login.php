<?php
require_once 'includes/config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['session']) && $_GET['session'] === 'expired') {
    $error = 'Your session has expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification guard
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed: invalid or expired CSRF token. Please refresh the page.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        // Rate limiting guard
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'client';
        if (!checkLoginRateLimit($clientIp)) {
            $error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
        } elseif (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!isSupabaseConfigured()) {
            $error = 'Database configuration missing. Please configure SUPABASE_URL and SUPABASE_SERVICE_KEY in .env.';
        } else {
            $db = new Supabase(true);
            $result = $db->select('user_roles', [
                'user_email' => 'eq.' . strtolower($email),
                'is_active'  => 'eq.true',
            ]);

            if ($result['status'] === 0) {
                $error = 'Database connection failed: unable to connect to Supabase. Please check your SUPABASE_URL and network.';
            } elseif ($result['status'] === 401 || $result['status'] === 403) {
                $error = 'Database authentication failed: invalid SUPABASE_SERVICE_KEY in .env.';
            } else {
                $userRecord = null;
                if ($result['status'] === 200 && !empty($result['data'])) {
                    $userRecord = $result['data'][0];
                }

                $authenticated = false;
                if ($userRecord) {
                    $hash = $userRecord['password_hash'] ?? '';
                    if (!empty($hash)) {
                        if (password_verify($password, $hash)) {
                            $authenticated = true;
                            // Auto-upgrade password hash if algorithm or cost parameters have changed
                            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $db->update('user_roles', ['password_hash' => $newHash], ['id' => 'eq.' . $userRecord['id']]);
                            }
                        }
                    } else {
                        // Secure Fail-Closed: Account has no cryptographic password hash provisioned in the database.
                        // Hardcoded, shared, or default password authentication is strictly prohibited in production.
                        $error = 'Authentication unavailable: this account has not been provisioned with a cryptographic password. Please execute the authentication migration (migration_v5_auth.sql) or contact an administrator.';
                    }
                }

                if ($authenticated && $userRecord) {
                    clearFailedLogins($clientIp);
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id'            => $userRecord['id'],
                        'name'          => $userRecord['user_name'],
                        'email'         => $userRecord['user_email'],
                        'role'          => $userRecord['role'],
                        'role_label'    => getRoleLabel($userRecord['role']),
                        'business_role' => normalizeRole($userRecord['role']),
                    ];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: dashboard.php');
                    exit;
                } else {
                    recordFailedLogin($clientIp);
                    if (empty($error)) {
                        $error = 'Invalid email or password.';
                    }
                }
            }
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

        .login-form-side {
            flex: 1; background: var(--bg-light); display: flex; align-items: center; justify-content: center;
            position: relative; padding: 40px;
        }
        .login-card {
            background: var(--surface); width: 100%; max-width: 440px; padding: 46px;
            border-radius: 28px; border: 1px solid var(--border); box-shadow: var(--shadow-premium);
            position: relative; z-index: 10;
        }

        .shape-circle {
            position: absolute; width: 300px; height: 300px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, transparent 100%);
            opacity: 0.05; filter: blur(50px); top: -100px; right: -100px;
        }
        .shape-square {
            position: absolute; width: 200px; height: 200px; 
            background: var(--accent); opacity: 0.05; filter: blur(40px);
            bottom: -50px; left: -50px; border-radius: 30px; transform: rotate(45deg);
        }
    </style>
</head>
<body>

<div class="login-split">
    <!-- Visual Half -->
    <div class="login-visual">
        <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?auto=format&fit=crop&w=1600&q=80" alt="Ira Sportswear Athletic Apparel" class="visual-img" id="visualImg">
        <div class="visual-overlay">
            <div class="visual-card" id="visualCard">
                <div style="display:flex; align-items:center; gap:16px; margin-bottom: 24px;">
                    <img src="assets/ira_logo_web.png" alt="Ira Sportswear" style="height: 48px; width: auto; object-fit: contain;">
                </div>
                <h2 style="font-size: 2.2rem; font-weight: 900; line-height: 1.15; margin-bottom: 16px; letter-spacing: -1px;">
                    Ira Sportswear <br>Sales & Workflow
                </h2>
                <p style="color: rgba(255,255,255,0.85); font-size: 1.05rem; line-height: 1.6; margin: 0;">
                    Custom Sportswear Solutions: Complete customer, order sheet, and manufacturing pipeline tracking for elite sports academies and athletic teams.
                </p>
                <div style="margin-top: 28px; display: flex; gap: 20px; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 20px;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #ec1e24;">100%</div>
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.7); text-transform: uppercase;">Custom Gear</div>
                    </div>
                    <div style="border-left: 1px solid rgba(255,255,255,0.15); padding-left: 20px;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: #fff;">10 Stages</div>
                        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.7); text-transform: uppercase;">Sales Pipeline</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Half -->
    <div class="login-form-side">
        <div class="shape-circle" id="circleShape"></div>
        <div class="shape-square" id="squareShape"></div>

        <div class="login-card" id="loginCard">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="assets/ira_logo_web.png" alt="Ira Sportswear Logo" style="height: 38px; width: auto; object-fit: contain;">
                    <div>
                        <div style="font-weight: 900; font-size: 1.1rem; color: var(--text-primary); letter-spacing:-0.5px;">IRA SPORTSWEAR</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); font-weight:700; text-transform: uppercase; letter-spacing: 0.05em;">CWFMS Enterprise Portal</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 28px;">
                <h1 style="font-size: 1.6rem; font-weight: 900; margin: 0 0 6px 0; letter-spacing: -0.5px; color: var(--text-primary);">
                    Sign In
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">
                    Sign in with your authorized Ira Sportswear credentials.
                </p>
            </div>

            <?php if ($error): ?>
                <div style="background: rgba(236,30,36,0.1); color: #ec1e24; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(236,30,36,0.2);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                <div class="input-group">
                    <label class="input-label" for="email">Email Address</label>
                    <input type="email" class="login-input" name="email" id="email" placeholder="name@irasportswear.com" required autofocus value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
                <div class="input-group">
                    <label class="input-label" for="password">Password</label>
                    <input type="password" class="login-input" name="password" id="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit" id="submitBtn">
                    Sign In
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="18"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="demo-access-panel" style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed var(--border);">
                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); font-weight: 700; margin-bottom: 8px;">Demo Role Access</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="demo-pill" onclick="fillDemo('admin@studio3.com', 'password123')" style="font-size: 0.75rem; padding: 5px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">Admin</button>
                    <button type="button" class="demo-pill" onclick="fillDemo('manager@studio3.com', 'password123')" style="font-size: 0.75rem; padding: 5px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">Sales Manager</button>
                    <button type="button" class="demo-pill" onclick="fillDemo('staff@studio3.com', 'password123')" style="font-size: 0.75rem; padding: 5px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">Sales Executive</button>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border); padding-top: 16px;">
                <p style="color: var(--text-muted); font-size: 0.82rem;">&copy; <?= date('Y') ?> Ira Sportswear. All rights reserved.</p>
            </div>
        </div>

        <!-- Floating Theme Toggle -->
        <button onclick="toggleTheme()" style="position: absolute; top: 32px; right: 32px; background: var(--surface); border: 1px solid var(--border); width: 44px; height: 44px; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-primary); transition: all 0.3s; box-shadow: var(--shadow-sm);" onmouseover="this.style.borderColor='#ec1e24'" onmouseout="this.style.borderColor='var(--border)'" aria-label="Toggle Theme">
            <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    const emailEl = document.getElementById('email');
    const passEl = document.getElementById('password');
    if (emailEl) emailEl.value = email;
    if (passEl) passEl.value = pass;
}
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

window.addEventListener('load', () => {
    const tl = gsap.timeline();
    tl.to('.visual-img', { scale: 1, duration: 2.5, ease: 'power2.out' })
      .to('.visual-card', { opacity: 1, y: 0, duration: 1, ease: 'back.out(1.7)' }, "-=1.5");
      
    document.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 50;
        const y = (window.innerHeight / 2 - e.pageY) / 50;
        gsap.to('#visualImg', { x: x, y: y, duration: 1, ease: 'power2.out' });
        gsap.to('#visualCard', { x: -x*1.5, y: -y*1.5, duration: 1, ease: 'power2.out' });
        gsap.to('#circleShape', { x: x*2, y: y*2, duration: 2 });
        gsap.to('#squareShape', { x: -x*1.5, y: -y*1.5, duration: 2 });
    });

    gsap.from('#loginCard', { opacity:0, x: 40, duration: 1.2, ease: 'power3.out', delay: 0.3 });
    gsap.from('.input-group', { opacity:0, y: 20, stagger: 0.15, duration: 0.8, ease: 'power2.out', delay: 0.6 });
});
</script>
</body>
</html>
