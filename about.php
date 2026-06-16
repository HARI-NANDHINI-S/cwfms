<?php include 'includes/public_header.php'; ?>

<!-- Hero Section For About Page -->
<section class="section" style="padding-top: 140px; background: var(--bg-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 60px; align-items: center;">
            <div class="reveal">
                <span style="color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Our Mission</span>
                <h1 style="font-size: 3.5rem; font-weight: 900; line-height: 1.1; margin: 15px 0 25px;">Business <span style="color: var(--primary);">Intelligence</span> by Studio 3.</h1>
                <p style="color: var(--text-muted); line-height: 1.8; font-size: 1.1rem; margin-bottom: 30px;">
                    At Studio 3 Interactive, we believe that managing customer data and operational insights shouldn't be a hurdle. Our platform is built for enterprises that demand precision, security, and scalability.
                </p>
                <div style="display: flex; gap: 20px;">
                    <a href="contact.php" class="btn-login-nav">Work with Us</a>
                </div>
            </div>
            <div class="reveal" style="position: relative;">
                <div style="background: var(--primary-dark); width: 100%; height: 100%; position: absolute; top: 20px; left: 20px; border-radius: 40px; z-index: 1;"></div>
                <img src="assets/images/about.png" alt="About Studio 3" style="width: 100%; border-radius: 40px; position: relative; z-index: 2; box-shadow: var(--shadow-premium);">
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="section" style="background: var(--surface);">
    <div class="container">
        <div class="section-title reveal">
            <h2>Our Core <span style="color: var(--primary);">Values</span></h2>
            <p>Driven by excellence and corporate integrity.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px;">
            <div class="feature-card reveal" style="text-align: center;">
                <h3 style="margin-bottom: 12px;">Innovation</h3>
                <p style="color: var(--text-muted);">We are constantly pushing the boundaries of what's possible in management tech.</p>
            </div>
            <div class="feature-card reveal" style="text-align: center;">
                <h3 style="margin-bottom: 12px;">Integrity</h3>
                <p style="color: var(--text-muted);">Your data security is our top priority. We build trust through transparency.</p>
            </div>
            <div class="feature-card reveal" style="text-align: center;">
                <h3 style="margin-bottom: 12px;">Velocity</h3>
                <p style="color: var(--text-muted);">We move fast to ensure you never miss a beat in your business operations.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
