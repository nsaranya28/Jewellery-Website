<?php
// ── SITE CONFIG ──────────────────────────────────────────────
// Detect the base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$project_root = str_replace('\\', '/', dirname(__DIR__));
$url_path = str_replace($doc_root, '', $project_root);
if ($url_path && $url_path[0] !== '/') $url_path = '/' . $url_path;
define('SITE_URL', $protocol . '://' . $host . $url_path);


define('SITE_NAME', 'Jewels.com');
define('CURRENCY', '₹');

// ── SESSION ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── AUTH HELPERS ──────────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
function currentUser() {
    return $_SESSION['user'] ?? null;
}

// ── FORMATTING ────────────────────────────────────────────────
function money($amount) {
    return CURRENCY . number_format($amount, 2);
}
function safeHtml($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
function truncate($str, $len = 80) {
    return mb_strlen($str) > $len ? mb_substr($str, 0, $len) . '…' : $str;
}
function timeAgo($datetime) {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->y) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ── PRODUCT IMAGE ─────────────────────────────────────────────
function productImage($img, $w = 400, $h = 400) {
    // Use beautiful placeholder images from picsum styled for jewellery
    $placeholders = [
        'kolusu1.jpg'   => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w='.$w.'&h='.$h.'&fit=crop',
        'kolusu2.jpg'   => 'https://images.unsplash.com/photo-1573408301185-9519f94816b5?w='.$w.'&h='.$h.'&fit=crop',
        'kolusu3.jpg'   => 'https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w='.$w.'&h='.$h.'&fit=crop',
        'kammal1.jpg'   => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w='.$w.'&h='.$h.'&fit=crop',
        'kammal2.jpg'   => 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?w='.$w.'&h='.$h.'&fit=crop',
        'kammal3.jpg'   => 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?w='.$w.'&h='.$h.'&fit=crop',
        'chain1.jpg'    => 'https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w='.$w.'&h='.$h.'&fit=crop',
        'chain2.jpg'    => 'https://images.unsplash.com/photo-1589128777073-263566ae5e4d?w='.$w.'&h='.$h.'&fit=crop',
        'chain3.jpg'    => 'https://images.unsplash.com/photo-1602173574767-37ac01994b2a?w='.$w.'&h='.$h.'&fit=crop',
        'bangle1.jpg'   => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w='.$w.'&h='.$h.'&fit=crop',
        'bangle2.jpg'   => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w='.$w.'&h='.$h.'&fit=crop',
        'bangle3.jpg'   => 'https://images.unsplash.com/photo-1543294001-f7cd5d7fb516?w='.$w.'&h='.$h.'&fit=crop',
        'ring1.jpg'     => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w='.$w.'&h='.$h.'&fit=crop',
        'ring2.jpg'     => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w='.$w.'&h='.$h.'&fit=crop',
        'ring3.jpg'     => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w='.$w.'&h='.$h.'&fit=crop',
        'necklace1.jpg' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w='.$w.'&h='.$h.'&fit=crop',
        'necklace2.jpg' => 'https://images.unsplash.com/photo-1610694955371-d4a3e0ce4b52?w='.$w.'&h='.$h.'&fit=crop',
        'earring1.jpg'  => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w='.$w.'&h='.$h.'&fit=crop',
        'earring2.jpg'  => 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?w='.$w.'&h='.$h.'&fit=crop',
        'earring3.jpg'  => 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?w='.$w.'&h='.$h.'&fit=crop',
    ];
    if ($img && file_exists(__DIR__ . '/../uploads/products/' . $img)) {
        return SITE_URL . '/uploads/products/' . $img;
    }
    return $placeholders[$img] ?? 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w='.$w.'&h='.$h.'&fit=crop';
}

// ── CATEGORY IMAGE ────────────────────────────────────────────
function categoryImage($slug) {
    $map = [
        'kolusu'      => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=400&fit=crop',
        'kammal'      => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=400&h=400&fit=crop',
        'chain'       => 'https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w=400&h=400&fit=crop',
        'bangle'      => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=400&h=400&fit=crop',
        'ring'        => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=400&h=400&fit=crop',
        'necklace'    => 'https://images.unsplash.com/photo-1610694955371-d4a3e0ce4b52?w=400&h=400&fit=crop',
        'earring'     => 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=400&h=400&fit=crop',
        'bracelets'   => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=400&h=400&fit=crop',
        'pendants'    => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=400&h=400&fit=crop',
        'mangalsutra' => 'https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w=400&h=400&fit=crop',
        'nose-pins'   => 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?w=400&h=400&fit=crop',
        'gold-coins'  => 'https://images.unsplash.com/photo-1589750678059-a9627a7a8f5d?w=400&h=400&fit=crop',
        'anklets'     => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=400&fit=crop',
    ];
    return $map[$slug] ?? 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=400&fit=crop';
}

// ── CART HELPERS ──────────────────────────────────────────────
function cartKey() {
    if (!isset($_SESSION['cart_session'])) {
        $_SESSION['cart_session'] = session_id();
    }
    return $_SESSION['cart_session'];
}
function cartCount(PDO $pdo) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id=?");
        $stmt->execute([cartKey()]);
    }
    return (int)$stmt->fetchColumn();
}
function wishlistCount(PDO $pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// ── STAR RATING HTML ──────────────────────────────────────────
function starRating($rating, $max = 5) {
    $html = '<span class="stars">';
    $full = floor($rating);
    $half = ($rating - $full) >= 0.4;
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $full) $html .= '<i class="fas fa-star"></i>';
        elseif ($half && $i == $full + 1) { $html .= '<i class="fas fa-star-half-alt"></i>'; $half = false; }
        else $html .= '<i class="far fa-star"></i>';
    }
    $html .= '</span>';
    return $html;
}

