-- ============================================================
--  Online Jewellery Shopping Website — Database Schema + Seed
-- ============================================================
CREATE DATABASE IF NOT EXISTS jewellery_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jewellery_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(15),
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT,
  image VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  discount_price DECIMAL(10,2) DEFAULT NULL,
  material VARCHAR(100),
  weight DECIMAL(8,2),
  purity VARCHAR(50),
  stock INT DEFAULT 10,
  image1 VARCHAR(255),
  image2 VARCHAR(255),
  image3 VARCHAR(255),
  is_featured TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Cart
CREATE TABLE IF NOT EXISTS cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(100) DEFAULT NULL,
  product_id INT NOT NULL,
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist
CREATE TABLE IF NOT EXISTS wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_wish (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Addresses
CREATE TABLE IF NOT EXISTS addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  label VARCHAR(50) DEFAULT 'Home',
  full_name VARCHAR(100),
  phone VARCHAR(15),
  line1 VARCHAR(200),
  line2 VARCHAR(200),
  city VARCHAR(100),
  state VARCHAR(100),
  pincode VARCHAR(10),
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  address_id INT DEFAULT NULL,
  address_snapshot TEXT,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  coupon_code VARCHAR(50) DEFAULT NULL,
  payment_method VARCHAR(50) DEFAULT 'COD',
  payment_status VARCHAR(30) DEFAULT 'pending',
  status ENUM('confirmed','packed','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'confirmed',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(200),
  product_image VARCHAR(255),
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Coupons
CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  type ENUM('percent','flat') DEFAULT 'percent',
  discount DECIMAL(10,2) NOT NULL,
  min_order DECIMAL(10,2) DEFAULT 0,
  max_uses INT DEFAULT 100,
  used_count INT DEFAULT 0,
  expiry DATE,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_review (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Recently Viewed
CREATE TABLE IF NOT EXISTS recently_viewed (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(100) DEFAULT NULL,
  product_id INT NOT NULL,
  viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================================
--  SEED DATA
-- ============================================================

-- Admin user (password: admin123)
INSERT INTO admins (name, email, password) VALUES
('Admin', 'admin@jewels.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Demo user (password: user123)
INSERT INTO users (name, email, phone, password) VALUES
('Priya Lakshmi', 'priya@gmail.com', '9876543210', '$2y$10$TKh8H1.lesMN8Y.HaMBiSeyEFAcq4m0OhX6q6STX3LQ0d.5P.O2uy');

-- Categories
INSERT INTO categories (name, slug, description, image) VALUES
('Kolusu', 'kolusu', 'Traditional South Indian anklets crafted in 22K gold', 'kolusu.jpg'),
('Kammal', 'kammal', 'Elegant earrings for every occasion', 'kammal.jpg'),
('Chain', 'chain', 'Delicate and bold gold chains for necklaces', 'chain.jpg'),
('Bangle', 'bangle', 'Classic bangles in gold, silver and diamond', 'bangle.jpg'),
('Ring', 'ring', 'Wedding rings, engagement rings and fashion rings', 'ring.jpg'),
('Necklace', 'necklace', 'Statement necklaces for brides and festive wear', 'necklace.jpg'),
('Earring', 'earring', 'Studs, hoops and jhumkas for everyday elegance', 'earring.jpg');

-- Products (20 seed products)
INSERT INTO products (category_id, name, slug, description, price, discount_price, material, weight, purity, stock, image1, is_featured) VALUES
-- Kolusu (cat 1)
(1, 'Traditional Lakshmi Kolusu', 'traditional-lakshmi-kolusu', 'Handcrafted 22K gold Lakshmi kolusu with intricate motifs, perfect for ceremonies and weddings.', 28500.00, 24999.00, 'Gold', 15.20, '22K', 8, 'kolusu1.jpg', 1),
(1, 'Silver Stone Kolusu', 'silver-stone-kolusu', 'Oxidised silver kolusu adorned with colourful stones, ideal for festive and casual wear.', 3200.00, 2799.00, 'Silver', 18.50, '92.5%', 12, 'kolusu2.jpg', 0),
(1, 'Diamond Cut Kolusu', 'diamond-cut-kolusu', 'Stunning diamond-cut pattern gold kolusu that reflects light beautifully.', 35000.00, 31500.00, 'Gold', 20.00, '22K', 5, 'kolusu3.jpg', 1),

-- Kammal (cat 2)
(2, 'Peacock Jhumka Kammal', 'peacock-jhumka-kammal', 'Gorgeous peacock-inspired jhumka earrings in 22K gold with pearl drops.', 18500.00, 16200.00, 'Gold', 8.50, '22K', 10, 'kammal1.jpg', 1),
(2, 'Ruby Stone Kammal', 'ruby-stone-kammal', 'Vibrant ruby-studded gold kammal, perfect for bridal and festive occasions.', 22000.00, 19500.00, 'Gold', 9.20, '22K', 7, 'kammal2.jpg', 0),
(2, 'Simple Stud Kammal', 'simple-stud-kammal', 'Minimalist 18K gold stud earrings suitable for everyday wear.', 5500.00, 4999.00, 'Gold', 2.10, '18K', 20, 'kammal3.jpg', 0),

-- Chain (cat 3)
(3, 'Singapore Chain Gold', 'singapore-chain-gold', 'Classic Singapore pattern 22K gold chain, lightweight and elegant.', 32000.00, 28500.00, 'Gold', 8.00, '22K', 15, 'chain1.jpg', 1),
(3, 'Box Chain Silver', 'box-chain-silver', 'Modern box-link sterling silver chain, versatile for any pendant.', 2800.00, 2499.00, 'Silver', 12.00, '92.5%', 18, 'chain2.jpg', 0),
(3, 'Rope Chain 22K', 'rope-chain-22k', 'Luxurious rope-pattern 22K gold chain, symbol of elegance.', 48000.00, 43200.00, 'Gold', 12.00, '22K', 6, 'chain3.jpg', 1),

-- Bangle (cat 4)
(4, 'Broad Kada Bangle Set', 'broad-kada-bangle-set', 'Set of 4 broad 22K gold kada bangles with traditional carvings.', 65000.00, 58500.00, 'Gold', 42.00, '22K', 4, 'bangle1.jpg', 1),
(4, 'Diamond Bangle Single', 'diamond-bangle-single', 'Solitaire diamond-studded 18K gold bangle for modern brides.', 85000.00, 78000.00, 'Gold + Diamond', 18.00, '18K', 3, 'bangle2.jpg', 1),
(4, 'Silver Filigree Bangle', 'silver-filigree-bangle', 'Delicate filigree work sterling silver bangle from Cuttack craftsmen.', 4500.00, 3999.00, 'Silver', 22.00, '92.5%', 10, 'bangle3.jpg', 0),

-- Ring (cat 5)
(5, 'Solitaire Engagement Ring', 'solitaire-engagement-ring', 'Classic round brilliant solitaire diamond set in 18K white gold band.', 55000.00, 49999.00, 'White Gold + Diamond', 4.50, '18K', 8, 'ring1.jpg', 1),
(5, 'Floral Gold Ring', 'floral-gold-ring', 'Intricate floral pattern 22K gold ring, handcrafted by artisans.', 12500.00, 10999.00, 'Gold', 5.80, '22K', 12, 'ring2.jpg', 0),
(5, 'Couple Band Set', 'couple-band-set', 'Matching couple wedding band set in 18K yellow gold.', 38000.00, 34500.00, 'Gold', 9.00, '18K', 6, 'ring3.jpg', 1),

-- Necklace (cat 6)
(6, 'Temple Bridal Necklace', 'temple-bridal-necklace', 'Grand temple-style bridal necklace in 22K gold with ruby and emerald.', 125000.00, 115000.00, 'Gold + Ruby', 58.00, '22K', 2, 'necklace1.jpg', 1),
(6, 'Layered Pearl Necklace', 'layered-pearl-necklace', 'Multi-strand freshwater pearl necklace in sterling silver.', 8500.00, 7499.00, 'Silver + Pearl', 35.00, '92.5%', 9, 'necklace2.jpg', 0),

-- Earring (cat 7)
(7, 'Chandbali Gold Earring', 'chandbali-gold-earring', 'Traditional Chandbali design 22K gold earrings with meenakari work.', 21000.00, 18500.00, 'Gold', 11.50, '22K', 7, 'earring1.jpg', 1),
(7, 'Diamond Hoop Earring', 'diamond-hoop-earring', 'Sparkling diamond-studded 18K gold hoop earrings for every occasion.', 42000.00, 38000.00, 'Gold + Diamond', 6.20, '18K', 5, 'earring2.jpg', 0),
(7, 'Silver Jhumka Earring', 'silver-jhumka-earring', 'Oxidised silver jhumka earrings with traditional bell drops.', 1800.00, 1499.00, 'Silver', 14.00, '92.5%', 25, 'earring3.jpg', 0);

-- Coupons
INSERT INTO coupons (code, type, discount, min_order, max_uses, expiry) VALUES
('SAVE10', 'percent', 10.00, 5000.00, 200, '2027-12-31'),
('FLAT500', 'flat', 500.00, 3000.00, 100, '2027-12-31'),
('BRIDAL20', 'percent', 20.00, 50000.00, 50, '2027-12-31');

-- Reviews
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(1, 1, 5, 'Absolutely beautiful kolusu! The craftsmanship is outstanding. Worth every rupee.'),
(1, 4, 5, 'The peacock jhumkas are stunning. Got so many compliments at the wedding.'),
(1, 7, 4, 'Lovely chain, very lightweight and shiny. Packaging was also premium.'),
(1, 13, 5, 'The diamond ring is breathtaking. My fiancée loved it!'),
(1, 16, 5, 'The bridal necklace is a masterpiece. Exactly as described and pictured.');
