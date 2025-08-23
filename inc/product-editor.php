<?php

namespace Avada\ProductEditor;

/**
 * Product Editor module
 * All hooks, markup, and behaviors for the WooCommerce product editor.
 */

defined('ABSPATH') || exit;


/**
 * Boot the module.
 * Hook into WP/Woo only when needed.
 */
function setup()
{

  // 1) i18n: load child theme textdomain
  add_action('after_setup_theme', __NAMESPACE__ . '\\load_textdomain');

  // 2) Include Font Awesome (used by editor buttons)
  add_action('wp_head', __NAMESPACE__ . '\\enqueue_fontawesome');

  // 3) Optional: debug translation loading in logs
  add_action('init', __NAMESPACE__ . '\\debug_i18n');

  // 4) Remove default Woo gallery and insert our editor on single product pages
  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
  add_action('woocommerce_before_single_product_summary', __NAMESPACE__ . '\\render_editor', 5);

  // 5) Cart / order meta plumbing
  add_filter('woocommerce_add_cart_item_data', __NAMESPACE__ . '\\capture_cart_item_data', 10, 3);
  add_action('woocommerce_checkout_create_order_line_item', __NAMESPACE__ . '\\save_order_item_meta', 10, 4);
  add_action('woocommerce_admin_order_item_values', __NAMESPACE__ . '\\show_admin_order_item_preview', 10, 3);
  add_filter('woocommerce_get_item_data', __NAMESPACE__ . '\\show_cart_checkout_preview', 10, 2);
  add_filter('woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\validate_before_add_to_cart', 10, 3);

  // 6) Front-end assets for the editor on product pages
  add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_editor_js');
}
setup();

/** =========================
 *  1) I18N
 *  ========================= */
function load_textdomain()
{
  load_child_theme_textdomain('pe-textdomain', get_stylesheet_directory() . '/languages');
}

/** =========================
 *  2) Font Awesome
 *  ========================= */
function enqueue_fontawesome()
{
  // Using CDN; version left null for CDN caching
  wp_enqueue_style(
    'font-awesome',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    array(),
    null
  );
}

/** =========================
 *  3) Debug i18n (optional logs)
 *  ========================= */
function debug_i18n()
{
  $locale = get_locale(); // e.g., it_IT
  $path   = get_stylesheet_directory() . '/languages/pe-textdomain-' . $locale . '.mo';

  error_log('Current Locale: ' . $locale);
  error_log('Expected MO Path: ' . $path);
  error_log('MO File Exists: ' . (file_exists($path) ? 'Yes' : 'No'));
  error_log(is_textdomain_loaded('pe-textdomain') ? 'pe-textdomain is loaded.' : 'pe-textdomain is NOT loaded.');

  // Test a string
  error_log('Test Translation: ' . __('Rotate Left', 'pe-textdomain'));
}

/** =========================
 *  4) Editor Markup (before summary)
 *  ========================= */
function render_editor()
{
?>
  <div id="pe-editor">
    <div id="pe-body">
      <!-- Primary border image -->
      <img id="pe-border-img"
        src="<?php echo esc_url(get_stylesheet_directory_uri() . '/images/input-border.png'); ?>"
        alt="Border Frame"
        class="pe-border-frame"
        onerror="this.style.display='none'; document.getElementById('pe-border-fallback').style.display='block';" />

      <!-- Fallback decorative border -->
      <div id="pe-border-fallback" class="pe-border-fallback"></div>

      <!-- Canvas for editing -->
      <canvas id="pe-canvas"
        width="400"
        height="400"
        class="pe-empty-canvas"
        aria-label="<?php echo esc_attr__('Image preview canvas', 'pe-textdomain'); ?>"
        role="img"></canvas>

      <!-- Centered Add Image Button -->
      <button id="pe-load-border" type="button">
        📷 <?php echo esc_html__('Add Image', 'pe-textdomain'); ?>
      </button>
    </div>

    <div id="pe-controls">
      <button type="button" id="pe-rotate-left" class="pe-btn"
        title="<?php echo esc_attr__('Rotate left', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Rotate left', 'pe-textdomain'); ?>">
        <i class="fas fa-undo-alt" aria-hidden="true"></i>
        <?php echo esc_html__('Rotate Left', 'pe-textdomain'); ?>
      </button>

      <button type="button" id="pe-rotate-right" class="pe-btn"
        title="<?php echo esc_attr__('Rotate right', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Rotate right', 'pe-textdomain'); ?>">
        <i class="fas fa-redo-alt" aria-hidden="true"></i>
        <?php echo esc_html__('Rotate Right', 'pe-textdomain'); ?>
      </button>

      <button type="button" id="pe-zoom-out" class="pe-btn"
        title="<?php echo esc_attr__('Zoom out', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Zoom out', 'pe-textdomain'); ?>">
        <i class="fas fa-search-minus" aria-hidden="true"></i>
        <?php echo esc_html__('Zoom Out', 'pe-textdomain'); ?>
      </button>

      <button type="button" id="pe-zoom-in" class="pe-btn"
        title="<?php echo esc_attr__('Zoom in', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Zoom in', 'pe-textdomain'); ?>">
        <i class="fas fa-search-plus" aria-hidden="true"></i>
        <?php echo esc_html__('Zoom In', 'pe-textdomain'); ?>
      </button>

      <button type="button" id="pe-reset" class="pe-btn"
        title="<?php echo esc_attr__('Reset view', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Reset view', 'pe-textdomain'); ?>">
        <i class="fas fa-home" aria-hidden="true"></i>
        <?php echo esc_html__('Reset View', 'pe-textdomain'); ?>
      </button>

      <button type="button" id="pe-clear" class="pe-btn"
        title="<?php echo esc_attr__('Clear image', 'pe-textdomain'); ?>"
        aria-label="<?php echo esc_attr__('Clear image', 'pe-textdomain'); ?>">
        <i class="fas fa-trash" aria-hidden="true"></i>
        <?php echo esc_html__('Clear', 'pe-textdomain'); ?>
      </button>
    </div>

    <!-- Hidden file input -->
    <input type="file" id="pe-file" accept="image/*" />
    <input type="hidden" name="image_customization" id="pe-data">

    <!-- Status message area -->
    <div id="pe-status-message" class="pe-status-message"></div>
  </div>
<?php
}