// ── PRODUCT RATING ────────────────────────────────────────────
function productRating(PDO $pdo, $productId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE product_id=?");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

// ── REDIRECT W/ MESSAGE ───────────────────────────────────────
function flashMessage($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function showFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $icon = $f['type'] === 'success' ? '✅' : ($f['type'] === 'error' ? '❌' : 'ℹ️');
        echo '<div class="flash flash-'.$f['type'].'">'.$icon.' '.safeHtml($f['msg']).'</div>';
    }
}

// ── SLUG GEN ──────────────────────────────────────────────────
function makeSlug($str) {
    $str = mb_strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    return preg_replace('/[\s-]+/', '-', $str);
}

// ── APPLY COUPON ──────────────────────────────────────────────
function applyCoupon(PDO $pdo, $code, $subtotal) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND used_count < max_uses");
    $stmt->execute([strtoupper($code)]);
    $coupon = $stmt->fetch();

    if (!$coupon) return ['error' => 'Invalid Coupon'];

    $today = date('Y-m-d');
    if ($coupon['start_date'] && $today < $coupon['start_date']) {
        return ['error' => 'Coupon not yet active. Valid from ' . date('d M Y', strtotime($coupon['start_date']))];
    }

    $endDate = $coupon['end_date'] ?: $coupon['expiry'];
    if ($endDate && $today > $endDate) {
        return ['error' => 'Coupon Expired'];
    }

    // Security: check coupon_usage table (once per user)
    if (isLoggedIn()) {
        $check = $pdo->prepare("SELECT id FROM coupon_usage WHERE user_id=? AND coupon_code=?");
        $check->execute([$_SESSION['user_id'], $coupon['code']]);
        if ($check->fetch()) {
            return ['error' => 'Already Used'];
        }
    }

    if ($subtotal > 0 && $subtotal < $coupon['min_amount']) {
        return ['error' => 'Minimum amount not reached'];
    }

    $discount = ($coupon['type'] === 'percent') ? ($subtotal * $coupon['discount'] / 100) : $coupon['discount'];
    return ['discount' => min($discount, $subtotal), 'coupon' => $coupon];
}

// ── PAGINATE ──────────────────────────────────────────────────
function paginate($total, $current, $perPage, $url) {
    $pages = ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<div class="pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i == $current ? ' active' : '';
        $sep = strpos($url, '?') !== false ? '&' : '?';
        $html .= '<a href="'.$url.$sep.'page='.$i.'" class="page-btn'.$active.'">'.$i.'</a>';
    }
    return $html . '</div>';
}
