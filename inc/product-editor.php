<?php

namespace Avada\ProductEditor;

defined('ABSPATH') || exit;

/** =========================================================================
 * Setup
 * ========================================================================= */
function setup()
{
  // i18n & assets
  add_action('after_setup_theme', __NAMESPACE__ . '\\load_textdomain');
  add_action('wp_head',           __NAMESPACE__ . '\\enqueue_fontawesome');
  add_action('init',              __NAMESPACE__ . '\\debug_i18n');

  // Editor (outside form) + hidden field inside form
  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
  add_action('woocommerce_before_single_product_summary', __NAMESPACE__ . '\\render_editor', 5);
  add_action('woocommerce_before_add_to_cart_button',   __NAMESPACE__ . '\\output_form_field', 1);

  // Cart / order meta
  add_filter('woocommerce_add_cart_item_data',              __NAMESPACE__ . '\\capture_cart_item_data', 10, 3);
  add_action('woocommerce_checkout_create_order_line_item', __NAMESPACE__ . '\\save_order_item_meta', 10, 4);
  add_action('woocommerce_admin_order_item_values',         __NAMESPACE__ . '\\show_admin_order_item_preview', 10, 3);

  // Product page validation (required only when adding to cart)
  add_filter('woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\validate_before_add_to_cart', 10, 3);

  // Cart / Mini-cart / Checkout left thumbnail (clickable)
  add_filter('woocommerce_cart_item_thumbnail', __NAMESPACE__ . '\\pe_cart_item_thumbnail', 99, 3);

  // Cart / Checkout: strip ONLY the “upload file required” error after all validations
  add_action('woocommerce_check_cart_items',          __NAMESPACE__ . '\\pe_strip_upload_required_cart', 999);
  add_action('woocommerce_after_checkout_validation', __NAMESPACE__ . '\\pe_strip_upload_required_checkout', 999, 2);

  // Order details (thank-you + view order)
  add_filter('woocommerce_order_item_thumbnail', __NAMESPACE__ . '\\pe_order_item_thumbnail', 99, 2); // preferred
  add_action('woocommerce_order_item_meta_end',  __NAMESPACE__ . '\\pe_output_order_thumb_marker', 10, 4); // marker for JS fallback
  add_action('wp_footer',                        __NAMESPACE__ . '\\pe_replace_order_thumbs_js', 99); // JS fallback that also wraps with <a>

  // Emails: use uploaded file URL if available (no links; some clients strip anchors)
  add_filter('woocommerce_email_order_item_thumbnail', __NAMESPACE__ . '\\pe_email_order_item_thumbnail', 99, 3);

  // Front-end assets (product page only)
  add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_editor_js');
}
setup();

/** =========================================================================
 * i18n / assets
 * ========================================================================= */
function load_textdomain()
{
  load_child_theme_textdomain('pe-textdomain', get_stylesheet_directory() . '/languages');
}
function enqueue_fontawesome()
{
  wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], null);
}
function debug_i18n()
{
  $locale = get_locale();
  $path   = get_stylesheet_directory() . '/languages/pe-textdomain-' . $locale . '.mo';
  error_log('Current Locale: ' . $locale);
  error_log('Expected MO Path: ' . $path);
  error_log('MO File Exists: ' . (file_exists($path) ? 'Yes' : 'No'));
  error_log(is_textdomain_loaded('pe-textdomain') ? 'pe-textdomain is loaded.' : 'pe-textdomain is NOT loaded.');
  error_log('Test Translation: ' . __('Rotate Left', 'pe-textdomain'));
}

/** =========================================================================
 * Hidden field inside add-to-cart form
 * ========================================================================= */
function output_form_field()
{
  echo '<input type="hidden" name="image_customization" id="pe-data">';
}

/** =========================================================================
 * Editor markup (outside the form)
 * ========================================================================= */
