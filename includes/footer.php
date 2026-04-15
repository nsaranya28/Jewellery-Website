</main>

<!-- ── FOOTER ── -->
<footer class="site-footer">
  <div class="footer-top container">
    <div class="footer-col">
      <div class="footer-logo">💎 Jewels.com</div>
      <p class="footer-tagline">Pure Gold, Pure Love — Handcrafted jewellery for every milestone of your life.</p>
      <div class="footer-social">
        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= SITE_URL ?>/">Home</a></li>
        <li><a href="<?= SITE_URL ?>/shop.php">Shop</a></li>
        <li><a href="<?= SITE_URL ?>/shop.php?filter=featured">Featured</a></li>
        <li><a href="<?= SITE_URL ?>/shop.php?filter=sale">Sale</a></li>
        <li><a href="<?= SITE_URL ?>/my-orders.php">Track Order</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Categories</h4>
      <ul>
        <?php
        global $pdo;
        try {
            $cats = $pdo->query("SELECT name, slug FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
            foreach ($cats as $cat):
        ?>
        <li><a href="<?= SITE_URL ?>/category.php?slug=<?= $cat['slug'] ?>"><?= safeHtml($cat['name']) ?></a></li>
        <?php endforeach; } catch (Exception $e) {} ?>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Customer Care</h4>
      <ul>
        <li><a href="#">Help & FAQs</a></li>
        <li><a href="#">Return Policy</a></li>
        <li><a href="#">Shipping Info</a></li>
        <li><a href="#">Size Guide</a></li>
        <li><a href="#">Privacy Policy</a></li>
      </ul>
      <div class="footer-contact">
        <p><i class="fas fa-phone"></i> +91 98765 43210</p>
        <p><i class="fas fa-envelope"></i> support@jewels.com</p>
      </div>
    </div>
  </div>

  <div class="footer-bottom container">
    <p>&copy; <?= date('Y') ?> Jewels.com — All rights reserved. Made with ❤️ for jewellery lovers.</p>
    <div class="payment-icons">
      <span>GPay</span><span>PhonePe</span><span>UPI</span><span>COD</span>
    </div>
  </div>
</footer>

<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
