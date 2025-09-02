<?php
 
/**
* Child theme bootstrap.
* Keep this file lean; load product-editor from /inc.
*/
 
// Do not allow direct access.
if (! defined('ABSPATH')) {
  exit;
}
 
if (! defined('AVADA_VERSION')) {
  define('AVADA_VERSION', '7.12.2');
}
if (! defined('AVADA_MIN_PHP_VER_REQUIRED')) {
  define('AVADA_MIN_PHP_VER_REQUIRED', '5.6');
}
if (! defined('AVADA_MIN_WP_VER_REQUIRED')) {
  define('AVADA_MIN_WP_VER_REQUIRED', '4.9');
}
if (! defined('AVADA_DEV_MODE')) {
  define('AVADA_DEV_MODE', false);
}
 
/**
* Load the Product Editor module (all hooks live inside).
*/
require_once get_theme_file_path('inc/product-editor.php');
 
/**
* Compatibility check.
* (Keep Avada bootstrap lines that are not part of the product editor.)
*/
if (version_compare($GLOBALS['wp_version'], AVADA_MIN_WP_VER_REQUIRED, '<') || version_compare(PHP_VERSION, AVADA_MIN_PHP_VER_REQUIRED, '<')) {
  require_once get_template_directory() . '/includes/bootstrap-compat.php';
  return;
}
 
/**
* Bootstrap the parent theme.
*/
require_once get_template_directory() . '/includes/bootstrap.php';
 
/**
* === HOTFIX TURBO: niente base64 pesante in carrello/checkout,
* niente Font Awesome globale, JS in defer, canvas più leggero ===
*/
 
// 1) Non usare l’anteprima personalizzata in carrello/checkout/email
add_filter('woocommerce_cart_item_thumbnail', function($image, $cart_item){
  return $image; // usa la miniatura del prodotto (leggera e cacheata)
}, 5, 2);
add_filter('woocommerce_order_item_thumbnail', function($image){
  return $image; // niente sostituzioni
}, 5, 1);
add_filter('woocommerce_email_order_item_thumbnail', function($image){
  return $image; // lascia quella standard nelle email
}, 5, 1);
 
// 2) Evita di caricare Font Awesome ovunque (se registrato dallo stesso tema/child)
add_action('wp_enqueue_scripts', function(){
  if (function_exists('is_product') && ! is_product()) {
    wp_dequeue_style('font-awesome');
    wp_deregister_style('font-awesome');
  }
}, 99);
 
// 3) Metti in defer lo script dell’editor (non blocca il rendering)
add_filter('script_loader_tag', function($tag, $handle){
  if ($handle === 'product-editor') {
    return str_replace('<script ', '<script defer ', $tag);
  }
  return $tag;
}, 10, 2);
 
// 4) Riduci dimensione canvas a 600/360 px (meno lavoro di draw)
add_action('wp_footer', function(){
  if (! (function_exists('is_product') && is_product())) return;
  ?>
  <script>
  (function(){
    var c = document.getElementById('pe-canvas');
    if (!c) return;
    var isMobile = /Mobi|Android/i.test(navigator.userAgent);
    var target = isMobile ? 1200 : 1200;
    if (c.width > target) { c.width = target; c.height = target; }
  })();
  </script>
  <?php
}, 99);