function render_editor()
{
  // Rileva user agent semplice per mobile
  $is_mobile = wp_is_mobile();

  // Set dimensioni canvas in base al device
  $canvas_size = $is_mobile ? 375 : 900;
?>
  <div id="pe-editor">
    <div id="pe-body">
      <img id="pe-border-img"
        src="<?php echo esc_url(get_stylesheet_directory_uri() . '/images/input-border-800.png'); ?>"
        alt="Border Frame"
        class="pe-border-frame"
        onerror="this.style.display='none'; document.getElementById('pe-border-fallback').style.display='block';" />

      <canvas id="pe-canvas" width="<?php echo $canvas_size; ?>" height="<?php echo $canvas_size; ?>" class="pe-empty-canvas"
        aria-label="<?php echo esc_attr__('Image preview canvas', 'pe-textdomain'); ?>" role="img"></canvas>

      <button id="pe-load-border" type="button">📷 <?php echo esc_html__('Add Image', 'pe-textdomain'); ?></button>
    </div>

    <div id="pe-controls">
      <button type="button" id="pe-rotate-left" class="pe-btn" title="<?php echo esc_attr__('Rotate left', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Rotate left', 'pe-textdomain'); ?>"><i class="fas fa-undo-alt" aria-hidden="true"></i><?php echo esc_html__('Rotate Left', 'pe-textdomain'); ?></button>
      <button type="button" id="pe-rotate-right" class="pe-btn" title="<?php echo esc_attr__('Rotate right', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Rotate right', 'pe-textdomain'); ?>"><i class="fas fa-redo-alt" aria-hidden="true"></i><?php echo esc_html__('Rotate Right', 'pe-textdomain'); ?></button>
      <button type="button" id="pe-zoom-out" class="pe-btn" title="<?php echo esc_attr__('Zoom out', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Zoom out', 'pe-textdomain'); ?>"><i class="fas fa-search-minus" aria-hidden="true"></i><?php echo esc_html__('Zoom Out', 'pe-textdomain'); ?></button>
      <button type="button" id="pe-zoom-in" class="pe-btn" title="<?php echo esc_attr__('Zoom in', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Zoom in', 'pe-textdomain'); ?>"><i class="fas fa-search-plus" aria-hidden="true"></i><?php echo esc_html__('Zoom In', 'pe-textdomain'); ?></button>
      <button type="button" id="pe-reset" class="pe-btn" title="<?php echo esc_attr__('Reset view', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Reset view', 'pe-textdomain'); ?>"><i class="fas fa-home" aria-hidden="true"></i><?php echo esc_html__('Reset View', 'pe-textdomain'); ?></button>
      <button type="button" id="pe-clear" class="pe-btn" title="<?php echo esc_attr__('Clear image', 'pe-textdomain'); ?>" aria-label="<?php echo esc_attr__('Clear image', 'pe-textdomain'); ?>"><i class="fas fa-trash" aria-hidden="true"></i><?php echo esc_html__('Clear', 'pe-textdomain'); ?></button>
    </div>

    <!-- Outside-form elements -->
    <input type="file" id="pe-file" accept="image/*" />
    <input type="hidden" id="pe-data-ui">
    <div id="pe-status-message" class="pe-status-message"></div>
  </div>
<?php
}

/** =========================================================================
 * Cart / Order meta
 * ========================================================================= */
function capture_cart_item_data($cart_item_data, $product_id, $variation_id)
{
  if (!empty($_POST['image_customization'])) {
    $raw     = wp_unslash($_POST['image_customization']);
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      $cart_item_data['image_customization'] = $decoded;
    }
  }
  return $cart_item_data;
}

function save_order_item_meta($item, $cart_item_key, $values, $order)
{
  if (empty($values['image_customization'])) return;

  $data = $values['image_customization'];

  // Prefer hi-res (finalImageFull), fallback to legacy finalImage
  $src = $data['finalImageFull'] ?? ($data['finalImage'] ?? '');

  // If we have a base64 image, save it to the Media Library and store its URL.
  if (!empty($src) && preg_match('/^data:image\/(\w+);base64,/', $src, $type)) {
    $blob = substr($src, strpos($src, ',') + 1);
    $ext  = strtolower($type[1]); // png/jpg/gif/webp...
    $bin  = base64_decode($blob);
    $file = 'custom_' . time() . '.' . $ext;

    $upload = wp_upload_bits($file, null, $bin);
    if (!$upload['error']) {
      $wp_filetype = wp_check_filetype($file, null);
      $attachment  = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($file),
        'post_content'   => '',
        'post_status'    => 'inherit',
      ];
      $attach_id = wp_insert_attachment($attachment, $upload['file']);

      require_once ABSPATH . 'wp-admin/includes/image.php';
      $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
      wp_update_attachment_metadata($attach_id, $attach_data);

      $data['finalImageURL'] = wp_get_attachment_url($attach_id);
    }
  }

  $item->add_meta_data('_image_customization', wp_json_encode($data), true);
}

/** =========================================================================
 * Helpers for clickable thumbnails
 * ========================================================================= */
