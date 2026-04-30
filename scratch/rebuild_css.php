<?php
$file = 'c:/xampp/htdocs/jewellery/css/style.css';
$content = file_get_contents($file);
$lines = explode("\n", $content);

// Keep first 1165 lines (0-indexed: 0 to 1164)
$newLines = array_slice($lines, 0, 1165);

// Add the clean footer and utilities
$footer = "/* ── FOOTER ──────────────────────────────────────────────────── */
.site-footer { background: var(--dark); color: #ccc; }
.footer-top {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 40px;
  padding: 60px 20px;
}
.footer-logo { font-family: var(--font-serif); font-size: 26px; color: var(--gold-light); font-weight: 700; margin-bottom: 12px; }
.footer-tagline { font-size: 13px; color: #aaa; margin-bottom: 20px; line-height: 1.7; }
.footer-social { display: flex; gap: 10px; }
.footer-social a { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 14px; transition: all var(--transition); }
.footer-social a:hover { background: var(--gold); border-color: var(--gold); color: var(--white); }
.footer-col h4 { color: var(--gold-light); font-size: 14px; font-weight: 600; margin-bottom: 18px; text-transform: uppercase; letter-spacing: 1px; }
.footer-col ul li { margin-bottom: 10px; }
.footer-col ul li a { color: #aaa; font-size: 13px; transition: color var(--transition); }
.footer-col ul li a:hover { color: var(--gold-light); }
.footer-contact { margin-top: 14px; }
.footer-contact p { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #aaa; margin-bottom: 8px; }
.footer-contact i { color: var(--gold); }
.footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 12px; color: #666; }
.payment-icons { display: flex; gap: 8px; }
.payment-icons span { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 600; color: #ccc; }

/* ── UTILITIES ───────────────────────────────────────────────── */
.text-center { text-align: center; }
.text-gold { color: var(--gold); }
.mt-20 { margin-top: 20px; }
.mt-30 { margin-top: 30px; }
.mb-30 { margin-bottom: 30px; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--gray); }
.empty-state .es-icon { font-size: 60px; opacity: 0.3; margin-bottom: 16px; }
.empty-state h3 { font-family: var(--font-serif); font-size: 24px; color: var(--dark); margin-bottom: 8px; }
.empty-state p { font-size: 14px; }

/* -- OFFER CARDS (Dynamic Homepage Section) ------------------ */
.offer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 30px;
  margin-top: 20px;
}
.offer-card {
  background: var(--white);
  border: 1px solid var(--gold-pale);
  border-radius: var(--radius-lg);
  padding: 40px 30px;
  position: relative;
  overflow: hidden;
  transition: all var(--transition);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background-image: radial-gradient(circle at 100% 0%, rgba(201,162,39,0.05) 0%, transparent 50%);
}
.offer-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-md);
  border-color: var(--gold);
}
.offer-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-light));
  opacity: 0;
  transition: opacity var(--transition);
}
.offer-card:hover::before { opacity: 1; }

.offer-cutout {
  position: absolute;
  top: 50%;
  width: 24px;
  height: 24px;
  background: var(--ivory-dark);
  border-radius: 50%;
  transform: translateY(-50%);
  z-index: 5;
}
.offer-cutout.left { left: -12px; border-right: 1px solid var(--gold-pale); }
.offer-cutout.right { right: -12px; border-left: 1px solid var(--gold-pale); }

.offer-badge {
  background: linear-gradient(135deg, var(--gold-dark), var(--gold));
  color: var(--white);
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  padding: 8px 24px;
  border-radius: 50px;
  margin-bottom: 20px;
  display: inline-block;
  box-shadow: var(--shadow-gold);
}
.offer-title {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 700;
  color: var(--dark);
  margin-bottom: 12px;
}
.offer-desc {
  font-size: 14px;
  color: var(--gray);
  margin-bottom: 24px;
  line-height: 1.6;
}
.offer-code-wrap {
  display: flex;
  background: var(--ivory-dark);
  border: 1.5px dashed var(--gold);
  border-radius: 50px;
  padding: 6px 6px 6px 24px;
  align-items: center;
  gap: 15px;
  width: 100%;
  max-width: 280px;
  margin-top: auto;
}
.offer-code {
  font-family: monospace;
  font-size: 18px;
  font-weight: 700;
  color: var(--gold-dark);
  letter-spacing: 2px;
}
.btn-copy {
  background: var(--dark);
  color: var(--white);
  padding: 10px 20px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all var(--transition);
  margin-left: auto;
}
.btn-copy:hover { background: var(--gold-dark); }
.btn-copy.copied { background: var(--green); }

/* ── RESPONSIVE ──────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .shop-layout { grid-template-columns: 1fr; }
  .filter-sidebar { position: static; }
  .product-detail { grid-template-columns: 1fr; }
  .cart-layout { grid-template-columns: 1fr; }
  .profile-layout { grid-template-columns: 1fr; }
  .footer-top { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .topbar-inner { justify-content: center; }
  .topbar-inner span:not(:first-child) { display: none; }
  .header-search { display: none; }
  .hamburger { display: block; }
  .site-nav { display: none; }
  .site-nav.open { display: block; }
  .nav-inner { flex-direction: column; align-items: stretch; }
  .nav-link { border-bottom: 1px solid rgba(255,255,255,0.1); }
  .dropdown-menu { position: static; box-shadow: none; border: none; background: rgba(0,0,0,0.2); border-radius: 0; }
  .hero h1 { font-size: 32px; }
  .hero-img { display: none; }
  .hero-gems { display: none; }
  .hero-stats { gap: 20px; }
  .footer-top { grid-template-columns: 1fr; gap: 24px; padding: 40px 20px; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
  .category-grid { grid-template-columns: repeat(3, 1fr); }
  .form-card { padding: 28px 20px; margin: 30px auto; }
  .form-row { grid-template-columns: 1fr; }
  .offer-grid { grid-template-columns: 1fr; }
  .offer-card { padding: 30px 20px; }
}
@media (max-width: 480px) {
  .category-grid { grid-template-columns: repeat(2, 1fr); }
  .product-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
}";

$finalContent = implode("\n", $newLines) . "\n" . $footer;
file_put_contents($file, $finalContent);
echo "✅ style.css rebuilt successfully.";
