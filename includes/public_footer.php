<footer class="section" style="background: var(--surface); border-top: 1px solid var(--border); padding-top: 60px;">
    <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 40px;">
        <div>
            <div class="brand" style="margin-bottom: 20px;">
                <div class="brand-logo">S3</div>
                <span class="brand-name">Studio 3</span>
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                Leading enterprise customer management solutions. Empowering businesses with data-driven precision.
            </p>
        </div>
        <div>
            <h4 style="margin-bottom: 20px;">Quick Links</h4>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="index.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">Home</a>
                <a href="about.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">About Us</a>
                <a href="contact.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">Contact</a>
            </div>
        </div>
        <div>
            <h4 style="margin-bottom: 20px;">Support</h4>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="#" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">Privacy Policy</a>
                <a href="#" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">Terms of Service</a>
                <a href="login.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.9rem;">Admin Login</a>
            </div>
        </div>
    </div>
    <div class="container" style="padding-top: 30px; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 0.85rem;">
        &copy; <?= date('Y') ?> Studio 3 Interactive. All rights reserved.
    </div>
</footer>

<script>
// Theme Toggle Logic
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

document.addEventListener('DOMContentLoaded', () => {
    updateIcons();
    
    // GSAP Initializations
    gsap.registerPlugin(ScrollTrigger);
    
    // Animate reveal elements
    gsap.utils.toArray('.reveal').forEach((elem) => {
        gsap.fromTo(elem, 
            { opacity: 0, y: 50 }, 
            { 
                opacity: 1, y: 0, duration: 1, ease: 'power3.out',
                scrollTrigger: {
                    trigger: elem,
                    start: 'top 85%',
                }
            }
        );
    });

    // Special hero animation
    gsap.from('.hero-content > *', {
        opacity: 0, y: 30, stagger: 0.2, duration: 1.2, ease: 'back.out(1.7)', delay: 0.3
    });
});
</script>
</body>
</html>