function pe_safe_img_src($src)
{
  return (is_string($src) && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $src))
    ? $src
    : esc_url($src);
}
function pe_guess_filename_from_src($src)
{
  if (preg_match('#^data:image/(png|jpe?g|gif|webp)#i', $src, $m)) {
    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
    return 'customized-image.' . $ext;
  }
  $path = wp_parse_url($src, PHP_URL_PATH);
  if ($path) {
    $base = basename($path);
    if ($base) return sanitize_file_name($base);
  }
  return 'customized-image.png';
}
function pe_clickable_thumb_html($src, $alt = '', $max_w = 80)
{
  $safe = pe_safe_img_src($src);
  $dl   = pe_guess_filename_from_src($src);
  $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';
  $alt     = esc_attr($alt);
  return '<a href="' . $safe . '" class="pe-thumb-link" target="_blank" rel="noopener noreferrer nofollow" download="' . esc_attr($dl) . '">' .
    '<img src="' . $safe . '" class="' . esc_attr($classes) . '" alt="' . $alt . '" style="max-width:' . intval($max_w) . 'px;height:auto;border:1px solid #ddd;" />' .
    '</a>';
}

/**
 * NEW helpers:
 * - pe_get_custom_image_href: best link target (prefer uploaded URL → hi-res data → others)
 * - pe_get_custom_image_thumb_src: best thumbnail source (prefer tiny thumb)
 * - pe_clickable_thumb_pair: <a href=HIRES><img src=THUMB/></a>
 */
function pe_get_custom_image_href($item)
{
  $json = $item->get_meta('_image_customization');
  if (!$json) return '';
  $data = json_decode($json, true);
  if (!is_array($data)) return '';
  if (!empty($data['finalImageURL']))  return esc_url($data['finalImageURL']);
  if (!empty($data['finalImageFull'])) return $data['finalImageFull'];
  if (!empty($data['finalImage']))     return $data['finalImage'];
  if (!empty($data['finalImageThumb'])) return $data['finalImageThumb'];
  return '';
}
function pe_get_custom_image_thumb_src($item)
{
  $json = $item->get_meta('_image_customization');
  if (!$json) return '';
  $data = json_decode($json, true);
  if (!is_array($data)) return '';
  if (!empty($data['finalImageThumb'])) return $data['finalImageThumb'];
  if (!empty($data['finalImage']))      return $data['finalImage'];
  if (!empty($data['finalImageFull']))  return $data['finalImageFull'];
  if (!empty($data['finalImageURL']))   return esc_url($data['finalImageURL']);
  return '';
}
function pe_clickable_thumb_pair($img_src, $href, $alt = '', $max_w = 80)
{
  $img_safe  = pe_safe_img_src($img_src);
  $href_safe = pe_safe_img_src($href);
  $dl        = pe_guess_filename_from_src($href_safe ?: $img_safe);
  $classes   = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';
  $alt       = esc_attr($alt);
  return '<a href="' . $href_safe . '" class="pe-thumb-link" target="_blank" rel="noopener noreferrer nofollow" download="' . esc_attr($dl) . '">' .
    '<img src="' . $img_safe . '" class="' . esc_attr($classes) . '" alt="' . $alt . '" style="max-width:' . intval($max_w) . 'px;height:auto;border:1px solid #ddd;" />' .
    '</a>';
}

/** =========================================================================
 * Admin order screen preview (clickable)
 * ========================================================================= */
function show_admin_order_item_preview($product, $item, $item_id)
{
  $json = $item->get_meta('_image_customization');
  if (!$json) return;
  $data = json_decode($json, true);
  if (!is_array($data)) return;

  $thumb = $data['finalImageThumb'] ?? ($data['finalImage'] ?? ($data['finalImageFull'] ?? ''));
  $href  = $data['finalImageURL']  ?? ($data['finalImageFull'] ?? $thumb);
  if (empty($thumb)) return;

  $html = pe_clickable_thumb_pair($thumb, $href, __('Customized Image', 'pe-textdomain'), 160);

  echo '<div style="margin:8px 0; padding:8px; border:1px solid #eee; background:#fafafa;">';
  echo '<strong>' . esc_html__('Customization', 'pe-textdomain') . '</strong><br>';
  echo $html;
  echo '<div style="font-size:12px; color:#333; margin-top:6px;">';
  echo esc_html__('Rotation', 'pe-textdomain') . ': ' . esc_html($data['rotation'] ?? 0) . '°<br>';
  echo esc_html__('Zoom', 'pe-textdomain') . ': ' . esc_html($data['zoom'] ?? 1) . 'x<br>';
  echo esc_html__('Position', 'pe-textdomain') . ': (' . esc_html($data['positionX'] ?? 0) . ', ' . esc_html($data['positionY'] ?? 0) . ')<br>';
  echo '</div>';
  echo '</div>';
}

