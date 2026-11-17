<?php include 'includes/public_header.php'; ?>

<!-- Contact Section -->
<section class="section" style="padding-top: 140px; min-height: 80vh;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 60px;">
            <!-- Contact Info -->
            <div class="reveal">
                <h1 style="font-size: 3rem; font-weight: 900; margin-bottom: 20px;">Get in <span style="color: var(--primary);">Touch</span></h1>
                <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 40px; font-size: 1.1rem;">
                    Have questions about our custom team uniforms and lead management solutions? We're here to help you elevate your athletic brand operations.
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div class="feature-icon" style="flex-shrink: 0; margin-bottom: 0; width: 50px; height: 50px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.27-2.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <h4 style="font-size: 1.1rem; font-weight: 800;">Phone</h4>
                            <p style="color: var(--text-muted);">+1 (234) 567-8901</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div class="feature-icon" style="flex-shrink: 0; margin-bottom: 0; width: 50px; height: 50px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <h4 style="font-size: 1.1rem; font-weight: 800;">Email</h4>
                            <p style="color: var(--text-muted);">hello@studio3.com</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div class="feature-icon" style="flex-shrink: 0; margin-bottom: 0; width: 50px; height: 50px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h4 style="font-size: 1.1rem; font-weight: 800;">Location</h4>
                            <p style="color: var(--text-muted);">742 Innovation Ave, Tech City</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="reveal">
                <div class="feature-card" style="padding: 50px;">
                    <form action="#" style="display: flex; flex-direction: column; gap: 25px;">
                        <div>
                            <label style="display: block; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted);">Full Name</label>
                            <input type="text" placeholder="John Doe" style="width: 100%; padding: 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text-primary); outline: none; transition: border 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted);">Work Email Address</label>
                            <input type="email" placeholder="manager@enterprise.com" style="width: 100%; padding: 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text-primary); outline: none; transition: border 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted);">Message</label>
                            <textarea rows="5" placeholder="How can we help your business?" style="width: 100%; padding: 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text-primary); outline: none; transition: border 0.3s; resize: none;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'"></textarea>
                        </div>
                        <button type="submit" class="btn-login-nav" style="padding: 18px; font-size: 1.05rem; cursor: pointer; border: none;">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
