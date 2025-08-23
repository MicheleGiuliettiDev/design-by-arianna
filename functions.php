<?php

/**
 * Extra files & functions are hooked here.
 *
 * Displays all of the head element and everything up until the "site-content" div.
 *
 * @package Avada
 * @subpackage Core
 * @since 1.0
 */

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
  exit('Direct script access denied.');
}

if (!defined('AVADA_VERSION')) {
  define('AVADA_VERSION', '7.12.2');
}
if (!defined('AVADA_MIN_PHP_VER_REQUIRED')) {
  define('AVADA_MIN_PHP_VER_REQUIRED', '5.6');
}
if (!defined('AVADA_MIN_WP_VER_REQUIRED')) {
  define('AVADA_MIN_WP_VER_REQUIRED', '4.9');
}
// Developer mode.
if (!defined('AVADA_DEV_MODE')) {
  define('AVADA_DEV_MODE', false);
}

// Load child theme textdomain
add_action('after_setup_theme', function () {
  load_child_theme_textdomain('pe-textdomain', get_stylesheet_directory() . '/languages');
});

// Enqueue Font Awesome
add_action('wp_head', function () {
  wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), null);
});

// Debug locale and translations
add_action('init', function () {
  $locale = get_locale();  // Should be 'it_IT' for Italian
  $path = get_stylesheet_directory() . '/languages/pe-textdomain-' . $locale . '.mo';

  error_log('Current Locale: ' . $locale);
  error_log('Expected MO Path: ' . $path);
  error_log('MO File Exists: ' . (file_exists($path) ? 'Yes' : 'No'));

  if (is_textdomain_loaded('pe-textdomain')) {
    error_log('pe-textdomain is loaded.');
  } else {
    error_log('pe-textdomain is NOT loaded.');
  }

  // Test a string
  error_log('Test Translation: ' . __('Rotate Left', 'pe-textdomain'));
});

// Remove default WooCommerce product images
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

// Add custom photo editor before product summary
add_action('woocommerce_before_single_product_summary', function () {
?>
  <style>
    /* === Product Editor Container === */
    #pe-editor {
      margin: 1.5rem auto;
      position: relative;
      max-width: 500px;
      width: 100%;
      background: #fff;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
      transition: box-shadow 0.3s ease;
    }

    #pe-editor:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    /* === Canvas Body === */
    #pe-body {
      margin: 0 auto;
      position: relative;
      width: 100%;
      max-width: 420px;
      aspect-ratio: 1 / 1;
      border-radius: 10px;
      overflow: hidden;
      background: #fafafa;
    }

    /* === Border Frame & Fallback === */
    .pe-border-frame,
    .pe-border-fallback {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 10;
      border-radius: 10px;
    }

    .pe-border-fallback {
      border: 6px solid transparent;
      border-image: linear-gradient(45deg, #007cba, #005a87, #007cba) 1;
      display: none;
    }

    /* === Canvas === */
    #pe-canvas {
      width: 100%;
      height: 100%;
      border: 1px solid #e0e0e0;
      display: block;
      margin: 0 auto;
      background: #fff;
      border-radius: 6px;
      box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.06);
      cursor: default;
      transition: background 0.2s ease, box-shadow 0.2s ease;
    }

    #pe-canvas.pe-canvas-draggable {
      cursor: grab;
    }

    #pe-canvas.pe-canvas-dragging {
      cursor: grabbing;
    }

    #pe-canvas.pe-empty-canvas {
      background-color: #f8f9fa;
      background-image:
        linear-gradient(#e9ecef 1px, transparent 1px),
        linear-gradient(90deg, #e9ecef 1px, transparent 1px);
      background-size: 20px 20px;
    }

    /* === Centered Add Image Button === */
    #pe-load-border {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 5;
      padding: 0.75rem 1.25rem;
      cursor: pointer;
      background: #007cba;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 0.95rem;
      font-weight: 500;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
      transition: all 0.25s ease;
    }

    #pe-load-border:hover {
      background: #005a87;
      transform: translate(-50%, -50%) scale(1.05);
    }

    #pe-load-border:focus {
      outline: 3px solid #80d0ff;
      outline-offset: 2px;
    }

    /* === Controls Toolbar === */
    #pe-controls {
      margin-top: 1.25rem;
      text-align: center;
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .pe-btn {
      padding: 0.5rem 0.9rem;
      border: 1px solid #ddd;
      background: #f9f9f9;
      cursor: pointer;
      border-radius: 6px;
      font-size: 0.85rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .pe-btn i {
      font-size: 0.9rem;
    }

    .pe-btn:hover {
      background: #efefef;
      border-color: #ccc;
    }

    .pe-btn:active {
      background: #e2e2e2;
    }

    .pe-btn:focus {
      outline: 2px solid #007cba;
      outline-offset: 2px;
    }

    .pe-btn:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      background: #f5f5f5;
      border-color: #e0e0e0;
    }

    /* === File Input (hidden) === */
    #pe-file {
      display: none;
    }

    /* === Status Messages === */
    .pe-status-message {
      text-align: center;
      margin-top: 0.8rem;
      padding: 0.6rem 1rem;
      border-radius: 6px;
      font-size: 0.9rem;
      display: none;
      transition: opacity 0.25s ease, transform 0.25s ease;
    }

    .pe-status-success {
      background: #e6f7ed;
      color: #17633d;
      border: 1px solid #b2e0c8;
    }

    .pe-status-error {
      background: #fdeaea;
      color: #a12d2d;
      border: 1px solid #f3b5b5;
    }
  </style>

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
}, 5);