/** =========================================================================
 * Validation
 * ========================================================================= */
function validate_before_add_to_cart($passed, $product_id, $quantity)
{
  if (empty($_POST['image_customization'])) {
    wc_add_notice(__('Please upload and apply your image customization before adding to cart.', 'pe-textdomain'), 'error');
    return false;
  }
  return $passed;
}

/** =========================================================================
 * Cart / Checkout UX
 * ========================================================================= */

// Cart/Mini-cart/Checkout left thumbnail (clickable)
// NOW: small src (thumb) but link/download points to hi-res
function pe_cart_item_thumbnail($image, $cart_item, $cart_item_key)
{
  $data = $cart_item['image_customization'] ?? [];
  if (empty($data)) return $image;

  $thumb = $data['finalImageThumb'] ?? ($data['finalImage'] ?? '');
  $href  = $data['finalImageURL']  ?? ($data['finalImageFull'] ?? $thumb);
  if (empty($thumb)) return $image;

  return pe_clickable_thumb_pair($thumb, $href, __('Customized Image Preview', 'pe-textdomain'), 80);
}

// Detect “upload required” messages (EN/IT + loose fallback)
function pe_is_upload_required_message($msg): bool
{
  $plain = wp_strip_all_tags((string) $msg);
  $plain = trim(preg_replace('/\s+/', ' ', $plain));
  $hit_en = (stripos($plain, 'upload your file') !== false && stripos($plain, 'required') !== false);
  $hit_it = (stripos($plain, 'carica il tuo file') !== false && stripos($plain, 'obbligatorio') !== false);
  $loose  = ((stripos($plain, 'upload') !== false || stripos($plain, 'file') !== false)
    && (stripos($plain, 'required') !== false || stripos($plain, 'obbligatorio') !== false));
  return $hit_en || $hit_it || $loose;
}

// Cart: remove only that message after all checks
function pe_strip_upload_required_cart()
{
  $errors = wc_get_notices('error');
  if (empty($errors)) return;

  $keep = [];
  $changed = false;
  foreach ($errors as $err) {
    $msg = is_array($err) && isset($err['notice']) ? $err['notice'] : $err;
    if (pe_is_upload_required_message($msg)) {
      $changed = true;
      continue;
    }
    $keep[] = $msg;
  }
  if ($changed) {
    wc_clear_notices();
    foreach ($keep as $m) wc_add_notice($m, 'error');
  }
}

// Checkout: remove only that message from WC_Error
function pe_strip_upload_required_checkout($data, $errors)
{
  if (empty($errors) || !is_object($errors) || !method_exists($errors, 'get_error_codes')) return;

  foreach ($errors->get_error_codes() as $code) {
    $messages = $errors->get_error_messages($code);
    $keep_any = [];
    $drop     = false;
    foreach ($messages as $m) {
      if (pe_is_upload_required_message($m)) $drop = true;
      else $keep_any[] = $m;
    }
    if ($drop) {
      $errors->remove($code);
      foreach ($keep_any as $km) $errors->add($code, $km);
    }
  }
}

/** =========================================================================
 * Order details (thank-you + view order) & Emails
 * ========================================================================= */

// Helper: best image for order item (prefer uploaded URL)
// For backward compatibility this now returns the link target (hi-res when possible)
function pe_get_order_item_custom_image_src($item)
{
  return pe_get_custom_image_href($item);
}

// Preferred replacement (if theme calls this filter)
// Use thumb for <img src>, but link to hi-res href
function pe_order_item_thumbnail($image, $item)
{
  $thumb = pe_get_custom_image_thumb_src($item);
  $href  = pe_get_custom_image_href($item);
  if (!$thumb || !$href) return $image;
  return pe_clickable_thumb_pair($thumb, $href, __('Customized Image Preview', 'pe-textdomain'), 80);
}

// Fallback: print a hidden marker with the src (thumb) + href (hi-res) for each order line
function pe_output_order_thumb_marker($item_id, $item, $order, $plain_text)
{
  $thumb = pe_get_custom_image_thumb_src($item);
  $href  = pe_get_custom_image_href($item);
  if (!$thumb || !$href) return;
  $safe_src  = pe_safe_img_src($thumb);
  $safe_href = pe_safe_img_src($href);
  echo '<span class="pe-order-thumb" data-src="' . esc_attr($safe_src) . '" data-href="' . esc_attr($safe_href) . '" style="display:none"></span>';
}