/** =========================
 *  5) Cart / Order meta
 *  ========================= */

/**
 * Capture product editor data when adding to cart.
 */
function capture_cart_item_data($cart_item_data, $product_id, $variation_id)
{
  if (! empty($_POST['image_customization'])) {
    $raw     = wp_unslash($_POST['image_customization']);
    $decoded = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE) {
      $cart_item_data['image_customization'] = $decoded; // store array
    }
  }
  return $cart_item_data;
}

/**
 * Persist customization to order item meta.
 * Also, if finalImage is a base64 data URL, save it to Media Library and add a URL back into the payload.
 */
function save_order_item_meta($item, $cart_item_key, $values, $order)
{
  if (empty($values['image_customization'])) {
    return;
  }

  $data = $values['image_customization'];

  if (! empty($data['finalImage'])) {
    $image_data = $data['finalImage'];

    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
      $image_data = substr($image_data, strpos($image_data, ',') + 1);
      $type       = strtolower($type[1]); // jpg, png, gif, etc.

      $image_data = base64_decode($image_data);
      $filename   = 'custom_' . time() . '.' . $type;
      $upload     = wp_upload_bits($filename, null, $image_data);

      if (! $upload['error']) {
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment  = array(
          'post_mime_type' => $wp_filetype['type'],
          'post_title'     => sanitize_file_name($filename),
          'post_content'   => '',
          'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        $data['finalImageURL'] = wp_get_attachment_url($attach_id);
      }
    }
  }

  $item->add_meta_data('_image_customization', wp_json_encode($data), true);
}

/**
 * Show a preview block in the admin order screen.
 */
function show_admin_order_item_preview($product, $item, $item_id)
{
  $json = $item->get_meta('_image_customization');
  if (! $json) {
    return;
  }
  $data = json_decode($json, true);
  if (! is_array($data) || empty($data['finalImage'])) {
    return;
  }

  echo '<div style="margin:8px 0; padding:8px; border:1px solid #eee; background:#fafafa;">';
  echo '<strong>' . esc_html__('Customization', 'pe-textdomain') . '</strong><br>';
  echo '<img src="' . esc_url($data['finalImage']) . '" style="max-width:160px; height:auto; border:1px solid #ddd; margin:6px 0;" alt="' . esc_attr__('Customized Image', 'pe-textdomain') . '">';
  echo '<div style="font-size:12px; color:#333;">';
  echo esc_html__('Rotation', 'pe-textdomain') . ': ' . esc_html($data['rotation']) . '°<br>';
  echo esc_html__('Zoom', 'pe-textdomain') . ': ' . esc_html($data['zoom']) . 'x<br>';
  echo esc_html__('Position', 'pe-textdomain') . ': (' . esc_html($data['positionX']) . ', ' . esc_html($data['positionY']) . ')<br>';
  echo '</div>';
  echo '</div>';
}

/**
 * Show preview in cart / checkout.
 */
function show_cart_checkout_preview($item_data, $cart_item)
{
  if (! empty($cart_item['image_customization']['finalImage'])) {
    $item_data[] = array(
      'key'     => esc_html__('Custom Image', 'pe-textdomain'),
      'value'   => '<img src="' . esc_url($cart_item['image_customization']['finalImage']) . '" style="max-width:120px; height:auto; border:1px solid #ddd;" alt="' . esc_attr__('Customized Image Preview', 'pe-textdomain') . '">',
      'display' => '',
    );
  }
  return $item_data;
}

/**
 * Validate that customization exists before adding to cart.
 */
function validate_before_add_to_cart($passed, $product_id, $quantity)
{
  if (empty($_POST['image_customization'])) {
    wc_add_notice(__('Please upload and apply your image customization before adding to cart.', 'pe-textdomain'), 'error');
    return false;
  }
  return $passed;
}

/** =========================
 *  6) Front-end JS
 *  ========================= */
function enqueue_editor_js()
{
  if (! is_product()) {
    return;
  }

  // Enqueue CSS for product editor
  wp_enqueue_style(
    'product-editor-css',
    get_stylesheet_directory_uri() . '/css/product-editor.css',
    array(),
    filemtime(get_stylesheet_directory() . '/css/product-editor.css')
  );

  // Enqueue JS for product editor
  $handle = 'product-editor';
  $src    = get_stylesheet_directory_uri() . '/js/product-editor.js';
  $path   = get_stylesheet_directory() . '/js/product-editor.js';
  $ver    = file_exists($path) ? filemtime($path) : null;

  wp_enqueue_script($handle, $src, array(), $ver, true);

  wp_localize_script($handle, 'peVars', array(
    'borderImageUrl' => get_stylesheet_directory_uri() . '/images/input-border.png',
    'ajaxUrl'        => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('pe_nonce'),
    'strings'        => array(
      'imageLoaded' => __('Image loaded successfully!', 'pe-textdomain'),
      'imageCleared' => __('Image cleared.', 'pe-textdomain'),
      'invalidFile' => __('Please select a valid image file.', 'pe-textdomain'),
      'loadError'   => __('Error loading image. Please try another file.', 'pe-textdomain'),
    ),
  ));
}
