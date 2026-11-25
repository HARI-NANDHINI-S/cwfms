<?php include 'includes/public_header.php'; ?>

<!-- Hero Section -->
<section class="hero" style="background-image: url('assets/images/hero.png');">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Elevate Your <span style="color:var(--primary)">Customer Relations</span></h1>
        <p>Advanced customer management solutions for modern enterprises. Track, analyze, and grow your client base with professional precision.</p>
        <div style="display:flex;gap:20px;justify-content:center">
            <a href="<?= isset($_SESSION['user']) ? 'dashboard.php' : 'login.php' ?>" class="btn-login-nav" style="padding:16px 36px;font-size:1.1rem">Get Started</a>
            <a href="about.php" style="padding:16px 36px;font-size:1.1rem;color:#fff;text-decoration:none;font-weight:700;border:1px solid rgba(255,255,255,0.3);border-radius:50px;transition:all 0.3s">Learn More</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section">
    <div class="container">
        <div class="section-title reveal">
            <h2>Why Choose <span style="color:var(--primary)">Ira Sportswear</span></h2>
            <p>We provide the ultimate management platform for athletic apparel and custom team sportswear.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Client Tracking</h3>
                <p style="color:var(--text-muted);line-height:1.6">Easily manage profiles for your entire client base or individual partners in one central hub.</p>
            </div>
            
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3>Live Analytics</h3>
                <p style="color:var(--text-muted);line-height:1.6">Visualize registration trends and distribution across countries with our dynamic charts.</p>
            </div>

            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Secure Access</h3>
                <p style="color:var(--text-muted);line-height:1.6">Role-based permissions ensure that your sensitive customer data is always protected.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Tracker (animated with GSAP) -->
<section class="section" style="background: var(--bg-dark); color: #fff;">
    <div class="container" style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 40px; text-align: center;">
        <div class="stat-item reveal">
            <h2 style="font-size: 3rem; font-weight: 900; color: var(--primary);" class="counter">500+</h2>
            <p style="opacity: 0.7;">Enterprise Clients</p>
        </div>
        <div class="stat-item reveal">
            <h2 style="font-size: 3rem; font-weight: 900; color: var(--primary);" class="counter">1.2M</h2>
            <p style="opacity: 0.7;">Data Points Analyzed</p>
        </div>
        <div class="stat-item reveal">
            <h2 style="font-size: 3rem; font-weight: 900; color: var(--primary);" class="counter">99.9%</h2>
            <p style="opacity: 0.7;">Uptime Reliability</p>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
