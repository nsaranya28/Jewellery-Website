// main.js — Jewels.com Premium Interactive Logic
const SITE_URL = '/jewellery'; // Fallback base URL for AJAX

// ── REVEAL ON SCROLL ──
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
        }
    });
}, { threshold: 0.1 });

// ── HEADER EFFECTS ──
const handleHeaderScroll = () => {
    const header = document.getElementById('siteHeader');
    if (!header) return;
    if (window.scrollY > 40) {
        header.classList.add('glass');
        header.style.padding = '8px 0';
        header.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
    } else {
        header.classList.remove('glass');
        header.style.padding = '14px 0';
        header.style.boxShadow = '';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Reveal Elements
    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
    
    // Scroll Listener
    window.addEventListener('scroll', handleHeaderScroll);
    handleHeaderScroll();

    // Mobile Nav
    const hb = document.getElementById('hamburger');
    const nav = document.getElementById('siteNav');
    if (hb && nav) {
        hb.addEventListener('click', () => {
            nav.classList.toggle('active');
            hb.innerHTML = nav.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
    }

    // Product Gallery
    const mainImg = document.getElementById('mainProductImg');
    const thumbs = document.querySelectorAll('.thumb-img');
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            thumbs.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            if (mainImg) {
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.src = thumb.querySelector('img').src;
                    mainImg.style.opacity = '1';
                }, 200);
            }
        });
    });

    // Qty Controls
    document.querySelectorAll('.qty-control').forEach(ctrl => {
        const display = ctrl.querySelector('.qty-val');
        const input = ctrl.querySelector('input[name="quantity"]');
        ctrl.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                let v = parseInt(display.textContent) || 1;
                if (btn.dataset.action === 'plus') v++;
                else if (v > 1) v--;
                display.textContent = v;
                if (input) input.value = v;
            });
        });
    });

    // AJAX Add to Cart
    document.querySelectorAll('.quick-cart').forEach(btn => {
        btn.addEventListener('click', async () => {
            const pid = btn.dataset.id;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            try {
                const res = await fetch('ajax/cart.php', {
                    method: 'POST',
                    body: JSON.stringify({ product_id: pid, quantity: 1 }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Added to cart! 🛍️', 'success');
                    document.querySelectorAll('.hicon[title="Cart"] .badge, .hicon[href*="cart"] .badge').forEach(b => {
                        b.textContent = data.count;
                        b.style.display = 'flex';
                    });
                } else {
                    showToast(data.error || 'Failed to add', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    });

    // AJAX Wishlist
    document.querySelectorAll('.wish-toggle').forEach(btn => {
        btn.addEventListener('click', async () => {
            const pid = btn.dataset.id;
            try {
                const res = await fetch('ajax/wishlist.php', {
                    method: 'POST',
                    body: JSON.stringify({ product_id: pid }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (data.login) {
                    window.location.href = 'login.php';
                    return;
                }
                const isAdded = data.action === 'added';
                showToast(isAdded ? 'Added to wishlist! 💖' : 'Removed from wishlist', isAdded ? 'success' : 'info');
                btn.classList.toggle('wishlisted', isAdded);
                btn.innerHTML = isAdded ? '<i class="fas fa-heart"></i>' : '<i class="far fa-heart"></i>';
                
                document.querySelectorAll('.hicon[title="Wishlist"] .badge, .hicon[href*="wishlist"] .badge').forEach(b => {
                    b.textContent = data.count || '';
                    b.style.display = data.count > 0 ? 'flex' : 'none';
                });
            } catch (err) {}
        });
    });

    // Price Display
    const pr = document.getElementById('priceRange');
    const pd = document.getElementById('priceDisplay');
    if (pr && pd) {
        pr.addEventListener('input', () => {
            pd.textContent = `₹0 — ₹${parseInt(pr.value).toLocaleString('en-IN')}`;
        });
    }
});
