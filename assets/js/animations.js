/**
 * Customer Management - Minimal animations (no fade/blur effects)
 */

const AppAnimations = {
    init() {
        // No entrance or table animations — keep UI crisp and fully visible
    },

    animateCounter(el, target) {
        if (!el) return;
        el.textContent = target.toLocaleString();
    },
};

document.addEventListener('DOMContentLoaded', () => AppAnimations.init());