// Add customization data to cart item
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
  if (!empty($_POST['image_customization'])) {
    // Sanitize and decode the JSON
    $raw = wp_unslash($_POST['image_customization']);
    $decoded = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE) {
      $cart_item_data['image_customization'] = $decoded;  // Store as array
    }
  }
  return $cart_item_data;
}, 10, 3);

// Save data to order item meta
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
  if (!empty($values['image_customization'])) {
    $data = $values['image_customization'];
    if (!empty($data['finalImage'])) {
      // Extract the image data from base64
      $image_data = $data['finalImage'];
      if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

        $image_data = base64_decode($image_data);
        $filename = 'custom_' . time() . '.' . $type;
        $upload_file = wp_upload_bits($filename, null, $image_data);

        if (!$upload_file['error']) {
          // Insert into Media Library
          $wp_filetype = wp_check_filetype($filename, null);
          $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
          );
          $attach_id = wp_insert_attachment($attachment, $upload_file['file']);
          require_once(ABSPATH . 'wp-admin/includes/image.php');
          $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
          wp_update_attachment_metadata($attach_id, $attach_data);

          $data['finalImageURL'] = wp_get_attachment_url($attach_id);
        }
      }
    }
    // Store updated data
    $item->add_meta_data('_image_customization', wp_json_encode($data), true);
  }
}, 10, 4);

// Display customization in admin order details
add_action('woocommerce_admin_order_item_values', function ($product, $item, $item_id) {
  if ($json = $item->get_meta('_image_customization')) {
    $data = json_decode($json, true);
    if (is_array($data) && !empty($data['finalImage'])) {
      echo '<div style="margin:8px 0; padding:8px; border:1px solid #eee; background:#fafafa;">';
      echo '<strong>' . __('Customization', 'pe-textdomain') . '</strong><br>';
      echo '<img src="' . esc_url($data['finalImage']) . '" style="max-width:160px; height:auto; border:1px solid #ddd; margin:6px 0;" alt="' . __('Customized Image', 'pe-textdomain') . '">';
      echo '<div style="font-size:12px; color:#333;">';
      echo __('Rotation', 'pe-textdomain') . ': ' . esc_html($data['rotation']) . '°<br>';
      echo __('Zoom', 'pe-textdomain') . ': ' . esc_html($data['zoom']) . 'x<br>';
      echo __('Position', 'pe-textdomain') . ': (' . esc_html($data['positionX']) . ', ' . esc_html($data['positionY']) . ')<br>';
      echo '</div>';
      echo '</div>';
    }
  }
}, 10, 3);

// Show preview in cart/checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
  if (!empty($cart_item['image_customization']['finalImage'])) {
    $item_data[] = array(
      'key'     => __('Custom Image', 'pe-textdomain'),
      'value'   => '<img src="' . esc_url($cart_item['image_customization']['finalImage']) . '" style="max-width:120px; height:auto; border:1px solid #ddd;" alt="' . __('Customized Image Preview', 'pe-textdomain') . '">',
      'display' => ''
    );
  }
  return $item_data;
}, 10, 2);

// Validate customization before adding to cart
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity) {
  if (empty($_POST['image_customization'])) {
    wc_add_notice(__('Please upload and apply your image customization before adding to cart.', 'pe-textdomain'), 'error');
    return false;
  }
  return $passed;
}, 10, 3);

// Enqueue JavaScript for product editor
function enqueue_product_editor_js()
{
  if (is_product()) {
    wp_enqueue_script(
      'product-editor',
      get_stylesheet_directory_uri() . '/js/product-editor.js',
      array(),
      filemtime(get_stylesheet_directory() . '/js/product-editor.js'), // Cache busting
      true // Load in footer
    );

    // Localize script with variables
    wp_localize_script('product-editor', 'peVars', array(
      'borderImageUrl' => get_stylesheet_directory_uri() . '/images/input-border.png',
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('pe_nonce'),
      'strings' => array(
        'imageLoaded' => __('Image loaded successfully!', 'pe-textdomain'),
        'imageCleared' => __('Image cleared.', 'pe-textdomain'),
        'invalidFile' => __('Please select a valid image file.', 'pe-textdomain'),
        'loadError' => __('Error loading image. Please try another file.', 'pe-textdomain')
      )
    ));
  }
}
add_action('wp_enqueue_scripts', 'enqueue_product_editor_js');

/**
 * Compatibility check.
 */
if (version_compare($GLOBALS['wp_version'], AVADA_MIN_WP_VER_REQUIRED, '<') || version_compare(PHP_VERSION, AVADA_MIN_PHP_VER_REQUIRED, '<')) {
  require_once get_template_directory() . '/includes/bootstrap-compat.php';
  return;
}

/**
 * Bootstrap the theme.
 */
require_once get_template_directory() . '/includes/bootstrap.php';

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
