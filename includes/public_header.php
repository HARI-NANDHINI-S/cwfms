<?php
require_once 'includes/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | <?= ucfirst(str_replace('.php', '', $current_page)) ?></title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/public.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- GSAP Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script>
        (function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();
    </script>
</head>
<body>

<header class="nav-header" id="navHeader">
    <div class="nav-container">
        <a href="index.php" class="brand" style="display:flex;align-items:center;gap:10px;text-decoration:none">
            <img src="assets/ira_logo_web.png" alt="Ira Sportswear Logo" style="height:36px;width:auto;object-fit:contain;border-radius:6px;background:#fff;padding:2px 6px;box-shadow:0 1px 4px rgba(0,0,0,0.15)">
            <span class="brand-name" style="font-weight:900;letter-spacing:-0.02em;font-size:1.15rem;color:var(--text-primary)">Ira Sportswear</span>
        </a>
        
        <nav class="nav-links">
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="about.php" class="nav-link <?= $current_page == 'about.php' ? 'active' : '' ?>">About</a>
            <a href="contact.php" class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>">Contact</a>
        </nav>

        <div class="nav-actions">
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle" style="background:none;border:none;cursor:pointer;color:var(--text-primary);padding:8px">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="dashboard.php" class="btn-login-nav" style="background:var(--primary);color:#fff">Dashboard</a>
                <a href="pages/leads.php" class="btn-login-nav" style="background:transparent;border:1px solid var(--primary);color:var(--primary);margin-left:6px">Leads</a>
                <a href="logout.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem;margin-left:10px;align-self:center">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-login-nav">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
