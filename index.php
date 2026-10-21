<?php
require_once 'includes/config.php';
$title = 'Home';
$pdo = db();
$categories = $pdo->query('SELECT * FROM categories WHERE status=1 ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status=1 ORDER BY p.featured DESC,p.id DESC LIMIT 8')->fetchAll();
$siteName = site_setting('store_name', 'ShopNex');
$heroEyebrow = site_setting('hero_eyebrow', 'NEW SEASON COLLECTION');
$heroTitle = site_setting('hero_title', 'Upgrade Your Lifestyle Today');
$heroSubtitle = site_setting('hero_subtitle', 'Explore top brands, latest products and amazing deals at ShopNex.');
$heroImage = site_setting('hero_image', 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1200&q=85');
$heroButton = site_setting('hero_button', 'Shop Now');
$heroLink = site_setting('hero_link', 'products.php');
$offerTitle = site_setting('offer_title', 'Big Electronics Sale');
$offerSubtitle = site_setting('offer_subtitle', 'Save up to 50% on selected favourites.');
$offerImage = site_setting('offer_image', 'https://images.unsplash.com/photo-1498049794561-52e0e0c5e3c8?auto=format&fit=crop&w=1200&q=80');
$offerButton = site_setting('offer_button', 'Explore Deals');
$offerLink = site_setting('offer_link', 'products.php');
$extraOffers=[]; foreach([2,3] as $n){$key='offer'.$n.'_';$t=site_setting($key.'title','');if(trim($t)!=='')$extraOffers[]=['title'=>$t,'subtitle'=>site_setting($key.'subtitle',''),'image'=>site_setting($key.'image',''),'button'=>site_setting($key.'button','Shop Now'),'link'=>site_setting($key.'link','products.php'),'position'=>site_setting($key.'image_position','center center')];}
$primary = site_setting('theme_primary', '#6f2cff');
$accent = site_setting('theme_accent', '#ff3e8a');
$themeStyle = site_setting('theme_style', 'purple');
$fontBase = site_setting('font_base', 'Inter, system-ui, sans-serif');
$fontHeading = site_setting('font_heading', 'Inter, system-ui, sans-serif');
$fontHero = site_setting('font_hero', 'Inter, system-ui, sans-serif');
$fontProducts = site_setting('font_products', 'Inter, system-ui, sans-serif');
$heroPosition=site_setting('hero_image_position','center center');$offerPosition=site_setting('offer_image_position','center center');
require 'includes/header.php';
?>
<style>:root{--purple:<?= e($primary) ?>;--purple2:<?= e($accent) ?>;--ink:#171329;--soft:#f5f1ff;--font-base:<?= e($fontBase) ?>;--font-heading:<?= e($fontHeading) ?>;--font-hero:<?= e($fontHero) ?>;--font-products:<?= e($fontProducts) ?>}body{font-family:var(--font-base)}.section-title,.section-heading{font-family:var(--font-heading)}.shopnex-hero h1{font-family:var(--font-hero)}.product-card{font-family:var(--font-products)}body.theme-blue{--soft:#eef7ff}body.theme-blue .shopnex-hero{background:radial-gradient(circle at 75% 50%,#cdeaff 0,#e8f5ff 30%,#fff 75%)}body.theme-blue .shopnex-offer{background:linear-gradient(90deg,#06234d,#0866c6 55%,#24b8ff)}body.theme-vibrant .shopnex-hero{background:radial-gradient(circle at 75% 50%,#ffd6f0 0,#eee0ff 38%,#fff 75%)}body.theme-vibrant .shopnex-offer{background:linear-gradient(105deg,#251047,#5b17d6 55%,#ff3d96)}body.theme-purple .shopnex-hero{background:radial-gradient(circle at 75% 50%,#f5dfff 0,#f2e7ff 30%,#fff 75%)}</style><script>document.addEventListener('DOMContentLoaded',()=>document.body.classList.add('theme-<?= e($themeStyle) ?>'));</script>
<div class="shopnex-promo-strip"><span><i class="bi bi-truck me-1"></i> Free shipping on eligible orders</span><span><i class="bi bi-arrow-repeat me-1"></i> Easy returns</span><span><i class="bi bi-clock me-1"></i> 7 days delivery</span><span><i class="bi bi-headset me-1"></i> 24/7 support</span></div>
<section class="shopnex-hero mb-5">
  <div class="shopnex-hero-copy">
    <span class="hero-eyebrow"><?= e($heroEyebrow) ?></span>
    <h1><?= e($heroTitle) ?></h1>
    <p><?= e($heroSubtitle) ?></p>
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-purple btn-lg" href="<?= e($heroLink) ?>"><?= e($heroButton) ?> <i class="bi bi-arrow-right ms-1"></i></a><a class="btn btn-light btn-lg border" href="products.php">View Offers</a></div>
    <div class="hero-trust"><span><i class="bi bi-shield-check"></i> Secure shopping</span><span><i class="bi bi-star-fill"></i> Great deals</span></div>
  </div>
  <div class="shopnex-hero-visual"><div class="hero-orbit"></div><img style="object-position:<?=e($heroPosition)?>" src="<?= e($heroImage) ?>" alt="<?= e($heroTitle) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1200&q=85'"><span class="hero-discount">UP TO <strong>50%</strong> OFF</span></div>
</section>
<section class="benefits-strip mb-5"><div><i class="bi bi-truck"></i><span><strong>Free Shipping</strong><small>On eligible orders</small></span></div><div><i class="bi bi-calendar-check"></i><span><strong>7 Days Delivery</strong><small>Fast & reliable</small></span></div><div><i class="bi bi-shield-lock"></i><span><strong>Secure Payment</strong><small>Safe checkout</small></span></div><div><i class="bi bi-arrow-repeat"></i><span><strong>Easy Returns</strong><small>Hassle-free returns</small></span></div><div><i class="bi bi-headset"></i><span><strong>24/7 Support</strong><small>We're here to help</small></span></div></section>
<section class="mb-5"><div class="section-heading"><div><span class="section-kicker">EXPLORE THE STORE</span><h2 class="section-title">Shop by Category</h2></div><a href="products.php">View all <i class="bi bi-arrow-up-right"></i></a></div><div class="category-grid"><?php foreach($categories as $i=>$c): ?><a class="category-tile" href="products.php?category=<?= (int)$c['id'] ?>"><span class="category-icon"><i class="bi <?= ['bi-phone','bi-laptop','bi-headphones','bi-controller','bi-bag','bi-house','bi-heart-pulse','bi-bicycle','bi-book','bi-briefcase','bi-puzzle','bi-car-front','bi-basket','bi-gem'][$i%14] ?>"></i></span><strong><?= e($c['name']) ?></strong><i class="bi bi-arrow-up-right tile-arrow"></i></a><?php endforeach; ?></div></section>
<section class="shopnex-offer mb-5" style="--offer-image:url('<?= e($offerImage) ?>');--offer-position:<?=e($offerPosition)?>"><div><span class="offer-kicker">LIMITED TIME OFFER</span><h2><?= e($offerTitle) ?></h2><p><?= e($offerSubtitle) ?></p><a href="<?= e($offerLink) ?>" class="btn btn-light fw-bold"><?= e($offerButton) ?> <i class="bi bi-arrow-right ms-1"></i></a></div><div class="offer-burst">UP TO<br><strong>50% OFF</strong></div></section>
<?php if($extraOffers): ?><section class="row g-3 mb-5"><?php foreach($extraOffers as $extra): ?><div class="col-md-6"><div class="shopnex-extra-offer h-100" style="--extra-image:url('<?=e($extra['image'] ?: $offerImage)?>');--extra-position:<?=e($extra['position'])?>"><span class="offer-kicker">SPECIAL OFFER</span><h3><?=e($extra['title'])?></h3><p><?=e($extra['subtitle'])?></p><a class="btn btn-light fw-bold" href="<?=e($extra['link'])?>"><?=e($extra['button'])?> <i class="bi bi-arrow-right ms-1"></i></a></div></div><?php endforeach; ?></section><?php endif; ?>
<section class="mb-5"><div class="section-heading"><div><span class="section-kicker">HANDPICKED FOR YOU</span><h2 class="section-title">Featured Products</h2></div><a href="products.php">Shop all products <i class="bi bi-arrow-up-right"></i></a></div><div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 g-lg-4"><?php foreach($products as $p) require 'includes/product-card.php'; ?></div></section>
<div class="newsletter-panel mb-4"><div><span class="section-kicker">STAY IN THE LOOP</span><h3>Good finds. Great prices.</h3><p class="mb-0">Explore fresh arrivals and offers picked for you.</p></div><a href="products.php" class="btn btn-purple">Explore all products <i class="bi bi-arrow-right ms-1"></i></a></div>
<div class="mobile-bottom"><a href="index.php"><i class="bi bi-house"></i>Home</a><a href="products.php"><i class="bi bi-grid"></i>Products</a><a href="wishlist.php"><i class="bi bi-heart"></i>Wishlist</a><a href="cart.php"><i class="bi bi-cart"></i>Cart</a><a href="account.php"><i class="bi bi-person"></i>Account</a></div>
<?php require 'includes/footer.php'; ?>