// JS fallback: swap/inject the left thumbnail and wrap with <a> (thank-you + view order)
function pe_replace_order_thumbs_js()
{
  $is_view_order = function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('view-order');
  if (!(function_exists('is_order_received_page') && is_order_received_page()) && !$is_view_order) return;
?>
  <script>
    (function() {
      var markers = document.querySelectorAll('.pe-order-thumb[data-src]');
      if (!markers.length) return;

      function wrapWithLink(img, href) {
        var a = document.createElement('a');
        a.href = href;
        a.target = '_blank';
        a.rel = 'noopener noreferrer nofollow';
        a.className = 'pe-thumb-link';
        a.setAttribute('download', 'customized-image.png');
        img.parentNode.insertBefore(a, img);
        a.appendChild(img);
        return a;
      }

      markers.forEach(function(marker) {
        var src = marker.getAttribute('data-src');
        var href = marker.getAttribute('data-href') || src;
        if (!src) return;
        var tr = marker.closest('tr');
        if (!tr) return;

        var img =
          tr.querySelector('td.product-thumbnail img') ||
          tr.querySelector('td.woocommerce-table__product-name img') ||
          tr.querySelector('img');

        if (img) {
          // ensure the img uses our src (thumb)
          img.removeAttribute('srcset');
          img.removeAttribute('sizes');
          img.src = src;
          img.style.maxWidth = '80px';
          img.style.height = 'auto';
          img.style.border = '1px solid #ddd';
          if (!img.className) img.className = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';

          // wrap in anchor if not already linked, and point to hi-res href
          var parent = img.parentNode;
          if (!parent || parent.tagName.toLowerCase() !== 'a') {
            wrapWithLink(img, href);
          } else {
            parent.setAttribute('href', href);
            parent.setAttribute('target', '_blank');
            parent.setAttribute('rel', 'noopener noreferrer nofollow');
            parent.setAttribute('download', 'customized-image.png');
          }
        } else {
          // no image present: inject linked thumbnail into product name cell
          var cell = tr.querySelector('td.product-name, td.woocommerce-table__product-name');
          if (cell) {
            var a = document.createElement('a');
            a.href = href;
            a.target = '_blank';
            a.rel = 'noopener noreferrer nofollow';
            a.className = 'pe-thumb-link';
            a.setAttribute('download', 'customized-image.png');

            var el = document.createElement('img');
            el.src = src;
            el.className = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';
            el.style.maxWidth = '80px';
            el.style.height = 'auto';
            el.style.border = '1px solid #ddd';
            el.style.marginRight = '8px';
            el.style.verticalAlign = 'middle';

            a.appendChild(el);
            cell.insertBefore(a, cell.firstChild);
          }
        }
      });
    })();
  </script>
<?php
}

// Emails: use uploaded URL if available (no links; some clients strip anchors)
function pe_email_order_item_thumbnail($image, $item, $email)
{
  $json = $item->get_meta('_image_customization');
  if (!$json) return $image;
  $data = json_decode($json, true);
  if (!is_array($data) || empty($data['finalImageURL'])) return $image;

  $safe_src = esc_url($data['finalImageURL']);
  $classes  = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';
  $alt      = esc_attr__('Customized Image Preview', 'pe-textdomain');

  return '<img src="' . $safe_src . '" class="' . esc_attr($classes) . '" alt="' . $alt . '" style="max-width:80px;height:auto;border:1px solid #ddd;" />';
}

/** =========================================================================
 * Front-end assets
 * ========================================================================= */
function enqueue_editor_js()
{
  if (!is_product()) return;

  // CSS
  $css_path = get_stylesheet_directory() . '/css/product-editor.css';
  wp_enqueue_style(
    'product-editor-css',
    get_stylesheet_directory_uri() . '/css/product-editor.css',
    [],
    file_exists($css_path) ? filemtime($css_path) : null
  );

  // JS
  $handle = 'product-editor';
  $src    = get_stylesheet_directory_uri() . '/js/product-editor.js';
  $path   = get_stylesheet_directory() . '/js/product-editor.js';
  $ver    = file_exists($path) ? filemtime($path) : null;

  wp_enqueue_script($handle, $src, [], $ver, true);

  wp_localize_script($handle, 'peVars', [
    'borderImageUrl' => get_stylesheet_directory_uri() . '/images/input-border.png',
    'ajaxUrl'        => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('pe_nonce'),
    'strings'        => [
      'imageLoaded' => __('Image loaded successfully!', 'pe-textdomain'),
      'imageCleared' => __('Image cleared.', 'pe-textdomain'),
      'invalidFile' => __('Please select a valid image file.', 'pe-textdomain'),
      'loadError'   => __('Error loading image. Please try another file.', 'pe-textdomain'),
    ],
  ]);
}
