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

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
